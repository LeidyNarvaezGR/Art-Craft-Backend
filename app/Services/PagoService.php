<?php

namespace App\Services;

use App\Models\Orden;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoService
{
    public function procesarPago(
        int $ordenId,
        string $metodoPago,
        string $resultado
    ) {
        $resultadosPermitidos = [
            'aprobado',
            'rechazado',
            'pendiente',
        ];

        $metodosPermitidos = [
            'tarjeta',
            'nequi',
            'pse',
        ];

        if (!in_array($resultado, $resultadosPermitidos, true)) {
            throw ValidationException::withMessages([
                'resultado' => 'Resultado de pago no válido.',
            ]);
        }

        if (!in_array($metodoPago, $metodosPermitidos, true)) {
            throw ValidationException::withMessages([
                'metodo_pago' => 'Método de pago no válido.',
            ]);
        }

        return DB::transaction(function () use (
            $ordenId,
            $metodoPago,
            $resultado
        ) {
            $orden = Orden::whereKey($ordenId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($orden->estado_pago === 'aprobado') {
                throw ValidationException::withMessages([
                    'orden_id' => 'Esta orden ya fue pagada.',
                ]);
            }

            $orden->metodo_pago = $metodoPago;
            $orden->estado_pago = $resultado;

            $orden->fecha_pago = $resultado === 'aprobado'
                ? now()
                : null;

            $orden->save();

            return $orden;
        });
    }
}