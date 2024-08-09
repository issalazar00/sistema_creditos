<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Company;
use App\Models\Credit;
use App\Models\CreditProvider;
use App\Models\Entry;
use App\Models\Installment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PDF;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPUnit\Framework\isEmpty;

class CreditController extends Controller
{
	private  $creditStatus = [
		0 => "Pendiente",
		1 => "Activo",
		2 => "Rechazado",
		3 => "Pendiente pago a proveedor",
		4 => "Completado",
		5 => "Cobro jurídico",
		6 => "Eliminado",
		7 => 'Pérdida'
	];

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
		// $installment_controller = new InstallmentController();
		// $installment_controller->correctStatusInstallments();

		$credits = Credit::select();
		$headquarterId = $request->headquarter_id ?: 'all';
		$status = $request->status != null ? $request->status : 1;
		$status = $status == 0 ? [0, 3] : [$request->status];
		$search_credit_id = $request->search_credit_id;

		if ($request->credit && ($request->credit != '')) {
			$credits  =   $credits->leftjoin('clients as c', 'c.id', 'credits.client_id')
				->select('credits.*', 'credits.id as id', 'c.name', 'c.last_name', 'c.document', 'c.type_document', 'c.phone_1', 'c.phone_2', 'c.maximum_credit_allowed')
				->where('document', 'LIKE', "%$request->credit%")
				->orWhere('name', 'LIKE', "%$request->credit%")
				->orWhere('last_name', 'LIKE', "%$request->credit%")
				->whereIn('credits.status', $status);
		} else {
			$credits  =     $credits->leftjoin('clients as c', 'c.id', 'credits.client_id')
				->select('credits.*', 'credits.id as id', 'c.name', 'c.last_name', 'c.document', 'c.type_document', 'c.phone_1', 'c.phone_2', 'c.maximum_credit_allowed')
				->whereIn('credits.status', $status);
		}
		$credits = $credits->where(function ($query) use ($headquarterId) {
			if (!is_null($headquarterId) && $headquarterId != '' && $headquarterId != 'all') {
				$query->where('credits.headquarter_id', $headquarterId);
			}
		})
			->where(function ($query) use ($search_credit_id) {
				if (!is_null($search_credit_id)) {
					$query->where('credits.id', $search_credit_id);
				}
			});
		$credits = $credits
			->orderBy('id', 'desc')
			// ->getAttributes()
			->with('headquarter:id,headquarter', 'client:id,name,last_name', 'product:id,product', 'guarantees:id,guarantee', 'debtors:id,name,last_name,type_document,document,phone_1,phone_2')
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
		$company = Company::first();
		$validateMethod = $company ? ($company->method  == "GENERAL") : false;

		$validate = Validator::make($request->all(), [
			'client_id' => 'required|integer|exists:clients,id',
			'provider' => 'nullable|boolean',
			'debtor' => 'nullable|boolean',
			'product_id' => [
				'nullable',
				'exists:products,id'
			],
			'provider_id' => [
				'nullable',
				Rule::requiredIf($request->provider == 1),
				'exists:providers,id'
			],
			'debtors' => [
				'nullable',
				Rule::requiredIf($request->debtor == 1)
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
			'guarantees' => 'nullable|array',
			'credit_requested' => [
				Rule::requiredIf($validateMethod),
				'numeric'
			],
			'doc_acc_imp' => [
				Rule::requiredIf($validateMethod),
				'numeric'
			],
			'initial_quota' => [
				Rule::requiredIf($validateMethod),
				'numeric'
			]
		]);

		if ($validate->fails()) {
			return response()->json([
				'status' => 'error',
				'code' =>  400,
				'message' => 'Validación de datos incorrecta',
				'errors' =>  $validate->errors()
			], 400);
		}

		$listInstallments = new MethodCreditController();
		$listInstallments = $listInstallments->calculateInstallments($request);

		$credit = new Credit();
		$credit->client_id = $request['client_id'];
		$credit->provider_id = $request['provider_id'];
		$credit->product_id = $request['product_id'];
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
		$credit->additional_interest = $request['additional_interest'];
		$credit->description = $request['description'];
		$credit->installment_value = $listInstallments['installment'];

		// if ($validateMethod) {
		$credit->credit_requested = $request['credit_requested'];
		$credit->doc_acc_imp = $request['doc_acc_imp'];
		$credit->initial_quota = $request['initial_quota'];
		// }

		if ($credit->save()) {

			if (!empty($request->debtors)) {
				foreach ($request->debtors as $debtor) {
					$credit_debtor_controller = new CreditDebtorController();
					$credit_debtor_controller->store($credit->id, $debtor['id']);
				}
			}

			if (!empty($request->guarantees)) {
				foreach ($request->guarantees as $guarantee) {
					$credit_guarantee_controller = new CreditGuaranteeController();
					$credit_guarantee_controller->store($credit->id, $guarantee['id']);
				}
			}

			foreach ($listInstallments['listInstallments'] as $new_installment) {
				$installment = new Installment();
				$installment->credit_id = $credit->id;
				$installment->installment_number = $new_installment['installment_number'];
				$installment->value = $new_installment['installment_value'];
				$installment->payment_date = $new_installment['payment_date'];
				$installment->interest_value = $new_installment['pagoInteres'];
				$installment->capital_value = $new_installment['pagoCapital'];
				$installment->additional_interest_value = $new_installment['pagoInteresAdicional'];
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
			'product_id' => [
				'nullable',
				'exists:products,id'
			],
			'provider_id' => [
				'nullable',
				Rule::requiredIf($request->provider == 1),
				'exists:providers,id'
			],
			'debtors' => [
				'nullable',
				Rule::requiredIf($request->debtor == 1)
			],
			'headquarter_id' => 'required|integer|exists:headquarters,id',
			// 'number_installments' => 'required|integer',
			// 'number_paid_installments' => 'nullable|integer',
			// 'day_limit' => 'nullable|integer',
			// 'start_date' => 'required|date',
			// 'interest' => 'required|numeric',
			// 'annual_interest_percentage' => 'nullable|numeric',
			// 'credit_value' =>  'required|numeric',
			// 'paid_value' => 'nullable|numeric',
			// 'capital_value' => 'nullable|numeric',
			// 'interest_value' => 'nullable|numeric',
			'description' => 'nullable|string',
			// 'disbursement_date' => 'nullable|date',
			// 'installment_value' => 'required|numeric',
			'guarantees' => 'nullable|array'
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
		$credit->product_id = $request['product_id'];
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

		if ($credit->save()) {

			foreach ($request->debtors as $debtor) {
				$credit_debtor_controller = new CreditDebtorController();
				$credit_debtor_controller->store($credit->id, $debtor['id']);
			}

			foreach ($request->guarantees as $guarantee) {
				$credit_guarantee_controller = new CreditGuaranteeController();
				$credit_guarantee_controller->store($credit->id, $guarantee['id']);
			}

			if ($request['provider_id']) {
				$credit->credit_provider()->updateOrCreate(
					['credit_id' => $credit->id],
					[
						'provider_id' => $request['provider_id'],
						'headquarter_id' => $request['headquarter_id']
					]
				);
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
		$client_id = $credit->client->id;
		$user_id = $request->user()->id;
		$status = $credit->status;
		$headquarter_id = $credit->headquarter_id;

		$box = Box::where('headquarter_id', $headquarter_id)->firstOrFail();

		if ($credit->provider_id != null && $credit->provider_id != 0) {
			$credit_provider = CreditProvider::where('credit_id', $credit->id)->first();

			if ($credit_provider != null) {
				if ($credit_provider->pending_value <= 0) {

					if ($request->status  == 1 || $request['status']  == 1) {

						$update_main_box = new MainBoxController();
						$update_main_box->subAmountMainBox($request, $credit->credit_value);
						$credit->disbursement_date = date('Y-m-d');
						$credit->approved_by = $request->user()->id;

						$validate_credit_limit = new ClientController();
						$validate_credit_limit->validateCreditLimit($client_id, $credit->credit_value);
					}

					if ($request->status  == 2 && $credit->status == 1) {
						$update_main_box = new MainBoxController();
						$update_main_box->addAmountMainBox($request, $credit->credit_value);
					}
				}
				$credit->status = $request->status;
				$credit_provider->status = $request->status;
				$credit_provider->save();
			}
		}

		if ($credit->provider_id == null || $credit->provider_id == 0) {
			if ($request->status  == 1 || $request['status']  == 1) {
				$update_main_box = new MainBoxController();
				$update_main_box->subAmountMainBox($request, $credit->credit_value);
				$credit->disbursement_date = date('Y-m-d');
				$credit->approved_by = $request->user()->id;

				$validate_credit_limit = new ClientController();
				$validate_credit_limit->validateCreditLimit($client_id, $credit->credit_value);
			}
			if ($request->status  == 2 && $credit->status == 1) {
				$update_main_box = new MainBoxController();
				$update_main_box->addAmountMainBox($request, $credit->credit_value);
			}
			$credit->status = $request->status;
		}

		if ($status != 1 && $request->status == 1) {

			if ($credit->initial_quota) {
				$entry = new InstallmentController;
				$extrasEntry = (object)[];
				$entry->saveEntryInstallment($credit, $credit->initial_quota, $credit->initial_quota, 1, 0, $credit->user_id, 'Cuota inicial', $extrasEntry);

				$user = User::find($credit->user_id);
				$box = Box::where('headquarter_id', $user->headquarter_id)->firstOrFail();

				$add_amount_box = new BoxController();
				$request['add_amount'] = $credit->initial_quota;
				$add_amount_box->addAmountBox((object)$request, $box->id, $credit->initial_quota);
			}
		}

		if ($request->description != null) {
			$valueStatus =  $this->creditStatus[$request->status];
			$credit->description  = "$credit->description \n$valueStatus por: $request->description";
		}

		$credit->save();
	}

	public function installments(Request $request, $id)
	{
		$credit = Credit::findOrFail($id);
		$installments =  $credit->installments()->get();
		$configuration = Company::first();

		foreach ($installments as $installment) {
			$now = now();
			$payment_date = Carbon::createFromFormat('Y-m-d', $installment->payment_date);

			if (($payment_date < $now) &&  $installment->status != '1') {
				$installment->capital_value_pending = $installment->capital_value - $installment->paid_capital < 0 ? 0 : $installment->capital_value - $installment->paid_capital;
				$installment->interest_value_pending = $installment->interest_value;
				$installment->additional_interest_value_pending = $installment->additional_interest_value;

				$days_past_due = $installment->days_past_due ? $installment->days_past_due :  $now->diffInDays($payment_date);
				$day_value_default = ($installment->interest_value * $configuration->late_interest_day);
				$late_interest_value =  $day_value_default * $days_past_due;

				$installment->days_past_due  = $days_past_due;
				$installment->late_interests_value  = round($late_interest_value, 5);
				$installment->late_interests_value_pending = $installment->late_interests_value;
				$installment->step = 7;

				$totalInterests = $installment->interest_value + $installment->additional_interest_value;
				if (($installment->paid_balance)) {
					$installment->step = 4;

					// Sobrante para pago a intereses después de pago a capital
					$paidInterest = $installment->paid_balance - $installment->paid_capital;

					if ($paidInterest > ($totalInterests)) {
						//Intereses de mora pendientes =  Total de interes de mora - total de intereses comunes pagados.
						$installment->late_interests_value_pending =  round($late_interest_value - ($paidInterest - $totalInterests), 5);
						$installment->interest_value_pending = 0;
						$installment->additional_interest_value_pending = 0;
						$installment->step = 3;
					} else if ($paidInterest > $installment->interest_value) {
						// Se calcula prioritariamente el interés mensual Pagado.
						$installment->interest_value_pending = 0;
						$installment->additional_interest_value_pending =   round($installment->additional_interest_value - ($paidInterest - $installment->interest_value), 5);
						$installment->late_interests_value_pending =  $late_interest_value;
						$installment->step = 5;
					} else {
						$installment->late_interests_value_pending =  $late_interest_value;
						$installment->interest_value_pending = round($installment->interest_value - $paidInterest, 2);
						$installment->step = 6;
					}
				}
				$installment->value_pending = round($installment->interest_value_pending + $installment->capital_value_pending + $installment->late_interests_value_pending + $installment->additional_interest_value_pending, 5);
			} else {
				$installment->capital_value_pending = $installment->capital_value - $installment->paid_capital < 0 ? 0 : $installment->capital_value - $installment->paid_capital;
				$installment->additional_interest_value_pending = $installment->additional_interest_value - $installment->additional_interest_paid;
				$installment->interest_value_pending = ((int)$installment->paid_balance - (int)$installment->paid_capital) >   $installment->interest_value ? 0 : round($installment->interest_value - ((int)$installment->paid_balance - (int)$installment->paid_capital), 5);
				$installment->value_pending = round($installment->interest_value_pending + $installment->capital_value_pending + $installment->late_interests_value_pending + $installment->additional_interest_value_pending, 5);
				$installment->paid_capital = $installment->paid_capital + $installment->credit_deposit;
				$installment->paid_balance = $installment->paid_balance + $installment->credit_deposit;
				$installment->step = 1;
			}
		}

		return $installments;
	}

	public function updateValuesCredit(Request $request, $id, $total_amount, $capital, $interest, $is_reverse = false)
	{
		$credit_id = $id;
		$credit = Credit::findOrFail($credit_id);
		$user_id = $request->user_id ? $request->user_id : $request->user()->id;
		$user = User::find($user_id);
		$headquarter_id = $user->headquarter_id;

		$box = Box::where('headquarter_id', $headquarter_id)->firstOrFail();

		$add_amount_box = new BoxController();
		if (!$is_reverse) {
			$request->merge(['add_amount' => $total_amount]);
			$add_amount_box->addAmountBox($request, $box->id, $total_amount);
		}

		$credit->paid_value +=  $total_amount;
		$credit->capital_value += $capital;
		$credit->interest_value +=  $interest;
		$credit->additional_interest_paid +=  $request->additional_interest;
		if ($credit->capital_value >= $credit->credit_value) {
			$checkLastInstallment = $credit->installments()->where('status', 0)->get();
			if (isEmpty($checkLastInstallment)) {
				$credit->status = 4;
				$credit->finish_date = date('Y-m-d');
			}
		}
		$credit->old_interest = $credit->interest;
		$credit->interest = $request->new_interest ? $request->new_interest : $credit->old_interest;

		$credit->save();

		if ($credit->status == 4) {
			Installment::where('credit_id', $credit->id)->where('status', 0)->update(
				[
					'status' => 1,
					'payment_register' => date('Y-m-d'),
					'paid_balance' => 0,
					'paid_capital' => 0,
					'additional_interest_paid' => 0,
					'collected_by' => $request->user()->id
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
		if ($credit->debtors()) {
			$debtor = $credit->debtors()->get();
			$data['debtors'] = $debtor;
		}
		if ($credit->guarantees()) {
			$debtor = $credit->guarantees()->get();
			$data['guarantees'] = $debtor;
		}
		return $data;
	}

	public function getTotalValueCredits(Request $request)
	{
		$from = $request->from;
		$to = $request->to;
		$this_month = Carbon::now()->month;
		$status = $request->status;
		$start_date = $request->start_date;
		$end_date = $request->end_date;
		$search_client = $request->search_client;
		$search_credit_id = $request->search_credit_id;
		$search_headquarter_id = $request->search_headquarter_id;

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
			DB::raw('SUM(additional_interest_paid) as additional_interest_paid'),
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
				->where(function ($query) use ($search_credit_id) {
					if (!is_null($search_credit_id)) {
						$query->where('id', $search_credit_id);
					}
				})
				->where(function ($query) use ($from, $to, $query_date) {
					if ($from != '' && $from != 'undefined' && $from != null) {
						$query->whereDate("$query_date", '>=', $from);
					}
					if ($to != '' && $to != 'undefined' && $to != null) {
						$query->whereDate("$query_date", '<=', $to);
					}
				})
				->where(function ($query) use ($start_date, $end_date) {
					if ($start_date != '' && $start_date != 'undefined' && $start_date != null) {
						$query->whereDate("start_date", '>=', $start_date);
					}
					if ($end_date != '' && $end_date != 'undefined' && $end_date != null) {
						$query->whereRaw("DATE_ADD(`start_date`, INTERVAL `number_installments` MONTH) <= '$end_date'");
					}
				});
		}

		if ($search_client) {
			$credits = $credits->whereHas('client', function ($query) use ($search_client) {
				$query->where('name', 'LIKE', "%$search_client%")
					->orWhere('last_name', 'LIKE', "%$search_client%")
					->orWhere('document', 'LIKE', "%$search_client%");
			});
		}

		$credits = $credits->where(function ($query) use ($search_headquarter_id) {
			if (!is_null($search_headquarter_id)) {
				$query->where('headquarter_id', $search_headquarter_id);
			}
		});
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

			$box = Box::where('headquarter_id',  $request->user()->headquarter_id)->firstOrFail();

			$update_box = new BoxController();
			$update_box->addAmountBox($request, $box->id, $pending_value, false);

			$entry =  new Entry();
			$entry->headquarter_id =  $request->user()->headquarter_id;
			$entry->user_id = $request->user()->id;
			$entry->credit_id = $credit->id;
			$entry->description = "
				Cliente: {$client->name} {$client->last_name} \n" .
				"Nro. Credito: $credit->id \n" .
				"Cupo crédito: {$client->maximum_credit_allowed}";
			$entry->date = date('Y-m-d');
			$entry->type_entry = 'Recoger capital de crédito';
			$entry->price = $pending_value;
			$entry->save();
		}
	}
}
