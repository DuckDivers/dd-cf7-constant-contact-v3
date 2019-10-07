<?php
/**
 * Class for API Calls
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @since    1.0.0
 */

class dd_ctct_api {
    
	private $api_url = 'https://api.cc.email/v3/';
	private $c = 0;
	private $details = array('first_name'=>'', 'last_name'=>'', 'job_title'=>'', 'comapny_name'=>'', 'create_source'=>'', 'birthday_month'=>'', 'birthday_day'=>'', 'anniversary'=>'');
    private $street_address = array( 'kind'=>'', 'street' => '', 'city' => '', 'state' => '', 'postal_code' => '', 'country' => '' );
	
    public function __construct(){
		add_action( 'wpcf7_before_send_mail', array($this, 'cf7_process_form'));
        add_action( 'wpcf7_mail_sent', function($cf7){
            $this->push_to_constant_contact();
        });
		add_action( 'wpcf7_init', array($this, 'check_auth'));
    }
		
	private function get_api_key(){

		$options = get_option( 'cf7_ctct_settings' );
		
		return $options['access_token'];

	}
	
	public function check_auth(){
		// Make sure mailing lists are in place
		if ( null == (get_option('dd_cf7_mailing_lists')) || get_option('dd_cf7_mailing_lists') == '1' ){
			$this->get_lists();
		}
		/**
		 * TODO
		 * Check Authorization is Valid on CF7 Init
		 *
		 * @since    1.0.0
		 */
	}

	public function cf7_process_form(){
		$submitted_values = $this->get_form_data();
		if (false !== $submitted_values){
			set_transient('ctct_to_process', $submitted_values, 3 * MINUTE_IN_SECONDS );
		}
	}
    
    public function push_to_constant_contact(){
		        
        if ( false === ($submitted_values = get_transient('ctct_to_process') ) ) {
            return;
        } 		

        $submitted_values = maybe_unserialize(get_transient('ctct_to_process'));
                
        $exists = $this->check_email_exists($submitted_values['email_address']);
		
		if ($exists == 'unauthorized'){
			if ($this->c > 2) return false;
			$options = get_option( 'cf7_ctct_settings' );
			$reauth = new dd_cf7_ctct_admin_settings;
			$reauth->refreshToken();	
	        $exists = $this->check_email_exists($submitted_values['email_address']);
			$this->c++;
		} elseif (false == $exists){
			$ctct = $this->create_new_subscription($submitted_values);
		} else {
			$ctct = $this->update_contact($submitted_values, $exists);
        }
		
		// If API Call Failed
		if (false == $ctct){
			$body = '<p>While connecting to Constant Contact, there was an error with the submision.  The submitted data will follow.  This subscriber was not synced, and will have to be done manually.</p>';
			ob_start();
			echo '<pre>'; print_r($submitted_values); echo '</pre>';
			$body .= ob_get_clean();
			$headers = array('Content-Type: text/html; charset=UTF-8');
				wp_mail(get_bloginfo('admin_email'), 'Constant Contat API Error', $body, $headers);
		}
    }
    
	public function get_form_data(){
		$options = get_option( 'cf7_ctct_settings' );
		$lists = get_option('dd_cf7_mailing_lists');
		$submitted_values = array();

        $submission = WPCF7_Submission::get_instance();
        if ( $submission ) {
            $posted_data = $submission->get_posted_data();    
        }

        $settings = get_post_meta( $posted_data['_wpcf7'] , '_ctct_cf7', true );
		/**
		 * Check to see if the checkbox option is used or not
		 *
		 * @since    1.0.0
		 */
		if (isset($settings['ignore-form'])){
			$ctct_list = array();
			foreach($posted_data as $key => $value){
				$ctct = explode('-', $key);
				if($ctct[0] == 'ctct'){
					 $ctct_list[] = $value;
				}
			}
			if (!empty($ctct_list)){
				$submitted_values['chosen-lists'] = $ctct_list;
			} else {
				// if no checkbox is checked, return.
				return false;
			}
			
		} else {
			$submitted_values['chosen-lists'] = $settings['chosen-lists'];
		}				
		foreach ($settings['fields'] as $field=>$value){
            if (array_key_exists($field, $posted_data)) {
                $submitted_values[$value[0]] = $posted_data[$field];
            }
		}
		return $submitted_values;
	}
	// Retrieve Lists
	public function get_lists(){
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.cc.email/v3/contact_lists",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"Accept: */*",
			"Accept-Encoding: gzip, deflate",
			"Authorization: Bearer {$this->get_api_key()}",
			"Cache-Control: no-cache",
			"Connection: keep-alive",
			"Content-Type: application/json",
			"cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		
		$body = 'While attempting to retrieve the constant contact lists. \r\n';
		$body .= "cURL Error #:" . $err;
		if ($err) {
			wp_mail(get_bloginfo('admin_email'), 'Constant Contat API Error', $body);
			return false;
	    } else {
			$lists = json_decode($response, true);
			$lists_array = array();
			
			foreach ($lists['lists'] as $list){
				$lists_array[$list['list_id']] = $list['name'];
			}
			update_option( 'dd_cf7_mailing_lists', $lists_array);
			error_log(print_r($lists_array, true));

			return true;
		}		
	}
	
	public function check_email_exists($email){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $this->api_url . "contacts?email=".urlencode($email)."&include=street_addresses,list_memberships&include_count=false",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			"Authorization: Bearer " . $this->get_api_key(),
			"Content-Type: application/json",
			"cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		$body = 'While performing the check if email exists function, there was an error \r\n';
		$body .= "cURL Error #:" . $err;
		if ($err) {
			wp_mail(get_bloginfo('admin_email'), 'Constant Contat API Error', $body);
	    }
        $ctct = json_decode($response);
        
		if (empty($ctct->contacts)){
			if (isset($ctct->error_key)){
				ob_start();
				print_r($ctct, true);
				$body = ob_get_clean();
				error_log('Error Key: ', $body);
				wp_mail(get_bloginfo('admin_email'), 'Constant Contat API Error', $body);
				return 'unauthorized';
			} else {
				return false;				
			}
		} else {
			return $ctct;
		}
	}
	
	public function create_new_subscription($submitted_values){
		
		$names = $this->create_new_contact_array($this->details, $submitted_values);
		$address = $this->create_new_contact_array($this->street_address, $submitted_values);
	       
		$json_data = array_merge($names, array(
			"email_address" => array (
				"address" => $submitted_values['email_address'],
				"permission_to_send" => "explicit",	
			),
			"create_source" => "Contact",
			"street_addresses" => array(array_filter($address)),
			"list_memberships" => array_filter($submitted_values['chosen-lists']),
			)
		);
		
        $content_length = strlen(json_encode($json_data));
		
        /**
         * Prepare the API Call Initiate CURL
         *
         * @since    1.0.0
         */
        $curl = curl_init();
        
		curl_setopt_array($curl, array(
			CURLOPT_URL => "{$this->api_url}contacts",
			CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => json_encode($json_data),
			  CURLOPT_HTTPHEADER => array(
				"Accept: */*",
				"Accept-Encoding: gzip, deflate",
				"Authorization: Bearer {$this->get_api_key()}",
				"Cache-Control: no-cache",
				"Connection: keep-alive",
				"Content-Length: ". $content_length,
				"Content-Type: application/json",
				"Host: api.cc.email",
				"cache-control: no-cache"
			  ),
			));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		
		curl_close($curl);
        
		$body = 'While trying to add a new email address, there was an error \r\n';
		$body .= "cURL Error #:" . $err;
		if ($err) {
			wp_mail(get_bloginfo('admin_email'), 'Constant Contat API Error', $body);
			return false;
	    } else {
          return true;
        }
	}
	
	private function create_new_contact_array($item, $submitted_values){
		/**
		 * @param $item = array of personal details or address
		 * @param $submitted_values = form submission
		 *
		 * @since    1.0.0
		 */
		foreach ( $item as $key => $val ) {
			if (isset($submitted_values[$key])){
            	$item[$key] = $submitted_values[ $key ];
			} elseif ($key == 'kind'){
				$item[$key] = "home";
			} else {
				unset($item[$key]);
			}
        }
        return $item;
	}
    
    public function update_contact($submitted_values, $ctct_data){
        /**
         * Retrieve Transients from Form Submission
         *
		 * @param $submitted_values = Form Data from CF7
		 * @param $ctct_data = response from CTCT with Contact info 
         * @since    1.0.0
         */
        $ctct = $ctct_data->contacts[ 0 ];
        $ctct_addr = $ctct->street_addresses[ 0 ];

        // Merge List Memberships
        $list_memberships = array_unique( array_merge( $ctct->list_memberships, $submitted_values[ 'chosen-lists' ] ) );
        $lists = array();
        foreach ($list_memberships as $key=>$value){
            $lists[] = $value;
        }

        $deets = $this->build_ctct_array($ctct, $this->details, $submitted_values);
        $sa = $this->build_ctct_array($ctct_addr, $this->street_address, $submitted_values);

        // Build JSON Array for Put on CTCT
        $json_data = array_merge($deets, array(
            "email_address" => array(
                "address" => "{$submitted_values['email_address']}",
            ),
            "street_addresses" => array(array_filter($sa)),
            "list_memberships" => array_filter($lists),
            "update_source" => "Contact",
            )
         );

        $contact_id = $ctct_data->contacts[ 0 ]->contact_id;

        $content_length = strlen( json_encode( $json_data ) );

        $curl = curl_init();

        curl_setopt_array( $curl, array(
            CURLOPT_URL => "{$this->api_url}contacts/{$contact_id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode( $json_data ),
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Authorization: Bearer {$this->get_api_key()}",
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-Length: " . $content_length,
                "Content-Type: application/json",
                "Host: api.cc.email",
                "cache-control: no-cache"
            ),
        ) );

        $response = curl_exec($curl);
		// For Debug
		// error_log($response);
        $err = curl_error($curl);
		$body = 'While trying to update an existing contact, there was an error \r\n';
		$body .= "cURL Error #:" . $err;
		if ($err) {
			wp_mail(get_bloginfo('admin_email'), 'Constant Contat API Error', $body);
			return false;
	    } else {
          return true;
        }
        
    }
    
    public function build_ctct_array($ctct, $item, $submitted_values){
    /**
     * @param $ctct = fields from ctct api object
     * @param $item = array of fields being submitted to ctct - details or addresses
     * @param $submitted_values = cf7 form field submissions from transient
     * @since    1.0.0
     */
        foreach ( $item as $key => $val ) {
            if ( isset( $ctct->$key ) ) {
                if ( ( isset( $submitted_values[ $key ] ) && $submitted_values[ $key ] == $ctct->$key ) || !isset($submitted_values[$key])) {
                    $item[$key] = $ctct->$key;
                } 
                else {
                    $item[$key] = $submitted_values[ $key ];
                }
            } else {
                if ( isset( $submitted_values[ $key ] ) ) {
                    $item[$key] = $submitted_values[$key];
                }
                else {
                    unset($item[$key]);
                }
            }
        }

        return $item;
    }
}