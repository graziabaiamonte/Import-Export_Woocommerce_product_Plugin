<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class TaxonomyService
{
    private TaxonomyRegistrar $taxonomyRegistrar;

    public function __construct(TaxonomyRegistrar $taxonomyRegistrar)
    {
        $this->taxonomyRegistrar = $taxonomyRegistrar;
    }

    public function assignTermToProduct(int $productId, string $taxonomySlug, string $termName): bool
    {
        if (!taxonomy_exists($taxonomySlug)) {
            return false;
        }

        $termName = trim($termName);
        if (empty($termName)) {
            return false;
        }

        $term = $this->getOrCreateTerm($termName, $taxonomySlug);
        if (is_wp_error($term)) {
            return false;
        }

        $termId = is_array($term) ? (int) $term['term_id'] : (int) $term->term_id;

        // Use wp_set_object_terms with append = false to replace existing terms
        $result = wp_set_object_terms($productId, [$termId], $taxonomySlug, false);
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Clear cache to ensure terms are visible immediately
        clean_object_term_cache($productId, 'product');

        return true;
    }

    public function getOrCreateTerm(string $termName, string $taxonomySlug): \WP_Term|\WP_Error|array
    {
        $existingTerm = get_term_by('name', $termName, $taxonomySlug);
        
        if ($existingTerm instanceof \WP_Term) {
            return $existingTerm;
        }

        return wp_insert_term($termName, $taxonomySlug);
    }

    public function clearProductTaxonomy(int $productId, string $taxonomySlug): void
    {
        if (taxonomy_exists($taxonomySlug)) {
            wp_set_object_terms($productId, [], $taxonomySlug, false);
        }
    }

    public function getProductTermName(int $productId, string $taxonomySlug): string
    {
        $terms = wp_get_post_terms($productId, $taxonomySlug, ['fields' => 'names']);

        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        return (string) $terms[0];
    }

    public function validateTaxonomyExists(string $taxonomySlug): bool
    {
        return taxonomy_exists($taxonomySlug);
    }
}
