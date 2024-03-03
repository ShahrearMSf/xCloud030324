var thirstyReports;

jQuery( document ).ready( function($){

    // Functions
    thirstyReports = {

        /**
         * Main chart series variable
         *
         * @since 1.0.0
         */
        series : [],

        /**
         * Main chart variable.
         *
         * @since 1.0.0
         */
        main_chart : null,

        /**
         * Local browser's timezone.
         *
         * @since 1.2.2
         */
        timezone : Intl.DateTimeFormat().resolvedOptions().timeZone,

        /**
         * First chart series variable (General all links)
         *
         * @since 1.0.0
         */
        firstSeries : {
            label           : tap_reports_args.general_report_label,
            slug            : tap_reports_args.general_report_slug,
            data            : [],
            yaxis           : 1,
            color           : '#3498db',
            points          : { show: true , radius: 6 , lineWidth: 4 , fillColor: '#fff' , fill: true },
            lines           : { show: true , lineWidth: 5, fill: false },
            shadowSize      : 0,
            prepend_tooltip : "&#36;",
            link_id         : null,
            category        : "ta_general_report",
            total_clicks    : 0
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
                to.datepicker( "option" , "minDate" , thirstyReports.getDate( this ) );
            } );

            to.datepicker({
                maxDate : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                from.datepicker( "option", "maxDate", thirstyReports.getDate( this ) );
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
        date = null;
      }

      return date;
        },

        /**
         * Initialize report graph.
         *
         * @since 1.2.2
         * @since 1.3.1 Add total clicks.
         */
        initReport : function() {

            var $input = $chart_sidebar.find( '#add-report-data' );

            // show overlay
            $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

            $.post( window.ajaxurl, {
                action     : 'ta_init_first_report',
                timezone   : thirstyReports.timezone,
                range      : $input.data( 'range' ),
                start_date : $input.data( 'start-date' ),
                end_date   : $input.data( 'end-date' )
            }, function( response ) {

                if ( response.status == 'success' ) {

                    // redraw the graph
                    thirstyReports.firstSeries.data         = response.flot_data;
                    thirstyReports.firstSeries.total_clicks = response.total_clicks;

                    thirstyReports.series = [];
                    thirstyReports.series.push( thirstyReports.firstSeries );
                    thirstyReports.drawGraph();

                    // update total click count
                    $chart_sidebar.find( 'ul.chart-legend li:first-child em.count' ).text( response.total_clicks );

                } else {
                    // TODO: Handle error here
                }

                // hide overlay.
                $report_block.find( '.overlay' ).hide();

            } , 'json' );
        },

        /**
         * Display the report graph.
         *
         * @since 1.0.0
         */
        drawGraph : function() {

            if ( thirstyReports.series.length < 1 )
                thirstyReports.series.push( thirstyReports.firstSeries );

            thirstyReports.main_chart = $.plot(
                $chart_placeholder,
                thirstyReports.series,
                {
                    legend : {
                        show : false,
                    },
                    grid : {
                        color       : '#aaa',
                        borderColor : 'transparent',
                        borderWidth : 0,
                        hoverable   : true,
                        markings: [ { xaxis: { from: 1.25, to: 1.25 }, color: "black" } ]
                    },
                    xaxis: {
                        show        : true,
                        color       : '#aaa',
                        position    : 'bottom',
                        tickColor   : 'transparent',
                        mode        : "time",
                        timeformat  : report_details.timeformat,
                        monthNames  : [ "Jan" , "Feb" , "Mar" , "Apr" , "May" , "Jun" , "Jul" , "Aug" , "Sep" , "Oct" , "Nov" , "Dec" ],
                        timezone    : "browser",
                        tickLength  : 1,
                        minTickSize : report_details.minTickSize,
                        font        : { color: '#000' }
                    },
                    yaxis: {
                        show         : true,
                        min          : 0,
                        minTickSize  : 1,
                        tickDecimals : 0,
                        color        : '#d4d9dc',
                        timezone     : "browser",
                        font         : { color: '#000' }
                    }
                }
            );

            $chart_placeholder.resize();
            thirstyReports.prepare_csv_data();
        },

        /**
         * Event function to display tooltip when datapoint is hovered.
         *
         * @since 1.0.0
         */
        plotTooltip : function() {

            var prev_data_point = null;

            $chart_placeholder.bind( 'plothover', function ( event, pos, item ) {

                if ( item ) {

                    if ( prev_data_point !== item.datapoint ) {

                        prev_data_point = item.datapoint;
                        $( '.chart-tooltip' ).remove();

                        var tooltip = report_details.clicksLabel + item.datapoint[1];

                        thirstyReports.showTooltip( item.pageX , item.pageY , tooltip );

                    }

                } else {
                    prev_data_point = null;
                    $( '.chart-tooltip' ).remove();
                }
            } );
        },

        /**
         * Append tooltip content.
         *
         * @since 1.0.0
         */
        showTooltip : function( x , y , contents ) {

            var xoffset = ( ( x + 100 ) > $( window ).width() ) ? x - 20 : x + 20,
                yoffset = ( ( x + 100 ) > $( window ).width() ) ? y - 35 : y - 16;

            $( '<div class="chart-tooltip">' + contents + '</div>' ).css( {
          top: yoffset,
          left: xoffset
        }).appendTo( 'body' ).fadeIn( 200 );
        },

        /**
         * Search affiliate link to display in the report
         *
         * @since 1.0.0
         */
        searchAffiliateLink : function() {

            // ajax search affiliate links on keyup event.
            $chart_sidebar.on( 'keyup' , '#add-report-data' , function() {

                var $input = $(this),
                exclude    = $chart_sidebar.data( 'exclude' );

                exclude = ( $.isArray( exclude ) && exclude.length > 0 ) ? exclude : [];

                // clear results list
                $results_list.html('').hide();
                $input.data( 'linkid' , '' ).attr( 'data-linkid' , '' );


                if ( $input.val().length < 3 )
                    return;

                if ( last_searched === $input.val() ) {

                    $results_list.html( search_cache ).show();
                    return;
                }

                last_searched = $input.val();

                $.post( window.ajaxurl, {
                    action   : 'search_affiliate_links_query',
                    keyword  : $input.val(),
                    exclude  : exclude,
                    timezone : thirstyReports.timezone
                }, function( response ) {

                    if ( response.status == 'success' ) {

                        search_cache = response.search_query_markup;
                        $results_list.html( response.search_query_markup ).show();

                    } else {
                        // TODO: Handle error here
                    }

                } , 'json' );

            } );

            // apply link data to search input on click of single search result
            $results_list.on( 'click' , 'li' , function( event ) {

                event.preventDefault();

                var $link  = $(this),
                    $input = $link.closest( '.input-wrap' ).find( 'input' ),
                    linkid = $link.data( 'link-id' );

                if ( ! linkid )
                    return;

                $input.val( $link.text() )
                      .attr( 'data-linkid' , linkid )
                      .data( 'linkid' , linkid );

                // empty cache after selection
                last_searched = '';
                search_cache  = '';

                $results_list.hide();
            } );
        },

        /**
         * Fetch link report data and redraw the graph
         *
         * @since 1.0.0
         * @since 1.2.0 Moved the AJAX success callback code to its own function. (see: fetchLinkReportSuccessCallback)
         */
        fetchLinkReport : function() {

            $chart_sidebar.on( 'click' , 'button#fetch-link-report' , function() {

                var $input = $(this).closest( '.add-legend' ).find( '#add-report-data' ),
                    color  = $( '#link-report-color' ).val(),
                    linkid = parseInt( $input.data( 'linkid' ) ),
                    series;

                if ( ! $input.data( 'linkid' ) ) {

                    // TODO: change to vex dialog
                    alert( tap_reports_args.i18n_invalid_affiliate_link );
                    return;
                }

                // show overlay
                $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

                $.post( window.ajaxurl, {
                    action     : 'ta_fetch_report_by_linkid',
                    link_id    : linkid,
                    range      : $input.data( 'range' ),
                    start_date : $input.data( 'start-date' ),
                    end_date   : $input.data( 'end-date' ),
                    timezone   : thirstyReports.timezone
                }, function( response ) {

                    thirstyReports.fetchLinkReportSuccessCallback( response , linkid , color , $input );

                } , 'json' );
            } );

            if ( $chart_sidebar.find( '#add-report-data' ).data( 'linkid' ) )
                $chart_sidebar.find( 'button#fetch-link-report' ).trigger( 'click' );

        },

        /**
         * Fetch link report data AJAX success callback.
         *
         * @since 1.2.0
         */
        fetchLinkReportSuccessCallback : function( response , linkid , color , $input , drawGraph = true ) {

            thirstyReports.displayNewReport( response , color , linkid );

            // add post ID to exclusion list
            var exclude = $chart_sidebar.data( 'exclude' );
                exclude = ( $.isArray( exclude ) && exclude.length > 0 ) ? exclude : [];

            exclude.push( linkid );
            $chart_sidebar.data( 'exclude' , exclude );

            // clear form
            $input.val( '' );
            $input.attr( 'data-linkid' , '' ).data( 'linkid' , '' );

            // hide overlay
            $report_block.find( '.overlay' ).hide();
        },

        /**
         * Fetch category report data
         *
         * @since 1.0.0
         * @since 1.1.2 add feature to add back general report.
         */
        fetchCategoryReport : function() {

            $chart_sidebar.on( 'click' , 'button#fetch-category-report' , function() {

                var $input   = $(this).closest( '.add-category-legend' ).find( '#add-category-report-data' ),
                    color    = $( '#category-report-color' ).val(),
                    category = $input.val(),
                    series;

                if ( ! category ) {

                    // TODO: change to vex dialog
                    alert( tap_reports_args.i18n_invalid_category );
                    return;
                }

                // show overlay
                $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

                // add back general report
                if ( category === 'ta_general_report' ) {

                    var response = {
                        status       : "success",
                        label        : tap_reports_args.general_report_label,
                        report_data  : thirstyReports.firstSeries.data,
                        slug         : tap_reports_args.general_report_slug,
                        total_clicks : thirstyReports.firstSeries.total_clicks
                    };

                    thirstyReports.fetchCategoryReportSuccessCallback( response , category , color , $input );
                    return;
                }

                $.post( window.ajaxurl, {
                    action     : 'ta_fetch_report_by_category',
                    category   : category,
                    range      : $input.data( 'range' ),
                    start_date : $input.data( 'start-date' ),
                    end_date   : $input.data( 'end-date' ),
                    timezone   : thirstyReports.timezone
                }, function( response ) {

                    thirstyReports.fetchCategoryReportSuccessCallback( response , category , color , $input );

                } , 'json' );
            } );
        },

        /**
         * Fetch category report data AJAX success callback.
         *
         * @since 1.1.2
         * @since 1.2.0 Add drawGraph parameter.
         */
        fetchCategoryReportSuccessCallback : function( response , category , color , $input , drawGraph = true ) {

            $input.find( 'option[value="' + category + '"]' ).prop( 'disabled' , true );
            $input.val('');

            thirstyReports.displayNewReport( response , color , 0 , category , drawGraph );

            // hide overlay
            $report_block.find( '.overlay' ).hide();
        },

        /**
         * Display new report data (redraw graph)
         *
         * @since 1.0.0
         * @since 1.1.2 add code to add .hide-remove class to legend <ul> when only one report is displayed.
         * @since 1.2.0 add drawGraph parameter that determines if graph needs to be redrawn or not.
         * @since 1.3.1 Add total clicks.
         */
        displayNewReport : function( response , color , link_id = 0 , category = '' , drawGraph = true ) {

            if ( response.status == 'success' ) {

                var new_line = {
                        label           : response.label,
                        slug            : response.slug,
                        data            : response.report_data,
                        yaxis           : 1,
                        color           : color,
                        points          : { show: true , radius: 6 , lineWidth: 4 , fillColor: '#fff' , fill: true },
                        lines           : { show: true , lineWidth: 5, fill: false },
                        shadowSize      : 0,
                        prepend_tooltip : "&#36;",
                        link_id         : link_id,
                        category        : category
                    },
                    key;

                // push new line report to thirstyReports.series
                thirstyReports.series.push( new_line );

                // add new legend
                $chart_sidebar.find( 'ul.chart-legend' )
                    .append( '<li class="single-link" style="border-color:' + color + ';">' + response.label + '<em class="count">' + response.total_clicks + '</em><span>' + response.slug + '</span><span class="remove"></span></li>' );

                if ( $legend.find( 'li' ).length > 1 )
                    $legend.removeClass( 'hide-remove' );

                // redraw the graph
                if ( drawGraph )
                    thirstyReports.drawGraph();

            } else {

                // TODO: change to vex dialog
                alert( response.error_msg );
            }
        },

        /**
         * Remove a single report in the chart.
         *
         * @since 1.1.0
         * @since 1.1.2 Made the general report removable, only when there are more than one reports displayed.
         */
        removeReport : function() {

            $chart_sidebar.on( 'click' , 'ul.chart-legend li span.remove' , function() {

                var $this    = $(this),
                    $li      = $this.closest( 'li' ),
                    key      = $li.index(),
                    link_id  = thirstyReports.series[ key ].link_id,
                    category = thirstyReports.series[ key ].category,
                    exclude  = $chart_sidebar.data( 'exclude' );

                $this.trigger( 'mouseleave' );

                if ( $legend.find( 'li' ).length <= 1 )
                    return;

                // show overlay
                $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

                // remove report legend
                $li.remove();

                // remove report data from thirstyReports.series
                thirstyReports.series.splice( key , 1 );

                // redraw the graph
                thirstyReports.drawGraph();

                // remove from search exclusion
                if ( link_id && exclude.indexOf( link_id ) > -1 ) {
                    exclude.splice( exclude.indexOf( link_id ) , 1 );
                    $chart_sidebar.data( 'exclude' , exclude );
                }

                // add category back as selectable option
                if ( category )
                    $chart_sidebar.find( '#add-category-report-data' ).find( 'option[value="' + category + '"]' ).prop( 'disabled' , false );

                if ( $legend.find( 'li' ).length <= 1 )
                    $legend.addClass( 'hide-remove' );

                // hide overlay
                $report_block.find( '.overlay' ).hide();

            } );

            // add remove button on General report on first load.
            $legend.find( 'li:first-child' ).append( '<span class="remove"></span>' );
            $legend.addClass( 'hide-remove' );
        },

        /**
         * Initialize the color picker field
         *
         * @since 1.0.0
         */
        initColorPicker : function() {

            $( '.color-field' ).wpColorPicker();
        },

        /**
         * Prepare report CSV data
         *
         * @since 1.0.0
         * @since 1.3.1 Get CSV headers via AJAX everytime new report is loaded.
         */
        prepare_csv_data : function() {

            // Create array of objects
            var items  = [],
                series = thirstyReports.series,
                plots  = [],
                data, x , y;

            for ( x in series[0].data )
                plots.push( series[0].data[x][0] );

            $.post( window.ajaxurl , {
                action   : 'tap_get_link_performance_report_csv_headers',
                plots    : plots,
                type     : $export_csv_wrap.data( 'report-type' ),
                timezone : thirstyReports.timezone
            } , function( response ) {

                if ( response.status != 'success' )
                    return;

                // push csv headers from response
                items.push( response.csv_headers );

                // get series data
                for ( x in series ) {

                    data = [ series[ x ].label ];

                    for ( y in series[ x ].data )
                        data.push( series[ x ].data[ y ][ 1 ] );

                    items.push( data );
                }

                // Convert Object to JSON
                var jsonObject = JSON.stringify( items );
                var csv_string = thirstyReports.json_to_csv( jsonObject );

                // update export button href property
                $export_csv_wrap.find( 'a' ).prop( 'href' , 'data:application/csv;charset=utf-8,' + escape( csv_string ) );

            } , 'json' );

        },

        /**
         * Convert JSON to CSV format
         *
         * @since 1.0.0
         * @since 1.3.1 Wrap cells in double quotes to prevent breaking when comma is added on the title.
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

        /**
         * Highlight series on legend hover.
         *
         * @since 1.2.0
         */
        highlight_series : function() {

            var isHighlight = false,
                highlight;

            $legend.on( 'mouseover' , 'li' , function() {

                var $this = $(this)
                    key   = $this.index();

                if ( highlight )
                    return;

                // clone hovered series
                highlight       = Object.assign( {} , thirstyReports.series[ key ] );
                highlight.lines = Object.assign( {} , thirstyReports.series[ key ].lines );
                highlight.label = 'Highlight';

                // set fill gradient color of highlight series
                highlight.lines.fill      = true;
                highlight.lines.fillColor = { colors: [{ opacity: 0.5 }, { opacity: 0.9 }] };

                // redraw graph
                thirstyReports.series.push( highlight );
                thirstyReports.drawGraph();
            });

            $legend.on( 'mouseleave' , 'li' , function() {

                var $this = $(this),
                    key;

                if ( ! highlight )
                    return;

                key = thirstyReports.series.indexOf( highlight );
                thirstyReports.series.splice( key , 1 );
                thirstyReports.drawGraph();

                highlight = null;
            });
        },

        /**
         * Save / Load Link Performance report.
         *
         * @since 1.2.0
         * @since 1.3.1 Add total clicks.
         */
        saveLoadReport : function() {

            // save report event
            $chart_sidebar.on( 'click' , '#save-link-performance-report' , function() {

                var $save_report = $chart_sidebar.find( '.save-link-performance-report' ),
                    $button      = $save_report.find( '#save-link-performance-report' ),
                    formData     = $save_report.find( 'input,select' ).serializeArray(),
                    $reportName  = $save_report.find( 'input[name="link_performance_report_name"]' ),
                    reports      = [];

                if ( ! $reportName.val() ) {
                    alert( tap_reports_args.i18n_invalid_report_name );
                    return;
                }

                $button.prop( 'disabled' , true );
                $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

                // get currently displayed reports
                for ( x in thirstyReports.series ) {

                    formData.push( { name : 'reports[' + x + '][label]' , value : thirstyReports.series[ x ].label } );
                    formData.push( { name : 'reports[' + x + '][slug]' , value : thirstyReports.series[ x ].slug } );
                    formData.push( { name : 'reports[' + x + '][link_id]' , value : thirstyReports.series[ x ].link_id } );
                    formData.push( { name : 'reports[' + x + '][category]' , value : thirstyReports.series[ x ].category } );
                    formData.push( { name : 'reports[' + x + '][color]' , value : thirstyReports.series[ x ].color } );
                }

                $.post( window.ajaxurl , formData , function( response ) {

                    if ( response.status == 'success' ) {

                        alert( response.message );
                        $reportName.val( '' );

                    } else {
                        // TODO: Handle error here
                        alert( response.error_msg );

                    }

                    $button.prop( 'disabled' , false );
                    $report_block.find( '.overlay' ).hide();

                } , 'json' );
            } );

            // Load report event
            $chart_sidebar.on( 'click' , '#load-link-performance-report' , function() {

                var $load_report = $chart_sidebar.find( '.load-link-performance-report' ),
                    $button      = $load_report.find( '#load-link-performance-report' ),
                    formData     = $load_report.find( 'input,select' ).serializeArray(),
                    $reportSel   = $load_report.find( 'select#saved-reports' ),
                    reports      = $load_report.data( 'saved_reports' ),
                    selectedData = reports[ parseInt( $reportSel.val() ) ],
                    $indicator   = $chart_sidebar.find( '.loaded-report-indicator' ),
                    $delbutton   = $load_report.find( '#delete-link-performance-report' ),
                    reportType, reportVal, temp;

                if ( ! $reportSel.val() ) {
                    alert( tap_reports_args.i18n_invalid_sel_report );
                    return;
                }

                if ( $reportSel.val() == 'best-performance' ) {
                    thirstyReports.getBestPerformanceReport( formData );
                    return;
                }

                $button.prop( 'disabled' , true );
                $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

                // get link id or category slug for report data to load.
                for ( x in selectedData.reports ) {

                    temp       = selectedData.reports[ x ];
                    reportType = temp.link_id > 0 ? 'link' : 'category';
                    reportVal  = reportType == 'link' ? temp.link_id : temp.category;

                    selectedData.reports[ x ].type = reportType;

                    // skip if general report
                    if ( ! temp.link_id && temp.category === 'ta_general_report' )
                        continue;

                    formData.push( { name : 'reports[' + x + '][type]' , value : reportType } );
                    formData.push( { name : 'reports[' + x + '][value]' , value : reportVal } );
                }

                // add timezone argument
                formData.push( { name : 'timezone' , value : thirstyReports.timezone } );

                // fetch report data and apply to chart.
                $.post( window.ajaxurl , formData , function( response ) {

                    if ( response.status == 'success' ) {

                        var res ,linkid , temp , data , $input;

                        // reset all report data
                        thirstyReports.series = [];
                        $legend.html('');
                        $chart_sidebar.data( 'exclude' , [] );
                        $chart_sidebar.find( '#add-category-report-data option' ).prop( 'disabled' , false );

                        for ( x in selectedData.reports ) {

                            temp   = selectedData.reports[ x ];
                            $input = ( temp.type == 'link' ) ? $chart_sidebar.find( '#add-report-data' ) : $chart_sidebar.find( '#add-category-report-data' );
                            linkid = parseInt( temp.link_id );
                            data   = ( ! linkid && temp.category === 'ta_general_report' ) ? thirstyReports.firstSeries.data : response.report_data[ x ][ 'plot' ];
                            clicks = ( ! linkid && temp.category === 'ta_general_report' ) ? thirstyReports.firstSeries.total_clicks : response.report_data[ x ][ 'total_clicks' ];
                            res    = {
                                status       : response.status,
                                label        : temp.label,
                                slug         : temp.slug,
                                report_data  : data,
                                total_clicks : clicks
                            };

                            if ( temp.type == 'link' )
                                thirstyReports.fetchLinkReportSuccessCallback( res , linkid , temp.color , $input , false );
                            else
                                thirstyReports.fetchCategoryReportSuccessCallback( res , temp.category , temp.color ,  $input , false );
                        }

                        $indicator.find( 'span' ).text( selectedData.report_name );
                        $indicator.show();

                        thirstyReports.drawGraph();
                        $reportSel.val( '' );

                    } else {
                        // TODO: Handle error here
                        alert( response.error_msg );

                    }

                    $button.prop( 'disabled' , false );
                    $delbutton.prop( 'disabled' , true );
                    $report_block.find( '.overlay' ).hide();

                } , 'json' );
            } );

            // show best performance category field when best performance is selected.
            $chart_sidebar.on( 'change' , '#saved-reports' , function() {

                var $select   = $(this),
                    $parent   = $select.closest( '.load-link-performance-report' ),
                    $catwrap  = $parent.find( '.best-performance-category-field' ),
                    $catfield = $catwrap.find( 'select' ),
                    $action   = $parent.find( 'input[name="action"]' );

                if ( $select.val() == 'best-performance' ) {

                    $catfield.prop( 'disabled' , false );
                    $catwrap.show();
                    $action.val( 'tap_load_best_link_performance_report' );

                } else {

                    $catfield.prop( 'disabled' , true );
                    $catwrap.hide();
                    $action.val( 'tap_load_link_performance_report' );
                }

            } );
        },

        /**
         * Delete saved link performance report
         *
         * @since 1.3.2
         */
        deleteSavedReport : function() {

            $chart_sidebar.on( 'change' , '#saved-reports' , function() {

                var $savedReports = $(this),
                    $deleteButton = $chart_sidebar.find( "#delete-link-performance-report" ),
                    toggle        = $savedReports.val() ? false : true;

                if ( $savedReports.val() == 'best-performance' )
                    toggle = true;

                $deleteButton.prop( 'disabled' , toggle );
            } );

            $chart_sidebar.on( 'click' , '#delete-link-performance-report' , function() {

                if ( ! confirm( tap_reports_args.delete_report_warning ) ) return;

                var $deleteButton   = $(this),
                    $load_report    = $chart_sidebar.find( '.load-link-performance-report' ),
                    reports         = $load_report.data( 'saved_reports' ),
                    $savedReports   = $chart_sidebar.find( "#saved-reports" ),
                    selectedKey     = $savedReports.val(),
                    $selectedReport = $savedReports.find( 'option[value="' + selectedKey + '"]' ),
                    formData       = {
                        action      : 'tap_delete_link_performance_report',
                        report_name : reports[ selectedKey ].report_name,
                        reports     : reports[ selectedKey ].reports,
                        nonce       : $deleteButton.data( 'nonce' )
                    };

                // remove selected report from options
                $savedReports.val( '' );
                $selectedReport.remove();
                reports.splice( selectedKey , 1 );

                // reset the saved report options
                var $reportOptions = $savedReports.find( 'option' );
                for ( var x = 1; x < $reportOptions.length; x++ )
                    $( $reportOptions[ x ] ).attr( 'value' , x - 1 );

                $.post( window.ajaxurl , formData , null , 'json' );

                $deleteButton.prop( 'disabled' , true );
            } );

        },

        /**
         * Report actions.
         *
         * @since 1.3.2
         */
        reportActions : function() {

            $chart_sidebar.on( 'change' , '#report-actions-list' , function() {

                var $select = $(this),
                    action  = $select.val();

                $chart_sidebar.find( '.report-action,.add-legend' ).hide();

                if ( action ) $chart_sidebar.find( '.' + action ).show();
            } );

            $chart_sidebar.find( '#report-actions-list' ).trigger( 'change' );
        },

        /**
         * Get best performance report.
         *
         * @since 1.4.0
         */
        getBestPerformanceReport( formData ) {

            var $load_report = $chart_sidebar.find( '.load-link-performance-report' ),
                $button      = $load_report.find( '#load-link-performance-report' ),
                $reportSel   = $load_report.find( 'select#saved-reports' ),
                $catSel      = $load_report.find( '#best-performance-category' ),
                $indicator   = $chart_sidebar.find( '.loaded-report-indicator' );

            $button.prop( 'disabled' , true );
            $report_block.find( '.overlay' ).css( 'height' , $report_block.height() ).show();

            // add timezone argument
            formData.push( { name : 'timezone' , value : thirstyReports.timezone } );

            // fetch report data and apply to chart.
            $.post( window.ajaxurl , formData , function( response ) {

                var $input = $chart_sidebar.find( '#add-report-data' ),
                    colors = [ '#f44336' , '#9C27B0' , '#3F51B5' , '#2196F3' , '#00BCD4' , '#009688' , '#8BC34A' , '#FF9800' , '#795548' , '#607D8B' ],
                    temp , res;

                if ( response.status == 'success' ) {

                    // reset all report data
                    thirstyReports.series = [];
                    $legend.html( '' );
                    $chart_sidebar.data( 'exclude' , [] );
                    $chart_sidebar.find( '#add-category-report-data option' ).prop( 'disabled' , false );

                    for ( x in response.report_data ) {

                        temp = response.report_data[x];
                        res  = {
                            status       : response.status,
                            label        : temp.label,
                            slug         : temp.slug,
                            report_data  : temp.plot,
                            total_clicks : temp.total_clicks
                        };

                        thirstyReports.fetchLinkReportSuccessCallback( res , temp.link_id , colors[x] , $input , false );
                    }

                    var indicatorText = $reportSel.find( "option:selected" ).text();
                    if ( $catSel.val() ) indicatorText += " (" + $catSel.find( "option:selected" ).text()  + ")";

                    $indicator.find( 'span' ).text( indicatorText );
                    $indicator.show();

                    thirstyReports.drawGraph();
                    $catSel.val( '' );
                    $reportSel.val( '' ).trigger( 'change' );

                } else {

                    // TODO: Handle error here
                    alert( response.error_msg );
                }

                $button.prop( 'disabled' , false );
                $report_block.find( '.overlay' ).hide();

            } , 'json' );

        }
    };

    var $custom_date_form  = $( 'form#custom-date-range' ),
        $report_block      = $( '.link-performance-report' ),
        $chart_placeholder = $( '.report-chart-placeholder' ),
        $chart_sidebar     = $( '.chart-sidebar' ),
        $legend            = $chart_sidebar.find( 'ul.chart-legend' ),
        $results_list      = $( '.report-chart-wrap .add-legend .link-search-result' ),
        $export_csv_wrap   = $( '.export-csv-button' ),
        date_format        = 'yy-mm-dd',
        last_searched, search_cache;

    // init range date picker
    thirstyReports.rangeDatepicker();

    // init jQuery flot graph
    thirstyReports.initReport();

    // init plot tooltip events
    thirstyReports.plotTooltip();

    // init search affiliate link event
    thirstyReports.searchAffiliateLink();

    // init fetch link report event
    thirstyReports.fetchLinkReport();

    // init fetch category report event
    thirstyReports.fetchCategoryReport();

    // remove single report
    thirstyReports.removeReport();

    // init color picker field
    thirstyReports.initColorPicker();

    // highlight series on legend hover
    thirstyReports.highlight_series();

    // init save load report feature
    thirstyReports.saveLoadReport();

    thirstyReports.deleteSavedReport();

    thirstyReports.reportActions();
} );
