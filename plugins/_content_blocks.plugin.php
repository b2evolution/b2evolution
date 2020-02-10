<?php
/**
 * This file implements the Include Content Blocks plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Include Content Blocks plugin.
 *
 * @package plugins
 */
class content_blocks_plugin extends Plugin
{
	var $name;
	var $code = 'content_blocks';
	var $priority = 102;
	var $version = '7.1.2';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'include-content-blocks-plugin';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_('Include Content Blocks');
		$this->short_desc = T_('Render content blocks.');
		$this->long_desc = sprintf( T_('This renderer display a content block found in the content by short tag %s'), '<code>[include:item-slug]</code>' );
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array(
				'default_post_rendering'    => 'stealth',
				'default_comment_rendering' => 'never',
			);

		$params = array_merge( $params, $default_params );

		return parent::get_coll_setting_definitions( $params );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		if( ! ( $Item = & $this->get_Item_from_params( $params ) ) )
		{	// Skip rendering without provided Item:
			return false;
		}

		// Remove block level short tag [include:...] inside <p> blocks and move them before the paragraph:
		$content = move_short_tags( $content, '#\[include:[^\]]+\]#i' );

		// Replace `[include:item-slug]` short tag with item content:
		$params['check_code_block'] = true;
		$content = $Item->render_content_blocks( $content, $params );

		return true;
	}


	/**
	 * Perform rendering of Message content
	 *
	 * @see Plugin::RenderMessageAsHtml()
	 */
	function RenderMessageAsHtml( & $params )
	{
		return true;
	}


	/**
	 * Perform rendering of Email content
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		return true;
	}


	/**
	 * Event handler: called to filter the comment's content
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentContent( & $params )
	{
		$Comment = & $params['Comment'];
		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{	// Always allow rendering for comment:
			$render_params = array_merge( array( 'data' => & $Comment->content ), $params );
			$this->RenderItemAsHtml( $render_params );
		}
		return false;
	}
}

?>