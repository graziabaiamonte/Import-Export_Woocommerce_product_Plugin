<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class TaxonomyRegistrar
{
    use SecureFormHandler;

    private const MAX_SLUG_LENGTH = 32;

    // Registra tutte le tassonomie e aggancia le azioni di WordPress.
    public function register(): void
    {
        foreach ($this->getAllTaxonomies() as $config) {
            $this->registerTaxonomy($config);
        }

        // Aggancia il metodo saveProductTaxonomies all'hook save_post_product con priorità 10 e 2 argomenti.
        // `add_action` è la funzione WordPress per registrare callback su eventi
        add_action('save_post_product', [$this, 'saveProductTaxonomies'], 10, 2);

        // JS che converte i checkbox nativi WP in radio button
        add_action('admin_enqueue_scripts', [$this, 'enqueueProductEditorAssets']);

        // Filtro: rimuove le nostre tassonomie dai dropdown automatici WP
        // nella barra di filtro sopra la lista prodotti
        // manage_taxonomies_for_product_columns controlla quali tassonomie mostrano un dropdown di filtro nella lista prodotti.
        add_filter('manage_taxonomies_for_product_columns', [$this, 'suppressNativeTaxonomyDropdowns']);

        // Filtri custom del plugin nella lista prodotti
        // restrict_manage_posts si attiva nella barra sopra la lista dei prodotti per aggiungere controlli di filtro personalizzati.
        add_action('restrict_manage_posts', [$this, 'addProductFilters'], 20);
        
        // Aggancia applyProductFilters all'hook pre_get_posts per modificare la query principale prima che venga eseguita.
        // pre_get_posts permette di alterare i parametri di WP_Query prima che WordPress recuperi i post dal database.
        add_action('pre_get_posts', [$this, 'applyProductFilters']);
    }

    public function registerOnActivation(): void
    {
        // Ogni $config è un array associativo con 'slug', 'label', 'hierarchical', ecc.
        foreach ($this->getAllTaxonomies() as $config) {
            $slug = $config['slug'];

            if (taxonomy_exists($slug) && $this->isTaxonomyConnectedToProduct($slug)) {
                continue;
            }

            $this->registerTaxonomy($config);
        }
    }

    private function isTaxonomyConnectedToProduct(string $taxonomy): bool
    {
        if (!taxonomy_exists($taxonomy)) {
            return false;
        }

        // Recupera l'oggetto tassonomia da WordPress con tutte le sue proprietà (label, object_type, ecc.).
        // `get_taxonomy()` restituisce un oggetto WP_Taxonomy oppure false se la tassonomia non esiste.
        $tax_object = get_taxonomy($taxonomy);

        if (!$tax_object || empty($tax_object->object_type)) {
            return false;
        }

        // cerca se 'product' è nell'array dei post type associati alla tassonomia.
        // Il parametro true abilita la strict comparison (=== invece di ==)
        return in_array('product', $tax_object->object_type, true);
    }

    /**
     * Clean orphaned terms (terms not associated with any product).
     * Called on plugin deactivation to keep database clean.
     */
    public function cleanOrphanedTerms(): void
    {
        foreach ($this->getAllTaxonomies() as $config) {
            $taxonomy = $config['slug'];

            if (!empty($config['native'])) {
                continue;
            }

            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            // Get all terms for this taxonomy
            $terms = get_terms([
                
                // Specifica su quale tassonomia fare la query dei termini.
                'taxonomy' => $taxonomy,
                
                // false: recupera anche i termini con count=0, cioè non associati ad alcun post.
                'hide_empty' => false,
                
                // Restituisce solo gli ID dei termini (array di interi) invece degli oggetti WP_Term completi.
                'fields' => 'ids',
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            // Check each term and delete if not associated with any product
            // `$terms` è un array di interi (ID) grazie a `fields => 'ids'` nella query precedente.
            foreach ($terms as $term_id) {
                
                // Conta quanti prodotti sono associati al termine 
                $count = $this->getTermProductCount($term_id, $taxonomy);

                // If term has no products, delete it
                if ($count === 0) {
                    wp_delete_term($term_id, $taxonomy);
                }
            }
        }
    }

    private function getTermProductCount(int $term_id, string $taxonomy): int
    {
        // Definisce i parametri per la WP_Query che cerca prodotti associati al termine specificato.
        $args = [
            'post_type' => 'product',
            'post_status' => 'any',
            // Recupera al massimo 1 prodotto: serve solo sapere se ne esiste almeno uno, non quanti.
            'posts_per_page' => 1,
            'fields' => 'ids',
            // Definisce il filtro per tassonomia: cerca solo i prodotti con il termine specificato.
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    // `field => 'term_id'` istruisce WordPress a cercare per ID invece che per slug o nome.
                    'field' => 'term_id',
                    // Il valore del termine da cercare: l'ID passato come argomento al metodo.
                    'terms' => $term_id,
                ],
            ],
        ];

        // Esegue la query WordPress
        $query = new \WP_Query($args);
        // Restituisce il numero totale di prodotti trovati 
        return $query->found_posts;
    }

    private function getAllTaxonomies(): array
    {
        // Unisce l'array delle tassonomie predefinite (da TaxonomyConfig) con quelle custom (da wp_options).
        return array_merge(TaxonomyConfig::getKnownTaxonomies(), $this->getCustomTaxonomies());
    }

    private function getKnownTaxonomies(): array
    {
        return TaxonomyConfig::getKnownTaxonomies();
    }

    // Metodo privato che recupera le tassonomie custom create dall'utente tramite l'interfaccia del plugin.
    private function getCustomTaxonomies(): array
    {
        // Recupera l'opzione 'woo_excel_importer_custom_taxonomies' dal database
        // `get_option()` legge dalla tabella wp_options di WordPress usando la chiave specificata.
        $custom = get_option('woo_excel_importer_custom_taxonomies', []);
        // Restituisce l'opzione solo se è un array, altrimenti un array vuoto come fallback sicuro.
        return is_array($custom) ? $custom : [];
    }

    // Salva l'array di tassonomie custom nel database WordPress.
    // Viene chiamato ogni volta che l'utente aggiunge o rimuove una tassonomia custom dall'interfaccia del plugin. (funzioanlità non usata >> elimina)
    private function saveCustomTaxonomies(array $customTaxonomies): void
    {
        // `update_option(..., false)` aggiorna il valore in wp_options; false = non caricare l'opzione ad ogni richiesta (autoload disabilitato).
        update_option('woo_excel_importer_custom_taxonomies', $customTaxonomies, false);
    }

    // Registra una singola tassonomia in WordPress usando il suo array di configurazione.
    private function registerTaxonomy(array $config): void
    {
        if (!empty($config['native'])) {
            return;
        }

        if (taxonomy_exists($config['slug'])) {
            // Collega una tassonomia già esistente al post type 'product' senza ri-registrarla da zero.
            register_taxonomy_for_object_type($config['slug'], 'product');
            return;
        }

        $label = $config['label'];

        // Definisce l'array di argomenti per register_taxonomy()
        $args = [
            'label'              => $label,
            // Array di etichette localizzate per ogni azione (cerca, modifica, aggiungi, ecc.).
            'labels'             => $this->generateLabels($label),
            'hierarchical'       => $config['hierarchical'],
           
            // nasconde la tassonomia dalle query pubbliche e dagli URL del sito.
            'public'             => false,
        
            // impedisce che i termini generino pagine archivio pubbliche.
            'publicly_queryable' => false,
            
            'show_ui'            => true,
            
            // Specifica sotto quale menu di WordPress compare la gestione dei termini della tassonomia.
            'show_in_menu'       => 'edit.php?post_type=product',
            
            // false: evita i dropdown di filtro automatici nella lista prodotti
            'show_admin_column'  => false,
           
            // false: la tassonomia non compare nei menu di navigazione costruiti tramite Aspetto > Menu.
            'show_in_nav_menus'  => false,
            'show_tagcloud'      => false,
            'show_in_rest'       => true,
            
            // `rewrite => false` disabilita la generazione di regole di riscrittura degli URL per la tassonomia.
            'rewrite'            => false,
            'query_var'          => false,
         
            // sostituisce il metabox tag (input libero) con quello categoria (checkbox).
            'meta_box_cb'        => 'post_categories_meta_box',
        ];

        register_taxonomy($config['slug'], ['product'], $args);
    }

    // Genera l'array di etichette per una tassonomia a partire dal nome singolare.
    private function generateLabels(string $singular): array
    {
        return [
            'name'              => $singular,
            'singular_name'     => $singular,
            'search_items'      => 'Cerca ' . $singular,
            'all_items'         => 'Tutti: ' . $singular,
            'edit_item'         => 'Modifica ' . $singular,
            'update_item'       => 'Aggiorna ' . $singular,
            'add_new_item'      => 'Aggiungi ' . $singular,
            'new_item_name'     => 'Nuovo: ' . $singular,
            'menu_name'         => $singular,
        ];
    }

    // Il parametro $hook è la stringa identificativa della pagina admin corrente (es. 'post.php', 'edit.php').
    public function enqueueProductEditorAssets(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        // get_current_screen() è una funzione WP che restituisce un oggetto con informazioni sulla pagina admin dove ti trovi in quel momento. 
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }

        $slugs = [];
        foreach ($this->getAllTaxonomies() as $config) {
            $slugs[] = $config['slug'];
        }

        // Registra e carica il file JavaScript dell'admin nel browser dell'utente.
        wp_enqueue_script(
            'woo-excel-taxonomy-radio',
            
            // URL assoluto del file JS
            WOO_EXCEL_IMPORTER_URL . 'assets/admin.js',
            
            // Array di dipendenze
            ['jquery'],
            
            // Versione dello script: aggiunta come query string (?ver=X.X) per forzare il refresh della cache.
            WOO_EXCEL_IMPORTER_VERSION,
           
            //carica lo script nel footer della pagina invece che nell'head.
            true
        );

        // Passa i dati PHP al JavaScript rendendoli disponibili come variabile globale nel browser.
        // `wp_localize_script()` è il modo corretto in WordPress di passare dati dal server al client.
        wp_localize_script(
            
            // Handle dello script a cui agganciare i dati
            'woo-excel-taxonomy-radio',
           
            // Nome della variabile JavaScript globale creata nel browser (es. window.wooExcelTaxonomies)
            'wooExcelTaxonomies',
            
            // Array PHP convertito automaticamente in oggetto JSON e assegnato alla variabile JS.
            // `'slugs' => $slugs` diventa `wooExcelTaxonomies.slugs = [...]` nel browser.
            ['slugs' => $slugs]
        );
    }

    /**
     * Impone max 1 termine per tassonomia custom al salvataggio del prodotto.
     */
 
    public function saveProductTaxonomies(int $postId, \WP_Post $post): void
    {
        if ($post->post_type !== 'product') {
            return;
        }

        // Controlla che WordPress non stia eseguendo un salvataggio automatico.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // Controlla che $_POST['tax_input'] esista e sia un array prima di accedervi.
        if (empty($_POST['tax_input']) || !is_array($_POST['tax_input'])) {
            return;
        }

        foreach ($this->getAllTaxonomies() as $config) {
            $taxonomy = $config['slug'];

            // Se questa tassonomia non è stata inviata nel form, passa alla successiva.
            if (!isset($_POST['tax_input'][$taxonomy])) {
                continue;
            }

            $submitted = $_POST['tax_input'][$taxonomy];

            // Normalizza in array di interi
            if (!is_array($submitted)) {
                $submitted = array_filter([(int) $submitted]);
            } else {
                // Converte tutti gli elementi dell'array in interi, filtra i falsy e reindex l'array.
                $submitted = array_values(array_filter(array_map('intval', $submitted)));
            }

            // Tronca a massimo 1 termine
            if (count($submitted) > 1) {
                // Sovrascrive $_POST['tax_input'][$taxonomy] con solo il primo termine selezionato.
                $_POST['tax_input'][$taxonomy] = [$submitted[0]];
            }
        }
    }

    // Usato dall'ImportService per mappare le colonne del file Excel alle tassonomie WordPress.
    public function getTaxonomySlug(string $columnName): ?string
    {
        $allTaxonomies = $this->getAllTaxonomies();

        // Controlla se esiste una tassonomia registrata per il nome colonna specificato.
        if (!isset($allTaxonomies[$columnName])) {
            return null;
        }

        return $allTaxonomies[$columnName]['slug'];
    }

    // Restituisce un array di tutti gli slug delle tassonomie registrate.
    public function getAllTaxonomySlugs(): array
    {
        return array_column($this->getAllTaxonomies(), 'slug');
    }

    public function getColumnNameBySlug(string $slug): ?string
    {
        foreach ($this->getAllTaxonomies() as $columnName => $config) {
            if ($config['slug'] === $slug) {
                return $columnName;
            }
        }
        return null;
    }

    public function getAllColumnNames(): array
    {
        // Restituisce le chiavi dell'array delle tassonomie = nomi colonne Excel.
        return array_keys($this->getAllTaxonomies());
    }

    // Verifica che una colonna sia una tassonomia consentita e ne restituisce lo slug.
    public function ensureTaxonomyForColumn(string $columnName): string
    {
        $allTaxonomies = $this->getAllTaxonomies();

        if (isset($allTaxonomies[$columnName])) {
            return $allTaxonomies[$columnName]['slug'];
        }

        throw new \RuntimeException(
            sprintf(
                'Taxonomy column "%s" is not allowed. Only registered taxonomies are permitted.',
                $columnName
            )
        );
    }

    private function generateUniqueSlug(string $columnName, array $allTaxonomies): string
    {
        // Genera uno slug base dal nome colonna sanitizzandolo (solo caratteri validi, minuscolo, ecc.).
        $base = self::sanitizeSlug($columnName);

        // Se la sanitizzazione produce una stringa vuota, usa 'taxonomy' come fallback.
        if ($base === '') {
            // Assegna il valore di fallback 'taxonomy' come base per lo slug.
            $base = 'taxonomy';
        }

        // Estrae tutti gli slug esistenti come array per verificare le collisioni.
        $existingSlugs = array_column($allTaxonomies, 'slug');
        
        $slug = $base;
       
        // Inizializza il contatore dei suffissi numerici a 2 (es. 'brand_2', 'brand_3', ecc.).
        $suffix = 2;

        // Continua ad aggiungere suffissi numerici finché lo slug non è univoco.
        while (in_array($slug, $existingSlugs, true)) {
            
            // Costruisce il testo del suffisso numerico da aggiungere allo slug (es. '_2', '_3').
            $suffixText = '_' . $suffix;
            
            // Calcola la lunghezza massima della parte base per rispettare MAX_SLUG_LENGTH con il suffisso.
            $maxBaseLength = self::MAX_SLUG_LENGTH - strlen($suffixText);
           
            // Tronca la base alla lunghezza massima e aggiunge il suffisso numerico.
            // `substr($str, 0, $len)` in PHP estrae i primi $len caratteri di $str.
            $slug = substr($base, 0, $maxBaseLength) . $suffixText;
            
            $suffix++;
        }
        return $slug;
    }

    /**
     * Rimuove le tassonomie del plugin dai dropdown di filtro automatici
     * che WordPress aggiunge sopra la lista prodotti.
     */
    public function suppressNativeTaxonomyDropdowns(array $taxonomies): array
    {
        $ourSlugs = [];
        foreach ($this->getAllTaxonomies() as $config) {
            if (empty($config['native'])) {
                $ourSlugs[] = $config['slug'];
            }
        }

        // Rimuove gli slug del plugin dall'array passato dal filtro
        // `array_diff()` restituisce gli elementi del primo array non presenti nel secondo.
        // `array_values()` reindexizza l'array risultante da 0 (rimuove i gap negli indici).
        return array_values(array_diff($taxonomies, $ourSlugs));
    }

    // Genera l'HTML del pannello filtri a scomparsa con checkbox per ogni tassonomia.
    public function addProductFilters(): void
    {
        // Accede alla variabile globale WordPress che contiene il post type della pagina corrente.
        global $typenow;

        if ($typenow !== 'product') {
            return;
        }

        $selectedFilters = [];
        
        // Controlla se ci sono filtri attivi nell'URL (es. ?woo_excel_tax_filter[taxonomy][]=1).
        if (isset($_GET['woo_excel_tax_filter']) && is_array($_GET['woo_excel_tax_filter'])) {
            // Assegna i filtri dall'URL all'array locale per usarli nel rendering del form.
            $selectedFilters = $_GET['woo_excel_tax_filter'];
        }

        // Inizializza il contatore totale dei filtri attivi.
        $totalActiveFilters = 0;
        
        // Scorre i filtri attivi per contare il totale dei termini selezionati tra tutte le tassonomie.
        foreach ($selectedFilters as $taxFilters) {
            if (is_array($taxFilters)) {
                $totalActiveFilters += count($taxFilters);
            }
        }

        // <details> che funge da pannello a scomparsa.
        echo '<details class="woo-excel-tax-filter">';
        echo '<summary>';
        echo esc_html__('Filtra per tassonomie', 'woo-excel-importer');
        
        if ($totalActiveFilters > 0) {
            echo '<span class="filter-count">' . esc_html($totalActiveFilters) . '</span>';
        }

        echo '<span class="toggle-indicator" aria-hidden="true"></span>';
        echo '</summary>';
        echo '<div class="filter-content">';
        echo '<div class="filter-header">';
        echo '<h4>' . esc_html__('Seleziona tassonomie', 'woo-excel-importer') . '</h4>';
        echo '<div class="filter-actions">';
        
        // Stampa il pulsante "Seleziona tutto" con JavaScript inline per selezionare tutti i checkbox.
        // `onclick` con `querySelectorAll` e `forEach` seleziona tutti i checkbox del form filtri.
        echo '<button type="button" class="filter-btn" onclick="document.querySelectorAll(\'#posts-filter .woo-excel-tax-filter input[type=checkbox]\').forEach(cb => cb.checked = true)">' . esc_html__('Seleziona tutto', 'woo-excel-importer') . '</button>';
       
        echo '<button type="button" class="filter-btn" onclick="document.querySelectorAll(\'#posts-filter .woo-excel-tax-filter input[type=checkbox]\').forEach(cb => cb.checked = false)">' . esc_html__('Deseleziona tutto', 'woo-excel-importer') . '</button>';
        echo '</div>';
        echo '</div>';

        // Flag booleano che traccia se almeno una tassonomia con termini è stata renderizzata.
        // Usato dopo il ciclo per mostrare il messaggio "nessuna tassonomia disponibile" se necessario.
        $hasVisibleTaxonomies = false;

        foreach ($this->getAllTaxonomies() as $config) {
            $taxonomy = $config['slug'];
            
            // Recupera l'oggetto WP_Taxonomy per accedere alle sue proprietà (label, ecc.).
            $tax = get_taxonomy($taxonomy);

            // Salta le tassonomie non registrate in WordPress
            if (!$tax) {
                continue;
            }

            // Recupera tutti i termini della tassonomia che hanno almeno un prodotto associato.
            $terms = get_terms([
                
                // Specifica la tassonomia su cui fare la query dei termini.
                // Corrisponde al parametro taxonomy di WP_Term_Query.
                'taxonomy' => $taxonomy,

                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);

            // Salta le tassonomie senza termini o con errori nella query.
            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            // Segna che almeno una tassonomia con termini è stata trovata: non mostrare lo stato vuoto.
            // Questo flag viene controllato dopo il ciclo per decidere se mostrare il messaggio "nessuna tassonomia".
            $hasVisibleTaxonomies = true;

            // Inizializza l'array degli ID termine selezionati.
            $selectedForTax = [];
            
            // Controlla se ci sono filtri selezionati per questa specifica tassonomia nell'URL.
            if (isset($selectedFilters[$taxonomy]) && is_array($selectedFilters[$taxonomy])) {
                $selectedForTax = array_map('intval', $selectedFilters[$taxonomy]);
            }

            $termsCount = count($terms);

            // Conta il numero di termini selezionati per questa tassonomia (per eventuale uso futuro).
            // Attualmente non usato direttamente nell'HTML ma utile per badge o logica aggiuntiva.
            $selectedCount = count($selectedForTax);

            // Apre il div del gruppo di una singola tassonomia
            // Ogni tassonomia ha il suo div.tax-group che raggruppa label e checkbox dei suoi termini.
            echo '<div class="tax-group">';
            echo '<div class="tax-label">';
            echo '<span class="tax-label-text">';
            echo '<span class="dashicons dashicons-category tax-label-icon"></span>';
            
            // Stampa il nome/etichetta della tassonomia recuperato dall'oggetto WP_Taxonomy.
            echo esc_html($tax->label);
            
            echo '</span>';
            
            // Stampa il contatore del numero di termini disponibili per questa tassonomia.
            echo '<span class="tax-count">' . esc_html($termsCount) . ' ' . esc_html__('termini', 'woo-excel-importer') . '</span>';
            
            echo '</div>';
            echo '<div class="checkbox-wrapper">';

            // Scorre ogni termine della tassonomia per generare il suo checkbox.
            foreach ($terms as $term) {
                // Estrae l'ID del termine come intero per uso sicuro nell'HTML e nel confronto.
                $termId = (int) $term->term_id;
                $checked = in_array($termId, $selectedForTax, true) ? 'checked' : '';

                echo '<label class="checkbox-label">';
                
                // Genera il checkbox con name per l'array POST e value uguale all'ID del termine.
                echo '<input type="checkbox" name="woo_excel_tax_filter[' . esc_attr($taxonomy) . '][]" value="' . esc_attr((string) $termId) . '" ' . $checked . ' />';
                // Stampa il nome del termine accanto al checkbox
                echo '<span class="checkbox-label-text">' . esc_html($term->name) . '</span>';
                echo '</label>';
            }
            echo '</div>';
            echo '</div>';
        }

        // Mostra stato vuoto se non ci sono tassonomie
        if (!$hasVisibleTaxonomies) {
            echo '<div class="empty-state">';
            echo '<div class="empty-state-icon">📋</div>';
            echo '<div class="empty-state-text">' . esc_html__('Nessuna tassonomia disponibile per il filtraggio.', 'woo-excel-importer') . '</div>';
            echo '</div>';
        }

        // Footer con info e pulsante applica
        echo '<div class="filter-footer">';
        echo '<span class="filter-info">';

        if ($totalActiveFilters > 0) {
     
            // `_n()` in WordPress restituisce la stringa singolare o plurale in base al numero.
            echo sprintf(
                esc_html(_n('%d filtro attivo', '%d filtri attivi', $totalActiveFilters, 'woo-excel-importer')),
                // Sostituisce %d nella stringa con il numero totale di filtri attivi.

                $totalActiveFilters
            );
        } else {
            echo esc_html__('Nessun filtro attivo', 'woo-excel-importer');
        }
        echo '</span>';
       
        echo '<div>';
        // Mostra il link "Cancella filtri" solo se ci sono filtri attivi da rimuovere.
        if ($totalActiveFilters > 0) {
            
            // rimuove il parametro specificato dall'URL corrente di WordPress.
            $clearUrl = remove_query_arg('woo_excel_tax_filter');
            
            echo '<a href="' . esc_url($clearUrl) . '" class="filter-clear">' . esc_html__('Cancella filtri', 'woo-excel-importer') . '</a> ';
        }

        echo '<button type="submit" class="filter-apply">' . esc_html__('Applica filtri', 'woo-excel-importer') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</details>';
    }

    // Modifica la query principale per filtrare per tassonomia.
    public function applyProductFilters($query): void
    {
        // Verifica che siamo nell'area admin E che la query sia quella principale della pagina.
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'product') {
            return;
        }

        // Verifica che ci siano filtri di tassonomia nell'URL GET e che siano un array.
        if (!isset($_GET['woo_excel_tax_filter']) || !is_array($_GET['woo_excel_tax_filter'])) {
            return;
        }

        // Recupera i filtri dall'URL GET come array 
        // `$_GET['woo_excel_tax_filter']` è l'array annidato ['slug' => [id1, id2], ...] dall'URL.
        $requested = $_GET['woo_excel_tax_filter'];
        
        // Recupera tutti gli slug delle tassonomie consentite per validare i filtri ricevuti.
        // `array_column()` estrae la colonna 'slug' come array piatto per confronti rapidi.
        $allowedSlugs = array_column($this->getAllTaxonomies(), 'slug');

        $taxQuery = [];

        // Scorre solo gli slug consentiti per costruire la tax_query.
        foreach ($allowedSlugs as $taxonomy) {
            
            // Controlla che la tassonomia sia presente nei filtri richiesti e che sia un array.
            if (!isset($requested[$taxonomy]) || !is_array($requested[$taxonomy])) {
                continue;
            }

            // Converte i valori in interi, filtra quelli non positivi e reindicizza l'array.
            $termIds = array_values(array_filter(array_map('intval', $requested[$taxonomy]), static fn($v) => $v > 0));

            // Se dopo la pulizia non rimangono ID validi, salta questa tassonomia.
            if (empty($termIds)) {
                continue;
            }

            $taxQuery[] = [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $termIds,
                'operator' => 'IN',
            ];
        }

        // Applica la tax_query alla WP_Query solo se ci sono filtri validi da applicare.
        // `!empty($taxQuery)` verifica che almeno una clausola di filtro sia stata costruita.
        if (!empty($taxQuery)) {
            // Se ci sono più filtri, aggiunge la relazione AND tra le clausole di tassonomia.
            if (count($taxQuery) > 1) {
                $taxQuery = array_merge(['relation' => 'AND'], $taxQuery);
            }

            // Imposta la tax_query nella WP_Query principale per filtrare i prodotti.
            $query->set('tax_query', $taxQuery);
        }
    }

    public static function sanitizeSlug(string $text): string
    {
        $slug = strtolower($text);
        
        // Sostituisce tutti i caratteri non permessi negli slug con underscore.
        // [^a-z0-9_\-] corrisponde a qualsiasi carattere non alfanumerico/underscore/trattino.
        $slug = preg_replace('/[^a-z0-9_\-]/', '_', $slug);
        
        // Riduce sequenze consecutive di underscore a un singolo underscore.
        $slug = preg_replace('/_+/', '_', $slug);
       
        // Rimuove gli underscore iniziali e finali dallo slug.
        $slug = trim($slug, '_');

        // Se lo slug supera la lunghezza massima consentita, lo tronca..
        if (strlen($slug) > self::MAX_SLUG_LENGTH) {
            
            // `substr($str, 0, $len)` estrae i primi $len caratteri dalla stringa.
            $slug = substr($slug, 0, self::MAX_SLUG_LENGTH);
            
            // Rimuove eventuali underscore finali rimasti dopo il troncamento.
            $slug = rtrim($slug, '_');
        }
        return $slug;
    }
}
