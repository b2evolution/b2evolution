<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
	var $priority = 63;
	var $version = '7.1.0';
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
	 * Array of tags used for current collection
	 *
	 * @var array
	 */
	var $tags_array = NULL;

	var $already_linked_tags = NULL;

	/**
	 * Current Blog
	 *
	 * @var object
	 */
	var $current_Blog = NULL;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Make URLs and specific terms/defintions clickable');
		$this->long_desc = T_('This renderer automatically creates links for you. URLs can be made clickable automatically. Specific and frequently used terms can be configured to be automatically linked to a definition URL.');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		return array(
				'autolink' => array(
						'label' => T_('Create auto-links for'),
						'type' => 'checklist',
						'options' => array(
							array( 'defs_default', sprintf( T_('Definitions as defined in %s'), '<code>definitions.default.txt</code>' ), 1 ),
							array( 'defs_local', sprintf( T_('Definitions as defined in %s'), '<code>definitions.local.txt</code>' ), 0 ),
						)
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
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_custom_setting_definitions()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
			'autolink_urls' => array(
					'label' => $this->T_('Autolink URLs'),
					'type' => 'checkbox',
					'note' => sprintf( $this->T_('Find URLs that match %s, %s or %s'), '<code>http://*</code>', '<code>https://*</code>', '<code>www.*.*</code>' ),
					'defaultvalue' => 1,
				),
			'autolink_emails' => array(
					'label' => $this->T_('Autolink email addresses'),
					'type' => 'checkbox',
					'note' => sprintf( $this->T_('Find addresses that match %s or %s'), '<code>mailto:</code>', '<code>*@*.*</code>' ),
					'defaultvalue' => 1,
				),
			'autolink_username' => array(
					'label' => T_( 'Autolink usernames' ),
					'type' => 'checkbox',
					// TRANS: the user can type in any username after "@" but it's typically only lowercase letters and no spaces.
					'note' => $this->T_( '@username will link to the user profile page' ),
					'defaultvalue' => 0,
				),
			'autolink_defs_coll_db' => array(
					'label' => T_( 'Custom autolink definitions' ),
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
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_values = array(
				'autolink_tag'                       => 0,
				'autolink_post_nofollow_auto'        => 0,
				'autolink_comment_nofollow_auto'     => 0,
			);

		// set params to allow rendering for comments by default
		$default_params = array_merge( $params, array( 'default_comment_rendering' => 'stealth' ) );
		$coll_params = parent::get_coll_setting_definitions( $default_params );

		if( isset( $coll_params['autolink_defs_coll_db'] ) )
		{	// Store this param in order to put under "Autolink tags":
			$coll_param_autolink_defs_coll_db = $coll_params['autolink_defs_coll_db'];
			unset( $coll_params['autolink_defs_coll_db'] );
		}

		$coll_params['autolink_tag'] = array(
				'label' => T_('Autolink tags'),
				'type' => 'checkbox',
				'note' => $this->T_( 'Find text matching tags of the current collection and autolink them to the tag page in the current collection' ),
				'defaultvalue' => $default_values['autolink_tag'],
			);

		if( isset( $coll_param_autolink_defs_coll_db ) )
		{	// Put the setting under "Autolink tags":
			$coll_params['autolink_defs_coll_db'] = $coll_param_autolink_defs_coll_db;
		}

		return array_merge( $coll_params,
			array(
				// No follow in posts
				'autolink_post_nofollow' => array(
						'label' => T_('No follow in posts'),
						'type' => 'checklist',
						'options' => array(
							array( 'auto', $this->T_('Add rel="nofollow" to links from autolink definitions'), $default_values['autolink_post_nofollow_auto'] ),
						)
					),
				// No follow in comments
				'autolink_comment_nofollow' => array(
						'label' => T_('No follow in comments'),
						'type' => 'checklist',
						'options' => array(
							array( 'auto', $this->T_('Add rel="nofollow" to links from autolink definitions'), $default_values['autolink_comment_nofollow_auto'] ),
						)
					),
			)
		);
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
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'stealth' ) );
		return array_merge( parent::get_msg_setting_definitions( $default_params ),
			array(
				// No follow settings in messages:
				'autolink_nofollow' => array(
						'label' => T_('No follow in messages'),
						'type' => 'checklist',
						'options' => array(
							array( 'auto', $this->T_('Add rel="nofollow" to auto-links'), 0 ),
						)
					),
			)
		);
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'stealth' ) );
		return array_merge( parent::get_email_setting_definitions( $default_params ),
			array(
				// No follow settings in email campaigns:
				'autolink_nofollow' => array(
						'label' => T_('No follow in messages'),
						'type' => 'checklist',
						'options' => array(
							array( 'auto', $this->T_('Add rel="nofollow" to auto-links'), 0 ),
						)
					),
			)
		);
	}


	/**
	 * Define here default shared settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_shared_setting_definitions( & $params )
	{
		// set params to allow rendering for shared container widgets by default:
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'stealth' ) );
		return array_merge( parent::get_shared_setting_definitions( $default_params ),
			array(
				// No follow settings in messages:
				'autolink_nofollow' => array(
						'label' => T_('No follow in messages'),
						'type' => 'checklist',
						'options' => array(
							array( 'auto', $this->T_('Add rel="nofollow" to auto-links'), 0 ),
						)
					),
			)
		);
	}


	/**
	 * Lazy load global definitions array
	 *
	 * @param object Blog
	 */
	function load_link_array( $Blog )
	{
		global $plugins_path;

		if( !isset($this->link_array[0]) )
		{	// global defs NOT already loaded
			$this->link_array[0] = array();

			if( $this->get_checklist_setting( 'autolink', 'defs_default' ) )
			{	// Load defaults:
				$this->read_csv_file( $plugins_path.'autolinks_plugin/definitions.default.txt', 0 );
			}
			if( $this->get_checklist_setting( 'autolink', 'defs_local' ) )
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
		$coll_ID = !empty( $Blog ) ? $Blog->ID : 0;
		if( !isset($this->link_array[$coll_ID]) )
		{	// This blog is not loaded yet:
			$this->link_array[$coll_ID] = array();
		}
		$text = $this->setting_autolink_defs_coll_db;
		if( ! empty( $text ) )
		{	// Load local user defintions:
			$this->read_textfield( $text, $coll_ID );
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
		{ // Remove URL (useful to remove some defs on a specific site):
			unset( $this->link_array[0][$word] );
			unset( $this->link_array[$coll_ID][$word] );
		}
		else
		{
			if( ! isset( $this->link_array[ $coll_ID ][ $word ] ) )
			{ // Initialize array only first time to store several previous words for each word:
				$this->link_array[ $coll_ID ][ $word ] = array();
			}
			$this->link_array[ $coll_ID ][ $word ][ $data[1] ] = $url;
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

		// Get collection from given params (also it is used to build link for tag links):
		$this->current_Blog = $this->get_Blog_from_params( $params );

		// Define the setting names depending on what is rendering now
		if( !empty( $params['Comment'] ) )
		{	// Comment is rendering
			$this->setting_nofollow_auto = $this->get_checklist_setting( 'autolink_comment_nofollow', 'auto', 'coll', $this->current_Blog );
		}
		else
		{	// Item is rendering
			$this->setting_nofollow_auto = $this->get_checklist_setting( 'autolink_post_nofollow', 'auto', 'coll', $this->current_Blog );
		}

		$this->setting_autolink_defs_coll_db = $this->get_coll_setting( 'autolink_defs_coll_db', $this->current_Blog );
		$this->setting_autolink_urls = $this->get_coll_setting( 'autolink_urls', $this->current_Blog );
		$this->setting_autolink_emails = $this->get_coll_setting( 'autolink_emails', $this->current_Blog );
		$this->setting_autolink_username = $this->get_coll_setting( 'autolink_username', $this->current_Blog );
		$this->setting_autolink_tag = $this->get_coll_setting( 'autolink_tag', $this->current_Blog );

		return $this->render_content( $content, $this->current_Blog );
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

		// Message is rendering
		$this->setting_nofollow_auto = $this->get_checklist_setting( 'autolink_nofollow', 'auto', 'msg' );
		$this->setting_autolink_defs_coll_db = $this->get_msg_setting( 'autolink_defs_coll_db' );
		$this->setting_autolink_urls = $this->get_msg_setting( 'autolink_urls' );
		$this->setting_autolink_emails = $this->get_msg_setting( 'autolink_emails' );
		$this->setting_autolink_username = $this->get_msg_setting( 'autolink_username' );
		$this->setting_autolink_tag = false;

		return $this->render_content( $content );
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

		// Email is rendering
		$this->setting_nofollow_auto = $this->get_checklist_setting( 'autolink_nofollow', 'auto', 'email' );
		$this->setting_autolink_defs_coll_db = $this->get_email_setting( 'autolink_defs_coll_db' );
		$this->setting_autolink_urls = $this->get_email_setting( 'autolink_urls' );
		$this->setting_autolink_emails = $this->get_email_setting( 'autolink_emails' );
		$this->setting_autolink_username = $this->get_email_setting( 'autolink_username' );
		$this->setting_autolink_tag = false;

		return $this->render_content( $content );
	}


	/**
	 * Render content of Item, Comment, Message
	 *
	 * @param string Content
	 * @param object Blog
	 * return boolean
	 */
	function render_content( & $content, $item_Blog = NULL )
	{
		// Reset already linked usernames:
		$this->already_linked_usernames = array();

		// Load global defs:
		$this->load_link_array( $item_Blog );

		// Load all tags of current collection:
		$this->load_tags_array( $item_Blog, $content );
		// Reset already linked tags:
		$this->already_linked_tags = array();

		// reset already linked:
		$this->already_linked_array = array();
		if( preg_match_all( '|[\'"](http://[^\'"]+)|i', $content, $matches ) )
		{	// There are existing links:
			$this->already_linked_array = $matches[1];
		}

		if( $this->setting_autolink_urls )
		{	// Make the URLs clickable:
			$content = make_clickable( $content, '&amp;', array( $this, 'make_clickable_callback_urls' ), '', true );
		}

		if( $this->setting_autolink_emails )
		{	// Make the email addresses clickable:
			$content = make_clickable( $content, '&amp;', array( $this, 'make_clickable_callback_emails' ), '', true );
		}

		// Make the desired remaining terms/definitions, usernames or tags clickable:
		$content = make_clickable( $content, '&amp;', array( $this, 'make_clickable_callback_definitions' ), '', true );

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
	 * Callback function to make URLs clickable
	 *
	 * @param string Text
	 * @param string Url delimeter
	 * @param string Additional attributes for tag <a>
	 * @return string The clickable text.
	 */
	function make_clickable_callback_urls( $text, $moredelim = '&amp;', $additional_attrs = '' )
	{
		if( ! empty( $additional_attrs ) )
		{
			$additional_attrs = ' '.trim( $additional_attrs );
		}

		$pattern_domain = '([\p{L}0-9\-]+\.[\p{L}0-9\-.\~]+)'; // a domain name (not very strict)
		$text = preg_replace(
			/* Tblue> I removed the double quotes from the first RegExp because
						it made URLs in tag attributes clickable.
						See http://forums.b2evolution.net/viewtopic.php?p=92073 */
			array( '#(^|[\s>\(]|\[url=)(https?)://([^"<>{}\s]+[^".,:;!\?<>{}\s\]\)])#i',
				'#(^|[\s>\(]|\[url=)www\.'.$pattern_domain.'([^"<>{}\s]*[^".,:;!\?\s\]\)])#i' ),
			array( '$1<a href="$2://$3"'.$additional_attrs.'>$2://$3</a>',
				'$1<a href="http://www.$2$3$4"'.$additional_attrs.'>www.$2$3$4</a>' ),
			$text );

		return $text;
	}


	/**
	 * Callback function to make email addresses clickable
	 *
	 * @param string Text
	 * @param string Url delimeter
	 * @param string Additional attributes for tag <a>
	 * @return string The clickable text.
	 */
	function make_clickable_callback_emails( $text, $moredelim = '&amp;', $additional_attrs = '' )
	{
		if( ! empty( $additional_attrs ) )
		{
			$additional_attrs = ' '.trim( $additional_attrs );
		}

		$pattern_domain = '([\p{L}0-9\-]+\.[\p{L}0-9\-.\~]+)'; // a domain name (not very strict)
		$text = preg_replace(
			/* Tblue> I removed the double quotes from the first RegExp because
						it made URLs in tag attributes clickable.
						See http://forums.b2evolution.net/viewtopic.php?p=92073 */
			array( '#(^|[\s>\(]|\[url=)mailto://([^"<>{}\s]+[^".,:;!\?<>{}\s\]\)])#i',
				'#(^|[\s>\(]|\[url=)([a-z0-9\-_.]+?)@'.$pattern_domain.'([^".,:;!\?&<\s\]\)]+)#i' ),
			array( '$1<a href="mailto://$2"'.$additional_attrs.'>mailto://$2</a>',
				'$1<a href="mailto:$2@$3$4"'.$additional_attrs.'>$2@$3$4</a>' ),
			$text );

		return $text;
	}


	/**
	 * Callback function to make terms/definitions, usernames or tags clickable
	 *
	 * @param string Text
	 * @param string Url delimeter
	 * @return string The clickable text.
	 */
	function make_clickable_callback_definitions( $text, $moredelim = '&amp;' )
	{
		global $evo_charset;

		if( ! empty( $this->replacement_link_array ) )
		{	// Make the desired remaining terms/definitions clickable:
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
			$text_words = preg_split( '/\s/', utf8_strtolower( $text ) );
			foreach( $text_words as $text_word )
			{ // Trim the signs [({/ from start and the signs ])}/.,:;!? from end of each word
				$clear_word = preg_replace( '#^[\[\({/]?([@\p{L}0-9_\-]{3,})[\.,:;!\?\]\)}/]?$#i', '$1', $text_word );
				if( $clear_word != $text_word )
				{ // Append a clear word to array if word has the punctuation signs
					$text_words[] = $clear_word;
				}
			}
			// Check if a content has at least one definition to make an url from word
			$text_contains_replacement = ( count( array_intersect( $text_words, array_keys( $this->replacement_link_array ) ) ) > 0 );
			if( $text_contains_replacement )
			{ // Find word with 3 characters at least:
				$text = preg_replace_callback( '#(^|\s|[(),;\'\"\[{/])([@\p{L}0-9_\-\.]{3,})([\.,:;!\'\"\?\]\)}/]?)#i'.$regexp_modifier, array( & $this, 'replace_callback' ), $text );
			}

			// Cleanup words to be deleted:
			$text = preg_replace( '/[@\p{L}0-9_\-]+\s*==!#DEL#!==/i'.$regexp_modifier, '', $text );
		}

		// Replace @usernames with user identity link:
		$text = replace_content_outcode( '#@([a-z0-9_.\-]+)#i', '@', $text, array( $this, 'replace_usernames' ) );

		// Make tag names clickable:
		$text = $this->replace_tags( $text );

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
		global $disp, $Item;

		$link_attrs = '';
		if( $this->setting_nofollow_auto )
		{	// Add attribute rel="nofollow" for auto-links:
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
		$lword = utf8_strtolower( $word );
		$r = $before_word.$word.$after_word;

		if( isset( $this->replacement_link_array[ $lword ] ) )
		{ // There is an autolink definition with the current word
			if( ! empty( $this->previous_lword ) && isset( $this->replacement_link_array[ $lword ][ $this->previous_lword ] ) )
			{ // Set an previous word and url from config array:
				// An optional previous required word (allows to create groups of 2 words)
				$previous = $this->previous_lword;
				// Url for current word
				$url = $this->replacement_link_array[ $lword ][ $this->previous_lword ];
			}
			else
			{ // No previous word, it is a single word
				foreach( $this->replacement_link_array[ $lword ] as $previous => $url )
				{ // Initialize an optional previous required word and url as first of the current word
					break;
				}
			}

			if( ! preg_match( '#(^|[a-z]+:)//#', $url ) )
			{	// Use default URL scheme if it is not defined by config:
				$url = 'http://'.$url;
			}

			if( in_array( $url, $this->already_linked_array ) || in_array( $lword, $this->already_linked_usernames ) )
			{ // Do not repeat link to same destination:
				// pre_dump( 'already linked:'. $url );
				// save previous word in original and lower case format with the after word signs
				$this->previous_word = $word.$after_word;
				$this->previous_lword = $lword.$after_word;
				$this->previous_used = false;
				return $r;
			}

			if( ( $disp == 'single' || $disp == 'page' ) &&
			    isset( $Item ) &&
			    $Item->get_permanent_url() == $url )
			{	// Do not make a link to same permalink URL of the current Item:
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
	 * Replace @usernames with link to profile page
	 *
	 * @param string Content
	 * @param array Search list
	 * @param array Replace list
	 * @return string Content
	 */
	function replace_usernames( $content, $search_list, $replace_list )
	{
		if( empty( $this->setting_autolink_username ) )
		{	// No data to correct username linking, Exit here:
			return $content;
		}

		if( preg_match_all( $search_list, $content, $user_matches ) )
		{
			// Add this for rel attribute in order to activate bubbletips on usernames
			$link_attrs = ' rel="bubbletip_user_%user_ID%"';
			$link_attrs .= ' class="user"';

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
						$user_link = '<a href="'.$User->get_userpage_url().'"'.$user_link_attrs.'>'.$user_matches[0][ $u ].'</a>';
						$content = preg_replace( '#'.preg_quote( $user_matches[0][ $u ] ).'#', $user_link, $content, 1 );
						$this->already_linked_usernames[] = $user_matches[1][ $u ];
					}
				}
			}
		}

		return $content;
	}


	/**
	 * Load tags array of collection
	 *
	 * @param object Blog
	 */
	function load_tags_array( $Blog )
	{
		if( empty( $this->setting_autolink_tag ) )
		{	// Don't load tags because it is not required by current settings:
			return;
		}

		if( is_array( $this->tags_array ) )
		{	// The tags array is already initialized, Don't do this twice, Exit here:
			return;
		}

		// Get all tags from published posts of the requested collection:
		$coll_tags = get_tags( $Blog->ID );

		$this->tags_array = array();
		foreach( $coll_tags as $coll_tag )
		{
			$this->tags_array[] = $coll_tag->tag_name;
		}
	}


	/**
	 * Replace tag names with link to filter posts by the tag
	 *
	 * @param string Content
	 * @return string Content
	 */
	function replace_tags( $content )
	{
		if( empty( $this->setting_autolink_tag ) || empty( $this->tags_array ) || empty( $this->current_Blog ) )
		{	// No data to correct tag linking, Exit here:
			return $content;
		}

		$new_linked_tags = array();
		$tag_search_patterns = array();
		$tag_replace_strings = array();
		foreach( $this->tags_array as $tag_name )
		{
			if( in_array( $tag_name, $this->already_linked_tags ) )
			{	// Skip this tag because it has been already linked before:
				continue;
			}
			if( stristr( $content, $tag_name ) !== false )
			{	// Replace tag name with its link if it is found in text:
				$tag_search_patterns[] = '#(^|\W)('.preg_quote( $tag_name, '#' ).')(\W|$)#iu';
				$tag_replace_strings[] = '$1'.$this->current_Blog->get_tag_link( $tag_name, "$2" ).'$3';
				// Mark this tag as linked and don't link it twice:
				$new_linked_tags[] = $tag_name;
			}
		}

		if( count( $tag_search_patterns ) )
		{	// Do replacement if at least one tag is found in content:
			if( stristr( $content, '<a' ) !== false )
			{	// Don't link tags in body of already existing links:
				$content = callback_on_non_matching_blocks( $content, '~<a[^>]*>.*?</a>~is',
					'replace_content', array( $tag_search_patterns, $tag_replace_strings, 'preg', 1 ) );
			}
			else
			{	// Replace in whole content:
				$content = replace_content( $content, $tag_search_patterns, $tag_replace_strings, 'preg', 1 );
			}
		}

		foreach( $new_linked_tags as $n => $new_linked_tag )
		{
			if( stristr( $content, $new_linked_tag.'</a>' ) === false )
			{	// This tag was not linked really in this call, Skip it:
				// It may happens when one tag is a substring of other tag, Example:
				//     - First tag is "long tag"
				//     - Second tag is "test long tag name"
				//     - $text = "1 test long tag name 2"
				//     So we should not mark the second as linked because only the first tag is linked only first time,
				//     and we should keep the second tag for other strings.
				unset( $new_linked_tags[ $n ] );
			}
		}

		if( count( $new_linked_tags ) )
		{	// Append new linked tags to skip them in next times:
			$this->already_linked_tags = array_merge( $this->already_linked_tags, $new_linked_tags );
		}

		return $content;
	}
}

?>