<?php

declare(strict_types=1);

namespace WooExcelImporter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use WC_Product_Simple;

final class ExcelWriter
{
    private TaxonomyRegistrar $taxonomyRegistrar;

    private const REQUIRED_HEADERS = [
        'SKU',
        'TITLE',
        'DESCRIPTION',
        'PRICE',
    ];

    public function __construct(TaxonomyRegistrar $taxonomyRegistrar)
    {
        $this->taxonomyRegistrar = $taxonomyRegistrar;
    }

    public function createExcel(array $products): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->writeHeaders($sheet);
        $this->writeProducts($sheet, $products);

        return $spreadsheet;
    }

    private function writeHeaders($sheet): void
    {
        $column = 1;
        $headers = array_merge(self::REQUIRED_HEADERS, $this->taxonomyRegistrar->getAllColumnNames());

        foreach ($headers as $header) {
            $cellCoord = $this->getColumnLetter($column) . '1';
            $sheet->setCellValue($cellCoord, $header . ';');
            $column++;
        }
    }

    private function writeProducts($sheet, array $products): void
    {
        $row = 2;
        
        foreach ($products as $product) {
            if (!$product instanceof WC_Product_Simple) {
                continue;
            }

            $this->writeProduct($sheet, $row, $product);
            $row++;
        }
    }

    private function writeProduct($sheet, int $row, WC_Product_Simple $product): void
    {
        $column = 1;

        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_sku());

        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_name());

        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_description());

        $cellCoord = $this->getColumnLetter($column++) . $row;
        $sheet->setCellValue($cellCoord, $product->get_regular_price());

        foreach ($this->taxonomyRegistrar->getAllColumnNames() as $taxonomyColumnName) {
            $taxonomySlug = $this->taxonomyRegistrar->getTaxonomySlug($taxonomyColumnName);
            
            if ($taxonomySlug === null) {
                $cellCoord = $this->getColumnLetter($column++) . $row;
                $sheet->setCellValue($cellCoord, '');
                continue;
            }

            $termName = $this->getProductTermName($product->get_id(), $taxonomySlug);
            $cellCoord = $this->getColumnLetter($column++) . $row;
            $sheet->setCellValue($cellCoord, $termName);
        }
    }

    private function getProductTermName(int $productId, string $taxonomySlug): string
    {
        $terms = wp_get_post_terms($productId, $taxonomySlug, ['fields' => 'names']);

        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        return (string) $terms[0];
    }

    public function saveToFile(Spreadsheet $spreadsheet, string $filePath): void
    {
        try {
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);
        } catch (WriterException $e) {
            throw new \RuntimeException('Failed to save Excel file: ' . $e->getMessage());
        }
    }

    public function outputToDownload(Spreadsheet $spreadsheet, string $filename): void
    {
        $safeFilename = sanitize_file_name($filename);
        
        if (!headers_sent()) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $safeFilename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');
        }

        try {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        } catch (WriterException $e) {
            throw new \RuntimeException('Failed to output Excel file: ' . $e->getMessage());
        }

        exit;
    }

    public function getHeaders(): array
    {
        return array_merge(self::REQUIRED_HEADERS, $this->taxonomyRegistrar->getAllColumnNames());
    }

    private function getColumnLetter(int $columnIndex): string
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)) . $letter;
            $columnIndex = (int) ($columnIndex / 26);
        }
        return $letter;
    }
}
