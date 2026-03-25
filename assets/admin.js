jQuery(document).ready(function ($) {

    // vieta variabili non dichiarate
    'use strict';

    // let ha block scope ({}), var ha solo function scope 

    /**
     * Converte i checkbox dei metabox tassonomia in radio button,
     * in modo che sia possibile selezionare un solo termine per volta.
     *
     * wooExcelTaxonomies.slugs contiene solo le tassonomie custom del plugin (no product_cat).
     */

    // Verifica che la variabile globale "wooExcelTaxonomies" esista e contenga la proprietà "slugs".
    if (typeof wooExcelTaxonomies === 'undefined' || !wooExcelTaxonomies.slugs) {
        return;
    }

    /**
     * Converte i checkbox di un singolo metabox tassonomia in radio button.
     *
     * @param {string} taxonomy - slug della tassonomia
     */
    function convertToRadio(taxonomy) {

        // Costruisce il selettore per trovare il metabox WordPress della tassonomia nel DOM.
        //
        // WordPress genera metabox con id "<slug>div" per le tassonomie gerarchiche (tipo categorie)
        // e "tagsdiv-<slug>" per quelle non gerarchiche (tipo tag); il selettore con virgola trova
        // entrambi i casi in un'unica chiamata.
        var $metabox = $('#' + taxonomy + 'div, #tagsdiv-' + taxonomy);

        if (!$metabox.length) return;

        $metabox.find('input[type="checkbox"]').each(function () {
            var $cb = $(this);
            if ($cb.attr('type') === 'radio') return;

            // Cambia l'attributo HTML "type" dell'input da "checkbox" a "radio".
            $cb.attr('type', 'radio');
        });

        // "Deseleziona tutto" tramite click sul radio già selezionato:
        // un radio non si deseleziona nativamente
        //
        // Rimuove qualsiasi listener click precedentemente registrato con il namespace
        // "wooExcelRadio" sul metabox, poi ne aggiunge uno nuovo.
        $metabox.off('click.wooExcelRadio').on('click.wooExcelRadio', 'input[type="radio"]', function () {

            // Salva un riferimento jQuery al radio button appena cliccato 
            var $radio = $(this);

            // Legge il dato personalizzato "wooExcelWasChecked" memorizzato sull'elemento jQuery tramite .data(). 
            // Serve a sapere se il radio era già selezionato PRIMA di questo click: se sì, significa che l'utente vuole deselezionarlo
            if ($radio.data('wooExcelWasChecked')) {

                // Deseleziona il radio button impostando "checked" a false e resetta il flag a false.
                $radio.prop('checked', false).data('wooExcelWasChecked', false);

                return;
            }

            // Resetta lo stato "era selezionato" per tutti i radio del gruppo.
            // Il name contiene "[]" quindi va escaped per l'uso come selettore CSS.
            //
            // I radio button delle tassonomie WordPress hanno un name tipo: tax_input[product_brand][] Il problema: [ e ] sono caratteri speciali nei selettori CSS. Se provi a usarli così com'è in un selettore ti dà errrore
            //
            var escapedName = $radio.attr('name').replace(/\[/g, '\\[').replace(/\]/g, '\\]');

            // Trova tutti i radio button dello stesso gruppo (stesso "name") nel metabox e
            // resetta il loro flag "wooExcelWasChecked" a false. Questo è necessario perché
            // quando si clicca un radio diverso, il browser deseleziona automaticamente gli altri,
            // ma il flag JS rimarrebbe sporco senza questo reset.
            $metabox.find('input[name="' + escapedName + '"]')
                .data('wooExcelWasChecked', false);

            // Imposta il flag "wooExcelWasChecked" a true solo sul radio appena cliccato
            $radio.data('wooExcelWasChecked', true);
        });

        // Cerca tutti i radio button già selezionati e imposta il loro flag a true.
        $metabox.find('input[type="radio"]:checked').data('wooExcelWasChecked', true);
    }

    // Itera l'array di slug delle tassonomie custom
    // e chiama convertToRadio() per ciascuno.
    // 
    // "$.each()" è l'equivalente jQuery di "foreach"
    // su un array; il callback riceve l'indice numerico "i" e il valore "slug" ad ogni iterazione.
    $.each(wooExcelTaxonomies.slugs, function (i, slug) {
        convertToRadio(slug);
    });

    // WordPress ricarica i termini via AJAX quando si clicca "Più usati" o si cerca:
    // ascolta l'evento ajaxSuccess e riconverte i checkbox appena inseriti nel DOM.

    // Ascolta l'evento globale "ajaxSuccess" che jQuery scatena ogni volta che una qualsiasi
    // chiamata AJAX va a buon fine. Viene usato per intercettare il momento in cui
    // WordPress inietta nuovi checkbox nel DOM così i nuovi elementi vengono immediatamente convertiti in radio button. 
    $(document).on('ajaxSuccess', function (e, xhr, settings) {

        // Filtra solo le chiamate AJAX dei metabox tassonomia (action=get-tagcloud o simili)
        // Controlla che la chiamata AJAX abbia un parametro "action=" nei dati inviati.
        // ".indexOf()" restituisce -1 se la sottostringa non è trovata
        if (settings.data && settings.data.indexOf('action=') !== -1) {

            // Ri-itera tutte le tassonomie custom e richiama convertToRadio() su ciascuna.
            $.each(wooExcelTaxonomies.slugs, function (i, slug) {
                convertToRadio(slug);
            });
        }
    });
});


// PERCHè LA LOGICA DEL CAMBIAMENTO DI TYPE DELL'INPUT E' IN JS E NON IN PHP?
// 
// WordPress renderizza i metabox tassonomia tramite funzioni interne che non offrono un filtro diretto sull'HTML degli input. Scrivendo degli hook appositi, essi sarebbero fragili e soggetti a rottura ad ogni aggiornamento di wp. Quindi, js è la scelta giusta: infatti il dom è già renderizzato, quindi js può modificarlo liberamente dopo che wp lo ha scritto, senza toccare le sue funzioni interne.