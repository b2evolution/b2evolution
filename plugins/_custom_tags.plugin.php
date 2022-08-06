<?php
/**
 * This file implements the Custom Tags plugin for b2evolution
 *
 * Custom Tags
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

class custom_tags_plugin extends Plugin
{
	var $code = 'b2evCTag';
	var $name = 'Custom Tags';
	var $author = 'The b2evo Group';
	var $priority = 17;
	var $group = 'rendering';
	var $short_desc = 'Custom tags';
	var $long_desc;
	var $version = '7.2.5';
	var $number_of_installs = 1;

	// Internal
	var $toolbar_label = 'Custom Tags:';
	var $configurable_post_list = true;
	var $configurable_comment_list = true;
	var $configurable_message_list = true;
	var $configurable_email_list = true;
	var $configurable_shared_list = true;

	var $post_search_list;
	var $post_replace_list;
	var $comment_search_list;
	var $comment_replace_list;
	var $msg_search_list;
	var $msg_replace_list;
	var $email_search_list;
	var $email_replace_list;
	var $shared_search_list;
	var $shared_replace_list;

	var $default_search_list = '[warning] #\[warning](.+?)\[/warning]#is
[info] #\[info](.+?)\[/info]#is
[clear] #\[clear]#is
[left] #\[left](.+?)\[/left]#is
[right] #\[right](.+?)\[/right]#is
[center] #\[center](.+?)\[/center]#is
[justify] #\[justify](.+?)\[/justify]#is
[note] #\[note](.+?)\[/note]#is';

	var $default_replace_list = '<div class="alert alert-warning" markdown="1">$1</div>
<div class="alert alert-info" markdown="1">$1</div>
<div class="clear" markdown="1"></div>
<div class="left" markdown="1">$1</div>
<div class="right" markdown="1">$1</div>
<div class="center" markdown="1">$1</div>
<div class="justify" markdown="1">$1</div>
<span class="note">$1</span>';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Custom Tags');
		$this->long_desc = T_('Enables users to define custom tags that would be searched and replaced in the source text.');
	}


	/**
	 * Prepares the search list
	 *
	 * @param string String value of a search list
	 * @return array The search list as array
	 */
	function prepare_search_list( $search_list_string )
	{
		if( ! $search_list_string )
		{ // No search list string, use default search list string
			$search_list_string = $this->default_search_list;
		}

		$search_list_array = explode( "\n", str_replace( "\r", "", $search_list_string ) );

		foreach( $search_list_array as $l => $line )
		{
			$line = explode( ' ', $line, 2 );
			if( empty( $line[1] ) )
			{ // Bad format of search string
				unset( $search_list_array[$l] );
			}
			else
			{ // Replace this line with regex value (to delete a button name)
				$search_list_array[ $l ] = $line[1];
			}
		}

		return $search_list_array;
	}


	/**
	 * Prepares the replace list
	 *
	 * @param string String value of a replacement list
	 * @return array The replacement list as array
	 */
	function prepare_replace_list( $replace_list_string )
	{
		if( ! $replace_list_string )
		{ // No replace list string, use default replace list string
			$replace_list_string = $this->default_replace_list;
		}

		$replace_list_array = explode( "\n", str_replace( "\r", "", $replace_list_string ) );
		return $replace_list_array;
	}


	/**
	 * This is supposedly the meat of the plugin. You most probably want to override this function
	 * to your specific needs.
	 *
	 * @param string Content
	 * @param array Search list
	 * @param array Replace list
	 */
	function replace_callback( $content, $search, $replace )
	{
		return preg_replace( $search, $replace, $content );
	}


	/**
	 * Define here the default collection/blog settings that are to be made available in the backoffice
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array(
				'default_comment_rendering' => 'never',
			);

		if( ! empty( $params['blog_type'] ) && get_class( $this ) == 'custom_tags_plugin' )
		{	// Set default settings depending on collection type:
			// (ONLY for current plugin excluding all child plugins like "BB code" or "GM code")
			switch( $params['blog_type'] )
			{
				case 'forum':
					$default_params['default_post_rendering'] = 'never';
					break;
			}
		}

		$default_params = array_merge( $params, $default_params );
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


	/**
	 * Define here the default message settings that are to be made available in the backoffice
	 *
	 * @param array Associative array of parameters
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		$plugin_params = array();

		if( $this->configurable_message_list )
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


	/**
	 * Define here the default email settings that are to be made available in the backoffice
	 *
	 * @param array Associative array of parameters
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
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
	 * Define here the default shared settings that are to be made available in the backoffice
	 *
	 * @param array Associative array of parameters
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_shared_setting_definitions( & $params )
	{
		$plugin_params = array();

		if( $this->configurable_shared_list )
		{
			$plugin_params['shared_search_list'] = array(
				'label' => $this->T_('Search list for shared container widgets'),
				'type' => 'html_textarea',
				'note' => $this->T_('This is the search array for shared container widgets (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING.'),
				'rows' => 10,
				'cols' => 60,
				'defaultvalue' => $this->default_search_list
			);
			$plugin_params['shared_replace_list'] = array(
				'label' => $this->T_('Replace list for shared container widgets'),
				'type' => 'html_textarea',
				'note' => $this->T_('This is the replace array for shared container widgets (one per line) it must match the exact order of the search array'),
				'rows' => 10,
				'cols' => 60,
				'defaultvalue' => $this->default_replace_list
			);
		}

		return array_merge( parent::get_shared_setting_definitions( $params ), $plugin_params );
	}


	/**
	 * Perform rendering of item
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$callback = array( $this, 'replace_callback' );

		if( $this->is_shared_widget( $params ) )
		{	// Use settings for shared container widgets:
			if( ! isset( $this->shared_search_list ) )
			{
				$search_list = $this->get_shared_setting( 'shared_search_list' );
				if( ! $search_list )
				{
					$search_list = $this->default_search_list;
				}
				$this->shared_search_list = $this->prepare_search_list( $search_list );
			}

			if( ! isset( $this->shared_replace_list ) )
			{
				$replace_list = $this->get_shared_setting( 'shared_replace_list' );
				if( ! $replace_list )
				{
					$replace_list = $this->default_replace_list;
				}
				$this->shared_replace_list = $this->prepare_replace_list( $replace_list );
			}

			// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
			$content = replace_outside_code_and_short_tags( $this->shared_search_list, $this->shared_replace_list, $content, $callback );
		}
		else
		{	// Use settings for collection:
			$setting_Blog = $this->get_Blog_from_params( $params );

			if( ! isset( $this->post_search_list ) )
			{
				$search_list = $this->get_coll_setting( 'coll_post_search_list', $setting_Blog );
				if( ! $search_list )
				{
					$search_list = $this->default_search_list;
				}
				$this->post_search_list = $this->prepare_search_list( $search_list );
			}

			if( ! isset( $this->post_replace_list ) )
			{
				$replace_list = $this->get_coll_setting( 'coll_post_replace_list', $setting_Blog );
				if( ! $replace_list )
				{
					$replace_list = $this->default_replace_list;
				}
				$this->post_replace_list = $this->prepare_replace_list( $replace_list );
			}

			// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
			$content = replace_outside_code_and_short_tags( $this->post_search_list, $this->post_replace_list, $content, $callback );
		}

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
			$search_list = $this->get_msg_setting( 'msg_search_list' );
			if( ! $search_list )
			{
				$search_list = $this->default_search_list;
			}
			$this->msg_search_list = $this->prepare_search_list( $search_list );
		}

		if( ! isset( $this->msg_replace_list ) )
		{
			$replace_list = $this->get_msg_setting( 'msg_replace_list' );
			if( ! $replace_list )
			{
				$replace_list = $this->default_replace_list;
			}
			$this->msg_replace_list = $this->prepare_replace_list( $replace_list );
		}

		$callback = array( $this, 'replace_callback' );

		// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
		$content = replace_outside_code_and_short_tags( $this->msg_search_list, $this->msg_replace_list, $content, $callback );

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
			$search_list = $this->get_email_setting( 'email_search_list' );
			if( ! $search_list )
			{
				$search_list = $this->default_search_list;
			}
			$this->email_search_list = $this->prepare_search_list( $search_list );
		}

		if( ! isset( $this->email_replace_list ) )
		{
			$replace_list = $this->get_email_setting( 'email_replace_list' );
			if( ! $replace_list )
			{
				$replace_list = $this->default_replace_list;
			}
			$this->email_replace_list = $this->prepare_replace_list( $replace_list );
		}

		$callback = array( $this, 'replace_callback' );

		// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks
		$content = replace_outside_code_and_short_tags( $this->email_search_list, $this->email_replace_list, $content, $callback );

		return true;
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
	 * Display a code toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCodeToolbar( & $params )
	{
		global $Hit;

		if( $Hit->is_lynx() )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}

		$params = array_merge( array(
				'target_type' => 'Item',
				'js_prefix'   => '', // Use different prefix if you use several toolbars on one page
			), $params );

		$js_code_prefix = $params['js_prefix'].$this->code.'_';

		switch( $params['target_type'] )
		{
			case 'Item':
				$search_list_setting_name = 'coll_post_search_list';
				$Item = $params['Item'];
				$item_Blog = & $Item->get_Blog();
				$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $item_Blog );
				$search_list = trim( $this->get_coll_setting( $search_list_setting_name, $item_Blog ) );
				break;

			case 'Comment':
				$search_list_setting_name = 'coll_comment_search_list';
				if( !empty( $params['Comment'] ) && !empty( $params['Comment']->item_ID ) )
				{	// Get Blog from Comment
					$Comment = & $params['Comment'];
					$comment_Item = & $Comment->get_Item();
					$item_Blog = & $comment_Item->get_Blog();
				}
				else if( !empty( $params['Item'] ) )
				{	// Get Blog from Item
					$comment_Item = & $params['Item'];
					$item_Blog = & $comment_Item->get_Blog();
				}
				$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $item_Blog );
				$search_list = trim( $this->get_coll_setting( $search_list_setting_name, $item_Blog ) );
				break;

			case 'Message':
				$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
				$search_list = trim( $this->get_msg_setting( 'search_list' ) );
				break;

			case 'EmailCampaign':
				$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
				$search_list = trim( $this->get_email_setting( 'search_list' ) );
				break;

			default:
				// Incorrect param
				return false;
				break;
		}

		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Don't display a toolbar if plugin is disabled:
			return false;
		}

		if( empty( $search_list ) )
		{	// No list defined
			return false;
		}

		$search_list = explode( "\n", str_replace( array( '\r\n', '\n\n' ), '\n', $search_list ) );

		$tagButtons = $this->get_tag_buttons( $search_list );

		if( empty( $tagButtons ) )
		{	// No buttons for toolbar
			return false;
		}

		// Load js to work with textarea
		require_js_defer( 'functions.js', 'blog', true );

		$js_config = array(
				'plugin_code' => $this->code,
				'js_prefix'   => $params['js_prefix'],
				'tag_buttons' => $tagButtons,

				'toolbar_button_class' => $this->get_template( 'toolbar_button_class'),
				'toolbar_title_before' => $this->get_template( 'toolbar_title_before' ),
				'toolbar_title_after'  => $this->get_template( 'toolbar_title_after' ),
				'toolbar_label'        => $this->toolbar_label,
				'toolbar_group_before' => $this->get_template( 'toolbar_group_before' ),
				'toolbar_group_after'  => $this->get_template( 'toolbar_group_after' ),

				'btn_title_close_all_tags' => T_('Close all tags'),
			);

		// Toolbar plugins extending this plugin will also use the same JS var evo_init_custom_tags_toolbar_config.
		// We prefix the config params with the plugin code to avoid overriding existing params.
		expose_var_to_js( $this->code.'_'.$params['js_prefix'], $js_config, 'evo_init_custom_tags_toolbar_config' );

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $js_code_prefix.'toolbar' ) );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}

	function get_tag_buttons( $search_list )
	{
		$tagButtons = array();

		foreach( $search_list as $line )
		{	// Init buttons from regexp lines
			$line = explode( ' ', $line, 2 );
			if( !empty( $line[0] ) && !empty( $line[1] ) )
			{
				$button_name = $line[0];
				$button_exp = $line[1];
				$start = preg_replace( '#(.+)\[([a-z0-1=\*\\\\]+)((\(.*\))*)\](.+)#is', '[$2]', $button_exp );
				$end = preg_replace( '#(.+)\[\/(.+)\](.+)#is', '[/$2]', $button_exp );
				$tagButtons[ $button_name ] = array(
						'name'  => $button_name,
						'start' => str_replace( '\\', '', $start ),
						'end'   => $end == $button_exp ? '' : $end,
						'title' => str_replace( array( '[', ']' ), '', $button_name ),
					);
			}
		}

		return $tagButtons;
	}
}
?>
