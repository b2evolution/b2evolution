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
	var $toolbar_label = 'Custom Tags:';
	var $configurable_post_list = true;
	var $configurable_comment_list = true;
	var $configurable_message_list = true;
	var $configurable_email_list = true;

	var $post_search_list;
	var $post_replace_list;
	var $comment_search_list;
	var $comment_replace_list;
	var $msg_search_list;
	var $msg_replace_list;
	var $email_search_list;
	var $email_replace_list;

	var $default_search_list = '[warning] #\[warning](.+?)\[/warning]#is
[info] #\[info](.+?)\[/info]#is';

	var $default_replace_list = '<div class="alert alert-warning">$1</div>
<div class="alert alert-info">$1</div>';


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
			$regexp = $line[1];
			if( empty( $regexp ) )
			{ // Bad format of search string
				unset( $search_list_array[$l] );
			}
			else
			{ // Replace this line with regex value (to delete a button name)
				$search_list_array[ $l ] = $regexp;
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
	 * Perform rendering of item
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		if( !empty( $params['Item'] ) )
		{	// Get Item from params:
			$Item = & $params['Item'];
		}
		elseif( !empty( $params['Comment'] ) )
		{	// Get Item from Comment:
			$Comment = & $params['Comment'];
			$Item = & $Comment->get_Item();
		}

		$item_Blog = & $Item->get_Blog();

		if( ! isset( $this->post_search_list ) )
		{
			$search_list = $this->get_coll_setting( 'coll_post_search_list', $item_Blog );
			if( ! $search_list )
			{
				$search_list = $this->default_search_list;
			}
			$this->post_search_list = $this->prepare_search_list( $search_list );
		}

		if( ! isset( $this->post_replace_list ) )
		{
			$replace_list = $this->get_coll_setting( 'coll_post_replace_list', $item_Blog );
			if( ! $replace_list )
			{
				$replace_list = $this->default_replace_list;
			}
			$this->post_replace_list = $this->prepare_replace_list( $replace_list );
		}

		$callback = array( $this, 'replace_callback' );

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
		$content = replace_content_outcode( $this->email_search_list, $this->email_replace_list, $content, $callback );

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

		$js_code_prefix = $params['js_prefix'].$this->code;

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
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
		//<![CDATA[
		<?php echo $js_code_prefix;?>_tagButtons = new Array();
		<?php echo $js_code_prefix;?>_tagOpenTags = new Array();


		<?php echo $js_code_prefix;?>_tagButton = function( id, display, style, tagStart, tagEnd, access, tit, open )
		{
			this.id = id;							// used to name the toolbar button
			this.display = display;		// label on button
			this.style = style;				// style on button
			this.tagStart = tagStart; // open tag
			this.tagEnd = tagEnd;			// close tag
			this.access = access;			// access key
			this.tit = tit;						// title
			this.open = open;					// set to -1 if tag does not need to be closed
		}

		<?php
		foreach( $tagButtons as $tagButton )
		{	// Init each button
		?>
		<?php echo $js_code_prefix;?>_tagButtons[<?php echo $js_code_prefix;?>_tagButtons.length] = new <?php echo $js_code_prefix;?>_tagButton(
				'tag_<?php echo $tagButton['title']; ?>'
				,'<?php echo $tagButton['name']; ?>', ''
				,'<?php echo $tagButton['start']; ?>', '<?php echo $tagButton['end']; ?>', ''
				,'<?php echo $tagButton['title']; ?>'
			);
		<?php
		}
		?>

		<?php echo $js_code_prefix;?>_tagGetButton = function( button, i )
		{
			return '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="<?php echo $js_code_prefix;?>_tagInsertTag|<?php echo $params['js_prefix']; ?>b2evoCanvas|'+i+'" value="' + button.display + '" />';
		}

		// Memorize a new open tag
		<?php echo $js_code_prefix;?>_tagAddTag = function( button )
		{
			if( <?php echo $js_code_prefix;?>_tagButtons[button].tagEnd != '' )
			{
				<?php echo $js_code_prefix;?>_tagOpenTags[<?php echo $js_code_prefix;?>_tagOpenTags.length] = button;
				document.getElementById(<?php echo $js_code_prefix;?>_tagButtons[button].id).style.fontWeight = 'bold';
			}
		}

		// Forget about an open tag
		<?php echo $js_code_prefix;?>_tagRemoveTag = function( button )
		{
			for ( i = 0; i < <?php echo $js_code_prefix;?>_tagOpenTags.length; i++ )
			{
				if ( <?php echo $js_code_prefix;?>_tagOpenTags[i] == button)
				{
					<?php echo $js_code_prefix;?>_tagOpenTags.splice(i, 1);
					document.getElementById(<?php echo $js_code_prefix;?>_tagButtons[button].id).style.fontWeight = 'normal';
				}
			}
		}

		<?php echo $js_code_prefix;?>_tagCheckOpenTags = function( button )
		{
			var tag = 0;
			for (i = 0; i < <?php echo $js_code_prefix;?>_tagOpenTags.length; i++)
			{
				if (<?php echo $js_code_prefix;?>_tagOpenTags[i] == button)
				{
					tag++;
				}
			}

			if (tag > 0)
			{
				return true; // tag found
			}
			else
			{
				return false; // tag not found
			}
		}

		<?php echo $js_code_prefix;?>_tagCloseAllTags = function()
		{
			var count = <?php echo $js_code_prefix;?>_tagOpenTags.length;
			for (o = 0; o < count; o++)
			{
				<?php echo $js_code_prefix;?>_tagInsertTag( <?php echo $params['js_prefix']; ?>b2evoCanvas, <?php echo $js_code_prefix;?>_tagOpenTags[<?php echo $js_code_prefix;?>_tagOpenTags.length - 1] );
			}
		}

		<?php echo $js_code_prefix;?>_tagToolbar = function()
		{
			var tagcode_toolbar = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ).$this->toolbar_label.' '.$this->get_template( 'toolbar_title_after' ) ); ?>';
			tagcode_toolbar += '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>';
			for( var i = 0; i < <?php echo $js_code_prefix;?>_tagButtons.length; i++ )
			{
				tagcode_toolbar += <?php echo $js_code_prefix;?>_tagGetButton( <?php echo $js_code_prefix;?>_tagButtons[i], i );
			}
			tagcode_toolbar += '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ).$this->get_template( 'toolbar_group_before' ) ); ?>';
			tagcode_toolbar += '<input type="button" id="tag_close" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="<?php echo $js_code_prefix;?>_tagCloseAllTags" title="<?php echo TS_('Close all tags') ?>" value="X" />';
			tagcode_toolbar += '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';
			jQuery( '.<?php echo $js_code_prefix; ?>_toolbar' ).html( tagcode_toolbar );
		}

		/**
		 * insertion code
		 */
		<?php echo $js_code_prefix;?>_tagInsertTag = function( myField, i )
		{
			// we need to know if something is selected.
			// First, ask plugins, then try IE and Mozilla.
			var sel_text = b2evo_Callbacks.trigger_callback("get_selected_text_for_"+myField.id);
			var focus_when_finished = false; // used for IE

			if( sel_text == null || sel_text == false )
			{ // detect selection:
				//IE support
				if(document.selection)
				{
					myField.focus();
					var sel = document.selection.createRange();
					sel_text = sel.text;
					focus_when_finished = true;
				}
				//MOZILLA/NETSCAPE support
				else if(myField.selectionStart || myField.selectionStart == '0')
				{
					var startPos = myField.selectionStart;
					var endPos = myField.selectionEnd;
					sel_text = (startPos != endPos);
				}
			}

			if( sel_text )
			{ // some text selected
				textarea_wrap_selection( myField, <?php echo $js_code_prefix;?>_tagButtons[i].tagStart, <?php echo $js_code_prefix;?>_tagButtons[i].tagEnd, 0 );
			}
			else
			{
				if( !<?php echo $js_code_prefix;?>_tagCheckOpenTags(i) || <?php echo $js_code_prefix;?>_tagButtons[i].tagEnd == '')
				{
					textarea_wrap_selection( myField, <?php echo $js_code_prefix;?>_tagButtons[i].tagStart, '', 0 );
					<?php echo $js_code_prefix;?>_tagAddTag(i);
				}
				else
				{
					textarea_wrap_selection( myField, '', <?php echo $js_code_prefix;?>_tagButtons[i].tagEnd, 0 );
					<?php echo $js_code_prefix;?>_tagRemoveTag(i);
				}
			}
			if(focus_when_finished)
			{
				myField.focus();
			}
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $js_code_prefix.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script type="text/javascript"><?php echo $js_code_prefix;?>_tagToolbar();</script><?php

		return true;
	}

	function get_tag_buttons( $search_list )
	{
		$tagButtons = array();

		foreach( $search_list as $line )
		{	// Init buttons from regexp lines
			$line = explode( ' ', $line, 2 );
			$button_name = $line[0];
			$button_exp = $line[1];
			if( !empty( $button_name ) && !empty( $button_exp ) )
			{
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