<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Información de egreso</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
</head>

<body>

    <header class="text-center">
        <img src="{{ $company->logo}}" alt="" srcset="" width="150">
        <h2>
            {{ $company->name}}
        </h2>
        <h3>
            Sede {{$headquarter->headquarter}}
        </h3>
        <p>NIT:{{$headquarter->nit}} </p>
        <p>Dirección: {{$headquarter->address}}</p>
        <p>Contacto: {{$headquarter->telephone ?? $headquarter->telephone ." ".  $headquarter->mobile ?? $headquarter->mobile }}</p>
        <br>
        <h2><strong>{{ $expense->type_movement}}</strong></h2>
        <h3><strong>{{ $expense->type_output}}</strong></h3>
    </header>
    <section>
        <table class="table">
            <tr border=0>
                <td>Nro. Egreso: </td>
                <td> #{{ $expense->id}}</td>
            </tr>
            <tr>
                <td>Usuario responsable: </td>
                <td>{{$user->name}} {{$user->last_name}}</td>
            </tr>
            <tr>
                <td>Nro. Documento </td>
                <td>{{$user->type_document}} {{$user->document}}</td>
            </tr>
            <tr>
                <td>Monto cancelado: </td>
                <td>$ {{ number_format($expense->price, 0, '', '.') }}</td>
            </tr>
            <tr>
                <td>Fecha: </td>
                <td>{{ $expense->date}}</td>
            </tr>
            <tr>
                <td>Producto: </td>
                <td>{{ $expense->description}}</td>
            </tr>
            <tr>
                <td colspan="3"></td>
            </tr>
        </table>
    </section>
    <br><br>
    <footer>
        <small>Tecnoplus Créditos</small> <br>
        <small>
            Fecha de impresión {{ date('Y-m-d H:i:s A')}}
        </small>
    </footer>


</body>

</html>