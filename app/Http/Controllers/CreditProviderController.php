<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\CreditProvider;
use App\Models\Expense;
use App\Models\MainBox;
use Illuminate\Http\Request;

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
		$credit_providers = CreditProvider::select()
			->where('status', 1)
			->paginate(10);
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
		$credit_provider->last_editor = 1;
		$credit_provider->credit_id = $credit_id;
		$credit_provider->provider_id = $request['provider_id'];
		$credit_provider->headquarter_id = $request['headquarter_id'];
		$credit_provider->last_editor = 1;
		$credit_provider->credit_value = $request['credit_value'];
		$credit_provider->paid_value = 0;
		$credit_provider->pending_value = $request['credit_value'];
		$credit_provider->last_paid = date('Y-m-d');
		$credit_provider->history = json_encode($history);
		$credit_provider->save();
	}

	public function payCreditProvider(CreditProvider $credit_provider, Request $request)
	{

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
		"#Cliente:  $client->name $client->last_name  \n".
		"#Proveedor:  $provider->business_name \n";

		$expense->date = date('Y-m-d');
		$expense->type_output = 'Pago a proveedor';
		$expense->price = $request['amount'];
		$expense->save();

		$update_main_box = new MainBoxController();
		$update_main_box->subAmountMainBox($request['amount']);

		if ($credit_provider->pending_value <= 0) {
			$credit = $credit_provider->credit()->first();
			$credit->status = 1;
			$credit->save();
		}
	}
}
