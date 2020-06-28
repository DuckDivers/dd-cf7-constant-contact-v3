<?php

class dd_cf7_ctct_additional_settings {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings'  ) );

	}

	public function add_admin_menu() {

		add_options_page(
			esc_html__( 'Contact Form 7 Constant Contact Additional Settings', 'dd-cf7-plugin' ),
			esc_html__( 'CTCT Extra Settings', 'dd-cf7-plugin' ),
			'manage_options',
			'dd-ctct-extra',
			array( $this, 'page_layout' )
		);

	}

	public function init_settings() {

		register_setting(
			'dd_cf7_ctct_extra',
			'cf7_ctct_extra_settings'
		);

		add_settings_section(
			'cf7_ctct_extra_settings_section',
			'',
			false,
			'cf7_ctct_extra_settings'
		);
		
		register_setting(
			'dd_cf7_optin_email',
			'dd_cf7_optin_email_settings'
		);
		
		add_settings_section(
			'cf7_ctct_resubscribe_email_section',
			'',
			false,
			'dd_cf7_optin_email_settings'
		);
				
		add_settings_field(
			'admin_email',
			__( 'Admin E-Mail', 'dd-cf7-plugin' ),
			array( $this, 'render_admin_email_field' ),
			'cf7_ctct_extra_settings',
			'cf7_ctct_extra_settings_section'
		);
		add_settings_field(
			'send_email',
			__( 'Send E-Mail?', 'dd-cf7-plugin' ),
			array( $this, 'render_send_email_field' ),
			'cf7_ctct_extra_settings',
			'cf7_ctct_extra_settings_section'
		);
        if ( 
              in_array( 
                'woocommerce/woocommerce.php', 
                apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) 
              ) 
            ) {
            add_settings_field(
                'add_to_wc_checkout', 
                __( 'Add to WooCommerce Checkout?'),
                array( $this, 'render_add_to_wc_field'),
                'cf7_ctct_extra_settings',
                'cf7_ctct_extra_settings_section'
            );

            add_settings_field(
                'wc_checkout_lists', 
                __( 'Choose WooCommerce CTCT Lists?'),
                array( $this, 'render_choose_wc_list'),
                'cf7_ctct_extra_settings',
                'cf7_ctct_extra_settings_section'
            );

            add_settings_field(
                'ctct_wc_checkout_text', 
                __( 'Opt-in Text?'),
                array( $this, 'render_wc_opt_in'),
                'cf7_ctct_extra_settings',
                'cf7_ctct_extra_settings_section'
                );
            
            }
		
		add_settings_field(
			'ctct_re_optin_form_url', 
			__( 'Re-Subscribe Form URL'),
			array( $this, 'render_re_optin_form_url'),
			'dd_cf7_optin_email_settings',
			'cf7_ctct_resubscribe_email_section'
			);
		
		add_settings_field(
			'ctct_re_optin_form_subject', 
			__( 'Re-Subscribe E-Mail Subject'),
			array( $this, 'render_resubscribe_subject'),
			'dd_cf7_optin_email_settings',
			'cf7_ctct_resubscribe_email_section'
			);
		
		add_settings_field(
			'cf7_ctct_resubscribe_email_content', 
			__('E-Mail Content', 'dd-cf7-plugin'), 
			array($this, 'resubscribe_email_content'), 
			'dd_cf7_optin_email_settings',
			'cf7_ctct_resubscribe_email_section'
		);

	}

	public function page_layout() {

		// Check required user capability
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dd-cf7-plugin' ) );
		}
        if (isset($_GET['do']) && $_GET['do'] == 'refresh_lists') {
            $api = new dd_ctct_api;
            $api->get_lists();
            $url = admin_url(). 'options-general.php?page=dd-ctct-extra';
            echo "<script>window.location.href='".$url."'</script>";
        }
		$active = 'main';
        if (isset($_GET['tab'])) {
            $active = $_GET['tab'];
        }
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url();?>admin.php?page=dd_ctct" class="nav-tab">API Settings</a>
			<a href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra" class="nav-tab <?php echo ($active == 'main') ? 'nav-tab-active' : '';?>">Additional Settings</a>
			<a href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra&tab=email" class="nav-tab <?php echo ($active == 'email') ? 'nav-tab-active' : '';?>">Re-Subscribe E-Mail</a>
		</h2> <?php 
		// Admin Page Layout
		echo '<div class="wrap">' . "\n";
		echo '	<h1>' . get_admin_page_title() . '</h1>' . "\n";
		echo '	<div class="card" id="tab_ctct_'.$active.'">' . "\n";
		echo '	<form action="options.php" method="post">' . "\n";
		
		if ($active == 'main'){
			settings_fields( 'dd_cf7_ctct_extra' );
			do_settings_sections( 'cf7_ctct_extra_settings' );
		} else {
			echo 'here';
			settings_fields('dd_cf7_optin_email');
			do_settings_sections('dd_cf7_optin_email_settings');
		}
		submit_button();

		echo '	</form>' . "\n";
		echo '</div></div>' . "\n";
				
	}

	function render_admin_email_field() {

		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_extra_settings' );

		// Set default value.
		$value = isset( $options['admin_email'] ) ? $options['admin_email'] : get_bloginfo('admin_email');

		// Field output.
		echo '<input type="email" name="cf7_ctct_extra_settings[admin_email]" class="regular-text admin_email_field" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '">';
		echo '<p class="description">' . __( 'E-Mail Address to notify if there is an error.', 'dd-cf7-plugin' ) . '</p>';

	}

	function render_send_email_field() {
		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_extra_settings' );
		
		if (false == $options) $options = array('send_email' => 'true');

		// Set default value.
		$value = isset( $options['send_email'] ) ? $options['send_email'] : 'false';

		// Field output.
		echo '<input type="checkbox" name="cf7_ctct_extra_settings[send_email]" class="send_email_field" value="true" ' . checked( $value, 'true' , false ) . '> ' . __( '', 'dd-cf7-plugin' );
		echo '<span class="description">' . __( 'Send an E-Mail to the Admin when Errors occur.', 'dd-cf7-plugin' ) . '</span>';

	}
	
    function render_add_to_wc_field() {
		// Retrieve data from the database.
		$options = get_option( 'cf7_ctct_extra_settings' );

		// Set default value.
		$value = isset( $options['add_to_wc_checkout'] ) ? $options['add_to_wc_checkout'] : '';

		// Field output.
		echo '<input type="checkbox" name="cf7_ctct_extra_settings[add_to_wc_checkout]" class="add_to_wc_checkout_field" value="checked" ' . checked( $value, 'checked', false ) . '> ';
		echo '<span class="description">' . __( 'Adds an opt-in box on the checkout for WooCommerce', 'dd-cf7-plugin' ) . '</span>';

	}
    
    function render_choose_wc_list(){
        wp_enqueue_script('dd-cf7-constant-contact-v3');
		$options = get_option( 'cf7_ctct_extra_settings' );
		$settings = isset( $options['wc_checkout_lists'] ) ? $options['wc_checkout_lists'] : array();
		$lists = get_option('dd_cf7_mailing_lists');
        ?>
            <?php if (false !== $lists) :?>
				<select id="list" class="select2" name="cf7_ctct_extra_settings[wc_checkout_lists][]" multiple>
					<?php foreach ($lists as $list => $name):
                        $selected = (isset($options['wc_checkout_lists']) && in_array( $list, $settings ) )? ' selected="selected" ' : ''; 
                        ?>
						<option value="<?php echo $list;?>" <?php echo $selected;?>><?php echo $name;?></option>
					<?php endforeach;?>
				</select>
				<p class="info"><?php echo esc_html__('You may choose multiple lists.', 'dd-cf7-plugin');?></p>
                <p class="info"><a class="button" href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra&do=refresh_lists">Refresh Mailing List Cache</a>
            <?php else :?>
            <h3><?php echo esc_html__('You must enter your constant contact settings before completing these fields', 'dd-cf7-plugin');?></h3>
            <a href="<?php echo admin_url();?>/admin.php?page=dd_ctct">Update your settings</a>
            <?php endif;?>
	    <?php
        
    }
    
    function render_wc_opt_in(){
        // Retrieve data from the database.
		$options = get_option( 'cf7_ctct_extra_settings' );

		// Set default value.
		$value = isset( $options['ctct_wc_checkout_text'] ) ? $options['ctct_wc_checkout_text'] : 'Please sign me up for your mailing list.';

		// Field output.
        echo '<input type="text" name="cf7_ctct_extra_settings[ctct_wc_checkout_text]" class="regular-text ctct_wc_checkout_text_field" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '">';

    }
	
    function render_resubscribe_subject(){
        // Retrieve data from the database.
		$options = get_option( 'dd_cf7_optin_email_settings' );

		// Set default value.
		$value = isset( $options['ctct_re_optin_form_subject'] ) ? $options['ctct_re_optin_form_subject'] : 'Resubscribe to ' . get_bloginfo('name');

		// Field output.
        echo '<input type="text" name="dd_cf7_optin_email_settings[ctct_re_optin_form_subject]" class="regular-text ctct_re_optin_form_subject" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_attr( $value ) . '">';

    }

    function render_re_optin_form_url(){
        // Retrieve data from the database.
		$options = get_option( 'dd_cf7_optin_email_settings' );

		// Set default value.
		$value = isset( $options['ctct_re_optin_form_url'] ) ? $options['ctct_re_optin_form_url'] : '';

		// Field output.
        echo '<input type="text" name="dd_cf7_optin_email_settings[ctct_re_optin_form_url]" class="regular-text ctct_re_optin_form_url" placeholder="' . esc_attr__( '', 'dd-cf7-plugin' ) . '" value="' . esc_url( $value ) . '" style="width: 100%">
		<p class="description">'. esc_attr__('Include the complete URL to the subscription form', 'dd-cf7-plugin').'</p>';

    }
	
	function resubscribe_email_content(){
		$options = get_option( 'dd_cf7_optin_email_settings' );
		$value = isset( $options['ctct_resubscribe_email_text'] ) ? $options['ctct_resubscribe_email_text'] : $this->default_email_text();
		echo '<textarea name="dd_cf7_optin_email_settings[ctct_resubscribe_email_text]" class="widefat ctct_resubscribe_email_text_field" rows="10">' . $value . '</textarea>';
		echo '<p class="description">' . __( 'Default Email Content. Variables include ', 'dd_theme' ) . '<code>{first_name} {last_name} {email} {form_url}</code> Please use basic HTML like <code>&lt;p&gt;&lt;br&gt;</code> etc for formatting.</p>';
	}
	
	private function default_email_text(){
		$text = 'Dear {first_name}, <br>
<p>Since you have previously unsubscribed from one of our mailing lists, Constant Contact requires that you must fill in one of their special subscription forms. If you would please visit {form_url} and fill in the form, you will be re-subscribed to our mailing list.</p>
<br>
Thanks,<br>
{blog_name} Team
';
		return $text;
	}
}