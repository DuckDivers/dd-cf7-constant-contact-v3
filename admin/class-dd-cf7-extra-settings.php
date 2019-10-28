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

	}

	public function page_layout() {

		// Check required user capability
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'dd-cf7-plugin' ) );
		}
        $active = 'none';
        if (isset($_GET['tab']) && $_GET['tab'] == 'custom_fields' ) {
            $active = 'custom';
        } else if (isset($_GET['tab']) && $_GET['tab'] == 'additional') {
            $active = 'additional';
        }
        
		?>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo admin_url();?>admin.php?page=dd_ctct" class="nav-tab">API Settings</a>
			<a href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra&tab=additional<?php echo ($active=='additional')?' nav-tab-active': '';?>" class="nav-tab">Additional Settings</a>			
            <a href="<?php echo admin_url();?>options-general.php?page=dd-ctct-extra&tab=custom_fields" class="nav-tab<?php echo ($active=='custom')?' nav-tab-active': '';?>">Custom Fields</a>
		</h2> <?php 
		// Admin Page Layout
        if ($active == 'custom'){
            $get_lists = new ctct_custom_fields();
            $load_lists = $get_lists->get_custom_fields();
            $lists = get_option('dd_cf7_ctct_custom_fields');
            echo '<div class="wrap">' . "\n";
            echo '	<h1>' . __('CTCT Custom Fields') . '</h1>' . "\n";
            echo '	<div class="card">' . "\n";
            echo '<pre>'; print_r($lists); echo '</pre>';
            echo '  </div>
                  </div>' . "\n";
        } else {
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
}