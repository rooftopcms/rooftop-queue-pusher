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

        $this->redis_key = 'site_id:'.get_current_blog_id().':webhooks';
        $this->redis = new Predis\Client();

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

    function add_job_status_table($blog_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . "${blog_id}_completed_jobs";

        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = <<<EOSQL
CREATE TABLE $table_name (
    id MEDIUMINT NOT NULL AUTO_INCREMENT,
    job_class VARCHAR(256) NOT NULL,
    status INTEGER NOT NULL,
    message VARCHAR(256),
    payload VARCHAR(256) NOT NULL,
    user_id INTEGER NOT NULL,
    PRIMARY KEY(id)
)
EOSQL;

            dbDelta($sql);
        }
    }
    function remove_job_status_table($blog_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . "completed_jobs";
        $sql = <<<EOSQL
DROP TABLE $table_name;
EOSQL;

        $wpdb->query($sql);
    }


    /**
     * @param $post_id
     *
     * post was saved - check the status and trigger a webhook request
     */
    function trigger_webhook_save($post_id) {
        $post = get_post($post_id);

        if(in_array($post->post_status, array('auto-draft', 'draft', 'inherit')) && $post->post_date == $post->post_modified) {
            return;
        }

        $webhook_request_body = array(
            'id' => $post_id,
            'type' => $post->post_type,
            'status' => $post->post_status
        );

        $this->send_webhook_request($webhook_request_body);
    }

    /**
     * @param $post_id
     *
     * post was deleted
     */
    function trigger_webhook_delete($post_id) {
        $post = get_post($post_id);

        if(in_array($post->post_status, array('revision')) && $post->post_date == $post->post_modified) {
            return;
        }

        $webhook_request_body = array(
            'id' => $post_id,
            'status' => 'deleted'
        );

        $this->send_webhook_request($webhook_request_body);
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

            $args = array( 'endpoint' => $endpoint, 'body' => $request_body );

            Resque::enqueue('content', 'PostSaved', $args);
        }
    }

    /**
     * @param null $environment
     * @return array|mixed|object
     *
     * Fetch the webhook endpoints from redis and decode form json
     */
    private function get_webhook_endpoints($environment=null) {
        $endpoints = json_decode($this->redis->get($this->redis_key));
        if(!is_array($endpoints)) {
            return array();
        }

        if($environment){
            $endpoints = array_filter($endpoints, function($e) use($environment) {
                return $e->environment == $environment;
            });
        }

        return $endpoints;
    }
}
