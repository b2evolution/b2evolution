<?php
/**
 * This file implements the Adjust headings renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class adjust_headings_plugin extends Plugin
{
	var $code = 'h_levels';
	var $name = 'Adjust headings';
	var $priority = 105;
	var $version = '6.7.8';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'adjust-headings-plugin';
	var $number_of_installs = 1;

	/*
	 * Internal vars:
	 */
	var $highest_level = NULL;
	var $setting_level = NULL;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Adjust heading levels so they are consistent for all posts.');
		$this->long_desc = T_('This plugin will adjust headings so they always start at the level you want: 1 for &lt;H1&gt;, 2 for &lt;H2&gt;, etc.');
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_custom_setting_definitions()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
			'level' => array(
					'label' => T_('Highest level heading'),
					'type' => 'integer',
					'size' => 1,
					'maxlength' => 1,
					'note' => T_('This plugin will adjust headings so they always start at the level you want: 1 for &lt;H1&gt;, 2 for &lt;H2&gt;, etc.'),
					'defaultvalue' => 3,
					'valid_range' => array(
						'min' => 1, // from <h1>
						'max' => 6, // to <h6>
					),
				),
		);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array(
				'default_comment_rendering' => 'never',
				'default_post_rendering' => 'opt-out'
			) );

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		if( ! empty( $params['Item'] ) )
		{ // Get Item from params:
			$Item = & $params['Item'];
		}
		elseif( ! empty( $params['Comment'] ) )
		{ // Get Item from Comment:
			$Comment = & $params['Comment'];
			$Item = & $Comment->get_Item();
		}

		if( empty( $Item ) )
		{ // Unknown call, Don't render this case:
			return;
		}

		// Get setting level of current blog:
		$item_Blog = & $Item->get_Blog();
		$this->setting_level = $this->get_coll_setting( 'level', $item_Blog );

		// Adjust headings:
		$content = $this->do_adjust_headings( $content );

		return true;
	}


	/**
	 * Perform rendering of Message content
	 *
	 * NOTE: Use default coll settings of comments as messages settings
	 *
	 * @see Plugin::RenderMessageAsHtml()
	 */
	function RenderMessageAsHtml( & $params )
	{
		$content = & $params['data'];

		// Get setting level for messages:
		$this->setting_level = $this->get_msg_setting( 'level' );

		// Adjust headings:
		$content = $this->do_adjust_headings( $content );

		return true;
	}


	/**
	 * Perform rendering of Email content
	 *
	 * NOTE: Use default coll settings of comments as messages settings
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		$content = & $params['data'];

		// Get setting level for emails:
		$this->setting_level = $this->get_email_setting( 'level' );

		// Adjust headings:
		$content = $this->do_adjust_headings( $content );

		return true;
	}


	/**
	 * Do the same as for HTML.
	 *
	 * @see RenderItemAsHtml()
	 */
	function RenderItemAsXml( & $params )
	{
		$this->RenderItemAsHtml( $params );
	}


	/**
	 *
	 * Render comments if required
	 *
	 * @see Plugin::FilterCommentContent()
	 */
	function FilterCommentContent( & $params )
	{
		$Comment = & $params['Comment'];

		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{ // apply_comment_rendering is set to render
			$content = & $params['data'];

			// Get setting level of current blog:
			$comment_Item = & $Comment->get_Item();
			$item_Blog = & $comment_Item->get_Blog();
			$this->setting_level = $this->get_coll_setting( 'level', $item_Blog );

			// Adjust headings:
			$content = $this->do_adjust_headings( $content );
		}
	}


	/**
	 * Adjust headings
	 *
	 * @param string Original content
	 * @return string Content with adjusted headings
	 */
	function do_adjust_headings( $content )
	{
		$this->highest_level = NULL;

		// Find the highest heading level in the current content:
		replace_content_outcode( '#</h([1-6])>#i', array( $this, 'callback_find_highest_heading' ), $content, 'replace_content_callback' );

		if( is_null( $this->highest_level ) || $this->highest_level == $this->setting_level )
		{ // The html heading tags have not been detected in the content
			// OR the highest level from content is equal to level of the current setting, so nothing to update
			return $content;
		}

		// Replace the headings with new:
		$content = replace_content_outcode( '#<(/?)h([1-6])([^>]*)?>#i', array( $this, 'callback_adjust_headings' ), $content, 'replace_content_callback' );

		return $content;
	}


	/**
	 * Callback function to find the highest heading level in the current content
	 *
	 * @param array Matches
	 */
	function callback_find_highest_heading( $matches )
	{
		if( empty( $matches ) )
		{ // Nothing to do
			return;
		}

		if( $this->highest_level == 1 )
		{ // The level is already the highest
			return;
		}

		$h_level = intval( $matches[1] );

		if( $h_level > 0 && ( is_null( $this->highest_level ) || $this->highest_level > $h_level ) )
		{ // Update the var to the highest level in the content:
			$this->highest_level = $h_level;
		}
	}


	/**
	 * Callback function to adjust headings
	 *
	 * @param array Matches
	 * @return string
	 */
	function callback_adjust_headings( $matches )
	{
		if( empty( $matches ) )
		{ // Nothing to do
			return;
		}

		$h_level = intval( $matches[2] );

		if( empty( $h_level ) )
		{ // Wrong heading level value, Return original html tag:
			return $matches[0];
		}
		else
		{ // Adjust current heading html tag:
			$h_level += $this->setting_level - $this->highest_level;

			if( $h_level > 6 )
			{ // Limit with max 6 level because of HTML:
				$h_level = 6;
			}

			// Return html heading tag with adjusted heading:
			return '<'.$matches[1].'h'.$h_level.$matches[3].'>';
		}
	}
}

?>