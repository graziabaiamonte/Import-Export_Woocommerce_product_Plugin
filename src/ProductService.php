<?php
declare(strict_types=1);

namespace WooExcelImporter;

use WC_Product_Simple;


// Gestisce tutte le operazioni CRUD sui prodotti WooCommerce (crea, leggi, aggiorna).
final class ProductService
{
    private TaxonomyService $taxonomyService;

    // Costruttore della classe: viene eseguito automaticamente quando si crea un'istanza con "new ProductService(...)".
    public function __construct(TaxonomyService $taxonomyService)
    {
        // Salva il TaxonomyService ricevuto come parametro nella proprietà dell'istanza.
        $this->taxonomyService = $taxonomyService;
    }


    // Il "?" davanti a WC_Product_Simple indica che può restituire null se il prodotto non esiste.
    // Usato nell'import per capire se il prodotto va creato o aggiornato.
    public function findProductBySku(string $sku): ?WC_Product_Simple
    {
        // Restituisce un intero (l'ID del post WordPress) oppure 0 se lo SKU non esiste nel database.
        $productId = wc_get_product_id_by_sku($sku);

        if (!$productId) {
            return null;
        }

        // wc_get_product() è una funzione WooCommerce che restituisce l'oggetto prodotto appropriato
        $product = wc_get_product($productId);

        if (!$product instanceof WC_Product_Simple) {
            return null;
        }

        return $product;
    }

    // Crea un nuovo prodotto WooCommerce partendo dai dati dell'array $data.
    // L'array $data viene tipicamente costruito a partire da una riga del file Excel durante l'import.
    public function createProduct(array $data): WC_Product_Simple
    {
        $product = new WC_Product_Simple();

        // Chiama il metodo che popola il prodotto con i dati dell'array (SKU, nome, prezzo, ecc.).
        // Il "false" come terzo argomento disabilita l'assegnazione automatica delle tassonomie in questa fase
        // (vengono gestite separatamente dopo il salvataggio per garantire che l'ID prodotto esista già).
        $this->updateProductData($product, $data, false);

        // Salva il prodotto nel database WordPress.
        $product->save();

        
        // Le tassonomie richiedono l'ID del prodotto per essere collegate nel database.
        // Controlla se nell'array dei dati esiste la chiave "taxonomies" ed è un array.
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            $this->assignTaxonomies($product->get_id(), $data['taxonomies']);
        }

        // Restituisce il prodotto ora con ID e tassonomie assegnate.
        return $product;
    }

    // Metodo pubblico che aggiorna un prodotto WooCommerce già esistente con nuovi dati.
    // Non restituisce nulla (void): modifica l'oggetto e salva direttamente nel database.
    public function updateProduct(WC_Product_Simple $product, array $data): void
    {
        $this->updateProductData($product, $data, false);
        $product->save();

        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            
            // Assegna le tassonomie aggiornate al prodotto. Se un termine era già assegnato,
            // TaxonomyService gestirà l'aggiornamento senza creare duplicati.
            $this->assignTaxonomies($product->get_id(), $data['taxonomies']);
        }
    }


    // Traduce le colonne del foglio Excel nei campi WooCommerce.
    // Il parametro $assignTaxonomies (default true) è un'opzione legacy non usata attivamente nella versione corrente.
    private function updateProductData(WC_Product_Simple $product, array $data, bool $assignTaxonomies = true): void
    {
        if (isset($data['sku'])) {
            $product->set_sku(sanitize_text_field($data['sku']));
        }

        if (isset($data['name'])) {
            $product->set_name(sanitize_text_field($data['name']));
        }

        if (isset($data['description'])) {
            
            // wp_kses_post() è più permissiva: mantiene l'HTML sicuro (grassetto, link, immagini)
            $product->set_description(wp_kses_post($data['description']));
        }

        if (isset($data['price'])) {
            $price = $this->sanitizePrice($data['price']);
            $product->set_regular_price($price);
            $product->set_price($price);
        }
    }

    // Metodo privato che normalizza una stringa prezzo in un formato numerico standard.
    private function sanitizePrice(string $price): string
    {
        // Sostituisce tutte le virgole con il punto.
        $price = str_replace(',', '.', $price);

        // Rimuove dal prezzo tutti i caratteri che non sono cifre o punti.
        $price = preg_replace('/[^0-9.]/', '', $price);

        // Converte la stringa in float, poi la formatta con esattamente 2 cifre decimali.
        return number_format((float) $price, 2, '.', '');
    }

    // Collega le tassonomie custom a un prodotto.
    private function assignTaxonomies(int $productId, array $taxonomies): void
    {
        foreach ($taxonomies as $taxonomySlug => $termName) {
            if (empty($termName)) {
                continue;
            }

            $this->taxonomyService->assignTermToProduct($productId, $taxonomySlug, $termName);
        }
    }

    // Recupera tutti i prodotti semplici pubblicati nel negozio WooCommerce.
    public function getAllProducts(): array
    {
        // Definisce i criteri di ricerca per la query WooCommerce come array associativo.
        // Ogni chiave corrisponde a un parametro di filtraggio supportato da wc_get_products().
        $args = [
            // limit = -1 significa "nessun limite": recupera tutti i prodotti trovati senza paginazione.
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
        if (empty(trim($sku))) {
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $sku)) {
            return false;
        }

        if (strlen($sku) > 100) {
            return false;
        }

        return true;
    }

    // Restituisce true se il prezzo è valido (numero non negativo), false altrimenti.
    public function validatePrice(string $price): bool
    {
        if (empty(trim($price))) {
            return false;
        }

        // Sostituisce la virgola con il punto per renderlo compatibile
        $sanitized = str_replace(',', '.', $price);

        if (!is_numeric($sanitized)) {
            return false;
        }

        if ((float) $sanitized < 0) {
            return false;
        }
        return true;
    }
}


// Perché le tassonomie vengono assegnate DOPO save()? Perché i termini WordPress (wp_set_object_terms) richiedono che il prodotto abbia già un ID nel database. Un prodotto appena creato con new WC_Product_Simple() non ha ancora un ID — lo ottiene solo dopo $product->save().