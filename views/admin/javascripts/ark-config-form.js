if (!Omeka) {
    var Omeka = {};
}

Omeka.Ark = {};

(function ($) {
    /**
     * Enable/disable options according to selected format.
     */
    Omeka.Ark.updateConfigOptions = function () {
        if ($('#ark_format_name-noid').is(':checked')) {
            $('#fieldset-ark-format-noid').slideDown();
            $('#fieldset-ark-format-omeka_id').slideUp();
            $('#fieldset-ark-format-command').slideUp();
        } else if ($('#ark_format_name-omeka_id').is(':checked')) {
            $('#fieldset-ark-format-noid').slideUp();
            $('#fieldset-ark-format-omeka_id').slideDown();
            $('#fieldset-ark-format-command').slideUp();
        } else if ($('#ark_format_name-command').is(':checked')) {
            $('#fieldset-ark-format-noid').slideUp();
            $('#fieldset-ark-format-omeka_id').slideUp();
            $('#fieldset-ark-format-command').slideDown();
        } else {
            $('#fieldset-ark-format-noid').slideUp();
            $('#fieldset-ark-format-omeka_id').slideUp();
            $('#fieldset-ark-format-command').slideUp();
        };
    };
})(jQuery);

/**
 * Enable/disable options after loading.
 */
jQuery(document).ready(function () {
    Omeka.Ark.updateConfigOptions();
    jQuery('#ark_format_name-noid').click(Omeka.Ark.updateConfigOptions);
    jQuery('#ark_format_name-omeka_id').click(Omeka.Ark.updateConfigOptions);
    jQuery('#ark_format_name-command').click(Omeka.Ark.updateConfigOptions);
});
