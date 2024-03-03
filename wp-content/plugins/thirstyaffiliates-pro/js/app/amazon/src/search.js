/* global ajaxurl , ta_amazon_args */

import $ from "jquery";
import each from "lodash.foreach";
import get from "lodash.get";


/**
 * Search amazon product advertisement api.
 *
 * @since 1.0.0
 *
 * @param {string} search_keywords  Terms to search.
 * @param {string} search_index     A.K.A category.
 * @param {string} search_endpoint  Country code.
 * @param {number} item_page      Item page number.
 * @return object jquery promise object.
 */
export function amazon_api_search( search_keywords , search_index , search_endpoint, item_page ) {

    return $.ajax( {
        url      : ajaxurl,
        type     : "POST",
        data     : {
            action : "tap_amazon_product_advertisement_api_search",
            "search-keywords" : search_keywords ,
            "search-index" : search_index ,
            "search-endpoint" : search_endpoint ,
            "item-page" : item_page,
            "ajax-nonce" : ta_amazon_args.nonce_amazon_product_advertisement_api_search
        },
        dataType : "json"
    } );

}

export function extract_results_to_datatables_data ( data, search_keywords , search_index , search_endpoint ) {
    let d = [],
        results = get( data, "results", [] );

    each( results, result => {

        let price = get( result, "Offers.Listings.0.Price.DisplayAmount", "" );

        // Determine if we need to skip this or not
        if ( ta_amazon_args.option_hide_empty_priced_products === "yes" ) {

            let raw_price = parseFloat( get( result, "Offers.Listings.0.Price.Amount", "" ) );

            if ( !price || ( !isNaN( raw_price ) && raw_price <= 0 ) ) {
                return;
            }

        }

        let ASIN            = get( result, "ASIN", "" ),
            title           = get( result, "ItemInfo.Title.DisplayValue", "" ),
            link_url        = get( result, "DetailPageURL", "" ),
            total_stock     = 0,
            images_raw_data = {};

        // Construct images raw data
        if ( get( result, "Images.Primary.Small.URL", "" ) ) {
            images_raw_data["small"] = {
                "url": get( result, "Images.Primary.Small.URL", "" ),
                "width": get( result, "Images.Primary.Small.Width", "" ),
                "height": get( result, "Images.Primary.Small.Height", "" )
            };
        }

        if ( get( result, "Images.Primary.Medium.URL", "" ) ) {
            images_raw_data["medium"] = {
                "url": get( result, "Images.Primary.Medium.URL", "" ),
                "width": get( result, "Images.Primary.Medium.Width", "" ),
                "height": get( result, "Images.Primary.Medium.Height", "" )
            };
        }

        if ( get( result, "Images.Primary.Large.URL", "" ) ) {
            images_raw_data["large"] = {
                "url": get( result, "Images.Primary.Large.URL", "" ),
                "width": get( result, "Images.Primary.Large.Width", "" ),
                "height": get( result, "Images.Primary.Large.Height", "" )
            };
        }

        // Total up the available stock
        each ( get ( result, "Offers.Summaries", [] ), summary => {
            total_stock += get ( summary, "OfferCount", 0 );
        });

        // Construct proper column controls
        let column_controls   = "<span class='actions'>" +
            "<a class='action import' title='" + ta_amazon_args.i18n_import + "'></a>" +
            "<a class='action quick-import' title='" + ta_amazon_args.i18n_quick_import + "'></a>" +
            "</span>" +
            "<span class='processing'>" +
            "<span class='spinner'></span>" +
            "<span class='processing-msg'>" + ta_amazon_args.i18n_quick_importing + "</span>" +
            "</span>" +
            "<div class='imported-product-actions'>"+
            "<p class='desc'>" + ta_amazon_args.i18n_imported_as_affiliated_link + "</p>" +
            "<a class='edit-link' href='{admin_url}' target='_blank'>" + ta_amazon_args.i18n_edit + "</a>" +
            "<span class='separator'>|</span>" +
            "<a class='visit-link' href='" + link_url + "' target='_blank'>" + ta_amazon_args.i18n_visit + "</a>" +
            "<span class='separator'>|</span>" +
            "<a class='delete-link' target='_blank'>" + ta_amazon_args.i18n_delete + "</a>" +
            "</div>",
            imported_link_data = ta_amazon_args.azon_link_data.find( ( data_item ) => { return data_item.asin === ASIN; } );

        if ( imported_link_data !== undefined )
            column_controls = column_controls.replace( "{admin_url}" , imported_link_data.admin_url );

        d.push( {
            "search-keywords" : search_keywords,
            "search-index"    : search_index,
            "search-endpoint" : search_endpoint,
            "asin"            : ASIN,
            "link-url"        : link_url,
            "check-column"    : `<input id="cb-select-${ ASIN }" class="cb" type="checkbox" name="product_asin[]" value="${ ASIN }">`,
            "image"           : {
                "raw"       : images_raw_data,
                "formatted" : `<img src="${ images_raw_data.small ? images_raw_data.small.url : images_raw_data.medium ? images_raw_data.medium.url : images_raw_data.large ? images_raw_data.large.url : "" }">`
            },
            "title"           : {
                "raw"       : title,
                "formatted" : `<a href="${ link_url }" target="_blank">${ title }</a>`
            },
            "price"           : price,
            "item-stock"      : total_stock,
            "sales-rank"      : get( result, "BrowseNodeInfo.WebsiteSalesRank.SalesRank", "" ),
            "controls-column" : column_controls

        } );

    } );

    return d;
}

/**
 * Construct search indexes string for the current active indexes.
 *
 * @param object active_search_indexes List of active amazon search indexes.
 * @param object List that contains the string equivalent (wrapped in option tag) for the active indexes.
 */
export function construct_active_search_indexes_str( active_search_indexes ) {

    let active_search_indexes_str = {};

    for ( let country_code in active_search_indexes ) {

        active_search_indexes_str[ country_code ] = "";

        for ( let index in active_search_indexes[ country_code ] )
            active_search_indexes_str[ country_code ] += `<option value="${index}">${active_search_indexes[ country_code ][ index ]}</option>`;

    }

    return active_search_indexes_str;

}

/**
 * Save last used search endpoint.
 *
 * @since 1.0.0
 *
 * @param string country_code Country code.
 * @return object jquery promise object.
 */
export function save_last_used_search_endpoint( country_code ) {

    return $.ajax( {
        "url"      : ajaxurl,
        "type"     : "POST",
        "data"     : { action : "tap_set_last_used_search_endpoint" , "amazon-search-endpoint" : country_code , "ajax-nonce" : ta_amazon_args.nonce_set_last_used_search_endpoint },
        "dataType" : "json"
    } );

}

/**
 * Put search controls in processing mode.
 *
 * @since 1.0.0
 *
 * @param {jQuery} $search_controls jquery object.
 * @param {jQuery} $load_more jquery object.
 */
export function search_controls_processing_mode( $search_controls, $load_more ) {

    $load_more.find( ".tap-amazon-button-text" ).hide();
    $search_controls.find( ".button , .field" ).add( $load_more ).attr( "disabled" , "disabled" );
    $search_controls.add( $load_more ).find( ".spinner" ).css( "visibility" , "visible" );
    $load_more.find( ".spinner" ).show();

}

/**
 * Put search controls in normal mode.
 *
 * @since 1.0.0
 *
 * @param {jQuery} $search_controls jquery object.
 * @param {jQuery} $load_more jquery object.
 */
export function search_controls_normal_mode( $search_controls, $load_more ) {

    $load_more.find( ".spinner" ).hide();
    $search_controls.find( ".button , .field" ).add( $load_more ).removeAttr( "disabled" );
    $search_controls.add( $load_more ).find( ".spinner" ).css( "visibility" , "hidden" );
    $load_more.find( ".tap-amazon-button-text" ).show();

}
