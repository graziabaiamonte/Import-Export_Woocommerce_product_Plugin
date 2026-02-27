<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class ImportReport
{
    private int $productsCreated = 0;
    private int $productsUpdated = 0;
    private int $termsCreated = 0;
    private int $rowsIgnored = 0;
    private array $errors = [];
    private array $ignoredRows = [];
    private array $createdTerms = [];

    public function incrementProductsCreated(): void
    {
        $this->productsCreated++;
    }

    public function incrementProductsUpdated(): void
    {
        $this->productsUpdated++;
    }

    public function incrementTermsCreated(string $termName, string $taxonomySlug): void
    {
        $this->termsCreated++;
        $this->createdTerms[] = [
            'term' => $termName,
            'taxonomy' => $taxonomySlug,
        ];
    }

    public function addIgnoredRow(int $rowNumber, string $reason, string $sku = ''): void
    {
        $this->rowsIgnored++;
        $this->ignoredRows[] = [
            'row' => $rowNumber,
            'sku' => $sku,
            'reason' => $reason,
        ];
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    public function getProductsCreated(): int
    {
        return $this->productsCreated;
    }

    public function getProductsUpdated(): int
    {
        return $this->productsUpdated;
    }

    public function getTermsCreated(): int
    {
        return $this->termsCreated;
    }

    public function getRowsIgnored(): int
    {
        return $this->rowsIgnored;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getIgnoredRows(): array
    {
        return $this->ignoredRows;
    }

    public function getCreatedTerms(): array
    {
        return $this->createdTerms;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function isSuccessful(): bool
    {
        return !$this->hasErrors() && ($this->productsCreated > 0 || $this->productsUpdated > 0);
    }

    public function getTotalProcessed(): int
    {
        return $this->productsCreated + $this->productsUpdated;
    }

    public function getSummary(): array
    {
        return [
            'products_created' => $this->productsCreated,
            'products_updated' => $this->productsUpdated,
            'terms_created' => $this->termsCreated,
            'rows_ignored' => $this->rowsIgnored,
            'total_processed' => $this->getTotalProcessed(),
            'has_errors' => $this->hasErrors(),
            'is_successful' => $this->isSuccessful(),
        ];
    }
}
