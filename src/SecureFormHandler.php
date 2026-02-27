<?php

declare(strict_types=1);

namespace WooExcelImporter;

/**
 * Trait SecureFormHandler
 * 
 * Centralizes security checks for forms and AJAX handlers.
 * Provides reusable methods for capability and nonce verification.
 */
trait SecureFormHandler
{
    /**
     * Verify that current user has the required capability.
     * Dies with error message if check fails.
     * 
     * @param string $capability WordPress capability to check
     * @param string|null $errorMessage Custom error message (optional)
     * @return void
     */
    protected function verifyCapability(string $capability = 'manage_woocommerce', ?string $errorMessage = null): void
    {
        if (!current_user_can($capability)) {
            $message = $errorMessage ?? __('You do not have sufficient permissions to perform this action.', 'woo-excel-importer');
            wp_die(esc_html($message));
        }
    }

    /**
     * Verify nonce for form submission.
     * Dies with error message if check fails.
     * 
     * @param string $nonce Nonce value from $_POST
     * @param string $action Nonce action name
     * @param string|null $errorMessage Custom error message (optional)
     * @return void
     */
    protected function verifyNonce(string $nonce, string $action, ?string $errorMessage = null): void
    {
        if (!wp_verify_nonce($nonce, $action)) {
            $message = $errorMessage ?? __('Security check failed. Please try again.', 'woo-excel-importer');
            wp_die(esc_html($message));
        }
    }

    /**
     * Verify both capability and nonce in one call.
     * Common pattern for form handlers.
     * 
     * @param string $nonceField Name of the nonce field in $_POST
     * @param string $nonceAction Nonce action name
     * @param string $capability WordPress capability to check
     * @return void
     */
    protected function verifySecureRequest(string $nonceField, string $nonceAction, string $capability = 'manage_woocommerce'): void
    {
        $this->verifyCapability($capability);

        if (!isset($_POST[$nonceField])) {
            wp_die(esc_html__('Security check failed: nonce field missing.', 'woo-excel-importer'));
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[$nonceField]));
        $this->verifyNonce($nonce, $nonceAction);
    }

    /**
     * Verify AJAX request security (capability + nonce).
     * Returns false instead of dying (for JSON responses).
     * 
     * @param string $nonceField Name of the nonce field in $_POST
     * @param string $nonceAction Nonce action name
     * @param string $capability WordPress capability to check
     * @return bool True if valid, false otherwise
     */
    protected function isSecureAjaxRequest(string $nonceField, string $nonceAction, string $capability = 'manage_woocommerce'): bool
    {
        if (!current_user_can($capability)) {
            return false;
        }

        if (!isset($_POST[$nonceField])) {
            return false;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[$nonceField]));
        return wp_verify_nonce($nonce, $nonceAction) !== false;
    }
}
