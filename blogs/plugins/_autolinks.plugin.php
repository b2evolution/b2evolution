<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Automatic links plugin.
 *
 * @todo dh> Provide a setting for: fp> This should be a DIFFERENT plugin that kicks in last in the rendering and actually prcesses ALL links, auto links as well as explicit/manual links
 *   - marking external and internal (relative URL or on the blog's URL) links with a HTML/CSS class
 *   - add e.g. 'target="_blank"' to external links
 * @todo Add "max. displayed length setting" and add full title + dots in the middle to shorten it.
 *       (e.g. plain long URLs with a lot of params and such). This should not cause the layout to
 *       behave ugly. This should only shorten non-whitespace strings in the link's innerHTML of course.
 *
 * @package plugins
 */
class autolinks_plugin extends Plugin
{
	var $code = 'b2evALnk';
	var $name = 'Auto Links';
	var $priority = 60;
	var $version = '5.0.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'autolinks-plugin';
	var $number_of_installs = null;	// Let admins install several instances with potentially different word lists

	/**
	 * Lazy loaded from txt files
	 *
	 * @var array of array for each blog. Index 0 is for shared content
	 */
	var $link_array = array();

	var $already_linked_array;

	/**
	 * Previous word from the text during the make clickable process
	 *
	 * @var string
	 */
	var $previous_word = null;
	/**
	 * Previous word in lower case format
	 *
	 * @var string
	 */
	var $previous_lword = null;
	/**
	 * Shows if the previous word was already used/converted to a link
	 *
	 * @var boolean
	 */
	var $previous_used = false;

	var $already_linked_usernames;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Make URLs and specific terms/defintions clickable');
		$this->long_desc = T_('This renderer automatically creates links for you. URLs can be made clickable automatically. Specific and frequently used terms can be configured to be automatically linked to a definition URL.');
	}


	/**
	 * @return array
	 */
	function GetDefaultSettings()
	{
		global $rsc_subdir;
		return array(
				'autolink_urls' => array(
						'label' => T_( 'Autolink URLs' ),
						'defaultvalue' => 1,
						'type' => 'checkbox',
						'note' => T_('Autolink URLs starting with http: https: mailto: aim: icq: as well as adresses of the form www.*.* or *@*.*'),
					),
				'autolink_defs_default' => array(
						'label' => T_( 'Autolink definitions' ),
						'defaultvalue' => 1,
						'type' => 'checkbox',
						'note' => T_('As defined in definitions.default.txt'),
					),
				'autolink_defs_local' => array(
						'label' => '',
						'defaultvalue' => 0,
						'type' => 'checkbox',
						'note' => T_('As defined in definitions.local.txt'),
					),
				'autolink_defs_db' => array(
						'label' => T_('Custom definitions'),
						'type' => 'html_textarea',
						'rows' => 15,
						'note' => $this->T_( 'Enter custom definitions above.' ),
						'defaultvalue' => '',
					),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_values = array(
				'autolink_defs_coll_db'              => '',
				'autolink_username'                  => 0,
				'autolink_post_nofollow_exist'       => 0,
				'autolink_post_nofollow_explicit'    => 0,
				'autolink_post_nofollow_auto'        => 0,
				'autolink_comment_nofollow_exist'    => 1,
				'autolink_comment_nofollow_explicit' => 1,
				'autolink_comment_nofollow_auto'     => 0,
			);

		if( !empty( $params['blog_type'] ) )
		{	// Set the default settings depends on blog type
			switch( $params['blog_type'] )
			{
				case 'forum':
				case 'manual':
					$default_values['autolink_post_nofollow_exist'] = 1;
					$default_values['autolink_post_nofollow_explicit'] = 1;
					break;
			}
		}

		// set params to allow rendering for comments by default
		$default_params = array_merge( $params, array( 'default_comment_rendering' => 'stealth' ) );
		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'autolink_defs_coll_db' => array(
						'label' => T_( 'Custom autolink definitions' ),
						'type' => 'html_textarea',
						'rows' => 15,
						'note' => $this->T_( 'Enter custom definitions above.' ),
						'defaultvalue' => $default_values['autolink_defs_coll_db'],
					),
				'autolink_username' => array(
						'label' => T_( 'Autolink usernames' ),
						'type' => 'checkbox',
						'note' => $this->T_( '@username will link to the user profile page' ),
						'defaultvalue' => $default_values['autolink_username'],
					),
				// No follow in posts
				'autolink_post_nofollow_exist' => array(
						'label' => T_( 'No follow in posts' ),
						'type' => 'checkbox',
						'note' => $this->T_( 'Add rel="nofollow" to pre-existings links' ),
						'defaultvalue' => $default_values['autolink_post_nofollow_exist'],
					),
				'autolink_post_nofollow_explicit' => array(
						'label' => '',
						'type' => 'checkbox',
						'note' => $this->T_( 'Add rel="nofollow" to explicit links' ),
						'defaultvalue' => $default_values['autolink_post_nofollow_explicit'],
					),
				'autolink_post_nofollow_auto' => array(
						'label' => '',
						'type' => 'checkbox',
						'note' => $this->T_( 'Add rel="nofollow" to auto-links' ),
						'defaultvalue' => $default_values['autolink_post_nofollow_auto'],
					),
				// No follow in comments
				'autolink_comment_nofollow_exist' => array(
						'label' => T_( 'No follow in comments' ),
						'type' => 'checkbox',
						'note' => $this->T_( 'Add rel="nofollow" to pre-existings links' ),
						'defaultvalue' => $default_values['autolink_comment_nofollow_exist'],
					),
				'autolink_comment_nofollow_explicit' => array(
						'label' => '',
						'type' => 'checkbox',
						'note' => $this->T_( 'Add rel="nofollow" to explicit links' ),
						'defaultvalue' => $default_values['autolink_comment_nofollow_explicit'],
					),
				'autolink_comment_nofollow_auto' => array(
						'label' => '',
						'type' => 'checkbox',
						'note' => $this->T_( 'Add rel="nofollow" to auto-links' ),
						'defaultvalue' => $default_values['autolink_comment_nofollow_auto'],
					),
			)
		);
	}


	/**
	 * Lazy load global definitions array
	 *
	 * @param Blog
	 */
	function load_link_array( $Blog )
	{
		global $plugins_path;

		if( !isset($this->link_array[0]) )
		{	// global defs NOT already loaded
			$this->link_array[0] = array();

			if( $this->Settings->get( 'autolink_defs_default' ) )
			{	// Load defaults:
				$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.default.txt', 0 );
			}
			if( $this->Settings->get( 'autolink_defs_local' ) )
			{	// Load local user defintions:
				$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.local.txt', 0 );
			}
			$text = $this->Settings->get( 'autolink_defs_db', 0 );
			if( !empty($text) )
			{	// Load local user defintions:
				$this->read_textfield( $text, 0 );
			}
		}

		// load defs for current blog:
		$coll_ID = $Blog->ID;
		if( !isset($this->link_array[$coll_ID]) )
		{	// This blog is not loaded yet:
			$this->link_array[$coll_ID] = array();
			$text = $this->get_coll_setting( 'autolink_defs_coll_db', $Blog );
			if( !empty($text) )
			{	// Load local user defintions:
				$this->read_textfield( $text, $coll_ID );
			}
		}

		// Prepare working link array:
		$this->replacement_link_array = array_merge( $this->link_array[0], $this->link_array[$coll_ID] );
	}


	/**
 	 * Load contents of one specific CSV file
	 *
	 * @param string $filename
	 */
	function read_csv_file( $filename, $coll_ID )
	{
		if( ! $handle = @fopen( $filename, 'r') )
		{	// File could not be opened:
			return;
		}

		while( ($data = fgetcsv($handle, 1000, ';', '"')) !== false )
		{
			$this->read_line( $data, $coll_ID );
		}

		fclose($handle);
	}


	/**
 	 * Load contents of one large textfield to be treated as CSV
 	 *
 	 * Note: This method is probably not well suited for very large lists.
	 *
	 * @param string $filename
	 */
	function read_textfield( $text, $coll_ID )
	{
		// split into lines:
		$lines = preg_split( '#\r|\n#', $text );

		foreach( $lines as $line )
		{
			// CSV style decoding in memory:
			// $keywords = preg_split( "/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|[\s,]+/", "textline with, commas and \"quoted text\" inserted", 0, PREG_SPLIT_DELIM_CAPTURE );
			$data = explode( ';', $line );
			$this->read_line( $data, $coll_ID );
		}
	}


	/**
	 * read line
	 *
	 * @param exploded $data array
	 */
	function read_line( $data, $coll_ID )
	{
		if( empty( $data[0] ) )
		{	// Skip empty and comment lines
			return;
		}

		$word = $data[0];
		$url = isset( $data[3] ) ? $data[3] : NULL;
		if( $url == '-' || empty( $url ) )
		{	// Remove URL (useful to remove some defs on a specific site):
			unset( $this->link_array[0][$word] );
			unset( $this->link_array[$coll_ID][$word] );
		}
		else
		{
			$this->link_array[$coll_ID][$word] = array( $data[1], $url );
		}
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];
		$Item = & $params['Item'];
		/**
		 * @var Blog
		 */
		$item_Blog = $params['Item']->get_Blog();

		// Define the setting names depending on what is rendering now
		if( !empty( $params['Comment'] ) )
		{	// Comment is rendering
			$this->setting_nofollow_exist = 'autolink_comment_nofollow_exist';
			$this->setting_nofollow_explicit = 'autolink_comment_nofollow_explicit';
			$this->setting_nofollow_auto = 'autolink_comment_nofollow_auto';
		}
		else
		{	// Item is rendering
			$this->setting_nofollow_exist = 'autolink_post_nofollow_exist';
			$this->setting_nofollow_explicit = 'autolink_post_nofollow_explicit';
			$this->setting_nofollow_auto = 'autolink_post_nofollow_auto';
		}

		// Prepare existing links
		$content = $this->prepare_existing_links( $content, $item_Blog );

		// reset already linked usernames
		$this->already_linked_usernames = array();
		if( !empty( $item_Blog ) && $this->get_coll_setting( 'autolink_username', $item_Blog ) )
		{	// Replace @usernames with user identity link
			$content = replace_content_outcode( '#@([A-Za-z0-9_.]+)#i', '@', $content, array( $this, 'replace_usernames' ) );
		}

		// load global defs
		$this->load_link_array( $item_Blog );

		// reset already linked:
		$this->already_linked_array = array();
		if( preg_match_all( '|[\'"](http://[^\'"]+)|i', $content, $matches ) )
		{	// There are existing links:
			$this->already_linked_array = $matches[1];
		}

		$link_attrs = '';
		if( !empty( $item_Blog ) && $this->get_coll_setting( $this->setting_nofollow_explicit, $item_Blog ) )
		{	// Add attribute rel="nofollow" for auto-links
			$link_attrs .= ' rel="nofollow"';
		}

		if( $this->Settings->get( 'autolink_urls' ) )
		{	// First, make the URLs clickable:
			$content = make_clickable( $content, '&amp;', 'make_clickable_callback', $link_attrs );
		}

		if( !empty( $this->replacement_link_array ) )
		{	// Make the desired remaining terms/definitions clickable:
			$content = make_clickable( $content, '&amp;', array( $this, 'make_clickable_callback' ), $link_attrs );
		}

		return true;
	}


	function FilterCommentContent( & $params )
	{
		$Comment = & $params['Comment'];
		$comment_Item = & $Comment->get_Item();
		$item_Blog = & $comment_Item->get_Blog();
		if( in_array( $this->code, $Comment->get_renderers_validated() ) )
		{ // Always allow rendering for comment
			$render_params = array_merge( array( 'data' => & $Comment->content, 'Item' => & $comment_Item ), $params );
			$this->RenderItemAsHtml( $render_params );
		}
		return false;
	}


	/**
	 * Callback function for {@link make_clickable()}.
	 *
	 * @param string Text
	 * @param string Url delimeter
	 * @return string The clickable text.
	 */
	function make_clickable_callback( $text, $moredelim = '&amp;' )
	{
		global $evo_charset;

		$regexp_modifier = '';
		if( $evo_charset == 'utf-8' )
		{ // Add this modifier to work with UTF-8 strings correctly
			$regexp_modifier = 'u';
		}

		// Previous word in lower case format
		$this->previous_lword = null;
		// Previous word was already used/converted to a link
		$this->previous_used = false;

		// Optimization: Check if the text contains words from the replacement links strings, and call replace callback only if there is at least one word which needs to be replaced.
		$text_words = explode( ' ', evo_strtolower( $text ) );
		foreach( $text_words as $text_word )
		{ // Trim the signs [({/ from start and the signs ])}/.,:;!? from end of each word
			$clear_word = preg_replace( '#^[\[\({/]?([@\p{L}0-9_\-\.]{3,})[\.,:;!\?\]\)}/]?$#i', '$1', $text_word );
			if( $clear_word != $text_word )
			{ // Append a clear word to array if word has the punctuation signs
				$text_words[] = $clear_word;
			}
		}
		// Check if a content has at least one definition to make an url from word
		$text_contains_replacement = ( count( array_intersect( $text_words, array_keys( $this->replacement_link_array ) ) ) > 0 );
		if( $text_contains_replacement )
		{ // Find word with 3 characters at least:
			$text = preg_replace_callback( '#(^|\s|[(),;\[{/])([@\p{L}0-9_\-\.]{3,})([\.,:;!\?\]\)}/]?)#i'.$regexp_modifier, array( & $this, 'replace_callback' ), $text );
		}

		// Cleanup words to be deleted:
		$text = preg_replace( '/[@\p{L}0-9_\-]+\s*==!#DEL#!==/i'.$regexp_modifier, '', $text );

		return $text;
	}


	/**
	 * This is the 2nd level of callback!!
	 *
	 * @param array The matches of regexp:
	 *     1 => punctuation signs before word
	 *     2 => a clear word without punctuation signs
	 *     3 => punctuation signs after word
	 */
	function replace_callback( $matches )
	{
		global $Blog;

		$link_attrs = '';
		if( !empty( $Blog ) && $this->get_coll_setting( $this->setting_nofollow_auto, $Blog ) )
		{	// Add attribute rel="nofollow" for auto-links
			$link_attrs .= ' rel="nofollow"';
		}

		$before_word = $matches[1];
		$word = $matches[2];
		$after_word = $matches[3];
		if( substr( $word, -1 ) == '.' )
		{ // If word has a dot in the end
			$word = substr( $word, 0, -1 );
			$after_word = '.'.$after_word;
		}
		$lword = evo_strtolower( $word );
		$r = $before_word.$word.$after_word;

		if( isset( $this->replacement_link_array[ $lword ] ) )
		{ // There is an autolink definition with the current word
			// An optional previous required word (allows to create groups of 2 words)
			$previous = $this->replacement_link_array[ $lword ][0];
			// Url for current word
			$url = 'http://'.$this->replacement_link_array[ $lword ][1];

			if( in_array( $url, $this->already_linked_array ) || in_array( $lword, $this->already_linked_usernames ) )
			{ // Do not repeat link to same destination:
				// pre_dump( 'already linked:'. $url );
				// save previous word in original and lower case format with the after word signs
				$this->previous_word = $word.$after_word;
				$this->previous_lword = $lword.$after_word;
				$this->previous_used = false;
				return $r;
			}

			if( !empty( $previous ) )
			{ // This definitions is a group of two word separated with space
				if( $this->previous_used || ( $this->previous_lword != $previous ) )
				{ // We do not have the required previous word or it was already used to another autolink definition
					// pre_dump( 'previous word does not match', $this->previous_lword, $previous );
					// save previous word in original and lower case format with the after word signs
					$this->previous_word = $word.$after_word;
					$this->previous_lword = $lword.$after_word;
					$this->previous_used = false;
					return $r;
				}
				$r = '==!#DEL#!==<a href="'.$url.'"'.$link_attrs.'>'.$this->previous_word.' '.$word.'</a>'.$after_word;
			}
			else
			{ // Single word
				$r = $before_word.'<a href="'.$url.'"'.$link_attrs.'>'.$word.'</a>'.$after_word;
			}

			// Make sure we don't link to same destination twice in the same text/post:
			$this->already_linked_array[] = $url;
			// Mark that the previous word was already converted to a link
			$this->previous_used = true;
		}
		else
		{ // Mark that the previous word was NOT converted to a link
			$this->previous_used = false;
		}

		// save previous word in original and lower case format with the after word signs
		// Note: after_word signs are important to be saved because in case of autlink definitions with two words the first word must have exact matching at the end!
		$this->previous_word = $word.$after_word;
		$this->previous_lword = $lword.$after_word;

		return $r;
	}


	/**
	 * Prepare existing links
	 *
	 * @param string Text
	 * @param object Blog
	 * @return string Prepared text
	 */
	function prepare_existing_links( $text, $Blog )
	{
		if( !empty( $Blog ) && $this->get_coll_setting( $this->setting_nofollow_exist, $Blog ) )
		{	// Add attribute rel="nofollow" for preexisting links
			// Remove all existing attributes "rel" from tag <a>
			$text = preg_replace( '#<a([^>]*) rel="([^"]+?)"([^>]*)>#is', '<a$1$3>', $text );
			// Add rel="nofollow"
			$text = preg_replace( '#(<a[^>]+?)>#is', '$1 rel="nofollow">', $text );
		}

		return $text;
	}


	/**
	 * Replace @usernames with link to profile page
	 *
	 * @param string Content
	 * @param array Search list
	 * @param array Replace list
	 * @return string Content
	 */
	function replace_usernames( $content, $search_list, $replace_list )
	{
		global $Blog;

		if( empty( $Blog ) )
		{	// No Blog, Exit here
			return $content;
		}

		if( preg_match_all( $search_list, $content, $user_matches ) )
		{
			$blog_url = $Blog->gen_blogurl();

			// Add this for rel attribute in order to activate bubbletips on usernames
			$link_attr_rel = 'bubbletip_user_%user_ID%';

			if( $this->get_coll_setting( $this->setting_nofollow_auto, $Blog ) )
			{	// Add attribute rel="nofollow" for auto-links
				$link_attr_rel .= ' nofollow';
			}
			$link_attrs = ' rel="'.$link_attr_rel.'"';

			if( !empty( $user_matches[1] ) )
			{
				$UserCache = & get_UserCache();
				foreach( $user_matches[1] as $u => $username )
				{
					if( in_array( $username, $this->already_linked_usernames ) )
					{	// Skip this username, it was already linked before
						continue;
					}

					if( $User = & $UserCache->get_by_login( $username ) )
					{	// Replace @usernames
						$user_link_attrs = str_replace( '%user_ID%', $User->ID, $link_attrs );
						$user_link = '<a href="'.url_add_param( $blog_url, 'disp=user&amp;user_ID='.$User->ID ).'"'.$user_link_attrs.'>'.$user_matches[0][ $u ].'</a>';
						$content = preg_replace( '#'.$user_matches[0][ $u ].'#', $user_link, $content, 1 );
						$this->already_linked_usernames[] = $user_matches[1][ $u ];
					}
				}
			}
		}

		return $content;
	}
}

?>