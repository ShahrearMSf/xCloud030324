<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Event_Notification module.
 *
 * @since 1.0.0
 */
class Event_Notification implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Settings_Extension.
     *
     * @since 1.0.0
     * @access private
     * @var Settings_Extension
     */
    private static $_instance;

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
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.0.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Settings_Extension
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

    /**
     * Register admin interfaces.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of admin interfaces.
     * @return array Filtered list of admin interfaces.
     */
    public function register_admin_interfaces( $interfaces ) {

        $interfaces[ 'edit-tap-event-notification' ] = 'manage_categories';

        return $interfaces;
    }

    /**
     * Register admin interfaces.
     *
     * @since 3.3.3
     * @access public
     *
     * @param array $interfaces List of menu items.
     * @return array Filtered list of menu items.
     */
    public function register_admin_menu_items( $menu_items ) {

        $slug                = 'edit-tags.php?taxonomy=' . Plugin_Constants::EVENT_NOTIFICATION_TAX . '&post_type=' . Plugin_Constants::AFFILIATE_LINKS_CPT;
        $menu_items[ $slug ] = 'manage_categories';

        return $menu_items;
    }

    /**
     * Register the 'thirstylink-category' custom taxonomy.
     *
     * @since 1.0.0
     * @access private
     */
    private function register_event_notification_taxonomy() {

        $labels = array(
        'name'                       => __( 'Event Notifications', 'thirstyaffiliates-pro' ),
        'singular_name'              => __( 'Event Notification', 'thirstyaffiliates-pro' ),
        'menu_name'                  => __( 'Event Notifications', 'thirstyaffiliates-pro' ),
        'all_items'                  => __( 'All Event Notifications', 'thirstyaffiliates-pro' ),
        'parent_item'                => __( 'Parent Event Notification', 'thirstyaffiliates-pro' ),
        'parent_item_colon'          => __( 'Parent Event Notification:', 'thirstyaffiliates-pro' ),
        'new_item_name'              => __( 'New Event Notification Name', 'thirstyaffiliates-pro' ),
        'add_new_item'               => __( 'Add New Event Notification', 'thirstyaffiliates-pro' ),
        'edit_item'                  => __( 'Edit Event Notification', 'thirstyaffiliates-pro' ),
        'update_item'                => __( 'Update Event Notification', 'thirstyaffiliates-pro' ),
        'view_item'                  => __( 'View Event Notification', 'thirstyaffiliates-pro' ),
        'separate_items_with_commas' => __( 'Separate items with commas', 'thirstyaffiliates-pro' ),
        'add_or_remove_items'        => __( 'Add or remove items', 'thirstyaffiliates-pro' ),
        'choose_from_most_used'      => __( 'Choose from the most used', 'thirstyaffiliates-pro' ),
        'popular_items'              => __( 'Popular Event Notifications', 'thirstyaffiliates-pro' ),
        'search_items'               => __( 'Search Event Notifications', 'thirstyaffiliates-pro' ),
        'not_found'                  => __( 'Not Found', 'thirstyaffiliates-pro' ),
        'no_terms'                   => __( 'No items', 'thirstyaffiliates-pro' ),
        'items_list'                 => __( 'Event notification list', 'thirstyaffiliates-pro' ),
        'items_list_navigation'      => __( 'Event notification list navigation', 'thirstyaffiliates-pro' )
      );

      $args = array(
        'labels'                     => $labels,
        'hierarchical'               => true,
        'public'                     => false,
        'show_ui'                    => true,
        'show_admin_column'          => false,
        'show_in_nav_menus'          => false,
        'show_tagcloud'              => false,
            'rewrite'                    => false,
            'capabilities'               => array(
                'manage_terms' => 'manage_categories',
            )
      );

      register_taxonomy( Plugin_Constants::EVENT_NOTIFICATION_TAX , Plugin_Constants::AFFILIATE_LINKS_CPT , apply_filters( 'tap_event_notification_taxonomy_args' , $args , $labels ) );
    }

    /**
     * Modify taxonomy metabox so it will display on the bottom of the right sidebar by default.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string  $post_type Post type of current post.
     * @param string  $context   Metabox context.
     * @param WP_Post $post      Current post WP_Post object.
     */
    public function modify_taxonomy_metabox( $post_type , $context , $post ) {

        if ( $post_type !== Plugin_Constants::AFFILIATE_LINKS_CPT )
            return;

        remove_meta_box( 'tap-event-notificationdiv' , Plugin_Constants::AFFILIATE_LINKS_CPT , 'side' );
        add_meta_box( 'tap-event-notificationdiv' , __( 'Event Notifications' , 'thirstyaffiliates-pro' ) , function() use ( $post ) {

            $box = array( 'args' => array( 'taxonomy' => Plugin_Constants::EVENT_NOTIFICATION_TAX ) );
            post_categories_meta_box( $post , $box );

            ?>
            <script>
                jQuery( '#tap-event-notification-add-toggle' ).remove();
            </script>
            <?php

        } , Plugin_Constants::AFFILIATE_LINKS_CPT , 'side' , 'low' );
    }

    /**
     * Edit event notification taxonomy columns.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $columns Taxonomy columns list.
     * @return array Filtered taxonomy columns list.
     */
    public function edit_taxonomy_columns( $columns ) {

        $new_columns = array();

        foreach ( $columns as $column_id => $column_name ) {

            // remove the description and slug columns
            if ( in_array( $column_id , array( 'description' , 'slug' ) ) )
                continue;

            if ( $column_id == 'posts' ) {

                $new_columns[ 'event_notification_type' ] = __( 'Notification Type' , 'thirstyaffiliates-pro' );
                $new_columns[ 'event_trigger_value' ]     = __( 'Trigger Value' , 'thirstyaffiliates-pro' );

            }

            $new_columns[ $column_id ] = $column_name;
        }

        return $new_columns;
    }

    /**
     * Edit event notification taxonomy columns.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $content     Taxonomy single column content.
     * @param string $column_name Taxonomy single column name.
     * @param int    $term_id     Current taxonomy term ID.
     * @return array Filtered taxonomy single column content.
     */
    public function edit_taxonomy_columns_content( $content , $column_name , $term_id ) {

        switch ( $column_name ) {

            case 'event_notification_type' :
                $notification_types = $this->get_event_notification_types();
                $index              = get_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_type' , true );
                $content            = isset( $notification_types[ $index ][ 'label' ] ) ? $notification_types[ $index ][ 'label' ] : $index;
                break;

            case 'event_trigger_value' :
                $content = get_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_trigger_value' , true );
                break;
        }

        return $content;
    }

    /**
     * Retrieves the event notification types.
     *
     * @since 1.0.0
     * @since 1.1.0 Added click_count_email_24hours notification type.
     * @access public
     *
     * @return array  Event notification types.
     */
    private function get_event_notification_types() {

        $notification_types = array(
            'click_count_email'         => __( 'Send an email when the link reaches a defined number of clicks.' , 'thirstyaffiliates-pro' ),
            'click_count_email_24hours' => __( 'Send an email when the link reaches a defined number of clicks within 24 hours.' , 'thirstyaffiliates-pro' )
        );

        return apply_filters( 'tap_event_notification_types' , $notification_types );
    }

    /**
     * Register custom taxonomy form fields.
     *
     * @since 1.0.0
     * @access public
     *
     * @param WP_Term $term     Current taxonomy term object.
     * @param string  $taxonomy Current taxonomy slug.
     */
    public function display_custom_edit_form_fields( $term , $taxonomy ) {

        $notification_types = $this->get_event_notification_types();
        $term_name          = $term->name;
        $recipient_email    = get_term_meta( $term->term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_recipient_email' , true );
        $notification_type  = get_term_meta( $term->term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_type' , true );
        $trigger_value      = get_term_meta( $term->term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_trigger_value' , true );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'event-notification/edit-event-notification-fields.php' );
    }

    /**
     * Register custom taxonomy form fields.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string  $taxonomy Current taxonomy slug.
     */
    public function display_custom_add_form_fields( $taxonomy ) {

        $notification_types = $this->get_event_notification_types();

        include( $this->_constants->VIEWS_ROOT_PATH() . 'event-notification/add-event-notification-fields.php' );
    }

    /**
     * Display the description for the add form.
     *
     * @since 1.0.0
     * @access public
     */
    public function display_custom_add_form_description() {

        echo ">"; // ends the <form> tag of the add form.

        echo '<div class="event-notification-description">';

        _e( '<p>Event notifications allow you to send a notification to someone when some significant event happens with your affiliate links.</p>
             <p>You can setup different event notifications here, then assign one or more to an affiliate link and ThirstyAffiliates will monitor that link for those events.</p>' , 'thirstyaffiliates-pro' );

        echo '</div'; // this is intended to not have the > of the tag.
    }

    /**
     * Save custom taxonomy form fields.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string  $taxonomy Current taxonomy slug.
     */
    public function save_custom_form_fields( $term_id ) {

        if ( isset( $_POST[ 'tap_event_notification_type' ] ) )
            update_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_type' , sanitize_text_field( $_POST[ 'tap_event_notification_type' ] ) );

        if ( isset( $_POST[ 'tap_event_notification_trigger_value' ] ) )
            update_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_trigger_value' , sanitize_text_field( $_POST[ 'tap_event_notification_trigger_value' ] ) );

        if ( isset( $_POST[ 'recipient_email' ] ) )
            update_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_recipient_email' , sanitize_email( $_POST[ 'recipient_email' ] ) );
    }

    /**
     * Trigger event notification before the last link redirect.
     *
     * @since 1.0.0
     * @since 1.1.0 Add click_count_email_24hours notification type
     * @access public
     *
     * @param Affiliate_Link $thirstylink ThirstyAffiliates affiliate link object.
     */
    public function trigger_event_notification( $thirstylink ) {

        $notifications = wp_get_post_terms( $thirstylink->get_id() , Plugin_Constants::EVENT_NOTIFICATION_TAX , array( 'fields' => 'ids' ) );

        if ( ! is_array( $notifications ) || empty( $notifications ) )
            return;

        foreach ( $notifications as $term_id ) {

            $recipient_email   = get_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_recipient_email' , true );
            $notification_type = get_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_type' , true );
            $trigger_value     = get_term_meta( $term_id , Plugin_Constants::META_DATA_PREFIX . 'event_notification_trigger_value' , true );

            switch ( $notification_type ) {

                case 'click_count_email' :

                    // NOTE: we are incrementing 1 here to include the current click action.
                    $clicks = $thirstylink->count_clicks() + 1;

                    if ( $clicks == $trigger_value )
                        $this->send_click_count_email_notification( $thirstylink , $trigger_value , $recipient_email );

                    break;

                case 'click_count_email_24hours' :

                    $utc    = new \DateTimeZone( 'UTC' );
                    $offset = new \DateTime( 'now' , $utc );

                    $offset->modify( '-24 hours' );

                    $clicks = $thirstylink->count_clicks( $offset->format( 'Y-m-d H:i:s' ) ) + 1;

                    if ( $clicks == $trigger_value )
                        $this->send_click_count_email_24hours_notification( $thirstylink , $trigger_value , $recipient_email );

                    break;

                default :
                    do_action( 'tap_trigger_event_notification' , $thirstylink , $notification_type , $trigger_value );
                    break;
            }
        }
    }

    /**
     * Send click count email notification.
     *
     * @since 1.0.0
     * @access public
     *
     * @param Affiliate_Link $thirstylink     ThirstyAffiliates affiliate link object.
     * @param string         $trigger_value   Event notification trigger value.
     * @param string         $recipient_email Event notification recipient email.
     */
    public function send_click_count_email_notification( $thirstylink , $trigger_value , $recipient_email ) {

        $args = apply_filters( 'tap_click_count_email_args', array(
            'to'          => $recipient_email ? $recipient_email : get_bloginfo( 'admin_email' ),
            'subject'     => sprintf( __( '%s affiliate link has reached %s clicks - %s' , 'thirstyaffiliates-pro' ) , $thirstylink->get_prop( 'name' ) , $trigger_value , get_bloginfo( 'name' ) ),
            'message'     => sprintf( __( '<h3>ThirstyAffiliates Event Notification</h3>
                                           <p>The <strong>%s</strong> affiliate link has reached <strong>%s clicks</strong>.</p>
                                           <p><a href="%s">Edit affiliate link</a> | <a href="%s">View affiliate link report</a></p>' , 'thirstyaffiliates-pro' ),
                                      $thirstylink->get_prop( 'name' ),
                                      $trigger_value,
                                      admin_url( 'post.php?post=' . $thirstylink->get_id() . '&action=edit' ),
                                      admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports&link_id=' . $thirstylink->get_id() )
                             ),
            'headers'     => array(),
            'attachments' => array()
        ) );

        extract( $args );
        ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->send_email( $to , $subject , $message , $headers , $attachments );
    }

    /**
     * Send click count email 24hours notification.
     *
     * @since 1.1.0
     * @access public
     *
     * @param Affiliate_Link $thirstylink     ThirstyAffiliates affiliate link object.
     * @param string         $trigger_value   Event notification trigger value.
     * @param string         $recipient_email Event notification recipient email.
     */
    public function send_click_count_email_24hours_notification( $thirstylink , $trigger_value , $recipient_email ) {

        $args = apply_filters( 'tap_click_count_email_24hours_args', array(
            'to'          => $recipient_email ? $recipient_email : get_bloginfo( 'admin_email' ),
            'subject'     => sprintf( __( '%s affiliate link has reached %s clicks within the last 24 hours - %s' , 'thirstyaffiliates-pro' ) , $thirstylink->get_prop( 'name' ) , $trigger_value , get_bloginfo( 'name' ) ),
            'message'     => sprintf( __( '<h3>ThirstyAffiliates Event Notification</h3>
                                           <p>The <strong>%s</strong> affiliate link has reached <strong>%s clicks</strong> within the last 24 hours.</p>
                                           <p><a href="%s">Edit affiliate link</a> | <a href="%s">View affiliate link report</a></p>' , 'thirstyaffiliates-pro' ),
                                      $thirstylink->get_prop( 'name' ),
                                      $trigger_value,
                                      admin_url( 'post.php?post=' . $thirstylink->get_id() . '&action=edit' ),
                                      admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports&link_id=' . $thirstylink->get_id() )
                             ),
            'headers'     => array(),
            'attachments' => array()
        ) );

        extract( $args );
        ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->send_email( $to , $subject , $message , $headers , $attachments );
    }






    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     * @inherit ThirstyAffiliates_Pro\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_event_notification' , 'yes' ) !== 'yes' )
            return;

        $this->register_event_notification_taxonomy();

    }

    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates_Pro\Interfaces\Model_Interface
     *
     * @since 1.0.0
     * @access public
     */
    public function run() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_event_notification' , 'yes' ) !== 'yes' )
            return;

        add_filter( 'manage_edit-' . Plugin_Constants::EVENT_NOTIFICATION_TAX . '_columns' , array( $this , 'edit_taxonomy_columns' ) );
        add_filter( 'manage_' . Plugin_Constants::EVENT_NOTIFICATION_TAX . '_custom_column' , array( $this , 'edit_taxonomy_columns_content' ) , 10 , 3 );
        add_action( 'do_meta_boxes' , array( $this , 'modify_taxonomy_metabox' ) , 10 , 3 );
        add_action( Plugin_Constants::EVENT_NOTIFICATION_TAX . '_add_form_fields' , array( $this , 'display_custom_add_form_fields' ) , 10 );
        add_action( Plugin_Constants::EVENT_NOTIFICATION_TAX . '_edit_form_fields' , array( $this , 'display_custom_edit_form_fields' ) , 10 , 2 );
        add_action( Plugin_Constants::EVENT_NOTIFICATION_TAX . '_term_new_form_tag' , array( $this , 'display_custom_add_form_description' ) , 9999 );
        add_action( 'create_' . Plugin_Constants::EVENT_NOTIFICATION_TAX , array( $this , 'save_custom_form_fields' ) , 10 );
        add_action( 'edited_' . Plugin_Constants::EVENT_NOTIFICATION_TAX , array( $this , 'save_custom_form_fields' ) , 10 );
        add_action( 'ta_before_link_redirect' , array( $this , 'trigger_event_notification' ) , 5 );

        // Register admin interface and menus.
        add_filter( 'ta_admin_interfaces' , array( $this , 'register_admin_interfaces' ) , 20 );
        add_filter( 'ta_menu_items' , array( $this , 'register_admin_menu_items' ) , 20 );
    }

}
