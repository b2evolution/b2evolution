<?php
/**
 * This file implements the Custom Tags plugin for b2evolution
 *
 * Custom Tags
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


class custom_tags_plugin extends Plugin
{
	var $code = 'b2evCTag';
	var $name = 'Custom Tags';
	var $author = 'The b2evo Group';
	var $priority = 40;
	var $group = 'rendering';
	var $short_desc = 'Custom tags';
	var $long_desc;
	var $version = '0.1';
	var $number_of_installs = 1;

	// Internal
	var $configurable_post_list = true;
	var $configurable_comment_list = false;
	var $configurable_message_list = false;
	var $configurable_email_list = false;

	var $post_search_list;
	var $post_replace_list;
	var $comment_search_list;
	var $comment_replace_list;
	var $msg_search_list;
	var $msg_replace_list;
	var $email_search_list;
	var $email_replace_list;

	var $default_search_list = '#\[warning](.+?)\[/warning]#is\n
															#\[info](.+?)\[/info]#is';
	var $default_replace_list = '<div class="alert alert-warning">$1</div>\n
	                             <div class="alert alert-info">$1</div>';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Custom Tags');
		$this->long_desc = T_('Enables users to define custom tags that would be searched and replaced in the source text.');
	}


	function prepareSearchList( $search_list_string )
	{
		$search_list_array = explode( "\n", str_replace( "\r", "", $search_list_string ) );

		return $search_list_array;
	}

	function prepareReplaceList( $replace_list_string )
	{
		$replace_list_array = explode( "\n", str_replace( "\r", "", $replace_list_string ) );

		return $replace_list_array;
	}

	/**
	 * This is the meat of the plugin. You most probably want to customize this function
	 * to your specific needs.
	 */
	function replaceCallback( $content, $search, $replace )
	{
		return preg_replace( $search, $replace, $content );
	}


	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_comment_rendering' => 'never' ) );
		$plugin_params = array();

		if( $this->configurable_post_list )
		{
			$plugin_params['coll_post_search_list'] = array(
					'label' => $this->T_('Search list for posts'),
					'type' => 'html_textarea',
					'note' => $this->T_('This is the search array for posts (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING.'),
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => $this->default_search_list
				);

			$plugin_params['coll_post_replace_list'] = array(
					'label' => $this->T_('Replace list for posts'),
					'type' => 'html_textarea',
					'note' => $this->T_('This is the replace array for posts (one per line) it must match the exact order of the search array'),
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => $this->default_replace_list
				);
		}

		if( $this->configurable_comment_list )
		{
			$plugin_params['coll_comment_search_list'] = array(
					'label' => $this->T_('Search list for comments'),
					'type' => 'html_textarea',
					'note' => $this->T_('This is the search array for comments (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING.'),
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => $this->default_search_list
				);

			$plugin_params['coll_comment_replace_list'] = array(
					'label' => $this->T_('Replace list for comments'),
					'type' => 'html_textarea',
					'note' => $this->T_('This is the replace array for comments (one per line) it must match the exact order of the search array'),
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => $this->default_replace_list
				);
		}

		return array_merge( parent::get_coll_setting_definitions( $default_params ), $plugin_params );
	}

	function get_msg_setting_definitions( & $params )
	{
		$plugin_params = array();

		if( $this->configurable_messsage_list )
		{
			$plugin_params['msg_search_list'] = array(
				'label' => $this->T_('Search list for messages'),
				'type' => 'html_textarea',
				'note' => $this->T_('This is the search array for messages (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING.'),
				'rows' => 10,
				'cols' => 60,
				'defaultvalue' => $this->default_search_list
			);

			$plugin_params['msg_replace_list'] = array(
				'label' => $this->T_('Replace list for messages'),
				'type' => 'html_textarea',
				'note' => $this->T_('This is the replace array for messages (one per line) it must match the exact order of the search array'),
				'rows' => 10,
				'cols' => 60,
				'defaultvalue' => $this->default_replace_list
			);
		}

		return array_merge( parent::get_msg_setting_definitions( $params ), $plugin_params );
	}

	function get_email_setting_definitions( & $params )
	{
		$plugin_params = array();

		if( $this->configurable_email_list )
		{
			$plugin_params['email_search_list'] = array(
				'label' => $this->T_('Search list for email messages'),
				'type' => 'html_textarea',
				'note' => $this->T_('This is the search array for emails (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING.'),
				'rows' => 10,
				'cols' => 60,
				'defaultvalue' => $this->default_search_list
			);

			$plugin_params['email_replace_list'] = array(
				'label' => $this->T_('Replace list for email messages'),
				'type' => 'html_textarea',
				'note' => $this->T_('This is the replace array for emails (one per line) it must match the exact order of the search array'),
				'rows' => 10,
				'cols' => 60,
				'defaultvalue' => $this->default_replace_list
			);
		}

		return array_merge( parent::get_email_setting_definitions( $params ), $plugin_params );
	}

	/**
	 * Perform rendering of item
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
			$content = & $params['data'];
			$Item = $params['Item'];
			$item_Blog = & $Item->get_Blog();

			if( ! isset( $this->post_search_list ) )
			{
				$this->post_search_list = $this->prepareSearchList( $this->get_coll_setting( 'coll_post_search_list', $item_Blog ) );
			}

			if( ! isset( $this->post_replace_list ) )
			{
				$this->post_replace_list = $this->prepareReplaceList( $this->get_coll_setting( 'coll_post_replace_list', $item_Blog ) );
			}

			$callback = array( $this, 'replaceCallback' );
			// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
			$content = replace_content_outcode( $this->post_search_list, $this->post_replace_list, $content, $callback );

			return true;
	}

	/**
	 * Perform rendering of message
	 *
	 * @see Plugin::RenderMessageAsHtml()
	 */
	function RenderMessageAsHtml( & $params )
	{
			$content = & $params['data'];

			if( ! isset( $this->msg_search_list ) )
			{
				$this->msg_search_list = $this->prepareSearchList( $this->get_msg_setting( 'msg_search_list' ) );
			}

			if( ! isset( $this->msg_replace_list ) )
			{
				$this->msg_replace_list = $this->prepareReplaceList( $this->get_msg_setting( 'msg_replace_list' ) );
			}

			$callback = array( $this, 'replaceCallback' );
			// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
			$content = replace_content_outcode( $this->msg_search_list, $this->msg_replace_list, $content, $callback );

			return true;
	}

	/**
	 * Perform rendering of email
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
			$content = & $params['data'];

			if( ! isset( $this->email_search_list ) )
			{
				$this->email_search_list = $this->prepareSearchList( $this->get_email_setting( 'email_search_list' ) );
			}

			if( ! isset( $this->email_replace_list ) )
			{
				$this->email_replace_list = $this->prepareReplaceList( $this->get_email_setting( 'email_replace_list' ) );
			}

			$callback = array( $this, 'replaceCallback' );
			// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
			$content = replace_content_outcode( $this->email_search_list, $this->email_replace_list, $content, $callback );

			return true;
	}
}
?>