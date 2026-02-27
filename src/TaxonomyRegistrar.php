<?php

declare(strict_types=1);

namespace WooExcelImporter;

final class TaxonomyRegistrar
{
    use SecureFormHandler;

    private const MAX_SLUG_LENGTH = 32;

    public function register(): void
    {
        foreach ($this->getAllTaxonomies() as $config) {
            $this->registerTaxonomy($config);
        }
        
        // Add custom meta box rendering
        add_action('add_meta_boxes', [$this, 'addCustomMetaBoxes'], 10);
        
        // Save taxonomy terms when product is saved
        add_action('save_post_product', [$this, 'saveProductTaxonomies'], 10, 2);
        
        // AJAX handler for adding new terms
        add_action('wp_ajax_woo_excel_add_term', [$this, 'ajaxAddTerm']);
        add_action('restrict_manage_posts', [$this, 'addProductFilters'], 20);
        add_action('pre_get_posts', [$this, 'applyProductFilters']);
    }

    /**
     * Register taxonomies on plugin activation.
     * Skips taxonomies that already exist and are connected to product post type.
     */
    public function registerOnActivation(): void
    {
        foreach ($this->getAllTaxonomies() as $config) {
            $slug = $config['slug'];
            
            // Skip if taxonomy already exists and is connected to product
            if (taxonomy_exists($slug) && $this->isTaxonomyConnectedToProduct($slug)) {
                continue;
            }
            
            // Register or connect the taxonomy
            $this->registerTaxonomy($config);
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
        if (taxonomy_exists($config['slug'])) {
            register_taxonomy_for_object_type($config['slug'], 'product');
            return;
        }

        $args = [
            'label' => $config['label'],
            'labels' => $this->generateLabels($config['label']),
            'hierarchical' => $config['hierarchical'],
            'public' => false,
            'show_ui' => false,
            'show_admin_column' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'rewrite' => false,
            'query_var' => false,
            'meta_box_cb' => null,
            'capabilities' => [
                'manage_terms' => 'manage_woocommerce',
                'edit_terms' => 'manage_woocommerce',
                'delete_terms' => 'manage_woocommerce',
                'assign_terms' => 'manage_woocommerce',
            ],
        ];

        register_taxonomy($config['slug'], ['product'], $args);
    }

    public function addCustomMetaBoxes(): void
    {
        static $scriptAdded = false;

        foreach ($this->getAllTaxonomies() as $config) {
            $callback = $config['hierarchical'] ? 'renderHierarchicalMetaBox' : 'renderMetaBox';

            remove_meta_box($config['slug'] . 'div', 'product', 'side');
            add_meta_box(
                $config['slug'] . '_custom',
                $config['label'],
                [$this, $callback],
                'product',
                'side',
                'default',
                ['taxonomy' => $config['slug']]
            );
        }

        if (!$scriptAdded) {
            add_action('admin_footer', [$this, 'renderTaxonomyScript']);
            $scriptAdded = true;
        }
    }

    public function renderMetaBox($post, $box): void
    {
        $taxonomy = $box['args']['taxonomy'];
        $tax = get_taxonomy($taxonomy);
        
        if (!$tax) {
            return;
        }

        $terms = wp_get_post_terms($post->ID, $taxonomy);
        $current_term_id = !empty($terms) && !is_wp_error($terms) ? $terms[0]->term_id : 0;
        
        $all_terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        wp_nonce_field('woo_excel_taxonomy_' . $taxonomy, 'woo_excel_taxonomy_nonce_' . $taxonomy);
        
        ?>
        <div class="woo-excel-taxonomy-select" style="margin: 10px 0;">
            <select name="tax_input_single[<?php echo esc_attr($taxonomy); ?>]" id="<?php echo esc_attr($taxonomy); ?>_selector" style="width: 100%;">
                <option value="0"><?php esc_html_e('— Seleziona —', 'woo-excel-importer'); ?></option>
                <?php foreach ($all_terms as $term): ?>
                    <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_term_id, $term->term_id); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <p style="margin-top: 10px;">
                <a href="#" class="woo-excel-add-new-term" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" style="text-decoration: none;">
                    <span class="dashicons dashicons-plus-alt" style="font-size: 16px; margin-top: 2px;"></span>
                    <?php esc_html_e('Aggiungi nuovo', 'woo-excel-importer'); ?>
                </a>
            </p>
            
            <div class="woo-excel-new-term-form" id="new-term-<?php echo esc_attr($taxonomy); ?>" style="display:none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <input type="text" 
                       id="new-term-name-<?php echo esc_attr($taxonomy); ?>" 
                       placeholder="<?php esc_attr_e('Nome nuovo termine', 'woo-excel-importer'); ?>" 
                       style="width: 100%; margin-bottom: 5px;">
                <button type="button" class="button woo-excel-save-term" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                    <?php esc_html_e('Aggiungi', 'woo-excel-importer'); ?>
                </button>
                <button type="button" class="button woo-excel-cancel-term">
                    <?php esc_html_e('Annulla', 'woo-excel-importer'); ?>
                </button>
                <span class="spinner" style="float: none; margin: 0;"></span>
            </div>
        </div>
        <?php
    }

    public function renderHierarchicalMetaBox($post, $box): void
    {
        $taxonomy = $box['args']['taxonomy'];
        $tax = get_taxonomy($taxonomy);
        
        if (!$tax) {
            return;
        }

        $terms = wp_get_post_terms($post->ID, $taxonomy);
        $current_term_id = !empty($terms) && !is_wp_error($terms) ? $terms[0]->term_id : 0;
        
        $all_terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        
        wp_nonce_field('woo_excel_taxonomy_' . $taxonomy, 'woo_excel_taxonomy_nonce_' . $taxonomy);
        
        ?>
        <div class="woo-excel-taxonomy-select" style="margin: 10px 0;">
            <select name="tax_input_single[<?php echo esc_attr($taxonomy); ?>]" id="<?php echo esc_attr($taxonomy); ?>_selector" style="width: 100%;">
                <option value="0"><?php esc_html_e('— Seleziona —', 'woo-excel-importer'); ?></option>
                <?php foreach ($all_terms as $term): ?>
                    <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($current_term_id, $term->term_id); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <p style="margin-top: 10px;">
                <a href="#" class="woo-excel-add-new-term" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" style="text-decoration: none;">
                    <span class="dashicons dashicons-plus-alt" style="font-size: 16px; margin-top: 2px;"></span>
                    <?php esc_html_e('Aggiungi nuovo', 'woo-excel-importer'); ?>
                </a>
            </p>
            
            <div class="woo-excel-new-term-form" id="new-term-<?php echo esc_attr($taxonomy); ?>" style="display:none; margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;">
                <input type="text" 
                       id="new-term-name-<?php echo esc_attr($taxonomy); ?>" 
                       placeholder="<?php esc_attr_e('Nome nuovo termine', 'woo-excel-importer'); ?>" 
                       style="width: 100%; margin-bottom: 5px;">
                <button type="button" class="button woo-excel-save-term" data-taxonomy="<?php echo esc_attr($taxonomy); ?>">
                    <?php esc_html_e('Aggiungi', 'woo-excel-importer'); ?>
                </button>
                <button type="button" class="button woo-excel-cancel-term">
                    <?php esc_html_e('Annulla', 'woo-excel-importer'); ?>
                </button>
                <span class="spinner" style="float: none; margin: 0;"></span>
            </div>
        </div>
        <?php
    }

    public function saveProductTaxonomies(int $post_id, $post): void
    {
        // Verify this is a product
        if ($post->post_type !== 'product') {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if our custom taxonomy data is present
        if (!isset($_POST['tax_input_single']) || !is_array($_POST['tax_input_single'])) {
            return;
        }

        // Process ALL taxonomies (base + custom)
        $allTaxonomies = $this->getAllTaxonomies();
        
        foreach ($allTaxonomies as $config) {
            $taxonomy = $config['slug'];
            
            // Verify nonce
            $nonce_key = 'woo_excel_taxonomy_nonce_' . $taxonomy;
            if (!isset($_POST[$nonce_key]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_key])), 'woo_excel_taxonomy_' . $taxonomy)) {
                continue;
            }

            // Get selected term ID
            $term_id = isset($_POST['tax_input_single'][$taxonomy]) ? intval($_POST['tax_input_single'][$taxonomy]) : 0;

            // Assign term to product (or clear if 0)
            if ($term_id > 0) {
                wp_set_object_terms($post_id, [$term_id], $taxonomy, false);
            } else {
                wp_set_object_terms($post_id, [], $taxonomy, false);
            }

            // Clear cache
            clean_object_term_cache($post_id, 'product');
        }
    }

    private function generateLabels(string $singular): string
    {
        return $singular;
    }

    public function renderTaxonomyScript(): void
    {
        // Enqueue external JavaScript file
        wp_enqueue_script(
            'woo-excel-taxonomy',
            WOO_EXCEL_IMPORTER_URL . 'assets/admin.js',
            ['jquery'],
            WOO_EXCEL_IMPORTER_VERSION,
            true
        );

        // Localize script with translations and nonce
        wp_localize_script('woo-excel-taxonomy', 'wooExcelImporter', [
            'addTermNonce' => wp_create_nonce('woo_excel_add_term'),
            'i18n' => [
                'enterTermName' => __('Inserisci un nome per il termine', 'woo-excel-importer'),
                'errorCreatingTerm' => __('Errore durante la creazione del termine', 'woo-excel-importer'),
                'connectionError' => __('Errore di connessione', 'woo-excel-importer'),
            ],
        ]);
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

    public function ajaxAddTerm(): void
    {
        // Verify security using trait method
        if (!$this->isSecureAjaxRequest('nonce', 'woo_excel_add_term', 'manage_woocommerce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Get taxonomy and term name
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field(wp_unslash($_POST['taxonomy'])) : '';
        $term_name = isset($_POST['term_name']) ? sanitize_text_field(wp_unslash($_POST['term_name'])) : '';

        if (empty($taxonomy) || empty($term_name)) {
            wp_send_json_error(['message' => 'Missing taxonomy or term name']);
            return;
        }

        // Verify taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            wp_send_json_error(['message' => 'Invalid taxonomy']);
            return;
        }

        // Create the term
        $result = wp_insert_term($term_name, $taxonomy);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        // Get the created term
        $term = get_term($result['term_id'], $taxonomy);

        if (is_wp_error($term)) {
            wp_send_json_error(['message' => 'Term created but could not be retrieved']);
            return;
        }

        wp_send_json_success([
            'term_id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        ]);
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
}
