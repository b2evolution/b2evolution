<?php
/**
 * This file implements the BBcode plugin for b2evolution
 *
 * BB style formatting, like [b]bold[/b]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class bbcode_plugin extends Plugin
{
	var $code = 'b2evBBco';
	var $name = 'BB code';
	var $priority = 50;
	var $version = '5.0.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'bb-code-plugin';
	var $number_of_installs = 1;

	/*
	 * Internal
	 */
	var $post_search_list;
	var $post_replace_list;
	var $comment_search_list;
	var $comment_replace_list;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('BB formatting e-g [b]bold[/b]');
		$this->long_desc = T_('Supported tags and the BB code toolbar are configurable.
Supported tags by default are: [b] [i] [s] [color=...] [size=...] [font=...] [quote] [list=1] [list=a] [list] [*] [bg=] [clear]');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		// TODO: Post and comment search/replace lists must be also converted to coll plugin settings
		$default_params = array_merge( $params, array( 'default_comment_rendering' => 'never' ) );
		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'coll_post_search_list' => array(
					'label' => $this->T_( 'Search list for posts'),
					'note' => $this->T_( 'This is the BBcode search array for posts (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING' ),
					'type' => 'html_textarea',
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => '[b] #\[b](.+?)\[/b]#is
[i] #\[i](.+?)\[/i]#is
[s] #\[s](.+?)\[/s]#is
[color] !\[color=(#?[A-Za-z0-9]+?)](.+?)\[/color]!is
[size] #\[size=([0-9]+?)](.+?)\[/size]#is
[font] #\[font=([A-Za-z0-9 ;\-]+?)](.+?)\[/font]#is
 #\[quote(=?)](.+?)\[/quote]#is
 #\[quote=([^\]\#]*?)\#([cp][0-9]+)](.+?)\[/quote]#is
[quote] #\[quote=([^\]]*?)](.+?)\[/quote]#is
[indent] #\[indent](.+?)\[/indent]#is
[list=1] #\[list=1](.+?)\[/list]#is
[list=a] #\[list=a](.+?)\[/list]#is
[list] #\[list](.+?)\[/list]#is
[*] #\[\*](.+?)(\n|\[/list\])#is
[bg] !\[bg=(#?[A-Za-z0-9]+?)](.+?)\[/bg]!is
[clear] #\[clear]#is',
				),
				'coll_post_replace_list' => array(
					'label' => $this->T_( 'Replace list for posts'),
					'note' => $this->T_( 'This is the replace array for posts (one per line) it must match the exact order of the search array' ),
					'type' => 'html_textarea',
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => '<strong>$1</strong>
<em>$1</em>
<span style="text-decoration:line-through">$1</span>
<span style="color:$1">$2</span>
<span style="font-size:$1px">$2</span>
<span style="font-family:$1">$2</span>
<blockquote>$2</blockquote>
<strong class="quote_author">$1 wrote <a href="#$2">earlier</a>:</strong><blockquote>$3</blockquote>
<strong class="quote_author">$1 wrote:</strong><blockquote>$2</blockquote>
<div class="indented">$1</div>
<ol type="1">$1</ol>
<ol type="a">$1</ol>
<ul>$1</ul>
<li>$1</li>
<span style="background-color:$1">$2</span>
<div class="clear"></div>',
				),
				'coll_comment_search_list' => array(
					'label' => $this->T_( 'Search list for comments'),
					'note' => $this->T_( 'This is the BBcode search array for COMMENTS (one per line) ONLY CHANGE THESE IF YOU KNOW WHAT YOU\'RE DOING' ),
					'type' => 'html_textarea',
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => '[b] #\[b](.+?)\[/b]#is
[i] #\[i](.+?)\[/i]#is
[s] #\[s](.+?)\[/s]#is
[color] !\[color=(#?[A-Za-z0-9]+?)](.+?)\[/color]!is
[size] #\[size=([0-9]+?)](.+?)\[/size]#is
[font] #\[font=([A-Za-z0-9 ;\-]+?)](.+?)\[/font]#is
 #\[quote(=?)](.+?)\[/quote]#is
 #\[quote=([^\]\#]*?)\#([cp][0-9]+)](.+?)\[/quote]#is
[quote] #\[quote=([^\]]*?)](.+?)\[/quote]#is
[indent] #\[indent](.+?)\[/indent]#is
[list=1] #\[list=1](.+?)\[/list]#is
[list=a] #\[list=a](.+?)\[/list]#is
[list] #\[list](.+?)\[/list]#is
[*] #\[\*](.+?)(\n|\[/list\])#is
[bg] !\[bg=(#?[A-Za-z0-9]+?)](.+?)\[/bg]!is
[clear] #\[clear]#is',
				),
				'coll_comment_replace_list' => array(
					'label' => $this->T_( 'Replace list for comments'),
					'note' => $this->T_( 'This is the replace array for COMMENTS (one per line) it must match the exact order of the search array' ),
					'type' => 'html_textarea',
					'rows' => 10,
					'cols' => 60,
					'defaultvalue' => '<strong>$1</strong>
<em>$1</em>
<span style="text-decoration:line-through">$1</span>
<span style="color:$1">$2</span>
<span style="font-size:$1px">$2</span>
<span style="font-family:$1">$2</span>
<blockquote>$2</blockquote>
<strong class="quote_author">$1 wrote <a href="#$2">earlier</a>:</strong><blockquote>$3</blockquote>
<strong class="quote_author">$1 wrote:</strong><blockquote>$2</blockquote>
<div class="indented">$1</div>
<ol type="1">$1</ol>
<ol type="a">$1</ol>
<ul>$1</ul>
<li>$1</li>
<span style="background-color:$1">$2</span>
<div class="clear"></div>',
				),
			)
		);
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];
		$Item = $params['Item'];
		$item_Blog = & $Item->get_Blog();
		if( !isset( $this->post_search_list ) )
		{
			$this->post_search_list = $this->prepare_search_list( 'coll_post_search_list', $item_Blog );
		}

		if( !isset( $this->post_replace_list ) )
		{
			$this->post_replace_list = explode( "\n", str_replace( "\r", '', $this->get_coll_setting( 'coll_post_replace_list', $item_Blog ) ) );
		}

		$content = replace_content_outcode( $this->post_search_list, $this->post_replace_list, $content, array( $this, 'parse_bbcode' ) );

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
		$Blog = NULL;
		if( !isset( $this->msg_search_list ) )
		{
			$this->msg_search_list = $this->prepare_search_list( 'coll_comment_search_list', $Blog, true );
		}

		if( !isset( $this->msg_replace_list ) )
		{
			$this->msg_replace_list = explode( "\n", str_replace( "\r", '', $this->get_coll_setting( 'coll_comment_replace_list', $Blog, true ) ) );
		}

		$content = replace_content_outcode( $this->msg_search_list, $this->msg_replace_list, $content, array( $this, 'parse_bbcode' ) );

		return true;
	}


	/**
	 * Parse BB code
	 *   ( The main purpose of this function is to parsing multilevel lists tags,
	 *     i.e. when one [list] is contained inside other [list] )
	 *
	 * @param string Content
	 * @param array Search list
	 * @param array Replace list
	 * @return string Content
	 */
	function parse_bbcode( $content, $search_list, $replace_list )
	{
		if( empty( $content ) )
		{ // No content, Exit here
			return $content;
		}

		$complex = array();
		$simple = array();

		foreach( $search_list as $i => $search_text )
		{ // Split the masks to separate things
			preg_match( '#(\\\\\[.+\])\(.+\)(\\\\\[.+\])#is', $search_text, $search_tags );
			preg_match( '#(<.+>)\$.+(<.+>)#is', $replace_list[ $i ], $replace_tags );
			if( !empty( $search_tags ) )
			{
				$complex[] = array(
						's_full'  => $search_text,
						's_start' => '!^'.$search_tags[1].'!is',
						's_end'   => '!^'.$search_tags[2].'!is',
						'r_full'  => $replace_list[ $i ],
						'r_start' => $replace_tags[1],
						'r_end'   => $replace_tags[2],
					);
			}
			else
			{
				$simple[] = array(
						's_full' => $search_text,
						'r_full' => $replace_list[ $i ],
					);
			}
		}
		//pre_dump($complex);

		foreach( $simple as $tag_info )
		{ // Make simple replacements
			$content = preg_replace( $tag_info['s_full'], $tag_info['r_full'], $content );
		}

		// Complex parsing with multilevel tags structure
		$c_pos = 0;
		$content_length = strlen( $content );
		$opened_tags = array();
		$new_content = $content;
		while( $c_pos < $content_length - 2 )
		{
			if( $content[ $c_pos ].$content[ $c_pos + 1 ] == '[/' )
			{ // Closed tag
				if( !empty( $opened_tags ) )
				{	// Get closed tag that was opened as last
					$sub_content = substr( $content, $c_pos );
					$closed_tag_info = $complex[ $opened_tags[ count( $opened_tags ) - 1 ] ];
					if( preg_match( $closed_tag_info['s_end'], $sub_content ) )
					{
						$first_part = str_replace( $sub_content, '', $new_content );
						$sub_content = preg_replace( $closed_tag_info['s_end'], $closed_tag_info['r_end'], $sub_content );
						$new_content = $first_part.$sub_content;
						//pre_dump( 'CLOSE', $opened_tags, $closed_tag_info['r_end'], $closed_tag_info['s_end'], $new_content );
						// Remove this tag from opened tags array
						array_pop( $opened_tags );
					}
				}
			}
			elseif( $content[ $c_pos ] == '[' )
			{ // Opened tag
				$sub_content = substr( $content, $c_pos );
				foreach( $complex as $tag_num => $tag_info )
				{
					if( preg_match( $tag_info['s_start'], $sub_content ) )
					{
						$first_part = str_replace( $sub_content, '', $new_content );
						$sub_content = preg_replace( $tag_info['s_start'], $tag_info['r_start'], $sub_content );
						$new_content = $first_part.$sub_content;
						// Add this tag to know what tag was opened as last
						$opened_tags[] = $tag_num;
						//pre_dump( 'OPEN', $opened_tags, $tag_info['s_start'], $new_content );
						break;
					}
				}
			}
			$c_pos++;
		}
		//pre_dump($new_content);

		$new_content = $this->parse_anchor_links( $new_content );

		return $new_content;
	}


	/**
	 * Parse anchor links, Set absolute path for each link with relative anchor like <a href="#">
	 *
	 * @param string Content
	 * @return string Content
	 */
	function parse_anchor_links( $content )
	{
		if( preg_match_all( '/ href="#(c|p)([0-9]+)"/i', $content, $matches ) )
		{
			$CommentCache = & get_CommentCache();
			$ItemCache = & get_ItemCache();
			foreach( $matches[0] as $m => $full_match )
			{
				$object_ID = $matches[2][$m];
				$new_url = '';
				switch( $matches[1][$m] )
				{ // Object type:
					case 'p':
						// Item
						if( $Item = & $ItemCache->get_by_ID( $object_ID, false, false ) )
						{ // Replace anchor url with item permanent url
							$new_url = $Item->get_permanent_url().'#p'.$object_ID;
						}
						break;

					case 'c':
						// Comment
						if( $Comment = & $CommentCache->get_by_ID( $object_ID, false, false ) )
						{ // Replace anchor url with comment permanent url
							$new_url = $Comment->get_permanent_url();
						}
						break;

					default:
						// Incorrect object type, Skip this url
						continue;
				}
				if( !empty( $new_url ) )
				{ // Replace relative anchor url with new absolute url
					$content = str_replace( $full_match, ' href="'.$new_url.'"', $content );
				}
			}
		}

		return $content;
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
		$comment_Item = & $Comment->get_Item();
		$item_Blog = & $comment_Item->get_Blog();
		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{ // apply_comment_rendering is set to always render
			$content = & $params['data'];
			if( !isset( $this->comment_search_list ) )
			{
				$this->comment_search_list = $this->prepare_search_list( 'coll_comment_search_list', $item_Blog );
			}

			if( !isset( $this->comment_replace_list ) )
			{
				$this->comment_replace_list = explode( "\n", str_replace( "\r", '', $this->get_coll_setting( 'coll_comment_replace_list', $item_Blog ) ) );
			}

			$content = replace_content_outcode( $this->comment_search_list, $this->comment_replace_list, $content, array( $this, 'parse_bbcode' ) );
		}
	}


	/**
	 * Display a toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
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
				'target_type' => 'Item'
			), $params );

		switch( $params['target_type'] )
		{
			case 'Item':
				$search_list_setting_name = 'coll_post_search_list';
				$Item = $params['Item'];
				$item_Blog = & $Item->get_Blog();
				$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $item_Blog );
				$allow_null_blog = false;
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
				$allow_null_blog = false;
				break;

			case 'Message':
				$search_list_setting_name = 'coll_comment_search_list';
				$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
				$allow_null_blog = true;
				break;

			default:
				// Incorrect param
				return false;
				break;
		}

		if( $apply_rendering == 'never' )
		{	// Don't display a toolbar if plugin is disabled
			return false;
		}

		$search_list = trim( $this->get_coll_setting( $search_list_setting_name, $item_Blog, $allow_null_blog ) );

		if( empty( $search_list ) )
		{	// No list defined
			return false;
		}

		$search_list = explode( "\n", str_replace( array( '\r\n', '\n\n' ), '\n', $search_list ) );

		$bbButtons = array();
		foreach( $search_list as $line )
		{	// Init buttons from regexp lines
			$line = explode( ' ', $line, 2 );
			$button_name = $line[0];
			$button_exp = $line[1];
			if( !empty( $button_name ) && !empty( $button_exp ) )
			{
				$start = preg_replace( '#(.+)\[([a-z0-1=\*\\\\]+)((\(.*\))*)\](.+)#is', '[$2]', $button_exp );
				$end = preg_replace( '#(.+)\[\/(.+)\](.+)#is', '[/$2]', $button_exp );
				$bbButtons[ $button_name ] = array(
						'name'  => $button_name,
						'start' => str_replace( '\\', '', $start ),
						'end'   => $end == $button_exp ? '' : $end,
						'title' => str_replace( array( '[', ']' ), '', $button_name ),
					);
			}
		}

		if( empty( $bbButtons ) )
		{	// No buttons for toolbar
			return false;
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
		//<![CDATA[
		var bbButtons = new Array();
		var bbOpenTags = new Array();

		function bbButton(id, display, style, tagStart, tagEnd, access, tit, open)
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
		foreach( $bbButtons as $bbButton )
		{	// Init each button
		?>
		bbButtons[bbButtons.length] = new bbButton(
				'bb_<?php echo $bbButton['title']; ?>'
				,'<?php echo $bbButton['name']; ?>', ''
				,'<?php echo $bbButton['start']; ?>', '<?php echo $bbButton['end']; ?>', ''
				,'<?php echo $bbButton['title']; ?>'
			);
		<?php
		}
		?>

		function bbGetButton(button, i)
		{
			return '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="bbInsertTag|b2evoCanvas|'+i+'" value="' + button.display + '" />';
		}

		// Memorize a new open tag
		function bbAddTag(button)
		{
			if( bbButtons[button].tagEnd != '' )
			{
				bbOpenTags[bbOpenTags.length] = button;
				document.getElementById(bbButtons[button].id).style.fontWeight = 'bold';
			}
		}

		// Forget about an open tag
		function bbRemoveTag(button)
		{
			for (i = 0; i < bbOpenTags.length; i++)
			{
				if (bbOpenTags[i] == button)
				{
					bbOpenTags.splice(i, 1);
					document.getElementById(bbButtons[button].id).style.fontWeight = 'normal';
				}
			}
		}

		function bbCheckOpenTags(button)
		{
			var tag = 0;
			for (i = 0; i < bbOpenTags.length; i++)
			{
				if (bbOpenTags[i] == button)
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

		function bbCloseAllTags()
		{
			var count = bbOpenTags.length;
			for (o = 0; o < count; o++)
			{
				bbInsertTag(b2evoCanvas, bbOpenTags[bbOpenTags.length - 1]);
			}
		}

		function bbToolbar()
		{
			var bbcode_toolbar = '<?php echo $this->get_template( 'toolbar_title_before' ).T_('BB code:').' '.$this->get_template( 'toolbar_title_after' ); ?>';
			bbcode_toolbar += '<?php echo $this->get_template( 'toolbar_group_before' ); ?>';
			for( var i = 0; i < bbButtons.length; i++ )
			{
				bbcode_toolbar += bbGetButton( bbButtons[i], i );
			}
			bbcode_toolbar += '<?php echo $this->get_template( 'toolbar_group_after' ).$this->get_template( 'toolbar_group_before' ); ?>';
			bbcode_toolbar += '<input type="button" id="bb_close" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="bbCloseAllTags" title="<?php echo T_('Close all tags') ?>" value="X" />';
			bbcode_toolbar += '<?php echo $this->get_template( 'toolbar_group_after' ); ?>';
			jQuery( '.bbcode_toolbar' ).html( bbcode_toolbar );
		}

		/**
		 * insertion code
		 */
		function bbInsertTag( myField, i )
		{
			// we need to know if something is selected.
			// First, ask plugins, then try IE and Mozilla.
			var sel_text = b2evo_Callbacks.trigger_callback("get_selected_text_for_"+myField.id);
			var focus_when_finished = false; // used for IE

			if( sel_text == null )
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
				textarea_wrap_selection( myField, bbButtons[i].tagStart, bbButtons[i].tagEnd, 0 );
			}
			else
			{
				if( !bbCheckOpenTags(i) || bbButtons[i].tagEnd == '')
				{
					textarea_wrap_selection( myField, bbButtons[i].tagStart, '', 0 );
					bbAddTag(i);
				}
				else
				{
					textarea_wrap_selection( myField, '', bbButtons[i].tagEnd, 0 );
					bbRemoveTag(i);
				}
			}
			if(focus_when_finished)
			{
				myField.focus();
			}
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => 'bbcode_toolbar' ) );
		?><script type="text/javascript">bbToolbar();</script><?php
		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( !empty( $params['Comment'] ) )
		{ // Comment is set, get Blog from comment
			$Comment = & $params['Comment'];
			if( !empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Blog = & $comment_Item->get_Blog();
			}
		}

		if( !empty( $params['Item'] ) )
		{	// Get Blog from Item
			$comment_Item = & $params['Item'];
			$Blog = & $comment_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{ // Comment is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available
				return false;
			}
		}

		if( $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) )
		{
			$params['target_type'] = 'Comment';
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		if( $this->get_msg_setting( 'msg_apply_rendering' ) )
		{
			$params['target_type'] = 'Message';
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Prepare a search list
	 *
	 * @param string Setting name of search list (' post_search_list', 'comment_search_list' )
	 * @param object Blog
	 * @param boolean Allow empty Blog
	 * @return array Search list
	 */
	function prepare_search_list( $setting_name, $Blog = NULL, $allow_null_blog = false )
	{
		$search_list = explode( "\n", str_replace( "\r", '', $this->get_coll_setting( $setting_name, $Blog, $allow_null_blog ) ) );

		foreach( $search_list as $l => $line )
		{	// Remove button name from regexp string
			$line = explode( ' ', $line, 2 );
			$regexp = $line[1];
			if( empty( $regexp ) )
			{	// Bad format of search string
				unset( $search_list[ $l ] );
			}
			else
			{	// Replace this line with regexp value (to delete a button name)
				$search_list[ $l ] = $regexp;
			}
		}

		return $search_list;
	}
}

?>
