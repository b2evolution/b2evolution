<?php
/**
 * This file implements the EU cookie consent plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * EU cookie consent plugin
 */
class cookie_consent_plugin extends Plugin
{
	var $name = 'EU cookie consent';
	var $code = 'cookie_consent';
	var $priority = 1;
	var $short_desc;
	var $long_desc;
	var $version = '5.0.0';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('EU cookie consent.');
		$this->long_desc = T_('EU cookie consent.');
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
		return array(
				'title' => array(
					'label' => T_('Title'),
					'note' => T_('Leave empty for default.'),
					'type' => 'text',
				),
				'intro' => array(
					'label' => T_('Intro Text'),
					'note' => T_('Leave empty for default.'),
					'type' => 'text',
				),
				'post_ID' => array(
					'label' => T_('Post ID for more info'),
					'type' => 'integer',
				),
				'info' => array(
					'label' => T_('More info Text'),
					'note' => T_('Leave empty for default.'),
					'type' => 'html_textarea',
				),
				'consent' => array(
					'label' => T_('Consent Text'),
					'note' => T_('Leave empty for default.'),
					'type' => 'html_textarea',
				),
				'accept_button' => array(
					'label' => T_('Accept button Text'),
					'note' => T_('Leave empty for default.'),
					'type' => 'text',
				),
			);
	}


	/**
	 * Event handler: Gets called before skin wrapper.
	 *
	 * Use this to add any HTML code before skin wrapper and after evo toolbar.
	 */
	function BeforeSkinWrapper( & $params )
	{
		// Title:
		$title = $this->Settings->get( 'title' );
		if( empty( $title ) )
		{ // Use default:
			$title = TS_('Cookies');
		}

		// Intro Text:
		$intro = $this->Settings->get( 'intro' );
		if( empty( $intro ) )
		{ // Use default:
			$intro = TS_('This site uses cookies to offer you a better browsing experience.');
		}

		// Post ID for more info:
		$post_ID = intval( $this->Settings->get( 'post_ID' ) );
		if( $post_ID > 0 )
		{
			$ItemCache = & get_ItemCache();
			$Item = & $ItemCache->get_by_ID( $post_ID, false, false );
		}
		if( ! empty( $Item ) )
		{ // More info Text:
			$info = $this->Settings->get( 'info' );
			if( empty( $info ) )
			{ // Use default:
				$info = TS_('Find out more on how we use cookies and how you can change your settings.');
			}
		}

		// Consent Text:
		$consent = $this->Settings->get( 'consent' );
		if( empty( $consent ) )
		{ // Use default:
			$consent = TS_('By continuing to use our website without changing your settings, you are agreeing to our use of cookies.');
		}

		// Accept button Text:
		$accept_button = $this->Settings->get( 'accept_button' );
		if( empty( $accept_button ) )
		{ // Use default:
			$accept_button = TS_('Accept');
		}

		// Initialize html block:
		require_css( $this->get_plugin_url().'style.css', true, NULL, NULL, '#', true );
		require_js( 'jquery/jquery.cookie.min.js', 'rsc_url', false, true );

		$html_block = '<div id="eu_cookie_consent"'.( is_logged_in() ? ' class="eu_cookie_consent__loggedin"' : '' ).'>'
				.'<div>'
				.'<h3>'.format_to_js( $title ).'</h3>'
				.'<p>'.format_to_js( $intro ).'</p>';
		if( ! empty( $Item ) )
		{ // Display a link to post with more info:
			$html_block .= '<p><a href="'.$Item->get_permanent_url().'">'.format_to_js( nl2br( $info ) ).'</a></p>';
		}
		$html_block .= '<p>'.format_to_js( nl2br( $consent ) ).'</p>'
				.'</div>'
				.'<div class="eu_cookie_consent__button"><button class="btn btn-info">'.format_to_js( $accept_button ).'</button></div>'
			.'</div>';

		echo '<script type="text/javascript">
var eu_cookie_consent = jQuery.cookie( "eu_cookie_consent" )
if( eu_cookie_consent != "accepted" )
{ // Print a block only if this was not accepted yet:
	document.write( \''.$html_block.'\' );
}

jQuery( document ).ready( function()
{
	jQuery( "#eu_cookie_consent button" ).click( function()
	{
		jQuery.cookie( "eu_cookie_consent", "accepted", { expires: 365, path: "/" } )
		jQuery( "#eu_cookie_consent" ).remove();
	} );
} );
</script>';
	}
}