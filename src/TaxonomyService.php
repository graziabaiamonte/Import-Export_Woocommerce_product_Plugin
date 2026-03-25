<?php

declare(strict_types=1);

namespace WooExcelImporter;

// Centralizza tutta la logica di assegnazione e gestione delle tassonomie WooCommerce ai prodotti importati.
final class TaxonomyService
{
    private TaxonomyRegistrar $taxonomyRegistrar;

    // Costruttore della classe: riceve un oggetto TaxonomyRegistrar tramite dependency injection.
    public function __construct(TaxonomyRegistrar $taxonomyRegistrar)
    {
        // Salva il riferimento all'oggetto TaxonomyRegistrar nella proprietà della classe, rendendolo disponibile a tutti i metodi interni.
        $this->taxonomyRegistrar = $taxonomyRegistrar;
    }

    // Assegna un termine (es. "Rosso", "XL") a un prodotto WooCommerce dato il suo ID, lo slug della tassonomia e il nome del termine. Restituisce true se l'operazione riesce, false in caso di errore.
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
        
        // is_wp_error() è una funzione WordPress che verifica se il valore restituito è un oggetto WP_Error, indicando che l'operazione precedente è fallita (es. errore database durante la creazione del termine).
        if (is_wp_error($term)) {
            return false;
        }

        // Ricava l'ID numerico del termine: se $term è un array (formato restituito da wp_insert_term), legge la chiave 'term_id'; altrimenti accede alla proprietà dell'oggetto WP_Term. 
        $termId = is_array($term) ? (int) $term['term_id'] : (int) $term->term_id;

        // Assegna il termine al prodotto WooCommerce tramite la funzione WordPress wp_set_object_terms(). Il parametro "false" come quarto argomento indica che i termini esistenti verranno SOSTITUITI (non aggiunti), garantendo un solo termine per tassonomia per ogni prodotto importato.
        $result = wp_set_object_terms($productId, [$termId], $taxonomySlug, false);

        if (is_wp_error($result)) {
            return false;
        }

        // Pulisce la cache di WordPress relativa ai termini del prodotto. Senza questa chiamata, le API di WordPress potrebbero restituire dati obsoleti dalla cache interna subito dopo l'import, rendendo i termini non visibili immediatamente.
        clean_object_term_cache($productId, 'product');

        return true;
    }

    // Recupera un termine esistente nella tassonomia cercandolo per nome, oppure lo crea se non trovato. Restituisce un oggetto WP_Term, un WP_Error o un array se il termine è stato appena creato.
    public function getOrCreateTerm(string $termName, string $taxonomySlug): \WP_Term|\WP_Error|array
    {
        // Cerca il termine nella tassonomia usando get_term_by() di WordPress. Restituisce un oggetto WP_Term se trovato, false altrimenti. Questo evita di creare duplicati durante l'importazione di prodotti con la stessa categoria/attributo.
        $existingTerm = get_term_by('name', $termName, $taxonomySlug);

        // Verifica con instanceof se il risultato è effettivamente un oggetto WP_Term (e non false o null).
        if ($existingTerm instanceof \WP_Term) {
            
            // Il termine esiste già: lo restituisce direttamente senza crearne uno nuovo
            return $existingTerm;
        }

        return wp_insert_term($termName, $taxonomySlug);
    }

    // Metodo pubblico per rimuovere tutti i termini di una specifica tassonomia da un prodotto. Utile durante l'import per azzerare le assegnazioni precedenti prima di applicare i nuovi valori dal file Excel.
    public function clearProductTaxonomy(int $productId, string $taxonomySlug): void
    {
        if (taxonomy_exists($taxonomySlug)) {
            
            // Passa un array vuoto [] a wp_set_object_terms() con append=false: questo rimuove tutti i termini della tassonomia indicata dal prodotto, senza restituire alcun valore (la funzione è dichiarata void).
            wp_set_object_terms($productId, [], $taxonomySlug, false);
        }
    }

    // Metodo pubblico per leggere il nome del primo termine assegnato a un prodotto per una data tassonomia. Viene usato durante l'export per recuperare il valore da scrivere nelle celle del file Excel.
    public function getProductTermName(int $productId, string $taxonomySlug): string
    {
        // Recupera tutti i termini assegnati al prodotto per la tassonomia indicata, chiedendo solo i nomi ('fields' => 'names'). Restituisce un array di stringhe o un WP_Error.
        $terms = wp_get_post_terms($productId, $taxonomySlug, ['fields' => 'names']);

        if (is_wp_error($terms) || empty($terms)) {
            
            // Nessun termine trovato o errore: restituisce stringa vuota come valore di default sicuro per la cella Excel.
            return '';
        }

        return (string) $terms[0];
    }

    public function validateTaxonomyExists(string $taxonomySlug): bool
    {
        return taxonomy_exists($taxonomySlug);
    }
}



// TaxonomyService e TaxonomyRegistrar >>> Perché due classi separate? 
//
// TaxonomyRegistrar conosce la struttura di WordPress (come registrare una tassonomia), TaxonomyService conosce le operazioni sui dati (come assegnare termini). Tenere queste responsabilità separate rende ogni classe più piccola e leggibile.