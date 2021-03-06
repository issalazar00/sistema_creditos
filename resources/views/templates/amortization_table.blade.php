<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
    }

    .table {
      border-collapse: collapse;
      width: 100%;
    }

    .table th,
    .table td {
      border: 1px solid #E4E4E4;
      padding: 5px;
    }

    .table th,
    table th {
      text-align: left;
    }

    .text-center {
      text-align: center !important;
    }
  </style>
</head>

<body>
  <img src="{{ $company->logo}}" alt="" srcset="" width="100">
  <header class="text-center">
    <h2>
      {{ $company->name}}
    </h2>
    <h6 class="font-weight-bold">
      Cupo de crédito: {{$client->maximum_credit_allowed}}
    </h6>
    <h3>
      Calendario de pagos
    </h3>
  </header>
  <section>
    <table class="table">
      <tr>
        <td>Cliente: {{$client->name}} {{$client->last_name}}</td>
        <td>Nro. Documento {{$client->document}}</td>
        <td>Nro. Crédito: {{ $credit->id}}</td>
      </tr>
      <tr>
        <td>Monto: $ {{ $credit->credit_value}}</td>
        <td>Nro. Cuotas: {{ $credit->number_installments}}</td>
        <td>Fecha de desembolso: {{ $credit->disbursement_date}}</td>
      </tr>
      <tr>
        <td>Producto: {{ $credit->description}}</td>
        <td>Sucursal</td>
        <td>Tasa de Interés mensual: {{ $credit->interest}}</td>
      </tr>
      <tr>
        <td colspan="3"></td>
      </tr>
    </table>
  </section>
  <section>
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Valor</th>
          <th>Abono Capital</th>
          <th>Abono Interés</th>
          <th>Saldo capital</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($installments as $key => $f)
        <tr>
          <th>{{$key + 1}}</th>
          <th scope="row">{{ $f->payment_date}}</th>
          <td>{{ $f->value}}</td>
          <td>{{ $f->capital_value}}</td>
          <td>{{ $f->interest_value}}</td>
          <td>{{ $f->capital_balance}}</td>
       
        </tr>
        @endforeach

      </tbody>
    </table>
  </section>
  <p>
    <small style="margin-top: 20px">
      {!! $company->condition_quotation !!}
    </small>
  </p>
  <p>
    <small style="margin-top: 20px">
      {{ $company->address }}
    </small>
  </p>

</body>

</html>