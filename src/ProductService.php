<?php

declare(strict_types=1);

namespace WooExcelImporter;

use WC_Product_Simple;

final class ProductService
{
    private TaxonomyService $taxonomyService;

    public function __construct(TaxonomyService $taxonomyService)
    {
        $this->taxonomyService = $taxonomyService;
    }

    public function findProductBySku(string $sku): ?WC_Product_Simple
    {
        $productId = wc_get_product_id_by_sku($sku);
        
        if (!$productId) {
            return null;
        }

        $product = wc_get_product($productId);

        if (!$product instanceof WC_Product_Simple) {
            return null;
        }

        return $product;
    }

    public function createProduct(array $data): WC_Product_Simple
    {
        $product = new WC_Product_Simple();
        
        $this->updateProductData($product, $data, false);
        
        $product->save();
        
        // Assign taxonomies AFTER saving the product
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            $this->assignTaxonomies($product->get_id(), $data['taxonomies']);
        }

        return $product;
    }

    public function updateProduct(WC_Product_Simple $product, array $data): void
    {
        $this->updateProductData($product, $data, false);
        
        $product->save();
        
        // Assign taxonomies AFTER saving the product
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            $this->assignTaxonomies($product->get_id(), $data['taxonomies']);
        }
    }

    private function updateProductData(WC_Product_Simple $product, array $data, bool $assignTaxonomies = true): void
    {
        if (isset($data['sku'])) {
            $product->set_sku(sanitize_text_field($data['sku']));
        }

        if (isset($data['name'])) {
            $product->set_name(sanitize_text_field($data['name']));
        }

        if (isset($data['description'])) {
            $product->set_description(wp_kses_post($data['description']));
        }

        if (isset($data['price'])) {
            $price = $this->sanitizePrice($data['price']);
            $product->set_regular_price($price);
            $product->set_price($price);
        }
    }

    private function sanitizePrice(string $price): string
    {
        $price = str_replace(',', '.', $price);
        $price = preg_replace('/[^0-9.]/', '', $price);
        
        return number_format((float) $price, 2, '.', '');
    }

    private function assignTaxonomies(int $productId, array $taxonomies): void
    {
        foreach ($taxonomies as $taxonomySlug => $termName) {
            if (empty($termName)) {
                continue;
            }

            $this->taxonomyService->assignTermToProduct($productId, $taxonomySlug, $termName);
        }
    }

    public function getAllProducts(): array
    {
        $args = [
            'limit' => -1,
            'type' => 'simple',
            'status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ];

        return wc_get_products($args);
    }

    public function validateSku(string $sku): bool
    {
        // Check not empty
        if (empty(trim($sku))) {
            return false;
        }
        
        // Check only contains allowed characters (alphanumeric, dash, underscore, dot)
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $sku)) {
            return false;
        }
        
        // Check reasonable length (max 100 characters)
        if (strlen($sku) > 100) {
            return false;
        }
        
        return true;
    }

    public function validatePrice(string $price): bool
    {
        // Must not be empty
        if (empty(trim($price))) {
            return false;
        }
        
        // Replace comma with dot
        $sanitized = str_replace(',', '.', $price);
        
        // Must be numeric
        if (!is_numeric($sanitized)) {
            return false;
        }
        
        // Must be non-negative
        if ((float) $sanitized < 0) {
            return false;
        }
        
        return true;
    }
}
