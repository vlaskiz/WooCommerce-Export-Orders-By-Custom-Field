
jQuery( document ).ready(function() {

    function SaveToDisk(fileURL, fileName) {
        // for non-IE
        if (!window.ActiveXObject) {
            var save = document.createElement('a');
            save.href = fileURL;
            save.target = '_blank';
            save.download = fileName || 'unknown';
    
            var evt = new MouseEvent('click', {
                'view': window,
                'bubbles': true,
                'cancelable': false
            });
            save.dispatchEvent(evt);
    
            (window.URL || window.webkitURL).revokeObjectURL(save.href);
        }
    
        // for IE < 11
        else if ( !! window.ActiveXObject && document.execCommand)     {
            var _window = window.open(fileURL, '_blank');
            _window.document.close();
            _window.document.execCommand('SaveAs', true, fileName || fileURL)
            _window.close();
        }
    }

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
                        // window.open( response.data.download_url, '_blank' // );
                        SaveToDisk(response.data.download_url, response.data.file_name);
                    }

                    $exportForm.append('<div class="export-message" style="margin-top:10px">'+ response.message +'</div>');
                    $this.removeClass('loading').attr('disabled', false).next('.spinner').removeClass('is-active');
                }
            });


        });

    })(jQuery);

});