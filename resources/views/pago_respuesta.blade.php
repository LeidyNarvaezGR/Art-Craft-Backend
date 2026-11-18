@extends('layouts.app')

@section('titulo', 'Estado de tu pago')

@push('estilos')
<link rel="stylesheet" data-turbo-track="dynamic" href="{{ asset('css/pagar.css') }}?v={{ filemtime(public_path('css/pagar.css')) }}">
<style>
    .resultado-pago { text-align: center; padding: 40px 20px; max-width: 520px; margin: 0 auto; }
    .icono-estado { font-size: 64px; margin-bottom: 16px; display: block; }
    .estado-aprobado { color: #15803d; }
    .estado-declinado, .estado-error { color: #b91c1c; }
    .estado-pendiente { color: #b45309; }
    .btn-principal { display: inline-block; margin-top: 20px; padding: 12px 28px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
    .btn-principal:hover { background: #1d4ed8; }
    .tabla-resumen { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .tabla-resumen th, .tabla-resumen td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
    .tabla-resumen .monto { text-align: right; }
</style>
@endpush

@section('contenido')
<main class="pago-pagina">
    <header class="pago-cabecera">
        <h1>Estado de tu pago</h1>
        <p>Referencia: <strong>{{ $orden->referencia }}</strong></p>
    </header>

    <div class="resultado-pago">
        @php
            $estadoClave = strtolower($orden->estado);
            $esAprobado = $estadoClave === 'aprobado';
            $esDeclinado = $estadoClave === 'declinado';
            $esError = $estadoClave === 'error';
            $esPendiente = $estadoClave === 'pendiente';
        @endphp

        @if ($esAprobado)
            <span class="icono-estado estado-aprobado"><i class="fa-solid fa-circle-check"></i></span>
            <h2>¡Pago aprobado!</h2>
            <p>Tu pago ha sido procesado correctamente. Gracias por tu compra en Art Craft.</p>
        @elseif ($esDeclinado)
            <span class="icono-estado estado-declinado"><i class="fa-solid fa-circle-exclamation"></i></span>
            <h2>Pago declinado</h2>
            <p>No se pudo completar el pago. Intenta con otro método de pago o comunícate con nosotros.</p>
        @elseif ($esError)
            <span class="icono-estado estado-error"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <h2>Error al procesar el pago</h2>
            <p>Hubo un problema al procesar tu pago. Intenta de nuevo más tarde.</p>
        @else
            <span class="icono-estado estado-pendiente"><i class="fa-solid fa-circle-notch fa-spin"></i></span>
            <h2>Pago pendiente</h2>
            <p>Tu pago está siendo procesado. Te notificaremos tan pronto se confirme.</p>
        @endif

        <table class="tabla-resumen">
            <tr>
                <th>Orden</th>
                <td>{{ $orden->id }}</td>
            </tr>
            <tr>
                <th>Referencia</th>
                <td>{{ $orden->referencia }}</td>
            </tr>
            <tr>
                <th>Estado</th>
                <td>
                    <span class="estado-{{ $estadoClave }}">
                        {{ ucfirst($orden->estado) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Total</th>
                <td class="monto">$ {{ number_format($orden->total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Fecha</th>
                <td>{{ $orden->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if ($orden->wompi_transaction_id)
                <tr>
                    <th>Transacción Wompi</th>
                    <td>{{ $orden->wompi_transaction_id }}</td>
                </tr>
            @endif
        </table>

        <a href="{{ route('categorias') }}" class="btn-principal">Seguir comprando</a>
        <a href="{{ route('mis.ordenes') }}" class="btn-principal" style="margin-left:10px; background:#6b7280;">Ver mis pedidos</a>
    </div>
</main>
@endsection
