<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>


<div class="link-health-report-actions">

    <div class="bulk-action">
        <select id="bulk_action" name="bulk_action">
            <option value=""><?php _e( 'Bulk actions' , 'thirstyaffiliates-pro' ); ?></option>
            <option value="recheck"><?php _e( 'Recheck' , 'thirstyaffiliates-pro' ); ?></option>
            <option value="ignore"><?php _e( 'Ignore' , 'thirstyaffiliates-pro' ); ?></option>
        </select>
        <button class="button" type="button"><?php _e( 'Apply' , 'thirstyaffiliates-pro' ); ?></button>
        <input type="hidden" name="action" value="tap_link_health_bulk_action">
        <?php wp_nonce_field( 'tap_link_health_bulk_action_nonce' ); ?>
    </div>

    <div class="toggle-by-status">
        <span><?php _e( 'Filter:', 'thirstyaffiliates-pro' ); ?></span>
        <button type="button" class="warning show" data-status="warning"><?php _e( 'warning' , 'thirstyaffiliates-pro' ); ?></button>
        <button type="button" class="error show" data-status="error"><?php _e( 'error' , 'thirstyaffiliates-pro' ); ?></button>
        <button type="button" class="ignored" data-status="ignored"><?php _e( 'ignored' , 'thirstyaffiliates-pro' ); ?></button>
    </div>
</div>

<div class="link-health-report-wrap link-performance-report">
<table class="link-health-issues" data-total="<?php echo esc_attr( $total ); ?>" data-statuses="<?php echo esc_attr( json_encode( $statuses ) ); ?>">
    <thead>
        <tr>
            <th class="bulk"><input type="checkbox" id="check_all_issues"></th>
            <th class="link_id"><?php _e( 'Affiliate Link' , 'thirstyaffiliates-pro' ); ?></th>
            <th class="destination"><?php _e( 'Destination' , 'thirstyaffiliates-pro' ); ?></th>
            <th class="status"><?php _e( 'Status' , 'thirstyaffiliates-pro' ); ?></th>
            <th class="last_checked"><?php _e( 'Last Checked' , 'thirstyaffiliates-pro' ); ?></th>
            <th class="issue"><?php _e( 'Issue' , 'thirstyaffiliates-pro' ); ?></th>
            <th class="actions"><?php _e( 'Actions' , 'thirstyaffiliates-pro' ); ?></th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
<div class="overlay"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function ($) {

    var linkHealthReport = {

        /**
         * Initialize load report.
         *
         * @since 1.2.0
         */
        init_load_report : function() {

            var total = $link_health_table.data( 'total' ),
                pages = Math.ceil( total / 25 ),
                x;

            if ( ! total || ! pages )
                return;

            $overlay.css( 'height' , $link_health_table.height() ).show();
            $report_wrap.css( 'height' , $link_health_table.height() ).css( 'overflow' , 'hidden' );

            linkHealthReport.load_page_rows_markup( 1 , pages );
        },

        /**
         * Load page rows markup.
         *
         * @since 1.2.0
         */
        load_page_rows_markup : function( x , pages ) {

            var $tbody = $link_health_table.find( 'tbody' ),
                data   = {
                    action : 'tap_load_page_rows_markup',
                    paged  : x
                };

            $.post( ajaxurl , data , function( response ) {

                if ( response.status == 'success' ) {

                    $tbody.append( response.markup );
                    x++;

                    if ( x <= pages )
                        linkHealthReport.load_page_rows_markup( x , pages );
                    else {

                        linkHealthReport.filter_report();
                        $overlay.hide();
                        $report_wrap.css( 'height' , 'auto' ).css( 'overflow' , 'visible' );
                    }

                    $( 'body' ).trigger( 'display_tooltip' );

                } else {

                    // TODO: changed to VEX modal
                    alert( response.error_msg );
                }

            }, 'json' );

        },

        /**
         * Recheck single link health AJAX trigger.
         *
         * @since 1.2.0
         */
        recheck_link_health : function() {

            $link_health_table.on( 'click' , '.recheck' , function() {

                var $this = $(this),
                    $row  = $this.closest( 'tr' ),
                    data  = {
                        action  : 'tap_check_single_link_health',
                        link_id : $row.find( 'input[name="link_id"]' ).val(),
                        nonce   : $row.find( 'input[name="_nonce"]' ).val()
                    };

                linkHealthReport.show_row_spinner( $row );

                $.post( ajaxurl , data , function( response ) {

                    if ( response.status == 'success' ) {

                        linkHealthReport.recheck_link_health_success_cb( $row , response.data );
                        linkHealthReport.filter_report();

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );
                    }

                    linkHealthReport.hide_row_spinner();

                }, 'json' );
            } );
        },

        /**
         * Recheck single link health success callback.
         *
         * @since 1.2.0
         */
        recheck_link_health_success_cb : function( $row , data ) {

            var status_txt = statuses[ data.status ],
                ignore     = data.status === 'ignored';

            $row.find( 'td.destination_url input' ).val( data.url );
            $row.find( 'td.status span' ).attr( 'class' , data.status ).text( status_txt );
            $row.find( 'td.last_checked').text( data.last_checked );
            $row.find( 'td.issue' ).html( data.message );
            $row.find( 'button.ignore' ).prop( 'disabled' , ignore );
            $row.removeClass( 'ignored inactive active waiting error warning' );
            $row.addClass( data.status );

            $( 'body' ).trigger( 'display_tooltip' );
        },

        /**
         * Ignore single link issue AJAX trigger.
         *
         * @since 1.2.0
         */
        ignore_link_issue : function() {

            $link_health_table.on( 'click' , '.ignore' , function() {

                var $this = $(this),
                    $row  = $this.closest( 'tr' ),
                    data  = {
                        action  : 'tap_ignore_link_health_issue',
                        link_id : $row.find( 'input[name="link_id"]' ).val(),
                        nonce   : $row.find( 'input[name="_nonce"]' ).val()
                    };

                linkHealthReport.show_row_spinner( $row );

                $.post( ajaxurl , data , function( response ) {

                    if ( response.status == 'success' ) {

                        linkHealthReport.ignore_link_issue_success_cb( $row , response.issue_status );
                        linkHealthReport.filter_report();

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );
                    }

                    linkHealthReport.hide_row_spinner();

                }, 'json' );
            } );
        },

        /**
         * Ignore link health issue success callback.
         *
         * @since 1.2.0
         */
        ignore_link_issue_success_cb : function( $row , status ) {

            var status_txt = statuses[ status ];

            $row.find( 'td.status span' ).attr( 'class' , status ).text( status_txt );
            $row.find( 'button.ignore' ).prop( 'disabled' , true );
            $row.removeClass( 'ignored inactive active waiting error warning' );
            $row.addClass( status );
        },

        /**
         * Show row spinner.
         *
         * @since 1.2.0
         */
        show_row_spinner : function( $row ) {

            var $relative = $row.find( '.row-spinner' ),
                $spinner  = $relative.find( 'span' );

            $spinner.css( 'width' , $row.width() );
            $spinner.css( 'height' , $row.height() );
            $relative.show();
        },

        /**
         * hide row spinner.
         *
         * @since 1.2.0
         */
        hide_row_spinner : function() {

            $link_health_table.find( '.row-spinner' ).hide();
        },

        /**
         * Link health report bulk action.
         *
         * @since 1.2.0
         */
        bulk_action : function() {

            $report_actions.on( 'click' , '.bulk-action .button' , function() {

                var $this  = $(this),
                    $tbody = $link_health_table.find( 'tbody' );
                    data   = $tbody.find( 'input[type="checkbox"]' ).serializeArray(),
                    form   = $report_actions.find( '.bulk-action' ).find( 'input,select' ).serializeArray(),
                    action = $( '#bulk_action' ).val();

                if ( ! data.length || ! action )
                    return;

                $this.prop( 'disabled' , true );
                $overlay.css( 'height' , $link_health_table.height() ).show();

                $.post( ajaxurl , data.concat( form ) , function( response ) {

                    if ( response.status == 'success' ) {

                        var temp, $row;

                        for ( link_id in response.results ) {

                            temp = response.results[ link_id ];
                            $row = $tbody.find( 'tr.link-' + link_id );

                            if ( action == 'ignore' )
                                linkHealthReport.ignore_link_issue_success_cb( $row , temp.issue_status );
                            else
                                linkHealthReport.recheck_link_health_success_cb( $row , temp );
                        }

                        linkHealthReport.filter_report();

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );
                    }

                    $( '#bulk_action' ).val( '' );
                    $this.prop( 'disabled' , false );
                    $overlay.hide();

                }, 'json' );
            } );
        },

        /**
         * Toggle check all issues.
         *
         * @since 1.2.0
         */
        toggle_check_all_issues : function() {

            $link_health_table.on( 'change' , '#check_all_issues' , function() {

                var $this  = $(this),
                    $tbody = $link_health_table.find( 'tbody' ),
                    val    = $this.prop( 'checked' );

                $tbody.find( 'td.bulk input[type="checkbox"]' ).prop( 'checked' , val );
            } );
        },

        /**
         * Toggle filter buttons.
         *
         * @since 1.2.0
         */
        toggle_filter_buttons : function() {

            $report_actions.on( 'click' , '.toggle-by-status button' , function() {

                $(this).toggleClass( 'show' );
                linkHealthReport.filter_report();
            } );
        },

        /**
         * Filter report.
         *
         * @since 1.2.0
         */
        filter_report : function() {

            var $buttons = $report_actions.find( '.toggle-by-status button:not(.show)' ),
                $tbody   = $link_health_table.find( 'tbody' ),
                classes  = '',
                x;

            for ( x = 0; x < $buttons.length; x++ )
                classes = classes + "tr." + $( $buttons[ x ] ).data( 'status' ) + ",";

            $tbody.find( 'tr').show().find( 'td.bulk input[type="checkbox"]' ).prop( 'disabled' , false )

            if ( ! classes )
                return;

            classes = classes.substring( 0 , classes.length - 1 );

            if ( classes.indexOf( 'tr.error' ) !== -1 )
                classes = classes + ",tr.inactive";

            $tbody.find( classes ).hide().find( 'td.bulk input[type="checkbox"]' ).prop( 'disabled' , true );
        },

        view_steps : function() {

            $link_health_table.on( 'click' , 'a.view-steps' , function() {

                var content = $(this).parent().find( '.steps-content' ).html();

                $dialog = vex.open({
                    content   : ' ',
                    className : 'vex-theme-plain vex-view-redirect-steps'
                });

                $( '.vex-theme-plain.vex-view-redirect-steps .vex-content' ).append( content );

            } );
        },

        tooltip : function() {

            $( "body" ).on( 'display_tooltip' , function() {

                jQuery( ".link-health-issues .tooltip" ).tipTip({
                    "attribute"       : "data-tip",
                    "defaultPosition" : "top",
                    "fadeIn"          : 50,
                    "fadeOut"         : 50,
                    "delay"           : 200
                });

            } );
        }
    };

    var $report_wrap       = $( '.link-health-report-wrap' ),
        $overlay           = $report_wrap.find( '.overlay' ),
        $link_health_table = $( 'table.link-health-issues' ),
        $report_actions    = $( '.link-health-report-actions' ),
        $actions_column    = $link_health_table.find( 'tbody tr td.actions' ),
        statuses           = $link_health_table.data( 'statuses' );

    linkHealthReport.init_load_report();
    linkHealthReport.recheck_link_health();
    linkHealthReport.ignore_link_issue();
    linkHealthReport.bulk_action();
    linkHealthReport.toggle_check_all_issues();
    linkHealthReport.toggle_filter_buttons();
    linkHealthReport.view_steps();
    linkHealthReport.tooltip();
});
</script>
