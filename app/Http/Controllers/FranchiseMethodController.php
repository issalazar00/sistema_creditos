<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FranchiseMethodController extends InstallmentController
{

  // Calculo de cuotas de sistema francés
  public function calculateInstallments(Request $request)
  {
    $capital = $request->credit_value;
    $interest = $request->interest;
    $additional_interest = floatval($request->additional_interest) ?: 0;
    $number_installments = $request->number_installments;
    $start_date = date('Y-m-d');

    if ($request->start_date && $request->start_date != 'undefined') {
      $start_date = $request->start_date;
    }

    $value = $capital;
    $valor_pago_interes = $interest;
    $valor_pago_additional_interest = $additional_interest;
    $installment_number = $number_installments;

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoInteresAdicional = [];
    $pagoCapital = [];
    $totalInterest = $valor_pago_interes + $valor_pago_additional_interest;
    $int = pow(1 + ($totalInterest) / 100, $installment_number);

    $installment = ($value * (($int * ($totalInterest)) / 100)) / ($int - 1);

    for ($i = 0; $i < $number_installments; $i++) {

      $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

      $pagoInteres[$i] = ($value * ($valor_pago_interes / 100));
      $pagoInteresAdicional[$i] = ($value * ($valor_pago_additional_interest / 100));
      $pagoCapital[$i] = $installment - $pagoInteres[$i] - $pagoInteresAdicional[$i];
      $value = ($value - $pagoCapital[$i]);

      $listInstallments[$i] = [
        'installment_number' => $i + 1,
        'pagoCapital' => (float) number_format($pagoCapital[$i], 2, '.', ''),
        'pagoInteres' => (float) number_format($pagoInteres[$i], 2, '.', ''),
        'pagoInteresAdicional' => (float) number_format($pagoInteresAdicional[$i], 2, '.', ''),
        'payment_date' => $payment_date[$i],
        'saldo_capital' => (float) number_format($value, 2, '.', ''),
        'installment_value' => (float) number_format($installment, 2, '.', '')
      ];
    }

    return ['listInstallments' => $listInstallments, 'installment' => (float) number_format($installment, 2, '.', '')];
  }

  // Actualizar valor de todas las cuotas de un credito
  public function updateInstallments($credit_id)
  {
    $credit = Credit::findOrFail($credit_id);
    $paidTotalCredit =  $credit->installments()->selectRaw("
    SUM(interest_value) AS interest_value,
      SUM(paid_capital) AS paid_capital,
      SUM(paid_balance) AS paid_balance
    ")->where('paid_balance', '>', 0)->first();

    $capital = $credit->credit_value - $paidTotalCredit->paid_capital;

    $installments = $credit->installments()
      ->where('status', 0)
      ->get();

    $interest = $credit->interest;
    $additional_interest = $credit->additional_interest;
    $number_installments = count($installments);
    $start_date = date('Y-m-d');

    if ($credit->start_date && $credit->start_date != 'undefined') {
      $start_date = $credit->start_date;
    }

    $value = $capital;
    $valor_pago_interes = $interest;
    $valor_pago_additional_interest = $additional_interest;
    $installment_number = $number_installments;

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoInteresAdicional = [];
    $pagoCapital = [];

    $totalInterest = $valor_pago_interes + $valor_pago_additional_interest;
    $int = pow(1 + ($totalInterest) / 100, $installment_number);

    if ($number_installments) {
      $installment = ($value * (($int * ($totalInterest)) / 100)) / ($int - 1);

      for ($i = 0; $i < $number_installments; $i++) {
        $id_installment = $installments[$i]->id;
        $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

        $pagoInteres[$i] = ($value * ($valor_pago_interes / 100));
        $pagoInteresAdicional[$i] = ($value * ($valor_pago_additional_interest / 100));
        $pagoCapital[$i] = $installment - $pagoInteres[$i] - $pagoInteresAdicional[$i];

        $value = ($value - $pagoCapital[$i]);

        $listInstallments[$i] = [
          'installment_number' => $i + 1,
          'pagoCapital' => (float) number_format($pagoCapital[$i], 2, '.', ''),
          'pagoInteres' => (float) number_format($pagoInteres[$i], 2, '.', ''),
          'pagoInteresAdicional' => (float) number_format($pagoInteresAdicional[$i], 2, '.', ''),
          'payment_date' => $payment_date[$i],
          'saldo_capital' => (float) number_format($value, 2, '.', ''),
          'installment_value' => (float) number_format($installment, 2, '.', '')
        ];

        Installment::findOrFail($id_installment)->update(
          [
            'value' =>  $listInstallments[$i]['installment_value'],
            'interest_value' =>  $listInstallments[$i]['pagoInteres'],
            'capital_value' =>  $listInstallments[$i]['pagoCapital'],
            'additional_interest_value' =>  $listInstallments[$i]['pagoInteresAdicional'],
            'capital_balance' => $listInstallments[$i]['saldo_capital']
          ]
        );
      }
      $credit->update(
        ['installment_value' =>  $listInstallments[0]['installment_value']]
      );
    }
  }

  // Actualizar valor de todas las cuotas de un credito desde abono a capital del crédito
  public function updateInstallmentsFromAbonoCredito($credit_id, $new_interest)
  {
    $credit = Credit::findOrFail($credit_id);
    $paidTotalCredit =  $credit->installments()->selectRaw("
      SUM(interest_value) AS interest_value,
        SUM(paid_capital) AS paid_capital,
        SUM(paid_balance) AS paid_balance
      ")
      ->where('paid_balance', '>', 0)->first();

    $capital = $credit->credit_value - ($credit->capital_value);

    $installments = $credit->installments()
      ->where('status', 0)
      ->get();

    $interest = $new_interest ? $new_interest : $credit->interest;
    $additional_interest = $credit->additional_interest;
    $number_installments = count($installments);
    $start_date = date('Y-m-d');

    if ($credit->start_date && $credit->start_date != 'undefined') {
      $start_date = $credit->start_date;
    }

    $value = $capital;
    $valor_pago_interes = $interest;
    $valor_pago_additional_interest = $additional_interest;
    $installment_number = $number_installments;

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoInteresAdicional = [];
    $pagoCapital = [];


    $totalInterest = $valor_pago_interes + $valor_pago_additional_interest;
    $int = pow(1 + ($totalInterest) / 100, $installment_number);


    if ($number_installments) {
      $installment = ($value * (($int * ($totalInterest)) / 100)) / ($int - 1);


      for ($i = 0; $i < $number_installments; $i++) {
        $id_installment = $installments[$i]->id;
        $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

        $pagoInteres[$i] = ($value * ($valor_pago_interes / 100));
        $pagoInteresAdicional[$i] = ($value * ($valor_pago_additional_interest / 100));

        $pagoCapital[$i] = $installment - $pagoInteres[$i];
        $value = ($value - $pagoCapital[$i]);

        $listInstallments[$i] = [
          'installment_number' => $i + 1,
          'pagoCapital' => (float) number_format($pagoCapital[$i], 2, '.', ''),
          'pagoInteres' => (float) number_format($pagoInteres[$i], 2, '.', ''),
          'pagoInteresAdicional' => (float) number_format($pagoInteresAdicional[$i], 2, '.', ''),
          'payment_date' => $payment_date[$i],
          'saldo_capital' => (float) number_format($value, 2, '.', ''),
          'installment_value' => (float) number_format($installment, 2, '.', '')
        ];
        Installment::findOrFail($id_installment)->update(
          [
            'value' =>  $listInstallments[$i]['installment_value'],
            'interest_value' =>  $listInstallments[$i]['pagoInteres'],
            'additional_interest_value' =>  $listInstallments[$i]['pagoInteresAdicional'],
            'capital_value' =>  $listInstallments[$i]['pagoCapital'],
            'capital_balance' => $listInstallments[$i]['saldo_capital']
          ]
        );
      }
      $credit->update(
        ['installment_value' =>  $listInstallments[0]['installment_value']]
      );
    }
  }
}
