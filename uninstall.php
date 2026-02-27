<?php
/**
 * Uninstall script for WooCommerce Excel Import/Export
 * 
 * This file is executed when the plugin is uninstalled via WordPress admin.
 * It checks the user's settings and optionally removes all plugin data.
 */

declare(strict_types=1);

// Exit if accessed directly or not from WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete data on uninstall
$deleteOnUninstall = get_option('woo_excel_importer_delete_on_uninstall', 'no');

if ($deleteOnUninstall !== 'yes') {
    // User wants to keep data, exit without deleting anything
    return;
}

// Delete plugin options
delete_option('woo_excel_importer_delete_on_uninstall');
delete_option('woo_excel_importer_custom_taxonomies');

// Get all registered taxonomies from the plugin
$taxonomySlugs = [
    'quantity_per_box',
    'disposable_reusable',
    'product_category',
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
];

// Delete all terms and taxonomy data
foreach ($taxonomySlugs as $taxonomy) {
    if (!taxonomy_exists($taxonomy)) {
        continue;
    }

    // Get all terms
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'fields' => 'ids',
    ]);

    if (is_array($terms)) {
        foreach ($terms as $termId) {
            wp_delete_term($termId, $taxonomy);
        }
    }

    // Unregister taxonomy
    unregister_taxonomy($taxonomy);
}

// Clean up term relationships (remove orphaned data)
global $wpdb;

// Remove term taxonomy entries for our taxonomies
$taxonomyList = "'" . implode("','", array_map('esc_sql', $taxonomySlugs)) . "'";
$wpdb->query(
    "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

// Remove orphaned term_relationships
$wpdb->query(
    "DELETE tr FROM {$wpdb->term_relationships} tr
    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    WHERE tt.term_taxonomy_id IS NULL"
);

// Remove orphaned terms
$wpdb->query(
    "DELETE t FROM {$wpdb->terms} t
    LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    WHERE tt.term_id IS NULL"
);

// Note: We do NOT delete products, only the custom taxonomies and their terms
// Products remain intact in WooCommerce
