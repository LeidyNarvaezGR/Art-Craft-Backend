<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function mensaje(Request $request)
    {
        $datos = $request->validate([
            'mensaje' => 'required|string|max:1000',
        ]);

        $clave = config('services.groq.key');
        $modelo = config('services.groq.model', 'llama-3.1-8b-instant');

        if (empty($clave)) {
            return response()->json([
                'ok' => false,
                'error' => 'La clave de Groq no está configurada.',
            ], 500);
        }

        try {
            $respuesta = Http::withToken($clave)
                ->acceptJson()
                ->timeout(45)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $modelo,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres el asistente virtual de ART CRAFT, una plataforma de venta de artesanías. Responde siempre en español, de manera amable, clara y breve. Ayuda con preguntas sobre productos artesanales, carrito de compras, pedidos y pagos. Los pagos actuales son simulados. No inventes precios, disponibilidad, datos de clientes ni estados de pedidos. Si necesitas información que no tienes, indícalo.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $datos['mensaje'],
                        ],
                    ],
                    'temperature' => 0.5,
                    'max_tokens' => 400,
                ]);

            if ($respuesta->failed()) {
                report(new \RuntimeException(
                    'Error de Groq. HTTP ' . $respuesta->status()
                ));

                return response()->json([
                    'ok' => false,
                    'error' => 'No fue posible obtener una respuesta de Groq.',
                ], 502);
            }

            $texto = $respuesta->json('choices.0.message.content');

            return response()->json([
                'ok' => true,
                'respuesta' => $texto ?: 'No se recibió una respuesta.',
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'El asistente no está disponible en este momento.',
            ], 502);
        }
    }
}