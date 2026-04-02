<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SicdDetallesImport implements ToCollection, WithStartRow
{
    /**
     * Empieza desde la fila 2 (la fila 1 son cabeceras).
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): Collection
    {
        return $rows;
    }
}
