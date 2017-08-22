<?php
/**
 * This file implements the BBcode plugin for b2evolution
 *
 * BB style formatting, like [b]bold[/b]
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
 * @package plugins
 */
class bbcode_plugin extends custom_tags_plugin
{
	var $code = 'b2evBBco';
	var $name = 'BB code';
	var $priority = 50;
	var $version = '6.9.3';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'bb-code-plugin';
	var $number_of_installs = 1;

	/*
	 * Internal
	 */
	var $toolbar_label = 'BB code:';

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

	var $default_search_list = '[b] #\[b](.+?)\[/b]#is
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
[clear] #\[clear]#is';

	var $default_replace_list = '<strong>$1</strong>
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
<div class="clear"></div>';


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
	 * The following function are here so the events will be registered
	 * @see Plugins_admin::get_registered_events()
	 */
	function RenderItemAsHtml( & $params )
	{
		parent::RenderItemAsHtml( $params );
	}

	function RenderMessageAsHtml( & $params )
	{
		parent::RenderMessageAsHtml( $params );
	}

	function RenderEmailAsHtml( & $params )
	{
		parent::RenderItemAsHtml( $params );
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
			{	// Init comment search list only first time:
				$this->comment_search_list = $this->prepare_search_list( $this->get_coll_setting( 'coll_comment_search_list', $item_Blog ) );
			}

			if( !isset( $this->comment_replace_list ) )
			{	// Init comment replace list only first time:
				$this->comment_replace_list = $this->prepare_replace_list( $this->get_coll_setting( 'coll_comment_replace_list', $item_Blog ) );
			}

			$content = replace_content_outcode( $this->comment_search_list, $this->comment_replace_list, $content, array( $this, 'parse_bbcode' ) );
		}
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
			if( empty( $line[1] ) )
			{	// Bad format of search string
				unset( $search_list_array[ $l ] );
			}
			else
			{	// Replace this line with regexp value (to delete a button name)
				$search_list_array[ $l ] = $line[1];
			}
		}

		return $search_list_array;
	}
}

?>
