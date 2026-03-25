<?php
declare(strict_types=1);

namespace WooExcelImporter;

// Scopo = collegare i servizi (ImportService, ExportService, AdminPage, ecc.) e registrare gli hook WordPress.
final class Plugin
{
    // Proprietà privata che tiene un riferimento all'oggetto TaxonomyRegistrar
    private TaxonomyRegistrar $taxonomyRegistrar;

    private AdminPage $adminPage;

    // Qui vengono istanziati e collegati tra loro tutti i servizi del plugin
    // seguendo il pattern Dependency Injection (DI).
    public function __construct()
    {
        // Crea l'istanza di TaxonomyRegistrar
        $this->taxonomyRegistrar = new TaxonomyRegistrar();

        $taxonomyService = new TaxonomyService($this->taxonomyRegistrar);
        $productService = new ProductService($taxonomyService);
        $excelReader = new ExcelReader();
        $excelWriter = new ExcelWriter($this->taxonomyRegistrar, $taxonomyService);
        $importService = new ImportService($productService, $excelReader);
        $exportService = new ExportService($excelWriter);
        $this->adminPage = new AdminPage($importService, $exportService);
    }

    // Registrazione degli hook di wordpress
    //
    public function init(): void
    {
        // Aggancia il metodo checkDependencies all'hook "init" di WordPress
        add_action('init', [$this, 'checkDependencies']);

        // Aggancia il metodo "register" di TaxonomyRegistrar all'hook "init"
        add_action('init', [$this->taxonomyRegistrar, 'register']);

        add_action('admin_menu', [$this->adminPage, 'addMenu']);

        // L'hook admin_post_{action} viene eseguito quando un form viene inviato nell'admin.
        add_action('admin_post_woo_excel_import', [$this->adminPage, 'handleImport']);

        add_action('admin_post_woo_excel_export', [$this->adminPage, 'handleExport']);
        add_action('admin_enqueue_scripts', [$this->adminPage, 'enqueueAssets']);
    }

    // Metodo chiamato da WordPress quando il plugin viene attivato dall'utente
    // tramite la pagina "Plugin"
    public function activate(): void
    {
        if (!$this->isWooCommerceActive()) {
            deactivate_plugins(plugin_basename(WOO_EXCEL_IMPORTER_FILE));
            wp_die(
                esc_html__('This plugin requires WooCommerce to be installed and active.', 'woo-excel-importer'),
                esc_html__('Plugin Activation Error', 'woo-excel-importer'),
                ['back_link' => true]
            );
        }

        $this->taxonomyRegistrar->registerOnActivation();

        // Funzione nativa di WP che rigenera e salva nel database i permalink.
        // Necessario dopo aver registrato nuove tassonomie per evitare errori 404.
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        $this->taxonomyRegistrar->cleanOrphanedTerms();
        flush_rewrite_rules();
    }

    public function checkDependencies(): void
    {
        if (!$this->isWooCommerceActive()) {
            add_action('admin_notices', function () {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html__('WooCommerce Excel Import/Export requires WooCommerce to be active.', 'woo-excel-importer')
                );
            });
        }
    }

    private function isWooCommerceActive(): bool
    {
        // se esiste, significa che il plugin WooCommerce è attivo.
        return class_exists('WooCommerce');
    }
}
