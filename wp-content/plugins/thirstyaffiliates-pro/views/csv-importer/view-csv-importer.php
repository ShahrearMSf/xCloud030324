<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<h3><?php _e( 'STEP 1 - select your CSV file:' , 'thirstyaffiliates-pro' ); ?></h3>

<p>
    <?php _e( 'This tool lets you upload a properly formatted CSV file containing a list of affiliate links. These links will be bulk imported for you directly into ThirstyAffiliates.' , 'thirstyaffiliates-pro' ); ?>
</p>

<p>
    <?php _e( 'Any categories you specify in your import file that are not found in the system will be created as the links are imported' , 'thirstyaffiliates-pro' ); ?>
</p>

<?php if ( isset( $upload_dir[ 'error' ] ) && ! empty( $upload_dir[ 'error' ] ) ) : ?>

    <div class="error">
        <p><?php _e( 'Before you can upload your import file, you will need to fix the following error:' , 'thirstyaffiliates-pro' ); ?></p>
        <p><strong><?php echo $upload_dir[ 'error' ]; ?></strong></p>
    </div>

<?php else : ?>

    <form class="tap-round-box" id="tap_upload_csv_form" enctype="multipart/form-data" method="post" action="<?php echo esc_url_raw( $nonce_action ); ?>">

        <p>
            <strong><?php _e( 'Import from CSV' , 'thirstyaffiliates-pro' ); ?></strong>
        </p>

        <p>
            <?php echo sprintf( __( 'Maximum file size: %s' , 'thirstyaffiliates-pro' ) , $max_size ); ?>
        </p>

        <p>
            <label for="import">
                <input type="file" id="import" name="import">
                <?php _e( 'Select a CSV file (comma delimited .csv)' , 'thirstyaffiliates-pro' ); ?>
            </label>
        </p>

        <p>
            <label for="override_links">
                <input type="checkbox" id="override_links" name="override_links">
                <?php _e( 'Override already existing links with the same slug?' , 'thirstyaffiliates-pro' ); ?>
            </label>
        </p>

        <p>
            <label for="skip_escape">
                <input type="checkbox" id="skip_escape" name="skip_escape">
                <?php _e( 'Skip escaping of URLs?' , 'thirstyaffiliates-pro' ); ?>
            </label>
        </p>

        <p>
            <button class="button-primary" type="submit">
                <?php _e( 'Upload file and import' , 'thirstyaffiliates-pro' ); ?>
            </button>
            <span class="tap-spinner"></span>
        </p>

    </form>

    <script type="text/javascript">
    jQuery( document ).ready( function( $ ) {

        var $csv_upload_form = $( '#tap_upload_csv_form' )

        // option for skipping URL escaping to prevent special characters like '[' and ']' from being deleted
        $csv_upload_form.on( 'change' , '#skip_escape , #override_links' , function() {

            var input_name = $(this).attr( 'name' );
                action_url = $csv_upload_form.attr( 'action' );

            if ( $(this).prop( 'checked' ) )
                action_url = action_url.replace( input_name + '=0' , input_name + '=1' );
            else
                action_url = action_url.replace( input_name + '=1' , input_name + '=0' );

            $csv_upload_form.attr( 'action' , action_url );
        } );

        $( '#skip_escape , #override_links' ).trigger( 'change' );

    } );
    </script>

<?php endif; ?>

<div class="tap-round-box import-instructions">

    <p>
        <strong><?php _e( 'Download Example CSV' , 'thirstyaffiliates-pro' ); ?></strong>
    </p>
    <p>
        <?php _e( 'We have provided a demo CSV file that you can use as a template for formatting your CSV appropriately.' , 'thirstyaffiliates-pro' ); ?>
    </p>
    <p>
        <?php echo sprintf( __( '<a class="button-secondary" href="%s">Download Sample CSV</a>' , 'thirstyaffiliates-pro' ) , $csv_sample_url ); ?>
    </p>
    <p>
        <?php _e( 'All columns are optional except for the first two (name & destination URL). If the destination URL is not valid, that row will be skipped.' , 'thirstyaffiliates-pro' ); ?>
    </p>
    <p>
        <?php _e( 'Geolocations URLs and Autolinker keywords will be imported, but will not be active if you do not have those modules enabled.' , 'thirstyaffiliates-pro' ); ?>
    </p>

    <?php if ( get_option( 'tap_enable_geolocation' , 'yes' ) === 'yes' ) : ?>
        <p>
            <strong><?php _e( 'Importing Country Specific URLs' , 'thirstyaffiliates-pro' ); ?></strong>
        </p>
        <p>
            <?php _e( 'Please use the two digit country code for your countries as found in the following XML file:' , 'thirstyaffiliates-pro' ); ?>
        </p>
        <p>
            <?php echo sprintf( __( '<a class="button-secondary" href="%s" target="_blank">Example country codes</a>' , 'thirstyaffiliates-pro' ) , $country_list ); ?>
        </p>
    <?php endif; ?>

    <?php if ( get_option( 'tap_enable_autolinker' , 'yes' ) === 'yes' ) : ?>
        <p>
            <strong><?php _e( 'Importing Autolink Keywords' , 'thirstyaffiliates-pro' ); ?></strong>
        </p>
    <p><?php _e( 'To import autolink keywords please provide a semi-colon delimited list (eg. Keyword One;Keyword Two).' , 'thirstyaffiliates-pro' ); ?></p>
    <?php endif; ?>
</div>
