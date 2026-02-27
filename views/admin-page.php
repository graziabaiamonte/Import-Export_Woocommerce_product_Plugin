<?php
/**
 * Admin Page Template
 * 
 * @var string $settingsUrl
 * @var array $notices
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('WooCommerce Excel Import/Export', 'woo-excel-importer'); ?></h1>
    
    <p style="margin-bottom: 20px;">
        <a href="<?php echo esc_url($settingsUrl); ?>" class="button button-secondary">
            ⚙️ <?php echo esc_html__('Plugin Settings', 'woo-excel-importer'); ?>
        </a>
    </p>

    <?php foreach ($notices as $notice): ?>
        <?php echo wp_kses_post($notice); ?>
    <?php endforeach; ?>

    <div class="woo-excel-importer-container">
        <!-- Import Section -->
        <div class="card" style="max-width: 800px;">
            <h2><?php echo esc_html__('Import Products from Excel', 'woo-excel-importer'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="woo_excel_import">
                <?php wp_nonce_field('woo_excel_import', 'woo_excel_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="excel_file"><?php echo esc_html__('Excel File', 'woo-excel-importer'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="excel_file" id="excel_file" required accept=".xls,.xlsx,.csv">
                            <p class="description">
                                <?php echo esc_html__('Upload an Excel file (.xls, .xlsx, .csv). Maximum size: 10MB', 'woo-excel-importer'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Import Products', 'woo-excel-importer'), 'primary', 'submit_import'); ?>
            </form>
        </div>

        <!-- Export Section -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php echo esc_html__('Export Products to Excel', 'woo-excel-importer'); ?></h2>
            <p><?php echo esc_html__('Export all WooCommerce products to an Excel file that can be re-imported without modifications.', 'woo-excel-importer'); ?></p>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="woo_excel_export">
                <?php wp_nonce_field('woo_excel_export', 'woo_excel_export_nonce'); ?>
                
                <?php submit_button(__('Export Products', 'woo-excel-importer'), 'secondary', 'submit_export'); ?>
            </form>
        </div>

        <!-- Format Info Section -->
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2><?php echo esc_html__('Excel File Format', 'woo-excel-importer'); ?></h2>
            <p><?php echo esc_html__('The Excel file must have the following columns in this exact order:', 'woo-excel-importer'); ?></p>
            <ol style="font-family: monospace; font-size: 12px;">
                <li>SKU;</li>
                <li>TITLE;</li>
                <li>DESCRIPTION;</li>
                <li>PRICE;</li>
                <li>QUANTITY PER BOX;</li>
                <li>DISPOSABLE/REUSABLE;</li>
                <li>CATEGORY;</li>
                <li>STEEL & TITANIUM INSTRUMENTS FAMILIES;</li>
                <li>And 18 more taxonomy columns...</li>
            </ol>
            <p><?php echo esc_html__('Use the Export function to generate a correctly formatted file.', 'woo-excel-importer'); ?></p>
        </div>
    </div>
</div>
