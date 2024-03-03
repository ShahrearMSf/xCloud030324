/* global jQuery, ajaxurl, tap_input_link_picker_params */

/**
 * Initialize input link picker events.
 *
 * @since 1.4.0
 */
export default function input_link_picker() {

    wc_external_product_support();

    jQuery( "body" ).on( "click" , ".tap-input-link-picker" , display_popup );
    jQuery( "body" ).on( "click" , ".tap-input-link-picker-modal .close" , close_popup );
    jQuery( "body" ).on( "click" , ".tap-input-link-picker-modal" , clicked_outside_modal );
    jQuery( "body" ).on( "click" , ".tap-input-link-picker-modal .results li" , select_affiliate_link );
    jQuery( "body" ).on( "click" , ".tap-input-link-picker-modal button.insert-affiliate-link" , insert_affiliate_link );
    jQuery( "body" ).on( "keyup" , ".tap-input-link-picker-modal #tap_input_search_value" , trigger_search );
    jQuery( "body" ).on( "click" , ".tap-input-link-picker-modal .results a.load-more" , load_more_affiliate_links );

    jQuery( window ).on( "resize" , reposition_ta_button );
    jQuery( window ).trigger( "resize" );
}

/**
 * Add support for WooCommerce external product.
 *
 * @since 1.4.0
 */
function wc_external_product_support() {

    const $parent = document.querySelector( "p._product_url_field" ),
        button  = `
            <button type="button" class="button tap-input-link-picker" data-target="#_product_url">
                <img src="${ tap_input_link_picker_params.button_img }">
            </button>
        `;

    if ( ! jQuery( $parent ).length ) return;

    jQuery( $parent ).addClass( "tap-input-linker-attached" ).append( button );
}

/**
 * Display popup script.
 *
 * @since 1.4.0
 */
function display_popup() {

    if ( jQuery( "body .tap-input-link-picker-modal" ).length ) {
        jQuery( "body" ).find( ".tap-input-link-picker-modal" ).show();
        return;
    }

    const $button = jQuery(this),
        target    = $button.data( "target" ),
        markup    = `
            <div class="tap-input-link-picker-modal" data-target="${ target }">
                <div class="modal-inner">
                    <div class="header">
                        <h3>${ tap_input_link_picker_params.modal_heading }</h3>
                        <span class="close">&times;</span>
                    </div>
                    <div class="content">
                        <div class="inner-relative">
                            <div class="thirstylink-search">
                                <label>${ tap_input_link_picker_params.search_label }</label>
                                <input type="text" id="tap_input_search_value">
                            </div>
                            <div class="results" data-count="0">
                                <ul></ul>
                                <a href="javascript:void(0);" class="button load-more" data-paged="2">
                                    ${ tap_input_link_picker_params.load_more }
                                </a>
                            </div>
                        </div>
                        <div class="overlay">
                            <div class="flex">
                                <img src="${ tap_input_link_picker_params.overlay_img }">
                            </div>
                        </div>
                    </div>
                    <div class="footer">
                        <button class="button close">${ tap_input_link_picker_params.cancel_insert }</button>
                        <button class="button-primary insert-affiliate-link" disabled>${ tap_input_link_picker_params.insert_link }</button>
                        <input type="hidden" id="tap_input_linker_value" value="">
                    </div>
                </div>
            </div>
        `;

    jQuery( "body" ).append( markup );
    jQuery( "body" ).find( ".tap-input-link-picker-modal" ).show();
    search_affiliate_links();
}

/**
 * Close popup script.
 *
 * @since 1.4.0
 */
function close_popup() {

    const $button = jQuery(this),
        $modal    = $button.closest( ".tap-input-link-picker-modal" );

    $modal.hide();
}

/**
 * Close popup script when clicked outside the modal content area.
 *
 * @since 1.4.0
 */
function clicked_outside_modal(e) {

    const $modal = jQuery(this);

    if ( ! $modal.is( e.target ) ) return;

    $modal.hide();
}

/**
 * Trigger affiliate link search on keyup.
 *
 * @since 1.4.0
 */
function trigger_search() {

    const $search = jQuery(this),
        $modal    = jQuery( ".tap-input-link-picker-modal" ),
        $results  = $modal.find( ".modal-inner .results" ),
        keyword   = $search.val();

    if ( keyword.length < 3 && keyword.length !== 0 )
        return;

    $results.data( "count" , 0 );
    search_affiliate_links( keyword );
}

/**
 * Search affiliate links.
 *
 * @since 1.4.0
 */
function search_affiliate_links( keyword = "" , paged = 1 ) {

    const $modal = jQuery( ".tap-input-link-picker-modal" ),
        $results = $modal.find( ".modal-inner .results" ),
        $overlay = $modal.find( ".modal-inner .overlay" ),
        $insert  = $modal.find( ".insert-affiliate-link" );

    let markup = "",
        count  = parseInt( $results.data( "count" ) ),
        temp;

    $overlay.show();
    $results.find( "li" ).removeClass( "selected" );
    $insert.prop( "disabled" , true );

    jQuery.post( ajaxurl , {
        action  : "tap_input_link_picker_search",
        keyword : keyword,
        paged   : paged,
        nonce   : tap_input_link_picker_params.nonce
    }, ( response ) => {

        if ( response.status == "success" && Object.keys( response.data ).length > 0 ) {

            for ( let link_id in response.data ) {

                let link = response.data[ link_id ];

                temp = `
                    <li data-permalink="${ link.permalink }">
                        ${ link.name }
                        <span class="slug">[${ link.slug }]</span>
                    </li>
                `;

                markup += temp;
            }

            count = count + response.count;
            $results.data( "count" , count );

            if( count < response.total )
                $results.find( "a.load-more" ).css( "display" , "inline-block" );
            else
                $results.find( "a.load-more" ).hide();

        } else {

            markup = `<li>${ tap_input_link_picker_params.no_aff_links }</li>`;
            $results.find( "a.load-more" ).hide();
        }

        $modal.find( "a.load-more" ).data( "paged" , 2 );
        $results.find( "ul" ).html( markup );
        $overlay.hide();

    } , "json" );
}

/**
 * Load more affiliate links.
 *
 * @since 1.4.0
 */
function load_more_affiliate_links(e) {

    e.preventDefault();

    const $button = jQuery(this),
        $modal    = $button.closest( ".tap-input-link-picker-modal" ),
        $search   = $modal.find( "input#tap_input_search_value" ),
        $results  = $modal.find( ".results" ),
        keyword   = $search.val(),
        paged     = parseInt( $button.data( "paged" ) ),
        $overlay  = $modal.find( ".modal-inner .overlay" ),
        $insert   = $modal.find( ".insert-affiliate-link" );

    let markup = "",
        count  = parseInt( $results.data( "count" ) ),
        temp;

    $overlay.show();
    $results.find( "li" ).removeClass( "selected" );
    $insert.prop( "disabled" , true );

    jQuery.post( ajaxurl , {
        action  : "tap_input_link_picker_search",
        keyword : keyword,
        paged   : paged,
        nonce   : tap_input_link_picker_params.nonce
    }, ( response ) => {

        if ( response.status == "success" && Object.keys( response.data ).length > 0 ) {

            for ( let link_id in response.data ) {

                let link = response.data[ link_id ];

                temp = `
                    <li data-permalink="${ link.permalink }">
                        ${ link.name }
                        <span class="slug">[${ link.slug }]</span>
                    </li>
                `;

                markup += temp;
            }

            $results.find( "ul" ).append( markup );
            $button.data( "paged" , paged + 1 );

            count = count + response.count;
            $results.data( "count" , count );

            if( count >= response.total )
                $button.hide();

        } else {

            $results.append( `<div class="no-more">${ tap_input_link_picker_params.no_more_links }</div>` );
            $button.hide();

            setTimeout( () => $results.find( ".no-more" ).fadeOut() , 3000 );
        }

        $overlay.hide();

    } , "json" );
}

/**
 * Select affiliate link.
 *
 * @since 1.4.0
 */
function select_affiliate_link() {

    const $link  = jQuery(this),
        $modal   = $link.closest( ".tap-input-link-picker-modal" ),
        $results = $modal.find( ".results" ),
        $input   = $modal.find( "#tap_input_linker_value" ),
        $insert  = $modal.find( ".insert-affiliate-link" );

    $results.find( "li" ).removeClass( "selected" );
    $link.addClass( "selected" );
    $input.val( $link.data( "permalink" ) );
    $insert.prop( "disabled" , false );
}

/**
 * Insert affiliate link.
 *
 * @since 1.4.0
 */
function insert_affiliate_link() {

    const $button = jQuery(this),
        $modal    = $button.closest( ".tap-input-link-picker-modal" ),
        $input    = $modal.find( "#tap_input_linker_value" ),
        target    = $modal.data( "target" ),
        $insert   = $modal.find( ".insert-affiliate-link" );

    jQuery( target ).val( $input.val() );
    $insert.prop( "disabled" , true );
    $modal.fadeOut( "fast" );
}

/**
 * Reposition TA button.
 *
 * @since 1.4.0
 */
function reposition_ta_button() {

    const metabox     = document.querySelector( "#woocommerce-product-data" ),
        product_url   = metabox.querySelector( "#_product_url" ),
        ta_button     = metabox.querySelector( "button.tap-input-link-picker" ),
        metaboxOffset = jQuery( metabox ).offset().left + jQuery( metabox ).width(),
        prodUrlOffset = jQuery( product_url ).offset().left + jQuery( product_url ).width();

    let right = metaboxOffset - prodUrlOffset;

    jQuery( ta_button ).css( "right" , right - 42 );
}