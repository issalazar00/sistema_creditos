<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Credit;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use PDF;

class InstallmentController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    //
    $credits = Installment::select();
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
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
    //
    $installment = new Installment();
    $installment->credit_id = $request['credit_id'];
    $installment->installment_number = $request['installment_number'];
    $installment->value = $request['value'];
    $installment->payment_date = $request['payment_date'];
    $installment->days_past_due = $request['days_past_due'];
    $installment->late_interests_value = $request['late_interests_value'];
    $installment->valor_pago_interes = $request['valor_pago_interes'];
    $installment->valor_pago_capital = $request['valor_pago_capital'];
    $installment->save();
  }

  /**
   * Display the specified resource.
   *
   * @param  \App\Models\Installment  $installment
   * @return \Illuminate\Http\Response
   */
  public function show(Installment $installment)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  \App\Models\Installment  $installment
   * @return \Illuminate\Http\Response
   */
  public function edit(Installment $installment)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \App\Models\Installment  $installment
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, Installment $installment)
  {
    //
    $installment = new Installment();
    $installment->installment_number = $request['installment_number'];
    $installment->value = $request['value'];
    $installment->payment_date = $request['payment_date'];
    $installment->days_past_due = $request['days_past_due'];
    $installment->late_interests_value = $request['late_interests_value'];
    $installment->valor_pago_interes = $request['valor_pago_interes'];
    $installment->valor_pago_capital = $request['valor_pago_capital'];
    $installment->save();
  }

  public function calculateInstallments(Request $request)
  {
    $capital = $request->credit_value;
    $interest = $request->interest;
    $number_installments = $request->number_installments;
    $start_date = date('Y-m-d');

    if ($request->start_date && $request->start_date != 'undefined') {
      $start_date = $request->start_date;
    }

    $value = $capital;
    $valor_pago_interes = $interest;
    $installment_number = $number_installments;

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoCapital = [];

    $installment =
      ($value *
        ((pow(1 + $valor_pago_interes / 100, $installment_number) *
          $valor_pago_interes) /
          100)) /
      (pow(1 + $valor_pago_interes / 100, $installment_number) - 1);

    for ($i = 0; $i < $number_installments; $i++) {

      $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

      $pagoInteres[$i] = ($value * ($valor_pago_interes / 100));
      $pagoCapital[$i] = $installment - $pagoInteres[$i];
      $value = ($value - $pagoCapital[$i]);

      foreach ($pagoCapital as $pc) {
        $listInstallments[$i]['pagoCapital'] = (float) number_format($pc, 2, '.', '');
      }
      foreach ($pagoInteres as $key => $pi) {
        $listInstallments[$i]['pagoInteres'] = (float) number_format($pi, 2, '.', '');
      }
      foreach ($payment_date as $fp) {
        $listInstallments[$i]['payment_date'] = (date($fp));
        $listInstallments[$i]['saldo_capital'] = (float) number_format($value, 2, '.', '');
        $listInstallments[$i]['installment_value'] = (float) number_format($installment, 2, '.', '');
      }
      $listInstallments[$i]['installment_number'] = $i + 1;
    }

    return ['listInstallments' => $listInstallments, 'installment' => (float) number_format($installment, 2, '.', '')];
  }

  /*
   * $id = id de la cuota a pagar
   * $amount =  Viene desde controlador 
   * $request = viene desde petición de cabeceras
   * Esto se usa en caso de que se pague una sola cuota
   * o multiples cuotas desde CreditController para abono a capital
   */

  public function payInstallment(Credit $credit, $amount = null, Request $request)
  {

    $quote = $request->quote_id != null || $request->quote_id != 0 ? true : false;
    $user_id = $request->user()->id;

    if ($amount == null) {
      //Valor de la cuota o abono
      $amount = $request->amount;
    }

    $amount_receipt = $amount;
    $now = now();
    $status = 0;

    // DB::enableQueryLog(); // Enable query log
    $installment = $credit->installments();
    if (!$quote) {
      $installment = $installment->whereDate('payment_date', '<=', $now);
    }

    $installment = $installment->where(function ($query) {
      $query
        ->whereRaw('((paid_capital) +0.1) < ((capital_value))')
        ->orWhereNull('paid_balance');
    })
      ->first();

    // dd(DB::getQueryLog()); // Show results of log

    if (($installment) == null && !$quote) {
      $installment = $credit->installments()
        ->where('payment_date', '>=', $now)
        ->first();
    }

    $amount_capital = 0;
    $interest = 0;
    $balance = 0;
    $days_past_due = 0;
    $no_installment = $installment->installment_number;

    //Se verifica el saldo restante del crédito
    if (!$quote) {
      $balance_credit = ($credit->credit_value - $credit->capital_value);
      $balance_credit  = $installment->paid_capital > 0 ? $balance_credit : $balance_credit + $installment->interest_value;
      if ($balance_credit <= $amount) {
        $amount = $balance_credit;
        $balance = $amount_receipt - $amount;
      }
    }

    $payment_date = Carbon::createFromFormat('Y-m-d', $installment->payment_date);

    if ($credit->credit_value > $credit->capital_value) {
      //Cuando el cliente paga a tiempo
      if ($payment_date >= $now) {
        //primer pago
        $status = 1;
        $amount_capital = $amount -  $installment->interest_value; //ok
        $interest = $installment->interest_value;
        if ($installment->paid_balance == null || $installment->paid_balance == 0) {
          if ($amount_capital < $installment->capital_value) {
            $status = 0;
          }
        }
        if (!$quote) {
          //cuando se ha realizado abono
          if ($installment->paid_balance > 0) {
            $amount_capital = $amount;
            $interest = 0;
          }
        }
      }

      //Cuando el cliente paga después de la fecha programada
      if ($payment_date < $now) {

        $days_past_due = $installment->days_past_due ? $installment->days_past_due :  $now->diffInDays($payment_date);
        $day_value_default = $installment->interest_value / 30;
        $late_interests_value = $day_value_default * $days_past_due;
        $installment->days_past_due  = $days_past_due > 30 ? 30 : $days_past_due;
        $installment->late_interests_value  = $late_interests_value;
        $interest = $installment->interest_value + $late_interests_value;

        $amount_capital = $amount -  $interest; //ok
        $status = $amount + 1 < ($installment->value + $late_interests_value) ? 0 : 1;
        $balance -= $late_interests_value;
        $balance = $balance < 0 ?? (int) 0;
      }

      $installment->paid_balance +=  ($amount_capital + $interest);
      $installment->paid_capital += $amount_capital;
      $installment->status  = $quote ? 1 : $status;
      $installment->payment_register = date('Y-m-d');

      if (!$installment->save()) {
        return false;
      }
      $credit_paid = new CreditController;
      $credit_paid->updateValuesCredit($request, $credit->id, $amount, $amount_capital, $interest);
      $entry_id =  $this->saveEntryInstallment($credit, $amount_receipt, $amount_capital + $interest, $no_installment, $balance, $user_id, $quote);
    }
    if (!$quote) {
      $this->updateInstallments($credit->id);
    }

    return ['balance' => $balance, 'no_installment' => $no_installment, 'entry_id' => $entry_id];
  }

  public function updateInstallments($credit_id)
  {
    $credit = Credit::findOrFail($credit_id);
    $capital = $credit->credit_value - $credit->capital_value;
    $installments = $credit->installments()
      ->where('status', 0)
      ->get();

    $interest = $credit->interest;
    $number_installments = count($installments);
    $start_date = date('Y-m-d');

    if ($credit->start_date && $credit->start_date != 'undefined') {
      $start_date = $credit->start_date;
    }

    $value = $capital;
    $valor_pago_interes = $interest;
    $installment_number = $number_installments;

    $payment_date = [];
    $fechaInicio = $start_date;
    $mes_actual =  (date("Y-m-d", strtotime($fechaInicio . "+ 1 months")));

    $listInstallments = [];
    $pagoInteres = [];
    $pagoCapital = [];

    if ($number_installments) {
      $installment =
        ($value *
          ((pow(1 + $valor_pago_interes / 100, $installment_number) *
            $valor_pago_interes) /
            100)) /
        (pow(1 + $valor_pago_interes / 100, $installment_number) - 1);

      for ($i = 0; $i < $number_installments; $i++) {
        $id_installment = $installments[$i]->id;
        $payment_date[$i] = (date("Y-m-d", strtotime($mes_actual . "+ $i months")));

        $pagoInteres[$i] = ($value * ($valor_pago_interes / 100));
        $pagoCapital[$i] = $installment - $pagoInteres[$i];
        $value = ($value - $pagoCapital[$i]);

        foreach ($pagoCapital as $pc) {
          $listInstallments[$i]['pagoCapital'] = (float) number_format($pc, 2, '.', '');
        }
        foreach ($pagoInteres as $key => $pi) {
          $listInstallments[$i]['pagoInteres'] = (float) number_format($pi, 2, '.', '');
        }
        foreach ($payment_date as $fp) {
          $listInstallments[$i]['payment_date'] = (date($fp));
          $listInstallments[$i]['saldo_capital'] = (float) number_format($value, 2, '.', '');
          $listInstallments[$i]['installment_value'] = (float) number_format($installment, 2, '.', '');
        }
        Installment::findOrFail($id_installment)->update(
          [
            'value' =>  $listInstallments[$i]['installment_value'],
            'interest_value' =>  $listInstallments[$i]['pagoInteres'],
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

  public function saveEntryInstallment(Credit $credit, $amount_receipt, $amount_paid, $no_installment, $balance,  $user_id, $quote)
  {
    $amount = $amount_paid;
    $amount_receipt = '$' . number_format($amount_receipt, 0, ',', '.');
    $amount_paid = '$' . number_format($amount_paid, 0, ',', '.');
    $balance = '$' . number_format($balance, 0, ',', '.');
    $type_entry = $quote ? 'Pago de cuota' : 'Abono a crédito';

    $client = $credit->client()->first();
    if ($amount > 0) {
      $entry =  new Entry();
      $entry->headquarter_id = $credit->headquarter_id;
      $entry->user_id = $user_id;
      $entry->credit_id = $credit->id;
      $entry->description =
        "Cliente: {$client->name} {$client->last_name}\n"
        . " Documento: {$client->type_document} {$client->document}"
        . "# crédito: {$credit->id} \n"
        . "# cuota: {$no_installment}\n"
        . "Efectivo: {$amount_receipt}\n"
        . "Valor pagado: {$amount_paid}\n"
        . "Regreso: {$balance} \n" .
        "Cupo crédito: {$client->maximum_credit_allowed}";
      $entry->date = date('Y-m-d');
      $entry->type_entry = $type_entry;
      $entry->price = $amount;
      $entry->save();
      return  $entry->id;
    }
  }


  public function printTable(Request $request)
  {
    $company = Company::first();

    $credit_id = $request->credit_id;
    $credit = Credit::where('id', $credit_id)->first();
    $client = $credit->client()->first();
    $installments = $credit->installments()->get();

    $details = [
      'company' => $company,
      'credit' => $credit,
      'client' => $client,
      'installments' => $installments,
      'url' => URL::to('/')
    ];

    $pdf = PDF::loadView('templates.amortization_table', $details);
    $pdf = $pdf->download('amortization_table.pdf');

    $data = [
      'status' => 200,
      'pdf' => base64_encode($pdf),
      'message' => 'Tabla generada en pdf'
    ];

    return response()->json($data);
  }

  public function reversePaymentInstallment(Request $request, $id)
  {
    date_default_timezone_set('America/Bogota');
    $headquarter_id = $request->user()->headquarter_id;
    $user_id = $request->user()->id;

    $installment = Installment::find($request->id);
    $paid_balance = $installment->paid_balance;
    $paid_capital =  $installment->paid_capital;
    $interest =  $installment->paid_balance - $installment->paid_capital;
    $credit = $installment->credit;

    $type_output = 'Reversar pago';
    $description = "Reversar pago \n" .
      "#Credito:  $credit->id \n" .
      "#Cuota Nro:  $installment->installment_number \n" .
      "#Cliente:  {$credit->client->name} {$credit->client->last_name}  \n" .
      "Fecha y hora: " . date('Y-m-d h:i:s A');

    // Certificar egreso
    $expense  = new ExpenseController();
    $expense->addExpense($user_id, $headquarter_id, $description, date('Y-m-d'), $type_output, $paid_balance);

    //Resetar valores de la cuota
    $installment->paid_balance = 0;
    $installment->paid_capital = 0;
    $installment->status = 0;
    $installment->save();

    //Restar valores en el crédito
    $credit_paid = new CreditController;
    $credit_paid->updateValuesCredit($request, $credit->id, $paid_balance * -1,  $paid_capital * -1, $interest * -1);

    //Actualizar valores de cuotas
    $this->updateInstallments($credit->id);
  }

  public function correctStatusInstallments()
  {
    $installments = Installment::where('capital_value', '<=', 'paid_capital')
      ->get();

    foreach ($installments as $installment) {
      $installment = Installment::find($installment->id);
      $installment->status = 1;
      $installment->save();
    }
  }
}
