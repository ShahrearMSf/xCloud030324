/* global ajaxurl , ta_amazon_args */

import $ from "jquery";


/**
 * Construct amazon table columns data.
 *
 * @since 1.0.0
 *
 * @param object $screen_option Jquery object.
 * @return object Amazon table columns data.
 */
export function construct_amazon_table_columns_data( $screen_option ) {

    return {
        "price"      : $screen_option.find( "#tap-column-price" ).is( ":checked" )      ? "yes" : "no",
        "item-stock" : $screen_option.find( "#tap-column-item-stock" ).is( ":checked" ) ? "yes" : "no",
        "sales-rank" : $screen_option.find( "#tap-column-sales-rank" ).is( ":checked" ) ? "yes" : "no"
    };

}

/**
 * Save amazon table columns data.
 *
 * @since 1.0.0
 *
 * @param object amazon_table_columns_data Amazon table columns data.
 * @return object Jquery promise object.
 */
export function save_amazon_table_columns_data( amazon_table_columns_data ) {

    return $.ajax( {
        url      : ajaxurl,
        type     : "POST",
        data     : { action : "tap_set_amazon_table_visible_columns" , "amazon-table-columns" : amazon_table_columns_data , "ajax-nonce" : ta_amazon_args.nonce_amazon_table_visible_columns },
        dataType : "json"
    } );

}

/**
 * Toggle amazon table columns.
 *
 * @since 1.0.0
 *
 * @param object $amazon_datatable_handle  DataTable object.
 * @param object amazon_table_columns_data Amazon table columns data.
 */
export function toggle_amazon_table_columns( $amazon_datatable_handle , amazon_table_columns_data ) {

    if ( amazon_table_columns_data.hasOwnProperty( "price" ) && amazon_table_columns_data[ "price" ] === "yes" )
        $amazon_datatable_handle.column( 3 ).visible( true );
    else
        $amazon_datatable_handle.column( 3 ).visible( false );

    if ( amazon_table_columns_data.hasOwnProperty( "item-stock" ) && amazon_table_columns_data[ "item-stock" ] === "yes" )
        $amazon_datatable_handle.column( 4 ).visible( true );
    else
        $amazon_datatable_handle.column( 4 ).visible( false );

    if ( amazon_table_columns_data.hasOwnProperty( "sales-rank" ) && amazon_table_columns_data[ "sales-rank" ] === "yes" )
        $amazon_datatable_handle.column( 5 ).visible( true );
    else
        $amazon_datatable_handle.column( 5 ).visible( false );

}
