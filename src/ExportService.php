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
            // TaxonomyService gestisce le tassonomie personalizzate (categorie, tag, attributi); riceve TaxonomyRegistrar per sapere quali tassonomie sono registrate nel plugin
            new TaxonomyService(
                // TaxonomyRegistrar conosce le definizioni delle tassonomie custom registrate da questo plugin in WordPress
                new TaxonomyRegistrar()
            )
        );

        $products = $productService->getAllProducts();

        if (empty($products)) {
            throw new \RuntimeException('No products found to export');
        }

        // Crea il file Excel (oggetto Spreadsheet di PhpSpreadsheet)
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
