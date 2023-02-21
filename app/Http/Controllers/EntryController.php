<?php

namespace App\Http\Controllers;

use PDF;
use Carbon\Carbon;
use App\Models\Box;
use App\Models\Entry;
use App\Models\Credit;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class EntryController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$id = $request->id;
		$client = $request->client;
		$document = $request->document;
		$from = $request->from;
		$to = $request->to;
		$this_month = Carbon::now()->month;
		$type_input = $request->type_input;
		$description = $request->description;

		$entries = Entry::select()
			->orderBy('id', 'desc')
			->with(['user:id,name,last_name','headquarter']);

		if ($client != null || $document != null) {
			$entries = $entries->select('entries.*', 'clients.name', 'clients.last_name', 'clients.document')
				->leftJoin('credits', 'entries.credit_id', 'credits.id')
				->leftJoin('clients', 'credits.client_id', 'clients.id');

			if ($client != null) {
				$entries = $entries->where('clients.name', 'LIKE', "%$client%")
					->orWhere('clients.last_name', 'LIKE', "%$client%");
			}
			if ($document != null) {
				$entries = $entries->where('clients.document', 'LIKE', "$document%");
			}
		};

		$entries = $entries
			->where(function ($query) use ($this_month, $from, $to) {

				$query->whereMonth('date', '<=', $this_month);

				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('date', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('date', '<=', $to);
				}
			});
		if ($type_input != null) {
			$entries =	$entries->where('type_entry', 'LIKE', "%$type_input%");
		}
		if ($description != null) {
			$entries =	$entries->where('description', 'LIKE', "%$description%");
		}

		if ($id != null) {
			$entries =	$entries->where('id', "$id");
		}
		$entries = $entries->paginate(15);
		return $entries;
	}
	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		// $credit = Credit::findOrFail($request->data['credit_id']);

		$validate = Validator::make($request->all(), [
			'description' => 'required|string|max:255',
			'date' => 'required|date',
			'type_entry' => 'required|string',
			'price' => 'required|numeric',
		]);


		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$entry =  new Entry();
		$entry->headquarter_id = $request->user()->headquarter_id;
		$entry->user_id = $request->user()->id;
		$entry->credit_id = $credit->id ?? NULL;
		$entry->description = $request['description'];
		$entry->date = date('Y-m-d');
		$entry->type_entry = $request['type_entry'];
		$entry->price =  $request['price'];


		if ($entry->save()) {
			$box = Box::where('headquarter_id', $entry->headquarter_id)->firstOrFail();
			$add_amount_box = new BoxController();
			$add_amount_box->addAmountBox($request, $box->id, $request['price']);
		}
		
		return response()->json([
			'status' => 'success',
			'code' =>  200,
			'message' => 'Registro exitoso',
			'entry' =>  $entry
		], 200);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Entry  $entry
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Entry $entry)
	{
		$credit = Credit::findOrFail($request->data['credit_id']);
		$client = $credit->client()->first();

		$validate = Validator::make($request->all(), [
			'description' => 'required|string|max:255',
			'date' => 'required|date',
			'type_entry' => 'required|string',
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$entry->description = $request['description'];
		$entry->date = $request['date'];
		$entry->type_entry = $request['type_output'];
		$entry->update();

		return response()->json([
			'status' => 'success',
			'code' =>  200,
			'message' => 'Registro exitoso',
			'entry' =>  $entry
		], 200);
	}

	public function showEntry(Request $request, Entry $entry)
	{
		$company = Company::first();

		$headquarter = $entry->headquarter()->first();
		$credit = $entry->credit()->first();
		$client = $credit ? $credit->client()->first():NULL;
		$product = $credit ?  $credit->product()->first():NULL;
		$user = $request->user();

		$details = [
			'company' => $company,
			'credit' => $credit,
			'client' => $client,
			'entry' => $entry,
			'headquarter' => $headquarter,
			'product' => $product,
			'user' => $user,
			'url' => URL::to('/')
		];

		if ($company->method == 'FRANCHISE') {
			$pdf = PDF::loadView('templates.entry_information', $details);
		} else {
			$pdf = PDF::loadView('templates.entry_information_general_method', $details);
		}

		$pdf = $pdf->download('entry_information.pdf');

		$data = [
			'status' => 200,
			'pdf' => base64_encode($pdf),
			'message' => 'Tabla generada en pdf'
		];

		// return $details;
		return response()->json($data);
	}
}
