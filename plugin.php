<?php
/**
 * Plugin Name: WooCommerce Excel Import/Export
 * Plugin URI: https://example.com
 * Description: Enterprise-level bulk product catalog management via Excel import/export for WooCommerce
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Grazia Baiamonte
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-excel-importer
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 */

declare(strict_types=1);

namespace WooExcelImporter;

if (!defined('ABSPATH')) {
    exit;
}

define('WOO_EXCEL_IMPORTER_VERSION', '1.0.0');
define('WOO_EXCEL_IMPORTER_FILE', __FILE__);
define('WOO_EXCEL_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('WOO_EXCEL_IMPORTER_URL', plugin_dir_url(__FILE__));

// Check if Composer autoload exists
$autoload_file = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload_file)) {
    add_action('admin_notices', function() {
        printf(
            '<div class="notice notice-error"><p><strong>WooCommerce Excel Import/Export:</strong> %s</p></div>',
            esc_html__('Composer dependencies are missing. Please run "composer install" in the plugin directory.', 'woo-excel-importer')
        );
    });
    return;
}

require_once $autoload_file;

if (!class_exists(Plugin::class)) {
    return;
}

$plugin = new Plugin();
$plugin->init();

register_activation_hook(__FILE__, [$plugin, 'activate']);
register_deactivation_hook(__FILE__, [$plugin, 'deactivate']);

// Register uninstall hook - WordPress will execute uninstall.php when plugin is deleted
// This is a fallback to ensure cleanup happens even if uninstall.php doesn't execute automatically
register_uninstall_hook(__FILE__, 'WooExcelImporter\\uninstall_cleanup');

/**
 * Cleanup function called on plugin uninstall.
 * This is a fallback that ensures the uninstall.php file is executed.
 */
function uninstall_cleanup(): void
{
    $uninstall_file = __DIR__ . '/uninstall.php';
    if (file_exists($uninstall_file)) {
        include_once $uninstall_file;
    }
}
