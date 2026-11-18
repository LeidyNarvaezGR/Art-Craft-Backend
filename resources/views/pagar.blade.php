@extends('layouts.app')

@section('titulo', 'Pagar')

@push('estilos')
<link rel="stylesheet" data-turbo-track="dynamic" href="{{ asset('css/pagar.css') }}?v={{ filemtime(public_path('css/pagar.css')) }}">
@endpush

@section('contenido')
<main class="pago-pagina">
    <header class="pago-cabecera">
        <h1>Finalizar compra</h1>
        <p>Revisa tus datos y confirma tu pedido.</p>
    </header>

    @if (session('mensaje'))
        <p class="alerta alerta-exito pago-alerta">{{ session('mensaje') }}</p>
    @endif

    @if (! $wompiConfigurado)
        <div class="alerta alerta-aviso">
            <strong>Modo demo:</strong> Wompi no está configurado. Define <code>WOMPI_PUBLIC_KEY</code>,
            <code>WOMPI_PRIVATE_KEY</code> y <code>WOMPI_INTEGRITY_SECRET</code> en tu <code>.env</code> para
            activar pagos con tarjeta. Mientras tanto, los pagos por transferencia y contraentrega funcionan en modo simulación.
        </div>
    @endif

    <div class="checkout-container">
        <section class="billing-details">
            <h2>Detalles de facturación</h2>

            <div class="campos">
                <div class="campo">
                    <label for="pago-nombre">Nombre completo *</label>
                    <input id="pago-nombre" type="text" placeholder="Nombre y apellidos" autocomplete="name" required>
                </div>

                <div class="campo">
                    <label for="pago-email">Correo electrónico *</label>
                    <input id="pago-email" type="email" placeholder="tuemail@ejemplo.com" autocomplete="email" required>
                </div>

                <div class="campo">
                    <label for="pago-telefono">Teléfono *</label>
                    <input id="pago-telefono" type="tel" placeholder="+57 300 000 0000" autocomplete="tel" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="pago-direccion">Dirección *</label>
                    <input id="pago-direccion" type="text" placeholder="Número de casa y calle" autocomplete="street-address" required>
                </div>

                <div class="campo">
                    <label for="pago-ciudad">Ciudad *</label>
                    <input id="pago-ciudad" type="text" placeholder="Ciudad" autocomplete="address-level2" required>
                </div>
            </div>
        </section>

        <section class="order-summary">
            <h2>Tu pedido</h2>

            <table>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Subtotal</th>
                </tr>

                @forelse ($filas as $fila)
                    <tr>
                        <td>{{ $fila['producto']->nombre }}</td>
                        <td>{{ $fila['cantidad'] }}</td>
                        <td>$ {{ number_format($fila['total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">No has agregado productos todavía.</td>
                    </tr>
                @endforelse

                <tr>
                    <td>Subtotal</td>
                    <td></td>
                    <td>$ {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Envío</td>
                    <td></td>
                    <td>$ {{ number_format($envio, 0, ',', '.') }}</td>
                </tr>
            </table>

            <p class="total">Total a pagar: <strong>$ {{ number_format($total, 0, ',', '.') }}</strong></p>

            @unless ($filas)
                <a href="{{ route('categorias') }}" class="volcar-carrito">Ver productos</a>
            @endunless

            <div class="payment-methods">
                <h3>Método de pago</h3>

                <input type="radio" name="metodo-pago" id="tab-tarjeta" value="tarjeta" checked>
                <input type="radio" name="metodo-pago" id="tab-transferencia" value="transferencia">
                <input type="radio" name="metodo-pago" id="tab-contraentrega" value="contraentrega">

                <div class="tabs">
                    <label for="tab-tarjeta" class="tab-label label-tarjeta">Tarjeta (Wompi)</label>
                    <label for="tab-transferencia" class="tab-label label-transferencia">Transferencia bancaria</label>
                    <label for="tab-contraentrega" class="tab-label label-contraentrega">Contraentrega</label>
                </div>

                <div class="payment-content">

                    <div class="payment-section seccion-tarjeta">
                        @if ($wompiConfigurado)
                            <p class="wompi-badge"><i class="fa-solid fa-lock"></i> Pago seguro procesado por <strong>Wompi</strong></p>
                        @else
                            <p class="wompi-badge alerta-aviso"><i class="fa-solid fa-triangle-exclamation"></i> Modo demo: Wompi no configurado</p>
                        @endif
                        <div class="card-logos-inline">
                            <img src="{{ asset('imagenes/Visa.png') }}" alt="Visa">
                            <img src="{{ asset('imagenes/Mastercard.png') }}" alt="Mastercard">
                        </div>
                    </div>

                    <div class="payment-section seccion-transferencia">
                        <p>Realiza tu pago mediante transferencia bancaria a la siguiente cuenta:</p>
                        <ul>
                            <li><strong>Banco:</strong> Bancolombia</li>
                            <li><strong>Tipo de cuenta:</strong> Ahorros</li>
                            <li><strong>Número:</strong> 123-456-789</li>
                            <li><strong>Titular:</strong> Art Craft S.A.S.</li>
                        </ul>
                        <p>Envía el comprobante al correo <strong>artcraft@gmail.com</strong>.</p>
                    </div>

                    <div class="payment-section seccion-contraentrega">
                        <p>Tu pedido será entregado en la dirección registrada y podrás pagar en efectivo directamente al recibirlo en tu hogar.</p>
                    </div>

                </div>

                <button id="btn-pagar" class="pay-btn" type="button">Realizar el pedido</button>
                <div id="estado-pago" class="estado-pago" hidden></div>
            </div>
        </section>
    </div>

    <p class="pago-volver">
        <a href="{{ route('carrito') }}">
            <i class="fa-solid fa-arrow-left"></i> Volver al carrito
        </a>
    </p>
</main>
@endsection

@push('scripts')
<script>
    (function () {
        const btnPagar = document.getElementById('btn-pagar');
        const estadoPago = document.getElementById('estado-pago');
        const metodoSeleccionado = () => document.querySelector('input[name="metodo-pago"]:checked').value;

        const mostrarEstado = (texto, tipo) => {
            estadoPago.hidden = false;
            estadoPago.className = 'estado-pago ' + (tipo || '');
            estadoPago.textContent = texto;
        };

        const ocultarEstado = () => {
            estadoPago.hidden = true;
            estadoPago.textContent = '';
        };

        btnPagar.addEventListener('click', async function () {
            ocultarEstado();

            const nombre = document.getElementById('pago-nombre').value.trim();
            const email = document.getElementById('pago-email').value.trim();
            const telefono = document.getElementById('pago-telefono').value.trim();
            const direccion = document.getElementById('pago-direccion').value.trim();
            const ciudad = document.getElementById('pago-ciudad').value.trim();
            const metodo = metodoSeleccionado();

            if (!nombre || !email || !telefono || !direccion || !ciudad) {
                mostrarEstado('Por favor completa todos los campos de facturación.', 'error');
                return;
            }

            btnPagar.disabled = true;
            btnPagar.textContent = 'Procesando...';

            try {
                const respuesta = await fetch('{{ route('pago.crear') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nombre,
                        email,
                        telefono,
                        direccion,
                        ciudad,
                        metodo_pago: metodo,
                    }),
                });

                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.ok) {
                    mostrarEstado(datos.error || 'No se pudo procesar el pedido. Intenta de nuevo.', 'error');
                    btnPagar.disabled = false;
                    btnPagar.textContent = 'Realizar el pedido';
                    return;
                }

                // Contraentrega o transferencia: se simula el éxito y se va a la página de respuesta.
                if (metodo !== 'tarjeta') {
                    mostrarEstado('Pedido registrado correctamente. Redirigiendo...', 'exito');
                    setTimeout(() => {
                        window.location.href = datos.redirect || '{{ route('pago.respuesta', ['orden' => 0]) }}'.replace('0', datos.orden_id);
                    }, 1200);
                    return;
                }

                // Tarjeta: redirigir al checkout seguro de Wompi.
                if (datos.checkout_url) {
                    mostrarEstado('Redirigiendo a Wompi para completar el pago...', 'exito');
                    setTimeout(() => {
                        window.location.href = datos.checkout_url;
                    }, 800);
                    return;
                }

                // Fallback: si Wompi devolvió redirect_url, usarlo.
                if (datos.redirect_url) {
                    window.location.href = datos.redirect_url;
                    return;
                }

                mostrarEstado('No se obtuvo una URL de pago de Wompi.', 'error');
                btnPagar.disabled = false;
                btnPagar.textContent = 'Realizar el pedido';
            } catch (error) {
                console.error(error);
                mostrarEstado('Error de conexión al procesar el pago.', 'error');
                btnPagar.disabled = false;
                btnPagar.textContent = 'Realizar el pedido';
            }
        });
    })();
</script>
@endpush
