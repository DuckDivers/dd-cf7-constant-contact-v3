<?php
/**
 * Class to add meta boxes to CF7 Form Page
 *
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @since    1.0.0
 */

class dd_cf7_form_admin_settings {
		
	public function __construct(){
		add_filter( 'wpcf7_editor_panels', array ($this, 'add_cf7_panel' ));
        add_action( 'wpcf7_save_contact_form', array($this , 'save_contact_form' ));
	}
	
	public $ctct_fields = array(
			'email_address' => 'E-Mail Address',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
            'job_title' => 'Job title',
            'company_name' => 'Company Name',
			'phone_home' => 'Home Phone Number',
            'phone_work' => 'Work Phone Number',
            'phone_other' => 'Other Phone',
			'street' => 'Street',
			'city' => 'City',
			'state' => 'State',
			'postal_code' => 'Zip/Postal Code',
			'country' => 'Country',
			);
	
	public function get_ctct_fields() {
		$ctct_fields = $this->ctct_fields;
		if (class_exists('ctct_custom_fields')){
			$lists = get_option('dd_cf7_ctct_custom_fields');
			foreach ($lists as $listid=>$name){
				$ctct_fields[$listid] = $name['label'];
			}
		}
		return $ctct_fields;
	}
	
	public function add_cf7_panel($panels) {
		if ( current_user_can( 'wpcf7_edit_contact_form' ) ) {
			$panels['dd-ctct-panel'] = array(
				'title'    => __( 'Constant Contact', 'dd-cf7-plugin' ),
				'callback' => array($this, 'panel_callback')
			);
		}
		return $panels;
	}

	public function panel_callback($form) {
        wp_enqueue_script('dd-cf7-constant-contact-v3');
        
		$settings = array();
		$form_id = (isset($_GET['post'])) ? $_GET['post'] : null;
		$lists = get_option('dd_cf7_mailing_lists');
        if (null !== $form_id) $settings = $this->dd_get_form_settings($form_id);

        // Define Initial Values
        $all_submissions = isset( $settings['all-submissions'] ) ? $settings['all-submissions'] : NULL;
        $saved_fields = isset( $settings['fields'] ) ? $settings['fields'] : NULL;
        $ignore_form = isset( $settings['ignore-form'] ) ? $settings['ignore-form'] : NULL;

        ?>
		<div class="wpcf7cf-inner-container">
			<h3><?php echo esc_html( __( 'Constant Contact', 'dd-cf7-plugin' ) ); ?></h3>
            
            <?php if (false !== $lists) :?>
			<div id="wpcf7cf-text-entries">
				<label class="bold-label">Choose the List </label>
				<select id="list" class="select2" name="cf7-ctct[chosen-lists][]" multiple>
					<?php foreach ($lists as $list => $name):
                        $selected = (isset($settings['chosen-lists']) && in_array( $list, $settings['chosen-lists'] ) )? ' selected="selected" ' : ''; 
                        ?>
						<option value="<?php echo $list;?>" <?php echo $selected;?>><?php echo $name;?></option>
					<?php endforeach;?>
				</select>
				<p class="info"><?php echo esc_html__('You may choose multiple lists, or use the ctct form tag on the form.', 'dd-cf7-plugin');?></p>
			</div>
            <?php else :?>
            <h3><?php echo esc_html__('You must enter your constant contact settings before completing these fields', 'dd-cf7-plugin');?></h3>
            <a href="<?php echo admin_url();?>/admin.php?page=dd_ctct">Update your settings</a>
            <?php endif;?>
		</div>
	    <?php
        
        // get all WPCF7 form fields
        $wpcf7_shortcodes = WPCF7_FormTagsManager::get_instance();
        $field_types_to_ignore = array( 'recaptcha', 'clear', 'submit' );
        $form_fields = array();
        foreach ( $wpcf7_shortcodes->get_scanned_tags() as $this_field ) {
            if ( ! in_array( $this_field['type'], $field_types_to_ignore ) ) {
                $form_fields[] = $this_field['name'];
            }
        }

		$all_fields = $form_fields;
        
		// start setting up Constant Contact settings fields
        
		$fields = array(
            'ignore-field' => array(
                'label'     => 'Used Shortcode?',
                'field'     => sprintf(
                    '<input id="ignore-form" name="cf7-ctct[ignore-form]" value="1" %s type="checkbox" />
                    <p class="desc"><label for="ignore-form">%s</label></p>',
                    checked( $ignore_form, true, false ),
                    'If you are using the [ctct] code on the form - you SHOULD check this box. Any lists chosen above will have no effect on the list the users subscribe to.'
                ),
            ),        
        );

		$ctct_fields = $this->get_ctct_fields();
		
        // add all CF7 fields to CTCT settings fields
        
        foreach ( $all_fields as $this_field ) {
            $fields_options = NULL;
			$fields_options .='<option value="">- - Select Field - -</option>';
            foreach ( $ctct_fields as $id => $label ) {
                $fields_options .= '<option value="' . $id . '"';
                if ( isset( $settings['fields'] ) && isset( $settings['fields'][$this_field] ) ) {
                    $fields_options .= in_array( $id, $settings['fields'][$this_field] ) ? ' selected="selected"' : '';
                }
                $fields_options .= '>' . $label . '</option>';
            }

            $fields[$this_field] = array(
                'label'     => '<code>' . esc_html( $this_field ) . '</code> Field',
                'field'     => sprintf(
                    '<label>
                        <select name="cf7-ctct[fields][%1$s][]" class="select2-field">
                            %2$s
                        </select>
                    </label>
                    <p class="desc">Add contents of the <code>%1$s</code> field to these Constant Contact field(s)</p>',
                    $this_field,
                    $fields_options
                )
            );
        }

        $rows = array();

        foreach ( $fields as $field_id => $field )
            $rows[] = sprintf(
                '<tr class="cf7-ctct-field-%1$s">
                    <th>
                        <label for="%1$s">%2$s</label><br/>
                    </th>
                    <td>%3$s</td>
                </tr>',
                esc_attr( $field_id ),
                $field['label'],
                $field['field']
            );

        printf(
            '<p class="cf7-ctct-message"></p>
            <table class="form-table cf7-ctct-table">
                %1$s
            </table>',
            implode( '', $rows ),
            $ignore_form ? 'disabled' : ''
        );

        
	}
    
    function save_contact_form( $cf7 ) {
        if ( ! isset( $_POST ) || empty( $_POST ) || ! isset( $_POST['cf7-ctct'] ) || ! is_array( $_POST['cf7-ctct'] ) ) {
            return;
        }

        $post_id = $cf7->id();

        if ( ! $post_id ) {
            return;
        }
        $data = sanitize_post($_POST['cf7-ctct']);
        if ( $_POST['cf7-ctct'] ) {
            update_post_meta( $post_id, '_ctct_cf7', $data );
        }
    }

    // retrieve WPCF7 CTCT Form Settings
    private function dd_get_form_settings( $form_id, $field = null, $fresh = false ) {
        $form_settings = array();

        if ( isset( $form_settings[ $form_id ] ) && ! $fresh ) {
            $settings = $form_settings[ $form_id ];
        } else {
            $settings = get_post_meta( $form_id, '_ctct_cf7', true );
        }

        $settings = wp_parse_args(
            $settings,
            array(
                '_ctct_cf7' => NULL,
            )
        );

        // Cache it for re-use
        $form_settings[ $form_id ] = $settings;

        // Return a specific field value
        if ( isset( $field ) ) {
            if ( isset( $settings[ $field ] ) ) {
                return $settings[ $field ];
            } else {
                return null;
            }
        }

        return $settings;
    }
}