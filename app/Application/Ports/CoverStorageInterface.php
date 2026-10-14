<?php

namespace App\Application\Ports;

interface CoverStorageInterface
{
    /** Sube los bytes y devuelve una clave opaca */
    public function store(string $binaryData, string $mimeType): string;
    /** Obtiene los bytes de la clave opaca o null */
    public function get(string $coverKey): ?array;
    /** Elimina el archivo físico de la clave opaca */
    public function delete(string $coverKey): void;
}
