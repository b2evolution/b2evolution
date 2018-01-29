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
		global $Settings;

		return array(
				'use_for_anonymous_comment' => array(
					'label' => $this->T_('Use for anonymous comment forms'),
					'defaultvalue' => 1,
					'note' => $this->T_('Should this plugin be used for anonymous users on comment forms?'),
					'type' => 'checkbox',
				),
				'use_for_registration' => array(
					'label' => $this->T_('Use for new registration forms'),
					'defaultvalue' => 1,
					'note' => $this->T_('Should this plugin be used on registration forms?'),
					'type' => 'checkbox',
				),
				'use_for_anonymous_message' => array(
					'label' => $this->T_('Use for anonymous messaging forms'),
					'defaultvalue' => 1,
					'note' => $this->T_('Should this plugin be used for anonymous users on messaging forms?'),
					'type' => 'checkbox',
				),
				'api_site_key' => array(
					'label' => $this->T_('API site key'),
					'note'  => sprintf( $this->T_('Use "%s" which is generated for your site on <a %s>%s</a>'), $this->T_('Site Key (public)'), 'href="https://coinhive.com/settings/sites" target="_blank"', 'coinhive.com' ),
					'type'  => 'text',
					'size'  => 40,
				),
				'api_secret_key' => array(
					'label' => $this->T_('API secret key'),
					'note'  => sprintf( $this->T_('Use "%s" which is generated for your site on <a %s>%s</a>'), $this->T_('Secret Key (private)'), 'href="https://coinhive.com/settings/sites" target="_blank"', 'coinhive.com' ),
					'type'  => 'text',
					'size'  => 40,
				),
				'hash_num' => array(
					'label'        => $this->T_('Number of hashes'),
					'type'         => 'integer',
					'defaultvalue' => 1024,
					'valid_range'  => array(
						'min' => 1,
					),
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

		$post_data = array(
			'secret' => $this->Settings->get( 'api_secret_key' ),
			'token'  => param( 'coinhive-captcha-token', 'string' ),
			'hashes' => 1024
		);

		$post_context = stream_context_create( array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query( $post_data )
			)
		) );

		$response = json_decode( file_get_contents( 'https://api.coinhive.com/token/verify', false, $post_context ) );

		if( $response && $response->success )
		{	// Successful verifying:
			return true;
		}

		// Display error message if captcha verifying has been failed:
		$Messages->add( $this->T_('Captcha has not been verified successfully!'), 'error' );

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
		global $DB, $Session;

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
			<div class="coinhive-captcha" data-hashes="'.format_to_output( $this->Settings->get( 'hash_num' ), 'htmlattr' ).'" data-key="'.format_to_output( $this->Settings->get( 'api_site_key' ), 'htmlattr' ).'" data-disable-elements="input[type=submit]">
				<em>'.$this->T_('Loading Captcha...<br>If it doesn\'t load, please disable Adblock!').'</em>
			</div>' );

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
	 * We display our captcha with comment forms.
	 */
	function DisplayCommentFormFieldset( & $params )
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
	function DisplayMessageFormFieldset( & $params )
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
		switch( $form_type )
		{
			case 'comment':
				if( !is_logged_in() )
				{
					return $this->Settings->get( 'use_for_anonymous_comment' );
				}
				break;

			case 'register':
				return $this->Settings->get( 'use_for_registration' );

			case 'message':
				if( !is_logged_in() )
				{
					return $this->Settings->get( 'use_for_anonymous_message' );
				}
				break;
		}

		return false;
	}
}
?>