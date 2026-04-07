<?php

namespace App\Services;

class PDFOcrService
{
    /**
     * Rutas posibles del binario pdftotext según el SO.
     */
    private static array $binarios = [
        'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe', // Windows (Git/Poppler)
        '/usr/bin/pdftotext',                                   // Linux/Ubuntu
        '/usr/local/bin/pdftotext',                             // macOS Homebrew
    ];

    /**
     * Extrae el texto completo de un PDF usando pdftotext.
     * Retorna null si el binario no está disponible o falla.
     */
    public function extraerTexto(string $rutaAbsoluta): ?string
    {
        $binario = $this->encontrarBinario();

        if ($binario === null) {
            return null;
        }

        $cmd    = '"' . $binario . '" ' . escapeshellarg($rutaAbsoluta) . ' -';
        $output = shell_exec($cmd);

        return $output ?: null;
    }

    /**
     * Extrae el número de OC desde el texto de un PDF de Mercado Público Chile.
     * Busca el patrón: ORDEN DE COMPRA  N°: 1057900-8-SE25
     * Retorna "N°1057900-8-SE25" o null si no encuentra.
     */
    public function extraerNumeroOC(string $rutaAbsoluta): ?string
    {
        $texto = $this->extraerTexto($rutaAbsoluta);

        if ($texto === null) {
            return null;
        }

        // Buscar: ORDEN DE COMPRA ... N°: 1057900-8-SE25
        // pdftotext puede poner texto entre "ORDEN DE COMPRA" y "N°", o en líneas separadas.
        // Estrategia: buscar directamente "N°:" (con cualquier variante del símbolo °)
        // seguido del patrón numérico del Mercado Público chileno.
        // El ° puede aparecer como: °, º, \xb0, &deg; o caracteres similares.

        // pdftotext extrae ° como byte \xb0 (latin-1).
        // Reemplazar todos los bytes \xb0 y \xba por el carácter ASCII "~" para simplificar el regex.
        $textNorm = str_replace(["\xb0", "\xba", "°", "º"], '~', $texto);

        // Buscar: ORDEN DE COMPRA ... N~: 1057900-8-SE25
        if (preg_match(
            '/ORDEN\s+DE\s+COMPRA[\s\S]{0,300}N\s*~\s*:?\s*(\d{5,7}-\d+-[A-Z0-9]+)/i',
            $textNorm,
            $match
        )) {
            return 'N°' . strtoupper(trim($match[1]));
        }

        // Fallback sin requerir "ORDEN DE COMPRA"
        if (preg_match(
            '/\bN\s*~\s*:?\s*(\d{5,7}-\d+-[A-Z0-9]+)/i',
            $textNorm,
            $match
        )) {
            return 'N°' . strtoupper(trim($match[1]));
        }

        return null;
    }

    /**
     * Devuelve un nombre descriptivo asociado al código OC si es conocido.
     * Agregar aquí los proyectos recurrentes del hospital.
     */
    public function nombreProyecto(string $codigoOc): ?string
    {
        $codigo = strtoupper(str_replace('N°', '', $codigoOc));

        return match ($codigo) {
            '1057900-8-SE25' => 'Compra de Sillones de Acompañamiento',
            '1057900-3-LE25' => 'Adquisición de Activos Fijos TIC',
            default          => null,
        };
    }

    private function encontrarBinario(): ?string
    {
        foreach (self::$binarios as $ruta) {
            if (file_exists($ruta)) {
                return $ruta;
            }
        }

        // Último recurso: buscar en PATH
        $output = shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where pdftotext 2>nul' : 'which pdftotext 2>/dev/null');
        $linea  = trim(explode("\n", trim($output ?? ''))[0]);

        return $linea !== '' ? $linea : null;
    }
}
