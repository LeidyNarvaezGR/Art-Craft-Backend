<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\CloudinaryService;

class SubirImagenesCloudinary extends Command
{
   protected $signature = 'cloudinary:subir-imagenes';

    protected $description = 'Subir las imagenes locales de ART CRAFT a Cloudinary';

    public function handle(CloudinaryService $cloudinary): int
    {
        if (!$cloudinary->estaConfigurado()) {
            $this->error('Cloudinary no esta configurado.');
            return self::FAILURE;
        }

        $carpeta = public_path('imagenes');

        if (!File::isDirectory($carpeta)) {
            $this->error('No existe la carpeta public/imagenes.');
            return self::FAILURE;
        }

        $archivos = File::files($carpeta);

        $subidas = 0;
        $fallidas = 0;

        foreach ($archivos as $archivo) {
            $extension = strtolower($archivo->getExtension());

            if (!in_array($extension, [
                'jpg', 'jpeg', 'png', 'webp', 'gif'
            ], true)) {
                continue;
            }

            $nombre = $archivo->getFilename();

            $this->info("Subiendo: {$nombre}");

            $resultado = $cloudinary->subirImagen(
                $archivo->getRealPath(),
                'artcraft/imagenes'
            );

            if ($resultado) {
                $subidas++;

                $this->info(
                    'Correcto: '.$resultado['secure_url']
                );
            } else {
                $fallidas++;

                $this->error(
                    "No se pudo subir: {$nombre}"
                );
            }
        }

        $this->newLine();

        $this->info("Imagenes subidas: {$subidas}");

        if ($fallidas > 0) {
            $this->warn("Imagenes fallidas: {$fallidas}");
        }

        return $fallidas > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}