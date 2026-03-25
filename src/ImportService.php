<?php

// Orchestra l'importazione: Chiede a ExcelReader di leggere il file
// Valida ogni riga. Per ogni riga chiama ProductService per creare o aggiornare il prodotto. Restituisce un ImportReport con il risultato

declare(strict_types=1);

namespace WooExcelImporter;

use WC_Product_Simple;

final class ImportService
{
    private ProductService $productService;
  
    private ExcelReader $excelReader;

    public function __construct(ProductService $productService, ExcelReader $excelReader)
    {
        $this->productService = $productService;
        $this->excelReader = $excelReader;
    }

    public function importFromFile(string $filePath): ImportReport
    {
        $report = new ImportReport();

        // Tenta di leggere il file Excel; se fallisce cattura l'eccezione
        try {
            $rows = $this->excelReader->readFile($filePath);
        } catch (\RuntimeException $e) {
            $report->addError('Failed to read Excel file: ' . $e->getMessage());
            return $report;
        }

        if (empty($rows)) {
            $report->addError('No data rows found in Excel file');
            return $report;
        }

        $this->processRows($rows, $report);
        return $report;
    }

    // Itera su tutte le righe del file applicando validazioni pre-elaborazione 
    private function processRows(array $rows, ImportReport $report): void
    {
        // Inizia a contare da riga 2 
        $rowNumber = 2;
        $processedSkus = [];

        // Itera su ogni riga restituita dall'ExcelReader (ognuna è un array associativo colonna → valore)
        foreach ($rows as $row) {
           
            // array_filter mantiene solo i valori non vuoti 
            $nonEmptyValues = array_filter($row, function($value) {
                // Converte il valore in stringa e verifica che non sia vuoto
                return !empty(trim((string) $value));
            });

            // Se tutti i valori della riga erano vuoti, $nonEmptyValues sarà un array vuoto
            if (empty($nonEmptyValues)) {
                $report->addIgnoredRow($rowNumber, 'Empty row - all cells are blank');
                $rowNumber++;
                continue;
            }

            $sku = $this->extractValue($row, 'SKU');

            if (!$this->productService->validateSku($sku)) {
                $sanitizedSku = $this->sanitizeSku($sku);
                
                if (empty($sanitizedSku)) {
                    $report->addIgnoredRow($rowNumber, 'SKU is empty or contains only invalid characters');
                } else {
                
                    // Ignora la riga segnalando il formato non valido ma mostrando la versione sanificata per riferimento
                    $report->addIgnoredRow($rowNumber, 'Invalid SKU format (allowed: alphanumeric, dots, dashes, underscores, max 100 chars)', $sanitizedSku);
                }
                $rowNumber++;
                continue;
            }

            if (isset($processedSkus[$sku])) {
                $report->addIgnoredRow($rowNumber, 'Duplicate SKU in file (first occurrence at row ' . $processedSkus[$sku] . ')', $sku);
                $rowNumber++;
                continue;
            }

            // Registra lo SKU come già elaborato, salvando il numero di riga corrente per eventuali segnalazioni future
            $processedSkus[$sku] = $rowNumber;

            // Tenta di elaborare la riga e creare/aggiornare il prodotto
            try {
                // Delega l'elaborazione dettagliata al metodo processRow che gestisce creazione/aggiornamento
                $this->processRow($row, $report);
            } catch (\Exception $e) {
                $report->addIgnoredRow($rowNumber, 'Error: ' . $e->getMessage(), $sku);
            }
            $rowNumber++;
        }
    }

    // Elabora una singola riga superata le validazioni di base: estrae tutti i campi, li valida e crea/aggiorna il prodotto
    private function processRow(array $row, ImportReport $report): void {
        $sku = $this->sanitizeSku($this->extractValue($row, 'SKU'));
        $title = $this->extractValue($row, 'TITLE');
        $description = $this->extractValue($row, 'DESCRIPTION');
        $price = $this->sanitizePrice($this->extractValue($row, 'PRICE'));

        // Ri-valida lo SKU dopo la sanificazione
        if (!$this->productService->validateSku($sku)) {
            throw new \RuntimeException('Invalid or empty SKU. SKU must contain only alphanumeric characters, dots, dashes, or underscores (max 100 characters)');
        }

        if (empty(trim($title))) {
            throw new \RuntimeException('Product title cannot be empty');
        }

        if (strlen($title) > 200) {
            throw new \RuntimeException('Product title is too long (max 200 characters)');
        }

        if (!$this->productService->validatePrice($price)) {
            throw new \RuntimeException('Invalid price format. Price must be a positive number (e.g., 10.50 or 10,50)');
        }

        $existingProduct = $this->productService->findProductBySku($sku);

        // Estrae tutte le colonne extra del file come tassonomie personalizzate
        $taxonomies = $this->extractTaxonomies($row, $report);

        // Costruisce l'array con tutti i dati del prodotto pronti per essere passati al ProductService
        $productData = [
            'sku' => $sku,
            'name' => $title,
            'description' => $description,
            'price' => $price,
            // Array associativo slug_tassonomia => valore_termine per tutte le tassonomie personalizzate
            'taxonomies' => $taxonomies,
        ];

        if ($existingProduct instanceof WC_Product_Simple) {
            $this->productService->updateProduct($existingProduct, $productData);
            $report->incrementProductsUpdated();
        } else {
            $this->productService->createProduct($productData);
            $report->incrementProductsCreated();
        }
    }

    // Identifica le colonne extra del file e le tratta come tassonomie WordPress
    private function extractTaxonomies(array $row, ImportReport $report): array
    {
        $taxonomies = [];
        $taxonomyRegistrar = new TaxonomyRegistrar();
        $requiredHeaders = $this->excelReader->getRequiredHeaders();
        
        // Ottiene tutti i nomi delle colonne presenti nella riga corrente
        $rowHeaders = array_keys($row);

        // Itera su ogni nome di colonna trovato nella riga per identificare le colonne extra da trattare come tassonomie
        foreach ($rowHeaders as $columnName) {
            if (in_array($columnName, $requiredHeaders, true)) {
                continue;
            }

            if ($columnName === '') {
                continue;
            }

            // Estrae il valore della cella per questa colonna extra/tassonomia
            $value = $this->extractValue($row, $columnName);

            if ($value === '') {
                continue;
            }

            if (strlen($value) > 200) {
                throw new \RuntimeException(
                    sprintf('Taxonomy term "%s" for column "%s" is too long (max 200 characters)',
                        substr($value, 0, 50) . '...',
                        $columnName)
                );
            }

            // Protezione da caratteri pericolosi: la regex cerca < > " ' che potrebbero causare XSS
            if (preg_match('/[<>"\']/', $value)) {
                throw new \RuntimeException(
                    sprintf('Taxonomy term "%s" for column "%s" contains invalid characters (< > " \' are not allowed)',
                        $value,
                        $columnName)
                );
            }

            // Tenta di ottenere o creare la tassonomia WordPress corrispondente al nome della colonna
            try {
                $slug = $taxonomyRegistrar->ensureTaxonomyForColumn($columnName);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException(
                    sprintf('Invalid taxonomy column "%s": %s', $columnName, $e->getMessage())
                );
            }

            // Aggiunge al risultato la coppia slug della tassonomia => valore del termine per questa riga
            $taxonomies[$slug] = $value;

            // Controlla tramite la funzione WordPress nativa se il termine esiste già nella tassonomia
            if (!term_exists($value, $slug)) {
                $report->incrementTermsCreated($value, $slug);
            }
        }
        return $taxonomies;
    }

    // Estrae un valore da una riga 
    private function extractValue(array $row, string $key): string
    {
        return isset($row[$key]) ? trim((string) $row[$key]) : '';
    }

    private function sanitizeSku(string $sku): string
    {
        $sku = trim($sku);

        // regex per eliminare tutti i caratteri che non sono lettere, cifre, punto, trattino o underscore
        $sku = preg_replace('/[^a-zA-Z0-9._-]/', '', $sku);

        return $sku;
    }

 
    private function sanitizePrice(string $price): string
    {
        $price = trim($price);

        // Se il prezzo è vuoto oppure contiene solo caratteri non numerici, lo scarta restituendo stringa vuota
        if ($price === '' || preg_match('/^[^0-9.,]+$/', $price)) {
            return '';
        }

        // sostituisce la virgola con il punto
        $price = str_replace(',', '.', $price);

        // Rimuove tutti i caratteri non numerici tranne il punto decimale
        $price = preg_replace('/[^0-9.]/', '', $price);

        // Gestisce il caso anomalo di più punti decimali (es. "1.234.56" → divide in parti e ricombina)
        $parts = explode('.', $price);
       
        // Se c'è più di un punto decimale nel valore
        if (count($parts) > 2) {
            // Combina la parte intera con tutte le parti decimali concatenate mantenendo un solo punto separatore
            $price = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }

        return $price;
    }

    public function validateUploadedFile(array $file): ?string
    {
        return $this->excelReader->validateFileUpload($file);
    }
}
