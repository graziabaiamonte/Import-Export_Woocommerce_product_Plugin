<?php

declare(strict_types=1);

namespace WooExcelImporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Classe che converte il documento in memoria nel formato .xlsx.
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;

use WC_Product_Simple;

final class ExcelWriter
{
  
    private TaxonomyRegistrar $taxonomyRegistrar;
    private TaxonomyService $taxonomyService;

    private const REQUIRED_HEADERS = [
        'SKU',
        'TITLE',
        'DESCRIPTION',
        'PRICE',
    ];

    public function __construct(TaxonomyRegistrar $taxonomyRegistrar, TaxonomyService $taxonomyService)
    {
        $this->taxonomyRegistrar = $taxonomyRegistrar;

        $this->taxonomyService = $taxonomyService;
    }

    // Crea e restituisce un oggetto Spreadsheet popolato con i dati dei prodotti.
    public function createExcel(array $products): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->writeHeaders($sheet);

        // Scrive una riga per ogni prodotto a partire dalla riga 2, dopo le intestazioni.
        $this->writeProducts($sheet, $products);

        // Restituisce il documento Excel popolato
        return $spreadsheet;
    }

    private function writeHeaders($sheet): void
    {
        // Inizializza il contatore di colonna a 1 (la prima colonna del foglio, corrispondente ad "A").
        $column = 1;

        $headers = array_merge(self::REQUIRED_HEADERS, $this->taxonomyRegistrar->getAllColumnNames());

        // Itera su ogni intestazione per scriverla nella cella corrispondente della prima riga.
        foreach ($headers as $header) {
           
            // Calcola la coordinata della cella (es. "A1", "B1") combinando la lettera di colonna con il numero di riga 1.
            $cellCoord = $this->getColumnLetter($column) . '1';

            // "setCellValue" è il metodo di PhpSpreadsheet per inserire un valore in una cella tramite coordinata.
            $sheet->setCellValue($cellCoord, $header);

            $column++;
        }
    }

    // Scrive nel foglio una riga Excel per ciascun prodotto dell'array ricevuto.
    private function writeProducts($sheet, array $products): void
    {
        $row = 2;

        // Itera su ogni prodotto dell'array passato come argomento.
        foreach ($products as $product) {
            if (!$product instanceof WC_Product_Simple) {
                continue;
            }

            // Scrive tutte le celle della riga corrente per questo prodotto
            $this->writeProduct($sheet, $row, $product);

            $row++;
        }
    }

    // Scrive tutte le celle di un singolo prodotto su una riga del foglio Excel.
    private function writeProduct($sheet, int $row, WC_Product_Simple $product): void
    {
        $column = 1;

        // Calcola la coordinata per la colonna SKU e incrementa subito "$column"
        $cellCoord = $this->getColumnLetter($column++) . $row;

        $sheet->setCellValue($cellCoord, $product->get_sku());

        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_name());


        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_description());


        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_regular_price());

        // Itera sulle colonne dinamiche delle tassonomie registrate
        foreach ($this->taxonomyRegistrar->getAllColumnNames() as $taxonomyColumnName) {
            $taxonomySlug = $this->taxonomyRegistrar->getTaxonomySlug($taxonomyColumnName);

            // Se il nome colonna non corrisponde a nessuna tassonomia registrata, scrive una cella vuota e passa avanti.
            if ($taxonomySlug === null) {
                
                // Calcola la coordinata della cella vuota e incrementa il contatore di colonna.
                $cellCoord = $this->getColumnLetter($column++) . $row;

                $sheet->setCellValue($cellCoord, '');
                continue;
            }

            // Recupera il nome del termine di tassonomia associato a questo prodotto per la tassonomia corrente.
            $termName = $this->taxonomyService->getProductTermName($product->get_id(), $taxonomySlug);

            $cellCoord = $this->getColumnLetter($column++) . $row;
            $sheet->setCellValue($cellCoord, $termName);
        }
    }

    // Salva il documento Excel su disco nel percorso file specificato.
    public function saveToFile(Spreadsheet $spreadsheet, string $filePath): void
    {
        try {
            
            // Crea un'istanza del writer Xlsx passando il documento da serializzare.
            $writer = new Xlsx($spreadsheet);

            // Scrive fisicamente il file .xlsx nel percorso specificato dal parametro "$filePath".
            $writer->save($filePath);

        } catch (WriterException $e) {
            throw new \RuntimeException('Failed to save Excel file: ' . $e->getMessage());
        }
    }

    // Invia il file Excel direttamente al browser dell'utente come download.
    public function outputToDownload(Spreadsheet $spreadsheet, string $filename): void
    {
        $safeFilename = sanitize_file_name($filename);

        // Controlla che gli header HTTP non siano ancora stati inviati al browser prima di inviarli.
        if (!headers_sent()) {
            
            // Dichiara il tipo MIME del file come Excel .xlsx, così il browser sa come gestirlo.
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            // "Content-Disposition: attachment" forza il download; "filename" imposta il nome suggerito per il salvataggio.
            header('Content-Disposition: attachment;filename="' . $safeFilename . '"');

            // Imposta la durata massima della cache a 0 secondi
            header('Cache-Control: max-age=0');

            // Sovrascrive il precedente Cache-Control con max-age=1 per compatibilità con alcuni browser/proxy.
            // Alcuni client ignorano max-age=0; max-age=1 ha lo stesso effetto pratico ma con compatibilità più ampia.
            header('Cache-Control: max-age=1');

            // Imposta una data di scadenza nel passato per forzare il browser a non cachare la risposta.
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

            // Imposta l'header Last-Modified con la data e ora attuali in formato GMT.
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

            // Forza il browser e i proxy a rivalidare il contenuto prima di usare una versione cachata.
            header('Cache-Control: cache, must-revalidate');

            // Imposta il pragma per la retrocompatibilità con HTTP/1.0 e proxy che non capiscono Cache-Control.
            // "Pragma: public" era il meccanismo di caching in HTTP/1.0; mantenuto per compatibilità con client molto vecchi.
            header('Pragma: public');
        }

        try {
            $writer = new Xlsx($spreadsheet);

            // Invia il contenuto del file .xlsx direttamente allo stream di output PHP (e quindi al browser).
            // "php://output" è uno stream speciale di PHP che scrive direttamente nel buffer di output della risposta HTTP.
            $writer->save('php://output');

        } catch (WriterException $e) {
            throw new \RuntimeException('Failed to output Excel file: ' . $e->getMessage());
        }
        exit;
    }

    public function getHeaders(): array
    {
        return array_merge(self::REQUIRED_HEADERS, $this->taxonomyRegistrar->getAllColumnNames());
    }

    // Converte un numero intero di colonna nella lettera (o coppia di lettere) corrispondente in Excel.
    // Es.: 1 → "A", 26 → "Z", 27 → "AA", 28 → "AB". Necessario perché PhpSpreadsheet accetta coordinate come "A1".
    private function getColumnLetter(int $columnIndex): string
    {
        $letter = '';

        // Il ciclo termina quando "$columnIndex" raggiunge 0, cioè quando tutte le "cifre" in base 26 sono state elaborate.
        while ($columnIndex > 0) {
            
            // Riduce l'indice di 1 per passare da base-1 (Excel) a base-0 prima del calcolo del modulo.
            $columnIndex--;

            // Calcola il carattere corrispondente all'ultima "cifra" in base 26 e lo prepone alla stringa corrente.
            // "chr(65 + modulo)" converte il resto (0–25) nel carattere ASCII corrispondente (A=65, B=66, … Z=90).
            // La concatenazione inversa (nuovo carattere + stringa esistente) costruisce il risultato da destra a sinistra.
            $letter = chr(65 + ($columnIndex % 26)) . $letter;

            // Divide il numero per 26 per passare alla "cifra" più significativa, come in una conversione di base numerica.
            // "(int)" tronca il risultato della divisione all'intero inferiore, ignorando il decimale.
            $columnIndex = (int) ($columnIndex / 26);
        }
        return $letter;
    }
}
