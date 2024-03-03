<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="link-performance-report geolocation-report wp-core-ui">
    <div class="stats-range">
        <ul>
            <?php foreach ( $range_nav as $nrange => $label ) : ?>
                <li<?php echo ( $nrange == $current_range ) ? ' class="current"' : ''; ?>>
                    <a href="<?php echo admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports&tab=geolocation&range=' . $nrange ); ?>">
                        <?php echo $label; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <li class="custom-range">
                <span><?php _e( 'Custom' , 'thirstyaffiliates-pro' ); ?></span>
                <form id="custom-date-range" method="GET">
                    <input type="hidden" name="post_type" value="<?php echo $cpt_slug; ?>">
                    <input type="hidden" name="page" value="thirsty-reports">
                    <input type="hidden" name="tab" value="geolocation">
                    <input type="hidden" name="range" value="custom">
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $start_date ); ?>" name="start_date" class="range_datepicker from" required>
                    <span>&mdash;</span>
                    <input type="text" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $end_date ); ?>" name="end_date" class="range_datepicker to" required>
                    <button type="submit" class="button"><?php _e( 'Go' , 'thirstyaffiliates-pro' ); ?></button>
                </form>
            </li>

            <?php do_action( 'ta_geolocation_reporting_menu_items' ); ?>

        </ul>
    </div>
    <div class="report-chart-wrap">

        <div class="geo-report-title"></div>

        <div class="chart-sidebar">

            <ul class="geo-report-legend">
            </ul>
        </div>

        <div class="report-chart-placeholder"></div>

        <?php do_action( 'ta_geolocation_reporting_after_chart_placeholder' ); ?>

    </div>
    <div class="overlay"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function ($) {

    GeolocationReports = {

        /**
         * Local browser's timezone.
         *
         * @since 1.3.2
         */
        timezone : Intl.DateTimeFormat().resolvedOptions().timeZone,

        /**
         * Initialize Geolocation report on DOM ready
         *
         * @since 1.0.0
         */
        initReport : function() {

            var $overlay = $main_report_wrap.find( '.overlay' ),
                data     = {
                action     : 'tap_geolocation_report',
                range      : '<?php echo $current_range; ?>',
                start_date : '<?php echo $start_date; ?>',
                end_date   : '<?php echo $end_date; ?>',
                timezone   : GeolocationReports.timezone
            };

            // show overlay
            $overlay.css( 'height' , $main_report_wrap.height() ).show();

            // AJAX call
            $.post( ajaxurl , data , function( response ) {

                if ( response.status == 'success' ) {

                    $report_title.html( response.title );
                    $report_legend.html( response.legend );
                    GeolocationReports.jqvmapInit( response.data );

                } else {

                    // TODO: changed to VEX modal
                    alert( response.error_msg );
                }

                // hide overlay
                $overlay.hide();


            }, 'json' );
        },

        /**
         * Initialize JQVmap script
         *
         * @since 1.0.0
         */
        jqvmapInit : function( data ) {

            $( '.report-chart-placeholder' ).vectorMap( {
                map           : 'world_en',
                color         : '#ffffff',
                backgroundColor: '#d8dce6',
                hoverOpacity  : 0.7,
                selectedColor : '#666666',
                enableZoom    : true,
                showTooltip   : true,
                scaleColors   : ['#85d6af', '#014022'],
                values        : data,
                onLabelShow   : function( event , label , code ) {

                    var labelText  = label.text(),
                        clickCount = (typeof data[ code ] !== 'undefined' ) ? data[ code ] : 0;

                    label.text( labelText + ': ' + clickCount );
                }
            });
        },

        /**
         * Initialize date picker function
         *
         * @since 1.0.0
         */
        rangeDatepicker : function() {

            var from        = $custom_date_form.find( '.range_datepicker.from' ),
                to          = $custom_date_form.find( '.range_datepicker.to' );

            from.datepicker({
                maxDate    : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                to.datepicker( "option" , "minDate" , GeolocationReports.getDate( this ) );
            } );

            to.datepicker({
                maxDate : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                console.log( GeolocationReports.getDate( this ) );
                from.datepicker( "option", "maxDate", GeolocationReports.getDate( this ) );
            } );
        },

        /**
         * Get the date value of the datepicker element.
         *
         * @since 1.0.0
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
    }

    var $custom_date_form  = $( 'form#custom-date-range' ),
        $main_report_wrap  = $( '.link-performance-report' ),
        $report_chart_wrap = $( '.report-chart-wrap' ),
        $report_legend     = $report_chart_wrap.find( '.geo-report-legend' ),
        $report_title      = $report_chart_wrap.find( '.geo-report-title' ),
        date_format        = 'yy-mm-dd';

    GeolocationReports.initReport();
    GeolocationReports.rangeDatepicker();
});
</script>
