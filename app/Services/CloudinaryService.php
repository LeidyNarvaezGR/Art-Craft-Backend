<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    private \Cloudinary\Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = $this->crearInstancia();
    }

    private function crearInstancia(): \Cloudinary\Cloudinary
    {
        $config = config('services.cloudinary', []);

        if (
            empty($config['cloud_name']) ||
            empty($config['api_key']) ||
            empty($config['api_secret'])
        ) {
            return new \Cloudinary\Cloudinary();
        }

        return new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => $config['cloud_name'],
                'api_key' => $config['api_key'],
                'api_secret' => $config['api_secret'],
                'secure' => true,
            ],
        ]);
    }

    
    public function estaConfigurado(): bool
    {
        $config = config('services.cloudinary', []);

        return !empty($config['cloud_name'])
            && !empty($config['api_key'])
            && !empty($config['api_secret']);
    }

    /**
     * Sube una imagen a Cloudinary.
     *
     * @param UploadedFile|string $file
     * @param string $folder
     * @return array|null
     */
    public function subirImagen(
        $file,
        string $folder = 'artcraft/productos'
    ): ?array {

        // 1. Verificar la configuración
        if (!$this->estaConfigurado()) {
            Log::warning(
                'Cloudinary no está configurado correctamente.'
            );

            return null;
        }

        try {

            $rutaArchivo = $file instanceof UploadedFile
                ? $file->getRealPath()
                : $file;

            
            $respuesta = $this->cloudinary
                ->uploadApi()
                ->upload($rutaArchivo, [
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'transformation' => [
                        'quality' => 'auto:good',
                        'fetch_format' => 'auto',
                    ],
                ]);

            // 4. Obtener los datos de Cloudinary
            $publicId = $respuesta['public_id'] ?? null;
            $secureUrl = $respuesta['secure_url'] ?? null;
            $version = $respuesta['version'] ?? null;

            // 5. Verificar que la imagen se subió
            if (!$publicId || !$secureUrl) {

                Log::error(
                    'Cloudinary no devolvió public_id o secure_url.'
                );

                return null;
            }

            // 6. Devolver los datos de la imagen
            return [
                'public_id' => $publicId,
                'secure_url' => $secureUrl,
                'version' => $version,
            ];

        } catch (\Throwable $e) {

            Log::error(
                'Error subiendo imagen a Cloudinary: ' .
                $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Genera la URL de una imagen almacenada en Cloudinary.
     */
    public function obtenerUrl(
        string $publicId,
        array $transformaciones = []
    ): string {

        $config = config('services.cloudinary', []);

        if (
            !$this->estaConfigurado() ||
            empty($config['cloud_name'])
        ) {
            return asset('imagenes/sin-imagen.png');
        }

        $transformacion = !empty($transformaciones)
            ? implode('/', $transformaciones) . '/'
            : '';

        return 'https://res.cloudinary.com/' .
            $config['cloud_name'] .
            '/image/upload/' .
            $transformacion .
            $publicId;
    }

    /**
     * Elimina una imagen de Cloudinary.
     */
    public function eliminarImagen(string $publicId): bool
    {
        if (!$this->estaConfigurado()) {
            return false;
        }

        try {

            $respuesta = $this->cloudinary
                ->uploadApi()
                ->destroy($publicId);

            return ($respuesta['result'] ?? null) === 'ok';

        } catch (\Throwable $e) {

            Log::error(
                'Error eliminando imagen de Cloudinary: ' .
                $e->getMessage()
            );

            return false;
        }
    }
}