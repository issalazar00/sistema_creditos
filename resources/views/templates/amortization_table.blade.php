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
    <img src="{{ $company->logo }}" alt="" srcset="" width="100">
    <header class="text-center">
        <h2>
            {{ $company->name }}
        </h2>
        <h6 class="font-weight-bold">
            Cupo de crédito: $ {{ number_format($client->maximum_credit_allowed, 0, '', '.') }}
        </h6>
        <h3>
            Calendario de pagos
        </h3>
    </header>
    <section>
        <table class="table">
            <tr>
                <td>Cliente: {{ $client->name }} {{ $client->last_name }}</td>
                <td>Nro. Documento {{ $client->document }}</td>
                <td>Nro. Crédito: {{ $credit->id }}</td>
            </tr>
            <tr>
                <td>Monto: $ {{ number_format($credit->credit_value, 0, '', '.') }}</td>
                <td>Nro. Cuotas: {{ $credit->number_installments }}</td>
                <td>Fecha de desembolso: {{ $credit->disbursement_date }}</td>
            </tr>
            <tr>
                <td>Producto: {{ $credit->description }}</td>
                <td>Sucursal</td>
                <td>Tasa de Interés mensual: {{ $credit->interest }} %</td>
            </tr>
            @if (count($debtors))
                <tr>
                    <td colspan="3" class="text-center"><b> Codeudor (es)</b></td>
                </tr>
                @foreach ($debtors as $debtor)
                    <tr>
                        <td>Codeudor: {{ $debtor->name }} {{ $debtor->last_name }}</td>
                        <td >Nro. Documento {{ $debtor->document }}</td>
                        <td >Teléfonos: {{ $debtor->phone_1 }}  {{ $debtor->phone_2 }}</td>
                    </tr>
                @endforeach
            @endif
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
                    <th>Abono a Interés Cobro & Admin</th>
                    <th>Saldo capital</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($installments as $key => $f)
                    <tr>
                        <th>{{ $key + 1 }}</th>
                        <th scope="row">{{ $f->payment_date }}</th>
                        <td>$ {{ number_format($f->value, 0, '', '.') }}</td>
                        <td> $ {{ number_format($f->capital_value, 0, '', '.') }}</td>
                        <td> $ {{ number_format($f->interest_value, 0, '', '.') }}</td>
                        <td> $ {{ number_format($f->additional_interest_value, 0, '', '.') }}</td>
                        <td>$ {{ number_format($f->capital_balance, 0, '', '.') }}</td>

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
