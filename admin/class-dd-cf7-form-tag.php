<?php
/*
 * Class to add the Form Tag Generator to CF7 Form Page
 *
 * @package    dd_cf7_constant_contact_v3
 * @subpackage dd_cf7_constant_contact_v3/admin
 * @since    1.0.0
 */

class dd_cf7_form_tag {

	public function __construct(){
		add_action( 'wpcf7_init', array($this , 'dd_ctct_add_form_tag' ) );
        add_action( 'admin_init', array( $this, 'init_tag_generator'), 99 ); 
	}
 
	public function dd_ctct_add_form_tag() {
		wpcf7_add_form_tag( 'ctct', array($this, 'dd_form_tag_handler'), array('name-attr' => true) ); 
	}
 	public function init_tag_generator() {
             if (class_exists('WPCF7_TagGenerator')) {
                WPCF7_TagGenerator::get_instance()->add( 'ctct', __( 'Constant Contact', 'dd-cf7-plugin' ), (array($this,'dd_ctct_form_tag' )), array(
                        'id'    => 'wpcf7-tg-pane-dd-ctct',
                        'title' => __( 'Constant Contact', 'dd-cf7-plugin' ),
                ) );
            }
    }
	// Add Form Tag on Contact Form Front End.
    function dd_form_tag_handler( $tag ) {
		new WPCF7_FormTag( $tag );

		if ( empty( $tag->name ) )
			return '';
		$hidden = $tag->get_option('hidden')[0];
		$hide = (null !== $hidden && $hidden == 'true') ? true : false;
		$atts = array();
		$atts['class'] = $tag->get_option( 'class' )[0];
		$atts['id'] = $tag->get_option( 'id' , 'id', true);
		$atts['message'] = ( empty ($tag->get_option('ctct_label') ) ) ? 'Sign me up for your mailing list' : str_replace('+', ' ', $tag->get_option('ctct_label')[0]);
   		$listid = $tag->get_option('list')[0];
		$checked = ($tag->get_option('checked')[0]) ? $listid : '0';
		$inputid = (!empty($atts['id'])) ? 'id="'.$atts['id'].'" ' : 'ctct-form-'. $tag->name ;
    
        ob_start();
        if ($hide) : ?>
        
        <input type="hidden" name="ctct-list[<?php echo $tag->name;?>]" id="<?php echo $inputid;?>" value="<?php echo $listid;?>" /> 
        
        <?php else: ?>         
        <span class="wpcf7-form-control-wrap <?php echo $tag->name;?>">
            <span class="wpcf7-form-control wpcf7-checkbox <?php echo $atts['class'];?>" id="wrapper-for-<?php echo $inputid;?>">
                <span class="wpcf7-list-item-label">
                <input id="<?php echo $inputid;?>" type="checkbox" name="ctct-list[<?php echo $tag->name;?>]" value="<?php echo $listid;?>" <?php checked($checked, $listid);?>>
                <label for=<?php echo $inputid;?>><?php echo $atts['message'];?></label></span>
            </span>
        </span>
        
        <?php 
        endif;
        return ob_get_clean();
        // End of form tag output.
	}

        public function dd_ctct_form_tag($contact_form, $args){
            $args = wp_parse_args( $args, array() );  
        ?>

        <div id="wpcf7-tg-pane-wc_products" class="control-box">
                <fieldset>
                    <h4><?php _e('This form tag will add a checkbox to opt in to the Constant Contact list you choose here', 'dd-cf7-plugin' ); ?></h4>
                        <table class="form-table"><tbody>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'dd-cf7-plugin' ) ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /><br>
                                    <em><?php echo esc_html( __( 'This is the name of the tag as it will appear in your email setting tab', 'dd-cf7-plugin' ) ); ?></em>
                                </td>
                            </tr>
                            <?php $lists = get_option('dd_cf7_mailing_lists');?>
                            <tr>
                                <?php if (false !== $lists) :?>
                                <th scope="row"><?php echo esc_html( __( 'Choose the List', 'dd-cf7-plugin' ) ); ?></th>
                                <td>
                                    <fieldset>
                                    <legend class="screen-reader-text"><?php echo esc_html( __( 'Choose the List', 'dd-cf7-plugin' ) ); ?></legend>
                                        <select id="listChoice" name="list-choice" onChange="set_value()" class="select2">
                                                <option value=""> - - Choose the List - - </option>
                                            <?php asort($lists);
                                                foreach ( $lists as $list => $name):?>
                                                <option value="<?php echo $list;?>"><?php echo $name;?></option>
                                            <?php endforeach;?>
                                        </select><br>
                                        <em>Choose a list. The List ID will appear in the tag.</em>
                                    <input type="text" id="<?php echo esc_attr( $args['content'] . '-list' ); ?>" class="listvalue oneline option" name="list" style="display:none;" value="">
                                        <script type="text/javascript">
                                        function set_value(){
                                            var chosenList = jQuery('#listChoice').val();
                                            jQuery('input[name="list"]').val(chosenList);
                                        }	
                                        </script></td>
                                <?php else:?>
                                    <th></th>
                                    <td><h5>You must enter your constant contact settings before completing these fields</h5>
                                        <a href="<?php echo admin_url();?>/admin.php?page=dd_ctct">Update your settings</a>
                                    </td>
                                <?php endif;?>    
								</tr>
								<tr>
									<th scope="row"><label for="ctct_label"><?php echo esc_html( __( 'Checkbox Label (optional)', 'dd-cf7-plugin' ) ); ?></label>
									</th>
									<td>
                                    <input type="text" id="ctct_label" class="widefat urlencode" onblur="dd_set_box_text()" onkeyup="dd_set_box_text()"/>
                                    <input class="option oneline labelvalue" id="checkboxLabel" name="ctct_label" type="text" style="display:none" value="" />
										<em>Default text is: Sign me up for your newsletter</em>
                                        <script type="text/javascript">
                                        function dd_set_box_text(){
											jQuery('#ctct_label').focusout(function(){
											var text = jQuery('#ctct_label').val();
											text = text.split(' ').join('+');
											jQuery('#checkboxLabel').val(text).trigger('change');
										  });
                                        }</script>
									</td>
								</tr>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr( $args['content'] . '-checked' ); ?>"><?php echo esc_html( __( 'Make Checkbox Pre-Checked', 'dd-cf7-plugin' ) ); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" name="checked:true" id="<?php echo esc_attr( $args['content'] . '-checked' ); ?>" class="checkedvalue option" />
                                    <em><?php echo __('If checked, This will make the opt-in pre-checked','dd-cf7-plugin'); ?></em>
                                </td>
                            </tr>
							<tr>
								<th scope="row">
                                    <label for="<?php echo esc_attr( $args['content'] . '-hidden' ); ?>"><?php echo esc_html( __( 'Hidden checkbox', 'dd-cf7-plugin' ) ); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" name="hidden:true" id="<?php echo esc_attr( $args['content'] . '-hidden' ); ?>" class="checkedvalue option" />
                                    <em><?php echo __('This will make the checkbox hidden','dd-cf7-plugin'); ?></em>
                                </td>

							</tr>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class (optional)', 'dd-cf7-plugin' ) ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'ID (optional)', 'dd-cf7-plugin' ) ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" />
                                </td>
                            </tr>
                        </tbody></table>    
                </fieldset>
                <div class="insert-box" style="padding-left: 15px; padding-right: 15px;">
                    <div class="tg-tag clear"><?php echo __( "This will insert a dropdown menu with the product for RMA or Registraiton.", 'dd-cf7-plugin' ); ?><br /><input type="text" name="ctct" class="tag code" readonly="readonly" onfocus="this.select();" onmouseup="return false;" /></div>

                    <div class="submitbox">
                        <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
                    </div>
                </div>
            </div>
        <?php 

        } 
}