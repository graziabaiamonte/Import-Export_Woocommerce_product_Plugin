<?php
declare(strict_types=1);

namespace WooExcelImporter;

// Definisce un trait (un blocco di metodi riutilizzabili incluso nelle classi tramite "use")
trait SecureFormHandler
{
    /**
     *
     * @param string $capability WordPress capability to check
     * @param string|null $errorMessage Custom error message (optional)
     * @return void
     */
    // Metodo protetto (accessibile solo dalla classe e dalle sue sottoclassi) che verifica se l'utente corrente ha il permesso richiesto
    // $capability: la capability WordPress da verificare (default: 'manage_woocommerce')
    // $errorMessage: messaggio di errore personalizzato opzionale (può essere null)
    // void: non restituisce nulla, termina l'esecuzione in caso di fallimento
    protected function verifyCapability(string $capability = 'manage_woocommerce', ?string $errorMessage = null): void
    {
        if (!current_user_can($capability)) {
            // Se non è stato passato un messaggio personalizzato ($errorMessage è null), usa l'operatore ?? (null coalescing)
            // per assegnare il messaggio di default tradotto con __() (funzione i18n di WordPress)
            $message = $errorMessage ?? __('You do not have sufficient permissions to perform this action.', 'woo-excel-importer');
            // Termina l'esecuzione dello script mostrando il messaggio di errore
            wp_die(esc_html($message));
        }
    }

    /**
     *
     * @param string $nonce Nonce value from $_POST
     * @param string $action Nonce action name
     * @param string|null $errorMessage Custom error message (optional)
     * @return void
     */
    // Metodo protetto che verifica la validità del nonce per una sottomissione di form
    // $nonce: il valore del nonce ricevuto dal form via $_POST
    // $action: il nome dell'azione con cui il nonce è stato generato (devono corrispondere)
    // $errorMessage: messaggio di errore opzionale
    protected function verifyNonce(string $nonce, string $action, ?string $errorMessage = null): void
    {
        // wp_verify_nonce() confronta il nonce ricevuto con quello generato dal server per l'azione specificata
        // Restituisce false (o 0) se il nonce è invalido o scaduto; il "!" inverte: entra nell'if se NON è valido
        if (!wp_verify_nonce($nonce, $action)) {
            // Se non c'è un messaggio personalizzato, usa il messaggio di default
            $message = $errorMessage ?? __('Security check failed. Please try again.', 'woo-excel-importer');
            wp_die(esc_html($message));
        }
    }

    /** 
     *
     * @param string $nonceField Name of the nonce field in $_POST
     * @param string $nonceAction Nonce action name
     * @param string $capability WordPress capability to check
     * @return void
     */
    // Metodo protetto che esegue entrambi i controlli di sicurezza (capability + nonce) con una sola chiamata
    // È il pattern standard da usare negli handler di form per garantire sia autenticazione che integrità della richiesta
    // $nonceField: nome del campo hidden nel form che contiene il valore del nonce 
    // $nonceAction: nome dell'azione associata al nonce (deve corrispondere a quello usato in wp_nonce_field())
    protected function verifySecureRequest(string $nonceField, string $nonceAction, string $capability = 'manage_woocommerce'): void
    {
        $this->verifyCapability($capability);

        // Controlla che il campo del nonce esista nell'array $_POST (dati inviati via form)
        // Se il campo è assente significa che la richiesta non è stata inviata correttamente
        if (!isset($_POST[$nonceField])) {
            wp_die(esc_html__('Security check failed: nonce field missing.', 'woo-excel-importer'));
        }

        // Recupera il valore del nonce da $_POST e lo sanitizza:
        // wp_unslash() rimuove i backslash aggiunti da PHP (magic quotes), sanitize_text_field() pulisce la stringa da tag e caratteri indesiderati
        $nonce = sanitize_text_field(wp_unslash($_POST[$nonceField]));
        // Chiama il metodo verifyNonce() per verificare che il nonce sia valido rispetto all'azione specificata
        $this->verifyNonce($nonce, $nonceAction);
    }

    /**
     *
     * @param string $nonceField Name of the nonce field in $_POST
     * @param string $nonceAction Nonce action name
     * @param string $capability WordPress capability to check
     * @return bool True if valid, false otherwise
     */
    // Metodo protetto per verificare la sicurezza di una richiesta AJAX
    // A differenza di verifySecureRequest(), restituisce bool invece di chiamare wp_die()
    // Questo permette al chiamante di gestire il fallimento con una risposta JSON invece di terminare bruscamente
    // $nonceField: nome del campo nonce in $_POST
    // $nonceAction: nome dell'azione del nonce
    // Restituisce true se tutti i controlli passano, false altrimenti
    protected function isSecureAjaxRequest(string $nonceField, string $nonceAction, string $capability = 'manage_woocommerce'): bool
    {
        if (!current_user_can($capability)) {
            // Restituisce false per segnalare il fallimento senza interrompere l'esecuzione
            // Il chiamante gestirà il fallimento con una risposta JSON appropriata (es. wp_send_json_error())
            return false;
        }

        // Verifica che il campo nonce sia presente nell'array $_POST
        // Una richiesta AJAX legittima deve sempre includere il nonce
        if (!isset($_POST[$nonceField])) {
            // Restituisce false: nonce mancante, richiesta non sicura
            return false;
        }

        // Recupera e sanitizza il valore del nonce:
        // wp_unslash() rimuove eventuali backslash aggiunti automaticamente da PHP
        // sanitize_text_field() rimuove tag HTML, caratteri di controllo e spazi extra
        $nonce = sanitize_text_field(wp_unslash($_POST[$nonceField]));
        // wp_verify_nonce() verifica il nonce: restituisce 1 (recente), 2 (vecchio ma valido) o false (non valido)
        // "!== false" assicura che anche il valore 2 (nonce ancora valido ma prossimo alla scadenza) venga considerato valido
        // Restituisce true se il nonce è valido, false se è scaduto o contraffatto
        return wp_verify_nonce($nonce, $nonceAction) !== false;
    }
}




// NONCE sta per "Number used ONCE" — un token monouso generato dal server per validare che una richiesta HTTP provenga da una sorgente legittima e non da un attaccante.

