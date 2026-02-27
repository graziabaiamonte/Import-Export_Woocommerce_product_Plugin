<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class AdminPage
{
    use SecureFormHandler;

    private const MENU_SLUG = 'woo-excel-importer';
    private const CAPABILITY = 'manage_woocommerce';
    private const NONCE_ACTION_IMPORT = 'woo_excel_import';
    private const NONCE_ACTION_EXPORT = 'woo_excel_export';

    private ImportService $importService;
    private ExportService $exportService;

    public function __construct(ImportService $importService, ExportService $exportService)
    {
        $this->importService = $importService;
        $this->exportService = $exportService;
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Excel Import/Export', 'woo-excel-importer'),
            __('Excel Import/Export', 'woo-excel-importer'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderPage']
        );
    }

    public function renderPage(): void
    {
        $this->verifyCapability(self::CAPABILITY);

        $data = [
            'settingsUrl' => admin_url('admin.php?page=woo-excel-importer-settings'),
            'notices' => $this->getNotices(),
        ];

        $this->renderView('admin-page', $data);
    }

    public function handleImport(): void
    {
        $this->verifySecureRequest('woo_excel_import_nonce', self::NONCE_ACTION_IMPORT, self::CAPABILITY);

        if (!isset($_FILES['excel_file'])) {
            $this->redirectWithError(__('No file was uploaded. Please select an Excel file and try again.', 'woo-excel-importer'));
            return;
        }

        $uploadedFile = $_FILES['excel_file'];
        $validationError = $this->importService->validateUploadedFile($uploadedFile);

        if ($validationError !== null) {
            $this->redirectWithError($validationError);
            return;
        }

        $tmpFile = $uploadedFile['tmp_name'];

        try {
            $report = $this->importService->importFromFile($tmpFile);
        } catch (\Exception $e) {
            $errorMessage = 'Import failed: ' . $e->getMessage();
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
            $this->storeImportReport($report);
            $this->redirectWithError($errorMessage);
            return;
        }

        $this->storeImportReport($report);
        $successMessage = sprintf(
            __('Import completed successfully! Created: %d, Updated: %d, New terms: %d', 'woo-excel-importer'),
            $report->getProductsCreated(),
            $report->getProductsUpdated(),
            $report->getTermsCreated()
        );
        $this->redirectWithSuccess($successMessage);
    }

    public function handleExport(): void
    {
        $this->verifySecureRequest('woo_excel_export_nonce', self::NONCE_ACTION_EXPORT, self::CAPABILITY);

        try {
            $this->exportService->exportAllProducts();
        } catch (\Exception $e) {
            $this->redirectWithError('Export failed: ' . $e->getMessage());
        }
    }

    private function redirectWithError(string $message): void
    {
        $url = add_query_arg([
            'page' => self::MENU_SLUG,
            'import_error' => urlencode($message),
        ], admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }

    private function redirectWithSuccess(string $message): void
    {
        $url = add_query_arg([
            'page' => self::MENU_SLUG,
            'import_success' => '1',
            'import_message' => urlencode($message),
        ], admin_url('admin.php'));

        wp_safe_redirect($url);
        exit;
    }

    private function storeImportReport(ImportReport $report): void
    {
        set_transient('woo_excel_import_report', $report, 300);
    }

    private function getNotices(): array
    {
        $notices = [];

        if (isset($_GET['import_error'])) {
            $error = sanitize_text_field(wp_unslash($_GET['import_error']));
            $notices[] = sprintf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                wp_kses_post($error)
            );
        }

        if (isset($_GET['import_success'])) {
            $report = get_transient('woo_excel_import_report');
            
            if ($report instanceof ImportReport) {
                $notices[] = $this->buildImportReport($report);
                delete_transient('woo_excel_import_report');
            } else {
                $message = isset($_GET['import_message']) 
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

    private function buildImportReport(ImportReport $report): string
    {
        $hasWarnings = $report->getRowsIgnored() > 0;
        $noticeClass = $hasWarnings ? 'notice-warning' : 'notice-success';
        
        ob_start();
        ?>
        <div class="notice <?php echo esc_attr($noticeClass); ?> is-dismissible">
            <h3><?php echo esc_html__('Import Completed', 'woo-excel-importer'); ?></h3>
            
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
                    <?php if ($hasWarnings): ?>
                    <tr style="background: #fff3cd;">
                        <td style="padding: 5px 10px;"><strong><?php echo esc_html__('⚠ Rows Ignored:', 'woo-excel-importer'); ?></strong></td>
                        <td style="padding: 5px 10px;"><span style="color: #856404; font-weight: bold;"><?php echo esc_html($report->getRowsIgnored()); ?></span></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php if ($hasWarnings): ?>
                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;">
                    <details style="cursor: pointer;">
                        <summary style="font-weight: bold; cursor: pointer; user-select: none;">
                            <?php echo esc_html__('⚠ View Ignored Rows Details', 'woo-excel-importer'); ?>
                            <span style="font-weight: normal; color: #856404;">
                                (<?php echo esc_html($report->getRowsIgnored()); ?> rows were skipped due to errors)
                            </span>
                        </summary>
                        
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
                                    <?php foreach ($report->getIgnoredRows() as $ignored): ?>
                                        <tr>
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
        return ob_get_clean();
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

    public function enqueueAssets(string $hook): void
    {
        // Carica gli stili nella pagina del plugin
        if (strpos($hook, self::MENU_SLUG) !== false) {
            wp_enqueue_style(
                'woo-excel-importer-admin',
                WOO_EXCEL_IMPORTER_URL . 'assets/admin.css',
                [],
                WOO_EXCEL_IMPORTER_VERSION
            );
        }

        // Carica gli stili anche nella lista prodotti WooCommerce (per il filtro tassonomie)
        global $typenow;
        if ($typenow === 'product') {
            wp_enqueue_style(
                'woo-excel-importer-admin',
                WOO_EXCEL_IMPORTER_URL . 'assets/admin.css',
                [],
                WOO_EXCEL_IMPORTER_VERSION
            );
        }
    }
}
