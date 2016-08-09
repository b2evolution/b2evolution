<?php
/**
 * This file implements the Markdown plugin for b2evolution
 *
 * Markdown
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class markdown_plugin extends Plugin
{
	var $code = 'b2evMark';
	var $name = 'Markdown';
	var $priority = 20;
	var $version = '6.7.5';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'markdown-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		require_once( dirname( __FILE__ ).'/_parsedown.inc.php' );

		$this->short_desc = T_('Markdown');
		$this->long_desc = T_('Accepted formats:<br />
# h1<br />
## h2<br />
### h3<br />
#### h4<br />
##### h5<br />
###### h6<br />
--- (horizontal rule)<br />
* * * (horizontal rule)<br />
- - - - (horizontal rule)<br />
`code spans`<br />
> blockquote');
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
			'links' => array(
					'label' => T_( 'Links' ),
					'type' => 'checkbox',
					'note' => T_( 'Detect and convert markdown link markup.' ),
					'defaultvalue' => 0,
				),
			'images' => array(
					'label' => T_( 'Images' ),
					'type' => 'checkbox',
					'note' => T_( 'Detect and convert markdown image markup.' ),
					'defaultvalue' => 0,
				),
			'text_styles' => array(
					'label' => T_( 'Italic & Bold styles' ),
					'type' => 'checkbox',
					'note' => T_( 'Detect and convert markdown italics and bold markup.' ),
					'defaultvalue' => 0,
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
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'opt-out' ) );
		return parent::get_msg_setting_definitions( $default_params );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'opt-out' ) );
		return parent::get_email_setting_definitions( $default_params );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 *   'data': the data (by reference). You probably want to modify this.
	 *   'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		if( !empty( $params['Item'] ) )
		{ // Get Item from params
			$Item = & $params['Item'];
		}
		elseif( !empty( $params['Comment'] ) )
		{ // Get Item from Comment
			$Comment = & $params['Comment'];
			$Item = & $Comment->get_Item();
		}

		if( ! empty( $Item ) )
		{ // We are rendering Item or Comment now, Get the settings depending on Blog
			$item_Blog = & $Item->get_Blog();
			$text_styles_enabled = $this->get_coll_setting( 'text_styles', $item_Blog );
			$links_enabled = $this->get_coll_setting( 'links', $item_Blog );
			$images_enabled = $this->get_coll_setting( 'images', $item_Blog );
		}
		elseif( ! empty( $params['Message'] ) )
		{	// We are rendering Message now:
			$text_styles_enabled = $this->get_msg_setting( 'text_styles' );
			$links_enabled = $this->get_msg_setting( 'links' );
			$images_enabled = $this->get_msg_setting( 'images' );
		}
		elseif( ! empty( $params['EmailCampaign'] ) )
		{	// We are rendering EmailCampaign now:
			$text_styles_enabled = $this->get_email_setting( 'text_styles' );
			$links_enabled = $this->get_email_setting( 'links' );
			$images_enabled = $this->get_email_setting( 'images' );
		}
		else
		{ // Unknown call, Don't render this case
			return;
		}

		// Init parser class with blog settings
		$Parsedown = Parsedown::instance();
		$Parsedown->parse_font_styles = $text_styles_enabled;
		$Parsedown->parse_links = $links_enabled;
		$Parsedown->parse_images = $images_enabled;

		// Parse markdown code to HTML
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
		{ // Call replace_content() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				array( $Parsedown, 'parse' ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = $Parsedown->parse( $content );
		}

		return true;
	}


	/**
	 * Event handler: Defines blog settings by its kind. Use {@link get_collection_kinds()} to return
	 * an array of available blog kinds and their names.
	 * Define new blog kinds in {@link Plugin::GetCollectionKinds()} method of your plugin.
	 *
	 * Note: You have to change $params['Blog'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'Blog': created Blog (by reference)
	 *   - 'kind': the kind of created blog (by reference)
	 */
	function InitCollectionKinds( & $params )
	{
		if( empty( $params['Blog'] ) || empty( $params['kind'] ) )
		{ // Invalid data, Exit here
			return;
		}

		switch( $params['kind'] )
		{
			case 'forum':
			case 'manual':
				$params['Blog']->set_setting( 'plugin'.$this->ID.'_coll_apply_comment_rendering', 'opt-out' );
				$params['Blog']->set_setting( 'plugin'.$this->ID.'_images', '1' );
				$params['Blog']->set_setting( 'plugin'.$this->ID.'_links', '1' );
				break;
		}
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @todo dh> This seems to be a lot of Javascript. Please try exporting it in a
	 *       (dynamically created) .js src file. Then we could use cache headers
	 *       to let the browser cache it.
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( ! empty( $params['Item'] ) )
		{ // Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{ // Item is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen
		return $this->DisplayCodeToolbar( 'coll', $Blog );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( ! empty( $params['Comment'] ) )
		{ // Comment is set, get Blog from comment
			$Comment = & $params['Comment'];
			if( !empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{ // Comment is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{ // Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen
		return $this->DisplayCodeToolbar( 'coll', $Blog );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{ // Print toolbar on screen
			return $this->DisplayCodeToolbar( 'msg' );
		}
		return false;
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
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( 'email' );
		}
		return false;
	}


	/**
	 * Display Toolbar
	 *
	 * @param string Setting type: 'coll', 'msg', 'email'
	 * @param object Blog
	 */
	function DisplayCodeToolbar( $type = 'coll', $Blog = NULL )
	{
		global $Hit;

		if( $Hit->is_lynx() )
		{ // let's deactivate toolbar on Lynx, because they don't work there.
			return false;
		}

		switch( $type )
		{
			case 'msg':
				// Get plugin setting values for messages:
				$text_styles_enabled = $this->get_msg_setting( 'text_styles' );
				$links_enabled = $this->get_msg_setting( 'links' );
				$images_enabled = $this->get_msg_setting( 'images' );
				break;

			case 'email':
				// Get plugin setting values for emails:
				$text_styles_enabled = $this->get_email_setting( 'text_styles' );
				$links_enabled = $this->get_email_setting( 'links' );
				$images_enabled = $this->get_email_setting( 'images' );
				break;

			default:
				// Get plugin setting values for current collection:
				$text_styles_enabled = $this->get_coll_setting( 'text_styles', $Blog );
				$links_enabled = $this->get_coll_setting( 'links', $Blog );
				$images_enabled = $this->get_coll_setting( 'images', $Blog );
				break;
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
		//<![CDATA[
		var markdown_btns = new Array();
		var markdown_open_tags = new Array();

		function markdown_btn( id, text, title, tag_start, tag_end, style, open, grp_pos )
		{
			this.id = id;               // used to name the toolbar button
			this.text = text;           // label on button
			this.title = title;         // title
			this.tag_start = tag_start; // open tag
			this.tag_end = tag_end;     // close tag
			this.style = style;         // style on button
			this.open = open;           // set to -1 if tag does not need to be closed
			this.grp_pos = grp_pos;     // position in the group, e.g. 'last'
		}

<?php
	if( $text_styles_enabled )
	{ // Show thess buttons only when plugin setting "Italic & Bold styles" is enabled ?>
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_bold','bold', '<?php echo TS_('Bold') ?>',
				'**','**',
				'font-weight:bold'
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_italic','italic', '<?php echo TS_('Italic') ?>',
				'*','*',
				'font-style:italic', -1, 'last'
			);
<?php
	}

	if( $links_enabled )
	{ // Show this button only when plugin setting "Links" is enabled ?>
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_link', 'link','<?php echo TS_('Link') ?>',
				'','',
				'text-decoration:underline', -1<?php echo ! $images_enabled ? ', \'last\'' : '' ?>
			);
<?php
	}

	if( $images_enabled )
	{ // Show this button only when plugin setting "Images" is enabled ?>
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_img', 'img','<?php echo TS_('Image') ?>',
				'','',
				'', -1, 'last'
			);
<?php
	} ?>

		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_h1','H1', '<?php echo TS_('Header 1') ?>',
				'\n# ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_h1','H2', '<?php echo TS_('Header 2') ?>',
				'\n## ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_h1','H3', '<?php echo TS_('Header 3') ?>',
				'\n### ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_h1','H4', '<?php echo TS_('Header 4') ?>',
				'\n#### ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_h1','H5', '<?php echo TS_('Header 5') ?>',
				'\n##### ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_h1','H6', '<?php echo TS_('Header 6') ?>',
				'\n###### ','',
				'', -1, 'last'
			);

		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_li','li', '<?php echo TS_('Unordered list item') ?>',
				'\n* ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_ol','ol', '<?php echo TS_('Ordered list item') ?>',
				'\n1. ','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_li','blockquote', '<?php echo TS_('Blockquote') ?>',
				'\n> ','',
				'', -1, 'last'
			);

		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_codespan','codespan', '<?php echo TS_('Codespan') ?>',
				'`','`',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_preblock','preblock', '<?php echo TS_('Preformatted code block') ?>',
				'\n\t','',
				'', -1, 'last'
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_codeblock','codeblock', '<?php echo TS_('Highlighted code block') ?>',
				'\n```\n','\n```\n',
				'', -1, 'last'
			);

		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_hr','hr', '<?php echo TS_('Horizontal Rule') ?>',
				'\n---\n','',
				'', -1
			);
		markdown_btns[markdown_btns.length] = new markdown_btn(
				'mrkdwn_br','<br>', '<?php echo TS_('Line Break') ?>',
				'  \n','',
				'', -1
			);

		function markdown_get_btn( button, i )
		{
			var r = '';
			if( button.id == 'mrkdwn_img' )
			{ // Image
				r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="markdown_insert_lnkimg|b2evoCanvas|img" value="' + button.text + '" />';
			}
			else if( button.id == 'mrkdwn_link' )
			{ // Link
				r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="markdown_insert_lnkimg|b2evoCanvas" value="' + button.text + '" />';
			}
			else
			{ // Normal buttons:
				r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="markdown_insert_tag|b2evoCanvas|'+i+'" value="' + button.text + '" />';
			}

			return r;
		}

		// Memorize a new open tag
		function markdown_add_tag( button )
		{
			if( markdown_btns[button].tag_end != '' )
			{
				markdown_open_tags[markdown_open_tags.length] = button;
				document.getElementById( markdown_btns[button].id ).value = '/' + document.getElementById( markdown_btns[button].id ).value;
			}
		}

		// Forget about an open tag
		function markdown_remove_tag( button )
		{
			for( i = 0; i < markdown_open_tags.length; i++ )
			{
				if( markdown_open_tags[i] == button )
				{
					markdown_open_tags.splice( i, 1 );
					document.getElementById( markdown_btns[button].id ).value = document.getElementById( markdown_btns[button].id ).value.replace( '/', '' );
				}
			}
		}

		function markdown_check_open_tags( button )
		{
			var tag = 0;
			for( i = 0; i < markdown_open_tags.length; i++ )
			{
				if( markdown_open_tags[i] == button )
				{
					tag++;
				}
			}

			if( tag > 0 )
			{
				return true; // tag found
			}
			else
			{
				return false; // tag not found
			}
		}

		function markdown_close_all_tags()
		{
			var count = markdown_open_tags.length;
			for( o = 0; o < count; o++ )
			{
				markdown_insert_tag( b2evoCanvas, markdown_open_tags[markdown_open_tags.length - 1] );
			}
		}

		function markdown_toolbar( title )
		{
			var r = '<?php echo $this->get_template( 'toolbar_title_before' ); ?>' + title + '<?php echo $this->get_template( 'toolbar_title_after' ); ?>'
				+ '<?php echo $this->get_template( 'toolbar_group_before' ); ?>';
			for( var i = 0; i < markdown_btns.length; i++ )
			{
				r += markdown_get_btn( markdown_btns[i], i );
				if( markdown_btns[i].grp_pos == 'last' && i > 0 && i < markdown_btns.length - 1 )
				{ // Separator between groups
					r += '<?php echo $this->get_template( 'toolbar_group_after' ).$this->get_template( 'toolbar_group_before' ); ?>';
				}
			}
			r += '<?php echo $this->get_template( 'toolbar_group_after' ).$this->get_template( 'toolbar_group_before' ); ?>'
				+ '<input type="button" id="mrkdwn_close" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="markdown_close_all_tags" title="<?php echo format_to_output( TS_('Close all tags'), 'htmlattr' ); ?>" value="X" />'
				+ '<?php echo $this->get_template( 'toolbar_group_after' ); ?>';

			jQuery( '.<?php echo $this->code ?>_toolbar' ).html( r );
		}

		function markdown_insert_tag( field, i )
		{
			// we need to know if something is selected.
			// First, ask plugins, then try IE and Mozilla.
			var sel_text = b2evo_Callbacks.trigger_callback( "get_selected_text_for_" + field.id );
			var focus_when_finished = false; // used for IE

			if( sel_text == null )
			{ // detect selection:
				//IE support
				if( document.selection )
				{
					field.focus();
					var sel = document.selection.createRange();
					sel_text = sel.text;
					focus_when_finished = true;
				}
				//MOZILLA/NETSCAPE support
				else if( field.selectionStart || field.selectionStart == '0' )
				{
					var startPos = field.selectionStart;
					var endPos = field.selectionEnd;
					sel_text = ( startPos != endPos );
				}
			}

			if( sel_text )
			{ // some text selected
				textarea_wrap_selection( field, markdown_btns[i].tag_start, markdown_btns[i].tag_end, 0 );
			}
			else
			{
				if( !markdown_check_open_tags(i) || markdown_btns[i].tag_end == '' )
				{
					textarea_wrap_selection( field, markdown_btns[i].tag_start, '', 0 );
					markdown_add_tag(i);
				}
				else
				{
					textarea_wrap_selection( field, '', markdown_btns[i].tag_end, 0 );
					markdown_remove_tag(i);
				}
			}
			if( focus_when_finished )
			{
				field.focus();
			}
		}


		function markdown_insert_lnkimg( field, type )
		{
			var url = prompt( '<?php echo TS_('URL') ?>:', 'http://' );
			if( url )
			{
				url = '[' + prompt('<?php echo TS_('Text') ?>:', '') + ']'
					+ '(' + url;
				var title = prompt( '<?php echo TS_('Title') ?>:', '' );
				if( title != '' )
				{
					url += ' "' + title + '"';
				}
				url += ')';
				if( typeof( type ) != 'undefined' && type == 'img' )
				{ // for <img> tag
					url = '!' + url;
				}
				textarea_wrap_selection( field, url, '', 1 );
			}
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script type="text/javascript">markdown_toolbar( '<?php echo TS_('Markdown').': '; ?>' );</script><?php

		return true;
	}
}

?>