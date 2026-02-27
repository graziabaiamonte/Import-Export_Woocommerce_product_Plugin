<?php
/**
 * Script di test per verificare l'assegnazione delle tassonomie
 * 
 * Questo script può essere eseguito da WP-CLI o incluso in un file di test
 * per verificare che le tassonomie vengano assegnate correttamente ai prodotti
 */

// Questo script va eseguito nel contesto WordPress
// Esempio: wp eval-file test-taxonomy-assignment.php

if (!defined('ABSPATH')) {
    echo "Questo script deve essere eseguito nel contesto WordPress\n";
    echo "Usa: wp eval-file test-taxonomy-assignment.php\n";
    exit;
}

echo "=== Test Assegnazione Tassonomie ===\n\n";

// Trova un prodotto di test
$products = wc_get_products([
    'limit' => 1,
    'status' => 'publish',
]);

if (empty($products)) {
    echo "❌ Nessun prodotto trovato per il test\n";
    exit;
}

$product = $products[0];
$product_id = $product->get_id();

echo "✓ Prodotto trovato:\n";
echo "  ID: {$product_id}\n";
echo "  Nome: {$product->get_name()}\n";
echo "  SKU: {$product->get_sku()}\n\n";

// Recupera tutte le tassonomie registrate dal plugin
$taxonomy_registrar = new WooExcelImporter\TaxonomyRegistrar();
$taxonomy_slugs = $taxonomy_registrar->getAllTaxonomySlugs();

echo "=== Tassonomie Registrate ===\n";
foreach ($taxonomy_slugs as $slug) {
    $exists = taxonomy_exists($slug);
    $status = $exists ? "✓" : "❌";
    echo "{$status} {$slug}\n";
}
echo "\n";

// Verifica i termini assegnati
echo "=== Termini Assegnati al Prodotto ===\n";
$has_terms = false;

foreach ($taxonomy_slugs as $slug) {
    $terms = wp_get_post_terms($product_id, $slug);
    
    if (!is_wp_error($terms) && !empty($terms)) {
        $has_terms = true;
        $column_name = $taxonomy_registrar->getColumnNameBySlug($slug);
        echo "✓ {$column_name} ({$slug}):\n";
        foreach ($terms as $term) {
            echo "  - {$term->name}\n";
        }
    }
}

if (!$has_terms) {
    echo "⚠️  Nessun termine assegnato a questo prodotto\n";
}

echo "\n=== Test Assegnazione Manuale ===\n";

// Test di assegnazione manuale
$test_taxonomy = 'gauge';
$test_term = 'Test 21G';

echo "Tentativo di assegnare '{$test_term}' alla tassonomia '{$test_taxonomy}'...\n";

$taxonomy_service = new WooExcelImporter\TaxonomyService($taxonomy_registrar);
$result = $taxonomy_service->assignTermToProduct($product_id, $test_taxonomy, $test_term);

if ($result) {
    echo "✓ Termine assegnato con successo\n";
    
    // Verifica
    $terms = wp_get_post_terms($product_id, $test_taxonomy);
    if (!is_wp_error($terms) && !empty($terms)) {
        echo "✓ Verifica: termine presente\n";
        foreach ($terms as $term) {
            echo "  - {$term->name}\n";
        }
    } else {
        echo "❌ Verifica fallita: termine non trovato\n";
    }
} else {
    echo "❌ Assegnazione fallita\n";
}

echo "\n=== Test Completato ===\n";
