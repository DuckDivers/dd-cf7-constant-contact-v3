<?php
/**
 * Class for Settings Page
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @since    1.0.0
 */
class dd_cf7_ctct_admin_settings {
	
	private $api_url = 'https://api.cc.email/v3/';

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings'  ) );
        add_action( 'admin_footer', array( $this, 'add_enabled_icon' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_links_to_plugin_listing') , 10, 2 );
		add_filter( 'plugin_action_links_dd-cf7-constant-contact-v3/dd-cf7-constant-contact-v3.php' , array( $this, 'filter_action_links'), 10, 1);
		//add_action( 'admin_notices', array( $this, 'upsell_notice' ) );
	}

	public function add_admin_menu() {

		add_submenu_page(
			'wpcf7',
			esc_html__( 'Constant Contact Settings', 'dd-cf7-plugin' ),
			esc_html__( 'Constant Contact', 'dd-cf7-plugin' ),
			'manage_options',
			'dd_ctct',
			array( $this, 'page_layout' )
		);

	}

	public function init_settings() {
		register_setting(
			'dd_cf7_ctct',
			'cf7_ctct_settings'
		);

		add_settings_section(
			'cf7_ctct_settings_section',
			'',
			false,
			'cf7_ctct_settings'
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'dd-cf7-plugin' ),
			array( $this, 'render_api_key_field' ),
			'cf7_ctct_settings',
			'cf7_ctct_settings_section'
		);
		add_settings_field(
			'api_secret',
			__( 'API Secret', 'dd-cf7-plugin' ),
			array( $this, 'render_api_secret_field' ),
			'cf7_ctct_settings',
			'cf7_ctct_settings_section'
		);
        add_settings_field(
			'api_callback',
			__( 'Redirect URI', 'dd-cf7-plugin' ),
			array( $this, 'render_api_callback_field' ),
			'cf7_ctct_settings',
			'cf7_ctct_settings_section'
		);
	}
	public function page_layout() {

		if (isset($_GET['action']) && $_GET['action'] == 'disconnect'){
                delete_option( 'cf7_ctct_settings' );
                echo '<script>window.location="admin.php?page=dd_ctct"</script>';
		}
		
		$logged_in = false;
        
		// Check required user capability
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dd-cf7-plugin' ) );
		}
		
		$options = get_option( 'cf7_ctct_settings' ); 
		$lists = get_option('dd_cf7_mailing_lists');
        // Set up variables for function
        $check = array();
        $error = false;

        if (false !== $options){
	       if(isset($_GET["perform"]) || (!isset($options['oauth_performed']) && !isset($_GET["code"]))){
                if (!isset($options['access_token'])) $this->performAuthorization();
                }
            if (isset($_GET["code"]) && $_GET["code"]!="") {

                $tokenData = $this->getAccessToken($options['api_callback'], $options['api_key'], $options['api_secret'], $_GET["code"]);

                if (isset($tokenData->error_description)){
                    $options['error'] = $tokenData->error_description;
                }

                $options['oauth_performed'] = 1;
                $options['refresh_token'] = $tokenData->refresh_token;
                $options['access_token'] = $tokenData->access_token;
                $options['token_time'] = time();			

                update_option( 'cf7_ctct_settings', $options );

                $api_call = new dd_ctct_api;
                $api_call->get_lists();

                echo '<script>window.location="admin.php?page=dd_ctct"</script>';

            } else {

                $ct = (isset($options['token_time'])) ? $options['token_time'] : time() - 8000;
                $timediff = time() - $ct;

                if (!empty($options['access_token']) && !empty($options['refresh_token']) && $timediff>7200){
                    self::refreshToken();
                } 
            }
            if (!empty($options['access_token'])) {        
                $check = $this->check_logged_in($options['access_token']);
            } elseif (false !== $options) {
                $check['error'] = __('There is a problem with the connection. Please Reauthorize', 'dd-cf7-plugin');
                $check['logged_in'] = false;
                $check['message'] = __('Connect to Constant Contact', 'dd-cf7-plugin');
                $error = true;
            }
        } 
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url();?>admin.php?page=dd_ctct" class="nav-tab nav-tab-active">API Settings</a>
			<a href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra" class="nav-tab">Additional Settings</a>
		</h2> <?php 

		// Admin Page Layout
		echo '<div class="wrap">' . "\n";
        echo '  <img src="'.plugin_dir_url(__FILE__) .'/img/CTCT_horizontal_logo.png">';
        echo '	<h1>' . get_admin_page_title() . '</h1>' . "\n";
        echo '<div class="card">' . "\n";
		
        // Check for API Errors
		if (isset($options['error']) && !empty($options['error'])) {
			echo '<div class="alert-danger"><h4>' . __('There has been an error processing your credentials', 'dd-cf7-plugin'). '</h4>';	
			echo '<p>' . $options['error'] . '</p></div>';
		} elseif ( false !== $error && false !== $options ) {
			echo '<div class="alert-danger"><h4>' . __('There has been an error connecting to the Constant Contact API.', 'dd-cf7-plugin'). '</h4>';	
			echo '<p>' . $check['error'] . '</p></div>';
        } elseif ( false == $options  ) {
			echo '<div class="alert-info"><h4>' . __('You must enter your API Key and API Secret to connect to Constant Contact', 'dd-cf7-plugin'). '</h4></div>';
            $check['logged_in'] = false;
            $check['message'] = __('Connect to Constant Contact', 'dd-cf7-plugin');
        }

        echo '<p>';
        _e('These fields are required to connect this application to your Constant Contact account. You must set up a Constant Contact developer account if you don&rsquo;t already have one.' , 'dd-cf7-plugin');
        echo ' <a href="https://v3.developer.constantcontact.com/api_guide/getting_started.html" target="_blank">' . __('Constant Contact Guide', 'dd-cf7-plugin') . '</a>';
        echo '</p>';
		if ($check['logged_in']){ 
			echo '<p><span class="dashicons dashicons-yes success" style="color: green;"></span> ';
			_e('You are connected to Constant Contact', 'dd-cf7-plugin');
			echo '</p>';
		}
        echo '	<form action="options.php" method="post">' . "\n";
		settings_fields( 'dd_cf7_ctct' );
		do_settings_sections( 'cf7_ctct_settings' );
		
		echo '<div class="dd-ctct-submit-wrapper">';
		if ($check['logged_in']){
			$m2 = sprintf(__("'Please confirm you wish to disconnect from Constant Contact and remove API Keys from this application'", 'dd-cf7-plugin'));
			$path = 'admin.php?page=dd_ctct&action=disconnect';
			echo '<p class="submit"><a href="'.admin_url($path).'" onclick="return confirm('.$m2.');" class="button button-link-delete">Disconnect</a></p>';
		}
		submit_button($check['message']);
		echo '</div>';
		echo '	</form>' . "\n";
        echo '</div>' ."\n";
		echo '</div>' . "\n";

	}

	function render_api_key_field() {

		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_settings' );

		// Set default value.
		$value = isset( $options['api_key'] ) ? $options['api_key'] : '';

		// Field output.
		echo '<input type="text" name="cf7_ctct_settings[api_key]" class="regular-text api_key_field" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '">';

	}

	function render_api_secret_field() {

		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_settings' );

		// Set default value.
		$value = isset( $options['api_secret'] ) ? $options['api_secret'] : '';
        

		// Field output.
		echo '<input type="password" name="cf7_ctct_settings[api_secret]" class="regular-text api_secret_field" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '">';

	}
    
    function render_api_callback_field() {

		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_settings' );

		// Set default value.
		$value = isset( $options['api_callback'] ) ? $options['api_callback'] : admin_url() . 'admin.php?page=dd_ctct';

		// Field output.
		echo '<input type="text" name="cf7_ctct_settings[api_callback]" class="regular-text api_callback_field" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '" readonly>';
		echo '<p class="description">' . __( 'This is the Redirect URI for your Constant Contact Application.', 'dd-cf7-plugin' ) . '</p>';

	}
    
	function performAuthorization(){
		// Create authorization URL
        
		$options = get_option('cf7_ctct_settings');
		$baseURL = "https://api.cc.email/v3/idfed";
		$authURL = $baseURL . "?client_id=" . $options['api_key'] . "&scope=account_update+contact_data&response_type=code" . "&redirect_uri=" . urlencode($options['api_callback']);
        
        // Test URL before submitting
        $test_url = wp_remote_request($authURL);
        $response_code = wp_remote_retrieve_response_code($test_url);
        
        // If not 200 - throw error
        
        if ($response_code !== 200){
            echo '<div class="alert-danger" style="margin-top: 1rem;"><h4>' . __('There has been an error trying to connect to Constant Contact. Please verify that API Key, Secret, and Callback URL are correct and saved in your constant contact API Settings page.', 'dd-cf7-plugin'). '</h4></div>';
        } else {
            echo '<script>window.location="'.$authURL.'"</script>';
        }
	}
	
	private function getAccessToken($redirectURI, $clientId, $clientSecret, $code) {
		$options = get_option('cf7_ctct_settings');
		$options['error'] = '';
		update_option('cf7_ctct_settings', $options);

		$base = 'https://idfed.constantcontact.com/as/token.oauth2';
		$url = $base . '?code=' . $code . '&redirect_uri=' . $redirectURI . '&grant_type=authorization_code&scope=contact_data';
		// Set authorization header
		// Make string of "API_KEY:SECRET"
		$auth = $clientId . ':' . $clientSecret;
		// Base64 encode it
		$credentials = base64_encode($auth);
		// Create and set the Authorization header to use the encoded credentials
		$authorization = 'Basic ' . $credentials;
        $args = array(
            "headers" => array(
                "Authorization" => $authorization,
            )
        );
        $response = wp_remote_post($url, $args);
		$result = wp_remote_retrieve_body($response);
        return json_decode($result);
    }
	
	public static function refreshToken() {
		$options = get_option( 'cf7_ctct_settings' );
		$refreshToken = $options['refresh_token'];
		$clientId = $options['api_key'];
		$clientSecret = $options['api_secret'];
        // Define base URL
		$base = 'https://idfed.constantcontact.com/as/token.oauth2';
        // Create full request URL
		$url = $base . '?refresh_token=' . $refreshToken . '&grant_type=refresh_token';
		// Set authorization header
		// Make string of "API_KEY:SECRET"
		$auth = $clientId . ':' . $clientSecret;
		// Base64 encode it
		$credentials = base64_encode($auth);
		// Create and set the Authorization header to use the encoded credentials
		$authorization = 'Basic ' . $credentials;
        // Set Headers for wp_remote_post
        $args = array(
            "headers" => array(
                "Authorization" => $authorization,
            )
        );
        // Get Response
        $response = wp_remote_post($url, $args);
		$tokenData = json_decode(wp_remote_retrieve_body($response));
        $code = wp_remote_retrieve_response_code($response);

		if ($code == 200){	
			$options['refresh_token'] = $tokenData->refresh_token;
			$options['access_token'] = $tokenData->access_token;
			$options['token_time'] = time();
			update_option('cf7_ctct_settings', $options );
		} else {
		 	$body = "<p>An error occurred when trying to get a refresh token.  This is a fatal error, and you will need to revisit the Constant Contact settings page and re-authorize the application.</p>";
			$headers = array('Content-Type: text/html; charset=UTF-8');
	        $options = get_option('cf7_ctct_extra_settings');
			$admin_email = esc_attr($options['admin_email']);
                 wp_mail($admin_email, 'Constant Contact Admin Settings (line 342)', $body, $headers);
		}

        return;		
	}
    
    public function add_enabled_icon() {
		global $pagenow, $plugin_page;

		if ( empty( $plugin_page ) || empty( $pagenow ) ) {
			return;
		}

		if ( $pagenow === 'admin.php' && $plugin_page === 'wpcf7' && ! isset( $_GET['action'] ) && class_exists( 'WPCF7_ContactForm' ) ) {

			// Get the forms
			$forms = WPCF7_ContactForm::find();

			// If there are no forms, return
			if ( empty( $forms ) ) {
				return;
			}

			// Otherwise, loop through and see which ones have settings
			// for Constant Contact integration.
			$activeforms = array();

			foreach ( $forms as &$form ) {
				$cf_id = method_exists( $form, 'id' ) ? $form->id() : $form->id;
                
				$is_active = get_post_meta($cf_id, '_ctct_cf7');
				
				if ( ! empty( $is_active ) /*&& isset( $is_active[0]['ignore_form'] )*/ ) {
					$activeforms[] = $cf_id;
				}
			}
		
			// Reset the post data, possibly modified by `WPCF7_ContactForm::find()`.
			wp_reset_postdata();

			// If there are no forms with CTCT integration, get outta here
			if ( empty( $activeforms ) ) {
				return;
			}

			// Otherwise, add the icon to each row with integration.
			?>
			<style>
				.ctct_enabled {
					position: absolute;
					background: url('<?php echo plugins_url('img/ctct-favicon.png',__FILE__); ?>') right top no-repeat;
					height: 22px;
					width: 30px;
					margin-left: 10px;
					background-size: contain;
				}
			</style>
			<script>
				jQuery( document ).ready( function ( $ ) {
					// Convert forms array into JSON array
					$activeforms = $.parseJSON( '<?php echo json_encode($activeforms); ?>' );

					// For each visible forms row
					$( 'table.posts tr' ).each( function () {
						// Get the ID of the row
						id = parseInt( $( '.check-column input', $( this ) ).val() );

						// If the row is in the $activeforms array, add the icon span
						if ( $activeforms.indexOf( id ) >= 0 ) {
							$( 'td a.row-title', $( this ) ).append( '<span class="ctct_enabled" title="Constant Contact integration is enabled for this form."></span>' );
						}
					} );
				} );
			</script>
			<?php
		}
	}
	public function check_logged_in($access_token){
        $code = $this->get_code_status($access_token);
        if ($code == 401) {
            self::refreshToken();
    		$options = get_option( 'cf7_ctct_settings' );
            $code = $this->get_code_status($options['access_token']);
        }
        $error = null;        
        switch ($code){
            case 200:
                $logged_in = true;
                break;
            case 401: 
                $error = esc_html("The Access Token used is invalid.");
                $logged_in = false;
                break;
            case 501:
                $error = esc_html("Our internal service is temporarily unavailable.");
                $logged_in = false;
                break;    
            case 500:
                $error = "There was a problem with our internal service.";
                $logged_in = false;
                break;
            default: 
                $logged_in = false;
                $error = "Undefined Error Occurred. Please check your settings, API Key, and API Secret.";
                break;
        }
		$message = ($logged_in) ? __('Update Settings', 'dd-cf7-plugin') : __('Connect to Constant Contact', 'dd-cf7-plugin');
        
        $check = array('message'=>$message, 'error'=>$error, 'logged_in' => $logged_in);
        
        return $check;
    }
    public function get_code_status($access_token){
        $args = array(
            "headers" => array(
                "Accept" => "*/*",
                "Accept-Encoding" => "gzip, deflate",
                "Authorization" => "Bearer {$access_token}",
                "Content-Type" => "application/json",
            )
        );

        $response = wp_remote_get('https://api.cc.email/v3/contact_lists', $args);
        $code = wp_remote_retrieve_response_code($response);
        
        return $code;
    }
	public function add_links_to_plugin_listing($links, $file){
			if ( strpos( $file, 'dd-cf7-constant-contact-v3.php' ) !== false ) {
				$new_links = array(
						'donate' => '<a href="https://www.duckdiverllc.com/" target="_blank">Donate</a>',
						'settings' => sprintf( '<a href="'.admin_url("/admin.php?page=dd_ctct").'">%s</a>', __('Settings') )
						);
				$links = array_merge( $links, $new_links );
			}

			return $links;

	}
	public function filter_action_links( $links ) {
		 $links['settings'] = sprintf( '<a href="'.admin_url("/admin.php?page=dd_ctct").'">%s</a>', __('Settings') );
		 return $links;
		}
	
	public function upsell_notice(){ 
		$screen = get_current_screen();
	    $user_id = get_current_user_id();
		$count = get_user_meta($user_id, 'dd-ctct-cf7-notice-counter', true);
		if ($screen->id == 'toplevel_page_wpcf7' && ($count % 5 == 0)) :
		?>

				<div id="dd-ctct-notices" class="notice notice-info notice-large"><p>Want more Constant Contact fields?  Get fields like, Phone, Birthday, Anniversary, custom fields, and any available field from Constant Contact with the Premium Plugin</p></div>
		<?php endif;
		$count = (empty(intval($count))) ? 1 : (intval($count)) + 1;
		update_user_meta($user_id, 'dd-ctct-cf7-notice-counter', $count);
	}
}