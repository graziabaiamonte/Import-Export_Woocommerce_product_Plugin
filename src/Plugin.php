<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class Plugin
{
    private TaxonomyRegistrar $taxonomyRegistrar;
    private AdminPage $adminPage;

    public function __construct()
    {
        $this->taxonomyRegistrar = new TaxonomyRegistrar();
        
        $taxonomyService = new TaxonomyService($this->taxonomyRegistrar);
        $productService = new ProductService($taxonomyService);
        $excelReader = new ExcelReader();
        $excelWriter = new ExcelWriter($this->taxonomyRegistrar);
        
        $importService = new ImportService($productService, $excelReader);
        $exportService = new ExportService($excelWriter);
        
        $this->adminPage = new AdminPage($importService, $exportService);
    }

    public function init(): void
    {
        add_action('init', [$this, 'checkDependencies']);
        add_action('init', [$this->taxonomyRegistrar, 'register']);
        add_action('admin_menu', [$this->adminPage, 'addMenu']);
        add_action('admin_menu', [$this, 'addSettingsMenu']);
        add_action('admin_post_woo_excel_import', [$this->adminPage, 'handleImport']);
        add_action('admin_post_woo_excel_export', [$this->adminPage, 'handleExport']);
        add_action('admin_post_woo_excel_save_settings', [$this, 'saveSettings']);
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

    public function addSettingsMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Excel Import/Export Settings', 'woo-excel-importer'),
            __('Import/Export Settings', 'woo-excel-importer'),
            'manage_woocommerce',
            'woo-excel-importer-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'woo-excel-importer'));
        }

        $deleteOnUninstall = get_option('woo_excel_importer_delete_on_uninstall', 'no');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Excel Import/Export Settings', 'woo-excel-importer'); ?></h1>

            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Settings saved.', 'woo-excel-importer'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="woo_excel_save_settings">
                <?php wp_nonce_field('woo_excel_save_settings', 'woo_excel_settings_nonce'); ?>

                <h2><?php echo esc_html__('Uninstall Options', 'woo-excel-importer'); ?></h2>
                
                <div class="notice notice-warning inline" style="margin: 15px 0; padding: 12px;">
                    <p><strong><?php echo esc_html__('⚠️ Important: Choose what happens when you delete this plugin', 'woo-excel-importer'); ?></strong></p>
                </div>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Data Deletion', 'woo-excel-importer'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="delete_on_uninstall" value="yes" <?php checked($deleteOnUninstall, 'yes'); ?>>
                                    <strong><?php echo esc_html__('Delete all plugin data when uninstalling', 'woo-excel-importer'); ?></strong>
                                </label>
                                <p class="description" style="margin-top: 10px;">
                                    <?php echo esc_html__('When you delete this plugin from WordPress:', 'woo-excel-importer'); ?>
                                </p>
                                <ul style="margin-left: 20px; margin-top: 5px;">
                                    <li><?php echo esc_html__('✓ If CHECKED: All 26 custom taxonomies and their terms will be permanently deleted', 'woo-excel-importer'); ?></li>
                                    <li><?php echo esc_html__('✓ If UNCHECKED: Taxonomies and terms will be kept in the database', 'woo-excel-importer'); ?></li>
                                    <li><strong><?php echo esc_html__('Note: Your products will NEVER be deleted, only the taxonomy data', 'woo-excel-importer'); ?></strong></li>
                                </ul>
                                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;">
                                    <strong><?php echo esc_html__('💡 Recommendation:', 'woo-excel-importer'); ?></strong>
                                    <p style="margin: 5px 0 0 0;">
                                        <?php echo esc_html__('Leave this UNCHECKED if you plan to reinstall the plugin later or want to keep the taxonomy terms for reference.', 'woo-excel-importer'); ?>
                                    </p>
                                </div>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'woo-excel-importer')); ?>
            </form>
        </div>
        <?php
    }

    public function saveSettings(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'woo-excel-importer'));
        }

        if (!isset($_POST['woo_excel_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woo_excel_settings_nonce'])), 'woo_excel_save_settings')) {
            wp_die(esc_html__('Security check failed.', 'woo-excel-importer'));
        }

        $deleteOnUninstall = isset($_POST['delete_on_uninstall']) && $_POST['delete_on_uninstall'] === 'yes' ? 'yes' : 'no';
        update_option('woo_excel_importer_delete_on_uninstall', $deleteOnUninstall);

        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=woo-excel-importer-settings')));
        exit;
    }
}
