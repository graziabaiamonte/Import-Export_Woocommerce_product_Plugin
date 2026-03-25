<?php

declare(strict_types=1);

namespace WooExcelImporter;

/**
 * TaxonomyConfig - Configuration class for all known taxonomies.
 *
 * This file centralizes all taxonomy definitions, making it easy to:
 * - Add new taxonomies
 * - Update existing taxonomies
 * - Maintain consistency across the plugin
 */

// final e static indicano che questa classe non è pensata per essere istanziata. È un punto di accesso globale a dati di configurazione.
//
// Grazie a static, qualsiasi altra classe può consultare la configurazione senza dipendenze(es.TaxonomyConfig::getAllSlugs();). Se i metodi non fossero static, ogni classe che ne ha bisogno dovrebbe ricevere un'istanza di TaxonomyConfig come dipendenza — inutile complessità per dati che non cambiano mai.
//
final class TaxonomyConfig
{
    public static function getKnownTaxonomies(): array
    {
        return [

            'QUANTITY PER BOX' => [
                'slug' => 'quantity_per_box',
                'hierarchical' => false,
                'label' => 'Quantity Per Box',
            ],

            'DISPOSABLE/REUSABLE' => [
                'slug' => 'disposable_reusable',
                'hierarchical' => false,
                'label' => 'Disposable/Reusable',
            ],

            // Corrisponde alla categoria nativa di WooCommerce (product_cat).
            // Senza questa entry, il plugin non saprebbe che la colonna Excel CATEGORY corrisponde alla tassonomia WooCommerce product_cat, e la ignorerebbe.
            'CATEGORY' => [
                'slug' => 'product_cat',
                'hierarchical' => true,
                'label' => 'Category',
                'native' => true,
            ],

            'STEEL & TITANIUM INSTRUMENTS FAMILIES' => [
                'slug' => 'steel_titanium_instruments',
                'hierarchical' => false,
                'label' => 'Steel & Titanium Instruments Families',
            ],

            'BACKFLUSH TYPE' => [
                'slug' => 'backflush_type',
                'hierarchical' => false,
                'label' => 'Backflush Type',
            ],

            'BACKFLUSH TIP' => [
                'slug' => 'backflush_tip',
                'hierarchical' => false,
                'label' => 'Backflush Tip',
            ],

            'BYPASS TYPE' => [
                'slug' => 'bypass_type',
                'hierarchical' => false,
                'label' => 'Bypass Type',
            ],

            'CHANDELIERS TYPE' => [
                'slug' => 'chandeliers_type',
                'hierarchical' => false,
                'label' => 'Chandeliers Type',
            ],

            'DOSAGE' => [
                'slug' => 'dosage',
                'hierarchical' => false,
                'label' => 'Dosage',
            ],

            'PACKAGING' => [
                'slug' => 'packaging',
                'hierarchical' => false,
                'label' => 'Packaging',
            ],

            'GAS TYPE' => [
                'slug' => 'gas_type',
                'hierarchical' => false,
                'label' => 'Gas Type',
            ],

            'GAUGE' => [
                'slug' => 'gauge',
                'hierarchical' => false,
                'label' => 'Gauge',
            ],

            'ILLUMINATION CONNECTOR FOR:' => [
                'slug' => 'illumination_connector_for',
                'hierarchical' => false,
                'label' => 'Illumination Connector For',
            ],

            'ILLUMINATION TYPE' => [
                'slug' => 'illumination_type',
                'hierarchical' => false,
                'label' => 'Illumination Type',
            ],

            'KNIVES & BLADES' => [
                'slug' => 'knives_blades',
                'hierarchical' => false,
                'label' => 'Knives & Blades',
            ],

            'LASER CONNECTOR' => [
                'slug' => 'laser_connector',
                'hierarchical' => false,
                'label' => 'Laser Connector',
            ],

            'LASER FIBER' => [
                'slug' => 'laser_fiber',
                'hierarchical' => false,
                'label' => 'Laser Fiber',
            ],

            'MIXING RATIO' => [
                'slug' => 'mixing_ratio',
                'hierarchical' => false,
                'label' => 'Mixing Ratio',
            ],

            'PIC TYPE' => [
                'slug' => 'pic_type',
                'hierarchical' => false,
                'label' => 'PIC Type',
            ],

            'TIP ANGLE' => [
                'slug' => 'tip_angle',
                'hierarchical' => false,
                'label' => 'Tip Angle',
            ],

            'TIP TYPE' => [
                'slug' => 'tip_type',
                'hierarchical' => false,
                'label' => 'Tip Type',
            ],

            'TUBING TYPE' => [
                'slug' => 'tubing_type',
                'hierarchical' => false,
                'label' => 'Tubing Type',
            ],

            'TWEEZER' => [
                'slug' => 'tweezer',
                'hierarchical' => false,
                'label' => 'Tweezer',
            ],

            'TWEEZER TYPE' => [
                'slug' => 'tweezer_type',
                'hierarchical' => false,
                'label' => 'Tweezer Type',
            ],

            'USE FOR' => [
                'slug' => 'use_for',
                'hierarchical' => false,
                'label' => 'Use For',
            ],

            '% NaCl' => [
                'slug' => 'nacl_percentage',
                'hierarchical' => false,
                'label' => '% NaCl',
            ],
        ];
    }

    // Restituisce l'intera configurazione della tassonomia corrispondente oppure null se non esiste.
    public static function getTaxonomyByColumnName(string $columnName): ?array
    {
        $taxonomies = self::getKnownTaxonomies();
        return $taxonomies[$columnName] ?? null;
    }


    public static function getAllSlugs(): array
    {
        // array_column estrae tutti i valori della chiave 'slug' dall'array
        // In PHP, questa funzione è equivalente a fare un foreach e raccogliere $item['slug'] per ogni elemento.
        return array_column(self::getKnownTaxonomies(), 'slug');
    }

    public static function getAllColumnNames(): array
    {
        return array_keys(self::getKnownTaxonomies());
    }

    public static function isKnownTaxonomy(string $columnName): bool
    {
        return isset(self::getKnownTaxonomies()[$columnName]);
    }

    public static function getSlugByColumnName(string $columnName): ?string
    {
        $taxonomy = self::getTaxonomyByColumnName($columnName);
        return $taxonomy['slug'] ?? null;
    }

    public static function getColumnNameBySlug(string $slug): ?string
    {
        // $config è l'array di configurazione associato (slug, hierarchical, label).
        foreach (self::getKnownTaxonomies() as $columnName => $config) {
            if ($config['slug'] === $slug) {
                return $columnName;
            }
        }
        return null;
    }
}
