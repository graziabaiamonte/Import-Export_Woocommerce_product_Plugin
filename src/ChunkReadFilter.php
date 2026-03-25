<?php

declare(strict_types=1);

namespace WooExcelImporter;

// "use" permette di usare il nome corto "IReadFilter" invece del percorso completo.
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class ChunkReadFilter implements IReadFilter
{
    private int $startRow;
    private int $endRow;

    /**
     * @param int $startRow Riga di inizio del chunk (1-based, la riga 1 è l'header)
     * @param int $chunkSize Numero di righe da leggere per chunk
     */

    // Riceve la riga di partenza e quante righe leggere in questo chunk.
    public function __construct(int $startRow, int $chunkSize)
    {
        // Salva nella proprietà $startRow il valore ricevuto come parametro.
        $this->startRow = $startRow;

        // Il "-1" serve perché sia la riga iniziale che quella finale sono incluse nel range.
        $this->endRow = $startRow + $chunkSize - 1;
    }

    /**
     * Determina se una cella deve essere letta o saltata.
     *
     * @param string $columnAddress Indirizzo della colonna (es. 'A', 'B', 'C')
     * @param int $row Numero di riga (1-based)
     * @param string $worksheetName Nome del foglio di lavoro
     * @return bool True se la cella deve essere letta, false se deve essere saltata
     */
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        // La riga 1 è sempre all'header
        if ($row === 1) {
            
            // true: la cella viene caricata in memoria.
            return true;
        }

        // Per tutte le altre righe, restituisce true solo se la riga rientra
        // nell'intervallo [startRow, endRow] del chunk corrente.
        return ($row >= $this->startRow && $row <= $this->endRow);
    }

    // Restituisce il valore della proprietà privata $startRow.
    // Serve alle classi esterne per sapere da quale riga parte questo chunk,
    // senza poter modificare il valore (accesso in sola lettura).
    public function getStartRow(): int
    {
        return $this->startRow;
    }

    public function getEndRow(): int
    {
        return $this->endRow;
    }
}
