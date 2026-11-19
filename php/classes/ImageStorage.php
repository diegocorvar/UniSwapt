<?php
/**
 * Almacenamiento de imagenes subidas por los usuarios (HU-04 foto de perfil,
 * HU-07 imagenes de publicaciones).
 *
 * Modo hibrido, controlado por variables de entorno:
 * - Produccion (SUPABASE_URL y SUPABASE_SERVICE_ROLE_KEY configuradas): sube el
 *   archivo a Supabase Storage con su API REST usando cURL nativo de PHP, dentro
 *   del bucket SUPABASE_STORAGE_BUCKET (por defecto "uniswapt-uploads") en la
 *   carpeta "products/" o "profiles/". Devuelve la URL publica completa, por lo
 *   que el bucket debe ser publico.
 * - Local (sin esas variables): mueve el archivo a assets/uploads/products/ o
 *   assets/uploads/profiles/ y devuelve solo el nombre del archivo.
 *
 * Lo que devuelve store() es lo que se guarda en la base de datos; las vistas lo
 * convierten en el atributo src con resolveUploadUrl().
 */
class ImageStorage
{
    public const TYPE_PRODUCTS = 'products';
    public const TYPE_PROFILES = 'profiles';

    /** true cuando las imagenes se suben a Supabase Storage en lugar del disco local. */
    public static function isRemote(): bool
    {
        return envValue('SUPABASE_URL') !== null && envValue('SUPABASE_SERVICE_ROLE_KEY') !== null;
    }

    /**
     * Guarda un archivo recien subido ($_FILES[...]['tmp_name']).
     *
     * @param string $type     ImageStorage::TYPE_PRODUCTS o ImageStorage::TYPE_PROFILES.
     * @param string $fileName Nombre generado por el servidor (nunca el original del cliente).
     * @return string|null URL publica (Supabase) o nombre del archivo (local); null si fallo.
     */
    public static function store(string $tmpPath, string $type, string $fileName): ?string
    {
        if (!in_array($type, [self::TYPE_PRODUCTS, self::TYPE_PROFILES], true)
            || preg_match('/^[A-Za-z0-9._-]+$/', $fileName) !== 1
            || !is_uploaded_file($tmpPath)
        ) {
            return null;
        }

        return self::isRemote()
            ? self::uploadToSupabase($tmpPath, $type . '/' . $fileName)
            : self::storeLocally($tmpPath, $type, $fileName);
    }

    /**
     * POST {SUPABASE_URL}/storage/v1/object/{bucket}/{ruta} con el contenido
     * binario del archivo. Nunca cae al disco local: en Railway el disco es
     * efimero y las imagenes se perderian en el siguiente despliegue.
     */
    private static function uploadToSupabase(string $tmpPath, string $objectPath): ?string
    {
        $baseUrl = rtrim((string) envValue('SUPABASE_URL'), '/');
        $serviceKey = (string) envValue('SUPABASE_SERVICE_ROLE_KEY');
        $bucket = self::bucket();

        if (!function_exists('curl_init')) {
            error_log('UniSwapt ImageStorage: la extension cURL no esta habilitada; no se subio la imagen.');
            return null;
        }

        $mimeType = self::detectMimeType($tmpPath);
        $contents = file_get_contents($tmpPath);

        if ($mimeType === null || $contents === false) {
            error_log("UniSwapt ImageStorage: no se pudo leer el archivo temporal para {$objectPath}.");
            return null;
        }

        $encodedPath = self::encodePath($bucket . '/' . $objectPath);

        $curl = curl_init($baseUrl . '/storage/v1/object/' . $encodedPath);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $contents, // Cadena, no arreglo: se envia el binario tal cual.
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $serviceKey,
                'apikey: ' . $serviceKey,
                'Content-Type: ' . $mimeType,
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $responseBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        unset($curl); // curl_close() es innecesario desde PHP 8 y esta obsoleto en 8.5.

        if ($responseBody === false) {
            error_log("UniSwapt ImageStorage: fallo la conexion con Supabase Storage ({$curlError}).");
            return null;
        }

        if (!in_array($statusCode, [200, 201], true)) {
            // La respuesta de error de Supabase no incluye la clave; se recorta por si es larga.
            error_log(sprintf(
                'UniSwapt ImageStorage: Supabase respondio HTTP %d al subir "%s": %s',
                $statusCode,
                $objectPath,
                substr((string) $responseBody, 0, 500)
            ));
            return null;
        }

        return $baseUrl . '/storage/v1/object/public/' . $encodedPath;
    }

    private static function storeLocally(string $tmpPath, string $type, string $fileName): ?string
    {
        $directory = $type === self::TYPE_PROFILES ? UPLOAD_DIR_PROFILES : UPLOAD_DIR_PRODUCTS;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return move_uploaded_file($tmpPath, $directory . $fileName) ? $fileName : null;
    }

    private static function bucket(): string
    {
        return (string) envValue('SUPABASE_STORAGE_BUCKET', SUPABASE_STORAGE_DEFAULT_BUCKET);
    }

    /** Content-Type real leido del contenido del archivo (no el que declara el cliente). */
    private static function detectMimeType(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo ? finfo_file($fileInfo, $path) : false;
            return is_string($mimeType) ? $mimeType : null;
        }

        $imageInfo = @getimagesize($path);
        return is_array($imageInfo) && isset($imageInfo['mime']) ? $imageInfo['mime'] : null;
    }

    /** Codifica cada segmento de la ruta del objeto, conservando las barras. */
    private static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
