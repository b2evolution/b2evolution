<?php
/**
 * This file implements the user_register_standard_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );
load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_register_standard_Widget extends ComponentWidget
{
	var $icon = 'registered';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_register_standard' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'registration-form-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Registration form');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a normal registration form.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $current_User, $admin_url;

		// Get available templates:
		$context = 'registration';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Register'),
				),
				'title_disabled' => array(
					'label' => T_('Block title for disabled registration'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Registration Currently Disabled'),
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Set default blockcache to false and disable this setting because caching is never allowed for this widget
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog, $Settings, $Session, $Plugins, $dummy_fields;

		// Default params:
		$params = array_merge( array(
				'form_class_register'       => 'evo_form__register',
				'register_use_placeholders' => false, // Set TRUE to use placeholders instead of notes for input fields
				'register_field_width'      => 140,
				'register_form_params'      => NULL,
				'register_disp_home_button' => false, // Display button to go home when registration is disabled
				'register_disp_messages'    => false,
				'register_buttons_before'   => '<p class="center">',
				'register_buttons_after'    => '</p>',
			), $params );

		$this->init_display( $params );

		// Check invitation code if it exists and registration is enabled
		$invitation_code_status = check_invitation_code();

		if( isset( $this->BlockCache ) )
		{	// Do NOT cache some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		$master_template_code = $Settings->get( 'registration_master_template' );
		
		$TemplateCache = & get_TemplateCache();
		if( ! $TemplateCache->get_by_code( $master_template_code, false, false ) )
		{
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
			return false;
		}

		$render_template_objects = array();
		$Form = new Form( get_htsrv_url( 'login' ).'register.php', 'register_form', 'post' );
		$render_template_objects['Form'] = $Form;

		if( $register_user = $Session->get('core.register_user') )
		{	// Get an user data from predefined session (after adding of a comment):
			$login = preg_replace( '/[^a-z0-9_\-\. ]/i', '', $register_user['name'] );
			$login = str_replace( ' ', '_', $login );
			$login = substr( $login, 0, 20 );
			$email = $register_user['email'];

			$render_template_objects['register_user_data'] = array(
					'login' => $login,
					'email' => $email
				);

			$Session->delete( 'core.register_user' );
		}
		$used_tags = array();

		// Render MASTER quick template:
		// In theory, this should not display anything.
		// Instead, this should set variables to define sub-templates (and potentially additional variables)
		echo render_template_code( $master_template_code, /* BY REF */ $this->disp_params );

		// Check if requestes sub-template exists:
		$register_form_Template = $TemplateCache->get_by_code( $this->disp_params['reg1_template'], false, false );
		if( empty( $register_form_Template ) )
		{	// Display error when no or wrong template for the registration form:
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['reg1_template'].'</code>' ) );
			return false;
		}

		// Render registration template:
		$register_form = render_template_code( $this->disp_params['reg1_template'], $this->disp_params, $render_template_objects, $used_tags );

		// Get required fields defined by master template:
		$required_fields = isset( $this->disp_params['reg1_required'] ) ? array_map( 'trim', explode( ',', $this->disp_params['reg1_required'] ) ) : array();

		if( ! empty( $register_form ) )
		{
			echo $this->disp_params['block_start'];

			$this->disp_title( ! is_logged_in() && $invitation_code_status == 'deny' ? $this->disp_params['title_disabled'] : NULL );

			echo $this->disp_params['block_body_start'];

			// Check that all required fields are available in the template:
			$missing_fields = array();
			if( in_array( 'country', $required_fields ) && ! in_array( 'Form:country', $used_tags ) )
			{
				$missing_fields[] = 'country';
			}
			if( in_array( 'firstname', $required_fields ) && ! in_array( 'Form:firstname', $used_tags ) )
			{
				$missing_fields[] = 'firstname';
			}
			if( in_array( 'lastname', $required_fields ) && ! in_array( 'Form:lastname', $used_tags ) )
			{
				$missing_fields[] = 'lastname';
			}
			if( in_array( 'gender', $required_fields ) && ! in_array( 'Form:gender', $used_tags ) )
			{
				$missing_fields[] = 'gender';
			}

			if( ! empty( $missing_fields ) )
			{
				foreach( $missing_fields as $missing_field )
				{
					echo '<p class="evo_param_error">';
					echo sprintf( T_('The template %s is missing the required field %s.'), '<code>'.$this->disp_params['reg1_template'].'</code>', '<code>'.$missing_field.'</code>' );
					echo '</p>';
				}
			}

			if( is_logged_in() )
			{	// Don't allow to register if a user is already logged in:
				echo '<p>'.T_('You are already logged in').'</p>';
			}
			elseif( $invitation_code_status == 'deny' )
			{	// If registration is disabled
				echo '<p class="error red">';
				if( $Settings->get( 'newusers_canregister' ) == 'no' || ! $Settings->get( 'system_lock' ) )
				{	// If registration is disabled:
					echo T_('User registration is currently not allowed.');
				}
				else
				{	// If system is locked:
					echo T_('The site is currently locked for maintenance.');
				}
				echo '</p>';

				if( $params['register_disp_home_button'] )
				{	// Display button to go home:
					global $baseurl;
					echo '<p class="center"><a href="'.$baseurl.'" class="btn btn-default">'.T_('Home').'</a></p>';
				}
			}
			else
			{	// Display a registration form:
				if( $params['register_disp_messages'] )
				{	// Display the form messages before form inside wrapper:
					messages( array(
							'block_start' => '<div class="action_messages">',
							'block_end'   => '</div>',
						) );
				}

				// Save trigger page:
				$session_registration_trigger_url = $Session->get( 'registration_trigger_url' );
				if( empty( $session_registration_trigger_url ) && isset( $_SERVER['HTTP_REFERER'] ) )
				{	// Trigger page still is not defined
					$Session->set( 'registration_trigger_url', $_SERVER['HTTP_REFERER'] );
				}

				$source      = param( 'source', 'string', 'register form' );
				$redirect_to = param( 'redirect_to', 'url', '' );
				$return_to   = param( 'return_to', 'url', '' );
				$widget      = param( 'widget', 'integer' );

				if( ! is_null( $params['register_form_params'] ) )
				{	// Use another template param from skin:
					$Form->switch_template_parts( $params['register_form_params'] );
				}

				$Form->add_crumb( 'regform' );
				$Form->hidden( 'inskin', true );
				if( isset( $Blog ) )
				{	// For in-skin registration form:
					$Form->hidden( 'blog', $Blog->ID );
				}

				$Form->begin_form( $params['form_class_register'] );

				$Plugins->trigger_event( 'DisplayRegisterFormBefore', array( 'Form' => & $Form, 'inskin' => true ) );

				$Form->hidden( 'action', 'register' );
				$Form->hidden( 'source', $source );
				$Form->hidden( 'redirect_to', $redirect_to );

				if( ! empty( $widget ) )
				{	// Widget ID may be used on this form when user cannot be quickly registered because of suspected data:
					$Form->hidden( 'widget', $widget );
				}

				if( $invitation_code_status == 'input' )
				{ // Display an input field to enter invitation code manually or to change incorrect code
					$invitation_field_params = array( 'maxlength' => 32, 'class' => 'input_text', 'style' => 'width:138px' );
					if( $Settings->get( 'newusers_canregister' ) == 'invite' )
					{ // Invitation code must be required when users can register ONLY with this code
						$invitation_field_params['required'] = 'required';
					}
					$Form->text_input( 'invitation', get_param( 'invitation' ), 22, T_('Your invitation code'), '', $invitation_field_params );
				}
				elseif( $invitation_code_status == 'info' )
				{ // Display info field (when invitation code is correct)
					$Form->info( T_('Your invitation code'), get_param( 'invitation' ) );
					$Form->hidden( 'invitation', get_param( 'invitation' ) );
				}

				// ===================================================================================================================

				echo $register_form;

				// ===================================================================================================================

				// Plugin fields:
				$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array(
						'Form'             => & $Form,
						'inskin'           => true,
						'use_placeholders' => $params['register_use_placeholders']
					) );

				// Display plugin captcha for register form before submit button:
				$Plugins->display_captcha( array(
						'Form'              => & $Form,
						'form_type'         => 'register',
						'form_position'     => 'before_submit_button',
						'form_use_fieldset' => false,
						'use_placeholders'  => $params['register_use_placeholders'],
					) );

				$Form->end_form();

				// Display javascript password strength indicator bar:
				display_password_indicator( array( 'field-width' => $params['register_field_width'] ) );

				// Display javascript login validator:
				display_login_validator();

				echo $this->disp_params['block_body_end'];

				echo $this->disp_params['block_end'];
			}

			return true;
		}

		$this->display_debug_message();
		return false;
	}
}

?>
