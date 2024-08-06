<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class GetTotalReportsController extends Controller
{

    public function getTotalReportPortfolio($results)
    {
        $data = [
            'interest_value' => $results->sum('interest_value'),
            'value' => $results->sum('value'),
            'capital_value' => $results->sum('capital_value'),
            'nro_credits' => $results->pluck('credit_id')->unique()->count()
        ];

        return $data;
    }

    public function getTotalReportInstallments($results)
    {
        $data = [
            'paid_capital' => $results->sum('paid_capital'),
            'paid_balance' => $results->sum('paid_balance'),
            'additional_interest_paid' => $results->sum('additional_interest_paid')

        ];

        return $data;
    }

    public function getTotalReportHeadquartersExpenses($results){
        $data = [
            'price' => $results->sum('price')
        ];
        
        return $data;
    }

    public function getTotalReportHeadquartersEntries($results){
        $data = [
            'price' => $results->sum('price')
        ];
        
        return $data;
    }
}
