<?php
/**
 * Class for WooCommerce Activation
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @since    1.0.0
 */
class dd_wc_ctct_settings {
    
    public $submitted_values = array();
    
    public function __construct(){
        $options = get_option( 'cf7_ctct_extra_settings' );
		if (isset( $options['add_to_wc_checkout'] )) {
            add_action( 'woocommerce_after_checkout_billing_form', array ($this, 'add_wc_optin' ), 10);   
            add_action( 'woocommerce_thankyou', array ( $this, 'after_wc_order_submit' ), 10, 1);
            add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_ctct_field' ) );
        }
    }
    
    public function add_wc_optin( $checkout ){
        $options = get_option( 'cf7_ctct_extra_settings' );
        
        woocommerce_form_field( 'ctct_optin', array(
		'type'	=> 'checkbox',
		'class'	=> array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox ctct_optin'),
		'label'	=> $options['ctct_wc_checkout_text'],
		), '1' );
        
        echo "<style>.ctct_optin .optional{display:none;}</style>";
    }
    
    function save_ctct_field( $order_id ) {
        if ( isset ($_POST['ctct_optin'] ) ) update_post_meta ($order_id, 'ctct_optin', 1);
    }
    
    public function after_wc_order_submit($order_id){
        if (! $order_id ) {
            return;
        }
        
        // check to see if checkbox was checked
        if (null !== get_post_meta($order_id, 'ctct_optin', true)) {
            // Get User Info
            $this->submitted_values['email_address'] = get_post_meta($order_id, '_billing_email', true);
            $this->submitted_values['first_name'] = get_post_meta($order_id, '_billing_first_name', true);
            $this->submitted_values['last_name'] = get_post_meta($order_id, '_billing_last_name', true);
            $this->submitted_values['street'] = get_post_meta($order_id, '_billing_address_1', true);
            $this->submitted_values['city'] = get_post_meta($order_id, '_billing_city', true);
            $this->submitted_values['state'] = get_post_meta($order_id, '_billing_state', true);
            $this->submitted_values['postcode'] = get_post_meta($order_id, '_billing_postcode', true);
            $this->submitted_values['country'] = get_post_meta($order_id, '_billing_country', true);
            
            $this->push_to_constant_contact();
        }
    }
    
    public function push_to_constant_contact( $c = 1, $failed = null ){
		if (null !== $failed){
			$submitted_values = $failed;			
		} else {
			$submitted_values = $this->submitted_values;
		}
        
        $options = get_option( 'cf7_ctct_extra_settings' );
        if (!isset($options['wc_checkout_lists'])) return;                 
		$submitted_values['chosen-lists'] = $options['wc_checkout_lists'];
                        
        // Check if E-Mail Address is valid
        
        $api = new dd_ctct_api;
        
        $email = sanitize_email($submitted_values['email_address']);
        
        $exists = $api->check_email_exists($submitted_values['email_address']);
		$tname = 'ctct_process_failure_' . time();
		if ($exists == 'unauthorized'){
			if ($c > 2) {
				set_transient($tname, $submitted_values, 5 * DAY_IN_SECONDS);	
				return false;
			}
			$options = get_option( 'cf7_ctct_settings' );
            if (isset($options['refresh_token'])) {
		        dd_cf7_ctct_admin_settings::refreshToken($c);	
                $api->push_to_constant_contact($c+1);
            } else {
                $body = "<p>While Attempting to connect to Constant Contact from Contact Form ID {$submitted_values['formid']}, an error was encountered. api is a fatal error, and you will need to revisit the Constant Contact settings page and re-authorize the application.</p>";
					if ($api->wants_email()) wp_mail($api->get_admin_email(), 'Constant Contact API Error', $body, $api->email_headers());
				set_transient($tname, $submitted_values, 5 * DAY_IN_SECONDS);
                return false;
            }
		} elseif (false == $exists){
			$ctct = $api->create_new_subscription($submitted_values);
		} elseif ($exists == 'connection_error') {
			set_transient($tname, $submitted_values, 5 * DAY_IN_SECONDS);
            return false;
		} else {
			$ctct = $api->update_contact($submitted_values, $exists);
        }

		// If API Call Failed

		if (isset($ctct)){        
		  if (true !== $ctct['success']){
			ob_start();
                echo "{$ctct['message']}\r\n\r\n";  
                echo '<pre>'; print_r($submitted_values); echo '</pre>';
				$body = ob_get_clean();
				if ($api->wants_email()) wp_mail($api->get_admin_email(), 'Constant Contact API Error', $body, $api->email_headers());
                return false;
            } 
        }
        return true;
    }

}