<?php

declare(strict_types=1);

namespace WooExcelImporter;

/**
 * TaxonomyConfig - Configuration file for all known taxonomies.
 * 
 * This file centralizes all taxonomy definitions, making it easy to:
 * - Add new taxonomies
 * - Update existing taxonomies
 * - Maintain consistency across the plugin
 * 
 * Each taxonomy has:
 * - slug: URL-safe identifier (max 32 chars, lowercase, alphanumeric + underscore)
 * - hierarchical: true for category-like, false for tag-like
 * - label: Human-readable name shown in admin
 */
final class TaxonomyConfig
{
    /**
     * Get all known taxonomies configuration.
     * 
     * @return array<string, array{slug: string, hierarchical: bool, label: string}>
     */
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
            'CATEGORY' => [
                'slug' => 'product_category',
                'hierarchical' => true,
                'label' => 'Category',
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

    /**
     * Get taxonomy configuration by column name.
     * 
     * @param string $columnName The Excel column name (e.g., 'QUANTITY PER BOX')
     * @return array{slug: string, hierarchical: bool, label: string}|null
     */
    public static function getTaxonomyByColumnName(string $columnName): ?array
    {
        $taxonomies = self::getKnownTaxonomies();
        return $taxonomies[$columnName] ?? null;
    }

    /**
     * Get all taxonomy slugs.
     * 
     * @return array<string>
     */
    public static function getAllSlugs(): array
    {
        return array_column(self::getKnownTaxonomies(), 'slug');
    }

    /**
     * Get all column names (Excel headers).
     * 
     * @return array<string>
     */
    public static function getAllColumnNames(): array
    {
        return array_keys(self::getKnownTaxonomies());
    }

    /**
     * Check if a column name is a known taxonomy.
     * 
     * @param string $columnName The Excel column name
     * @return bool
     */
    public static function isKnownTaxonomy(string $columnName): bool
    {
        return isset(self::getKnownTaxonomies()[$columnName]);
    }

    /**
     * Get the slug for a column name.
     * 
     * @param string $columnName The Excel column name
     * @return string|null
     */
    public static function getSlugByColumnName(string $columnName): ?string
    {
        $taxonomy = self::getTaxonomyByColumnName($columnName);
        return $taxonomy['slug'] ?? null;
    }

    /**
     * Get the column name for a slug.
     * 
     * @param string $slug The taxonomy slug
     * @return string|null
     */
    public static function getColumnNameBySlug(string $slug): ?string
    {
        foreach (self::getKnownTaxonomies() as $columnName => $config) {
            if ($config['slug'] === $slug) {
                return $columnName;
            }
        }
        return null;
    }
}
