<?php
/**
 * Questo file viene eseguito automaticamente da WordPress quando il plugin viene disinstallato
 * Il suo scopo è ripulire il database da tutti i dati creati dal plugin:
 * tassonomie custom, termini e relazioni.
 */

declare(strict_types=1);

// "WP_UNINSTALL_PLUGIN" è una costante che WordPress definisce SOLO quando sta eseguendo
// una disinstallazione legittima. Se qualcuno accede direttamente a questo file via browser
// (es. example.com/wp-content/plugins/mio-plugin/uninstall.php), la costante non esiste
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// "delete_option()" è una funzione WP che rimuove una riga dalla tabella "wp_options"
// del database.
delete_option('woo_excel_importer_custom_taxonomies');

// Array contenente tutti gli slug delle tassonomie custom sia nella versione attuale (con underscore "_") sia nelle versioni precedenti (con trattino "-"), per garantire la pulizia anche su installazioni vecchie.
$taxonomySlugs = [
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

// rende accessibile l'oggetto globale di WordPress per interagire
// direttamente col database. "$wpdb" è un'istanza della classe "wpdb" di WordPress.
// È necessario usarlo direttamente perché al momento della disinstallazione le tassonomie
// non sono più registrate in WordPress, quindi le funzioni ad alto livello (come get_terms())
// non funzionerebbero — il plugin è già disattivato.
global $wpdb;

// "WP_CONTENT_DIR" è una costante PHP definita da WordPress che contiene il percorso
// assoluto sul server della cartella "wp-content" (es. /var/www/html/wp-content)
$log_file = WP_CONTENT_DIR . '/woo-excel-uninstall.log';

// "file_put_contents()" è una funzione PHP nativa che scrive una stringa in un file.
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Starting uninstall\n", FILE_APPEND);

// Costruisce la lista degli slug formattata per una query SQL.
// "array_map('esc_sql', $taxonomySlugs)" applica la funzione "esc_sql" (escape per SQL,
// previene SQL injection) a ogni elemento dell'array
//
// "implode" unisce gli elementi dell'array con il separatore "',
// Il risultato finale è una stringa tipo: 'quantity_per_box','dosage','packaging',...
$taxonomyList = "'" . implode("','", array_map('esc_sql', $taxonomySlugs)) . "'";

// Scrive nel log la lista delle tassonomie che verranno eliminate.
// Le parentesi graffe "{}" dentro una stringa >> simile ai template literal di JS
file_put_contents($log_file, "Taxonomy list: {$taxonomyList}\n", FILE_APPEND);

// STEP 1 — Recupera gli ID delle righe in "wp_term_taxonomy" da eliminare.
// "$wpdb->get_col()" esegue una query SQL e restituisce un array con i valori
// della prima colonna di ogni riga trovata
$term_taxonomy_ids = $wpdb->get_col(
    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

file_put_contents($log_file, "Found " . count($term_taxonomy_ids) . " term_taxonomy entries\n", FILE_APPEND);

// STEP 2 — Elimina le relazioni tra prodotti e termini delle tassonomie custom.
if (!empty($term_taxonomy_ids)) {

    // "array_map('intval', $term_taxonomy_ids)" converte ogni ID in intero con "intval()"
    // Poi "implode(',', ...)" li unisce con virgola
    // ottenendo una stringa tipo: 1,2,3,4,5 da usare nella query SQL.
    $ids_list = implode(',', array_map('intval', $term_taxonomy_ids));

    // "$wpdb->query()" esegue una query SQL generica e restituisce il numero di righe
    // interessate (int). Qui elimina dalla tabella
    // "wp_term_relationships" tutte le righe che collegano prodotti alle tassonomie custom —
    // ovvero "scollega" i prodotti dai termini che stiamo per cancellare.
    $deleted_relationships = $wpdb->query(
        "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ({$ids_list})"
    );
    file_put_contents($log_file, "Deleted {$deleted_relationships} term relationships\n", FILE_APPEND);
}

// STEP 3 — Recupera gli ID dei termini prima di eliminare le righe di term_taxonomy.
$term_ids = $wpdb->get_col(
    "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

// STEP 4 — Elimina le righe dalla tabella "wp_term_taxonomy" per le tassonomie custom.
$deleted_taxonomies = $wpdb->query(
    "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ({$taxonomyList})"
);

file_put_contents($log_file, "Deleted {$deleted_taxonomies} term_taxonomy entries\n", FILE_APPEND);

// STEP 5 — Elimina i termini rimasti orfani (non più usati da nessuna tassonomia).
if (!empty($term_ids)) {
    foreach ($term_ids as $term_id) {

        // "$wpdb->get_var()" esegue una query e restituisce un singolo valore scalare
        // (la prima colonna della prima riga) — ideale per query tipo COUNT(*).
        // "$wpdb->prepare()" è fondamentale per la sicurezza: prepara la query
        // sostituendo i placeholder ("%d" = intero, "%s" = stringa) con i valori reali
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE term_id = %d",
                $term_id
            )
        );

        // Se COUNT(*) ha restituito 0, il termine non è più usato da nessuna tassonomia
        // e può essere eliminato in sicurezza. "== 0" in PHP è un confronto debole
        if ($count == 0) {

            // Primo parametro: nome tabella. Secondo: array associativo WHERE (colonna => valore).
            // Terzo: array con i formati dei valori ("%d" = intero) per prevenire SQL injection.
            // Elimina il termine dalla tabella "wp_terms" (nome e slug del termine).
            $wpdb->delete($wpdb->terms, ['term_id' => $term_id], ['%d']);

            $wpdb->delete($wpdb->termmeta, ['term_id' => $term_id], ['%d']);
        }
    }
}

// STEP 6 — Pulizia finale: elimina le relazioni orfane rimaste nel database.
//
// Una relazione è "orfana" se punta a un term_taxonomy_id che non esiste più in wp_term_taxonomy.
// "LEFT JOIN ... WHERE tt.term_taxonomy_id IS NULL" è il pattern SQL classico per trovare
// righe in una tabella che non hanno corrispondenza in un'altra.
// 
$wpdb->query(
    "DELETE tr FROM {$wpdb->term_relationships} tr
    LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
    WHERE tt.term_taxonomy_id IS NULL"
);

file_put_contents($log_file, date('Y-m-d H:i:s') . " - Uninstall completed\n", FILE_APPEND);

