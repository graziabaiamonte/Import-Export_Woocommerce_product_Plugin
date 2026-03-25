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

    // Scarica il file .xlsx direttamente come download HTTP
    public function exportAllProducts(): void
    {
        // Istanzia ProductService passandogli TaxonomyService (che a sua volta usa TaxonomyRegistrar): costruisce la catena di dipendenze necessaria per recuperare i prodotti WooCommerce con le loro tassonomie
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

        // Genera il nome del file con timestamp per rendere identificabile la data dell'export
        $filename = $this->generateFilename();

        // Invia il file Excel al browser come download
        $this->excelWriter->outputToDownload($spreadsheet, $filename);
    }

    // Genera il nome del file di export
    private function generateFilename(): string
    {
        $timestamp = gmdate('Y-m-d_H-i-s');
        return sprintf('woocommerce-products-export_%s.xlsx', $timestamp);
    }

}
