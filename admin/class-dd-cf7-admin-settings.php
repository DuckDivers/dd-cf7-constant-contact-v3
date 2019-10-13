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
			__( 'Callback URL', 'dd-cf7-plugin' ),
			array( $this, 'render_api_callback_field' ),
			'cf7_ctct_settings',
			'cf7_ctct_settings_section'
		);
        add_settings_field(
			'admin_email',
			__( 'Admin E-Mail', 'dd-cf7-plugin' ),
			array( $this, 'render_admin_email_field' ),
			'cf7_ctct_settings',
			'cf7_ctct_settings_section'
		);

	}

	public function page_layout() {
		
		$logged_in = false;

		// Check required user capability
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dd-cf7-plugin' ) );
		}
		
		$options = get_option( 'cf7_ctct_settings' );
		$lists = get_option('dd_cf7_mailing_lists');

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

                $logged_in = true;
                $ct = (isset($options['token_time'])) ? $options['token_time'] : time() - 8000;
                $timediff = time() - $ct;

                if (isset($options['access_token']) && $timediff>7200){
                    $this->refreshToken();								
                }	

            }
        }
		
		$message = ($logged_in) ? __('Disconnect from Constant Contact', 'dd-cf7-plugin') : __('Connect to Constant Contact', 'dd-cf7-plugin');

		// Admin Page Layout
		echo '<div class="wrap">' . "\n";
        echo '  <img src="'.plugin_dir_url(__FILE__) .'/img/CTCT_horizontal_logo.png">';
        echo '	<h1>' . get_admin_page_title() . '</h1>' . "\n";
        echo '<div class="card">' . "\n";
		// Check for API Errors
		if (isset($options['error']) && !empty($options['error'])) {
			echo '<h4>' . __('There has been an error processing your credentials', 'dd-cf7-plugin'). '</h4>';	
			echo '<div class="alert-danger"><p>' . $options['error'] . '</p></div>';
		}
        echo '<p>';
        _e('These fields are required to connect this application to your Constant Contact account. You must set up a Constant Contact developer account if you don&rsquo;t already have one.' , 'dd-cf7-plugin');
        echo ' <a href="https://v3.developer.constantcontact.com/api_guide/getting_started.html" target="_blank">' . __('Constant Contact Guide', 'dd-cf7-plugin') . '</a>';
        echo '</p>';
		if ($logged_in){ 
			echo '<p><span class="dashicons dashicons-yes success" style="color: green;"></span> ';
			_e('You are connected to Constant Contact', 'dd-cf7-plugin');
			echo '</p>';
		}
        echo '	<form action="options.php" method="post">' . "\n";
		settings_fields( 'dd_cf7_ctct' );
		do_settings_sections( 'cf7_ctct_settings' );
		submit_button($message);
    
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
		echo '<p class="description">' . __( 'This is the callback URL for Constant Contact Application', 'dd-cf7-plugin' ) . '</p>';

	}
    
    function render_admin_email_field() {

		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_settings' );

		// Set default value.
		$value = isset( $options['admin_email'] ) ? $options['admin_email'] : get_bloginfo('admin_email');

		// Field output.
		echo '<input type="email" name="cf7_ctct_settings[admin_email]" class="regular-text admin_email_field" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '">';
		echo '<p class="description">' . __( 'E-Mail Address to notify if there is an error.', 'dd-cf7-plugin' ) . '</p>';

	}
	function performAuthorization(){
		// Create authorization URL
        
		$options = get_option('cf7_ctct_settings');
		$baseURL = "https://api.cc.email/v3/idfed";
		$authURL = $baseURL . "?client_id=" . $options['api_key'] . "&scope=account_update+contact_data&response_type=code" . "&redirect_uri=" . urlencode($options['api_callback']);
        echo '<script>window.location="'.$authURL.'"</script>';
		//wp_redirect($authURL);	
	}
	
	private function getAccessToken($redirectURI, $clientId, $clientSecret, $code) {
		$options = get_option('cf7_ctct_settings');
		$options['error'] = '';
		update_option('cf7_ctct_settings', $options);

		// Use cURL to get access token and refresh token
		$ch = curl_init();

		// Define base URL
		$base = 'https://idfed.constantcontact.com/as/token.oauth2';

		// Create full request URL
		$url = $base . '?code=' . $code . '&redirect_uri=' . $redirectURI . '&grant_type=authorization_code&scope=contact_data';
		curl_setopt($ch, CURLOPT_URL, $url);

		// Set authorization header
		// Make string of "API_KEY:SECRET"
		$auth = $clientId . ':' . $clientSecret;
		// Base64 encode it
		$credentials = base64_encode($auth);
		// Create and set the Authorization header to use the encoded credentials
		$authorization = 'Authorization: Basic ' . $credentials;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization));

		// Set method and to expect response
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Make the call
			$result = curl_exec($ch);
			curl_close($ch);
			return json_decode($result);
	}
	
	public function refreshToken() {
		$options = get_option( 'cf7_ctct_settings' );
		$refreshToken = $options['refresh_token'];
		$clientId = $options['api_key'];
		$clientSecret = $options['api_secret'];

		// Use cURL to get a new access token and refresh token
		$ch = curl_init();

		// Define base URL
		$base = 'https://idfed.constantcontact.com/as/token.oauth2';

		// Create full request URL
		$url = $base . '?refresh_token=' . $refreshToken . '&grant_type=refresh_token';
		curl_setopt($ch, CURLOPT_URL, $url);

		// Set authorization header
		// Make string of "API_KEY:SECRET"
		$auth = $clientId . ':' . $clientSecret;
		// Base64 encode it
		$credentials = base64_encode($auth);
		// Create and set the Authorization header to use the encoded credentials
		$authorization = 'Authorization: Basic ' . $credentials;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization));

		// Set method and to expect response
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Make the call
		$result = curl_exec($ch);
		curl_close($ch);
		error_log($result);
		$tokenData = json_decode($result);

		$options['refresh_token'] = $tokenData->refresh_token;
		$options['access_token'] = $tokenData->access_token;
		$options['token_time'] = time();

		update_option('cf7_ctct_settings', $options );
			
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
	
}