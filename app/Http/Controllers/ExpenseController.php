<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Company;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
	public function __construct()
	{
		$this->middleware('permission:expense.index', ['only' => ['index', 'show']]);
		$this->middleware('permission:expense.store', ['only' => ['store']]);
		$this->middleware('permission:expense.update', ['only' => ['update']]);
		$this->middleware('permission:expense.delete', ['only' => ['destroy']]);
		$this->middleware('permission:expense.status', ['only' => ['changeStatus']]);
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function index(Request $request)
	{
		$from = $request->from;
		$to = $request->to;
		$this_month = Carbon::now()->month;
		$type_output = $request->type_output;
		$description = $request->description;

		$expenses = Expense::with('user:id,name,last_name', 'headquarter:id,headquarter')
			->where(function ($query) use ($this_month, $from, $to) {

				$query->whereMonth('date', '<=', $this_month);

				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('date', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('date', '<=', $to);
				}
			});
		if ($type_output != null) {
			$expenses =	$expenses->where('type_output', 'LIKE', "%$type_output%");
		}
		if ($description != null) {
			$expenses =	$expenses->where('description', 'LIKE', "%$description%");
		}
		$expenses =	$expenses->paginate(15);

		return $expenses;
	}


	public function changeStatus(Expense $expense)
	{
		$ex = Expense::find($expense->id);
		$ex->status = !$ex->status;
		$ex->save();
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
			'type_output' => 'required|string|exists:type_expenses,description',
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


		$expense =  new Expense();
		$expense->headquarter_id = $request->user()->headquarter_id;
		$expense->user_id = $request->user()->id;
		$expense->status = 1;
		$expense->description = $request['description'];
		$expense->date = $request['date'];
		$expense->type_output = $request['type_output'];
		$expense->price = $request['price'];
		$expense->save();

		$box = Box::where('headquarter_id', $expense->headquarter_id)->firstOrFail();
		$sub_amount_box = new BoxController();
		$sub_amount_box->subAmountBox($box->id, $request['price']);

		return response()->json([
			'status' => 'success',
			'code' =>  200,
			'message' => 'Registro exitoso',
			'expense' =>  $expense
		], 200);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Client  $client
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Expense $expense)
	{
		$validate = Validator::make($request->all(), [
			'description' => 'required|string|max:255',
			'date' => 'required|date',
			'type_output' => 'required|string|exists:type_expenses,description',
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$expense->description = $request['description'];
		$expense->date = $request['date'];
		$expense->type_output = $request['type_output'];
		$expense->update();

		return response()->json([
			'status' => 'success',
			'code' =>  200,
			'message' => 'Registro exitoso',
			'expense' =>  $expense
		], 200);
	}

	public function showExpense(Expense $expense)
	{
		$company = Company::first();
		$headquarter = $expense->headquarter()->first();
		$user = $expense->user()->first();

		$details = [
			'company' => $company,
			'expense' => $expense,
			'headquarter' => $headquarter,
			'user' => $user,
			'url' => URL::to('/')
		];

		$pdf = PDF::loadView('templates.expense_information', $details);
		$pdf = $pdf->download('expense_information.pdf');

		$data = [
			'status' => 200,
			'pdf' => base64_encode($pdf),
			'message' => 'Tabla generada en pdf'
		];

		return response()->json($data);
	}

	public function addExpense($user_id, $headquarter_id, $description, $date, $type_output, $price)
	{
		$expense =  new Expense();
		$expense->headquarter_id = $headquarter_id;
		$expense->user_id = $user_id;
		$expense->status = 1;
		$expense->description = $description;
		$expense->date = $date;
		$expense->type_output = $type_output;
		$expense->price = $price;
		$expense->save();

	}
}
