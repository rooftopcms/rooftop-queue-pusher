<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://errorstudio.co.uk
 * @since      1.0.0
 *
 * @package    Rooftop_Queue_Pusher
 * @subpackage Rooftop_Queue_Pusher/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rooftop_Queue_Pusher
 * @subpackage Rooftop_Queue_Pusher/admin
 * @author     Error <info@errorstudio.co.uk>
 */
class Rooftop_Queue_Pusher_Admin {

    static $menu_update_hook_called = false;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        $this->redis = new Predis\Client( [
            'scheme' => 'tcp',
            'host'   => REDIS_HOST,
            'port'   => REDIS_PORT,
            'password' => REDIS_PASSWORD
        ] );

        $this->blog_id = get_current_blog_id();

        if( function_exists('get_blog_details') ) {
            $details = get_blog_details($this->blog_id, 'domain', false);
            $domain = $details->domain;
            $sub_domain = explode(".", $domain)[0];
            $this->sub_domain = $sub_domain;
        }

        Resque::setBackend(REDIS_HOST.":".REDIS_PORT,REDIS_DB);

        Resque_Event::listen('afterPerform', array('RooftopJob', 'afterPerform'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rooftop_Queue_Pusher_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Queue_Pusher_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rooftop-queue-pusher-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rooftop_Queue_Pusher_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Queue_Pusher_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rooftop-queue-pusher-admin.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * @param $post_id
     *
     * post was saved - check the status and trigger a webhook request
     */
    function trigger_webhook_save($post_id) {
        $post = get_post($post_id);

        /**
         * Don't trigger the webhook request for save Menu Item's, or content that in a temporary or draft state
         */
        if( $post->post_type === 'nav_menu_item' || ( in_array($post->post_status, array('auto-draft', 'draft', 'inherit')) && $post->post_date == $post->post_modified ) ) {
            return;
        }

        if( $post->post_type === 'page' && $post->post_parent == null ) {
            // check for menus that have this page as a member
            $post_is_in_menus = $this->post_menu_ids( $post );
            if( ! empty( $post_is_in_menus ) ) {
                foreach( $post_is_in_menus as $menu_id ) {
                    $this->trigger_menu_save( $menu_id, $force_update = true );
                }
            }
        }

        $webhook_request_body = array(
            'id' => $post_id,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'type' => $post->post_type,
            'status' => $post->post_status
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    function post_menu_ids( $post ) {
        $menus = wp_get_nav_menus();
        $menu_ids = array();

        foreach( $menus as $menu ) {
            $menu_object = wp_get_nav_menu_items( $menu->term_id );
            $menu_item_ids = wp_list_pluck( $menu_object, 'object_id' );

            if( in_array( $post->ID, $menu_item_ids ) ) {
                $menu_ids[] = $menu->term_id;
            }
        }

        return $menu_ids;
    }

    /**
     * @param $menu_id
     *
     * trigger a webhook for a saved menu
     */
    function trigger_menu_save( $menu_id, $force_update = false ) {

        // wp triggers this event (wp_update_nav_menu) twice - check the static var is false before generating the webhook
        $updating = self::$menu_update_hook_called;

        if( !$updating || $force_update ) {
            self::$menu_update_hook_called = true;

            $menu = wp_get_nav_menu_object( $menu_id );

            $webhook_request_body = array(
                'id' => $menu_id,
                'type' => 'menu',
                'slug' => $menu->slug,
                'name' => $menu->name,
                'blog_id' => $this->blog_id,
                'sub_domain' => $this->sub_domain,
            );

            $this->send_webhook_request( $webhook_request_body );
        }

    }

    /**
     * @param $menu_id
     *
     * fire a webhook for a deleted nav menu
     */
    function trigger_menu_delete( $menu_id ) {
        $webhook_request_body = array(
            'id' => $menu_id,
            'type' => 'menu',
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'status' => 'deleted'
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    /**
     * @param $menu_id
     * @param $menu_item_db_id
     * @param $menu_item_data
     *
     * called whenever a menu item is added or changed.
     */
    function trigger_menu_item_save( $menu_id, $menu_item_db_id, $menu_item_data ) {
        /*
         * since we don't want to trigger a webhook for a menu item when editing a menu (we'd get 1 hook
         * per-action), we instead hook into this and check for the _status key in the request.
         * This allows  us to trigger menu webhooks when the user adds a top-level page and they have a
         * menu with "Automatically add new top-level pages to this menu" enabled
         */
        if( array_key_exists( '_status', $_REQUEST) ) {
            $this->trigger_menu_save( $menu_id );
        }
    }

    /**
     * @param $post_id
     *
     * post was deleted
     */
    function trigger_webhook_delete( $post_id ) {
        $post = get_post($post_id);

        if( $post->post_type === 'nav_menu_item' || ( in_array( $post->post_status, array('revision')) && $post->post_date == $post->post_modified ) ) {
            return;
        }

        $webhook_request_body = array(
            'id' => $post_id,
            'type' => $post->post_type,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'status' => 'deleted'
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    function trigger_created_term( $term_id, $tt_id, $taxonomy_slug ) {
        $term = get_term( $term_id );
        $webhook_request_body = array(
            'id' => $term_id,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'type' => 'term',
            'taxonomy_slug' => $taxonomy_slug,
            'term_slug' => $term->slug,
            'status' => 'created'
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    function trigger_edited_terms( $term_id, $taxonomy_slug ) {
        $term = get_term( $term_id );
        $webhook_request_body = array(
            'id' => $term_id,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'type' => 'term',
            'taxonomy_slug' => $taxonomy_slug,
            'term_slug' => $term->slug,
            'status' => 'updated'
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    function trigger_delete_term( $term_id, $tt_id, $taxonomy_slug ) {
        $term = get_term( $term_id );
        $webhook_request_body = array(
            'id' => $term_id,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'type' => 'term',
            'taxonomy_slug' => $taxonomy_slug,
            'term_slug' => $term->slug,
            'status' => 'deleted'
        );

        $this->send_webhook_request( $webhook_request_body );
    }


    // this hook is RT specific and triggered by calling do_action('rooftop/created_taxonomy', $id, $slug);
    function trigger_rooftop_created_taxonomy( $taxonomy_slug ) {
        $webhook_request_body = array(
            'id' => $taxonomy_slug,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'type' => 'taxonomy',
            'slug' => $taxonomy_slug,
            'status' => 'created'
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    // this hook is RT specific and triggered by calling do_action('rooftop/created_taxonomy', $id, $slug);
    function trigger_rooftop_deleted_taxonomy( $taxonomy_slug ) {
        $webhook_request_body = array(
            'id' => $taxonomy_slug,
            'blog_id' => $this->blog_id,
            'sub_domain' => $this->sub_domain,
            'type' => 'taxonomy',
            'status' => 'deleted'
        );

        $this->send_webhook_request( $webhook_request_body );
    }

    /**
     * @param $request_body
     *
     * fetch all of the webhook endpoints out of redis and post the given request_body to it
     */
    function send_webhook_request($request_body) {
        foreach($this->get_webhook_endpoints() as $endpoint) {
            $request_body = apply_filters( 'prepare_webhook_payload', $request_body ); // filters to apply to all content types
            $request_body = apply_filters( 'prepare_'.$request_body['type'].'_webhook_payload', $request_body ); // content type specific filter (eg. 'prepare_event_webhook_payload')

            $rooftop_job_id = md5(uniqid('', true));
            $args = array( 'rooftop_job_id' => $rooftop_job_id, 'endpoint' => $endpoint, 'body' => $request_body );

            Resque::enqueue( 'content', 'PostSaved', $args, true );
        }
    }

    /**
     * @return array|mixed|object
     *
     * Fetch the webhook endpoints from redis and decode form json
     */
    private function get_webhook_endpoints() {
        $endpoints = get_blog_option( get_current_blog_id(), 'webhook_endpoints', array() );

        if(!is_array($endpoints)) {
            return array();
        }

        return $endpoints;
    }
}
