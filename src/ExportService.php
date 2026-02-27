<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class ExportService
{
    private ExcelWriter $excelWriter;

    public function __construct(ExcelWriter $excelWriter)
    {
        $this->excelWriter = $excelWriter;
    }

    public function exportAllProducts(): void
    {
        $productService = new ProductService(
            new TaxonomyService(
                new TaxonomyRegistrar()
            )
        );

        $products = $productService->getAllProducts();

        if (empty($products)) {
            throw new \RuntimeException('No products found to export');
        }

        $spreadsheet = $this->excelWriter->createExcel($products);

        $filename = $this->generateFilename();

        $this->excelWriter->outputToDownload($spreadsheet, $filename);
    }

    private function generateFilename(): string
    {
        $timestamp = gmdate('Y-m-d_H-i-s');
        return sprintf('woocommerce-products-export_%s.xlsx', $timestamp);
    }

    public function exportToFile(string $filePath): void
    {
        $productService = new ProductService(
            new TaxonomyService(
                new TaxonomyRegistrar()
            )
        );

        $products = $productService->getAllProducts();

        if (empty($products)) {
            throw new \RuntimeException('No products found to export');
        }

        $spreadsheet = $this->excelWriter->createExcel($products);

        $this->excelWriter->saveToFile($spreadsheet, $filePath);
    }
}
