<?php

namespace ThirstyAffiliates_Pro\Models\CSV_Importer;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( ! class_exists( 'WP_Importer' ) ) return;  // Exit if WP_Importer doesn't exist
if ( ! defined( 'ABSPATH' ) ) exit;  // Exit if accessed directly

/**
 * Register importer class for ThirstyAffiliates Pro CSV Importer
 *
 * @since 1.0.0
 */
class CSV_Importer extends \WP_Importer {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * CSV Attachment ID.
     *
     * @since 1.0.0
     * @access private
     * @var int
     */
  private $id;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Plugin_Constants $constants        Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     */
  public function __construct( Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

  }

    /**
     * Registered handler for the CSV Importer. This function directs basically traffic depending on the step the import is up to.
     *
     * @since 1.0.0
     * @access public
     */
  public function handler() {

        // Get the current step
    $step = empty( $_GET[ 'step' ] ) ? 0 : (int) $_GET[ 'step' ];

    echo '<div class="wrap">';
    echo '<h2>' . __( 'ThirstyAffiliates CSV Importer' , 'thirstyaffiliates-pro' ) . '</h2>';

    switch ( $step ) {

      case 0 :
        $this->introduction();
        break;

      case 1 :
        check_admin_referer( 'tap_upload_csv_form' );

        echo '<h3>' . __( 'STEP 2 - importing CSV:' , 'thirstyaffiliates-pro' ) . '</h3>';
        echo '<p>' . __( 'We are now checking your CSV and importing links into the system.' , 'thirstyaffiliates-pro' ) . '</p>';

        if ( $this->upload_file() )
          $this->parse_csv();
                else
                    echo 'File upload error.';

        break;
    }

    echo '</div>'; // end .wrap
  }

    /**
     * Handles uploading of the CSV file.
     *
     * @since 1.0.0
     * @access public
     */
    private function upload_file() {

    $file = wp_import_handle_upload();

    if ( isset( $file[ 'error' ] ) ) {

      echo '<div class="error"><p><strong>ERROR:</strong> There was an error uploading your file.<br />';
      echo esc_html( $file[ 'error' ] ) . '</p></div>';
      return false;
    }

    $this->id = (int) $file[ 'id' ];

    return true;
  }

    /**
     * Parses the uploaded CSV file and tells the importer to import the link
     *
     * @since 1.0.0
     * @access public
     */
  private function parse_csv() {

    set_time_limit( 0 );

    $file                = get_attached_file( $this->id );
    $count               = 0;
    $first_row_flag      = true;
        $meta_keys           = array();

    if ( ( $handle = fopen( $file , "r" ) ) === false ) {

            echo '<div class="error"><p><strong>ERROR:</strong> Looks like there was something wrong with the upload,
       the file does not exist. Please check your permissions on the wp-content/uploads directory and try again.</div>';
      return;
        }

    while ( $data = fgetcsv( $handle , 0 , "," ) ) {

      $name         = trim( $data[0] );
      $url          = trim( $data[1] );
      $slug         = trim( $data[2] );
            $cats         = !empty( $data[3] ) ? explode( ';' , $data[3] ) : array();
      $images       = !empty( $data[4] ) ? explode( ';' , $data[4] ) : array();
      $geolinks     = !empty( $data[5] ) ? explode( ';' , $data[5] ) : array();
      $autokeywords = !empty( $data[6] ) ? str_replace(  ';' , ',' , $data[6] ) : '';

            // Store meta key
      if( $first_row_flag ) {

        for ( $i = 7; $i < count( $data ); $i++ ) {
                    $meta_keys[ $i ] = $data[ $i ];
                }

        $first_row_flag = false;
                continue;
      }

      // 1.2: Allow user to choose if they want to escape the urls or not
      $skip_escape = ( empty( $_GET['skip_escape'] ) ? 0 : (int) $_GET[ 'skip_escape' ] );

      if ( ! $skip_escape ) {
                $url = esc_url_raw( $url );
            }

      if ( filter_var( $url , FILTER_VALIDATE_URL ) === false ) {
                continue;
            }

      // Get all meta values
            $meta_values = array();
      for ( $i = 7; $i < count( $data ); $i++ ) {
                $meta_values[ $meta_keys[ $i ] ] = maybe_unserialize( $data[ $i ] );
            }

      $this->import_link( $name , $url , $slug , $cats , $images , $geolinks , $autokeywords , $meta_values );
      $count++;
    }

    echo '<div class="updated"><p>' . sprintf( __( 'All done. %d affiliate links imported successfully.' , 'thirstyaffiliates-pro' ) , $count ) . '</p></div>';
    echo '<p><a href="' . admin_url( 'edit.php?post_type=thirstylink' ) . '">' . __( 'View all affiliate links &rarr;' , 'thirstyaffiliates-pro' ) . '</a></p>';

  }

    /**
     * Given a name, url and categories list this function imports the affiliate
     *
     * @since 1.0.0
     * @access public
     *
     * @global int $user_ID Current logged in user ID.
     *
     * @param string $name         Affiliate Link post title.
     * @param string $url          Affiliate Link destination url.
     * @param string $slug         Affiliate Link post slug.
     * @param array  $cats         Affiliate Link list of categories.
     * @param array  $images       Affiliate Link attached images.
     * @param array  $geolinks     Affiliate Link list of geolocations links.
     * @param array  $autokeywords Affiliate Link list of keywords for autolinker.
     * @param array  $meta_values  Affiliate Link list of meta keys and its values.
     */
    private function import_link( $name , $url , $slug , $cats , $images , $geolinks , $autokeywords , $meta_values ) {

        global $user_ID;

    echo '<div class="tap-round-box">';
        echo '<p><strong>' . __( 'Importing link:' , 'thirstyaffiliates-pro' ) . '</strong> ' . $name . ' (' . $url . ') </p>';

    // 1.5: Allow user to override links with existing slugs
    $override_links = isset( $_GET[ 'override_links' ] ) && $_GET[ 'override_links' ];
    $post_id        = 0;

    if ( $override_links ) {

      $post = get_posts( array(
        'name'        => $slug,
        'post_type'   => 'thirstylink',
        'post_status' => 'publish',
        'numberposts' => 1
      ));

            if ( ! empty( $post ) && isset( $post[0] ) )
            $post_id = $post[0]->ID;
    }

    if ( $post_id ) {

      $update_post = array(
        'ID'           => $post_id,
        'post_title'   => $name,
        'post_content' => '',
        'post_status'  => 'publish',
      );

      $post_id = wp_update_post( $update_post );

    }

    if ( is_wp_error( $post_id ) ) {

            echo '<p><strong>Error importing link!</strong><br />';
      echo esc_html($post_id->get_error_message()) . '</p>';
        }

        $thirstylink = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_affiliate_link( $post_id );

    /* unset categories and images when $ovverideLinks is true */
    if ( $override_links ) {

      // unassign all thirstylink-category terms from affiliate link
      $this->_unassign_all_terms_from_link( $post_id );
    }

        // set properties
        $thirstylink->set_prop( 'name' , sanitize_text_field( $name ) );
        $thirstylink->set_prop( 'slug' , sanitize_text_field( $slug ) );
        $thirstylink->set_prop( 'destination_url' , $url );

        // process all meta keys that are under ThirstyAffiliates (_ta_)
        foreach ( $meta_values as $meta_key => $meta_value ) {

            if ( substr( $meta_key , 0 , 4 ) !== Plugin_Constants::META_DATA_PREFIX )
                continue;

            $prop_key  = str_replace( Plugin_Constants::META_DATA_PREFIX , '' , $meta_key );
            $thirstylink->set_prop( $prop_key , maybe_unserialize( $meta_value ) );
            unset( $meta_values[ $meta_key ] );
        }

        $is_saved = $thirstylink->save();
        $this->process_categories( $thirstylink->get_id() , $cats );

        update_post_meta( $thirstylink->get_id() , Plugin_Constants::META_DATA_PREFIX . 'image_ids' , $this->process_images( $post_id , $images , $name ) );
        update_post_meta( $thirstylink->get_id() , Plugin_Constants::META_DATA_PREFIX . 'geolocation_links' , $this->process_geolocation( $geolinks ) );
        update_post_meta( $thirstylink->get_id() , Plugin_Constants::META_DATA_PREFIX . 'autolink_keyword_list' , sanitize_text_field( $autokeywords ) );

        // process meta keys that are not for ThirstyAffiliates
        foreach ( $meta_values as $meta_key => $meta_value ) {

            $meta_value = gettype( $meta_value ) == 'array' ? maybe_unserialize( $meta_value ) : sanitize_text_field( $meta_value );
            update_post_meta( $thirstylink->get_id() , $meta_key , $meta_value );
        }

        if ( $is_saved ) {
            echo sprintf( __( '<p><a href="%s">Edit Link</a> | <a href="%s" target="_blank">View Link</a></p>' , 'thirstyaffiliates-pro' ),
                admin_url( 'post.php?post=' . $thirstylink->get_id() . '&action=edit' ),
                get_post_permalink( $thirstylink->get_id() )
            );
        }

    echo '</div>';
  }

    /**
     * Process images and set them to the Affiliate Link post.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int    $post_id Affiliate Link post ID.
     * @param array  $images  List of image urls to be processed and attached.
     * @param string $name    Affiiate Link post title.
     */
    private function process_images( $post_id , $images , $name ) {

        if ( ! is_array( $images ) || empty( $images ) )
            return;

        $override_links = empty( $_GET[ 'override_links' ] ) ? 0 : (int) $_GET[ 'override_links' ];
        $attachments    = ( $post_id ) ? get_post_meta( $post_id , Plugin_Constants::META_DATA_PREFIX . 'image_ids' , true ) : array();

        if ( ! is_array( $attachments ) )
            $attachments = array();

        foreach ( $images as $key => $img_url ) {

            $file          = array();
            $img_url       = trim( $img_url );
            $attachment_id = $this->_get_attachment_id_by_url( $img_url );

            if ( ! $img_url )
                continue;

            if ( in_array( $attachment_id , $attachments ) ) {
                echo 'The image: ' . $img_url . ' is already attached to the affiliate link (skipping).<br>';
                continue;
            }

            echo 'Downloading image: ' . $img_url . '<br>';

            $tmp_img = download_url( $img_url );

            // If error storing temporarily, unlink
            if ( is_wp_error( $tmp_img ) ) {

                @unlink( $file[ 'tmp_name' ] );
        $file[ 'tmp_name' ] = '';
        echo __( 'Error storing temporary image file, check permissions on upload directory.' , 'thirstyaffiliates-pro' ) . '<br>';
                continue;
            }

            // Set variables for storage. Fix filename for query strings
            preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i' , $img_url , $matches );
            $file[ 'name' ]     = basename( $matches[0] );
            $file[ 'tmp_name' ] = $tmp_img;

            // do the validation and storage stuff
      $img_id = media_handle_sideload( $file , $post_id , $name );

            if ( is_wp_error( $img_id ) || ! $img_id ) {

                @unlink( $file[ 'tmp_name' ] );
                echo __( 'Error storing image permanently, check permissions on upload directory.' , 'thirstyaffiliates-pro' ) . '<br>';
                continue;
            }

            // assign image to post
            $attachments[] = $img_id;
            echo __( 'Attaching image to link, success.' , 'thirstyaffiliates-pro' ) . '<br>';
        }

        return array_unique( $attachments );
    }

    /**
     * Process categories and set the terms to the Affiliate Link post.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int   $post_id   Affiliate Link post ID.
     * @param array $cat_names List of category names.
     */
    private function process_categories( $post_id , $cat_names ) {

        $terms = array();

        foreach ( $cat_names as $cat_name ) {

            $cat_slug = sanitize_title_with_dashes( trim( $cat_name ) );
            $term     = term_exists( $cat_slug , Plugin_Constants::AFFILIATE_LINKS_TAX );
            $term_id  = is_array( $term ) && isset( $term[ 'term_id' ] ) ? (int) $term[ 'term_id' ] : 0;

            if ( ! $term_id ) {
                $term = wp_insert_term( $cat_name , Plugin_Constants::AFFILIATE_LINKS_TAX , array(
          'parent'      => 0,
          'description' => '',
        ));

                $term_id = ! is_wp_error( $term ) ? (int) $term[ 'term_id' ] : 0;
            }

            if ( $term_id && gettype( $term_id ) == 'integer' )
                $terms[] = $term_id;
        }

        wp_set_object_terms( $post_id , $terms , Plugin_Constants::AFFILIATE_LINKS_TAX , true );
    }

    /**
     * Process images and set them to the Affiliate Link post.
     *
     * @since 1.0.0
     * @access private
     *
     * @param array $images  List of image urls to be processed and attached.
     * @return array Converted geolinks from old to new format.
     */
    private function process_geolocation( $csv_raw_geolinks ) {

        $old_geolinks = array();

        if ( ! is_array( $csv_raw_geolinks ) || empty( $csv_raw_geolinks ) )
            return $old_geolinks;

        foreach ( $csv_raw_geolinks as $raw_geolink ) {
            $temp = explode( ':' , $raw_geolink , 2 );
            $old_geolinks[ $temp[0] ] = $temp[1];
        }

        return $this->_helper_functions->convert_geolinks_old_to_new_format( $old_geolinks );
    }

    /**
     * Output the introductory text and the importer form
     *
     * @since 1.0.0
     * @access private
     */
    private function introduction() {

        $action         = admin_url( 'admin.php?import=tap_csv_importer&skip_escape=0&step=1&override_links=0' );
        $nonce_action   = wp_nonce_url( $action , 'tap_upload_csv_form' );
    $bytes          = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
    $max_size       = size_format( $bytes );
    $upload_dir     = wp_upload_dir();
        $csv_sample_url = $this->_constants->PLUGIN_DIR_URL() . 'sample.csv';
        $country_list   = $this->_constants->PLUGIN_DIR_URL() . 'countryList.xml';

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'csv-importer/view-csv-importer.php' );

    }

  /**
   * Removes all thirstylink-category assigned to a affiliate link
     *
   * @since 1.0.0
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param int $post_id Affiliate Link post ID.
   */
  private function _unassign_all_terms_from_link( $post_id ) {

    global $wpdb;

    $terms_db  = $wpdb->get_results( "SELECT `term_id` FROM $wpdb->term_taxonomy WHERE `taxonomy` = 'thirstylink-category'" , ARRAY_N );
    $all_terms = array_reduce( $terms_db , 'array_merge' , array() );
    $all_terms = array_map( 'intval' , $all_terms );

    wp_remove_object_terms( $post_id , $all_terms , 'thirstylink-category' );
  }

    /**
     * Get attachment ID by image URL.
     *
     * @since 1.0.0
     * @access private
     *
     * @param string $image_url URL of image to process.
     * @return int Attachment ID.
     */
    private function _get_attachment_id_by_url( $image_url ) {

        global $wpdb;

        if ( ! $image_url )
            return;

        return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';" , $image_url ) );
    }
}
