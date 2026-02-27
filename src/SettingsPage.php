<?php

declare(strict_types=1);

namespace WooExcelImporter;

/**
 * SettingsPage - Handles plugin settings page in WordPress admin.
 */
final class SettingsPage
{
    use SecureFormHandler;

    private const MENU_SLUG = 'woo-excel-importer-settings';
    private const CAPABILITY = 'manage_woocommerce';

    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Excel Import/Export Settings', 'woo-excel-importer'),
            __('Import/Export Settings', 'woo-excel-importer'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $this->verifyCapability(self::CAPABILITY);

        $data = [
            'deleteOnUninstall' => get_option('woo_excel_importer_delete_on_uninstall', 'no'),
            'settingsUpdated' => isset($_GET['settings-updated']),
        ];

        $this->renderView('settings-page', $data);
    }

    public function handleSave(): void
    {
        $this->verifySecureRequest('woo_excel_settings_nonce', 'woo_excel_save_settings', self::CAPABILITY);

        $deleteOnUninstall = isset($_POST['delete_on_uninstall']) && $_POST['delete_on_uninstall'] === 'yes' ? 'yes' : 'no';
        update_option('woo_excel_importer_delete_on_uninstall', $deleteOnUninstall);

        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=' . self::MENU_SLUG)));
        exit;
    }

    private function renderView(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        
        $viewPath = WOO_EXCEL_IMPORTER_PATH . 'views/' . $template . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            wp_die(sprintf(
                esc_html__('Template file not found: %s', 'woo-excel-importer'),
                esc_html($template)
            ));
        }
    }
}
