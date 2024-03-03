jQuery( document ).ready( function($){

    thirstyProSettings = {

        /**
         * Geolocation maxmind DB field toggle
         *
         * @since 1.0.0
         */
        geolocationMaxMindDBToggle : function() {

            $settingsBlock.on( 'change' , 'input[name="tap_geolocations_maxmind_db"]' , function() {

                $premiumDBToggle.closest( 'tr' ).hide();
                $webServiceToggle.prop( 'readonly' , true ).closest( 'tr' ).hide();

                switch ( $(this).val() ) {

                    case 'premium' :
                        $premiumDBToggle.closest( 'tr' ).show();
                        break;

                    case 'web_service' :
                        $webServiceToggle.prop( 'readonly' , false ).closest( 'tr' ).show();
                        break;

                    default :
                        break;
                }

            } ).find( 'input[name="tap_geolocations_maxmind_db"]:checked' ).trigger( 'change' );
        },

        gctCustomFunctionName : function() {

            $settingsBlock.on( 'change' , 'input[name="tap_google_click_tracking_script"]:checked' , function() {

                var tracking_script  = $(this).val(),
                    $customFuncField = $settingsBlock.find( 'input#tap_universal_ga_custom_func' );

                if ( tracking_script == 'universal_ga' ) {
                    $customFuncField.prop( 'readonly' , false ).closest( 'tr' ).show();
                } else {
                    $customFuncField.prop( 'readonly' , true ).closest( 'tr' ).hide();
                }
            }).find( 'input[name="tap_google_click_tracking_script"]:checked' ).trigger( 'change' );
        },

        pluginVisibilityAdminInterfaces : function() {

            $settingsBlock.on( 'change' , '#capability-thirstylink_list' , function() {

                var $list_sel  = $(this),
                    $edit_sel  = $settingsBlock.find( "select#capability-thirstylink_edit" ),
                    $selectize = $edit_sel[0].selectize,
                    edit_val   = $edit_sel.val(),
                    edit_opts  = $edit_sel.data( 'options' );

                if ( ! edit_opts ) {
                    edit_opts = $selectize.options;
                    $edit_sel.data( 'options' , edit_opts );
                }

                var edit_keys  = Object.keys( edit_opts ),
                    cap_key    = edit_keys.indexOf( $list_sel.val() ),
                    newOptions = edit_keys.filter( function( el , i ) {
                    return i <= cap_key;
                } );
                newOptions.push( 'custom' );

                $selectize.clearOptions();

                for ( var x in newOptions ) {
                    $selectize.addOption(edit_opts[ newOptions[x] ]);
                }

                if ( newOptions.indexOf( edit_val ) > -1 )
                    $selectize.setValue( edit_val );
            } );
            $( '#capability-thirstylink_list' ).trigger( "change" );
        }

    };

    var $settingsBlock    = $( '.ta-settings.wrap' ),
        $premiumDBToggle  = $( '.maxmind-db-toggle' ),
        $webServiceToggle = $( '.maxmind-web-toggle' );

    // init geolocation maxmind DB field toggle
    thirstyProSettings.geolocationMaxMindDBToggle();

    thirstyProSettings.gctCustomFunctionName();

    thirstyProSettings.pluginVisibilityAdminInterfaces();
});
