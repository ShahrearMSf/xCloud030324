/* global ta_amazon_args */

// https://stackoverflow.com/questions/27778389/how-to-manually-update-datatables-table-with-new-json-data

import $ from "jquery";

export default ( $table ) => {

    return {
        "dom"        : "<'bulkactions'>rfti<'tap-amazon-load-more'>",
        "processing" : true,
        "serverSide" : false,
        "order"      : [],
        "searching"  : true,
        "paging"     : false,
        "pageLength" : 100,
        "lengthChange" : false,
        data: [],
        "columnDefs" : [
            {
                "targets"   : 0,
                "orderable" : false,
                "className" : "check-column",
                "data"      : "check-column"
            },
            {
                "targets"   : 1,
                "orderable" : false,
                "className" : "image",
                "data"      : "image",
                "render"    : function( data , type , row ) {

                    return data.formatted;

                }
            },
            {
                "targets"   : 2,
                "orderable" : true,
                "className" : "title",
                "data"      : "title",
                "render"    : function( data , type , row ) {

                    if ( type === "display" )
                        return data.formatted; // Return formatted data

                    return data.raw;

                }
            },
            {
                "targets"   : 3,
                "orderable" : true,
                "className" : "price",
                "data"      : "price"
            },
            {
                "targets"   : 4,
                "orderable" : true,
                "className" : "item-stock",
                "data"      : "item-stock"
            },
            {
                "targets"   : 5,
                "orderable" : true,
                "className" : "sales-rank",
                "data"      : "sales-rank"
            },
            {
                "targets"   : 6,
                "orderable" : false,
                "className" : "controls-column",
                "data"      : "controls-column"
            }
        ],
        "createdRow": function( row , data , dataIndex ) {

            // Check if this product is already imported, if so, mark as imported
            let imported_link_data = ta_amazon_args.azon_link_data.find( ( data_item ) => { return data_item.asin === data.asin; } );

            if ( imported_link_data !== undefined ) {

                $( row ).attr( "data-link-id" , imported_link_data.link_id );
                $( row ).addClass( "imported" );

            }

            // Always add asin to the attribute
            $( row ).attr( "data-asin" , data.asin );

        },
        "preDrawCallback" : function() {

            // Before draw
            $table.trigger( "retrieving_data_mode" );

        },
        "language": {
            "zeroRecords" : ta_amazon_args.i18n_no_data_available,
            "search"      : ta_amazon_args.i18n_filter_results
        },
        "drawCallback" : function() {

            var $tr = $table.find( "th[aria-sort]" );

            $table.trigger( "normal_mode" );

            // Add Sort Icons
            if ( $tr.attr( "aria-sort" ) == "descending" ) {

                $table.find( "th .dashicons" ).remove();
                $tr.append( "<span class='dashicons dashicons-arrow-down'></span>" );

            } else {

                $table.find( "th .dashicons" ).remove();
                $tr.append( "<span class='dashicons dashicons-arrow-up'></span>" );

            }

            // There is a side effect to this on dynamically showing/hiding DataTables columns
            // We will comment this out until the fix is found
            // Until then, the tfoot headers will have no sorting available

            // // Clone table header to footer
            // var header_row = $table.find( "thead tr" ).clone( true );
            // $table.find( "tfoot tr" ).replaceWith( header_row );

        }
    };

};
