<?php

declare(strict_types=1);

namespace WooExcelImporter;

use PhpOffice\PhpSpreadsheet\IOFactory;
// Classe principale di PhpSpreadsheet per caricare file Excel.

use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;

use PhpOffice\PhpSpreadsheet\Settings;
// Usata per configurare il comportamento globale della libreria (es. cache, memoria).

use PhpOffice\PhpSpreadsheet\Cell\Cell;
// Importa la classe Cell di PhpSpreadsheet che rappresenta una singola cella.
// (In questo file non viene usata direttamente ma è importata per compatibilità
// con la libreria).

/**
 * Legge e valida file Excel per l'importazione prodotti.
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

    private const CHUNK_SIZE = 100;
    // Costante privata: indica quante righe leggere per volta (chunk)

    private const CHUNK_THRESHOLD = 5 * 1024 * 1024;

    public function readFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('File does not exist: ' . $filePath);
        }

        if (!is_readable($filePath)) {
            // "is_readable()" verifica che PHP abbia i permessi di lettura sul file.
            
            throw new \RuntimeException('File is not readable. Please check file permissions.');
        }

        $fileSize = filesize($filePath);

        if ($fileSize === false || $fileSize === 0) {
            // "$fileSize === false" → filesize() ha fallito.
            // "$fileSize === 0" → il file esiste ma è vuoto (0 byte).
           
            throw new \RuntimeException('File is empty or cannot be read.');
        }

        $this->configureMemoryOptimizations();

        $useChunking = $fileSize > self::CHUNK_THRESHOLD;
        // restituisce true o false


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
        // Disabilita la cache delle celle in memoria
        // Questo riduce drasticamente l'uso di RAM per file grandi
        Settings::setCache(new \PhpOffice\PhpSpreadsheet\Collection\Memory\SimpleCache3());

        // Limita il numero di celle mantenute in cache
        if (method_exists(Settings::class, 'setCacheSize')) {
            // "method_exists()" verifica se un metodo esiste prima di chiamarlo.
            // "Settings::class" restituisce il nome completo della classe come stringa.
            // Questo controllo evita errori se la versione di PhpSpreadsheet non ha "setCacheSize".
            Settings::setCacheSize(1000);
            // Limita la cache a massimo 1000 celle tenute in memoria contemporaneamente.
        }
    }

    private function readFileNormally(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            // Rileva il formato del file (xlsx, xls, csv...)
            // L'oggetto viene salvato in $spreadsheet.

        } catch (ReaderException $e) {
            throw new \RuntimeException('Failed to read Excel file. The file may be corrupted or in an unsupported format. Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Unexpected error reading Excel file: ' . $e->getMessage());
        }

        try {
            $worksheet = $spreadsheet->getActiveSheet();

            $data = $worksheet->toArray();
            // "toArray()" converte l'intero foglio in un array

        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to read worksheet data. The file may be corrupted. Error: ' . $e->getMessage());
        } finally {
            $spreadsheet->disconnectWorksheets();
            // "disconnectWorksheets()" rimuove i riferimenti interni tra lo Spreadsheet
            // e i suoi fogli, permettendo al garbage collector di liberare memoria.

            unset($spreadsheet);
            // distrugge la variabile e libera la memoria occupata dall'oggetto.
        }

        if (empty($data)) {
            throw new \RuntimeException('Excel file contains no data. Please ensure the file has at least a header row.');
        }

        if (count($data) === 1) {
            throw new \RuntimeException('Excel file contains only headers but no data rows.');
        }

        $headerData = $this->parseHeaders($data[0]);

        $headers = $headerData['headers'];
        // Estrae dall'array $headerData la chiave 'headers':
        // lista dei nomi colonna puliti (senza celle vuote).

        $headerIndices = $headerData['indices'];
        // Estrae i corrispondenti indici ORIGINALI delle colonne nel file Excel.
        // Serve per mappare correttamente i dati anche se ci sono celle vuote.

        $this->validateHeaders($headers);

        $rows = [];

        $rowCount = count($data);
        // Conta il numero totale di righe nel file (inclusa quella degli header).

        for ($i = 1; $i < $rowCount; $i++) {
            $rows[] = $this->parseRow($data[$i], $headers, $headerIndices);
            // "parseRow()" converte una riga in array associativo chiave→valore.
        }

        return $rows;
    }

    private function readFileInChunks(string $filePath): array
    {
        $allRows = [];

        $headers = null;
        // Variabile per i nomi colonna, inizialmente null (non ancora letti).

        $headerIndices = null;
        // Variabile per gli indici originali delle colonne

        try {
            // Primo passaggio: leggi solo l'header per validarlo
            $reader = IOFactory::createReaderForFile($filePath);
            // "createReaderForFile()" crea il reader appropriato per il tipo di file
            // (xlsx → Xlsx reader, xls → Xls reader, csv → Csv reader) ma NON carica.

            $reader->setReadDataOnly(true);
            // Imposta il reader in modalità "solo dati":
            // ignora formattazione, stili, colori → usa molta meno memoria.

            // Leggi solo la prima riga per ottenere gli header
            $headerFilter = new ChunkReadFilter(1, 1);

            $reader->setReadFilter($headerFilter);
            // Applica il filtro al reader: quando caricherà il file,
            // leggerà solo le righe indicate dal filtro.

            $spreadsheet = $reader->load($filePath);
            // Carica il file applicando il filtro → in memoria ci sarà solo la riga 1.

            $worksheet = $spreadsheet->getActiveSheet();

            $headerRow = $worksheet->toArray()[0] ?? [];
            // "toArray()" converte il foglio in array. "[0]" prende la prima (e unica) riga.

            $headerData = $this->parseHeaders($headerRow);
            // Elabora la riga header per estrarre nomi colonna e indici originali.

            $headers = $headerData['headers'];
            // Salva i nomi delle colonne nella variabile $headers.

            $headerIndices = $headerData['indices'];
            // Salva gli indici originali delle colonne 

            $this->validateHeaders($headers);

            // Ottieni il numero totale di righe
            $highestRow = $worksheet->getHighestRow();
            // "getHighestRow()" restituisce il numero dell'ultima riga con dati

            // Libera memoria dopo aver letto l'header
            $spreadsheet->disconnectWorksheets();

            unset($spreadsheet, $worksheet);
            // Distrugge entrambe le variabili in una sola istruzione "unset"

            // Secondo passaggio: leggi i dati a chunk
            // Inizia dalla riga 2 
            $startRow = 2;

            while ($startRow <= $highestRow) {
                // Continua a leggere chunk finché non supera l'ultima riga del file.

                // Crea un nuovo reader per ogni chunk
                $chunkReader = IOFactory::createReaderForFile($filePath);
                // Crea un reader fresco per ogni chunk. 

                $chunkReader->setReadDataOnly(true);

                $chunkFilter = new ChunkReadFilter($startRow, self::CHUNK_SIZE);
                // Crea un filtro che leggerà a partire da $startRow per CHUNK_SIZE righe.

                $chunkReader->setReadFilter($chunkFilter);

                $chunkSpreadsheet = $chunkReader->load($filePath);
                // Carica il file ma solo con le righe indicate dal filtro.

                $chunkWorksheet = $chunkSpreadsheet->getActiveSheet();

                $chunkData = $chunkWorksheet->toArray();

                // Processa le righe del chunk 
                foreach ($chunkData as $rowIndex => $rowData) {
                    if ($rowIndex === 0) {
                        continue;
                    }

                    $allRows[] = $this->parseRow($rowData, $headers, $headerIndices);
                    // Aggiunge la riga elaborata all'array $allRows.
                }

                // Libera memoria dopo ogni chunk
                $chunkSpreadsheet->disconnectWorksheets();

                unset($chunkSpreadsheet, $chunkWorksheet, $chunkData);
                // Distrugge le variabili del chunk

                // Forza garbage collection per liberare memoria
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                    // Forza il garbage collector di PHP a eseguire subito,
                    // raccogliendo gli oggetti senza riferimenti e liberando RAM.
                }

                // Passa al prossimo chunk
                $startRow += self::CHUNK_SIZE;
                // Sposta il punto di partenza al chunk successivo (es. 2 → 102 → 202...).
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
    // Prende la riga grezza degli header e restituisce
    // un array con i nomi puliti e i loro indici originali nel file.
    {
        $headers = [];

        $indices = [];

        foreach ($headerRow as $index => $header) {
            // Itera su ogni cella della riga header.
            // $header = valore grezzo della cella (potrebbe essere null, stringa, numero...).

            $cleaned = trim((string) $header);

            if ($cleaned !== '') {
                
                // Se la cella non è completamente vuota, la consideriamo un header valido.
                $headers[] = $cleaned;

                $indices[] = $index;
                // Salva l'indice originale nel file Excel (es. colonna D → indice 3).
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
            // "array_filter()" filtra un array mantenendo solo gli elementi per cui
            // il callback (la funzione anonima) restituisce true.
            // "function($h)" è una funzione anonima (closure) che riceve un elemento dell'array.
            return !empty(trim((string) $h));
            // Restituisce true se l'header è non vuoto.
        });

        if (empty($nonEmptyHeaders)) {
            // Se dopo il filtro l'array è vuoto, tutti gli header erano vuoti.
            throw new \RuntimeException(
                'Excel file header row is empty. The first row must contain column names.'
            );
        }

        $cleanHeaders = array_map('trim', $headers);
        // "array_map()" applica una funzione a ogni elemento dell'array e restituisce
        // un nuovo array con i risultati
        // Risultato: array di header tutti trimmati.

        $cleanRequired = array_map('trim', self::REQUIRED_HEADERS);
        // Applica trim anche alle costanti degli header obbligatori

        // Check for duplicate headers
        $duplicates = array_diff_assoc($cleanHeaders, array_unique($cleanHeaders));
        // "array_unique()" rimuove i duplicati da un array.
        // "array_diff_assoc()" confronta due array considerando anche le chiavi:
        // restituisce gli elementi presenti nel primo array ma non nel secondo.

        if (!empty($duplicates)) {
            throw new \RuntimeException(
                'Excel file contains duplicate column headers: ' . implode(', ', array_unique($duplicates)) . '. Each column name must be unique.'
                // "implode(', ', array)" concatena gli elementi dell'array con ", " tra loro.
                // "array_unique($duplicates)" evita di mostrare lo stesso duplicato due volte.
            );
        }

        // Check missing required columns
        $missing = array_diff($cleanRequired, $cleanHeaders);

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Invalid Excel headers. Missing required columns: ' . implode(', ', $missing) . '. Please use the Export function to generate a correctly formatted template.'
            );
        }

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
    // Metodo privato che converte una riga grezza (array numerico) in un array associativo.
    {
        $row = [];

        foreach ($headers as $idx => $header) {
            // Itera su ogni nome colonna.
            // $header = nome della colonna (es. 'SKU', 'TITLE'...).

            $originalIndex = $headerIndices[$idx];

            $value = isset($rowData[$originalIndex]) ? (string) $rowData[$originalIndex] : '';

            $row[$header] = trim($value);
        }

        return $row;
        // Restituisce l'array associativo: ['SKU' => 'PROD001', 'TITLE' => 'Maglia', ...]
    }

    public function validateFileUpload(array $file): ?string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            // "is_array($file['error'])" → se 'error' è un array, significa che
            // sono stati caricati più file con lo stesso nome campo (non supportato).
            return 'Invalid file upload. Please select a single file.';
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            // Se il codice errore è diverso da 0, c'è stato un errore nel caricamento.
            return $this->getUploadErrorMessage($file['error']);
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            // PHP salva il file caricato in una directory temporanea, il cui percorso è in 'tmp_name'.
            // "is_uploaded_file()" verifica che il file sia stato davvero caricato via HTTP POST
            return 'Invalid file upload. The file was not uploaded correctly.';
        }

        if (!isset($file['name']) || empty($file['name'])) {
            return 'Invalid file upload. File name is missing.';
        }

        $allowedExtensions = ['xls', 'xlsx', 'csv'];

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // "pathinfo()" analizza un percorso/nome file e ne estrae le componenti.
        // "PATHINFO_EXTENSION" è una costante che chiede solo l'estensione (es. 'XLSX').

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            // "in_array($valore, $array, true)" controlla se $valore è presente in $array.
            // Il terzo parametro "true" abilita il confronto stretto (tipo + valore).
            // >> entra nell'if se l'estensione NON è tra quelle consentite.
            
            return 'Invalid file type "' . esc_html($fileExtension) . '". Allowed formats: ' . implode(', ', $allowedExtensions) . '. Please upload an Excel file.';
        }

        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!isset($file['size']) || $file['size'] > $maxSize) {
            // Se la dimensione non è impostata o supera il limite 
            $sizeMB = isset($file['size']) ? round($file['size'] / 1024 / 1024, 2) : 0;
            return 'File too large (' . $sizeMB . 'MB). Maximum allowed size: 10MB. Please reduce the file size or split into multiple files.';
        }

        if ($file['size'] === 0) {
            return 'The uploaded file is empty (0 bytes). Please check the file and try again.';
        }

        return null;
        // Tutto ok: restituisce null per indicare assenza di errori.
    }

    private function getUploadErrorMessage(int $errorCode): string
    // Metodo privato che converte un codice errore PHP upload in un messaggio leggibile.
    {
        return match ($errorCode) {
            // "match" è simile a switch:
            // confronta $errorCode con ogni caso usando ===.
            // Restituisce il valore del caso corrispondente.

            UPLOAD_ERR_INI_SIZE => 'File exceeds the maximum upload size set by the server. Please contact your administrator or use a smaller file.',
            // file supera upload_max_filesize in php.ini.

            UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum allowed size (10MB). Please reduce the file size.',
            // file supera MAX_FILE_SIZE nel form HTML.

            UPLOAD_ERR_PARTIAL => 'File upload was interrupted. Please try again.',
            // file è stato caricato solo parzialmente.

            UPLOAD_ERR_NO_FILE => 'No file was uploaded. Please select a file and try again.',

            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: temporary folder is missing. Please contact your administrator.',
            // cartella temporanea mancante sul server.

            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Please check server permissions or contact your administrator.',
            // impossibile scrivere nella cartella temporanea.

            UPLOAD_ERR_EXTENSION => 'File upload was blocked by a server extension. Please contact your administrator.',
            // un'estensione PHP ha bloccato l'upload.

            default => 'Unknown file upload error (code: ' . $errorCode . '). Please try again or contact support.',
            // gestisce tutti i codici errore non previsti sopra.
        };
    }

    public function getRequiredHeaders(): array
    // Espone all'esterno la lista degli header obbligatori.
    {
        return self::REQUIRED_HEADERS;
    }
}

