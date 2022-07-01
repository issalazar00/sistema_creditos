<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Company;
use App\Models\Credit;
use App\Models\CreditProvider;
use App\Models\Entry;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PDF;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreditController extends Controller
{

	public function __construct()
	{
		$this->middleware('auth:api')->except('index');
		$this->middleware('permission:credit.index')->only('installments', 'generalInformation', 'show');
		$this->middleware('permission:credit.store')->only('store');
		$this->middleware('permission:credit.update')->only('update', 'updateValuesCredit');
		$this->middleware('permission:credit.delete')->only('delete');
		$this->middleware('permission:credit.status')->only('changeStatus');
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{

		$credits = Credit::select();
		$status = $request->status != null ? $request->status : 1;
		$status = $status == 0 ? [0, 3] : [$request->status];

		if ($request->credit && ($request->credit != '')) {
			$credits  =   $credits->leftjoin('clients as c', 'c.id', 'credits.client_id')
				->select('credits.*', 'credits.id as id', 'c.name', 'c.last_name', 'c.document', 'c.type_document', 'c.phone_1', 'c.phone_2')
				->where('document', 'LIKE', "%$request->credit%")
				->orWhere('name', 'LIKE', "%$request->credit%")
				->orWhere('email', 'LIKE', "%$request->credit%")
				->orWhere('last_name', 'LIKE', "%$request->credit%")
				->whereIn('credits.status', $status);
		} else {
			$credits  =     $credits->leftjoin('clients as c', 'c.id', 'credits.client_id')
				->select('credits.*', 'credits.id as id', 'c.name', 'c.last_name', 'c.document', 'c.type_document', 'c.phone_1', 'c.phone_2')
				->whereIn('credits.status', $status);
		}
		$credits = $credits
			->orderBy('id', 'desc')
			->with('headquarter:id,headquarter')
			->paginate(10);
		return $credits;
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create($id)
	{
		//
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
			'client_id' => 'required|integer|exists:clients,id',
			'provider' => 'nullable|boolean',
			'debtor' => 'nullable|boolean',
			'provider_id' => [
				'nullable',
				Rule::requiredIf($request->provider == 1),
				'exists:providers,id'
			],
			'debtor_id' => [
				'nullable',
				Rule::requiredIf($request->debtor == 1),
				'exists:clients,id'
			],
			'headquarter_id' => 'required|integer|exists:headquarters,id',
			'number_installments' => 'required|integer',
			'number_paid_installments' => 'nullable|integer',
			'day_limit' => 'nullable|integer',
			'start_date' => 'required|date',
			'interest' => 'required|numeric',
			'annual_interest_percentage' => 'nullable|numeric',
			'credit_value' =>  'required|numeric',
			'paid_value' => 'nullable|numeric',
			'capital_value' => 'nullable|numeric',
			'interest_value' => 'nullable|numeric',
			'description' => 'nullable|string',
			'disbursement_date' => 'nullable|date',
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$validate = Validator::make($request->all(), [
			'client_id' => 'required|integer|exists:clients,id',
			'provider' => 'nullable|boolean',
			'debtor' => 'nullable|boolean',
			'provider_id' => [
				'nullable',
				Rule::requiredIf($request->provider == 1),
				'exists:providers,id'
			],
			'debtor_id' => [
				'nullable',
				Rule::requiredIf($request->debtor == 1),
				'exists:clients,id'
			],
			'headquarter_id' => 'required|integer|exists:headquarters,id',
			'number_installments' => 'required|integer',
			'number_paid_installments' => 'nullable|integer',
			'day_limit' => 'nullable|integer',
			'start_date' => 'required|date',
			'interest' => 'required|numeric',
			'annual_interest_percentage' => 'nullable|numeric',
			'credit_value' =>  'required|numeric',
			'paid_value' => 'nullable|numeric',
			'capital_value' => 'nullable|numeric',
			'interest_value' => 'nullable|numeric',
			'description' => 'nullable|string',
			'disbursement_date' => 'nullable|date',
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$listInstallments = new InstallmentController();
		$listInstallments = $listInstallments->calculateInstallments($request);


		$credit = new Credit();
		$credit->client_id = $request['client_id'];
		$credit->provider_id = $request['provider_id'];
		$credit->debtor_id = $request['debtor_id'];
		$credit->user_id = $request->user()->id;
		$credit->debtor = $request['debtor'] ?  $request['debtor'] : false;
		$credit->provider = $request['provider'] ?  $request['provider'] : false;
		if ($request['provider'] != null && $request['provider'] != 0) {
			$credit->status = 3;
		} else {
			$credit->status = 0;
		}
		$credit->headquarter_id = $request->user()->headquarter_id;
		$credit->number_installments = $request['number_installments'];
		$credit->number_paid_installments = $request['number_paid_installments'];
		$credit->day_limit = $request['day_limit'];
		$credit->start_date = $request['start_date'];
		$credit->interest = $request['interest'];
		$credit->annual_interest_percentage = $request['annual_interest_percentage'];
		$credit->credit_value = $request['credit_value'];
		$credit->paid_value = $request['paid_value'];
		$credit->capital_value = $request['capital_value'];
		$credit->interest_value = $request['interest_value'];
		$credit->description = $request['description'];
		$credit->installment_value = $listInstallments['installment'];

		if ($credit->save()) {
			foreach ($listInstallments['listInstallments'] as $new_installment) {
				$installment = new Installment();
				$installment->credit_id = $credit->id;
				$installment->installment_number = $new_installment['installment_number'];
				$installment->value = $new_installment['installment_value'];
				$installment->payment_date = $new_installment['payment_date'];
				$installment->interest_value = $new_installment['pagoInteres'];
				$installment->capital_value = $new_installment['pagoCapital'];
				$installment->capital_balance = $new_installment['saldo_capital'];
				if (!$installment->save()) {
					Credit::findOrFail($credit->id)->delete();
					return false;
				}
			}
			if ($request['provider_id'] != NULL && $request['provider_id'] != 0) {
				$credit_provider = new CreditProviderController();
				$credit_provider->store($request, $credit->id);
			}
		}

		return response()->json([
			'status' => 'success',
			'code' =>  200,
			'message' => 'Registro exitoso',
			'credit' => $credit
		], 200);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\Credit  $credit
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Credit $credit)
	{
		$validate = Validator::make($request->all(), [
			'client_id' => 'required|integer|exists:clients,id',
			'provider' => 'nullable|boolean',
			'debtor' => 'nullable|boolean',
			'provider_id' => [
				'nullable',
				Rule::requiredIf($request->provider == 1),
				'exists:providers,id'
			],
			'debtor_id' => [
				'nullable',
				Rule::requiredIf($request->debtor == 1),
				'exists:clients,id'
			],
			'headquarter_id' => 'required|integer|exists:headquarters,id',
			'number_installments' => 'required|integer',
			'number_paid_installments' => 'nullable|integer',
			'day_limit' => 'nullable|integer',
			'start_date' => 'required|date',
			'interest' => 'required|numeric',
			'annual_interest_percentage' => 'nullable|numeric',
			'credit_value' =>  'required|numeric',
			'paid_value' => 'nullable|numeric',
			'capital_value' => 'nullable|numeric',
			'interest_value' => 'nullable|numeric',
			'description' => 'nullable|string',
			'disbursement_date' => 'nullable|date',
			'installment_value' => 'required|numeric'
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$credit = Credit::find($request->id);
		$credit->client_id = $request['client_id'];
		$credit->provider_id = $request['provider_id'];
		$credit->debtor_id = $request['debtor_id'];
		$credit->debtor = $request['debtor'];
		$credit->provider = $request['provider'];
		$credit->headquarter_id = $request['headquarter_id'];
		$credit->number_installments = $request['number_installments'];
		$credit->number_paid_installments = $request['number_paid_installments'];
		$credit->day_limit = $request['day_limit'];
		$credit->start_date = $request['start_date'];
		$credit->interest = $request['interest'];
		$credit->annual_interest_percentage = $request['annual_interest_percentage'];
		//$credit->user_id = $request->user()->id;
		$credit->installment_value = $request['installment_value'];
		$credit->description = $request['description'];

		$credit->save();

		return response()->json([
			'status' => 'success',
			'code' =>  200,
			'message' => 'Registro exitoso',
			'credit' => $credit
		], 200);
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Credit  $credit
	 * @return \Illuminate\Http\Response
	 */

	public function destroy($id)
	{
		$credit = Credit::findOrFail($id);
		$credit->destroy($id);
		return redirect('credit')->with('mensaje', 'Credito eliminado correctamente');
	}

	public function changeStatus(Request $request,  Credit $credit)
	{
		$credit_provider = '';
		if ($credit->provider_id != null && $credit->provider_id != 0) {
			$credit_provider = CreditProvider::where('credit_id', $credit->id)->first();

			if ($credit_provider != null) {
				if ($credit_provider->pending_value <= 0) {
					if ($request->status  == 1) {
						$update_main_box = new MainBoxController();
						$update_main_box->subAmountMainBox($credit->credit_value);
						$credit->disbursement_date = date('Y-m-d');
					}

					if ($request->status  == 2 && $credit->status == 1) {
						$update_main_box = new MainBoxController();
						$update_main_box->addAmountMainBox($credit->credit_value);
					}
				}
				$credit->status = $request->status;
				$credit_provider->status = $request->status;
				$credit_provider->save();
			}
		}

		if ($credit->provider_id == null || $credit->provider_id == 0) {
			if ($request->status  == 1) {
				$update_main_box = new MainBoxController();
				$update_main_box->subAmountMainBox($credit->credit_value);
				$credit->disbursement_date = date('Y-m-d');
			}
			if ($request->status  == 2 && $credit->status == 1) {
				$update_main_box = new MainBoxController();
				$update_main_box->addAmountMainBox($credit->credit_value);
			}
			$credit->status = $request->status;
		}

		if ($request->status  == 2 && $request->description != null) {
			$credit->description  = "$credit->description \nRechazado por: $request->description";
		}
		$credit->save();
	}

	public function installments(Request $request, $id)
	{
		$credit = Credit::findOrFail($id);
		$installments =  $credit->installments()->get();
		foreach ($installments as $installment) {
			$now = now();
			$payment_date = Carbon::createFromFormat('Y-m-d', $installment->payment_date);

			if ($payment_date < $now) {
				$days_past_due = $installment->days_past_due ? $installment->days_past_due :  $now->diffInDays($payment_date);
				$day_value_default = $installment->interest_value / 30;
				$late_interests_actual =  $days_past_due > 30 ?  $day_value_default * 30 : $day_value_default * $days_past_due;
				$late_interests_value = $installment->late_interests_value ? $installment->late_interests_value : $late_interests_actual;

				$installment->days_past_due  = $days_past_due > 30 ? 30 : $days_past_due;
				$installment->late_interests_value  =  $late_interests_value;
				if ($installment->paid_capital > 0 && $installment->paid_capital < $installment->capital_value) {
					$installment->value = $installment->capital_value - $installment->paid_capital;
				} else {
					$installment->value  += $late_interests_value;
				}
			}
		}

		return $installments;
	}


	public function updateValuesCredit($id, $total_amount, $capital, $interest)
	{
		$credit_id = $id;
		$credit = Credit::findOrFail($credit_id);
		$headquarter_id = $credit->headquarter->id;

		$box = Box::where('headquarter_id', $headquarter_id)->firstOrFail();

		$add_amount_box = new BoxController();
		$add_amount_box->addAmountBox($box->id, $total_amount);

		$credit->paid_value = $credit->paid_value + $total_amount;
		$credit->capital_value = $credit->capital_value + $capital;
		$credit->interest_value = $credit->interest_value + $interest;
		if ($credit->capital_value >= $credit->credit_value) {
			$credit->status = 4;
			$credit->finish_date = date('Y-m-d');
		}
		$credit->save();

		if ($credit->status == 4) {
			Installment::where('credit_id', $credit->id)->where('status', 0)->update(
				[
					'status' => 1,
					'payment_register' => date('Y-m-d'),
					'paid_balance' => 0,
					'paid_capital' => 0
				]
			);
		}
	}

	public function downloadReceiptPDF(Credit $credit)
	{
		$company = Company::first();
		$client = $credit->client()->first();
		$installments = $credit->installments()->get();
		$headquarter = $credit->headquarter()->first();

		$details = [
			'company' => $company,
			'credit' => $credit,
			'client' => $client,
			'installments' => $installments,
			'headquarter' => $headquarter,
			'url' => URL::to('/')
		];

		$pdf = PDF::loadView('templates.receipt_completed_credit', $details);
		$pdf = $pdf->download('receipt_completed_credit.pdf');

		$data = [
			'status' => 200,
			'pdf' => base64_encode($pdf),
			'message' => 'Tabla generada en pdf'
		];

		return response()->json($data);
	}

	public function generalInformation(Credit $credit)
	{
		$credit = Credit::findOrFail($credit->id);
		$data = array();

		if ($credit->provider_id != null && $credit->provider_id != 0) {
			$provider = $credit->provider()->firstOrFail();
			$data['provider'] = $provider;
		}
		if ($credit->debtor_id != null && $credit->debtor_id != 0) {
			$debtor = $credit->debtor()->firstOrFail();
			$data['debtor'] = $debtor;
		}
		return $data;
	}

	public function getTotalValueCredits(Request $request)
	{
		$from = $request->from;
		$to = $request->to;
		$this_month = Carbon::now()->month;
		$status = $request->status;

		switch ($status) {
			case 'all':
				$query_date = 'created_at';
				break;
			case '0':
				$query_date = 'created_at';
				break;
			case '1':
				$query_date = 'disbursement_date';
				break;
			case '2':
				$query_date = 'updated_at';
				break;
			case '3':
				$query_date = 'created_at';
				break;
			case '4':
				$query_date = 'finish_date';
				break;
			case '5':
				$query_date = 'updated_at';
				break;
		}

		$credits = Credit::select(
			DB::raw('SUM(credit_value) as credit_value '),
			DB::raw('SUM(paid_value) as paid_value'),
			DB::raw('SUM(interest_value) as interest_value'),
			DB::raw('SUM(capital_value) as capital_value')
		);
		if ($status == null) {
			$credits = $credits->whereMonth('created_at', $this_month);
		} else {
			$credits = $credits
				->where(function ($query) use ($status) {
					if ($status == 'all') {
						$query->where('status', '<>', '-1');
					} else {
						$query->where('status', $status);
					}
				})
				->where(function ($query) use ($from, $to, $query_date) {
					if ($from != '' && $from != 'undefined' && $from != null) {
						$query->whereDate("$query_date", '>=', $from);
					}
					if ($to != '' && $to != 'undefined' && $to != null) {
						$query->whereDate("$query_date", '<=', $to);
					}
				});
		}
		$credits = $credits->first();
		return $credits;
	}

	function collectCredit(Credit $credit, Request $request)
	{
		//Valor del credito - valor capital pagado
		$pending_value = $credit->credit_value - $credit->capital_value;

		if ($credit->status == 1) {
			$client = $credit->client()->first();

			$credit->status = 4;
			$credit->finish_date = date('Y-m-d');
			$credit->paid_value += $pending_value;
			$credit->capital_value += $pending_value;
			$credit->save();

			Installment::where('credit_id', $credit->id)->where('status', 0)->update(
				[
					'status' => 1,
					'payment_register' => date('Y-m-d'),
					'paid_balance' => DB::raw('capital_value'),
					'paid_capital' => DB::raw('capital_value')
				]
			);

			$update_main_box = new MainBoxController();
			$update_main_box->addAmountMainBox($pending_value);

			$entry =  new Entry();
			$entry->headquarter_id = $credit->headquarter_id;
			$entry->user_id = $request->user()->id;
			$entry->credit_id = $credit->id;
			$entry->description = "
				Cliente: {$client->name} {$client->last_name}
				Nro. Credito: $credit->id
				";
			$entry->date = date('Y-m-d');
			$entry->type_entry = 'Recoger capital de crédito';
			$entry->price = $pending_value;
			$entry->save();
		}
	}
}
