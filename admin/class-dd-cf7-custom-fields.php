<?php
/**
 * Class for Custom Fields
 * @package    CTCT Premium
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @since    1.0.0
 */
class ctct_custom_fields extends dd_ctct_api {

	public function get_custom_fields(){
	
		$url = "{$this->api_url}contact_custom_fields";

		$args = array(
            "headers" => array(
                "Accept" => "*/*",
                "Accept-Encoding" => "gzip, deflate",
                "Authorization" => "Bearer {$this->get_api_key()}",
                "Content-Type" => "application/json",
            )
        );

		$response = wp_remote_get( $url, $args);
		$ctct = json_decode(wp_remote_retrieve_body($response));
		$code = wp_remote_retrieve_response_code($response);

		$custom_fields = array();

		if ( $code !== 200 ){
            if ( $code == 401 ){
				dd_ctct_api::refreshToken();	
			} else {
				return $code;		
			}
		} else {
			foreach ($ctct->custom_fields as $field){
				$custom_fields[sanitize_text_field($field->custom_field_id)] = array(
					'label' => sanitize_text_field($field->label),
					'name' => sanitize_text_field($field->name),
					'type' => sanitize_text_field($field->type),
				);
			}
			update_option('dd_cf7_ctct_custom_fields', $custom_fields);
			return true;
		}	
		
	}

}