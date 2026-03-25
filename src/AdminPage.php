<?php
// La pagina admin sotto il menu WooCommerce. Gestisce:
//
// Mostrare la pagina HTML (views/admin-page.php)
// Ricevere il file caricato dall'utente e passarlo a ImportService
// Chiamare ExportService quando si clicca "Esporta"
// Mostrare i risultati/errori dopo l'operazione

declare(strict_types=1);

namespace WooExcelImporter;

final class AdminPage
{
    use SecureFormHandler;

    // Usata da WordPress per costruire l'URL della pagina admin del plugin.
    private const MENU_SLUG = 'woo-excel-importer';

    private const CAPABILITY = 'manage_woocommerce';

    // Costante usata per generare e verificare il nonce del form di importazione.
    // la stringa è il nome identificativo dell'azione di importazione (il nome dell'etichetta del nonce).
    private const NONCE_ACTION_IMPORT = 'woo_excel_import';

    private const NONCE_ACTION_EXPORT = 'woo_excel_export';

    private ImportService $importService;

    private ExportService $exportService;

    // Riceve le dipendenze dall'esterno (ImportService ed ExportService)
    // e le assegna alle proprietà private. 
    public function __construct(ImportService $importService, ExportService $exportService)
    // accetta solo un oggetto di tipo ImportService, e dentro il metodo lo chiami $importService, stessa cosa per ExportService.
    {
        // Salva l'istanza di ImportService nella proprietà per usarla nei metodi della classe.
        $this->importService = $importService;

        $this->exportService = $exportService;
    }
    //
    // $importService (destra) — è il parametro appena ricevuto, esiste solo dentro il costruttore. $this->importService (sinistra) — è la proprietà della classe, esiste per tutta la vita dell'oggetto. 

    // Registra la voce di sottomenu nella barra laterale di WordPress admin.
    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',

            // tag <title>
            __('Excel Import/Export', 'woo-excel-importer'),

            // Testo della voce nel menu laterale di WordPress admin.
            __('Excel Import/Export', 'woo-excel-importer'),

            self::CAPABILITY,

            self::MENU_SLUG,

            // Callback: il metodo da chiamare per generare il contenuto HTML della pagina.
            [$this, 'renderPage']
        );
    }

    // Genera e mostra il contenuto HTML della pagina admin.
    public function renderPage(): void
    {
        $this->verifyCapability(self::CAPABILITY);

        // Costruisce l'array di dati da passare al template HTML.
        // In questo caso contiene solo i "notices" (messaggi di successo/errore da mostrare).
        $data = [
            'notices' => $this->getNotices(),
        ];

        // Carica e renderizza il template HTML della pagina, passandogli i dati.
        $this->renderView('admin-page', $data);
    }

    // Viene chiamato da WordPress quando il form di importazione viene inviato.
    public function handleImport(): void
    {
        // Verifica che la richiesta sia sicura: controlla il nonce, il metodo HTTP (POST)
        // e il permesso dell'utente.
        $this->verifySecureRequest('woo_excel_import_nonce', self::NONCE_ACTION_IMPORT, self::CAPABILITY);

        if (!isset($_FILES['excel_file'])) {
            $this->redirectWithError(__('No file was uploaded. Please select an Excel file and try again.', 'woo-excel-importer'));
            return;
        }

        $uploadedFile = $_FILES['excel_file'];
        $validationError = $this->importService->validateUploadedFile($uploadedFile);

        // Se la validazione ha restituito un errore 
        if ($validationError !== null) {
            $this->redirectWithError($validationError);
            return;
        }

        // Estrae il percorso temporaneo del file caricato
        // PHP salva i file caricati in una cartella temporanea prima che vengano elaborati.
        $tmpFile = $uploadedFile['tmp_name'];

        // tenta l'importazione e gestisce eventuali eccezioni.
        try {
            $report = $this->importService->importFromFile($tmpFile);
        } catch (\Exception $e) {
            $errorMessage = 'Import failed: ' . $e->getMessage();

            // Controlla se il messaggio dell'eccezione contiene le parole 'header' o 'column':
            // suggerisce un problema con la struttura delle colonne del file Excel.
            if (strpos($e->getMessage(), 'header') !== false || strpos($e->getMessage(), 'column') !== false) {
                $errorMessage .= ' <strong>Tip:</strong> Use the Export function to generate a correctly formatted template.';
            }

            $this->redirectWithError($errorMessage);
            return;
        }

        if ($report->hasErrors()) {
            $errorMessage = '<strong>Import failed:</strong><br>' . implode('<br>', $report->getErrors());
            $this->redirectWithError($errorMessage);
            return;
        }

        if ($report->getTotalProcessed() === 0 && $report->getRowsIgnored() > 0) {
            $errorMessage = 'No products were imported. All ' . $report->getRowsIgnored() . ' rows were ignored due to errors. Please review the ignored rows below and fix the issues in your Excel file.';

            // Salva il report nel transient di WordPress per poterlo recuperare dopo il redirect.
            $this->storeImportReport($report);

            $this->redirectWithError($errorMessage);
            return;
        }

        // Se l'importazione è avvenuta con successo, salva il report nel transient.
        $this->storeImportReport($report);

        // Costruisce il messaggio di successo con il numero di prodotti creati, aggiornati e termini creati.
        $successMessage = sprintf(
            // Stringa traducibile con tre segnaposto numerici (%d).
            __('Import completed successfully! Created: %d, Updated: %d, New terms: %d', 'woo-excel-importer'),

            // Numero di prodotti nuovi creati durante l'importazione.
            $report->getProductsCreated(),

            $report->getProductsUpdated(),
            $report->getTermsCreated()
        );
        $this->redirectWithSuccess($successMessage);
    }

    // Gestisce la richiesta di esportazione dei prodotti in formato Excel.
    // Viene chiamato quando l'utente clicca il pulsante "Esporta".
    public function handleExport(): void
    {
        // Verifica sicurezza della richiesta: nonce, metodo POST e permessi utente.
        $this->verifySecureRequest('woo_excel_export_nonce', self::NONCE_ACTION_EXPORT, self::CAPABILITY);

        // Tenta l'esportazione e gestisce eventuali errori.
        try {
            $this->exportService->exportAllProducts();
        } catch (\Exception $e) {
            $this->redirectWithError('Export failed: ' . $e->getMessage());
        }
    }

    // Costruisce l'URL di redirect con un parametro di errore nell'URL
    // e reindirizza l'utente alla pagina del plugin mostrando il messaggio di errore.
    private function redirectWithError(string $message): void
    {
        // Costruisce l'URL della pagina admin aggiungendo i parametri GET necessari.
        $url = add_query_arg([
            // Parametro 'page': indica a WordPress quale pagina admin mostrare.
            'page' => self::MENU_SLUG,

            // Parametro 'import_error': contiene il messaggio di errore codificato per l'URL.
            // urlencode() converte caratteri speciali (spazi, <, >, ecc.) in formato sicuro per URL.
            'import_error' => urlencode($message),

        // restituisce l'URL base della pagina admin di WordPress.
        ], admin_url('admin.php'));

        // verifica che l'URL sia nello stesso dominio, prevenendo redirect a siti esterni malevoli.
        wp_safe_redirect($url);

        exit;
    }

    // Costruisce l'URL di redirect con parametri di successo
    // e reindirizza l'utente alla pagina del plugin con il messaggio di conferma.
    private function redirectWithSuccess(string $message): void
    {
        // Costruisce l'URL con i parametri di successo da passare tramite query string.
        $url = add_query_arg([
            'page' => self::MENU_SLUG,

            // Flag che indica che l'importazione è andata a buon fine (valore '1' = true).
            'import_success' => '1',

            // Il messaggio di successo codificato per l'URL.
            'import_message' => urlencode($message),

        ], admin_url('admin.php'));
        wp_safe_redirect($url);
        exit;
    }

    // Salva temporaneamente il report di importazione
    // usando il sistema dei "transient" di WordPress (dati temporanei nel database).
    private function storeImportReport(ImportReport $report): void
    {
        // set_transient() salva un valore nel database con scadenza temporale.
        // Parametri: nome univoco del transient, il valore da salvare, scadenza in secondi (300 = 5 minuti).
        set_transient('woo_excel_import_report', $report, 300);
    }

    // Raccoglie i messaggi di notifica da mostrare nella pagina admin.
    private function getNotices(): array
    {
        $notices = [];

        // Controlla se nell'URL è presente il parametro 'import_error' (errore di importazione).
        if (isset($_GET['import_error'])) {
            
            // wp_unslash() rimuove i backslash aggiuntivi aggiunti da PHP (magic quotes).
            $error = sanitize_text_field(wp_unslash($_GET['import_error']));

            // sprintf() inserisce il testo dell'errore nel template HTML.
            // wp_kses_post() permette solo i tag HTML sicuri (bold, strong, ecc.) nel messaggio.
            $notices[] = sprintf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                wp_kses_post($error)
            );
        }

        // Controlla se nell'URL è presente il parametro 'import_success' (importazione riuscita).
        if (isset($_GET['import_success'])) {
            // Tenta di recuperare il report di importazione salvato nel transient del database.
            $report = get_transient('woo_excel_import_report');

            // Verifica che il valore recuperato sia effettivamente un'istanza di ImportReport.
            if ($report instanceof ImportReport) {
                // Se il report esiste, costruisce un notice HTML dettagliato con statistiche.
                $notices[] = $this->buildImportReport($report);

                // Elimina il transient dal database: non serve più dopo averlo mostrato.
                delete_transient('woo_excel_import_report');
            } else {
                // Se il report non è disponibile, usa il messaggio testuale dall'URL come fallback.
                // L'operatore ternario controlla se 'import_message' è presente nell'URL.
                $message = isset($_GET['import_message'])
                    // Se presente, sanitizza e usa il messaggio dall'URL.
                    ? sanitize_text_field(wp_unslash($_GET['import_message']))
                    : __('Import completed.', 'woo-excel-importer');

                $notices[] = sprintf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html($message)
                );
            }
        }
        return $notices;
    }

    // Costruisce l'HTML dettagliato del report di importazione
    // con statistiche sui prodotti elaborati e dettagli sulle righe ignorate.
    private function buildImportReport(ImportReport $report): string
    {
        // Controlla se ci sono righe ignorate: se getRowsIgnored() > 0, ci sono avvertimenti.
        $hasWarnings = $report->getRowsIgnored() > 0;

        // Determina la classe CSS del notice in base alla presenza di avvertimenti:
        $noticeClass = $hasWarnings ? 'notice-warning' : 'notice-success';

        // Avvia il buffer di output: tutto l'HTML scritto da ora verrà catturato in memoria
        // invece di essere inviato direttamente al browser.
        ob_start();
        ?>
        <?php // Div principale del notice WordPress con classe dinamica (warning o success). ?>
        <div class="notice <?php echo esc_attr($noticeClass); ?> is-dismissible">
            <h3><?php echo esc_html__('Import Completed', 'woo-excel-importer'); ?></h3>

            <?php // Sezione che contiene la tabella riassuntiva. ?>
            <div style="background: #fff; padding: 15px; border-left: 4px solid #46b450; margin: 10px 0;">
                <h4 style="margin-top: 0;"><?php echo esc_html__('Summary', 'woo-excel-importer'); ?></h4>
                <table style="width: 100%; max-width: 600px;">
                    <tr>
                        <td style="padding: 5px 10px;"><strong><?php echo esc_html__('✓ Products Created:', 'woo-excel-importer'); ?></strong></td>
                        <td style="padding: 5px 10px;"><span style="color: #46b450; font-weight: bold;"><?php echo esc_html($report->getProductsCreated()); ?></span></td>
                    </tr>
                    <tr style="background: #f9f9f9;">
                        <td style="padding: 5px 10px;"><strong><?php echo esc_html__('↻ Products Updated:', 'woo-excel-importer'); ?></strong></td>
                        <td style="padding: 5px 10px;"><span style="color: #0073aa; font-weight: bold;"><?php echo esc_html($report->getProductsUpdated()); ?></span></td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 10px;"><strong><?php echo esc_html__('+ New Terms Created:', 'woo-excel-importer'); ?></strong></td>
                        <td style="padding: 5px 10px;"><span style="font-weight: bold;"><?php echo esc_html($report->getTermsCreated()); ?></span></td>
                    </tr>
                    <?php // Mostra la riga delle righe ignorate SOLO se ci sono avvertimenti. ?>
                    <?php if ($hasWarnings): ?>
                    <tr style="background: #fff3cd;">
                        <td style="padding: 5px 10px;"><strong><?php echo esc_html__('⚠ Rows Ignored:', 'woo-excel-importer'); ?></strong></td>
                        <td style="padding: 5px 10px;"><span style="color: #856404; font-weight: bold;"><?php echo esc_html($report->getRowsIgnored()); ?></span></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php // Mostra la sezione dettagli righe ignorate SOLO se ci sono avvertimenti. ?>
            <?php if ($hasWarnings): ?>
                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;">
                    <?php // Elemento HTML <details>: crea un pannello espandibile/collassabile cliccando. ?>
                    <details style="cursor: pointer;">
                        <?php // Elemento <summary>: il titolo cliccabile del pannello espandibile. ?>
                        <summary style="font-weight: bold; cursor: pointer; user-select: none;">
                            <?php echo esc_html__('⚠ View Ignored Rows Details', 'woo-excel-importer'); ?>
                            <span style="font-weight: normal; color: #856404;">
                                (<?php echo esc_html($report->getRowsIgnored()); ?> rows were skipped due to errors)
                            </span>
                        </summary>

                        <?php // Contenuto del pannello espandibile, con scroll orizzontale per tabelle larghe. ?>
                        <div style="margin-top: 15px; overflow-x: auto;">
                            <p style="margin-bottom: 10px;">
                                <strong><?php echo esc_html__('Common issues and solutions:', 'woo-excel-importer'); ?></strong>
                            </p>
                            <ul style="margin-left: 20px; margin-bottom: 15px;">
                                <li>Empty or invalid SKU → Ensure each product has a unique SKU with only alphanumeric characters, dots, dashes, or underscores</li>
                                <li>Invalid price → Use numeric format (e.g., 10.50 or 10,50)</li>
                                <li>Duplicate SKU → Each SKU must be unique in the file</li>
                                <li>Empty title → Each product must have a name</li>
                            </ul>

                            <table class="widefat striped" style="margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;"><?php echo esc_html__('Row #', 'woo-excel-importer'); ?></th>
                                        <th style="width: 120px;"><?php echo esc_html__('SKU', 'woo-excel-importer'); ?></th>
                                        <th><?php echo esc_html__('Reason', 'woo-excel-importer'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php // Ciclo foreach: itera su ogni riga ignorata del report e la mostra. ?>
                                    <?php foreach ($report->getIgnoredRows() as $ignored): ?>
                                        <tr>
                                            <?php // Numero della riga nel file Excel ?>
                                            <td style="font-weight: bold;"><?php echo esc_html($ignored['row']); ?></td>
                                            <td><code><?php echo esc_html($ignored['sku'] ?: '—'); ?></code></td>
                                            <td><?php echo esc_html($ignored['reason']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <p style="margin-top: 15px; padding: 10px; background: #fff; border-left: 3px solid #0073aa;">
                                <strong><?php echo esc_html__('💡 Tip:', 'woo-excel-importer'); ?></strong>
                                <?php echo esc_html__('Fix the errors in your Excel file and re-import. Successfully processed products will be updated, not duplicated.', 'woo-excel-importer'); ?>
                            </p>
                        </div>
                    </details>
                </div>
            <?php endif; ?>
        </div>
        <?php
        // ob_get_clean() recupera tutto l'HTML accumulato nel buffer e lo restituisce come stringa,
        // azzerando il buffer. In questo modo il metodo restituisce l'HTML senza averlo stampato.
        return ob_get_clean();
    }

    // Metodo privato che carica e mostra un template PHP dalla cartella views/.
    // Implementa un pattern MVC semplificato: separa la logica dalla presentazione.
    private function renderView(string $template, array $data = []): void
    {
        // extract() trasforma ogni chiave dell'array $data in una variabile locale accessibile nel template.
        // EXTR_SKIP evita di sovrascrivere variabili già esistenti con lo stesso nome.
        extract($data, EXTR_SKIP);

        // Costruisce il percorso assoluto del file template unendo la costante del plugin
        // (percorso base del plugin) con il nome del template e l'estensione .php.
        $viewPath = WOO_EXCEL_IMPORTER_PATH . 'views/' . $template . '.php';

        // Verifica che il file del template esista fisicamente sul filesystem.
        if (file_exists($viewPath)) {
            // Carica ed esegue il file template, che ora ha accesso alle variabili estratte da $data.
            include $viewPath;
        } else {
            // Se il file non esiste, usa wp_die() per mostrare un messaggio di errore
            // e interrompere l'esecuzione di WordPress in modo controllato.
            wp_die(sprintf(
                esc_html__('Template file not found: %s', 'woo-excel-importer'),
                esc_html($template)
            ));
        }
    }

    // Carica i file CSS e JS del plugin nelle pagine admin corrette.
    // Viene chiamato tramite il hook 'admin_enqueue_scripts' di WordPress.
    // Il parametro $hook contiene l'identificatore della pagina admin corrente.
    public function enqueueAssets(string $hook): void
    {
        // Carica gli stili nella pagina del plugin
        // Controlla se lo slug del plugin è contenuto nell'identificatore della pagina corrente.
        if (strpos($hook, self::MENU_SLUG) !== false) {
            // Registra e carica il foglio di stile CSS del plugin.
            wp_enqueue_style(
                // Handle univoco dello stile, usato da WordPress per evitare duplicati.
                'woo-excel-importer-admin',
                // URL assoluto del file CSS, costruito dalla costante URL del plugin.
                WOO_EXCEL_IMPORTER_URL . 'assets/admin.css',
                // Array delle dipendenze CSS: vuoto perché non dipende da altri stili.
                [],
                // Versione del file: usata da WordPress per il cache-busting (aggiorna il file nel browser).
                WOO_EXCEL_IMPORTER_VERSION
            );
        }

        // Carica gli stili anche nella lista prodotti WooCommerce (per il filtro tassonomie)
        // Accede alla variabile globale WordPress che contiene il tipo di post corrente nell'admin.
        global $typenow;

        // Controlla se la pagina corrente mostra post di tipo 'product' (lista prodotti WooCommerce).
        if ($typenow === 'product') {
            // Carica lo stesso foglio di stile CSS anche nella pagina lista prodotti.
            // Questo permette di applicare stili personalizzati ai filtri di tassonomia nella lista.
            wp_enqueue_style(
                // Stesso handle: se lo stile è già stato caricato, WordPress non lo carica di nuovo.
                'woo-excel-importer-admin',
    
                WOO_EXCEL_IMPORTER_URL . 'assets/admin.css',
                [],
                WOO_EXCEL_IMPORTER_VERSION
            );
        }
    }
}
