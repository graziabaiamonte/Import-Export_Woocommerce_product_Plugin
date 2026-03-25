<?php
/**
 * Admin Page Template
 * 
 * @var array $notices
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('WooCommerce Excel Import/Export', 'woo-excel-importer'); ?></h1>

    <?php foreach ($notices as $notice): ?>
        <?php echo wp_kses_post($notice); ?>
    <?php endforeach; ?>

    <div class="woo-excel-importer-container">
        
        <!-- Import Section -->
        <div class="card" style="max-width: 800px;">
            <h2><?php echo esc_html__('Import Products from Excel', 'woo-excel-importer'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">

                // parametro action = 'woo_excel_import' >> Identifica a cosa serve questo nonce — WordPress lo usa per generare e verificare il token
                //
                // name	'woo_excel_import_nonce' >>	Il name dell'input hidden nell'HTML, usato per inviare il token al server quando il form viene sottomesso
                //
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

    </div>
</div>


// NONCE
//
// token di sicurezza temporaneo che WordPress genera e poi verifica. Serve a garantire che una richiesta (es. submit di un form) provenga davvero dalla tua pagina e non da un sito esterno malevolo — CSRF (Cross-Site Request Forgery).
