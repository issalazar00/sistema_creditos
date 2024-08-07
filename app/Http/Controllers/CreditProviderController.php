<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\CreditProvider;
use App\Models\CreditProviderPayment;
use App\Models\Expense;
use App\Models\MainBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreditProviderController extends Controller
{

	public function __construct()
	{
		$this->middleware('permission:provider.index', ['only' => ['index', 'show']]);
		$this->middleware('permission:provider.store', ['only' => ['store']]);
		$this->middleware('permission:provider.update', ['only' => ['update', 'payCreditProvider']]);
		$this->middleware('permission:provider.delete', ['only' => ['destroy']]);
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$search_status = $request->search_status ?? -1;
		$search_from = $request->search_from ?? NULL;
		$search_to = $request->search_to ?? NULL;
		$provider_id = $request->search_provider_id ?? NULL;
		$results = $request->results ?? 10; 

		$credit_providers = CreditProvider::select();

		if ($search_status != '-1') {
			if(!$search_status){
				$credit_providers = $credit_providers->where('status', $search_status)->where('pending_value', '>',0);
			} 
			$credit_providers = $credit_providers->where('status', $search_status);
		}

		if (!is_null($provider_id)) {
			$credit_providers = $credit_providers->where('provider_id', $provider_id);
		}

		if ($search_from) {
			$credit_providers = $credit_providers->where('created_at', '>=', $search_from);
		}

		if ($search_to) {
			$credit_providers = $credit_providers->where('created_at', '<=', $search_to);
		}

		$credit_providers = $credit_providers->orderBy('created_at', 'desc')->with('creditProviderPayments')->paginate($results);
		return $credit_providers;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request, $credit_id)
	{
		$credit_provider = new CreditProvider();

		if ($credit_provider->history != null) {
			$history = (array) json_decode($credit_provider->history);
		} else {
			$history = array();
		}
		$credit_provider->last_editor = $request->user()->id;
		$credit_provider->credit_id = $credit_id;
		$credit_provider->provider_id = $request['provider_id'];
		$credit_provider->headquarter_id = $request['headquarter_id'];
		$credit_provider->credit_value = $request['credit_requested'] ? $request['credit_requested'] : $request['credit_value'];
		$credit_provider->paid_value = 0;
		$credit_provider->pending_value =  $request['credit_requested'] ? $request['credit_requested'] : $request['credit_value'];
		$credit_provider->last_paid = date('Y-m-d');
		$credit_provider->history = json_encode($history);
		$credit_provider->save();
	}

	public function payCreditProvider(CreditProvider $credit_provider, Request $request)
	{
		$validate = Validator::make($request->all(), [
			'amount' => 'required|numeric|min:1',
			'description' => 'nullable|string',
			'evidence.*' => 'nullable|file|mimes:jpeg,jpg,png,pdf',
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$user = $request->user();
		$data = ([
			'Asesor' => "$user->name $user->last_name",
			'Fecha' => date('Y-m-d'),
			'Monto' => $request['amount']
		]);
		if ($credit_provider->history != null) {
			$history = (array) json_decode($credit_provider->history);
		} else {
			$history = array();
		}
		array_push($history, $data);

		$credit_provider->paid_value = $credit_provider->paid_value + $request['amount'];
		$credit_provider->pending_value = $credit_provider->pending_value - $request['amount'];
		$credit_provider->history = json_encode($history);
		$credit_provider->save();


		$creditProviderPayment = new CreditProviderPayment();
		$creditProviderPayment->credit_provider_id = $credit_provider->id;
		$creditProviderPayment->adviser = "$user->name $user->last_name";
		$creditProviderPayment->paid_value = $request['amount'];
		$creditProviderPayment->description = $request['description'];

		if ($request->hasFile('evidence')) {
			$name = uniqid() . $request->file('evidence')->getClientOriginalName();
			$request->evidence->move(public_path('storage/evidences'), $name);
			$creditProviderPayment->evidence = 'storage/evidences/' . $name;
		}

		$creditProviderPayment->save();

		$credit = $credit_provider->credit()->first();
		$client = $credit->client()->first();
		$provider = $credit->provider()->first();

		$expense =  new Expense();
		$expense->headquarter_id = $request->user()->headquarter_id;
		$expense->user_id = $request->user()->id;
		$expense->status = 1;
		$expense->description = "Abono a proveedor\n" .
			"#Credito proveedor:  $credit_provider->id \n" .
			"#Credito:  $credit->id \n" .
			"#Cliente:  $client->name $client->last_name  \n" .
			"#Proveedor:  $provider->business_name \n";

		$expense->date = date('Y-m-d');
		$expense->type_output = 'Pago a proveedor';
		$expense->type_movement = 'EGRESO';
		$expense->price = $request['amount'];
		$expense->save();

		$update_main_box = new MainBoxController();
		$update_main_box->subAmountMainBox($request, $request['amount']);

		if ($credit_provider->pending_value <= 0) {
			$credit = $credit_provider->credit()->first();
			$credit->status = 1;
			$credit->save();
		}
	}
}
