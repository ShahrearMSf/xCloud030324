/* global require , ta_amazon_args */

// Common Currency Symbol : https://gist.githubusercontent.com/Fluidbyte/2973986/raw/b0d1722b04b0a737aade2ce6e055263625a0b435/Common-Currency.json

import $ from "jquery";
import "datatables.net";
import vex from "vex-js";
import datatable_config from "./datatable-config";

import { construct_amazon_table_columns_data , save_amazon_table_columns_data , toggle_amazon_table_columns } from "./screen_option";
import { amazon_api_search , extract_results_to_datatables_data , construct_active_search_indexes_str ,
         save_last_used_search_endpoint , search_controls_processing_mode , search_controls_normal_mode ,  }     from "./search";
import { construct_import_popup_fields , prepopulate_import_popup_form , import_product_as_affiliate_link ,
         quick_import_product_as_affiliate_link , delete_imported_affiliate_link , bulk_action_toolbar_processing_mode , bulk_action_toolbar_normal_mode }   from "./import";

// Initialize vex
vex.registerPlugin( require( "vex-dialog" ) );
vex.defaultOptions.className = "vex-theme-plain";

import "./assets/styles/index.scss";

$( document ).ready( () => {

    /*
    |--------------------------------------------------------------------------
    | Initialization
    |--------------------------------------------------------------------------
    */

    let active_search_indexes     = $.parseJSON( $( "#azon" ).attr( "data-active-indexes" ) ),
        active_search_indexes_str = construct_active_search_indexes_str( active_search_indexes ),
        $screen_options           = $( "#tap-amazon-table-fields" ),
        $amazon_column_ajax       = null,
        $search_controls          = $( "#search-controls" ),
        $amazon_table             = $( "#search-results-table" ),
        $amazon_datatable_handle  = $amazon_table.DataTable( datatable_config( $amazon_table ) ),
        import_link_fields        = construct_import_popup_fields(),
        datatable_data            = [],
        last_search_query         = "",
        current_search_page       = 1,
        $load_more                = $( "<button type='button' class='button'>" )
                                        .append( $( "<span class='tap-amazon-button-text'>" ).text( ta_amazon_args.i18n_load_more) )
                                        .append( $( "<span class='spinner'>" ).hide() )
                                        .appendTo( $( "div.tap-amazon-load-more" ) );

    toggle_amazon_table_columns( $amazon_datatable_handle , ta_amazon_args.option_amazon_columns_data );

    $( "div.bulkactions" ).html( "<select id='bulk-action-selector' autocomplete='off'>" +
                                    "<option value=''>" + ta_amazon_args.i18n_bulk_actions + "</option>" +
                                    "<option value='import'>" + ta_amazon_args.i18n_import + "</option>" +
                                    "<option value='delete'>" + ta_amazon_args.i18n_delete_imported_link + "</option>" +
                                 "</select>" +
                                 "<input type='button' id='do-bulk-action' class='button action' value='" + ta_amazon_args.i18n_apply + "'>" );



    /*
    |--------------------------------------------------------------------------
    | Screen Options
    |--------------------------------------------------------------------------
    */

    $screen_options.find( ".column-check-field" ).change( function() {

        if ( $amazon_column_ajax )
            $amazon_column_ajax.abort();

        let amazon_table_columns_data = construct_amazon_table_columns_data( $screen_options );

        $amazon_column_ajax = save_amazon_table_columns_data( amazon_table_columns_data );

        toggle_amazon_table_columns( $amazon_datatable_handle , amazon_table_columns_data );

        $amazon_column_ajax = null;

    } );




    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    // Amazon Search
    function search_amazon_api() {

        search_controls_processing_mode( $search_controls, $load_more );

        let search_keywords = $.trim( $search_controls.find( "#search-keywords" ).val() ),
            search_index    = $.trim( $search_controls.find( "#search-index" ).val() ),
            search_endpoint = $.trim( $search_controls.find( "#search-endpoint" ).val() ),
            item_page       = current_search_page;

        if ( last_search_query !== search_keywords || current_search_page === 1) {
            item_page = current_search_page = 1;
            datatable_data = [];
            $amazon_datatable_handle.clear();
            $amazon_datatable_handle.draw();
        }

        last_search_query = search_keywords;

        if ( search_keywords === "" ) {

            vex.dialog.alert( ta_amazon_args.i18n_please_input_search_terms );
            search_controls_normal_mode( $search_controls, $load_more );
            return false;

        }

        amazon_api_search( search_keywords, search_index, search_endpoint, item_page )
            .done( function( data ) {

                if ( data.status === "success" ) {

                    let results = extract_results_to_datatables_data( data , search_keywords , search_index , search_endpoint );

                    datatable_data = datatable_data.concat( datatable_data, results );

                    $amazon_datatable_handle.rows.add( results );
                    $amazon_datatable_handle.draw();

                } else {

                    vex.dialog.alert( data.message );
                    console.log( data );

                }

            } )
            .fail( function( jqxhr ) {

                vex.dialog.alert( ta_amazon_args.i18n_failed_to_perform_search );
                console.log( jqxhr );

            } )
            .always( function() {

                search_controls_normal_mode( $search_controls, $load_more );

            } );

    }

    $search_controls.find( "#search-keywords" ).on( "keypress" , function( e ) {

        if ( e.keyCode == 13 ) {
            current_search_page = 1;
            search_amazon_api();
        }

    } );

    $search_controls.find( "#search-button" ).click( function () {
        current_search_page = 1;
        search_amazon_api();
    } );

    // Changing amazon search endpoint and indexes
    $search_controls.find( "#search-endpoint" ).change( function() {

        let country_code = $( this ).val();

        $search_controls.find( "#search-index" ).html( active_search_indexes_str[ country_code ] );

        save_last_used_search_endpoint( country_code );

    } );




    /*
    |--------------------------------------------------------------------------
    | Import
    |--------------------------------------------------------------------------
    */

    // Single Import
    $amazon_table.on( "click" , ".controls-column .import" , function() {

        let $tr          = $( this ).closest( "tr" ),
            asin         = $tr.attr( "data-asin" ),
            product_data = datatable_data.find( ( data_item ) => { return data_item.asin == asin; } );

        vex.dialog.open( {
            overlayClosesOnClick : false,
            escapeButtonCloses   : false,
            className            : "vex-theme-plain azon-import-popup",
            unsafeMessage        : "<h2 class='popup-title'>" + ta_amazon_args.i18n_import_link + "</h2>",
            input   : import_link_fields.join( "" ),
            afterOpen : () => {

                let $vex_input_form = $( ".vex-dialog-form .vex-dialog-input" );

                prepopulate_import_popup_form( $vex_input_form , product_data );

            },
            onSubmit: function( e ) { e.preventDefault(); }, // Don't close on form submit, we will close this manually
            buttons : [
                $.extend( {} , vex.dialog.buttons.YES , { className : "vex-dialog-button-primary" , text : ta_amazon_args.i18n_import_as_affiliate_link , click : function ( $vexContent , event ) {

                    import_product_as_affiliate_link( vex , $tr , asin , product_data );

                } } ),
                $.extend( {} , vex.dialog.buttons.NO , { className : "vex-dialog-button-secondary" , text : ta_amazon_args.i18n_cancel , click : function ( $vexContent , event) {

                    vex.closeAll();

                } } )
            ]
        } );

    } );

    // Quick Import
    $amazon_table.on( "click" , ".controls-column .quick-import" , function() {

        let $tr          = $( this ).closest( "tr" ),
            asin         = $tr.attr( "data-asin" ),
            product_data = datatable_data.find( ( data_item ) => { return data_item.asin == asin; } );

        quick_import_product_as_affiliate_link( product_data , $tr );

    } );

    // Bulk Import
    $( "body" ).on( "click" , "#do-bulk-action" , function() {

        let bulk_action = $( "#bulk-action-selector" ).val();

        if ( bulk_action === "import" ) {

            let bulk_import     = { counter : 0 },
                $trs            = $amazon_table.find( "tr:not(.imported) .cb:checked" ), // Get all rows selected that are not imported yet
                total_to_import = $trs.length;

            if ( total_to_import ) {

                bulk_action_toolbar_processing_mode( $( "div.bulkactions" ) );

                $trs.each( function() {

                    let $tr          = $( this ).closest( "tr" ),
                        asin         = $tr.attr( "data-asin" ),
                        product_data = datatable_data.find( ( data_item ) => { return data_item.asin == asin; } );

                    quick_import_product_as_affiliate_link( product_data , $tr , bulk_import );

                } );

                let interval = setInterval( () => {

                    if ( bulk_import.counter >= total_to_import ) {

                        bulk_action_toolbar_normal_mode( $( "div.bulkactions" ) );
                        clearInterval( interval );

                    }

                } , 1000 );

            }

        } else if ( bulk_action === "delete" ) {

            let bulk_delete     = { counter : 0 },
                $trs            = $amazon_table.find( "tr.imported .cb:checked" ), // Get all rows selected that are imported
                total_to_delete = $trs.length;

            if ( total_to_delete ) {

                vex.dialog.open( {
                    overlayClosesOnClick : false,
                    escapeButtonCloses   : false,
                    unsafeMessage        : ta_amazon_args.i18n_confirm_bulk_delete,
                    onSubmit: function( e ) { e.preventDefault(); }, // Don't close on form submit, we will close this manually
                    buttons : [
                        $.extend( {} , vex.dialog.buttons.YES , { className : "vex-dialog-button-primary" , text : ta_amazon_args.i18n_yes , click : function ( $vexContent , event ) {

                            let $vex_buttons = $( ".vex-dialog-form .vex-dialog-buttons" );

                            $trs.each( function() {

                                let $tr     = $( this ).closest( "tr" ),
                                    link_id = $tr.attr( "data-link-id" );

                                delete_imported_affiliate_link( link_id , $tr , vex , $vex_buttons , bulk_delete );

                            } );

                            let interval = setInterval( () => {

                                if ( bulk_delete.counter >= total_to_delete ) {

                                    $vex_buttons.find( "button" ).removeAttr( "disabled" );
                                    $vex_buttons.find( ".spinner" ).remove();

                                    vex.closeAll();

                                    clearInterval( interval );

                                }

                            } , 1000 );

                        } } ),
                        $.extend( {} , vex.dialog.buttons.NO , { className : "vex-dialog-button-secondary" , text : ta_amazon_args.i18n_cancel , click : function ( $vexContent , event) {

                            vex.closeAll();

                        } } )
                    ]
                } );

            }

        }

    } );

    // Delete Imported Link
    $amazon_table.on( "click" , ".controls-column .delete-link" , function() {

        let $tr     = $( this ).closest( "tr" ),
            link_id = $tr.attr( "data-link-id" );

        vex.dialog.open( {
            overlayClosesOnClick : false,
            escapeButtonCloses   : false,
            unsafeMessage        : ta_amazon_args.i18n_confirm_delete_link,
            onSubmit: function( e ) { e.preventDefault(); }, // Don't close on form submit, we will close this manually
            buttons : [
                $.extend( {} , vex.dialog.buttons.YES , { className : "vex-dialog-button-primary" , text : ta_amazon_args.i18n_yes , click : function ( $vexContent , event ) {

                    let $vex_buttons = $( ".vex-dialog-form .vex-dialog-buttons" );

                    delete_imported_affiliate_link( link_id , $tr , vex , $vex_buttons );

                } } ),
                $.extend( {} , vex.dialog.buttons.NO , { className : "vex-dialog-button-secondary" , text : ta_amazon_args.i18n_cancel , click : function ( $vexContent , event) {

                    vex.closeAll();

                } } )
            ]
        } );

    } );

    $load_more.click( function () {

        if ( current_search_page > 9 ) {

            vex.dialog.alert( ta_amazon_args.i18n_no_more_results );

        } else {

            current_search_page++;

            search_amazon_api();

        }

    } );

} );
