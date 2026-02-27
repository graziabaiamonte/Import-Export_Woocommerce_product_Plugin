<?php
/**
 * Settings Page Template
 * 
 * @var string $deleteOnUninstall
 * @var bool $settingsUpdated
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Excel Import/Export Settings', 'woo-excel-importer'); ?></h1>

    <?php if ($settingsUpdated): ?>
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
