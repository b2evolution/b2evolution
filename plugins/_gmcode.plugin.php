<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '../plugins/_custom_tags.plugin.php', 'custom_tags_plugin' );

/**
 * Replaces GreyMatter markup in HTML (not XML).
 *
 * @todo dh> Do not replace in tags, it matches e.g. the following for italic:
 *           """<img src="//url" /> [...] http://"""!
 *
 * @package plugins
 */
class gmcode_plugin extends custom_tags_plugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 45;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '6.9.3';
	var $number_of_installs = 1;

	var $toolbar_label = 'GM code:';

	var $configurable_post_list = true;
	var $configurable_comment_list = true;
	var $configurable_message_list = true;
	var $configurable_email_list = true;

	var $default_search_list = '** #\*\*(.+?)\*\*#x
\\\\ #\\\\\\\\(.+?)\\\\\\\\#x
// #(?<!:)\x2f\x2f(.+?)\x2f\x2f#x
__ #__(.+?)__#x
## /\#\#(.+?)\#\#/x
%% /%%(\s*?\n)?(.+?)(\n\s*?)?%%/sx';

	var $default_replace_list = '<strong>$1</strong>
<em>$1</em>
<em>$1</em>
<span style="text-decoration: underline">$1</span>
<tt>$1</tt>
<div class="codeblock"><pre><code>$2</code></pre></div>';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('GreyMatter style formatting');
		$this->long_desc = T_('**bold** \\\\italics\\\\ //italics// __underline__ ##tt## %%codeblock%%');
	}


	function replace_callback( $content, $search, $replace )
	{ // Replace text outside of html tags
		return callback_on_non_matching_blocks( $content, '~<[^>]*>~s', array( $this, 'second_replace_callback' ), array( $search, $replace ) );
	}


	function second_replace_callback( $content, $search, $replace)
	{
		return preg_replace( $search, $replace, $content );
	}

	/**
	 * The following function are here so the events will be registered
	 * @see Plugins_admin::get_registered_events()
	 */
	function RenderItemAsHtml( & $params )
	{
		parent::RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		$params['target_type'] = 'Item';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		$params['target_type'] = 'Comment';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		$params['target_type'] = 'Message';
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{
			$params['target_type'] = 'EmailCampaign';
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Prepare a search list
	 *
	 * @param string String value of a search list
	 * @return array The search list as array
	 */
	function prepare_search_list( $search_list_string )
	{
		$search_list_array = explode( "\n", str_replace( "\r", '', $search_list_string ) );

		foreach( $search_list_array as $l => $line )
		{	// Remove button name from regexp string
			$line = explode( ' ', $line, 2 );
			$regexp = $line[1];
			if( empty( $regexp ) )
			{	// Bad format of search string
				unset( $search_list_array[ $l ] );
			}
			else
			{	// Replace this line with regexp value (to delete a button name)
				$search_list_array[ $l ] = $regexp;
			}
		}

		return $search_list_array;
	}


	function get_tag_buttons( $search_list )
	{
		$tagButtons = array();

		foreach( $search_list as $line )
		{	// Init buttons from regexp lines
			$line = explode( ' ', $line, 2 );
			$button_name = addslashes( $line[0] );
			$button_exp = $line[1];
			if( !empty( $button_name ) && !empty( $button_exp ) )
			{
				$tagButtons[ $button_name ] = array(
						'name'  => $button_name,
						'start' => $button_name,
						'end'   => $button_name,
						'title' => $button_name
					);
			}
		}

		return $tagButtons;
	}
}

?>