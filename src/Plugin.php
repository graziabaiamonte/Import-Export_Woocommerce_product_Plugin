<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class Plugin
{
    private TaxonomyRegistrar $taxonomyRegistrar;
    private AdminPage $adminPage;
    private SettingsPage $settingsPage;

    public function __construct()
    {
        $this->taxonomyRegistrar = new TaxonomyRegistrar();
        
        $taxonomyService = new TaxonomyService($this->taxonomyRegistrar);
        $productService = new ProductService($taxonomyService);
        $excelReader = new ExcelReader();
        $excelWriter = new ExcelWriter($this->taxonomyRegistrar, $taxonomyService);
        
        $importService = new ImportService($productService, $excelReader);
        $exportService = new ExportService($excelWriter);
        
        $this->adminPage = new AdminPage($importService, $exportService);
        $this->settingsPage = new SettingsPage();
    }

    public function init(): void
    {
        add_action('init', [$this, 'checkDependencies']);
        add_action('init', [$this->taxonomyRegistrar, 'register']);
        add_action('admin_menu', [$this->adminPage, 'addMenu']);
        add_action('admin_menu', [$this->settingsPage, 'addMenu']);
        add_action('admin_post_woo_excel_import', [$this->adminPage, 'handleImport']);
        add_action('admin_post_woo_excel_export', [$this->adminPage, 'handleExport']);
        add_action('admin_post_woo_excel_save_settings', [$this->settingsPage, 'handleSave']);
        add_action('admin_enqueue_scripts', [$this->adminPage, 'enqueueAssets']);
    }

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

        // Register taxonomies, skipping those already connected to product post type
        $this->taxonomyRegistrar->registerOnActivation();
        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
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
        return class_exists('WooCommerce');
    }
}
