<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.howardehrenberg.com
 * @since      1.0.0
 *
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @author     Howard Ehrenberg <howard@howardehrenberg.com>
 */
class dd_cf7_constant_contact_v3_Admin {

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
	 * @param      string    $plugin_name The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->admin_includes();
        new dd_cf7_ctct_admin_settings;
		new dd_cf7_form_admin_settings;
		new dd_cf7_form_tag;
        new dd_ctct_api;
		new dd_cf7_ctct_additional_settings;
	}
    
    /**
     * Admin Includes for Plugin
     *
     * @since    1.0.0
     */
    private function admin_includes(){
        include('class-dd-cf7-admin-settings.php');
		include('class-dd-cf7-admin-form-settings.php');
		include('class-dd-cf7-form-tag.php');
        include('class-dd-cf7-ctct-api.php');
		include('class-dd-cf7-extra-settings.php');
		include('class-dd-cf7-custom-fields.php');
    }
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/dd-cf7-constant-contact-v3-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . 'select2css', plugin_dir_url( __FILE__ ) . 'css/select2.min.css', false, '4.0.10', 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/dd-cf7-constant-contact-v3-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . 'select2', plugin_dir_url( __FILE__ ) . 'js/select2.min.js', array( 'jquery' ), '4.0.10', true );

	}

}
