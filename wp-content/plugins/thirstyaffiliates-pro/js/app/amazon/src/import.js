/* global ajaxurl , ta_amazon_args */

import $ from "jquery";


/**
 * Construct import popup fields.
 *
 * @since 1.0.0
 *
 * @return array Array of input fields in string format (Format as expected by vex dialog).
 */
export function construct_import_popup_fields() {

    return [
        "<div class='vex-custom-field-wrapper'><label id='product-asin' class='main-label'></label></div>",
        "<div class='vex-custom-field-wrapper'>" +
            "<label for='azon-link-name' class='main-label'>" + ta_amazon_args.i18n_link_name + "</label>" +
            "<div clas='vex-custom-input-wrapper'>" +
                "<input type='text' id='azon-link-name' value='' autocomplete='off' />" +
            "</div>" +
        "</div>",
        "<div class='vex-custom-field-wrapper'>" +
            "<label for='azon-link-destination-url' class='main-label'>" + ta_amazon_args.i18n_link_url + "</label>" +
            "<p class='desc'>" + ta_amazon_args.i18n_link_protocol_required + "</p>" +
            "<div clas='vex-custom-input-wrapper'>" +
                "<input type='text' id='azon-link-destination-url' value='' autocomplete='off' />" +
            "</div>" +
        "</div>",
        "<div class='vex-custom-field-wrapper'>" +
            "<div clas='vex-custom-input-wrapper'>" +
                "<input type='checkbox' id='azon-nofollow-link' autocomplete='off' /><label for='azon-nofollow-link'>" + ta_amazon_args.i18n_no_follow_link + "</label>" +
            "</div>" +
        "</div>",
        "<div class='vex-custom-field-wrapper'>" +
            "<div clas='vex-custom-input-wrapper'>" +
                "<input type='checkbox' id='azon-open-in-new-tab' autocomplete='off' /><label for='azon-open-in-new-tab'>" + ta_amazon_args.i18n_open_link_new_tab + "</label>" +
            "</div>" +
        "</div>",
        "<div class='vex-custom-field-wrapper'>" +
            "<label class='main-label'>" + ta_amazon_args.i18n_redirection_type + "</label>" +
            "<ul id='redirection-types'>" +
                "<li><input type='radio' id='azon-link-destination-url-301' name='azon-link-destination-url' value='301' autocomplete='off' /><label for='azon-link-destination-url-301'>" + ta_amazon_args.i18n_301_permanent + "</label></li>" +
                "<li><input type='radio' id='azon-link-destination-url-302' name='azon-link-destination-url' value='302' autocomplete='off' /><label for='azon-link-destination-url-302'>" + ta_amazon_args.i18n_302_temporary + "</label></li>" +
                "<li><input type='radio' id='azon-link-destination-url-307' name='azon-link-destination-url' value='307' autocomplete='off' /><label for='azon-link-destination-url-307'>" + ta_amazon_args.i18n_307_temporary_alternative + "</label></li>" +
            "</ul>" +
        "</div>",
        "<div class='vex-custom-field-wrapper'>" +
            "<div clas='vex-custom-input-wrapper'>" +
                "<label for='azon-import-image' class='main-label'>" + ta_amazon_args.i18n_import_images + "</label>" +
                "<ul id='link-images'></ul>" +
            "</div>" +
        "</div>",
    ];

}

/**
 * Prepopulate the import popup form with selected product data.
 *
 * @since 1.0.0
 *
 * @param object $vex_input_form Jquery object.
 * @param object product_data    Selected product data.
 */
export function prepopulate_import_popup_form( $vex_input_form , product_data ) {

    let image_lists = "";

    $vex_input_form.find( "#product-asin" ).html( "ASIN : <span style='font-weight: normal;'>" + product_data.asin + "</span>" );
    $vex_input_form.find( "#azon-link-name" ).val( product_data.title.raw );
    $vex_input_form.find( "#azon-link-destination-url" ).val( product_data[ "link-url" ] );

    if ( ta_amazon_args.option_no_follow === "yes" )
        $vex_input_form.find( "#azon-nofollow-link" ).attr( "checked" , "checked" );

    if ( ta_amazon_args.option_new_window === "yes" )
        $vex_input_form.find( "#azon-open-in-new-tab" ).attr( "checked" , "checked" );

    $vex_input_form.find( "#azon-link-destination-url-" + ta_amazon_args.option_redirect_type ).attr( "checked" , "checked" );

    let small_checked = ta_amazon_args.option_import_images && ta_amazon_args.option_import_images.find( ( item ) => { return item === "small"; } ) ? "checked='checked'" : "";
    if ( product_data.image.raw.small )
        image_lists += `<li><input type='checkbox' id='small-image' value='${ product_data.image.raw.small.url }' ${ small_checked } autocomplete='off'><label for='small-image'><a href='${ product_data.image.raw.small.url }' target='_blank'>${ ta_amazon_args.i18n_small }</a> (${ product_data.image.raw.small.width }px X ${ product_data.image.raw.small.height }px)</label></li>`;

    let medium_checked = ta_amazon_args.option_import_images && ta_amazon_args.option_import_images.find( ( item ) => { return item === "medium"; } ) ? "checked='checked'" : "";
    if ( product_data.image.raw.medium )
        image_lists += `<li><input type='checkbox' id='medium-image' value='${ product_data.image.raw.medium.url }' ${ medium_checked } autocomplete='off'><label for='medium-image'><a href='${ product_data.image.raw.medium.url }' target='_blank'>${ ta_amazon_args.i18n_medium }</a> (${ product_data.image.raw.medium.width }px X ${ product_data.image.raw.medium.height }px)</label></li>`;

    let large_checked = ta_amazon_args.option_import_images && ta_amazon_args.option_import_images.find( ( item ) => { return item === "large"; } ) ? "checked='checked'" : "";
    if ( product_data.image.raw.large )
        image_lists += `<li><input type='checkbox' id='large-image' value='${ product_data.image.raw.large.url }' ${ large_checked } autocomplete='off'><label for='large-image'><a href='${ product_data.image.raw.large.url }' target='_blank'>${ ta_amazon_args.i18n_large }</a> (${ product_data.image.raw.large.width }px X ${ product_data.image.raw.large.height }px)</label></li>`;

    $vex_input_form.find( "#link-images" ).html( image_lists );

}

/**
 * Set import popup form to processing mode.
 *
 * @since 1.0.0
 *
 * @param object $vex_popup_form jQuery object.
 */
export function import_popup_form_processing_mode( $vex_popup_form ) {

    $vex_popup_form.find( "input , button" ).attr( "disabled" , "disabled" );

    $vex_popup_form.find( ".vex-dialog-buttons .vex-dialog-button-primary" ).before( "<span class='spinner' style='margin-left:4px; margin-right: 0; visibility: visible;'></span>" );

}

/**
 * Set import popup form to normal mode.
 *
 * @param object $vex_popup_form jQuery object.
 */
export function import_popup_form_normal_mode( $vex_popup_form ) {

    $vex_popup_form.find( "input , button" ).removeAttr( "disabled" );

    $vex_popup_form.find( ".spinner" ).remove();

}

/**
 * Make bulk action toolbar in processing mode.
 *
 * @since 1.0.0
 *
 * @param object $bulk_action_toolbar Bulk action toolbar.
 */
export function bulk_action_toolbar_processing_mode( $bulk_action_toolbar ) {

    $bulk_action_toolbar.find( "select , input" ).attr( "disabled" , "disabled" );

}

/**
 * Make bulk action toolbar in normal mode.
 *
 * @since 1.0.0
 *
 * @param object $bulk_action_toolbar Bulk action toolbar.
 */
export function bulk_action_toolbar_normal_mode( $bulk_action_toolbar ) {

    $bulk_action_toolbar.find( "select , input" ).removeAttr( "disabled" );
    $bulk_action_toolbar.find( "select" ).val( "" );

}

/**
 * Import amazon product as an affiliate link.
 *
 * @since 1.0.0
 *
 * @param object vex  Vex dialog object.
 * @param object $tr  Table row jquery object.
 * @param string asin Product ASIN.
 * @param object product_data Product data.
 */
export function import_product_as_affiliate_link( vex , $tr , asin , product_data ) {

    let $vex_popup_form = $( ".vex-dialog-form" );

    import_popup_form_processing_mode( $vex_popup_form );

    let vex_input       = $vex_popup_form.find( ".vex-dialog-input" ),
        link_data       = {
            "search-keywords" : product_data[ "search-keywords" ],
            "search-index"    : product_data[ "search-index" ],
            "search-endpoint" : product_data[ "search-endpoint" ],
            "asin"            : asin,
            "link-name"       : vex_input.find( "#azon-link-name" ).val(),
            "link-url"        : vex_input.find( "#azon-link-destination-url" ).val(),
            "no-follow"       : vex_input.find( "#azon-nofollow-link" ).is( ":checked" ) ? "yes" : "no",
            "new-window"      : vex_input.find( "#azon-open-in-new-tab" ).is( ":checked" ) ? "yes" : "no",
            "redirect-type"   : vex_input.find( "input[name='azon-link-destination-url']:checked" ).val(),
            "link-images"     : {}
        };

    if ( vex_input.find( "#small-image" ) && vex_input.find( "#small-image" ).is( ":checked" ) )
        link_data[ "link-images" ][ "small" ] = vex_input.find( "#small-image" ).val();

    if ( vex_input.find( "#medium-image" ) && vex_input.find( "#medium-image" ).is( ":checked" ) )
        link_data[ "link-images" ][ "medium" ] = vex_input.find( "#medium-image" ).val();

    if ( vex_input.find( "#large-image" ) && vex_input.find( "#large-image" ).is( ":checked" ) )
        link_data[ "link-images" ][ "large" ] = vex_input.find( "#large-image" ).val();

    $.ajax( {
        url      : ajaxurl,
        type     : "POST",
        data     : { action : "tap_import_link" , "link-data" : link_data , "ajax-nonce" : ta_amazon_args.nonce_import_link },
        dataType : "json"
    } )
    .done( function( data ) {

        import_popup_form_normal_mode( $vex_popup_form );

        if ( data.status === "success" ) {

            ta_amazon_args.azon_link_data.push( {
                "link_id"     : data.link_id,
                "asin"        : link_data.asin,
                "admin_url"   : data.admin_url,
                "cloaked_url" : data.cloaked_url
            } );

            $tr.attr( "data-link-id" , data.link_id );

            $tr.find( ".edit-link" ).attr( "href" , data.admin_url );

            $tr.addClass( "imported" );

            vex.closeAll();

        } else {

            vex.dialog.alert( data.error_msg );
            console.log( data );

        }

    } )
    .fail( function( jqxhr ) {

        import_popup_form_normal_mode( $vex_popup_form );

        vex.dialog.alert( ta_amazon_args.i18n_failed_to_import_link );
        console.log( jqxhr );

    } );

}

/**
 * Quick import product as an affiliate link.
 *
 * @since 1.0.0
 *
 * @param object product_data Product data.
 * @param object $tr          Table row jquery object.
 * @param object bulk_import  Optional parameter, basically the purpose for this is to aid when to set the bulk action control back to normal mode ( after all selected links have been imported ).
 */
export function quick_import_product_as_affiliate_link( product_data , $tr , bulk_import ) {

    $tr.addClass( "processing" ).find( ".controls-column" ).addClass( "quick-importing" );

    let link_data = {
        "search-keywords" : product_data[ "search-keywords" ],
        "search-index"    : product_data[ "search-index" ],
        "search-endpoint" : product_data[ "search-endpoint" ],
        "asin"            : product_data.asin,
        "link-name"       : product_data.title.raw,
        "link-url"        : product_data[ "link-url" ],
        "no-follow"       : ta_amazon_args.option_no_follow,
        "new-window"      : ta_amazon_args.option_new_window,
        "redirect-type"   : ta_amazon_args.option_redirect_type,
        "link-images"     : {}
    };

    if ( ta_amazon_args.option_import_images && ta_amazon_args.option_import_images.find( ( item ) => { return item === "small"; } ) && product_data.image.raw.small )
        link_data[ "link-images" ][ "small" ] = product_data.image.raw.small.url;

    if ( ta_amazon_args.option_import_images && ta_amazon_args.option_import_images.find( ( item ) => { return item === "medium"; } ) && product_data.image.raw.medium )
        link_data[ "link-images" ][ "medium" ] = product_data.image.raw.medium.url;

    if ( ta_amazon_args.option_import_images && ta_amazon_args.option_import_images.find( ( item ) => { return item === "large"; } ) && product_data.image.raw.large )
        link_data[ "link-images" ][ "large" ] = product_data.image.raw.large.url;

    $.ajax( {
        url      : ajaxurl,
        type     : "POST",
        data     : { action : "tap_import_link" , "link-data" : link_data , "ajax-nonce" : ta_amazon_args.nonce_import_link },
        dataType : "json"
    } )
    .done( function( data ) {

        if ( data.status === "success" ) {

            ta_amazon_args.azon_link_data.push( {
                "link_id"     : data.link_id,
                "asin"        : link_data.asin,
                "admin_url"   : data.admin_url,
                "cloaked_url" : data.cloaked_url
            } );

            $tr.attr( "data-link-id" , data.link_id );

            $tr.find( ".edit-link" ).attr( "href" , data.admin_url );

            $tr.addClass( "imported" );

        } else {

            if ( !bulk_import )
                console.log( data );

        }

    } )
    .fail( function( jqxhr ) {

        if ( !bulk_import )
            console.log( jqxhr );

    } )
    .always( function() {

        $tr.removeClass( "processing" ).find( ".controls-column" ).removeClass( "quick-importing" );

        if ( bulk_import )
            bulk_import.counter += 1;

    } );

}

/**
 * Delete imported link.
 *
 * @since 1.0.0
 *
 * @param int link_id         Affiliate link id.
 * @param object $tr          Table row jquery object.
 * @param object vex          Vex dialog object.
 * @param object $vex_buttons Jquery object of vex dialog buttons form fields.
 * @param object bulk_delete  Optional parameter, basically the purpose for this is to aid when to set the bulk action control back to normal mode ( after all selected imported links have been deleted ).
 */
export function delete_imported_affiliate_link( link_id , $tr , vex , $vex_buttons , bulk_delete ) {

    if ( $vex_buttons.find( ".spinner" ).length <= 0 ) {

        $vex_buttons.find( "button" ).attr( "disabled" , "disabled" );
        $vex_buttons.find( ".vex-dialog-button-primary" ).before( "<span class='spinner' style='margin-left:4px; margin-right: 0; visibility: visible;'></span>" );

    }

    $.ajax( {
        url      : ajaxurl,
        type     : "POST",
        data     : { action : "tap_delete_amazon_imported_link" , "link-id" : link_id , "ajax-nonce" : ta_amazon_args.nonce_delete_amazon_imported_link },
        dataType : "json"
    } )
    .done( function( data ) {

        if ( data.status === "success" ) {

            let idx = ta_amazon_args.azon_link_data.findIndex( ( data_item ) => { return data_item.link_id == link_id; } );

            ta_amazon_args.azon_link_data.splice( idx , 1 );

            $tr.removeAttr( "data-link-id" );
            $tr.find( ".edit-link" ).removeAttr( "href" );

            $tr.removeClass( "imported" );

            if ( !bulk_delete ) {

                $vex_buttons.find( "button" ).removeAttr( "disabled" );
                $vex_buttons.find( ".spinner" ).remove();

                vex.closeAll();

            }

        } else {

            if ( !bulk_delete ) {

                vex.dialog.alert( data.error_msg );
                console.log( data );

            }

        }

    } )
    .fail( function( jqxhr ) {

        if ( !bulk_delete ) {

            vex.dialog.alert( ta_amazon_args.i18n_failed_delete_link );
            console.log( jqxhr );

        }

    } )
    .always( function() {

        if ( bulk_delete )
            bulk_delete.counter += 1;

    } );

}
