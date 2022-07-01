<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Expense;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Echo_;

class ReportController extends Controller
{
	public function ReportPortfolio(Request $request)
	{
		//var_dump($request->to);
		$from = $request->from;
		$to = $request->to;
		$now = date("Y-m-d");
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
		)
			->where(function ($query) use ($payment_date_add_days, $from, $to) {
				$query->whereDate('payment_date', '<=', $payment_date_add_days);
				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('payment_date', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('payment_date', '<=', $to);
				}
			})
			->whereNull('payment_register')
			->where('credits.status', 1)
			->leftJoin('credits', 'installments.credit_id', 'credits.id')
			->leftJoin('clients', 'credits.client_id', 'clients.id')
			->paginate(15);

		return $installments;
	}


	public function ReportGeneralCredits(Request $request)
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
		$credits = Credit::select();

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

		$credits = $credits
			->with('client:id,name,last_name')->paginate(15);
		$total_credits = new CreditController;
		$total_credits = $total_credits->getTotalValueCredits($request);

		return ['credits' => $credits, 'total_credits' => $total_credits];
	}

	public function ReportHeadquarters()
	{
		$credits = Credit::select(
			DB::raw('SUM(credit_value) as credit_value '),
			DB::raw('SUM(paid_value) as paid_value'),
			DB::raw('SUM(interest_value) as interest_value'),
			DB::raw('SUM(capital_value) as capital_value'),
			'headquarter_id',
			'headquarters.headquarter as headquarter'
		)
			->selectRaw('count(credits.id) as number_of_credits')
			->selectRaw("count(case when credits.status = '0' then 1 end) as pending")
			->selectRaw("count(case when credits.status = '1' then 1 end) as approved")
			->selectRaw("count(case when credits.status = '2' then 1 end) as rejected")
			->selectRaw("count(case when credits.status = '3' then 1 end) as pending_provider")
			->selectRaw("count(case when credits.status = '4' then 1 end) as completed")
			->groupBy('headquarter_id')
			->groupBy('headquarter')
			->leftJoin('headquarters', 'headquarters.id', 'credits.headquarter_id')
			->paginate(15);

		return $credits;
	}

	public function ReportHeadquartersExpenses(Request $request)
	{
		$from = $request->from;
		$to = $request->to;
		$this_month = Carbon::now()->month;

		$expenses = Expense::select(
			DB::raw('SUM(price) as price '),
			'headquarters.headquarter as headquarter',
		)
			->where(function ($query) use ($this_month, $from, $to) {

				$query->whereMonth('date', '<=', $this_month);

				if ($from != '' && $from != 'undefined' && $from != null) {
					$query->whereDate('date', '>=', $from);
				}
				if ($to != '' && $to != 'undefined' && $to != null) {
					$query->whereDate('date', '<=', $to);
				}
			})
			->groupBy('headquarter')
			->leftJoin('headquarters', 'headquarters.id', 'expenses.headquarter_id')
			->paginate(15);

		return $expenses;
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
			'clients.last_name'


		)
			->selectRaw('count(credits.id) as number_of_credits')
			->selectRaw("count(case when credits.status = '0' then 1 end) as pending")
			->selectRaw("count(case when credits.status = '1' then 1 end) as approved")
			->selectRaw("count(case when credits.status = '2' then 1 end) as rejected")
			->selectRaw("count(case when credits.status = '3' then 1 end) as pending_provider")
			->selectRaw("count(case when credits.status = '4' then 1 end) as completed")
			->groupBy('headquarter')
			->groupBy('clients.name', 'clients.last_name')
			->leftJoin('headquarters', 'headquarters.id', 'credits.headquarter_id')
			->leftJoin('clients', 'clients.id', 'credits.client_id')
			->where('clients.document',  'LIKE', "%$request->document%")
			->get();

		return $credits;
	}
}
