jQuery(document).ready(function ($) {
    'use strict';

    /**
     * Converte i checkbox dei metabox tassonomia in radio button,
     * in modo che sia possibile selezionare un solo termine per volta.
     *
     * WordPress genera i metabox nativi con input[type="checkbox"] e
     * name="tax_input[slug][]". Il name viene mantenuto invariato (con [])
     * così il browser invia sempre un array, compatibile con la funzione
     * taxonomy_meta_box_sanitize_cb_checkboxes di WordPress.
     *
     * wooExcelTaxonomies.slugs è iniettato via wp_localize_script
     * e contiene solo le tassonomie custom del plugin (no product_cat).
     */
    if (typeof wooExcelTaxonomies === 'undefined' || !wooExcelTaxonomies.slugs) {
        return;
    }

    /**
     * Converte i checkbox di un singolo metabox tassonomia in radio button.
     * Viene chiamata sia all'avvio sia dopo che WordPress carica nuovi termini
     * tramite AJAX (tab "Più usati" / ricerca).
     *
     * @param {string} taxonomy - slug della tassonomia
     */
    function convertToRadio(taxonomy) {
        var $metabox = $('#' + taxonomy + 'div, #tagsdiv-' + taxonomy);
        if (!$metabox.length) return;

        $metabox.find('input[type="checkbox"]').each(function () {
            var $cb = $(this);

            // Evita doppia conversione
            if ($cb.attr('type') === 'radio') return;

            // Mantiene il name originale "tax_input[slug][]" con le parentesi:
            // il browser invia un array con un solo elemento, che è quello
            // che WordPress si aspetta da taxonomy_meta_box_sanitize_cb_checkboxes.
            $cb.attr('type', 'radio');
        });

        // "Deseleziona tutto" tramite click sul radio già selezionato:
        // un radio non si deseleziona nativamente, aggiunge questo comportamento.
        $metabox.off('click.wooExcelRadio').on('click.wooExcelRadio', 'input[type="radio"]', function () {
            var $radio = $(this);
            if ($radio.data('wooExcelWasChecked')) {
                $radio.prop('checked', false).data('wooExcelWasChecked', false);
                return;
            }
            // Resetta lo stato "era selezionato" per tutti i radio del gruppo.
            // Il name contiene "[]" quindi va escaped per l'uso come selettore CSS.
            var escapedName = $radio.attr('name').replace(/\[/g, '\\[').replace(/\]/g, '\\]');
            $metabox.find('input[name="' + escapedName + '"]')
                .data('wooExcelWasChecked', false);
            $radio.data('wooExcelWasChecked', true);
        });

        // Segna come già selezionato il radio che risulta checked al caricamento
        $metabox.find('input[type="radio"]:checked').data('wooExcelWasChecked', true);
    }

    // Prima conversione al caricamento della pagina
    $.each(wooExcelTaxonomies.slugs, function (i, slug) {
        convertToRadio(slug);
    });

    // WordPress ricarica i termini via AJAX quando si clicca "Più usati" o si cerca:
    // ascolta l'evento ajaxSuccess e riconverte i checkbox appena inseriti nel DOM.
    $(document).on('ajaxSuccess', function (e, xhr, settings) {
        // Filtra solo le chiamate AJAX dei metabox tassonomia (action=get-tagcloud o simili)
        if (settings.data && settings.data.indexOf('action=') !== -1) {
            $.each(wooExcelTaxonomies.slugs, function (i, slug) {
                convertToRadio(slug);
            });
        }
    });

});
