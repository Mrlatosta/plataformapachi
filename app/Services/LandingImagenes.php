<?php

namespace App\Services;

use App\Models\LandingImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Guarda las imagenes de la pagina publica en la base de datos, redimensionadas
 * y comprimidas. El panel ya las reduce en el navegador; aqui se vuelve a
 * procesar para no confiar en lo que llega.
 */
class LandingImagenes
{
    private const ANCHO_MAXIMO = 1600;
    private const PIXELES_MAXIMOS = 40_000_000; // evita agotar memoria con fotos enormes

    public function guardar(UploadedFile $archivo, ?string $alt = null, string $campo = 'imagen'): LandingImagen
    {
        $info = @getimagesize($archivo->getRealPath());
        if (! $info || $info[0] * $info[1] > self::PIXELES_MAXIMOS) {
            throw ValidationException::withMessages([
                $campo => 'No pudimos leer la imagen o es demasiado grande. Usa un JPG, PNG o WebP de menos de 6000 px por lado.',
            ]);
        }

        $imagen = @imagecreatefromstring(file_get_contents($archivo->getRealPath()));
        if (! $imagen) {
            throw ValidationException::withMessages([$campo => 'No pudimos leer la imagen. Usa un archivo JPG, PNG o WebP.']);
        }

        if (! imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen);
        }

        $imagen = $this->redimensionar($imagen);
        [$datos, $mime] = $this->codificar($imagen);
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        imagedestroy($imagen);

        return LandingImagen::create([
            'uuid' => (string) Str::uuid(),
            'mime' => $mime,
            'ancho' => $ancho,
            'alto' => $alto,
            'tamano' => strlen($datos),
            'alt' => $alt ? Str::limit(trim($alt), 150, '') : null,
            'datos' => base64_encode($datos),
        ]);
    }

    public function eliminar(?int $id): void
    {
        if ($id) {
            LandingImagen::whereKey($id)->delete();
        }
    }

    private function redimensionar(\GdImage $imagen): \GdImage
    {
        $ancho = imagesx($imagen);
        if ($ancho <= self::ANCHO_MAXIMO) {
            return $imagen;
        }

        $alto = (int) round(imagesy($imagen) * self::ANCHO_MAXIMO / $ancho);
        $nueva = imagecreatetruecolor(self::ANCHO_MAXIMO, $alto);
        imagealphablending($nueva, false);
        imagesavealpha($nueva, true);
        imagecopyresampled($nueva, $imagen, 0, 0, 0, 0, self::ANCHO_MAXIMO, $alto, $ancho, imagesy($imagen));
        imagedestroy($imagen);

        return $nueva;
    }

    /** @return array{0: string, 1: string} binario y tipo MIME */
    private function codificar(\GdImage $imagen): array
    {
        ob_start();
        if (function_exists('imagewebp')) {
            imagesavealpha($imagen, true);
            imagewebp($imagen, null, 80);
            $mime = 'image/webp';
        } else {
            // Sin soporte WebP: JPEG sobre fondo blanco (JPEG no admite transparencia)
            $fondo = imagecreatetruecolor(imagesx($imagen), imagesy($imagen));
            imagefill($fondo, 0, 0, imagecolorallocate($fondo, 255, 255, 255));
            imagecopy($fondo, $imagen, 0, 0, 0, 0, imagesx($imagen), imagesy($imagen));
            imagejpeg($fondo, null, 82);
            imagedestroy($fondo);
            $mime = 'image/jpeg';
        }

        return [ob_get_clean(), $mime];
    }
}
