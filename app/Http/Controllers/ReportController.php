<?php

namespace App\Http\Controllers;

use App\Models\BoxHistory;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
	const RESULTS = 15;

	/* @Route api/reports/portfolio */
	public function ReportPortfolio(Request $request)
	{
		$now = date("Y-m-d");
		$status = $request->status;
		$results = $request->results ?? 15;
		$search_client = $request->search_client;

		if ($status != 'all') {
			$from = null;
			$to = null;
		} else {
			$from = $request->from;
			$to = $request->to;
		}
		$headquarter_id = $request->headquarter_id ?? 'all';

		$payment_date_add_days = date("Y-m-d", strtotime($now . "+ 5 days"));

		$installments = Installment::select(
			'installments.*',
			'credits.client_id',
			'clients.name',
			'clients.last_name',
			'clients.phone_1',
			'clients.phone_2',
			'credits.credit_value',
			'credits.status',
			'headquarters.headquarter as headquarter'
		)
			->where(function ($query) use ($payment_date_add_days, $from, $to, $now, $status, $headquarter_id) {
				$query->whereDate('payment_date', '<=', $payment_date_add_days);
				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('payment_date', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('payment_date', '<=', $to);
				}
				if ($status) {
					if ($status == 'now') {
						$query->whereDate('payment_date', '=', $now);
					}
					if ($status == 'expired') {
						$query->whereDate('payment_date', '<', $now);
					}
					if ($status == 'dueSoon') {
						$query->whereDate('payment_date', '>', $now);
					}
					if ($headquarter_id != 'all') {
						$query->where('credits.headquarter_id', $headquarter_id);
					}
				}
			})
			->where(function ($query) {
				$query
					->whereNull('payment_register')
					->orWhere('installments.status', 0);
			})
			->where('credits.status', 1)
			->leftJoin('credits', 'installments.credit_id', 'credits.id')
			->leftJoin('clients', 'credits.client_id', 'clients.id')
			->leftJoin('headquarters', 'credits.headquarter_id', 'headquarters.id');

		if ($search_client) {
			$installments = $installments->whereHas('credit.client', function ($query) use ($search_client) {
				$query->where('name', 'LIKE', "%$search_client%")
					->orWhere('last_name', 'LIKE', "%$search_client%")
					->orWhere('document', 'LIKE', "%$search_client%");
			});
		}

		$installments = $installments->with('credit')->paginate($results);

		$getTotalReportsController = new GetTotalReportsController;
		$totals = $getTotalReportsController->getTotalReportPortfolio($installments);

		return ['installments' => $installments, 'totals' => $totals];
	}

	public function ReportGeneralCredits(Request $request)
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
		$results = $request->results ?? 15;

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
		$credits = Credit::select()->selectRaw("DATE_ADD(`start_date`, INTERVAL `number_installments` MONTH) as end_date");

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
				->where(function ($query) use ($search_headquarter_id) {
					if (!is_null($search_headquarter_id)) {
						$query->where('headquarter_id', $search_headquarter_id);
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

		$credits = $credits
			->with('client', 'headquarter:id,headquarter')->orderBy('created_at', 'desc')->paginate($results);

		$total_credits = new CreditController;
		$total_credits = $total_credits->getTotalValueCredits($request);

		$total_credits->total_credit_to_pay = $this->getTotalInstallmentsForGeneralCredits($request)->total_credit_to_pay;

		return ['credits' => $credits, 'total_credits' => $total_credits];
	}

	public function ReportHeadquarters(Request $request)
	{
		$results = $request->results ?? 15;

		$credits = Credit::select(
			DB::raw('SUM(credit_value) as credit_value '),
			DB::raw('SUM(paid_value) as paid_value'),
			DB::raw('SUM(interest_value) as interest_value'),
			DB::raw('SUM(capital_value) as capital_value'),
			DB::raw('SUM(additional_interest_paid) as additional_interest_paid'),
			'headquarter_id',
			'headquarters.headquarter as headquarter'
		)
			->selectRaw('count(credits.id) as number_of_credits')
			->selectRaw("count(case when credits.status = '0' then 1 end) as pending")
			->selectRaw("count(case when credits.status = '1' then 1 end) as approved")
			->selectRaw("count(case when credits.status = '2' then 1 end) as rejected")
			->selectRaw("count(case when credits.status = '3' then 1 end) as pending_provider")
			->selectRaw("count(case when credits.status = '4' then 1 end) as completed")
			->selectRaw("count(case when credits.status = '5' then 1 end) as legal_recovery")
			->groupBy('headquarter_id')
			->groupBy('headquarter')
			->leftJoin('headquarters', 'headquarters.id', 'credits.headquarter_id')
			->paginate($results);

		return $credits;
	}

	public function ReportHeadquartersExpenses(Request $request)
	{
		$this_month = Carbon::now()->month;
		$from = $request->from;
		$to = $request->to;
		$user_id = $request->user_id;
		$type_output = $request->type_output;
		$headquarterId = $request->headquarter_id;

		$expenses = Expense::where(function ($query) use ($this_month, $from, $to, $headquarterId) {

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

			if (!is_null($headquarterId) && $headquarterId != '') {
				$query->where('headquarter_id', $headquarterId);
			}
		})->where(function ($query) use ($type_output) {
			if ($type_output != '' && $type_output != 'undefined' && $type_output != null) {
				$query->where('type_output', 'LIKE', "%$type_output%");
			}
		})->where(function ($query) use ($user_id) {
			if ($user_id != '' && $user_id != 'undefined' && $user_id != null) {
				$query->where('user_id', 'LIKE', "%$user_id%");
			}
		})
			->with('headquarter');


		$getTotalReportsController = new GetTotalReportsController;
		$totals = $getTotalReportsController->getTotalReportHeadquartersExpenses($expenses->get());

		return [
			'expenses' => $expenses->paginate(isset($request->results) ? $request->results : self::RESULTS),
			'totals' => $totals,
		];
	}

	public function ReportHeadquartersEntries(Request $request)
	{
		$this_month = Carbon::now()->month;
		$from = $request->from;
		$user_id = $request->user_id;
		$to = $request->to;
		$type_entry = $request->type_entry;
		$headquarterId = $request->headquarter_id;

		$entries = Entry::where(function ($query) use ($this_month, $from, $to, $headquarterId, $user_id) {

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

			if (!is_null($headquarterId) && $headquarterId != '') {
				$query->where('headquarter_id', $headquarterId);
			}
		})->where(function ($query) use ($type_entry) {
			if ($type_entry != '' && $type_entry != 'undefined' && $type_entry != null) {
				$query->where('type_entry', 'LIKE', "%$type_entry%");
			}
		})->where(function ($query) use ($user_id) {
			if ($user_id != '' && $user_id != 'undefined' && $user_id != null) {
				$query->where('user_id', 'LIKE', "%$user_id%");
			}
		})
			->with('headquarter');

		$getTotalReportsController = new GetTotalReportsController;
		$totals = $getTotalReportsController->getTotalReportHeadquartersEntries($entries->get());

		return [
			'entries' => $entries->paginate(isset($request->results) ? $request->results : self::RESULTS),
			'totals' => $totals,
		];
	}

	public function ReportGeneralClient(Request $request)
	{
		if ($request->document == NULL)
			return false;

		$credits = Credit::select(
			DB::raw('SUM(credit_value) as credit_value '),
			DB::raw('SUM(paid_value) as paid_value'),
			DB::raw('SUM(interest_value) as interest_value'),
			DB::raw('SUM(capital_value) as capital_value'),
			'headquarters.headquarter as headquarter',
			'clients.name',
			'clients.last_name',
			'clients.document'
		)
			->selectRaw('count(credits.id) as number_of_credits')
			->selectRaw("count(case when credits.status = '0' then 1 end) as pending")
			->selectRaw("count(case when credits.status = '1' then 1 end) as approved")
			->selectRaw("count(case when credits.status = '2' then 1 end) as rejected")
			->selectRaw("count(case when credits.status = '3' then 1 end) as pending_provider")
			->selectRaw("count(case when credits.status = '4' then 1 end) as completed")
			->groupBy('headquarter')
			->groupBy('clients.name', 'clients.last_name', 'clients.document')
			->leftJoin('headquarters', 'headquarters.id', 'credits.headquarter_id')
			->leftJoin('clients', 'clients.id', 'credits.client_id')
			->where('clients.document',  'LIKE', "%$request->document%")
			->orWhere('clients.name', 'LIKE', "%$request->document%")
			->orWhere('clients.last_name', 'LIKE', "%$request->document%")
			->get();

		return $credits;
	}

	/* @Route api/reports/profitability */
	public function ReportProfitability(Request $request)
	{
		$from = $request->from ?? '';
		$to = $request->to ?? '';
		$data = [];

		$total_expense = Expense::selectRaw('SUM(price) as total_expense')->where(function ($query) use ($from, $to) {
			if ($from) {
				$query->where('created_at', '>=', $from);
			}
			if ($to) {
				$query->where('created_at', '>=', $to);
			}
		})->first();
		$total_credit_interest = Installment::selectRaw('SUM(paid_balance - paid_capital) as total_credit_interest')->where('status', 1)->where(function ($query) use ($from, $to) {
			if ($from) {
				$query->whereDate('payment_register', '>=', $from);
			}
			if ($to) {
				$query->whereDate('payment_register', '>=', $to);
			}
		})->first();

		// return $total_expense;
		$data['total_credit_interest'] = $total_credit_interest->total_credit_interest;
		$data['total_expense'] = $total_expense->total_expense;
		$data['total_gross'] = $data['total_credit_interest'] - $data['total_expense'];

		return $data;
	}

	/* @Route api/reports/cash-flow */
	public function ReportCashFlow(Request $request)
	{
		$from = $request->from ?? '';
		$to = $request->to ?? '';

		$data = [];

		$total_expense = Expense::selectRaw('SUM(price) as total_expense')->where(function ($query) use ($from, $to) {
			if ($from) {
				$query->where('created_at', '>=', $from);
			}
			if ($to) {
				$query->where('created_at', '>=', $to);
			}
		})->first();
		$total_payment = Installment::selectRaw('SUM(paid_balance) as total_payment')->where('status', 1)->where(function ($query) use ($from, $to) {
			if ($from) {
				$query->whereDate('payment_register', '>=', $from);
			}
			if ($to) {
				$query->whereDate('payment_register', '>=', $to);
			}
		})->first();

		// return $total_expense;
		$data['total_payment'] = $total_payment->total_payment;
		$data['total_expense'] = $total_expense->total_expense;
		$data['total_cash_flow'] = $data['total_payment'] - $data['total_expense'];

		return $data;
	}

	public function getTotalInstallmentsForGeneralCredits(Request $request)
	{
		$from = $request->from;
		$to = $request->to;
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

		$installments = Installment::select(
			DB::raw('SUM(value) as value'),
			DB::raw('SUM(paid_balance) as paid_balance ')
		)
			->where(function ($query) use ($search_credit_id) {
				if (!is_null($search_credit_id)) {
					$query->where('credit_id', $search_credit_id);
				}
			});

		$installments =  $installments
			->whereHas('credit.client', function ($query) use ($search_client) {
				$query->where('name', 'LIKE', "%$search_client%")
					->orWhere('last_name', 'LIKE', "%$search_client%")
					->orWhere('document', 'LIKE', "%$search_client%");
			});

		$installments = $installments->whereHas('credit', function ($query)
		use ($status, $from, $to, $query_date, $start_date, $end_date, $search_headquarter_id) {

			if ($status == null) {
				$query->whereMonth('created_at', Carbon::now()->month);
			}

			if ($status == 'all') {
				$query->where('status', '<>', '-1');
			} else {
				$query->where('status', $status);
			}
			if ($from != '' && $from != 'undefined' && $from != null) {
				$query->whereDate("$query_date", '>=', $from);
			}
			if ($to != '' && $to != 'undefined' && $to != null) {
				$query->whereDate("$query_date", '<=', $to);
			}

			if ($start_date != '' && $start_date != 'undefined' && $start_date != null) {
				$query->whereDate("start_date", '>=', $start_date);
			}
			if ($end_date != '' && $end_date != 'undefined' && $end_date != null) {
				$query->whereRaw("DATE_ADD(`start_date`, INTERVAL `number_installments` MONTH) <= '$end_date'");
			}
		})
			->whereHas('credit', function ($query) use ($search_headquarter_id) {
				if (!is_null($search_headquarter_id)) {
					$query->where('headquarter_id', $search_headquarter_id);
				}
			});

		$installments = $installments->first();
		$installments->total_credit_to_pay = $installments->value - $installments->paid_balance;

		return $installments;
	}

	public function ReportRatingClient(Request $request)
	{
		if ($request->document == NULL)
			return false;

		$client = Client::where('clients.document',  'LIKE', "%$request->document%")
			->orWhere('clients.name', 'LIKE', "%$request->document%")
			->orWhere('clients.last_name', 'LIKE', "%$request->document%")
			->first();

		$credits = $client->credits()
			->get();

		foreach ($credits as $credit) {
			$credit->days_past_due = $credit->installments()->get()->sum('days_past_due_calculated');
			$credit->days_past_due_avg = $credit->days_past_due  / $credit->number_installments;
		}
		$nro_credits =  $credits->count('id');
		$total_credits = $credits->sum('days_past_due') / $nro_credits;

		return ["credits" => $credits, "client" => $client, "total_credits" => $total_credits];
	}


	/* @Route api/reports/installments */
	public function ReportInstallments(Request $request)
	{
		$results = $request->results ?? 15;
		$search_client = $request->search_client;
		$from = $request->from;
		$to = $request->to;

		$installments = Installment::select(
			'installments.*',
			'credits.client_id',
			'clients.name',
			'clients.last_name',
			'clients.type_document',
			'clients.document',
			'headquarters.headquarter as headquarter'
		)
			->where(function ($query) use ($from, $to) {
				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('payment_register', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('payment_register', '<=', $to);
				}
			})
			->where('paid_balance', '<>', 0)
			->leftJoin('credits', 'installments.credit_id', 'credits.id')
			->leftJoin('clients', 'credits.client_id', 'clients.id')
			->leftJoin('headquarters', 'credits.headquarter_id', 'headquarters.id');

		if ($search_client) {
			$installments = $installments->whereHas('credit.client', function ($query) use ($search_client) {
				$query->where('name', 'LIKE', "%$search_client%")
					->orWhere('last_name', 'LIKE', "%$search_client%")
					->orWhere('document', 'LIKE', "%$search_client%");
			});
		}

		$installments = $installments->with('credit')->orderBy('payment_register', 'desc')->paginate($results);

		$getTotalReportsController = new GetTotalReportsController;
		$totals = $getTotalReportsController->getTotalReportInstallments($installments);

		return ['installments' => $installments, 'totals' => $totals];
	}

	/* @Route api/reports/box-flow */
	public function ReportBoxFlow(Request $request)
	{
		$from = $request->from ?? '';
		$to = $request->to ?? '';
		$headquarter_id = $request->headquarter_id ?? '';
		$status = $request->status ?? '';
		$results = $request->results ?? '15';
		$description = $request->description ?? '';

		$boxes = BoxHistory::select(
			'box_histories.*',
			'headquarters.headquarter'

		)
			->where(function ($query) use ($from, $to) {

				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('date', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('date', '<=', $to);
				}
			})
			->where(function ($query) use ($status) {

				if ($status != '' && $status != 'undefined' && $status != null) {
					$query->where('box_histories.status', 'LIKE', "$status%");
				}
			})
			->where(function ($query) use ($description) {

				if ($description != '' && $description != 'undefined' && $description != null) {
					$query->where('box_histories.description', 'LIKE', "%$description%");
				}
			})
			->leftJoin('boxes', 'box_histories.box_id', 'boxes.id')
			->leftJoin('headquarters', 'boxes.headquarter_id', 'headquarters.id');

		if ($headquarter_id != '' && $headquarter_id != 'undefined' && $headquarter_id != null) {
			$boxes = $boxes->whereHas('box', function ($query) use ($headquarter_id) {
				$query->where('headquarter_id', "$headquarter_id");
			});
		}

		$boxes = $boxes->orderBy('date', 'desc')->paginate($results);
		return ['boxes' => $boxes];
	}
}
