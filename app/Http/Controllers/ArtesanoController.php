<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\CloudinaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArtesanoController extends Controller
{
    public function __construct(
        private CloudinaryService $cloudinary,
    ) {
    }

    public function perfil(): View
    {
        $usuario = session('usuario');

        $esArtesano = is_array($usuario)
            && ($usuario['rol'] ?? 'cliente') === 'artesano';

        return view('perfil-artesano', [
            'esArtesano' => $esArtesano,
            'nombre' => $esArtesano ? $usuario['nombre'] : 'María Gómez',
            'email' => $esArtesano ? ($usuario['email'] ?? null) : null,
        ]);
    }

    public function subirProducto(): View
    {
        return view('subir-producto', [
            'cloudinaryConfigurado' => $this->cloudinary->estaConfigurado(),
        ]);
    }

    public function guardarProducto(Request $request): RedirectResponse
    {
        // 1. Validar los datos del formulario
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['required', 'string', 'max:60'],
            'precio' => ['required', 'numeric', 'min:1'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'imagen' => ['required', 'file', 'image', 'max:4096'],
        ]);

        // 2. Preparar las variables de la imagen
        $imagenUrl = null;
        $publicId = null;

        $imagenNombre = $request->file('imagen')
            ? $request->file('imagen')->getClientOriginalName()
            : null;

        // 3. Subir la imagen a Cloudinary
        if ($request->hasFile('imagen')) {

            // Verificar que Cloudinary esté configurado
            if (!$this->cloudinary->estaConfigurado()) {
                return back()
                    ->withErrors([
                        'imagen' => 'Cloudinary no está configurado correctamente.'
                    ])
                    ->withInput();
            }

            try {
                $subida = $this->cloudinary->subirImagen(
                    $request->file('imagen'),
                    'artcraft/productos'
                );

                // Verificar que la subida fue exitosa
                if (
                    !$subida ||
                    empty($subida['secure_url']) ||
                    empty($subida['public_id'])
                ) {
                    return back()
                        ->withErrors([
                            'imagen' => 'No se pudo subir la imagen a Cloudinary.'
                        ])
                        ->withInput();
                }

                // Guardar los datos devueltos por Cloudinary
                $imagenUrl = $subida['secure_url'];
                $publicId = $subida['public_id'];

            } catch (\Throwable $e) {

                report($e);

                return back()
                    ->withErrors([
                        'imagen' => 'Ocurrió un error al subir la imagen. Inténtalo nuevamente.'
                    ])
                    ->withInput();
            }
        }

        // 4. Registrar el producto en MySQL
        Producto::create([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'categoria' => $datos['categoria'],
            'precio' => (int) $datos['precio'],
            'imagen' => $imagenNombre,
            'cloudinary_public_id' => $publicId,
            'cloudinary_imagen_url' => $imagenUrl,
            'ruta_detalle' => 'mostrar-producto',
            'destacado' => false,
        ]);

        // 5. Mostrar mensaje de confirmación
        return back()->with(
            'mensaje',
            'Producto '.$datos['nombre'].' registrado correctamente.'
        );
    }
}


