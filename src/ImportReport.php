<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class ImportReport
{
    // Contatore dei prodotti WooCommerce creati ex-novo durante l'import:
    // viene incrementato ogni volta che un prodotto non esistente viene inserito nel database.
    private int $productsCreated = 0;

    private int $productsUpdated = 0;

    private int $termsCreated = 0;

    private int $rowsIgnored = 0;

    // Array che raccoglie i messaggi di errore critici verificatisi durante l'import:
    private array $errors = [];

    // permette di mostrare all'utente quali righe non sono state importate e perché.
    private array $ignoredRows = [];

    // utile per il riepilogo finale, così l'utente sa quali nuovi valori sono stati aggiunti al catalogo.
    private array $createdTerms = [];

    // Incrementa di 1 il contatore dei prodotti creati:
    // viene chiamato da ImportService ogni volta che un nuovo prodotto WooCommerce viene inserito.
    public function incrementProductsCreated(): void
    {
        $this->productsCreated++;
    }

    // Metodo pubblico che incrementa di 1 il contatore dei prodotti aggiornati:
    // viene chiamato da ImportService ogni volta che un prodotto esistente viene sovrascritto con i dati del file Excel.
    public function incrementProductsUpdated(): void
    {
        // Incrementa il contatore dei prodotti aggiornati di 1 ad ogni chiamata.
        $this->productsUpdated++;
    }

    // Metodo pubblico che incrementa il contatore dei termini creati e registra il dettaglio del termine
    public function incrementTermsCreated(string $termName, string $taxonomySlug): void
    {
        // Incrementa il contatore dei termini di tassonomia creati di 1.
        $this->termsCreated++;

        // Aggiunge un elemento associativo all'array $createdTerms con i dati del termine appena creato:
        $this->createdTerms[] = [
            'term' => $termName,
            'taxonomy' => $taxonomySlug,
        ];
    }

    // Registra una riga ignorata con i relativi dettagli:
    // accetta il numero di riga (per localizzarla nel file), il motivo dell'esclusione,
    // e opzionalmente lo SKU.
    public function addIgnoredRow(int $rowNumber, string $reason, string $sku = ''): void
    {
        $this->rowsIgnored++;

        // Aggiunge in coda all'array $ignoredRows un array associativo con i dati della riga ignorata:
        $this->ignoredRows[] = [
            'row' => $rowNumber,
            'sku' => $sku,
            'reason' => $reason,
        ];
    }

    // Metodo pubblico che aggiunge un messaggio di errore critico all'array degli errori:
    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }

    // Restituisce il numero di prodotti creati durante l'import:
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
        //se $errors non è vuoto (ci sono errori), il metodo restituisce true.
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
