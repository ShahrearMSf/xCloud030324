<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<h3><?php _e( 'STEP 1 - Export your links:' , 'thirstyaffiliates-pro' ); ?></h3>

<p>
    <?php _e( 'This tool lets you export a properly formatted CSV file containing a list of all the affiliate links on this website. This CSV file will be compatible with the ThirstyAffiliates CSV Importer.' , 'thirstyaffiliates-pro' ); ?>
</p>

<p>
    <?php _e( 'You can choose to only export the links from a specific category.' , 'thirstyaffiliates-pro' ); ?>
</p>

<?php if ( isset( $upload_dir[ 'error' ] ) && ! empty( $upload_dir[ 'error' ] ) ) : ?>

    <div class="error">
        <p><?php _e( 'Before you can upload your export file, you will need to fix the following error:' , 'thirstyaffiliates-pro' ); ?></p>
        <p><strong><?php echo $upload_dir[ 'error' ]; ?></strong></p>
    </div>

<?php else : ?>

    <form class="tap-round-box" id="tap_export_csv_form" enctype="multipart/form-data" method="post" action="<?php echo esc_url_raw( $nonce_action ); ?>">

        <p>
            <label for="export_category">
                <?php _e( 'Category to export from:' , 'thirstyaffiliates-pro' ); ?>
            </label>
            <select id="export_category" name="export_category">
                <option value="all" selected="selected"><?php _e( '-- All Categories --' , 'thirstyaffiliates-pro' ); ?></option>
                <?php foreach ( $categories as $category ) : ?>
                    <option value="<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <input type="submit" class="button-primary" name="exportsubmit" value="<?php esc_attr_e( 'Export affiliate links' , 'thirstyaffiliates-pro' ); ?>" />
            <span class="spinner"></span>
        </p>


    </form>

    <script type="text/javascript">
    jQuery( document ).ready( function( $ ) {

        $( "#export_category" ).change(function() {

            var $export_form = $( "#tap_export_csv_form" );
                action_val   = $export_form.attr ( "action" );

            action_val = action_val.replace( /export_category=[a-z0-9]*/ , "export_category=" + $(this).val() );
            $export_form.attr( "action" , action_val );
        });

        $( "#export_category" ).trigger( 'change' );

    } );
    </script>

<?php endif; ?>
