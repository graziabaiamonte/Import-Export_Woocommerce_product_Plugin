jQuery(document).ready(function($) {
    'use strict';

    /**
     * Taxonomy Meta Box - Add New Term Functionality
     */
    
    // Show/hide add term form
    $(document).on('click', '.woo-excel-add-new-term', function(e) {
        e.preventDefault();
        var taxonomy = $(this).data('taxonomy');
        $('#new-term-' + taxonomy).slideToggle();
    });
    
    // Cancel button - hide form and clear input
    $(document).on('click', '.woo-excel-cancel-term', function() {
        $(this).closest('.woo-excel-new-term-form').slideUp();
        $(this).siblings('input').val('');
    });
    
    // Save new term via AJAX
    $(document).on('click', '.woo-excel-save-term', function() {
        var button = $(this);
        var taxonomy = button.data('taxonomy');
        var termName = $('#new-term-name-' + taxonomy).val().trim();
        var spinner = button.siblings('.spinner');
        
        if (!termName) {
            alert(wooExcelImporter.i18n.enterTermName);
            return;
        }
        
        spinner.addClass('is-active');
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'woo_excel_add_term',
                taxonomy: taxonomy,
                term_name: termName,
                nonce: wooExcelImporter.addTermNonce
            },
            success: function(response) {
                if (response.success) {
                    // Add new option to select
                    var select = $('#' + taxonomy + '_selector');
                    var option = $('<option>', {
                        value: response.data.term_id,
                        text: response.data.name,
                        selected: true
                    });
                    select.append(option);
                    
                    // Reset form
                    $('#new-term-name-' + taxonomy).val('');
                    $('#new-term-' + taxonomy).slideUp();
                } else {
                    alert(response.data.message || wooExcelImporter.i18n.errorCreatingTerm);
                }
            },
            error: function() {
                alert(wooExcelImporter.i18n.connectionError);
            },
            complete: function() {
                spinner.removeClass('is-active');
                button.prop('disabled', false);
            }
        });
    });
});
