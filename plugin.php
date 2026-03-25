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

// Dichiara il namespace del plugin. Tutti i file PHP del plugin useranno questo namespace
// per evitare conflitti di nome con altre classi o funzioni di WordPress o altri plugin.
namespace WooExcelImporter;

// Controllo di sicurezza fondamentale: se la costante ABSPATH non è definita,
// significa che qualcuno sta cercando di accedere direttamente a questo file
// (non tramite WordPress).
if (!defined('ABSPATH')) {
    exit;
}

// Definisce la versione corrente del plugin come costante globale.
define('WOO_EXCEL_IMPORTER_VERSION', '1.0.0');

// Definisce il percorso assoluto al file plugin.php.
define('WOO_EXCEL_IMPORTER_FILE', __FILE__);

// Definisce il percorso assoluto alla cartella radice del plugin sul server
// (es. /var/www/html/wp-content/plugins/Import-Export_Woocommerce_product_Plugin/).
define('WOO_EXCEL_IMPORTER_PATH', plugin_dir_path(__FILE__));

// Definisce l'URL pubblico della cartella radice del plugin
// (es. https://example.com/wp-content/plugins/Import-Export_Woocommerce_product_Plugin/).
// Usata per caricare asset come CSS e JS nel browser.
define('WOO_EXCEL_IMPORTER_URL', plugin_dir_url(__FILE__));


// Costruisce il percorso al file autoload.php generato da Composer
$autoload_file = __DIR__ . '/vendor/autoload.php';

// Se non esiste, significa che "composer install" non è stato eseguito nella cartella del plugin.
if (!file_exists($autoload_file)) {
    add_action('admin_notices', function() {
        printf(
            '<div class="notice notice-error"><p><strong>WooCommerce Excel Import/Export:</strong> %s</p></div>',
            esc_html__('Composer dependencies are missing. Please run "composer install" in the plugin directory.', 'woo-excel-importer')
        );
    });
    return;
}

// Include il file autoload.php di Composer, attivando il caricamento automatico
// di tutte le classi PHP dichiarate nelle dipendenze del plugin.
require_once $autoload_file;

if (!class_exists(Plugin::class)) {
    return;
}

$plugin = new Plugin(); 

// registra tutti gli hook WordPress (azioni e filtri),
// inizializza i componenti interni e prepara il plugin a funzionare.
$plugin->init();

// Registra il metodo activate() della classe Plugin come hook di attivazione.
// WordPress lo eseguirà automaticamente quando l'amministratore attiva il plugin dal pannello Plugins
//
// __FILE__ fornisce il percorso assoluto al file principale del plugin
register_activation_hook(__FILE__, [$plugin, 'activate']);

register_deactivation_hook(__FILE__, [$plugin, 'deactivate']);

// Il doppio \\ serve perché dentro una stringa PHP il backslash \ è un carattere di escape, quindi ne servono due per rappresentarne uno solo.
register_uninstall_hook(__FILE__, 'WooExcelImporter\\uninstall_cleanup');

/**
 * Cleanup function called on plugin uninstall.
 * This is a fallback that ensures the uninstall.php file is executed.
 */
function uninstall_cleanup(): void
{
    // Costruisce il percorso assoluto al file 
    $uninstall_file = __DIR__ . '/uninstall.php';
    if (file_exists($uninstall_file)) {
        include_once $uninstall_file;
    }
}

// -------------------------------------------------------------------------
// I 4 MODI DI INCLUDERE UN FILE IN PHP
// -------------------------------------------------------------------------
// | Istruzione   | File mancante              | File già incluso          |
// |--------------|----------------------------|---------------------------|
// | include      | Warning, continua          | Lo include di nuovo       |
// | include_once | Warning, continua          | Lo salta                  |
// | require      | Errore fatale, si ferma    | Lo include di nuovo       |
// | require_once | Errore fatale, si ferma    | Lo salta                  |
// -------------------------------------------------------------------------
