<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class TaxonomyRegistrar
{
    use SecureFormHandler;

    private const MAX_SLUG_LENGTH = 32;

    public function register(): void
    {
        $disabled = get_option('woo_excel_disabled_taxonomies', []);
        foreach ($this->getAllTaxonomies() as $config) {
            if (in_array($config['slug'], $disabled, true)) {
                continue;
            }
            $this->registerTaxonomy($config);
        }

        // Salvataggio: impone max 1 termine per tassonomia prima che WP salvi
        add_action('save_post_product', [$this, 'saveProductTaxonomies'], 10, 2);

        // JS che converte i checkbox nativi WP in radio button (pagina prodotto)
        add_action('admin_enqueue_scripts', [$this, 'enqueueProductEditorAssets']);

        // Filtro: rimuove le nostre tassonomie dai dropdown automatici WP/WooCommerce
        // nella barra di filtro sopra la lista prodotti
        add_filter('manage_taxonomies_for_product_columns', [$this, 'suppressNativeTaxonomyDropdowns']);

        // Filtri custom del plugin nella lista prodotti
        add_action('restrict_manage_posts', [$this, 'addProductFilters'], 20);
        add_action('pre_get_posts', [$this, 'applyProductFilters']);
    }

    /**
     * Register taxonomies on plugin activation.
     * Skips taxonomies that already exist and are connected to product post type.
     */
    public function registerOnActivation(): void
    {
        $disabled = get_option('woo_excel_disabled_taxonomies', []);
        foreach ($this->getAllTaxonomies() as $config) {
            $slug = $config['slug'];
            if (in_array($slug, $disabled, true)) {
                continue;
            }
            
            // Skip if taxonomy already exists and is connected to product
            if (taxonomy_exists($slug) && $this->isTaxonomyConnectedToProduct($slug)) {
                continue;
            }
            
            // Register or connect the taxonomy
            $this->registerTaxonomy($config);
        }
    }

    /**
     * Crea le categorie WooCommerce (product_cat) predefinite all'attivazione del plugin.
     * Le categorie già esistenti vengono saltate silenziosamente.
     */
    public function createDefaultWooCategories(): void
    {
        foreach (TaxonomyConfig::getKnownTaxonomies() as $columnName => $config) {
            if (empty($config['native']) || $config['slug'] !== 'product_cat') {
                continue;
            }

            // Nessuna categoria di default da pre-creare: le categorie WooCommerce
            // vengono create dinamicamente durante l'import dal file Excel.
            // Questo metodo è il punto di estensione se in futuro si volessero
            // creare categorie predefinite all'attivazione.
        }
    }

    /**
     * Check if a taxonomy is already connected to the product post type.
     */
    private function isTaxonomyConnectedToProduct(string $taxonomy): bool
    {
        if (!taxonomy_exists($taxonomy)) {
            return false;
        }

        $tax_object = get_taxonomy($taxonomy);
        
        if (!$tax_object || empty($tax_object->object_type)) {
            return false;
        }

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

            // Non toccare le tassonomie native WooCommerce (es. product_cat)
            if (!empty($config['native'])) {
                continue;
            }
            
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            // Get all terms for this taxonomy
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'fields' => 'ids',
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            // Check each term and delete if not associated with any product
            foreach ($terms as $term_id) {
                $count = $this->getTermProductCount($term_id, $taxonomy);
                
                // If term has no products, delete it
                if ($count === 0) {
                    wp_delete_term($term_id, $taxonomy);
                }
            }
        }
    }

    /**
     * Get the number of products associated with a term.
     * 
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy slug
     * @return int Number of products
     */
    private function getTermProductCount(int $term_id, string $taxonomy): int
    {
        $args = [
            'post_type' => 'product',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term_id,
                ],
            ],
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    private function getAllTaxonomies(): array
    {
        return array_merge(TaxonomyConfig::getKnownTaxonomies(), $this->getCustomTaxonomies());
    }

    private function getKnownTaxonomies(): array
    {
        return TaxonomyConfig::getKnownTaxonomies();
    }

    private function getCustomTaxonomies(): array
    {
        $custom = get_option('woo_excel_importer_custom_taxonomies', []);
        return is_array($custom) ? $custom : [];
    }

    private function saveCustomTaxonomies(array $customTaxonomies): void
    {
        update_option('woo_excel_importer_custom_taxonomies', $customTaxonomies, false);
    }

    private function registerTaxonomy(array $config): void
    {
        // Le tassonomie native WooCommerce (es. product_cat) non vanno registrate
        if (!empty($config['native'])) {
            return;
        }

        if (taxonomy_exists($config['slug'])) {
            register_taxonomy_for_object_type($config['slug'], 'product');
            return;
        }

        // Applica eventuale override di label salvato dall'utente
        $labelOverrides = get_option('woo_excel_taxonomy_label_overrides', []);
        $label = $labelOverrides[$config['slug']] ?? $config['label'];

        // Usa sempre il metabox gerarchico nativo di WP (checkbox con albero indentato).
        // Il JS nella pagina prodotto converte i checkbox in radio per imporre singola selezione.
        $args = [
            'label'              => $label,
            'labels'             => $this->generateLabels($label),
            'hierarchical'       => $config['hierarchical'],
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=product',
            'show_admin_column'  => false, // false: evita i dropdown di filtro automatici nella lista prodotti
            'show_in_nav_menus'  => false,
            'show_tagcloud'      => false,
            'show_in_rest'       => true,
            'rewrite'            => false,
            'query_var'          => false,
            'meta_box_cb'        => 'post_categories_meta_box',
        ];

        register_taxonomy($config['slug'], ['product'], $args);
    }

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

    /**
     * Carica il JS che converte i checkbox nativi WP in radio button
     * nella pagina di modifica prodotto.
     */
    public function enqueueProductEditorAssets(string $hook): void
    {
        // Solo nella pagina di creazione/modifica prodotto
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }

        // Raccoglie tutti gli slug (custom + nativi come product_cat) da passare al JS.
        // Anche product_cat deve essere convertito in radio per imporre singola selezione.
        $slugs = [];
        foreach ($this->getAllTaxonomies() as $config) {
            $slugs[] = $config['slug'];
        }

        wp_enqueue_script(
            'woo-excel-taxonomy-radio',
            WOO_EXCEL_IMPORTER_URL . 'assets/admin.js',
            ['jquery'],
            WOO_EXCEL_IMPORTER_VERSION,
            true
        );

        wp_localize_script(
            'woo-excel-taxonomy-radio',
            'wooExcelTaxonomies',
            ['slugs' => $slugs]
        );
    }

    /**
     * Impone max 1 termine per tassonomia custom al salvataggio del prodotto.
     * Intercetta i dati inviati dal form nativo WP (array $_POST['tax_input'])
     * e tronca a un solo termine prima che WordPress esegua il salvataggio reale.
     *
     * WordPress salva le tassonomie tramite wp_set_post_terms() chiamato da
     * wp_insert_post(); agendo su save_post con priorità bassa (5) possiamo
     * correggere $_POST prima che il ciclo nativo avvenga (priorità 10).
     */
    public function saveProductTaxonomies(int $postId, \WP_Post $post): void
    {
        if ($post->post_type !== 'product') {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        // $_POST['tax_input'] è l'array che WP usa per salvare i termini dai metabox nativi
        if (empty($_POST['tax_input']) || !is_array($_POST['tax_input'])) {
            return;
        }

        foreach ($this->getAllTaxonomies() as $config) {
            $taxonomy = $config['slug'];

            if (!isset($_POST['tax_input'][$taxonomy])) {
                continue;
            }

            $submitted = $_POST['tax_input'][$taxonomy];

            // Normalizza in array di interi
            if (!is_array($submitted)) {
                $submitted = array_filter([(int) $submitted]);
            } else {
                $submitted = array_values(array_filter(array_map('intval', $submitted)));
            }

            // Tronca a massimo 1 termine (prende il primo selezionato)
            if (count($submitted) > 1) {
                $_POST['tax_input'][$taxonomy] = [$submitted[0]];
            }
        }
    }




    public function getTaxonomySlug(string $columnName): ?string
    {
        $allTaxonomies = $this->getAllTaxonomies();

        if (!isset($allTaxonomies[$columnName])) {
            return null;
        }

        return $allTaxonomies[$columnName]['slug'];
    }

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
        return array_keys($this->getAllTaxonomies());
    }

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
        $base = self::sanitizeSlug($columnName);

        if ($base === '') {
            $base = 'taxonomy';
        }

        $existingSlugs = array_column($allTaxonomies, 'slug');
        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $existingSlugs, true)) {
            $suffixText = '_' . $suffix;
            $maxBaseLength = self::MAX_SLUG_LENGTH - strlen($suffixText);
            $slug = substr($base, 0, $maxBaseLength) . $suffixText;
            $suffix++;
        }

        return $slug;
    }



    /**
     * Rimuove le tassonomie del plugin dai dropdown di filtro automatici
     * che WordPress e WooCommerce aggiungono sopra la lista prodotti.
     *
     * @param string[] $taxonomies Array di slug tassonomia con show_admin_column=true.
     * @return string[] Array ripulito dagli slug del plugin.
     */
    public function suppressNativeTaxonomyDropdowns(array $taxonomies): array
    {
        $ourSlugs = [];
        foreach ($this->getAllTaxonomies() as $config) {
            if (empty($config['native'])) {
                $ourSlugs[] = $config['slug'];
            }
        }

        return array_values(array_diff($taxonomies, $ourSlugs));
    }

    public function addProductFilters(): void
    {
        global $typenow;

        if ($typenow !== 'product') {
            return;
        }

        $selectedFilters = [];
        if (isset($_GET['woo_excel_tax_filter']) && is_array($_GET['woo_excel_tax_filter'])) {
            $selectedFilters = $_GET['woo_excel_tax_filter'];
        }

        // Conta i filtri attivi totali
        $totalActiveFilters = 0;
        foreach ($selectedFilters as $taxFilters) {
            if (is_array($taxFilters)) {
                $totalActiveFilters += count($taxFilters);
            }
        }

        echo '<details class="woo-excel-tax-filter">';
        echo '<summary>';
        echo esc_html__('Filtra per tassonomie', 'woo-excel-importer');
        if ($totalActiveFilters > 0) {
            echo '<span class="filter-count">' . esc_html($totalActiveFilters) . '</span>';
        }
        echo '<span class="toggle-indicator" aria-hidden="true"></span>';
        echo '</summary>';
        
        echo '<div class="filter-content">';
        
        // Header con pulsanti azione
        echo '<div class="filter-header">';
        echo '<h4>' . esc_html__('Seleziona tassonomie', 'woo-excel-importer') . '</h4>';
        echo '<div class="filter-actions">';
        echo '<button type="button" class="filter-btn" onclick="document.querySelectorAll(\'#posts-filter .woo-excel-tax-filter input[type=checkbox]\').forEach(cb => cb.checked = true)">' . esc_html__('Seleziona tutto', 'woo-excel-importer') . '</button>';
        echo '<button type="button" class="filter-btn" onclick="document.querySelectorAll(\'#posts-filter .woo-excel-tax-filter input[type=checkbox]\').forEach(cb => cb.checked = false)">' . esc_html__('Deseleziona tutto', 'woo-excel-importer') . '</button>';
        echo '</div>';
        echo '</div>';

        $hasVisibleTaxonomies = false;

        foreach ($this->getAllTaxonomies() as $config) {
            $taxonomy = $config['slug'];
            $tax = get_taxonomy($taxonomy);

            if (!$tax) {
                continue;
            }

            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);

            if (empty($terms) || is_wp_error($terms)) {
                continue;
            }

            $hasVisibleTaxonomies = true;

            $selectedForTax = [];
            if (isset($selectedFilters[$taxonomy]) && is_array($selectedFilters[$taxonomy])) {
                $selectedForTax = array_map('intval', $selectedFilters[$taxonomy]);
            }

            $termsCount = count($terms);
            $selectedCount = count($selectedForTax);

            echo '<div class="tax-group">';
            echo '<div class="tax-label">';
            echo '<span class="tax-label-text">';
            echo '<span class="dashicons dashicons-category tax-label-icon"></span>';
            echo esc_html($tax->label);
            echo '</span>';
            echo '<span class="tax-count">' . esc_html($termsCount) . ' ' . esc_html__('termini', 'woo-excel-importer') . '</span>';
            echo '</div>';
            
            echo '<div class="checkbox-wrapper">';

            foreach ($terms as $term) {
                $termId = (int) $term->term_id;
                $checked = in_array($termId, $selectedForTax, true) ? 'checked' : '';

                echo '<label class="checkbox-label">';
                echo '<input type="checkbox" name="woo_excel_tax_filter[' . esc_attr($taxonomy) . '][]" value="' . esc_attr((string) $termId) . '" ' . $checked . ' />';
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
            echo sprintf(
                esc_html(_n('%d filtro attivo', '%d filtri attivi', $totalActiveFilters, 'woo-excel-importer')),
                $totalActiveFilters
            );
        } else {
            echo esc_html__('Nessun filtro attivo', 'woo-excel-importer');
        }
        echo '</span>';
        echo '<div>';
        if ($totalActiveFilters > 0) {
            $clearUrl = remove_query_arg('woo_excel_tax_filter');
            echo '<a href="' . esc_url($clearUrl) . '" class="filter-clear">' . esc_html__('Cancella filtri', 'woo-excel-importer') . '</a> ';
        }
        echo '<button type="submit" class="filter-apply">' . esc_html__('Applica filtri', 'woo-excel-importer') . '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</details>';
    }

    public function applyProductFilters($query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'product') {
            return;
        }

        if (!isset($_GET['woo_excel_tax_filter']) || !is_array($_GET['woo_excel_tax_filter'])) {
            return;
        }

        $requested = $_GET['woo_excel_tax_filter'];
        $allowedSlugs = array_column($this->getAllTaxonomies(), 'slug');

        $taxQuery = [];

        foreach ($allowedSlugs as $taxonomy) {
            if (!isset($requested[$taxonomy]) || !is_array($requested[$taxonomy])) {
                continue;
            }

            $termIds = array_values(array_filter(array_map('intval', $requested[$taxonomy]), static fn($v) => $v > 0));

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

        if (!empty($taxQuery)) {
            if (count($taxQuery) > 1) {
                $taxQuery = array_merge(['relation' => 'AND'], $taxQuery);
            }

            $query->set('tax_query', $taxQuery);
        }
    }

    public static function sanitizeSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9_\-]/', '_', $slug);
        $slug = preg_replace('/_+/', '_', $slug);
        $slug = trim($slug, '_');
        
        if (strlen($slug) > self::MAX_SLUG_LENGTH) {
            $slug = substr($slug, 0, self::MAX_SLUG_LENGTH);
            $slug = rtrim($slug, '_');
        }

        return $slug;
    }

    /**
     * Restituisce tutte le tassonomie gestibili dall'utente.
     * Per ognuna indica se è modificabile solo nel nome ('known') o anche eliminabile ('custom').
     *
     * @return array{slug: string, label: string, column: string, type: 'known'|'custom'}[]
     */
    public function getCustomizableTaxonomies(): array
    {
        $result         = [];
        $labelOverrides = get_option('woo_excel_taxonomy_label_overrides', []);
        $disabled       = get_option('woo_excel_disabled_taxonomies', []);

        foreach (TaxonomyConfig::getKnownTaxonomies() as $columnName => $config) {
            if (!empty($config['native'])) {
                continue;
            }
            if (in_array($config['slug'], $disabled, true)) {
                continue;
            }
            $slug  = $config['slug'];
            $label = $labelOverrides[$slug] ?? $config['label'];
            $result[] = [
                'slug'    => $slug,
                'label'   => $label,
                'column'  => $columnName,
                'type'    => 'config',
            ];
        }

        foreach ($this->getCustomTaxonomies() as $columnName => $config) {
            $slug  = $config['slug'];
            $label = $config['label'];
            $result[] = [
                'slug'    => $slug,
                'label'   => $label,
                'column'  => $columnName,
                'type'    => 'custom', // salvata in DB: rinominabile ed eliminabile
            ];
        }

        return $result;
    }

    /**
     * Rinomina la label di una tassonomia (sia known che custom).
     * Per le tassonomie 'known' salva un override in un'opzione separata.
     * Per le tassonomie 'custom' aggiorna direttamente l'opzione.
     *
     * @throws \InvalidArgumentException se lo slug non è trovato o la label è vuota.
     */
    public function renameTaxonomy(string $slug, string $newLabel): void
    {
        $newLabel = trim($newLabel);
        if ($newLabel === '') {
            throw new \InvalidArgumentException('Il nome della tassonomia non può essere vuoto.');
        }

        // Verifica se è una tassonomia 'known'
        foreach (TaxonomyConfig::getKnownTaxonomies() as $config) {
            if (!empty($config['native'])) {
                continue;
            }
            if ($config['slug'] === $slug) {
                $overrides         = get_option('woo_excel_taxonomy_label_overrides', []);
                $overrides[$slug]  = $newLabel;
                update_option('woo_excel_taxonomy_label_overrides', $overrides, false);
                return;
            }
        }

        // Verifica se è una tassonomia 'custom'
        $customs = $this->getCustomTaxonomies();
        foreach ($customs as $columnName => $config) {
            if ($config['slug'] === $slug) {
                $customs[$columnName]['label'] = $newLabel;
                $this->saveCustomTaxonomies($customs);
                return;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Tassonomia con slug "%s" non trovata.', $slug)
        );
    }

    /**
     * Elimina una tassonomia e tutti i suoi termini.
     * - Tassonomie 'config' (da TaxonomyConfig): vengono aggiunte alla lista disabilitati.
     * - Tassonomie 'custom' (da DB): vengono rimosse dall'opzione.
     *
     * @throws \InvalidArgumentException se lo slug non è trovato.
     */
    public function deleteTaxonomy(string $slug): void
    {
        $found = false;

        // Tassonomia 'config': aggiungila ai disabilitati
        foreach (TaxonomyConfig::getKnownTaxonomies() as $config) {
            if (!empty($config['native'])) {
                continue;
            }
            if ($config['slug'] === $slug) {
                $disabled   = get_option('woo_excel_disabled_taxonomies', []);
                $disabled[] = $slug;
                update_option('woo_excel_disabled_taxonomies', array_unique($disabled), false);
                $found = true;
                break;
            }
        }

        // Tassonomia 'custom': rimuovila dal DB
        if (!$found) {
            $customs = $this->getCustomTaxonomies();
            foreach ($customs as $columnName => $config) {
                if ($config['slug'] === $slug) {
                    unset($customs[$columnName]);
                    $this->saveCustomTaxonomies($customs);
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            throw new \InvalidArgumentException(
                sprintf('La tassonomia "%s" non è stata trovata.', $slug)
            );
        }

        // Elimina tutti i termini della tassonomia dal DB
        if (taxonomy_exists($slug)) {
            $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false, 'fields' => 'ids']);
            if (!is_wp_error($terms)) {
                foreach ($terms as $termId) {
                    wp_delete_term((int) $termId, $slug);
                }
            }
        }
    }
}
