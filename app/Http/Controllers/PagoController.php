<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PagoController extends Controller
{
    // Solo se permiten pagos de prueba en desarrollo.
    private function verificarEntorno(): void
    {
        abort_unless(
            app()->environment('local'),
            403,
            'Los pagos simulados solo están disponibles en desarrollo.'
        );
    }

    public function crear(Request $request)
    {
        $this->verificarEntorno();

        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'email' => 'required|email|max:255',
            'telefono' => 'required|string|max:30',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:100',
            'metodo_pago' => 'required|in:tarjeta,nequi,pse',
            'intento_compra' => 'required|uuid',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer|distinct|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1|max:100',
        ]);
      
        // Revisar si este intento de compra ya fue registrado.
        $ordenExistente = Orden::where(
            'intento_compra',
            $datos['intento_compra']
        )->first();

        if ($ordenExistente) {
            return response()->json([
                'success' => true,
                'mensaje' => 'Esta compra ya estaba registrada.',
                'orden_id' => $ordenExistente->id,
                'referencia' => $ordenExistente->referencia,
                'total' => $ordenExistente->total,
                'estado_pago' => $ordenExistente->estado_pago,
            ], 200);
        }

        $ids = collect($datos['productos'])->pluck('id');

        $productos = Producto::whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $detalles = [];

        foreach ($datos['productos'] as $item) {
            $producto = $productos->get($item['id']);

            if (! $producto) {
                throw ValidationException::withMessages([
                    'productos' => 'Uno de los productos no existe.',
                ]);
            }

            $cantidad = (int) $item['cantidad'];
            $precio = (int) $producto->precio;
            $totalItem = $precio * $cantidad;

            $subtotal += $totalItem;

            $detalles[] = [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $totalItem,
            ];
        }

        $envio = 10000;
        $total = $subtotal + $envio;

        $orden = DB::transaction(function () use (
            $datos,
            $subtotal,
            $envio,
            $total,
            $detalles
        ) {
            $orden = Orden::create([
                'intento_compra' => $datos['intento_compra'],
                'usuario_id' => null,
                'nombre_cliente' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'],
                'direccion' => $datos['direccion'],
                'ciudad' => $datos['ciudad'],
                'referencia' => 'ART-' . Str::uuid(),
                'subtotal' => $subtotal,
                'envio' => $envio,
                'total' => $total,
                'moneda' => 'COP',
                'estado' => 'pendiente',
                'metodo_pago' => $datos['metodo_pago'],
                'estado_pago' => 'pendiente',
            ]);

            $orden->items()->createMany($detalles);

            return $orden->load('items');
        });

        return response()->json([
            'success' => true,
            'mensaje' => 'Orden de prueba creada correctamente.',
            'orden_id' => $orden->id,
            'referencia' => $orden->referencia,
            'total' => $orden->total,
            'estado_pago' => $orden->estado_pago,
        ], 201);
    }


public function confirmarSimulado(Request $request)
{
    $this->verificarEntorno();

    // Comprobar que la solicitud venga de nuestro frontend.
    $clave = config('services.artcraft_demo.key');

    abort_unless(
        is_string($clave)
            && $clave !== ''
            && hash_equals(
                $clave,
                (string) $request->header('X-Artcraft-Demo-Key')
            ),
        403,
        'No autorizado.'
    );

    // Validar los datos de la orden.
    $datos = $request->validate([
        'orden_id' => 'required|integer|exists:ordenes,id',
        'referencia' => 'required|string',
    ]);

    $orden = DB::transaction(function () use ($datos) {

        $orden = Orden::whereKey($datos['orden_id'])
            ->lockForUpdate()
            ->firstOrFail();

        // Verificar que la referencia sea correcta.
        if ($orden->referencia !== $datos['referencia']) {
            abort(409, 'La referencia de la orden no coincide.');
        }

        // Si ya está aprobado, no modificarlo nuevamente.
        if ($orden->estado_pago === 'aprobado') {
            return $orden;
        }

        // Solo se pueden aprobar órdenes pendientes.
        if ($orden->estado_pago !== 'pendiente') {
            abort(409, 'Esta orden no se puede confirmar.');
        }

        // Aprobar el pago simulado.
        $orden->estado_pago = 'aprobado';
        $orden->estado = 'confirmada';
        $orden->fecha_pago = now();

        $orden->save();

        return $orden;
    });

    return response()->json([
        'success' => true,
        'mensaje' => 'Pago de demostración aprobado.',
        'orden_id' => $orden->id,
        'referencia' => $orden->referencia,
        'estado_pago' => $orden->estado_pago,
        'total' => $orden->total,
    ]);
}
}