<?php

declare(strict_types=1);

namespace WooExcelImporter;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * ExcelReader - Legge e valida file Excel per l'importazione prodotti.
 * 
 * GESTIONE MEMORIA OTTIMIZZATA:
 * - File < 5MB: lettura normale (veloce ma usa più RAM)
 * - File > 5MB: lettura a chunk (più lenta ma memoria costante)
 * 
 * La lettura a chunk usa un IReadFilter per leggere 100 righe alla volta,
 * evitando "Out of Memory" su file con migliaia di righe.
 * 
 * Ogni chunk viene processato e poi rimosso dalla memoria prima di leggere il successivo.
 */
final class ExcelReader
{
    private const REQUIRED_HEADERS = [
        'SKU',
        'TITLE',
        'DESCRIPTION',
        'PRICE',
    ];

    /**
     * Numero di righe da leggere per volta quando si usa la lettura a chunk.
     * Per file molto grandi, ridurre questo valore se il server ha poca memoria.
     */
    private const CHUNK_SIZE = 100;

    /**
     * Soglia in byte oltre la quale attivare la lettura a chunk.
     * Default: 5MB (per file più grandi viene usata la lettura ottimizzata)
     */
    private const CHUNK_THRESHOLD = 5 * 1024 * 1024;

    public function readFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File does not exist: ' . $filePath);
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException('File is not readable. Please check file permissions.');
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            throw new \RuntimeException('File is empty or cannot be read.');
        }

        // Ottimizzazioni di memoria per PhpSpreadsheet
        $this->configureMemoryOptimizations();

        // Determina se usare lettura a chunk in base alla dimensione del file
        $useChunking = $fileSize > self::CHUNK_THRESHOLD;

        if ($useChunking) {
            return $this->readFileInChunks($filePath);
        }

        return $this->readFileNormally($filePath);
    }

    /**
     * Configura PhpSpreadsheet per un uso ottimizzato della memoria.
     */
    private function configureMemoryOptimizations(): void
    {
        // Disabilita la cache delle celle in memoria quando possibile
        // Questo riduce drasticamente l'uso di RAM per file grandi
        Settings::setCache(new \PhpOffice\PhpSpreadsheet\Collection\Memory\SimpleCache3());
        
        // Limita il numero di celle mantenute in cache
        if (method_exists(Settings::class, 'setCacheSize')) {
            Settings::setCacheSize(1000);
        }
    }

    /**
     * Legge il file Excel normalmente (per file piccoli/medi).
     */
    private function readFileNormally(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (ReaderException $e) {
            throw new \RuntimeException('Failed to read Excel file. The file may be corrupted or in an unsupported format. Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Unexpected error reading Excel file: ' . $e->getMessage());
        }

        try {
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to read worksheet data. The file may be corrupted. Error: ' . $e->getMessage());
        } finally {
            // Libera memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        if (empty($data)) {
            throw new \RuntimeException('Excel file contains no data. Please ensure the file has at least a header row.');
        }

        if (count($data) === 1) {
            throw new \RuntimeException('Excel file contains only headers but no data rows.');
        }

        $headerData = $this->parseHeaders($data[0]);
        $headers = $headerData['headers'];
        $headerIndices = $headerData['indices'];
        
        $this->validateHeaders($headers);

        $rows = [];
        $rowCount = count($data);

        for ($i = 1; $i < $rowCount; $i++) {
            $rows[] = $this->parseRow($data[$i], $headers, $headerIndices);
        }

        return $rows;
    }

    /**
     * Legge il file Excel a "pezzi" (chunk reading) per ottimizzare la memoria.
     * Ideale per file con migliaia di righe che causerebbero "Out of Memory".
     */
    private function readFileInChunks(string $filePath): array
    {
        $allRows = [];
        $headers = null;
        $headerIndices = null;

        try {
            // Primo passaggio: leggi solo l'header per validarlo
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            
            // Leggi solo la prima riga per ottenere gli header
            $headerFilter = new ChunkReadFilter(1, 1);
            $reader->setReadFilter($headerFilter);
            
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $headerRow = $worksheet->toArray()[0] ?? [];
            
            $headerData = $this->parseHeaders($headerRow);
            $headers = $headerData['headers'];
            $headerIndices = $headerData['indices'];
            
            $this->validateHeaders($headers);
            
            // Ottieni il numero totale di righe
            $highestRow = $worksheet->getHighestRow();
            
            // Libera memoria dopo aver letto l'header
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $worksheet);
            
            // Secondo passaggio: leggi i dati a chunk
            // Inizia dalla riga 2 (la riga 1 è l'header)
            $startRow = 2;
            
            while ($startRow <= $highestRow) {
                // Crea un nuovo reader per ogni chunk
                $chunkReader = IOFactory::createReaderForFile($filePath);
                $chunkReader->setReadDataOnly(true);
                
                // Configura il filtro per leggere questo chunk specifico
                $chunkFilter = new ChunkReadFilter($startRow, self::CHUNK_SIZE);
                $chunkReader->setReadFilter($chunkFilter);
                
                $chunkSpreadsheet = $chunkReader->load($filePath);
                $chunkWorksheet = $chunkSpreadsheet->getActiveSheet();
                $chunkData = $chunkWorksheet->toArray();
                
                // Processa le righe del chunk (skippa la prima riga se è l'header)
                foreach ($chunkData as $rowIndex => $rowData) {
                    // Skippa la riga di header che viene sempre inclusa
                    if ($rowIndex === 0) {
                        continue;
                    }
                    
                    $allRows[] = $this->parseRow($rowData, $headers, $headerIndices);
                }
                
                // Libera memoria dopo ogni chunk
                $chunkSpreadsheet->disconnectWorksheets();
                unset($chunkSpreadsheet, $chunkWorksheet, $chunkData);
                
                // Forza garbage collection per liberare memoria
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                
                // Passa al prossimo chunk
                $startRow += self::CHUNK_SIZE;
            }
            
        } catch (ReaderException $e) {
            throw new \RuntimeException('Failed to read Excel file. The file may be corrupted or in an unsupported format. Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Unexpected error reading Excel file in chunks: ' . $e->getMessage());
        }

        if (empty($allRows)) {
            throw new \RuntimeException('Excel file contains only headers but no data rows.');
        }

        return $allRows;
    }

    private function parseHeaders(array $headerRow): array
    {
        $headers = [];
        $indices = [];
        
        foreach ($headerRow as $index => $header) {
            $cleaned = trim((string) $header);
            // Remove semicolon separator
            $cleaned = str_replace(';', '', $cleaned);
            $cleaned = trim($cleaned);
            
            // Skip completely empty headers (empty cells at the end)
            if ($cleaned !== '') {
                $headers[] = $cleaned;
                $indices[] = $index; // Store original column index
            }
        }
        
        return [
            'headers' => $headers,
            'indices' => $indices,
        ];
    }

    private function validateHeaders(array $headers): void
    {
        // Check for completely empty header row
        $nonEmptyHeaders = array_filter($headers, function($h) {
            return !empty(trim((string) $h));
        });
        
        if (empty($nonEmptyHeaders)) {
            throw new \RuntimeException(
                'Excel file header row is empty. The first row must contain column names.'
            );
        }

        $cleanHeaders = array_map('trim', $headers);
        $cleanRequired = array_map('trim', self::REQUIRED_HEADERS);

        // Check for duplicate headers
        $duplicates = array_diff_assoc($cleanHeaders, array_unique($cleanHeaders));
        if (!empty($duplicates)) {
            throw new \RuntimeException(
                'Excel file contains duplicate column headers: ' . implode(', ', array_unique($duplicates)) . '. Each column name must be unique.'
            );
        }

        // Check missing required columns
        $missing = array_diff($cleanRequired, $cleanHeaders);
        if (!empty($missing)) {
            throw new \RuntimeException(
                'Invalid Excel headers. Missing required columns: ' . implode(', ', $missing) . '. Please use the Export function to generate a correctly formatted template.'
            );
        }

        // Get all registered taxonomy column names
        $taxonomyRegistrar = new TaxonomyRegistrar();
        $expectedTaxonomies = $taxonomyRegistrar->getAllColumnNames();
        $expectedHeaders = array_merge(self::REQUIRED_HEADERS, $expectedTaxonomies);

        // Check for extra columns (not allowed)
        $extraColumns = array_diff($cleanHeaders, $expectedHeaders);
        if (!empty($extraColumns)) {
            throw new \RuntimeException(
                'Invalid Excel headers. Unexpected columns found: ' . implode(', ', $extraColumns) . '. Only the following columns are allowed: ' . implode(', ', $expectedHeaders) . '. Please use the Export function to generate a correct template.'
            );
        }

        // Check for missing taxonomy columns
        $missingTaxonomies = array_diff($expectedTaxonomies, $cleanHeaders);
        if (!empty($missingTaxonomies)) {
            throw new \RuntimeException(
                'Invalid Excel headers. Missing taxonomy columns: ' . implode(', ', $missingTaxonomies) . '. All ' . count($expectedTaxonomies) . ' registered taxonomy columns must be present. Please use the Export function to generate a correct template.'
            );
        }
    }

    private function parseRow(array $rowData, array $headers, array $headerIndices): array
    {
        $row = [];
        
        foreach ($headers as $idx => $header) {
            $originalIndex = $headerIndices[$idx];
            $value = isset($rowData[$originalIndex]) ? (string) $rowData[$originalIndex] : '';
            $row[$header] = trim($value);
        }

        return $row;
    }

    public function validateFileUpload(array $file): ?string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return 'Invalid file upload. Please select a single file.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->getUploadErrorMessage($file['error']);
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'Invalid file upload. The file was not uploaded correctly.';
        }

        if (!isset($file['name']) || empty($file['name'])) {
            return 'Invalid file upload. File name is missing.';
        }

        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            return 'Invalid file type "' . esc_html($fileExtension) . '". Allowed formats: ' . implode(', ', $allowedExtensions) . '. Please upload an Excel file.';
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if (!isset($file['size']) || $file['size'] > $maxSize) {
            $sizeMB = isset($file['size']) ? round($file['size'] / 1024 / 1024, 2) : 0;
            return 'File too large (' . $sizeMB . 'MB). Maximum allowed size: 10MB. Please reduce the file size or split into multiple files.';
        }

        if ($file['size'] === 0) {
            return 'The uploaded file is empty (0 bytes). Please check the file and try again.';
        }

        return null;
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds the maximum upload size set by the server. Please contact your administrator or use a smaller file.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum allowed size (10MB). Please reduce the file size.',
            UPLOAD_ERR_PARTIAL => 'File upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded. Please select a file and try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: temporary folder is missing. Please contact your administrator.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Please check server permissions or contact your administrator.',
            UPLOAD_ERR_EXTENSION => 'File upload was blocked by a server extension. Please contact your administrator.',
            default => 'Unknown file upload error (code: ' . $errorCode . '). Please try again or contact support.',
        };
    }

    public function getRequiredHeaders(): array
    {
        return self::REQUIRED_HEADERS;
    }
}
