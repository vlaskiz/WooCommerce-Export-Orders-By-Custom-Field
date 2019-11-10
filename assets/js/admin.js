
jQuery( document ).ready(function() {

    (function ($) {
        "use strict";

        var $exportForm = jQuery('form#export_order');

        $exportForm.on('click', 'input#woo_export_order_bcf', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $this = jQuery(this),
                response,
                exportFormData = new FormData();

            $exportForm.find('.export-message').remove();

            // Form data
            var form_data = $exportForm.serializeArray();

            jQuery.each(form_data, function (key, input) {
                exportFormData.append(input.name, input.value);
            });

            // AJAX action
            exportFormData.append( 'action', 'woo_export_orders_bcfpdf');

            jQuery.ajax({
                url: weobcf_data.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: exportFormData,
                processData: false,
                contentType: false,
                beforeSend: function (response) {
                    $this.addClass('loading').prop('disabled', 'disabled').next('.spinner').addClass('is-active');
                },
                // error: function (data) {},
                success: function (data) {
                    response = data;
                    if ( response.status === 'success' ) {
                        window.open(
                            response.data.download_url,
                            '_blank' // <- This is what makes it open in a new window.
                        );
                    }

                    $exportForm.append('<div class="export-message" style="margin-top:10px">'+ response.message +'</div>');
                    $this.removeClass('loading').attr('disabled', false).next('.spinner').removeClass('is-active');
                }
            });


        });

    })(jQuery);

});