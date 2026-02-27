<?php

declare(strict_types=1);

namespace WooExcelImporter;

use WC_Product_Simple;

final class ImportService
{
    private ProductService $productService;
    private ExcelReader $excelReader;

    public function __construct(ProductService $productService, ExcelReader $excelReader)
    {
        $this->productService = $productService;
        $this->excelReader = $excelReader;
    }

    public function importFromFile(string $filePath): ImportReport
    {
        $report = new ImportReport();

        try {
            $rows = $this->excelReader->readFile($filePath);
        } catch (\RuntimeException $e) {
            $report->addError('Failed to read Excel file: ' . $e->getMessage());
            return $report;
        }

        if (empty($rows)) {
            $report->addError('No data rows found in Excel file');
            return $report;
        }

        $this->processRows($rows, $report);

        return $report;
    }

    private function processRows(array $rows, ImportReport $report): void
    {
        $rowNumber = 2;
        $processedSkus = [];

        foreach ($rows as $row) {
            // Check if row is completely empty
            $nonEmptyValues = array_filter($row, function($value) {
                return !empty(trim((string) $value));
            });
            
            if (empty($nonEmptyValues)) {
                $report->addIgnoredRow($rowNumber, 'Empty row - all cells are blank');
                $rowNumber++;
                continue;
            }

            $sku = $this->extractValue($row, 'SKU');

            if (!$this->productService->validateSku($sku)) {
                $sanitizedSku = $this->sanitizeSku($sku);
                if (empty($sanitizedSku)) {
                    $report->addIgnoredRow($rowNumber, 'SKU is empty or contains only invalid characters');
                } else {
                    $report->addIgnoredRow($rowNumber, 'Invalid SKU format (allowed: alphanumeric, dots, dashes, underscores, max 100 chars)', $sanitizedSku);
                }
                $rowNumber++;
                continue;
            }

            if (isset($processedSkus[$sku])) {
                $report->addIgnoredRow($rowNumber, 'Duplicate SKU in file (first occurrence at row ' . $processedSkus[$sku] . ')', $sku);
                $rowNumber++;
                continue;
            }

            $processedSkus[$sku] = $rowNumber;

            try {
                $this->processRow($row, $report);
            } catch (\Exception $e) {
                $report->addIgnoredRow($rowNumber, 'Error: ' . $e->getMessage(), $sku);
            }

            $rowNumber++;
        }
    }

    private function processRow(array $row, ImportReport $report): void
    {
        $sku = $this->sanitizeSku($this->extractValue($row, 'SKU'));
        $title = $this->extractValue($row, 'TITLE');
        $description = $this->extractValue($row, 'DESCRIPTION');
        $price = $this->sanitizePrice($this->extractValue($row, 'PRICE'));

        if (!$this->productService->validateSku($sku)) {
            throw new \RuntimeException('Invalid or empty SKU. SKU must contain only alphanumeric characters, dots, dashes, or underscores (max 100 characters)');
        }

        if (empty(trim($title))) {
            throw new \RuntimeException('Product title cannot be empty');
        }

        if (strlen($title) > 200) {
            throw new \RuntimeException('Product title is too long (max 200 characters)');
        }

        if (!$this->productService->validatePrice($price)) {
            throw new \RuntimeException('Invalid price format. Price must be a positive number (e.g., 10.50 or 10,50)');
        }

        $existingProduct = $this->productService->findProductBySku($sku);

        $taxonomies = $this->extractTaxonomies($row, $report);

        $productData = [
            'sku' => $sku,
            'name' => $title,
            'description' => $description,
            'price' => $price,
            'taxonomies' => $taxonomies,
        ];

        if ($existingProduct instanceof WC_Product_Simple) {
            $this->productService->updateProduct($existingProduct, $productData);
            $report->incrementProductsUpdated();
        } else {
            $this->productService->createProduct($productData);
            $report->incrementProductsCreated();
        }
    }

    private function extractTaxonomies(array $row, ImportReport $report): array
    {
        $taxonomies = [];
        $taxonomyRegistrar = new TaxonomyRegistrar();

        $requiredHeaders = $this->excelReader->getRequiredHeaders();
        $rowHeaders = array_keys($row);

        foreach ($rowHeaders as $columnName) {
            if (in_array($columnName, $requiredHeaders, true)) {
                continue;
            }

            if ($columnName === '') {
                continue;
            }

            $value = $this->extractValue($row, $columnName);

            if ($value === '') {
                continue;
            }

            // Validate term value format
            if (strlen($value) > 200) {
                throw new \RuntimeException(
                    sprintf('Taxonomy term "%s" for column "%s" is too long (max 200 characters)', 
                        substr($value, 0, 50) . '...', 
                        $columnName)
                );
            }

            // Check for dangerous characters or malformed input
            if (preg_match('/[<>"\']/', $value)) {
                throw new \RuntimeException(
                    sprintf('Taxonomy term "%s" for column "%s" contains invalid characters (< > " \' are not allowed)', 
                        $value, 
                        $columnName)
                );
            }

            try {
                $slug = $taxonomyRegistrar->ensureTaxonomyForColumn($columnName);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException(
                    sprintf('Invalid taxonomy column "%s": %s', $columnName, $e->getMessage())
                );
            }

            $taxonomies[$slug] = $value;

            if (!term_exists($value, $slug)) {
                $report->incrementTermsCreated($value, $slug);
            }
        }

        return $taxonomies;
    }

    private function extractValue(array $row, string $key): string
    {
        return isset($row[$key]) ? trim((string) $row[$key]) : '';
    }

    private function sanitizeSku(string $sku): string
    {
        // Remove leading/trailing whitespace
        $sku = trim($sku);
        
        // Remove any characters that are not alphanumeric, dash, underscore, or dot
        $sku = preg_replace('/[^a-zA-Z0-9._-]/', '', $sku);
        
        return $sku;
    }

    private function sanitizePrice(string $price): string
    {
        // Remove whitespace
        $price = trim($price);
        
        // Check for completely empty or non-numeric characters only
        if ($price === '' || preg_match('/^[^0-9.,]+$/', $price)) {
            return '';
        }
        
        // Replace comma with dot for decimal separator
        $price = str_replace(',', '.', $price);
        
        // Remove any non-numeric characters except dot
        $price = preg_replace('/[^0-9.]/', '', $price);
        
        // Ensure only one decimal point
        $parts = explode('.', $price);
        if (count($parts) > 2) {
            $price = $parts[0] . '.' . implode('', array_slice($parts, 1));
        }
        
        return $price;
    }

    public function validateUploadedFile(array $file): ?string
    {
        return $this->excelReader->validateFileUpload($file);
    }
}
