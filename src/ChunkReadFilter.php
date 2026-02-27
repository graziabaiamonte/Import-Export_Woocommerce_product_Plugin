<?php

declare(strict_types=1);

namespace WooExcelImporter;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * ChunkReadFilter - Legge il file Excel a "pezzi" per ottimizzare l'uso della memoria.
 * Utile per file con migliaia di righe che altrimenti causerebbero "Out of Memory".
 */
final class ChunkReadFilter implements IReadFilter
{
    private int $startRow;
    private int $endRow;

    /**
     * @param int $startRow Riga di inizio del chunk (1-based, la riga 1 è l'header)
     * @param int $chunkSize Numero di righe da leggere per chunk
     */
    public function __construct(int $startRow, int $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    /**
     * Determina se una cella deve essere letta o saltata.
     * Viene chiamato da PhpSpreadsheet per ogni cella del file.
     * 
     * @param string $columnAddress Indirizzo della colonna (es. 'A', 'B', 'C')
     * @param int $row Numero di riga (1-based)
     * @param string $worksheetName Nome del foglio di lavoro
     * @return bool True se la cella deve essere letta, false se deve essere saltata
     */
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        // Legge sempre la riga di intestazione (riga 1)
        if ($row === 1) {
            return true;
        }

        // Legge solo le righe nel range del chunk corrente
        return ($row >= $this->startRow && $row <= $this->endRow);
    }

    public function getStartRow(): int
    {
        return $this->startRow;
    }

    public function getEndRow(): int
    {
        return $this->endRow;
    }
}
