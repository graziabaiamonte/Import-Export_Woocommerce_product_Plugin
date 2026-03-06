<?php
/**
 * Uninstall script for WooCommerce Excel Import/Export
 * 
 * This file is executed when the plugin is uninstalled via WordPress admin.
 * It automatically removes all plugin data including custom taxonomies and their terms.
 * 
 * NOTE: Products are NEVER deleted, only the taxonomy data is removed.
 */

declare(strict_types=1);

// Exit if accessed directly or not from WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('woo_excel_importer_custom_taxonomies');

// Get all custom taxonomies from the plugin (including old versions with dashes)
$taxonomySlugs = [
    // Current version (with underscores)
    'quantity_per_box',
    'disposable_reusable',
    // 'product_cat' è la tassonomia nativa WooCommerce: non va eliminata
    // 'product_category' era il vecchio slug custom (rimosso)
    'steel_titanium_instruments',
    'backflush_type',
    'backflush_tip',
    'bypass_type',
    'chandeliers_type',
    'dosage',
    'packaging',
    'gas_type',
    'gauge',
    'illumination_connector_for',
    'illumination_type',
    'knives_blades',
    'laser_connector',
    'laser_fiber',
    'mixing_ratio',
    'pic_type',
    'tip_angle',
    'tip_type',
    'tubing_type',
    'tweezer',
    'tweezer_type',
    'use_for',
    'nacl_percentage',
    // Old versions (with dashes) - for backward compatibility
    'quantity-per-box',
    'disposable-reusable',
    'steel-titanium-families',
    'backflush-type',
    'backflush-tip',
    'packaging-gas-gauge',
    'knives-blades',
    'tip-angle',
    'tip-type',
    'tubing-type',
    'use-for',
    'nacl-percentage',
];

// Direct database cleanup - the plugin is already deactivated at this point
// so we can't use WordPress functions that require registered taxonomies
global $wpdb;

// Log file for debugging (optional - can be removed after testing)
$log_file = WP_CONTENT_DIR . '/woo-excel-uninstall.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting uninstall\n", FILE_APPEND);

// Prepare taxonomy list for SQL
$taxonomyList = "'" . implode("','", array_map('esc_sql', $taxonomySlugs)) . "'";

file_put_contents($log_file, "Taxonomy list: {$taxonomyList}\n", FILE_APPEND);

// Step 1: Get term_taxonomy_ids that need to be deleted
$term_taxonomy_ids = $wpdb->get_col(
    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

file_put_contents($log_file, "Found " . count($term_taxonomy_ids) . " term_taxonomy entries\n", FILE_APPEND);

// Step 2: Delete term relationships for these taxonomies
if (!empty($term_taxonomy_ids)) {
    $ids_list = implode(',', array_map('intval', $term_taxonomy_ids));
    $deleted_relationships = $wpdb->query(
        "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ({$ids_list})"
    );
    file_put_contents($log_file, "Deleted {$deleted_relationships} term relationships\n", FILE_APPEND);
}

// Step 3: Get term_ids before deleting term_taxonomy entries
$term_ids = $wpdb->get_col(
    "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

// Step 4: Delete term_taxonomy entries
$deleted_taxonomies = $wpdb->query(
    "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

file_put_contents($log_file, "Deleted {$deleted_taxonomies} term_taxonomy entries\n", FILE_APPEND);

// Step 5: Delete terms that are no longer used by any taxonomy
if (!empty($term_ids)) {
    foreach ($term_ids as $term_id) {
        // Check if term is still used by other taxonomies
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE term_id = %d",
                $term_id
            )
        );
        
        // If term is not used anymore, delete it
        if ($count == 0) {
            $wpdb->delete($wpdb->terms, ['term_id' => $term_id], ['%d']);
            $wpdb->delete($wpdb->termmeta, ['term_id' => $term_id], ['%d']);
        }
    }
}

// Step 6: Clean up any remaining orphaned relationships
$wpdb->query(
    "DELETE tr FROM {$wpdb->term_relationships} tr
    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    WHERE tt.term_taxonomy_id IS NULL"
);

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Uninstall completed\n", FILE_APPEND);

// Note: We do NOT delete products, only the custom taxonomies and their terms
// Products remain intact in WooCommerce
