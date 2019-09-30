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
       // add_action( 'wpcf7_add_meta_boxes', array($this , 'wpcf7_add_meta_boxes' ));
        add_action( 'wpcf7_save_contact_form', array($this, 'save_contact_form' ));
	}
	
	const ctct_fields = array(
			'email_address' => 'E-Mail Address',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'street' => 'Street',
			'city' => 'City',
			'state' => 'State',
			'postal_code' => 'Zip/Postal Code',
			'country' => 'Country',
			);
	
	public function add_cf7_panel($panels) {
		if ( current_user_can( 'wpcf7_edit_contact_form' ) ) {
			$panels['dd-ctct-panel'] = array(
				'title'    => __( 'Constant Contact', 'dd-cf7-plugin' ),
				'callback' => array($this, 'panel_callback')
			);
		}
		return $panels;
	}
	// add WPCF7 metabox

    function wpcf7_add_meta_boxes() {
        add_meta_box(
            'dd-ctct-settings',
            'Constant Contact',
            array($this, 'panel_callback'),
            NULL,
            'form',
            'low'
        );
    }
	public function panel_callback($form) {

		$form_id = $_GET['post'];
		$lists = get_option('dd_cf7_mailing_lists');
        $settings = $this->dd_get_form_settings($form_id);

        // Define Initial Values
        $all_submissions = isset( $settings['all-submissions'] ) ? $settings['all-submissions'] : NULL;
        $saved_fields = isset( $settings['fields'] ) ? $settings['fields'] : NULL;
        $ignore_form = isset( $settings['ignore-form'] ) ? $settings['ignore-form'] : NULL;

		?>
		<div class="wpcf7cf-inner-container">
			<h3><?php echo esc_html( __( 'Constant Contact', 'dd-cf7-plugin' ) ); ?></h3>


			<div id="wpcf7cf-text-entries">
				<label>Choose the List</label>
				<select id="list" class="select2">
					<?php foreach ($lists as $list => $name):?>
						<option value="<?php echo $list;?>"><?php echo $name;?></option>
					<?php endforeach;?>
				</select>	
			</div>
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

		// get saved fields and combine with WPCF7
        if ( $saved_fields ) {
            $all_fields = array_merge( $form_fields, array_keys( $saved_fields ) );
        } else {
            $all_fields = $form_fields;
        }
        // start setting up Constant Contact settings fields
        $fields = array(
            'ignore-field' => array(
                'label'     => 'Ignore this Contact Form',
                'field'     => sprintf(
                    '<input id="ignore-form" name="cf7-ctct[ignore-form]" value="1" %s type="checkbox" />
                    <p class="desc"><label for="ignore-form">%s</label></p>',
                    checked( $ignore_form, true, false ),
                    'Don&rsquo;t send anything from this form to Constant Contact - IF you are using the [ctct] code on the form - you SHOULD check this box.'
                ),
            ),        
        );

		$ctct_fields = $this::ctct_fields;
		
        // add all CF7 fields to Robly settings fields
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
                        <select name="cf7-ctct[fields][%1$s][]" %3$s class="select2-field">
                            %2$s
                        </select>
                    </label>
                    <p class="desc">Add contents of the <code>%1$s</code> field to these Constant Contact field(s)</p>',
                    $this_field,
                    $fields_options,
                    $ignore_form ? 'disabled' : ''
                )
            );
        }

        // add a hidden row to use for cloning
        $fields['custom-field-template'] = array(
            'label'     => '<input type="text" placeholder="Custom Field Name" name="custom-field-name" /> Field',
            'field'     => sprintf(
                '<label>
                    <select name="cf7-ctct[fields][%1$s][]" class="select2-field">
                        %2$s
                    </select>
                </label>
                <p class="desc">Add contents of the <code><span class="name">%1$s</span></code> field to these Robly field(s)</p>',
                'custom-field-template-name',
                str_replace( 'selected="selected"', '', $fields_options )
            )
        );

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
            </table>
            <p><button class="cf7-robly-add-custom-field button-secondary" %2$s>Add a custom field</button></p>',
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

        if ( $_POST['cf7-ctct'] ) {
            update_post_meta( $post_id, '_ctct_cf7', $_POST['cf7-ctct'] );
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