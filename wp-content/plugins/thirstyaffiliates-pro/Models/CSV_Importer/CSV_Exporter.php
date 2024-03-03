<?php

namespace ThirstyAffiliates_Pro\Models\CSV_Importer;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( ! class_exists( 'WP_Importer' ) ) return;  // Exit if WP_Importer doesn't exist
if ( ! defined( 'ABSPATH' ) ) exit;  // Exit if accessed directly

/*******************************************************************************
** ThirstyAffiliates_CSV_Exporter
** Register exporter class for ThirstyAffiliates CSV Exporter
** @since 1.3
*******************************************************************************/
class CSV_Exporter extends \WP_Importer {

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
     * Registered handler for the CSV Exporter. This function directs basically traffic depending on the step the export is up to.
     *
     * @since 1.0.0
     * @access public
     */
    public function handler() {

        // Get the current step
    $step = ( empty( $_GET[ 'step' ] ) ? 0 : (int) $_GET[ 'step' ] );

    echo '<div class="wrap">';
    echo '<h2>' . __( 'ThirstyAffiliates CSV Exporter' , 'thirstyaffiliates-pro' ) . '</h2>';

    switch ( $step ) {

      case 0 :
        $this->introduction();
        break;

      case 1 :
        check_admin_referer( 'tap_export_csv' );

        echo '<h3>' . __( 'STEP 2 - exporting CSV:' , 'thirstyaffiliates-pro' ) . '</h3>';
        echo '<p>' . __( 'We are now exporting your CSV file.' , 'thirstyaffiliates-pro' ) . '</p>';

        $this->export_csv();

        break;
    }

    echo '</div>';
  }

    /**
     * Output the introductory text and the exporter form
     *
     * @since 1.0.0
     * @access private
     */
  private function introduction() {

    $action       = admin_url( 'admin.php?import=tap_csv_exporter&export_category=all&step=1' );
        $nonce_action = wp_nonce_url( $action , 'tap_export_csv' );
    $bytes        = apply_filters( 'export_upload_size_limit', wp_max_upload_size() );
    $max_size     = size_format( $bytes );
    $upload_dir   = wp_upload_dir();
        $categories   = get_terms( Plugin_Constants::AFFILIATE_LINKS_TAX , array( 'hide_empty' => false ) );

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'csv-importer/view-csv-exporter.php' );
  }

    /**
     * Export the CSV file and provide a link to download
     *
     * @since 1.0.0
     * @access public
     */
    private function export_csv() {

    global $wpdb;

    $args = array(
      'post_type'      => Plugin_Constants::AFFILIATE_LINKS_CPT,
      'posts_per_page' => -1
    );

    if ( isset( $_GET[ 'export_category' ] ) && $_GET[ 'export_category' ] != 'all' ) {

      $export_category     = (int) sanitize_text_field( $_GET[ 'export_category' ] );
      $args[ 'tax_query' ] = array(
        array(
          'taxonomy' => Plugin_Constants::AFFILIATE_LINKS_TAX,
                'field'    => 'term_id',
                'terms'    => $export_category
        )
      );
    }

    $links = get_posts( $args );

    if ( ! is_wp_error( $links ) && ! empty( $links ) ) {

      $csv_values  = array();
      $csv_headers = array(
        'Name',
        'Destination URL',
        'Slug',
        'Categories (separated by semicolons)',
        'Images To Download & Attach (separated by semicolons; jpg/png/gif only)',
        'Geolocations Links (format: AU:http://google.com.au separated by semicolon)',
        'Autolinker Keywords (separated by semicolon)'
      );

            // get meta keys and include it on CSV headers.
      $meta_keys   = $this->get_thirstylink_registered_meta_keys();
            $csv_headers = array_merge( $csv_headers , $meta_keys );

      $csv_values[] = $csv_headers;

      foreach ( $links as $link ) {

                $thirstylink = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_affiliate_link( $link->ID );
                $csv_row     = array(
                    $thirstylink->get_prop( 'name' ),
                    $thirstylink->get_prop( 'destination_url' ),
                    $thirstylink->get_prop( 'slug' ),
                    $this->process_categories( $thirstylink->get_prop( 'categories' ) ),
                    $this->process_images( $thirstylink->get_prop( 'image_ids' ) ),
                    $this->process_geolocations( $thirstylink->get_prop( 'geolocation_links' ) ),
                    $this->process_autolink_keywords( $thirstylink->get_prop( 'autolink_keyword_list' ) )
                );

                foreach ( $meta_keys as $meta_key ) {

                    if ( substr( $meta_key , 0 , 4 ) === Plugin_Constants::META_DATA_PREFIX ) {

                        $prop_key  = str_replace( Plugin_Constants::META_DATA_PREFIX , '' , $meta_key );
                        $csv_row[] = maybe_serialize( $thirstylink->get_prop( $prop_key ) );

                    } else
                        $csv_row[] = maybe_serialize( get_post_meta( $thirstylink->get_id() , $meta_key , true ) );
                }

                $csv_values[] = $csv_row;
      }

      $file_name  = 'thirstyaffiliates-export-' . date( 'YmdHis' , current_time( 'timestamp' ) ) . '.csv';
      $upload_dir = wp_upload_dir();
      $file_path  = trailingslashit( $upload_dir[ 'basedir' ] ) . $file_name;
      $file_url   = trailingslashit( $upload_dir[ 'baseurl' ] ) . $file_name;

      $handle = fopen( $file_path , 'w' );

      if ( $handle ) {

        foreach ( $csv_values as $line ) {

          fputcsv( $handle , $line , ',' , '"' );
        }

      } else
                echo '<div class="error"><p>' . __( 'Could not open file for writing, check your upload directory permissions.' , 'thirstyaffiliates-pro' ) . '</p></div>';

      fclose( $handle );

      $count          = count( $links );
            $success_string = sprintf( __( 'All done. %d affiliate links exported successfully. <a href="%s">Click here to download your file.</a>' , 'thirstyaffiliates-pro' ) , $count , $file_url );

            echo '<div class="updated inline"><p>' . $success_string . '</p></div>';

    } else {
      echo '<div class="error"><p>' . __( 'No links found to export.' , 'thirstyaffiliates-pro' ) . '</p></div>';
    }

  }

    /**
     * Process affiliate link categories for export
     *
     * @since 1.0.0
     * @access private
     *
     * @return array List of registered metakeys
     */
    private function get_thirstylink_registered_meta_keys() {

        global $wpdb;

        $meta_keys    = array();
        $post_type    = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $metakeys_sql = $wpdb->get_results( "SELECT DISTINCT m.meta_key FROM $wpdb->posts as p, $wpdb->postmeta as m
            WHERE p.post_type = '$post_type'
            AND p.post_status = 'publish'
            AND p.ID = m.post_id
            AND m.meta_key != 'thirstyData'
            AND m.meta_key != '_edit_last'
            AND m.meta_key != '_edit_lock'
            AND m.meta_key != '_ta_geolocation_links'
            AND m.meta_key != '_ta_autolink_keyword_list'
            AND m.meta_key != '_ta_destination_url'
            AND m.meta_key != '_ta_image_ids'
            AND m.meta_key != '_ta_name'
            ORDER BY m.meta_key ASC"
        );

        foreach ( $metakeys_sql as $key => $val )
            $meta_keys[] = $val->meta_key;

        return $meta_keys;
    }

    /**
     * Process affiliate link categories for export
     *
     * @since 1.0.0
     * @access private
     *
     * @param array $categories List of Affiliate link category term objects.
     * @return string Processed category string list for export.
     */
    private function process_categories( $categories ) {

        if ( ! is_array( $categories ) || empty( $categories ) )
            return;

        $categories_string = '';
        $count             = 0;

        foreach ( $categories as $category ) {

            if ( $count > 0 )
                $categories_string .= ';';

            $categories_string .= wp_specialchars_decode($category->name);
            $count++;
        }

        return $categories_string;
    }

    /**
     * Process affiliate link images for export.
     *
     * @since 1.0.0
     * @access private
     *
     * @param array $image_ids List of image ids assigned to the affiliate link.
     * @param string String list of affiliate link image sources.
     */
    private function process_images( $image_ids ) {

        if ( ! is_array( $image_ids ) || empty( $image_ids ) )
            return;

        $images_string = '';
        $count         = 0;

        foreach ( $image_ids as $image_id ) {

            if ( $count > 0 )
                $images_string .= ';';

            $image = wp_get_attachment_image_src( $image_id , 'full' );

            if ( ! $image )
                continue;

            $images_string .= $image[ 0 ];
            $count++;
        }

        return $images_string;
    }

    /**
     * Process affiliate link geolocations for export. Convert to old format first then combine as string.
     *
     * @since 1.0.0
     * @access private
     *
     * @param array $categories List of geolocation links data.
     * @return string Processed geolocation links string list for export.
     */
    private function process_geolocations( $geolinks ) {

        $old_geolinks    = array();
        $geolinks_string = '';

        // convert to V2 format of geolinks data
        foreach ( $geolinks as $key => $destination_url ) {

            $countries     = explode( ',' , $key );
            $count         = 1;
            $first_country = '';

            foreach ( $countries as $country ) {

                if ( $count == 1 ) {

                    $old_geolinks[ $country ] = $destination_url;
                    $first_country            = $country;

                } else
                    $old_geolinks[ $country ] = $first_country;

                $count++;
            }
        }

        $count = 0;

        // combine as one string for export
        foreach ( $old_geolinks as $country => $geolink ) {

            if ( $count > 0 )
                $geolinks_string .= ';';

            $geolinks_string .= $country . ':' . $geolink;
            $count++;
        }

        return $geolinks_string;
    }

    /**
     * Process affiliate link autolinker keywords export.
     *
     * @since 1.0.0
     * @access private
     *
     * @param string $autokeywords List of autolinker keywords.
     * @return string Processed list of autolinker keywords for export.
     */
    private function process_autolink_keywords( $autokeywords ) {

        if ( empty( $autokeywords ) )
            return;

        return str_replace( ',' , ';' , $autokeywords );
    }

}
