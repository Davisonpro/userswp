(function( $, window, undefined ) {
    $(document).ready(function () {
        $('.uwp-modal-close').click(function(e) {
            e.preventDefault();
            var uwp_popup_type = $( this ).data( 'type' );
            // $('#uwp-'+uwp_popup_type+'-modal').hide();
            var mod_shadow = jQuery('#uwp-modal-backdrop');
            var container = jQuery('#uwp-popup-modal-wrap');
            container.hide();
            container.replaceWith('<div id="uwp-popup-modal-wrap" style="display: none;">\
                <div class="uwp-bs-modal fade show">\
                <div class="uwp-bs-modal-dialog">\
                <div class="uwp-bs-modal-content">\
                <div class="uwp-bs-modal-header">\
                            <h4 class="uwp-bs-modal-title">\
                            Loading Form ...\
                        </h4>\
                        </div>\
                <div class="uwp-bs-modal-body">\
                <div class="uwp-bs-modal-loading-icon-wrap">\
                <div class="uwp-bs-modal-loading-icon"></div>\
                </div>\
                </div>\
                </div>\
                </div>\
                </div>\
                </div>');
            mod_shadow.remove();
        });
    });
}( jQuery, window ));

jQuery(window).load(function() {
    // Chosen selects
    if (jQuery("select.uwp_chosen_select").length > 0) {
        jQuery("select.uwp_chosen_select").chosen();
        jQuery("select.uwp_chosen_select_nostd").chosen({
            allow_single_deselect: 'true'
        });
    }
});


(function( $, window, undefined ) {
    $(document).ready(function() {
        var showChar = 100;
        var ellipsestext = "...";
        var moretext = "more";
        var lesstext = "less";
        $('.uwp_more').each(function() {
            var content = $(this).html();

            if(content.length > showChar) {

                var c = content.substr(0, showChar);
                var h = content.substr(showChar-1, content.length - showChar);

                var html = c + '<span class="uwp_more_ellipses">' + ellipsestext+ '&nbsp;</span><span class="uwp_more_content"><span>' + h + '</span>&nbsp;&nbsp;<a href="" class="uwp_more_link">' + moretext + '</a></span>';

                $(this).html(html);
            }

        });

        $(".uwp_more_link").click(function(){
            if($(this).hasClass("uwp_less")) {
                $(this).removeClass("uwp_less");
                $(this).html(moretext);
            } else {
                $(this).addClass("uwp_less");
                $(this).html(lesstext);
            }
            $(this).parent().prev().toggle();
            $(this).prev().toggle();
            return false;
        });
    });
}( jQuery, window ));


(function( $, window, undefined ) {

    var uwp_popup_type;
    var uwp_full_width;
    var uwp_full_height;
    var uwp_thumb_width;
    var uwp_thumb_height;
    var uwp_aspect_ratio;
    var uwp_crop_left;
    var uwp_crop_top;
    var uwp_crop_right;
    var uwp_crop_bottom;

    $(document).ready( function() {
        $( '.uwp-profile-modal-form-trigger' ).on( 'click', function( event ) {
            event.preventDefault();

            uwp_popup_type = $( this ).data( 'type' );

            // do something with the file here
            var data = {
                'action': 'uwp_ajax_image_crop_popup_form',
                'type': uwp_popup_type
            };

            var container = jQuery('#uwp-popup-modal-wrap');
            container.show();

            jQuery.post(ajaxurl, data, function(response) {
                $(document.body).append("<div id='uwp-modal-backdrop'></div>");
                container.replaceWith(response);
            });
        }); 
    });

    $(document).ready( function() {
        var file_frame; // variable for the wp.media file_frame

        // attach a click event (or whatever you want) to some element on your page
        $( '.uwp-profile-modal-trigger' ).on( 'click', function( event ) {
            event.preventDefault();

            uwp_popup_type = $( this ).data( 'type' );

            // if the file_frame has already been created, just reuse it
            if ( file_frame ) {
                file_frame.open();
                return;
            }

            file_frame = wp.media.frames.file_frame = wp.media({
                title: $( this ).data( 'uploader_title' ),
                button: {
                    text: $( this ).data( 'uploader_button_text' )
                },
                multiple: false, // set this to true for multiple file selection
                /*
                 * Here is where the main magic happens.
                 *
                 * We take the type, e.g. video, image, audio,
                 * and we send it to library.type which only
                 * shows the files of that type.
                 */
                library: { type : "image" }
            });

            file_frame.on( 'select', function() {
                attachment = file_frame.state().get('selection').first().toJSON();

                // do something with the file here
                var data = {
                    'action': 'uwp_ajax_image_crop_popup',
                    'image_url': attachment.url,
                    'type': uwp_popup_type
                };

                jQuery.post(ajaxurl, data, function(response) {
                    resp = JSON.parse(response);
                    uwp_full_width = resp['uwp_full_width'];
                    uwp_full_height = resp['uwp_full_height'];
                    uwp_thumb_width = resp['uwp_thumb_width'];
                    uwp_thumb_height = resp['uwp_thumb_height'];
                    uwp_aspect_ratio = resp['uwp_aspect_ratio'];
                    uwp_crop_left = resp['uwp_crop_left'];
                    uwp_crop_top = resp['uwp_crop_top'];
                    uwp_crop_right = resp['uwp_crop_right'];
                    uwp_crop_bottom = resp['uwp_crop_bottom'];

                    $(document.body).append("<div id='uwp-modal-backdrop'></div>");
                    jQuery('#uwp-popup-modal-wrap').html(resp['uwp_popup_content']).find('#uwp-'+uwp_popup_type+'-to-crop').Jcrop({
                        // onChange: showPreview,
                        onSelect: updateCoords,
                        aspectRatio: uwp_aspect_ratio,
                        setSelect: [ uwp_crop_left, uwp_crop_top, uwp_crop_right, uwp_crop_bottom ],
                        trueSize: [uwp_thumb_width,uwp_thumb_height]
                    });
                });

            });

            file_frame.open();
        });
    });

    function updateCoords(c) {
        jQuery('#'+uwp_popup_type+'-x').val(c.x);
        jQuery('#'+uwp_popup_type+'-y').val(c.y);
        jQuery('#'+uwp_popup_type+'-w').val(c.w);
        jQuery('#'+uwp_popup_type+'-h').val(c.h);
    }

    function showPreview(coords) {
        if ( parseInt(coords.w) > 0 ) {
            var fw = uwp_full_width;
            var fh = uwp_full_height;
            var rx = fw / coords.w;
            var ry = fh / coords.h;

            jQuery( '#uwp-'+uwp_popup_type+'-crop-preview' ).css({
                width: Math.round(rx * uwp_thumb_width) + 'px',
                height: Math.round(ry * uwp_thumb_height) + 'px',
                marginLeft: '-' + Math.round(rx * coords.x) + 'px',
                marginTop: '-' + Math.round(ry * coords.y) + 'px'
        });
        }
    }

    $(document).ready(function() {
        $("#uwp_upload_file_remove").click(function(event){
            event.preventDefault();

            var htmlvar =  $( this ).data( 'htmlvar' );
            
            var data = {
                'action': 'uwp_upload_file_remove',
                'htmlvar': htmlvar
            };

            jQuery.post(ajaxurl, data, function(response) {
                $(".uwp_upload_file_preview").remove();
                $("#uwp_upload_file_remove").remove();
            });
        });
    });

}( jQuery, window ));