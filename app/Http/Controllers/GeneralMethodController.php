<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Installment;
use Illuminate\Http\Request;

class GeneralMethodController extends InstallmentController
{

  // Calculo de cuotas de sistema frances
  public function calculateInstallments(Request $request)
  {
    $capital = $request->credit_value;
    $interest = ($request->interest);
    $number_installments = $request->number_installments;
    $additional_interest = floatval($request->additional_interest) ?: 0;
    $general_tax = $number_installments * $interest;
    $total_credit = $capital * $general_tax;
    $valor_pago_interes = $total_credit - $capital;
    $valor_pago_interes_additional = 0;
    if ($additional_interest) {
      $valor_pago_interes_additional = ($number_installments * $additional_interest * $capital) - $capital;
    }

    $fechaInicio = date('Y-m-d');

    if ($request->start_date && $request->start_date != 'undefined') {
      $fechaInicio = $request->start_date;
    }

    $value = $capital;

    $payment_date = [];
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoInteresAdicional = [];
    $pagoCapital = [];

    $installment = $capital * $interest + ($valor_pago_interes_additional / $number_installments);

    for ($i = 0; $i < $number_installments; $i++) {

      $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

      $pagoInteres[$i] = $valor_pago_interes / $number_installments;
      $pagoCapital[$i] = $capital / $number_installments;
      $pagoInteresAdicional[$i] = $valor_pago_interes_additional / $number_installments;
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
    $capital = $credit->credit_value - ($credit->capital_value);

    $installments = $credit->installments()
      ->where('status', 0)
      ->get();

    $interest = ($credit->interest);
    $number_installments = count($installments);
    $start_date = date('Y-m-d');

    if ($credit->start_date && $credit->start_date != 'undefined') {
      $start_date = $credit->start_date;
    }

    $value = $capital;
    $additional_interest = floatval($credit->additional_interest) ?: 0;
    $general_tax = $number_installments * $interest;
    $total_credit = $capital * $general_tax;
    $valor_pago_interes = $total_credit - $capital;
    $valor_pago_interes_additional = 0;
    if ($additional_interest) {
      $valor_pago_interes_additional = ($number_installments * $additional_interest * $capital) - $capital;
    }

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoInteresAdicional = [];
    $pagoCapital = [];

    if ($number_installments) {
      $installment = $capital * $interest + ($valor_pago_interes_additional / $number_installments);

      for ($i = 0; $i < $number_installments; $i++) {
        $id_installment = $installments[$i]->id;
        $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

        $pagoInteres[$i] = $valor_pago_interes / $number_installments;
        $pagoInteresAdicional[$i] = $valor_pago_interes_additional / $number_installments;
        $pagoCapital[$i] = $capital / $number_installments;
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
            'additional_interest_value' =>   $listInstallments[$i]['pagoInteresAdicional'],
            'capital_value' =>  $listInstallments[$i]['pagoCapital'],
            'capital_balance' => $listInstallments[$i]['saldo_capital']
          ]
        );
        $listInstallments[$i]['installment_number'] = $i + 1;
      }
      $credit->update(
        ['installment_value' =>  $listInstallments[0]['installment_value']]
      );
    }
  }

  public function updateInstallmentsFromAbonoCredito($credit_id, $new_interest)
  {
    $credit = Credit::findOrFail($credit_id);

    $installments = $credit->installments()
      ->where('status', 0)
      ->get();

    $capital = $credit->credit_value - ($credit->capital_value);
    $interest = $new_interest ? $new_interest : ($credit->interest);
    $additional_interest = floatval($credit->additional_interest) ?: 0;
    $number_installments = count($installments);
    $start_date = date('Y-m-d');

    if ($credit->start_date && $credit->start_date != 'undefined') {
      $start_date = $credit->start_date;
    }

    $value = $capital;
    $general_tax = $number_installments * $interest;
    $total_credit = $capital * $general_tax;
    $valor_pago_interes = $total_credit - $capital;
    $valor_pago_interes_additional = 0;
    if ($additional_interest) {
      $valor_pago_interes_additional = ($number_installments * $additional_interest * $capital) - $capital;
    }

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoInteresAdicional = [];
    $pagoCapital = [];

    if ($number_installments) {
      $installment = $capital * $interest + ($valor_pago_interes_additional / $number_installments);

      for ($i = 0; $i < $number_installments; $i++) {
        $id_installment = $installments[$i]->id;
        $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

        $pagoInteres[$i] = $valor_pago_interes / $number_installments;
        $pagoCapital[$i] = $capital / $number_installments;
        $pagoInteresAdicional[$i] = $valor_pago_interes_additional / $number_installments;
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
            'additional_interest_value' =>   $listInstallments[$i]['pagoInteresAdicional'],
            'capital_value' =>  $listInstallments[$i]['pagoCapital'],
            'capital_balance' => $listInstallments[$i]['saldo_capital']
          ]
        );
        $listInstallments[$i]['installment_number'] = $i + 1;
      }
      $credit->update(
        ['installment_value' =>  $listInstallments[0]['installment_value']]
      );
    }
  }
}
