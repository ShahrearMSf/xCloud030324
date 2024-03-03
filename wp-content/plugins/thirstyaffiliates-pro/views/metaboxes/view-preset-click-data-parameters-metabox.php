<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="add-preset-click-data-form">

    <div class="form-input ip-address-input">
        <label for="preset_ip_address"><?php _e( 'IP address' , 'thirstyaffiliates-pro' ); ?></label>
        <input type="text" id="preset_ip_address" name="_preset_ip_address" value="">
    </div>

    <div class="form-input referrer-address-input">
        <label for="preset_http_referrer"><?php _e( 'HTTP Referrer' , 'thirstyaffiliates-pro' ); ?></label>
        <input type="url" id="preset_http_referrer" name="_preset_http_referrer" value="">
    </div>

    <div class="form-input keyword-input">
        <label for="preset_keyword"><?php _e( 'Keyword' , 'thirstyaffiliates-pro' ); ?></label>
        <input type="text" id="preset_keyword" name="_preset_keyword" value="">
    </div>

    <div class="form-input form-submit">

        <div class="save">
            <button type="button" class="button-primary save-preset-click-data"><?php _e( 'Save' , 'thirstyaffiliates-pro' ); ?></button>
        </div>

        <div class="edit" style="display: none;">
            <button type="button" class="button-primary edit-preset-click-data"><?php _e( 'Edit' , 'thirstyaffiliates-pro' ); ?></button>
            <button type="button" class="button cancel-edit"><?php _e( 'Cancel' , 'thirstyaffiliates-pro' ); ?></button>
        </div>

        <input type="hidden" name="_preset_link_id" value="<?php echo $post->ID; ?>">
        <input type="hidden" name="_preset_qcode" value="">
        <?php wp_nonce_field( 'tap_save_preset_click_data' , '_preset_click_data_nonce' ); ?>

    </div>

</div>

<div class="preset-click-data-table">
    <table>
        <thead>
            <tr>
                <th class="qcode"><?php _e( 'Cloaked URL with code' , 'thirstyaffiliates-pro' ); ?></th>
                <th class="ip-address"><?php _e( 'IP Address' , 'thirstyaffiliates-pro' ); ?></th>
                <th class="referrer"><?php _e( 'HTTP Referrer' , 'thirstyaffiliates-pro' ); ?></th>
                <th class="keyword"><?php _e( 'Keyword' , 'thirstyaffiliates-pro' ); ?></th>
                <th class="actions"></th>
            </tr>
        </thead>
        <tbody>
            <tr class="no-result">
                <td colspan="5"><?php _e( 'No preset click data parameters saved yet.' , 'thirstyaffiliates-pro' ); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="overlay">
    <div class="spinner-img">
        <img src="<?php echo $spinner_img; ?>">
    </div>
</div>

<script type="text/javascript">
jQuery( document ).ready( function($) {

    var presetClickData = {

        events : function() {

            $form.on( 'click' , 'button.save-preset-click-data' , presetClickData.saveClickData );
            $form.on( 'click' , 'button.edit-preset-click-data' , presetClickData.saveClickData );
            $form.on( 'click' , 'button.cancel-edit' , presetClickData.resetForm );
            $table.on( 'click' , 'td.actions .edit' , presetClickData.editClickData );
            $table.on( 'click' , 'td.actions .remove' , presetClickData.deleteClickData );
        },

        initialize : function() {

            var data = {
                action  : 'tap_load_preset_click_data',
                link_id : <?php echo $post->ID; ?>
            };

            presetClickData.showOverlay();

            $.post( ajaxurl , data , function( response ) {

                if ( response.status == 'success' )
                    $tbody.html( response.markup );

                presetClickData.hideOverlay();

            }, 'json' );

        },

        saveClickData : function() {

            var $button      = $(this),
                formData     = $form.find( 'input,select' ).serializeArray(),
                $row         = $form.data( 'editing' ),
                httpReferrer = $form.find( "#preset_http_referrer" ).val(),
                ipAddress    = $form.find( "#preset_ip_address" ).val();

            var isValidUrl = /^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.​\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[​6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1​,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00​a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u​00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i;
            if ( httpReferrer && ! isValidUrl.test( httpReferrer ) ) {
                alert( "<?php _e( 'HTTP Referrer field needs to be a valid URL' , 'thirstyaffiliates-pro' ); ?>" );
                return;
            }

            var isValidIPAddress = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
            if ( ipAddress && ! isValidIPAddress.test( ipAddress ) ) {
                alert( "<?php _e( 'The set IP address is not valid.' , 'thirstyaffiliates-pro' ); ?>" );
                return;
            }

            presetClickData.showOverlay();
            formData.push({ name : 'action' , value : 'tap_save_edit_preset_click_data' });

            $.post( ajaxurl , formData , function( response ) {

                if ( response.status == 'success' ) {

                    if ( $row && $row.length )
                        $row.replaceWith( response.markup );
                    else
                        $tbody.append( response.markup );

                    $tbody.find( "tr.no-result" ).remove();
                    presetClickData.resetForm();

                } else {

                    // TODO: change to vex.
                    alert( response.error_msg );
                }

                presetClickData.hideOverlay();

            } , 'json' );
        },

        editClickData : function() {

            var $button = $(this),
                $row    = $button.closest( 'tr' ),
                data    = $row.data( 'preset' ),
                qcode   = $row.find( 'td.qcode' ).data( 'qcode' );

            $row.addClass( 'editing' );
            $form.find( '#preset_ip_address' ).val( data.user_ip_address );
            $form.find( '#preset_http_referrer' ).val( data.http_referer );
            $form.find( '#preset_keyword' ).val( data.keyword );
            $form.find( 'input[name="_preset_qcode"]' ).val( qcode );
            $form.data( 'editing' , $row );

            $form.find( '.save' ).hide();
            $form.find( '.edit' ).show();

        },

        deleteClickData : function() {

            var $button = $(this),
                $row    = $button.closest( 'tr' ),
                qcode   = $row.find( 'td.qcode' ).data( 'qcode' ),
                data    = {
                    action          : 'tap_delete_preset_click_data',
                    _preset_link_id : <?php echo $post->ID ?>,
                    _preset_qcode   : qcode,
                    nonce           : $form.find( 'input[name="_preset_click_data_nonce"]' ).val()
                };

            presetClickData.showOverlay();

            $.post( ajaxurl , data , function( response ){

                if ( response.status == 'success' )
                    $row.remove();
                else {

                    // TODO: change to vex.
                    alert( response.error_msg );
                }

                if ( $tbody.find( "tr" ).length < 1 )
                    $tbody.append( '<tr class="no-result"><td colspan="5"><?php _e( 'No preset click data parameters saved yet.' , 'thirstyaffiliates-pro' ); ?></td></tr>' );

                presetClickData.hideOverlay();
            } );
        },

        resetForm : function() {

            $form.find( 'input[type="text"],input[type="url"],input[name="qcode"],input[name="_preset_qcode"]' ).val( '' );
            $form.find( '.save' ).show();
            $form.find( '.edit' ).hide();
            $form.data( 'editing' , null );
            $tbody.find( 'tr' ).removeClass( 'editing' );
        },

        showOverlay : function() {
            $overlay.css( 'display' , 'flex' );
        },

        hideOverlay : function() {
            $overlay.hide();
        }

    };

    var $metabox = $( "#tap-preset-click-data-parameters-metabox" ),
        $form    = $metabox.find( '.add-preset-click-data-form' ),
        $table   = $metabox.find( '.preset-click-data-table table' ),
        $tbody   = $table.find( 'tbody' ),
        $overlay = $metabox.find( '.overlay' );

    presetClickData.events();
    presetClickData.initialize();
} );
</script>