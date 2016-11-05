<?php
/**
 * This file implements the Escape code plugin for b2evolution
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
class escapecode_plugin extends Plugin
{
	var $code = 'escape_code';
	var $name = 'Escape code';
	var $priority = 8;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '6.7.8';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Escapes html tags in code blocks');
		$this->long_desc = T_('Escapes tags in blocks marked with &lt;code&gt; [codeblock] [codespan] or ``` (Markdown)');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array(
				'default_post_rendering'    => 'stealth',
				'default_comment_rendering' => 'stealth'
			) );
		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Filters out the custom tag that would not validate, PLUS escapes the actual code.
	 *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		if( $params['object_type'] == 'Item' && ! empty( $params['object'] ) )
		{
			$Item = & $params['object'];
			if( $Item->get_type_setting( 'allow_html' ) )
			{	// Do escape html entities only when html is allowed for content:
				$content = & $params['content'];
				$content = $this->escape_code( $content );
			}
		}

		return true;
	}


	/**
	 * Event handler: Called before at the beginning, if a comment form gets sent (and received).
	 */
	function CommentFormSent( & $params )
	{
		$ItemCache = & get_ItemCache();
		$comment_Item = & $ItemCache->get_by_ID( $params['comment_item_ID'], false );
		if( !$comment_Item )
		{ // Incorrect item
			return false;
		}

		$item_Blog = & $comment_Item->get_Blog();
		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $item_Blog );
		if( $item_Blog->get_setting( 'allow_html_comment' ) && $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // Do escape html entities only when html is allowed for content and plugin is enabled
			$content = & $params['comment'];
			$content = $this->escape_code( $content );
		}
	}


	/**
	 * Event handler: Called before at the beginning, if a message of thread form gets sent (and received).
	 */
	function MessageThreadFormSent( & $params )
	{
		global $Settings;

		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( $Settings->get( 'allow_html_message' ) && $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // Do escape html entities only when html is allowed for content and plugin is enabled
			$content = & $params['content'];
			$content = $this->escape_code( $content );
		}
	}


	/**
	 * Event handler: Called before at the beginning, if an email form gets sent (and received).
	 */
	function EmailFormSent( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // Do escape html entities only when html is allowed for content and plugin is enabled
			$content = & $params['content'];
			$content = $this->escape_code( $content );
		}
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		/* Initialize this function only in order to detect this plugin as renderer */
		return true;
	}


	/**
	 * Escape html entities inside <code> tag
	 *
	 * @param string Content
	 * @param string Function name for callback
	 * @return string Escaped content
	 */
	function escape_code( $content, $callback_function = 'escape_code_callback' )
	{
		if( strpos( $content, '[codeblock' ) !== false || strpos( $content, '<codeblock' ) !== false )
		{ // Do escape the html entities in code blocks:
			$content = preg_replace_callback( '#([<\[]codeblock[^>\]]*[>\]])([\s\S]+?)([<\[]/codeblock[>\]])#is', array( $this, $callback_function ), $content );
		}

		if( strpos( $content, '[codespan' ) !== false || strpos( $content, '<codespan' ) !== false )
		{ // Do escape the html entities in code spans:
			$content = preg_replace_callback( '#([<\[]codespan[>\]])([\s\S]+?)([<\[]/codespan[>\]])#is', array( $this, $callback_function ), $content );
		}

		if( strpos( $content, '<code' ) !== false )
		{ // At least one tag <code> exists in the content, Do escape the html entities:
			$content = preg_replace_callback( '#(<code[^>]*>)([\s\S]+?)(</code>)#is', array( $this, $callback_function ), $content );
		}

		if( strpos( $content, '`' ) !== false )
		{ // String of codespan from markdown, Do escape the html entities:
			$content = preg_replace_callback( '#(`)([^`\n]+)(`)#i', array( $this, $callback_function ), $content );
		}

		if( strpos( $content, '```' ) !== false )
		{ // String of codeblock from markdown, Do escape the html entities:
			$content = preg_replace_callback( '#(```)([\s\S]+?)(```)#is', array( $this, $callback_function ), $content );
		}

		return $content;
	}


	/**
	 * Escape html entities inside <code> tag
	 *
	 * @param string Code content
	 * @return string Escaped code content
	 */
	function escape_code_callback( $code_content )
	{
		// Start tag
		$escaped_content = $code_content[1];

		// Escape two chars to escape html tags inside <code>
		$escaped_content .= str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $code_content[2] );

		// End tag
		$escaped_content .= $code_content[3];

		return $escaped_content;
	}


	/**
	 * Unescape html entities inside <code> tag
	 *
	 * @param string Code content
	 * @return string Unescaped code content
	 */
	function unescape_code_callback( $code_content )
	{
		// Start tag
		$escaped_content = $code_content[1];

		// Escape two chars to escape html tags inside <code>
		$escaped_content .= str_replace( array( '&lt;', '&gt;' ), array( '<', '>' ), $code_content[2] );

		// End tag
		$escaped_content .= $code_content[3];

		return $escaped_content;
	}


	/**
	 * Formats post contents ready for editing
	 *
	 * @param mixed $params
	 */
	function UnfilterItemContents( & $params )
	{
		$content = & $params['content'];
		$content = $this->escape_code( $content, 'unescape_code_callback' );

		return true;
	}
}

?>