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

	}

	public function page_layout() {

		// Check required user capability
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dd-cf7-plugin' ) );
		}
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url();?>admin.php?page=dd_ctct" class="nav-tab">API Settings</a>
			<a href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra" class="nav-tab nav-tab-active">Additional Settings</a>
		</h2> <?php 
		// Admin Page Layout
		echo '<div class="wrap">' . "\n";
		echo '	<h1>' . get_admin_page_title() . '</h1>' . "\n";
		echo '	<div class="card">' . "\n";
		echo '	<form action="options.php" method="post">' . "\n";

		settings_fields( 'dd_cf7_ctct_extra' );
		do_settings_sections( 'cf7_ctct_extra_settings' );
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
				<p class="info"><?php echo esc_html__('You may choose multiple lists, or use the ctct form tag on the form.', 'dd-cf7-plugin');?></p>
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
}