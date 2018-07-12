<?php
/**
 * This file implements the Proof of Work Captcha plugin.
 *
 * The core functionality was provided by Francois PLANQUE.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2012-2017 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * }}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Proof of Work Captcha Plugin.
 *
 * It displays an captcha question through {@link CaptchaValidated()} and validates
 * it in {@link CaptchaValidated()}.
 */
class proof_of_work_captcha_plugin extends Plugin
{
	var $version = '6.9.4';
	var $group = 'antispam';
	var $code = 'proof_of_work_captcha';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = $this->T_('Proof of Work Captcha');
		$this->short_desc = $this->T_('Proof of Work Captcha');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		global $DB;

		$GroupCache = & get_GroupCache();
		$GroupCache->load_all();
		$groups = array();
		foreach( $GroupCache->cache as $Group )
		{
			if( $Group->get( 'usage' ) == 'primary' )
			{
				$is_default = preg_match( '#(spammer|suspect)#i', $Group->get( 'name' ) );
				$groups[] = array( $Group->ID, $Group->get( 'name' ), $is_default );
			}
		}

		return array(
				'apy_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('API settings')
				),
					'api_site_key' => array(
						'label' => $this->T_('Coinhive site key'),
						'note'  => sprintf( $this->T_('Use "%s" which is generated for your site on <a %s>%s</a>'), $this->T_('Site Key (public)'), 'href="https://coinhive.com/settings/sites" target="_blank"', 'coinhive.com' ),
						'type'  => 'text',
						'size'  => 40,
					),
					'api_secret_key' => array(
						'label' => $this->T_('Coinhive secret key'),
						'note'  => sprintf( $this->T_('Use "%s" which is generated for your site on <a %s>%s</a>'), $this->T_('Secret Key (private)'), 'href="https://coinhive.com/settings/sites" target="_blank"', 'coinhive.com' ),
						'type'  => 'text',
						'size'  => 40,
					),
				'api_layout_end' => array(
					'layout' => 'end_fieldset',
				),
				'use_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Where to use')
				),
					'use_for_registration' => array(
						'label' => $this->T_('Use in user registration forms'),
						'defaultvalue' => 1,
						'note' => $this->T_('Should this plugin be used on registration forms?'),
						'type' => 'checkbox',
					),
					'use_for_anonymous_item' => array(
						'label' => $this->T_('Use in anonymous item posting forms'),
						'defaultvalue' => 1,
						'note' => $this->T_('Should this plugin be used for anonymous users on item forms?'),
						'type' => 'checkbox',
					),
					'use_for_anonymous_comment' => array(
						'label' => $this->T_('Use in anonymous commenting forms'),
						'defaultvalue' => 1,
						'note' => $this->T_('Should this plugin be used for anonymous users on comment forms?'),
						'type' => 'checkbox',
					),
					'use_for_anonymous_message' => array(
						'label' => $this->T_('Use in anonymous messaging forms'),
						'defaultvalue' => 1,
						'note' => $this->T_('Should this plugin be used for anonymous users on messaging forms?'),
						'type' => 'checkbox',
					),
					'use_for_suspect_users' => array(
						'label' => $this->T_('Also use in the above forms for suspect users'),
						'defaultvalue' => 1,
						'note' => $this->T_('Should this plugin be used for suspect users in the above forms?'),
						'type' => 'checkbox',
					),
				'use_layout_end' => array(
					'layout' => 'end_fieldset',
				),
				'difficulty_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Difficulty settings')
				),
					'hash_num' => array(
						'label'        => $this->T_('# of hashes by default'),
						'type'         => 'integer',
						'defaultvalue' => 1024,
						'valid_range'  => array(
							'min' => 256,
						),
					),
					'hash_num_suspect' => array(
						'label'        => $this->T_('# of hashes for anonymous users from suspect countries'),
						'note'         => $this->T_('Plugin "GeoIP" must be enabled for using of this setting.'),
						'type'         => 'integer',
						'defaultvalue' => 10240,
						'valid_range'  => array(
							'min' => 256,
						),
					),
					'hash_num_suspect_users' => array(
						'label'        => $this->T_('# of hashes for suspect users'),
						'type'         => 'integer',
						'defaultvalue' => 10240,
						'valid_range'  => array(
							'min' => 256,
						),
					),
				'difficulty_layout_end' => array(
					'layout' => 'end_fieldset',
				),
				'suspect_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Suspect users')
				),
					'suspect_groups' => array(
						'label' => T_('Suspect groups'),
						'type' => 'checklist',
						'options' => $groups,
					),
				'suspect_layout_end' => array(
					'layout' => 'end_fieldset',
				),
			);
	}


	/**
	 * Check the available questions in DB table.
	 */
	function BeforeEnable()
	{
		$api_site_key = trim( $this->Settings->get( 'api_site_key' ) );
		$api_secret_key = trim( $this->Settings->get( 'api_secret_key' ) );

		if( empty( $api_site_key ) || empty( $api_secret_key ) )
		{	// API keys must be filled:
			return $this->T_('To enable this plugin you should enter API keys!');
		}

		return true;
	}


	/**
	 * Validate the given answer against our stored one.
	 *
	 * This event is provided for other plugins and gets used internally
	 * for other events we're hooking into.
	 *
	 * @param array Associative array of parameters.
	 * @return boolean|NULL
	 */
	function CaptchaValidated( & $params )
	{
		global $Messages;

		if( ! empty( $params['is_preview'] ) )
		{	// Don't validate on preview action:
			return false;
		}

		if( empty( $params['form_type'] ) )
		{	// Form type must be defined:
			return false;
		}

		if( ! $this->does_apply( $params['form_type'] ) )
		{	// We should not apply captcha to the requested form:
			return false;
		}

		$post_params = array(
			'method'       => 'POST',
			'content_type' => 'application/x-www-form-urlencoded',
			'fields'       => array(
				'secret' => $this->Settings->get( 'api_secret_key' ),
				'token'  => param( 'coinhive-captcha-token', 'string' ),
				'hashes' => $this->get_hash_num(),
			),
		);

		$response = json_decode( fetch_remote_page( 'https://api.coinhive.com/token/verify', $info, NULL, NULL, $post_params ) );

		if( $response && isset( $response->success ) && $response->success )
		{	// Successful verifying:
			return true;
		}

		// Display error message if captcha verifying has been failed:
		$Messages->add_to_group( $this->T_('Antispam has not been verified successfully!'), 'error', T_('Validation errors:') );

		return false;
	}


	/**
	 * When a comment form gets displayed, we inject our captcha and an input field to
	 * enter the answer.
	 *
	 * The question ID is saved into the user's Session and in the DB table "ip_question".
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the form where payload should get added (by reference, OPTIONALLY!)
	 *   - 'form_use_fieldset':
	 *   - 'key': A key that is associated to the caller of the event (string, OPTIONALLY!)
	 *   - 'form_type': Form type ( comment|register|message )
	 * @return boolean|NULL true, if displayed; false, if error; NULL if it does not apply
	 */
	function CaptchaPayload( & $params )
	{
		if( ! isset( $params['form_type'] ) || ! $this->does_apply( $params['form_type'] ) )
		{	// We should not apply captcha to the requested form:
			return;
		}

		if( ! isset( $params['Form'] ) )
		{	// there's no Form where we add to, but we create our own form:
			$Form = new Form( regenerate_url() );
			$Form->begin_form();
		}
		else
		{
			$Form = & $params['Form'];
			if( ! isset( $params['form_use_fieldset'] ) || $params['form_use_fieldset'] )
			{
				$Form->begin_fieldset();
			}
		}

		$Form->info( $this->T_('Antispam'), '<script src="https://authedmine.com/lib/captcha.min.js" async></script>
			<div class="coinhive-captcha" data-hashes="'.format_to_output( $this->get_hash_num(), 'htmlattr' ).'" data-key="'.format_to_output( $this->Settings->get( 'api_site_key' ), 'htmlattr' ).'" data-disable-elements="form[data-coinhive-captcha] input[type=submit]:not([name$=\'[preview]\'])">
				<em>'.$this->T_('Loading Captcha...<br>If it doesn\'t load, please disable Adblock!').'</em>
			</div>' );
		// Append a flag to the forms which contains the coinhive captcha in order to don't disable the submit buttons from other forms on the same page:
		echo '<script type="text/javascript">jQuery( \'.coinhive-captcha[data-hashes]\' ).closest( \'form\' ).attr( \'data-coinhive-captcha\', 1 )</script>';

		if( ! isset( $params['Form'] ) )
		{	// there's no Form where we add to, but our own form:
			$Form->end_form( array( array( 'submit', 'submit', $this->T_('Validate me'), 'ActionButton' ) ) );
		}
		else
		{
			if( ! isset($params['form_use_fieldset']) || $params['form_use_fieldset'] )
			{
				$Form->end_fieldset();
			}
		}

		return true;
	}


	/**
	 * We display our captcha with item forms.
	 */
	function DisplayItemFormFieldset( & $params )
	{
		$params['form_type'] = 'item';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the answer against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the item from
	 * being posted.
	 *
	 * @param array Associative array of parameters.
	 */
	function AdminBeforeItemEditCreate( & $params )
	{
		$params['form_type'] = 'item';
		$this->CaptchaValidated( $params );
	}


	/**
	 * We display our captcha with comment forms.
	 */
	function DisplayCommentFormFieldsetAboveComment( & $params )
	{
		$params['form_type'] = 'comment';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the answer against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the comment from
	 * being posted.
	 *
	 * @param array Associative array of parameters.
	 */
	function BeforeCommentFormInsert( & $params )
	{
		$params['form_type'] = 'comment';
		$this->CaptchaValidated( $params );
	}


	/**
	 * We display our captcha with the register form.
	 */
	function DisplayRegisterFormFieldset( & $params )
	{
		$params['form_type'] = 'register';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the given private key against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the
	 * user from being registered.
	 */
	function RegisterFormSent( & $params )
	{
		$params['form_type'] = 'register';
		$this->CaptchaValidated( $params );
	}


	/**
	 * We display our captcha with the message form.
	 */
	function DisplayMessageFormFieldsetAboveMessage( & $params )
	{
		$params['form_type'] = 'message';
		$this->CaptchaPayload( $params );
	}


	/**
	 * Validate the given private key against our stored one.
	 *
	 * In case of error we add a message of category 'error' which prevents the
	 * user from being registered.
	 */
	function MessageFormSent( & $params )
	{
		$params['form_type'] = 'message';
		$this->CaptchaValidated( $params );
	}


	/* PRIVATE methods */

	/**
	 * Checks if we should captcha the current request, according to the settings made.
	 *
	 * @param string Form type ( comment|register|message )
	 * @return boolean
	 */
	function does_apply( $form_type )
	{
		$check_suspect_user = false;

		switch( $form_type )
		{
			case 'item':
				if( !is_logged_in() )
				{	// Use setting for anonymous user:
					return $this->Settings->get( 'use_for_anonymous_item' );
				}
				// Check if it should be applied also for suspected users:
				$check_suspect_user = ( $this->Settings->get( 'use_for_suspect_users' ) && $this->Settings->get( 'use_for_anonymous_item' ) );
				break;

			case 'comment':
				if( !is_logged_in() )
				{	// Use setting for anonymous user:
					return $this->Settings->get( 'use_for_anonymous_comment' );
				}
				// Check if it should be applied also for suspected users:
				$check_suspect_user = ( $this->Settings->get( 'use_for_suspect_users' ) && $this->Settings->get( 'use_for_anonymous_comment' ) );
				break;

			case 'register':
				return $this->Settings->get( 'use_for_registration' );

			case 'message':
				if( !is_logged_in() )
				{	// Use setting for anonymous user:
					return $this->Settings->get( 'use_for_anonymous_message' );
				}
				// Check if it should be applied also for suspected users:
				$check_suspect_user = ( $this->Settings->get( 'use_for_suspect_users' ) && $this->Settings->get( 'use_for_anonymous_message' ) );
				break;
		}

		if( $check_suspect_user && is_logged_in() )
		{	// Try to check if current logged in user is suspected by this plugin:
			global $current_User;
			$suspect_groups = $this->Settings->get( 'suspect_groups' );
			return ( is_array( $suspect_groups ) && isset( $suspect_groups[ $current_User->get( 'grp_ID' ) ] ) );
		}

		return false;
	}


	/**
	 * Get number of hashes
	 *
	 * @return integer
	 */
	function get_hash_num()
	{
		$Plugins_admin = & get_Plugins_admin();
		if( ( $geoip_Plugin = & $Plugins_admin->get_by_code( 'evo_GeoIP' ) ) &&
		    ( $Country = $geoip_Plugin->get_country_by_IP( get_ip_list( true ) ) ) &&
		    ( $Country->get( 'status' ) == 'suspect' ) )
		{	// Use special setting when country can be detected by IP address and it is suspected:
			$plugin_hash_num = $this->Settings->get( 'hash_num_suspect' );
		}
		else
		{	// Use normal setting for number of hashes:
			$plugin_hash_num = $this->Settings->get( 'hash_num' );
		}

		return intval( $plugin_hash_num );
	}
}
?>