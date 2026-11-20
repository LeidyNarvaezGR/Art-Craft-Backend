<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ArtesanoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;

/*
|--------------------------------------------------------------------------
| API Routes - Art Craft Backend
|--------------------------------------------------------------------------
|
| Estas rutas están pensadas para ser consumidas por un frontend separado
| (React, Vue, móvil, etc.). El frontend original (ArtCraft-main) puede
| consumirlas vía fetch/axios.
*/

// --- Catálogo de productos (público) ---

Route::get('/catalogo', [CatalogoController::class, 'apiCatalogo'])->name('api.catalogo');
Route::get('/catalogo/bisuteria', [CatalogoController::class, 'bisuteria']);
Route::get('/catalogo/ceramica', [CatalogoController::class, 'ceramica']);
Route::get('/catalogo/mascaras', [CatalogoController::class, 'mascaras']);
Route::get('/catalogo/buscar', [CatalogoController::class, 'busqueda']);
Route::get('/producto/{producto}', [CatalogoController::class, 'mostrarProducto']);

// --- Carrito (sesión del backend) ---
Route::get('/carrito', [App\Http\Controllers\CarritoController::class, 'index'])->name('api.carrito');
Route::post('/carrito/agregar', [App\Http\Controllers\CarritoController::class, 'agregar'])->name('api.carrito.agregar');
Route::post('/carrito/actualizar', [App\Http\Controllers\CarritoController::class, 'actualizar'])->name('api.carrito.actualizar');
Route::post('/carrito/quitar', [App\Http\Controllers\CarritoController::class, 'quitar'])->name('api.carrito.quitar');
Route::post('/carrito/vaciar', [App\Http\Controllers\CarritoController::class, 'vaciar'])->name('api.carrito.vaciar');

// Crear una orden desde el carrito
Route::post('/pago/crear', [PagoController::class, 'crear'])
    ->name('api.pago.crear');

// Consultar el estado de una orden
Route::get('/pago/consultar/{orden}', [PagoController::class, 'consultar'])
    ->name('api.pago.consultar');

// Consultar las órdenes del usuario
Route::get('/mis-ordenes', [PagoController::class, 'misOrdenes'])
    ->name('api.mis.ordenes');

// --- Subida de productos (artesano) ---
Route::post('/productos', [ArtesanoController::class, 'guardarProducto'])->name('api.productos.guardar');
Route::get('/catalogo/destacados', [CatalogoController::class, 'masVendidos']);


if (app()->environment('local')) {
    Route::post(
        '/pago/confirmar-simulado',
        [PagoController::class, 'confirmarSimulado']
    );
}

Route::post('/chatbot/mensaje', [
    \App\Http\Controllers\ChatbotController::class,
    'mensaje'
])->middleware('throttle:20,1');


Route::post('/productos/remoto', function (Request $request) {

    $token = $request->bearerToken();
    $tokenEsperado = config('services.artcraft.api_token');

    if (
        !is_string($token) ||
        !is_string($tokenEsperado) ||
        $tokenEsperado === '' ||
        !hash_equals($tokenEsperado, $token)
    ) {
        return response()->json([
            'mensaje' => 'No autorizado'
        ], 401);
    }

    $datos = $request->validate([
        'nombre' => ['required', 'string', 'max:120'],
        'categoria' => ['required', 'string', 'max:60'],
        'precio' => ['required', 'integer', 'min:1'],
        'descripcion' => ['required', 'string', 'max:1000'],
        'imagen' => ['required', 'string', 'max:255'],
        'cloudinary_public_id' => ['required', 'string', 'max:255'],
        'cloudinary_imagen_url' => ['required', 'url', 'max:255'],
    ]);

    $producto = \App\Models\Producto::create([
        ...$datos,
        'ruta_detalle' => 'mostrar-producto',
        'destacado' => false,
    ]);

    return response()->json([
        'mensaje' => 'Producto registrado correctamente',
        'producto' => $producto,
    ], 201);
});