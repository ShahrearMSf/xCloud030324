<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="link-performance-report wp-core-ui">
    <div class="stats-range">
        <ul>
            <?php foreach ( $range_nav as $nrange => $label ) : ?>
                <li<?php echo ( $nrange == $current_range ) ? ' class="current"' : ''; ?>>
                    <a href="<?php echo admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports&tab=stats_table&range=' . $nrange ); ?>">
                        <?php echo $label; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <li class="custom-range">
                <span><?php _e( 'Custom' , 'thirstyaffiliates-pro' ); ?></span>
                <form id="custom-date-range" method="GET">
                    <input type="hidden" name="post_type" value="<?php echo $cpt_slug; ?>">
                    <input type="hidden" name="page" value="thirsty-reports">
                    <input type="hidden" name="tab" value="stats_table">
                    <input type="hidden" name="range" value="custom">
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $start_date ); ?>" name="start_date" class="range_datepicker from" required>
                    <span>&mdash;</span>
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $end_date ); ?>" name="end_date" class="range_datepicker to" required>
                    <button type="submit" class="button"><?php _e( 'Go' , 'thirstyaffiliates-pro' ); ?></button>
                </form>
            </li>

            <?php do_action( 'ta_stats_table_reporting_menu_items' ); ?>

            <li class="export-csv-button">
                <a id="export_stats_table_csv" href="javascript:void(0);" data-filename="<?php echo 'stats-table-' . $current_range . '-' . $today_date . '.csv'; ?>">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e( 'Export CSV' , 'thirstyaffiliates-pro' ); ?>
                </a>
            </li>

        </ul>
    </div>
    <div class="report-chart-wrap">

        <div class="stats-table-filter-form">
            <div class="limit-field form-field">
                <?php _e( 'Show' , 'thirstyaffiliates-pro' ); ?>
                <select name="limit">
                    <option>25</option>
                    <option>50</option>
                    <option>75</option>
                    <option>100</option>
                </select>
                <?php _e( 'entries' , 'thirstyaffiliates-pro' ); ?>

            </div>
            <div class="search-field form-field">
                <input type="text" name="search" placeholder="<?php _e( 'Search...' , 'thirstyaffiliates-pro' ); ?>">
            </div>
            <div class="category-field form-field">
                <select name="category">
                    <option value=""><?php _e( '-- No Category Filter --' , 'thirstyaffiliates-pro' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="submit-field form-field">
                <button class="button-primary" id="filter-stats-table-report"><?php _e( 'Filter' , 'thirstyaffiliates-pro' ); ?></button>
            </div>
            <input type="hidden" name="action" value="tap_filter_stats_table_report">
            <input type="hidden" name="paged" value="1">
            <input type="hidden" name="total" value="0">
            <input type="hidden" name="range" value="<?php echo esc_attr( $current_range ); ?>">
            <input type="hidden" name="start_date" value="<?php echo esc_attr( $start_date ); ?>">
            <input type="hidden" name="end_date" value="<?php echo esc_attr( $end_date ); ?>">
            <?php wp_nonce_field( 'tap_filter_stats_table_nonce_field' ); ?>
        </div>

        <div class="report-table-wrap stats-table-wrap">
            <div class="responsive-table">
                <table class="report-table stats-table" data-total="0">
                    <thead>
                        <tr>
                            <th class="ip-address"><?php _e( 'Visitor IP' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="date"><?php _e( 'Date' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="geolocation"><?php _e( 'Country' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="link"><?php _e( 'Link' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="referrer"><?php _e( 'Page Referrer' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="cloaked_url"><?php _e( 'Cloaked URL' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="redirect_url"><?php _e( 'Redirect URL' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="redirect_type"><?php _e( 'Redirect Type' , 'thirstyaffiliates-pro' ); ?></th>
                            <?php if ( get_option( 'ta_disable_browser_device_collection' ) !== 'yes' && ini_get( "browscap" ) ) : ?>
                                <th class="browser-device"><?php _e( 'Browser/Device' , 'thirstyaffiliates-pro' ); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="pagination-wrap">
                <div class="pagination"></div>
            </div>
            <div class="overlay"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function ($) {

    statsTableReports = {

        /**
         * Local browser's timezone.
         *
         * @since 1.3.2
         */
        timezone : Intl.DateTimeFormat().resolvedOptions().timeZone,

        /**
         * Initialize date picker function
         *
         * @since 1.1.0
         */
        rangeDatepicker : function() {

            var from        = $custom_date_form.find( '.range_datepicker.from' ),
                to          = $custom_date_form.find( '.range_datepicker.to' );

            from.datepicker({
                maxDate    : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                to.datepicker( "option" , "minDate" , statsTableReports.getDate( this ) );
            } );

            to.datepicker({
                maxDate : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                from.datepicker( "option", "maxDate", statsTableReports.getDate( this ) );
            } );
        },

        /**
         * Get the date value of the datepicker element.
         *
         * @since 1.1.0
         */
        getDate : function( element ) {

            var date;

      try {
        date = $.datepicker.parseDate( date_format, element.value );
      } catch( error ) {
                console.log( error );
        date = null;
      }

      return date;
        },

        /**
         * Filter stats table results.
         *
         * @since 1.1.0
         */
        filterStatsTableReport : function() {

            $filter_form.on( 'click' , '#filter-stats-table-report' , function() {

                var $this   = $(this),
                    $fields = $filter_form.find( 'input,select' ),
                    $tbody  = $report_chart_wrap.find( '.stats-table tbody' ),
                    data    = $fields.serializeArray();

                data.push( { name: "timezone" , value: statsTableReports.timezone } );

                $this.prop( 'disabled' , true );
                $report_chart_wrap.find( '.overlay' ).css( 'height' , $stats_table_wrap.height() ).show();

                // AJAX call
                $.post( ajaxurl , data , function( response ) {

                    if ( response.status == 'success' ) {

                        filter_options_cache = data;
                        $tbody.html( response.markup );
                        $pagination.html( response.pagination );

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );
                    }

                    $this.prop( 'disabled' , false );
                    $filter_form.find( 'input[name="paged"]' ).val( 1 );
                    $filter_form.find( 'input[name="total"]' ).val( 0 );
                    $stats_table_wrap.find( '.stats-table' ).data( 'total' , response.total );
                    $report_chart_wrap.find( '.overlay' ).hide();

                }, 'json' );
            } );

            $filter_form.find( '#filter-stats-table-report' ).trigger( 'click' );
        },

        /**
         * Stats table report pagination
         *
         * @since 1.1.0
         */
        statsTableReportPagination : function() {

            $pagination.on( 'click' , 'button' , function() {

                var $this = $(this),
                    val   = $this.val(),
                    total = $stats_table_wrap.find( '.stats-table' ).data( 'total' );

                $filter_form.find( 'input[name="paged"]' ).val( val );
                $filter_form.find( 'input[name="total"]' ).val( total );
                $filter_form.find( '#filter-stats-table-report' ).trigger( 'click' );

            } );
        },

        /**
         * Export stats table to CSV
         *
         * @since 1.2.0
         */
        statsTableExportCSV : function() {

            $( '#export_stats_table_csv' ).on( 'click' , function() {

                var filename = $(this).data( 'filename' ),
                    parent   = $(this).closest( '.export-csv-button' ),
                    data     = {
                    action     : 'tap_stats_table_export_csv',
                    search     : $filter_form.find( 'input[name="search"]' ).val(),
                    category   : $filter_form.find( 'select[name="category"]' ).val(),
                    range      : $filter_form.find( 'input[name="range"]' ).val(),
                    start_date : $filter_form.find( 'input[name="start_date"]' ).val(),
                    end_date   : $filter_form.find( 'input[name="end_date"]' ).val()
                };

                if ( parent.hasClass( 'downloading' ) )
                    return;

                parent.addClass( 'downloading' );

                $.post( ajaxurl , data , function( response ) {

                    if ( response.status == 'success' ) {

                        var csv_string = statsTableReports.json_to_csv( response[ 'data' ] ),
                            link       = document.createElement( "a" );

                        link.setAttribute( 'href' , 'data:application/csv;charset=utf-8,' + escape( csv_string ) );
                        link.setAttribute( 'download' , filename );
                        document.body.appendChild( link ); // required for FF

                        link.click();

                        $( link ).remove();

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );
                    }

                    parent.removeClass( 'downloading' );

                });
            });
        },

        /**
         * Convert JSON to CSV format
         *
         * @since 1.2.0
         */
        json_to_csv : function( objArray ) {

            var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;
            var str = '';

            for (var i = 0; i < array.length; i++) {
                var line = '';
                for (var index in array[i]) {
                    if (line != '') line += ','

                    line += '"' + array[i][index] + '"';
                }

                str += line + '\r\n';
            }

            return str;
        },
    };

    var $custom_date_form  = $( 'form#custom-date-range' ),
        $report_chart_wrap = $( '.report-chart-wrap' ),
        $stats_table_wrap  = $report_chart_wrap.find( '.stats-table-wrap' ),
        $filter_form       = $report_chart_wrap.find( '.stats-table-filter-form' ),
        $pagination        = $report_chart_wrap.find( '.pagination' ),
        date_format        = 'yy-mm-dd';

    statsTableReports.rangeDatepicker();
    statsTableReports.filterStatsTableReport();
    statsTableReports.statsTableReportPagination();
    statsTableReports.statsTableExportCSV();
});
</script>
