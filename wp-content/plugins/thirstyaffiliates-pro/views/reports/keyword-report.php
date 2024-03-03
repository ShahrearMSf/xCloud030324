<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="link-performance-report wp-core-ui">
    <div class="stats-range">
        <ul>
            <?php foreach ( $range_nav as $nrange => $label ) : ?>
                <li<?php echo ( $nrange == $current_range ) ? ' class="current"' : ''; ?>>
                    <a href="<?php echo admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports&tab=keyword_report&range=' . $nrange ); ?>">
                        <?php echo $label; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <li class="custom-range">
                <span><?php _e( 'Custom' , 'thirstyaffiliates-pro' ); ?></span>
                <form id="custom-date-range" method="GET">
                    <input type="hidden" name="post_type" value="<?php echo $cpt_slug; ?>">
                    <input type="hidden" name="page" value="thirsty-reports">
                    <input type="hidden" name="tab" value="keyword_report">
                    <input type="hidden" name="range" value="custom">
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $start_date ); ?>" name="start_date" class="range_datepicker from" required>
                    <span>&mdash;</span>
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $end_date ); ?>" name="end_date" class="range_datepicker to" required>
                    <button type="submit" class="button"><?php _e( 'Go' , 'thirstyaffiliates-pro' ); ?></button>
                </form>
            </li>

            <?php do_action( 'ta_keyword_report_reporting_menu_items' ); ?>

        </ul>
    </div>
    <div class="report-chart-wrap">

        <div class="keyword-report-filter-form">

            <div class="affilite-links-field form-field">
                <select id="affiliate_links" name="affiliate_links[]" multiple data-placeholder="<?php _e( 'Search and select affiliate links...' , 'thirstyaffiliates-pro' ); ?>"></select>
            </div>
            <div class="categories-field form-field">
                <select id="categories" name="categories[]" multiple data-placeholder="<?php _e( 'Select categories...' , 'thirstyaffiliates-pro' ); ?>">
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="submit-field form-field">
                <button class="button-primary" id="filter-keyword-report"><?php _e( 'Filter' , 'thirstyaffiliates-pro' ); ?></button>
            </div>
            <input type="hidden" name="action" value="tap_filter_keyword_report">
            <input type="hidden" name="range" value="<?php echo esc_attr( $current_range ); ?>">
            <input type="hidden" name="start_date" value="<?php echo esc_attr( $start_date ); ?>">
            <input type="hidden" name="end_date" value="<?php echo esc_attr( $end_date ); ?>">
            <?php wp_nonce_field( 'tap_filter_keyword_report_nonce_field' ); ?>

        </div>

        <div class="report-table-wrap keyword-table-wrap">
            <div class="responsive-table">
                <table class="report-table keyword-table">
                    <thead>
                        <tr>
                            <th class="keyword"><?php _e( 'Keyword' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="total-clicks"><?php _e( 'Total Clicks' , 'thirstyaffiliates-pro' ); ?></th>
                            <th class="affiliate-links"><?php _e( 'Affiliate Links' , 'thirstyaffiliates-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="overlay"></div>
        </div>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function ($) {

    keywordReport = {

        /**
         * Local browser's timezone.
         *
         * @since 1.3.2
         */
        timezone : Intl.DateTimeFormat().resolvedOptions().timeZone,

        /**
         * Initialize date picker function
         *
         * @since 1.2.0
         */
        rangeDatepicker : function() {

            var from        = $custom_date_form.find( '.range_datepicker.from' ),
                to          = $custom_date_form.find( '.range_datepicker.to' );

            from.datepicker({
                maxDate    : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                to.datepicker( "option" , "minDate" , keywordReport.getDate( this ) );
            } );

            to.datepicker({
                maxDate : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                from.datepicker( "option", "maxDate", keywordReport.getDate( this ) );
            } );
        },

        /**
         * Get the date value of the datepicker element.
         *
         * @since 1.2.0
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
         * Initialize affiliate link tooltip
         *
         * @since 1.2.0
         */
        linkTooltip : function() {

            $report_chart_wrap.find( '.keyword-table .affiliate-links span.tooltip' ).tipTip({
                "attribute"       : "data-tip",
                "defaultPosition" : "top",
                "fadeIn"          : 50,
                "fadeOut"         : 50,
                "delay"           : 200
            });
        },

        /**
         * Initialize filter form
         *
         * @since 1.2.0
         */
        initFilterForm : function() {

            // init affiliate links field.
            $filter_form.find( 'select#affiliate_links' ).selectize({
                plugins     : [ 'remove_button' ],
                valueField  : 'value',
                labelField  : 'label',
                searchField : 'label',
                create      : false,
                render      : {
                    option : function( item , escape ) {
                        return '<div class="option" data-value="' + item.value + '">' + item.label + ' (' + item.slug +')' + '</div>';
                    }
                },
                load : function( query , callback ) {

                    if ( query.length < 3 ) return callback();

                    var data = {
                        action : 'tap_selectize_affiliate_link',
                        search : query,
                    };

                    $.ajax({
                        url     : ajaxurl,
                        data    : data,
                        method  : 'post',
                        error   : function() { callback(); },
                        success : function( response ) {
                            if ( response.status == 'success' )
                                callback( response.items );
                            else
                                callback();
                        }
                    });
                }
            });

            // init categories field.
            $filter_form.find( 'select#categories' ).selectize({
                plugins : [ 'remove_button' ],
                create  : false
            });
        },

        /**
         * Filter keyword report.
         *
         * @since 1.2.0
         */
        filterKeywordReport : function() {

            $filter_form.on( 'click' , '#filter-keyword-report' , function() {

                var $this   = $(this),
                    $fields = $filter_form.find( 'input,select' ),
                    $tbody  = $report_chart_wrap.find( '.keyword-table tbody' ),
                    data    = $fields.serializeArray();

                data.push( { name: "timezone" , value: keywordReport.timezone } );

                $this.prop( 'disabled' , true );
                $report_chart_wrap.find( '.overlay' ).css( 'height' , $keyword_table_wrap.height() ).show();

                // AJAX call
                $.post( ajaxurl , data , function( response ) {

                    if ( response.status == 'success' ) {

                        $tbody.html( response.markup );
                        keywordReport.linkTooltip();

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );
                    }

                    $this.prop( 'disabled' , false );
                    $report_chart_wrap.find( '.overlay' ).hide();

                }, 'json' );

            } ).find( '#filter-keyword-report' ).trigger( 'click' );
        }
    };

    var $custom_date_form   = $( 'form#custom-date-range' ),
        $report_chart_wrap  = $( '.report-chart-wrap' ),
        $keyword_table_wrap = $report_chart_wrap.find( '.keyword-table-wrap' ),
        $filter_form        = $( '.keyword-report-filter-form' ),
        date_format         = 'yy-mm-dd',
        edit_link_url       = '<?php echo admin_url( 'post.php?post=link_id&action=edit' ); ?>';

    keywordReport.rangeDatepicker();
    keywordReport.initFilterForm();
    keywordReport.filterKeywordReport();

});
</script>
