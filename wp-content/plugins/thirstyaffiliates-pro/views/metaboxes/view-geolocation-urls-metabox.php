<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<p><?php _e( 'Enter your geolocation URLs, these will override the destination URL above for visitors from these countries:' , 'thirstyaffiliates-pro' ); ?></p>

<div class="add-geo-link-form" data-id="<?php echo $thirstylink->get_id(); ?>" data-countries="<?php echo esc_attr( json_encode( $all_countries ) ); ?>">
    <div class="field-wrap select-countries">
        <select multiple id="geolink_countries" data-placeholder="<?php _e( 'Select countries' , 'thirstyaffiliates-pro' ); ?>">
            <?php foreach( $countries as $code => $country ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>"><?php echo $country; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field-wrap destination-input">
        <input type="text" id="geolink_destination" placeholder="<?php _e( 'Destination URL' , 'thirstyaffiliates-pro' ); ?>">
        <input type="hidden" id="geolink_oldkey" value="">
    </div>
    <div class="field-wrap geolink-button">
        <button type="button" id="add_geolink_btn" class="button-primary"
                data-add="<?php _e( 'Add Geolink', 'thirstyaffiliates-pro' ); ?>"
                data-update="<?php _e( 'Save', 'thirstyaffiliates-pro' ); ?>">
            <?php _e( 'Add Geolink' , 'thirstyaffiliates-pro' ); ?>
        </button>
        <button type="button" id="cancel-update-geolink" class="button" style="display:none;"><?php _e( 'Cancel' , 'thirstyaffiliates-pro' ); ?></button>
        <span class="tap-spinner" style="display:none"></span>
    </div>
</div>

<table class="tap-geo-links-table">
    <thead>
        <tr>
            <th><?php _e( 'Countries' , 'thirstyaffiliates-pro' ); ?></th>
            <th><?php _e( 'Destination URL' , 'thirstyaffiliates-pro' ); ?></th>
            <th class="actions"></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( is_array( $geolinks ) && ! empty( $geolinks ) ) : ?>
            <?php foreach ( $geolinks as $key => $geolink ) : ?>
                <tr class="geolink">
                    <td><?php echo $key; ?></td>
                    <td>
                        <span title="<?php echo esc_attr( $geolink ); ?>">
                            <?php echo mb_strimwidth( $geolink , 0 , 71 , '[...]' ); ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a class="link" href="<?php echo esc_attr( $geolink ); ?>" target="_blank"><span class="dashicons dashicons-admin-links"></span></a>
                        <a class="edit" href="#"><span class="dashicons dashicons-edit"></span></a>
                        <a class="remove" href="#"><span class="dashicons dashicons-no"></span></a>
                    </td>
                </tr>
            <?php endforeach ; ?>
        <?php else : ?>
            <tr>
                <td colspan="3" style="text-align: center;"><?php _e( 'No geolocation links recorded.' , 'thirstyaffiliates-pro' ); ?></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>

    var countrySelectize;
    jQuery( document ).ready( function( $ ) {

        var $geolinkMetabox  = $( '#tap-geolocation-urls-metabox' ),
            $geolinkForm     = $geolinkMetabox.find( '.add-geo-link-form' ),
            $geolinksTable   = $geolinkMetabox.find( 'table.tap-geo-links-table' ),
            $countrySelect   = $( 'select#geolink_countries' ).selectize();

            countrySelectize = $countrySelect[0].selectize;

        // add / update geolink event
        $geolinkForm.on( 'click' , '#add_geolink_btn' , function() {

            var $this     = $(this),
                $parent   = $this.closest( 'div' ),
                post_id   = $geolinkForm.data( 'id' ),
                countries = $geolinkForm.find( '#geolink_countries' ).val(),
                link      = $geolinkForm.find( '#geolink_destination' ).val(),
                old_key   = $geolinkForm.find( '#geolink_oldkey' ).val();

            if ( ! countries ) {
                alert( '<?php _e( 'Please select one or more countries.' , 'thirstyaffiliates-pro' ); ?>' );
                return;
            }

            if ( ! validate_url( link ) ) {
                alert( '<?php _e( 'Please enter a valid URL for the destination field.' , 'thirstyaffiliates-pro' ); ?>' );
                return;
            }

            // disable button and show spinner
            $this.prop( 'disabled' , true );
            $geolinkForm.find( '#cancel-update-geolink' ).prop( 'disabled' , true );
            $parent.find( '.tap-spinner' ).css( 'display' , 'inline-block' );

            $.post( parent.ajaxurl, {
                action    : 'tap_add_single_geolink',
                post_id   : post_id,
                countries : countries,
                link      : link,
                old_key   : old_key
            }, function( response ) {

                if ( response.status == "success" ) {

                    var $tbody          = $geolinksTable.find( 'tbody' ),
                        $current_edited = $geolinkForm.data( 'currently_edited' );

                    // remove the placeholder row when there are no geolinks registered yet
                    if ( $tbody.find( 'tr.geolink' ).length < 1 )
                        $tbody.html( '' );

                    if ( typeof $current_edited == 'object' )
                        $current_edited.replaceWith( response.row_markup );
                    else
                        $tbody.append( response.row_markup );

                    countrySelectize.clearOptions();
                    countrySelectize.addOption( response.countries_selectize );
                    countrySelectize.refreshOptions( false );

                    $geolinkForm.find( '#add_geolink_btn' ).text( $geolinkForm.find( '#add_geolink_btn' ).data( 'add' ) );
                    setTimeout(function(){ $tbody.find( 'tr.new' ).removeClass( 'new' ) }, 500);

                }

                reset_geolink_form();
                $parent.find( '.tap-spinner' ).hide();

            } , 'json' );
        } );

        // edit geolink event
        $geolinksTable.on( 'click' , 'td.actions a.edit' , function( e ) {

            e.preventDefault();

            var $row          = $(this).closest( 'tr.geolink' ),
                post_id       = $geolinkForm.data( 'id' ),
                old_key       = $row.find( 'td:first-child' ).text().trim(),
                link          = $row.find( 'td:nth-child(2) span' ).prop( 'title' ).trim(),
                countries_key = $row.find( 'td:first-child' ).text(),
                country_codes = countries_key.split( ',' ),
                all_countries = $geolinkForm.data( 'countries' ),
                countries     = [],
                x, code;

                // clear country select
                cancel_geolink_edit();
                countrySelectize.setValue( '' , false );

                for ( x in country_codes )
                    countries[ country_codes[ x ] ] = all_countries[ country_codes[ x ] ];

                for ( code in countries ) {
                    countrySelectize.addOption( { value: code , text: countries[ code ] } );
                    countrySelectize.addItem( code );
                }

                $geolinkForm.find( '#geolink_destination' ).val( link );
                $geolinkForm.find( '#geolink_oldkey' ).val( old_key );
                $geolinkForm.find( '#add_geolink_btn' ).text( $geolinkForm.find( '#add_geolink_btn' ).data( 'update' ) );
                $geolinkForm.find( '#cancel-update-geolink' ).show();
                $geolinkForm.data( 'currently_edited' , $row );
                $row.addClass( 'edit' );
        } );

        // cancel edit event
        $geolinkForm.on( 'click' , '#cancel-update-geolink' , function() {

            cancel_geolink_edit();
            reset_geolink_form();
        } );

        // remove geolink event
        $geolinksTable.on( 'click' , 'td.actions a.remove' , function( e ) {

            e.preventDefault();

            var $row = $(this).closest( 'tr.geolink' ),
                post_id = $geolinkForm.data( 'id' ),
                countries_key = $row.find( 'td:first-child' ).text();

            $row.css( 'background' , '#ffc3bd' );
            $(this).removeClass( 'remove' );
            $(this).find( 'span' ).removeClass( 'dashicons' ).addClass( 'tap-spinner' );

            $.post( parent.ajaxurl, {
                action        : 'tap_remove_single_geolink',
                post_id       : post_id,
                countries_key : countries_key
            }, function( response ) {

                if ( response.status == "success" ) {

                    countrySelectize.clearOptions();
                    countrySelectize.addOption( response.countries_selectize );
                    countrySelectize.refreshOptions( false );

                    $row.fadeOut( 'fast' , function() {
                        $(this).remove();
                    } );
                }

                reset_geolink_form();

            } , 'json' );
        } );

        function validate_url( url ) {

            var pattern = /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i;

            return pattern.test( url );
        }

        function cancel_geolink_edit() {

            var selected_countries = countrySelectize.getValue(),
                x;

            $geolinksTable.find( 'tr' ).removeClass( 'edit new' );

            if ( selected_countries.length < 1 )
                return;

            setTimeout( function(){

                for ( x in selected_countries )
                    countrySelectize.removeOption( selected_countries[ x ] );

                countrySelectize.refreshOptions( false );

            }, 100 );
        }

        function reset_geolink_form() {

            $geolinkForm.find( 'input' ).val('');
            countrySelectize.setValue( '' , false );
            $geolinkForm.find( '.geolink-button button' ).prop( 'disabled' , false );
            $geolinkForm.find( '.geolink-button .geo-spinner' ).hide();
            $geolinkForm.find( '#cancel-update-geolink' ).prop( 'disabled' , false ).hide();
            $geolinkForm.data( 'currently_edited' , '' );
        }

    } );
</script>
