<?php

namespace App\Http\Controllers;

use PDF;
use Carbon\Carbon;
use App\Models\Box;
use App\Models\Entry;
use App\Models\Credit;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;


class EntryController extends Controller
{

	public function __construct()
	{
		$this->middleware('permission:entry.index', ['only' => ['index', 'show']]);
		$this->middleware('permission:entry.store', ['only' => ['store']]);
		$this->middleware('permission:entry.update', ['only' => ['update']]);
		$this->middleware('permission:entry.delete', ['only' => ['destroy']]);
		$this->middleware('permission:entry.status', ['only' => ['changeStatus']]);
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$id = $request->id;
		$client = $request->client;
		$user_id = $request->user_id;
		$document = $request->document;
		$from = $request->from;
		$to = $request->to;
		$this_month = Carbon::now()->month;
		$type_input = $request->type_input;
		$description = $request->description;
		$results = $request->results ?? 15;

		$entries = Entry::select()
			->orderBy('id', 'desc')
			->with(['user:id,name,last_name', 'headquarter', 'credit.client']);

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

				if ($from != '' && $from != 'undefined' && $from != null) {
					$from = Carbon::parse($from)->format('Y-m-d');
					$query->whereDate('date', '>=', $from);
				}

				if ($to != '' && $to != 'undefined' && $to != null) {
					$to = Carbon::parse($to)->format('Y-m-d');
					$query->whereDate('date', '<=', $to);
				}

				if (is_null($from) && is_null($to)) {
					$query->whereMonth('date', '<=', $this_month);
				};
			});

		if ($type_input != null) {
			$entries =	$entries->where('type_entry', 'LIKE', "%$type_input%");
		}
		if ($user_id != null) {
			$entries =	$entries->where('user_id', "%$user_id%");
		}
		if ($description != null) {
			$entries =	$entries->where('description', 'LIKE', "%$description%");
		}
		if ($id != null) {
			$entries =	$entries->where('id', "$id");
		}
		$entries = $entries->paginate($results);

		$getTotalReportsController = new GetTotalReportsController;
		$totals = $getTotalReportsController->getTotalReportHeadquartersEntries($entries);

		return [
			'entries' => $entries,
			'totals' => $totals,
		];
	}
	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
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
		$entry->type_entry = $request['type_entry'];
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
		$client = $credit ? $credit->client()->first() : NULL;
		$product = $credit ?  $credit->product()->first() : NULL;
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

	public function destroy(Request $request, Entry $entry)
	{
		$request->merge(['add_amount' => $entry->price]);
		try {
			$box = Box::where('headquarter_id', $entry->headquarter_id)->firstOrFail();

			$update_box_heaquarter = new BoxController();
			$update_box_heaquarter->subAmountBox($request, $box->id, $entry->price);

			$entry->delete();

			return response()->json([
				'status' => 'success',
				'code' => 200,
				'message' => 'Entrada borrada exitosamente',
				'entry' => $entry
			]);
		} catch (\Throwable $th) {
			return response()->json([
				'status' => 'failure',
				'error' => $th->getMessage(),
				'code' => 400,
				'message' => 'Hubo un error al eliminar esta entrada',
				'entry' => $entry
			]);
		}
	}
}
