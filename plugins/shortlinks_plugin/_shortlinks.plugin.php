<?php
/**
 * This file implements the Short Links plugin for b2evolution
 *
 * Creates wiki links
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class shortlinks_plugin extends Plugin
{
	var $code = 'b2evWiLi';
	var $name = 'Short Links';
	var $priority = 35;
	var $version = '7.0.2';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'wiki-links-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Wiki Links converter');
		$this->long_desc = T_('You can create links with [[CamelCase]] or ((CamelCase)) which will try to link to the category or the post with the slug "camel-case". See manual for more.');
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
			'link_types' => array(
				'label' => T_('Link types to allow'),
				'type' => 'checklist',
				'options' => array(
						array( 'absolute_urls',         sprintf( $this->T_('Absolute URLs (starting with %s or %s) in brackets'), '<code>http://</code>, <code>https://</code>, <code>mailto://</code>', '<code>//</code>' ), 1 ),
						array( 'abs_target_blank',      $this->T_('Open in new tab').' (<code>target="_blank"</code>)', 1, NULL, NULL, NULL, NULL, NULL, array( 'style' => 'margin-left:20px' ) ),
						array( 'relative_urls',         sprintf( $this->T_('Relative URLs (starting with %s followed by a letter or digit) in brackets'), '<code>/</code>' ), 0 ),
						array( 'anchor',                sprintf( $this->T_('Current page anchor URLs (starting with %s) in brackets'), '<code>#</code>' ), 1 ),
						array( 'cat_slugs',             $this->T_('Category slugs in brackets'), 1 ),
						array( 'item_slugs',            $this->T_('Item slugs in brackets'), 1 ),
						array( 'item_id',               $this->T_('Item ID in brackets'), 1 ),
						array( 'cat_without_brackets',  $this->T_('WikiWords without brackets matching category slugs'), 0 ),
						array( 'item_without_brackets', $this->T_('WikiWords without brackets matching item slugs'), 0 ),
					),
				),
			'optimize' => array(
				'label' => T_('Optimize URLs'),
				'type' => 'checklist',
				'options' => array(
						array( 'absolute_urls', $this->T_('If an absolute URL references a collection on this system, try to identity and keep just the slug '), 1 ),
						array( 'relative_urls', $this->T_('If a relative URL references a collection on this system, try to identity and keep just the slug'), 1 ),
					),
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
		$default_values = array(
				'default_post_rendering' => 'opt-out'
			);

		$default_params = array_merge( $params, $default_values );

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
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'opt-out' ) );
		return parent::get_email_setting_definitions( $default_params );
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
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'opt-out' ) );
		return parent::get_shared_setting_definitions( $default_params );
	}


	/**
	 * Event handler: Called when displaying an item/post's content as HTML.
	 *
	 * This is different from {@link RenderItemAsHtml()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @param array Associative array of parameters
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Replace the create post links with simple text if current user has no perm to create a post:
		$content = replace_content_outcode( '#<a[^>]+href="([^"]+)"[^>]+data-function="create_post" data-coll="(\d+)"[^>]*>(.+?)</a>#i', array( $this, 'callback_replace_post_links' ), $content, 'replace_content_callback' );

		return true;
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

		// Get collection from given params:
		$setting_Blog = $this->get_Blog_from_params( $params );

		$this->link_types = $this->get_coll_setting( 'link_types', $setting_Blog );

		return $this->render_content( $content );
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

		$this->link_types = $this->get_msg_setting( 'link_types' );

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

		$this->render_type = 'email';
		$this->link_types = $this->get_email_setting( 'link_types' );

		return $this->render_content( $content );
	}


	/**
	 * Render content of Item, Comment, Message
	 *
	 * @todo get rid of global $blog
	 *
	 * @param string Content
	 * @return boolean
	 */
	function render_content( & $content )
	{
		global $admin_url, $blog, $evo_charset, $post_ID;

		// Add regexp modifier 'u' to work with UTF-8 strings correctly:
		$regexp_modifier = ( $evo_charset == 'utf-8' ) ? 'u' : '';

		// -------- ABSOLUTE BRACKETED URLS -------- :
		if( ! empty( $this->link_types['absolute_urls'] ) )
		{	// If it is allowed by plugin setting
			$search_urls = '*
				( \[\[ | \(\( )                    # Lookbehind for (( or [[
				( (https?://|mailto://|//)[^<>{}\s\]]+ ) # URL
				( \s \.[a-z0-9_\-\.]+ )?           # Style classes started and separated with dot (Optional)
				( \s _[a-z0-9_\-]+ )?              # Link target started with _ (Optional)
				( \s [^\n\r]+? )?                  # Custom link text instead of URL (Optional)
				( \]\] | \)\) )                    # Lookahead for )) or ]]
				*ix'; // x = extended (spaces + comments allowed)
			$content = replace_content_outcode( $search_urls, array( $this, 'callback_replace_bracketed_urls' ), $content, 'replace_content', 'preg_callback' );
		}

		// -------- RELATIVE BRACKETED URLS -------- :
		if( ! empty( $this->link_types['relative_urls'] ) )
		{	// If it is allowed by plugin setting
			$search_urls = '*
				( \[\[ | \(\( )                    # Lookbehind for (( or [[
				( (/)[^/][^<>{}\s\]]+ ) # URL
				( \s \.[a-z0-9_\-\.]+ )?           # Style classes started and separated with dot (Optional)
				( \s _[a-z0-9_\-]+ )?              # Link target started with _ (Optional)
				( \s [^\n\r]+? )?                  # Custom link text instead of URL (Optional)
				( \]\] | \)\) )                    # Lookahead for )) or ]]
				*ix'; // x = extended (spaces + comments allowed)
			$content = replace_content_outcode( $search_urls, array( $this, 'callback_replace_bracketed_urls' ), $content, 'replace_content', 'preg_callback' );
		}

/* QUESTION: fplanque, implementation of this planned? then use make_clickable() - or remove this comment
	$ret = preg_replace("#([\n ])aim:([^,< \n\r]+)#i", "\\1<a href=\"aim:goim?screenname=\\2\\3&message=Hello\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])icq:([^,< \n\r]+)#i", "\\1<a href=\"http://wwp.icq.com/scripts/search.dll?to=\\2\\3\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\">www.\\2.\\3\\4</a>", $ret);

	$ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^,< \n\r]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret); */

		// To use function replace_special_chars()
		load_funcs('locales/_charset.funcs.php');

		// -------- STANDALONE WIKIWORDS -------- :
		if( ! empty( $this->link_types['cat_without_brackets'] ) ||
		    ! empty( $this->link_types['item_without_brackets'] ) )
		{	// Create the links from standalone WikiWords

			$search_wikiwords = array();
			$replace_links = array();

			// STANDALONE WIKIWORDS:
			$search = '/
					(?<= \s | ^ )													# Lookbehind for whitespace
					([\p{Lu}]+[\p{Ll}0-9_]+([\p{Lu}]+[\p{L}0-9_]+)+)	# WikiWord or WikiWordLong
					(?= [\.,:;!\?] \s | \s | $ )											# Lookahead for whitespace or punctuation
				/x'.$regexp_modifier;	// x = extended (spaces + comments allowed)

			if( preg_match_all( $search, $content, $matches, PREG_SET_ORDER ) )
			{
				// Construct array of wikiwords to look up in post urltitles
				$wikiwords = array();
				foreach( $matches as $match )
				{
					// Convert the WikiWord to an urltitle
					$WikiWord = $match[0];
					$Wiki_Word = preg_replace( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '$1-$2', $WikiWord );
					$wiki_word = utf8_strtolower( $Wiki_Word );
					// echo '<br />Match: [', $WikiWord, '] -> [', $wiki_word, ']';
					$wiki_word = replace_special_chars( $wiki_word );
					$wikiwords[ $WikiWord ] = $wiki_word;
				}

				// Lookup all urltitles at once in DB and preload cache:
				if( ! empty( $this->link_types['cat_without_brackets'] ) )
				{
					$ChapterCache = & get_ChapterCache();
					$ChapterCache->load_urlname_array( $wikiwords );
				}
				if( ! empty( $this->link_types['item_without_brackets'] ) )
				{
					$ItemCache = & get_ItemCache();
					$ItemCache->load_urltitle_array( $wikiwords );
				}

				// Construct arrays for replacing wikiwords by links:
				foreach( $wikiwords as $WikiWord => $wiki_word )
				{
					// WikiWord
					$search_wikiwords[] = '/
						(?<= \s | ^ ) 						# Lookbehind for whitespace or start
						(?<! evo_shortlink_broken">)
						'.$WikiWord.'							# Specific WikiWord to replace
						(?= [\.,:;!\?] \s | \s | $ )							# Lookahead for whitespace or end of string
						/sx';	// s = dot matches newlines, x = extended (spaces + comments allowed)


					// Find matching Item or Chapter:
					if( ! empty( $this->link_types['item_without_brackets'] ) &&
					    ( $Item = & $ItemCache->get_by_urltitle( $wiki_word, false, false ) ) )
					{	// Replace WikiWord with post permanent link if item is found:
						$replace_links[] = '<a href="'.$Item->get_permanent_url().'">'.$Item->get( 'title' ).'</a>';
					}
					elseif( ! empty( $this->link_types['cat_without_brackets'] ) &&
					        ( $Chapter = & $ChapterCache->get_by_urlname( $wiki_word, false, false ) ) )
					{	// Replace WikiWord with category permanent link if Chapter is found:
						$replace_links[] = '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->get( 'name' ).'</a>';
					}
					else
					{	// Replace WikiWord with broken link if Item and Chapter are not found:
						$replace_links[] = $this->get_broken_link( $wiki_word, $WikiWord );
					}
				}
			}

			// Replace all found standalone words with links:
			$content = replace_content_outcode( $search_wikiwords, $replace_links, $content );
		}

		// -------- BRACKETED WIKIWORDS -------- :
		if( ! empty( $this->link_types['anchor'] ) ||
		    ! empty( $this->link_types['cat_slugs'] ) ||
		    ! empty( $this->link_types['item_slugs'] ) ||
		    ! empty( $this->link_types['item_id'] ) )
		{	// If it is allowed by plugin settings:
			$search_anchor_slug_itemid = ( empty( $this->link_types['anchor'] ) && empty( $this->link_types['cat_slugs'] ) && empty( $this->link_types['item_slugs'] ) ) ?
					'([0-9]+) # Only item ID' :
					'([\p{L}0-9#]+[\p{L}0-9#_\-]*) # Anything from Wikiword to WikiWordLong';
			$search = '/
					(?<= \(\( | \[\[ )            # Lookbehind for (( or [[
					'.$search_anchor_slug_itemid.'
					(?=
						( \s .*? )?                 # Custom link text instead of post or chapter title with optional style classes
						( \)\) | \]\] )             # Lookahead for )) or ]]
					)
				/x'.$regexp_modifier; // x = extended (spaces + comments allowed)
			if( preg_match_all( $search, $content, $matches, PREG_SET_ORDER ) )
			{
				// Construct array of wikiwords to look up in post urltitles
				$wikiwords = array();
				foreach( $matches as $match )
				{
					// Convert the WikiWord to an urltitle
					$WikiWord = $match[0];
					if( preg_match( '/^[\p{Ll}0-9#_\-]+$/'.$regexp_modifier, $WikiWord ) )
					{	// This WikiWord already matches a slug format
						$Wiki_Word = $WikiWord;
						$wiki_word = $Wiki_Word;
					}
					else
					{	// Convert WikiWord to slug format
						$Wiki_Word = preg_replace( array( '*([^\p{Lu}#_])([\p{Lu}#])*'.$regexp_modifier, '*([^0-9])([0-9])*'.$regexp_modifier ), '$1-$2', $WikiWord );
						$wiki_word = utf8_strtolower( $Wiki_Word );
					}
					// Remove additional params from $wiki_word, it should be cleared. We keep the params in $WikiWord and parse them below.
					$wiki_word = preg_replace( '/^([^#]+)(#.+)?$/i', '$1', $wiki_word );
					$wiki_word = replace_special_chars( $wiki_word );
					$wikiwords[ $WikiWord ] = $wiki_word;
				}

				// Lookup all urltitles at once in DB and preload cache:
				if( ! empty( $this->link_types['cat_slugs'] ) )
				{
					$ChapterCache = & get_ChapterCache();
					$ChapterCache->load_urlname_array( $wikiwords );
				}
				if( ! empty( $this->link_types['item_slugs'] ) )
				{
					$ItemCache = & get_ItemCache();
					$ItemCache->load_urltitle_array( $wikiwords );
				}

				// Replace wikiwords by links:
				foreach( $wikiwords as $WikiWord => $wiki_word )
				{
					// Initialize current wiki word which is used in callback function callback_replace_bracketed_words():
					$this->current_WikiWord = $WikiWord;
					$this->current_wiki_word = $wiki_word;

					// Fix for regexp:
					$WikiWord = str_replace( '#', '\#', preg_quote( $WikiWord ) );

					// [[WikiWord]]
					// [[WikiWord text]]
					// [[WikiWord .style.classes text]]
					// ((WikiWord))
					// ((WikiWord text))
					// ((WikiWord .style.classes text))
					$search_wikiword = '*
						( \[\[ | \(\( )          # Lookbehind for (( or [[
						'.$WikiWord.'            # Specific WikiWord to replace
						( \s \.[a-z0-9_\-\.]+ )? # Style classes started and separated with dot (Optional)
						( \s _[a-z0-9_\-]+ )?    # Link target started with _ (Optional)
						( \s .+? )?              # Custom link text instead of post/chapter title (Optional)
						( \]\] | \)\) )          # Lookahead for )) or ]]
						*isx'; // s = dot matches newlines, x = extended (spaces + comments allowed)

					$content = replace_content_outcode( $search_wikiword, array( $this, 'callback_replace_bracketed_words' ), $content, 'replace_content', 'preg_callback' );
				}
			}
		}

		return true;
	}


	/**
	 * Callback function for replace_content_outcode to render links like [[http://site.com/page.html .style.classes text]] or ((http://site.com/page.html .style.classes text))
	 *
	 * @param array Matches of regexp
	 * @return string A processed link to the requested URL
	 */
	function callback_replace_bracketed_urls( $m )
	{
		if( ! ( $m[1] == '[[' && $m[7] == ']]' ) &&
		    ! ( $m[1] == '((' && $m[7] == '))' ) )
		{	// Wrong pattern, Return original text:
			return $m[0];
		}

		// Clear custom link text:
		$custom_link_text = utf8_trim( $m[6] );

		// Clear custom link style classes:
		$custom_link_class = utf8_trim( str_replace( '.', ' ', $m[4] ) );

		if( $m[3] != '/' && ! empty( $this->link_types['abs_target_blank'] ) )
		{	// Force target to "_blank" for absolute URLs when it is defined in plugin settings:
			$custom_link_target = '_blank';
		}
		else
		{	// Use custom link target:
			$custom_link_target = utf8_trim( $m[5] );
		}

		// Build a link from bracketed URL:
		$r = '<a href="'.$m[2].'"';
		$r .= empty( $custom_link_class ) ? '' : ' class="'.$custom_link_class.'"';
		$r .= empty( $custom_link_target ) ? '' : ' target="'.$custom_link_target.'"';
		$r .= '>';
		$r .= empty( $custom_link_text ) ? $m[2] : $custom_link_text;
		$r .= '</a>';

		return $r;
	}


	/**
	 * Callback function for replace_content_outcode to render links like [[wiki-word .style.classes text]] or ((wiki-word .style.classes text))
	 *
	 * @param array Matches of regexp
	 * @return string A processed link to post/chapter URL OR a suggestion text to create new post from unfound post urltitle
	 */
	function callback_replace_bracketed_words( $m )
	{
		global $blog, $evo_charset, $admin_url;

		if( ! ( $m[1] == '[[' && $m[5] == ']]' ) &&
		    ! ( $m[1] == '((' && $m[5] == '))' ) )
		{	// Wrong pattern, Return original text:
			return $m[0];
		}

		$ItemCache = & get_ItemCache();
		$ChapterCache = & get_ChapterCache();

		// Add regexp modifier 'u' to work with UTF-8 strings correctly:
		$regexp_modifier = ( $evo_charset == 'utf-8' ) ? 'u' : '';

		// Parse wiki word to find additional param for atrr "id":
		$url_params = '';
		preg_match( '/^([^#]+)(#(.+))?$/i', $this->current_WikiWord, $WikiWord_match );
		if( empty( $WikiWord_match ) )
		{
			preg_match( '/#(?<=#).*/', $this->current_WikiWord, $WikiWord_match );
			$WikiWord_match[1] = isset( $WikiWord_match[0] ) ? $WikiWord_match[0] : null;
			$anchor = $WikiWord_match[1];
		}

		if( isset( $WikiWord_match[3] ) )
		{	// wiki word has attr "id"
			$url_params .= '#'.$WikiWord_match[3];
		}

		// Use title of wiki word without attribute part:
		$WikiWord = $WikiWord_match[1];

		// Find matching Chapter or Item:
		$permalink = '';
		$link_text = preg_replace( array( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '*([^0-9])([0-9])*'.$regexp_modifier ), '$1 $2', $WikiWord );
		$link_text = ucwords( str_replace( '-', ' ', $link_text ) );

		if( ! empty( $this->link_types['item_id'] ) && is_numeric( $this->current_wiki_word ) && ( $Item = & $ItemCache->get_by_ID( $this->current_wiki_word, false, false ) ) )
		{	// Item is found
			$permalink = $Item->get_permanent_url();
			$existing_link_text = $Item->get( 'title' );
		}
		elseif( ! empty( $this->link_types['cat_slugs'] ) && $Chapter = & $ChapterCache->get_by_urlname( $this->current_wiki_word, false, false ) )
		{	// Chapter is found
			$permalink = $Chapter->get_permanent_url();
			$existing_link_text = $Chapter->get( 'name' );
		}
		elseif( ! empty( $this->link_types['item_slugs'] ) && $Item = & $ItemCache->get_by_urltitle( $this->current_wiki_word, false, false ) )
		{	// Item is found
			$permalink = $Item->get_permanent_url();
			$existing_link_text = $Item->get( 'title' );
		}
		elseif( ! empty( $this->link_types['anchor'] ) && isset( $anchor ) && ( $Item = & $ItemCache->get_by_ID( $ItemCache->ID_array[0], false, false ) ) )
		{	// Item is found
			$permalink = $Item->get_permanent_url();
			$permalink = $url_params == '' ? $permalink.$anchor : $url_params;
			$existing_link_text = $Item->get( 'title' );
			unset($anchor);
		}

		// Clear custom link text:
		$custom_link_text = utf8_trim( $m[4] );

		// Clear custom link style classes:
		$custom_link_class = utf8_trim( str_replace( '.', ' ', $m[2] ) );

		// Clear custom link target:
		$custom_link_target = utf8_trim( $m[3] );

		if( ! empty( $permalink ) )
		{	// Chapter or Item are found in DB
			$custom_link_class = empty( $custom_link_class ) ? '' : ' class="'.$custom_link_class.'"';
			$custom_link_target = empty( $custom_link_target ) ? '' : ' target="'.$custom_link_target.'"';

			if( ! empty( $custom_link_text ) )
			{	// [[WikiWord custom link text]] or ((WikiWord custom link text)) or [[WikiWord .style.classes custom link text]] or ((WikiWord .style.classes custom link text))
				return '<a href="'.$permalink.$url_params.'"'.$custom_link_class.$custom_link_target.'>'.$custom_link_text.'</a>';
			}
			elseif( $m[1] == '[[' )
			{	// [[Wikiword]] or [[Wikiword .style.classes]]
				return '<a href="'.$permalink.$url_params.'"'.$custom_link_class.$custom_link_target.'>'.$existing_link_text.'</a>';
			}
			else
			{	// ((Wikiword)) or ((Wikiword .style.classes))
				return '<a href="'.$permalink.$url_params.'"'.$custom_link_class.$custom_link_target.'>'.$link_text.'</a>';
			}
		}
		else
		{	// Chapter and Item are not found in DB
			if( ( empty( $this->link_types['item_id'] ) && is_numeric( $this->current_wiki_word ) ) ||
			    ( empty( $this->link_types['anchor'] ) && isset( $anchor ) ) )
			{	// Return original text if no found by numeric wikiword and "Item ID in brackets" is disabled:
				return $m[0];
			}
			else
			{	// Display a link to suggest to create new post from wiki word:
				return $this->get_broken_link( $this->current_wiki_word, ( empty( $custom_link_text ) ? $link_text : $custom_link_text ), $custom_link_class );
			}
		}
	}


	/**
	 * Get HTML code for broken link
	 *
	 * @param string Post slug
	 * @param string Link/Span text
	 * @param string Link/Span class
	 * @return string
	 */
	function get_broken_link( $post_slug, $text, $class = '' )
	{
		global $blog, $admin_url, $evo_charset;

		if( isset( $this->render_type ) && $this->render_type == 'email' )
		{	// Don't render broken link for Email Campaign because it is impossible
			// to check user permission when content will be viewed on email inbox:
			return $text;
		}

		// Add regexp modifier 'u' to work with UTF-8 strings correctly:
		$regexp_modifier = ( $evo_charset == 'utf-8' ) ? 'u' : '';

		$class = empty( $class ) ? '' : $class.' ';

		if( is_numeric( $post_slug ) && ! is_numeric( $text ) )
		{	// Try to use custom text if it is provided instead of post ID to suggest a link to create new post:
			$post_slug = preg_replace( array( '*([^\p{Lu}#_])([\p{Lu}#])*'.$regexp_modifier, '*([^0-9])([0-9])*'.$regexp_modifier ), '$1-$2', utf8_strtolower( $text ) );
		}

		if( isset( $blog ) && ! is_numeric( $post_slug ) )
		{	// Suggest to create new post from given word:
			$before_wikiword = '<a'
				.' href="#"'
				.' class="'.$class.'evo_shortlink_broken"'
				// Add these data attributes in order to display this link only for user who can really create a post:
				.' data-function="create_post" data-coll="'.$blog.'">';
			$after_wikiword = '</a>';
		}
		else
		{	// Don't allow to create new post from numeric wiki word:
			$before_wikiword = '<span class="'.$class.'evo_shortlink_broken">';
			$after_wikiword = '</span>';
		}

		return $before_wikiword.$text.$after_wikiword;
	}


	/**
	 * Callback function to replace the links for creating new posts if current user has no permission
	 *
	 * @param array Matches
	 * @return string
	 */
	function callback_replace_post_links( $matches )
	{
		if( ! isset( $matches[1], $matches[2], $matches[3] ) )
		{	// Return a source string when no enough data to check user permissions:
			return $matches[0];
		}

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $matches[2], false, false );

		// Get an URL to create new post,
		// If this function return an empty string then current user has no permission:
		$new_post_url = $Blog ? $Blog->get_write_item_url( 0, $matches[3] ) : false;

		if( ! $new_post_url )
		{	// If user has no permission to create a post for the collection,
			// display only a link text without providing a link to create new post:
			return $matches[3];
		}

		// If user has a permission to create a post for the collection,
		// display the source link but replace the source URL with new generated,
		// because it may be different between back- and front-office and also between
		// anonymous and logged in users (disp=edit vs disp=anonpost):
		return preg_replace( '# href="[^"]+"#i', ' href="'.$new_post_url.'" title="'.format_to_output( T_('Create').'...', 'htmlattr' ).'"', $matches[0] );
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
		{	// Item is set, get Blog from post:
			$edited_Item = & $params['Item'];
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen:
		return $this->DisplayCodeToolbar( $Blog, $params );
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
		{	// Comment is set, get Blog from comment:
			$Comment = & $params['Comment'];
			if( ! empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Comment is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen
		return $this->DisplayCodeToolbar( $Blog, $params );
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
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( NULL, $params );
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
			return $this->DisplayCodeToolbar( NULL, $params );
		}
		return false;
	}


	/**
	 * Display Toolbar
	 *
	 * @param object Blog
	 */
	function DisplayCodeToolbar( $Blog = NULL, $params = array() )
	{
		global $Hit, $baseurl, $debug;

		if( $Hit->is_lynx() )
		{	// let's deactivate toolbar on Lynx, because they don't work there:
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		// Load js to work with textarea:
		require_js( 'functions.js', 'blog', true, true );

		// Load css for modal window:
		$this->require_css( 'shortlinks.css', true );

		// Initialize JavaScript to build and open window:
		echo_modalwindow_js();

		// Initialize Javascript to build shortlinks modal window;
		init_shortlinks_js();

		?><script>
		//<![CDATA[
		function shortlinks_toolbar( title, prefix )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) ); ?>' + title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) ); ?>'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>'

				+ '<input type="button" title="<?php echo TS_('Link to a Post') ?>"'
				+ ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>"'
				+ ' data-func="shortlinks_load_window|shortlinks|' + prefix + '" value="<?php echo TS_('Link to a Post') ?>" />'

				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';

				jQuery( '.' + prefix + '<?php echo $this->code ?>_toolbar' ).html( r );
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script>shortlinks_toolbar( '<?php echo TS_('Short Links:'); ?>', '<?php echo $params['js_prefix']; ?>' );</script><?php

		return true;
	}


	/**
	 * Event handler: called at the beginning of {@link Item::dbinsert() inserting
	 * an item/post in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function PrependItemInsertTransact( & $params )
	{
		$Item = & $params['Item'];

		// Get collection from given params:
		$setting_Blog = $this->get_Blog_from_params( $params );

		if( ! $this->is_renderer_enabled( $this->get_coll_setting( 'coll_apply_rendering', $setting_Blog ), $Item->get_renderers_validated() ) )
		{	// Don't try to optimize when this plugin is not applied for Items:
			return;
		}

		// Get settings to know what should be optimized:
		$this->optimize = $this->get_coll_setting( 'optimize', $setting_Blog );

		// Optimize URLs:
		$Item->set( 'content', $this->optimize_urls( $Item->get( 'content' ) ) );
	}


	/**
	 * Event handler: called at the beginning of {@link Item::dbupdate() updating
	 * an item/post in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function PrependItemUpdateTransact( & $params )
	{
		$this->PrependItemInsertTransact( $params );
	}


	/**
	 * Event handler: called at the beginning of {@link Comment::dbinsert() inserting
	 * a Comment in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 */
	function PrependCommentInsertTransact( & $params )
	{
		$Comment = & $params['Comment'];

		// Get collection from given params:
		$setting_Blog = $this->get_Blog_from_params( $params );

		if( ! $this->is_renderer_enabled( $this->get_coll_setting( 'coll_apply_comment_rendering', $setting_Blog ), $Comment->get_renderers_validated() ) )
		{	// Don't try to optimize when this plugin is not applied for Comments:
			return;
		}

		// Get settings to know what should be optimized:
		$this->optimize = $this->get_coll_setting( 'optimize', $setting_Blog );

		// Optimize URLs:
		$Comment->set( 'content', $this->optimize_urls( $Comment->get( 'content' ) ) );
	}


	/**
	 * Event handler: called at the beginning of {@link Comment::dbupdate() updating
	 * a Comment in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 */
	function PrependCommentUpdateTransact( & $params )
	{
		$this->PrependCommentInsertTransact( $params );
	}


	/**
	 * Event handler: called at the beginning of {@link Message::dbinsert_discussion() inserting
	 * an Message in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Message': the related Message (by reference)
	 */
	function PrependMessageInsertTransact( & $params )
	{
		$Message = & $params['Message'];

		if( ! $this->is_renderer_enabled( $this->get_msg_setting( 'msg_apply_rendering' ), $Message->get_renderers_validated() ) )
		{	// Don't try to optimize when this plugin is not applied for Items:
			return;
		}

		$this->optimize = $this->get_msg_setting( 'optimize' );

		$Message->set( 'text', $this->optimize_urls( $Message->get( 'text' ) ) );
	}


	/**
	 * Event handler: called at the beginning of {@link EmailCampaign::dbinsert() inserting
	 * an Email Campaign in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'EmailCampaign': the related EmailCampaign (by reference)
	 */
	function PrependEmailInsertTransact( & $params )
	{
		$EmailCampaign = & $params['EmailCampaign'];

		if( ! $this->is_renderer_enabled( $this->get_email_setting( 'email_apply_rendering' ), $EmailCampaign->get_renderers_validated() ) )
		{	// Don't try to optimize when this plugin is not applied for Items:
			return;
		}

		$this->optimize = $this->get_email_setting( 'optimize' );

		$EmailCampaign->set( 'email_text', $this->optimize_urls( $EmailCampaign->get( 'email_text' ) ) );
		//$EmailCampaign->set( 'email_html', $this->optimize_urls( $EmailCampaign->get( 'email_html' ) ) );
		//$EmailCampaign->set( 'email_plaintext', $this->optimize_urls( $EmailCampaign->get( 'email_plaintext' ) ) );
	}


	/**
	 * Event handler: called at the beginning of {@link EmailCampaign::dbupdate() updating
	 * an Email Campaign in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'EmailCampaign': the related EmailCampaign (by reference)
	 */
	function PrependEmailUpdateTransact( & $params )
	{
		$this->PrependEmailInsertTransact( $params );
	}


	/**
	 * Optimize URLs in content
	 *
	 * @param string Source content
	 * @return string Optimized content
	 */
	function optimize_urls( $content )
	{
		if( ! empty( $this->optimize['absolute_urls'] ) )
		{	// Optimize absolute URLs:
			$content = replace_content_outcode( '*
					( \[\[ | \(\( ) # Lookbehind for (( or [[
					( ( (https?://|//).+/ ) ( [^/][^<>{}\s\]\)]+ ) ) # URL
					( \s.+ )?       # Additional attributes like style classes, link target, custon link text (Optional)
					( \]\] | \)\) ) # Lookahead for )) or ]]
					*ix', // x = extended (spaces + comments allowed)
				array( $this, 'optimize_urls_callback' ), $content, 'replace_content', 'preg_callback' );
		}

		if( ! empty( $this->optimize['relative_urls'] ) )
		{	// Optimize relative URLs:
			$content = replace_content_outcode( '*
					( \[\[ | \(\( ) # Lookbehind for (( or [[
					( ( /(.+/)? ) ( [^/][^<>{}\s\]\)]+ ) ) # URL
					( \s.+ )?       # Additional attributes like style classes, link target, custon link text (Optional)
					( \]\] | \)\) ) # Lookahead for )) or ]]
					*ix', // x = extended (spaces + comments allowed)
				array( $this, 'optimize_urls_callback' ), $content, 'replace_content', 'preg_callback' );
		}

		return $content;
	}


	/**
	 * Callback function for URLs optimization
	 *
	 * @param array $m
	 * @return string
	 */
	function optimize_urls_callback( $m )
	{
		if( ! ( $m[1] == '[[' && $m[7] == ']]' ) &&
		    ! ( $m[1] == '((' && $m[7] == '))' ) )
		{	// Wrong pattern, Return original text:
			return $m[0];
		}

		if( preg_match( '#^(https?://|//)$#', $m[4] ) &&
		    ! is_internal_url( $m[3] ) )
		{	// This is an absolute URL but this is an external URLs,
			// don't optimize this URL to slug:
			return $m[0];
		}

		$SlugCache = & get_SlugCache();
		if( ! $SlugCache->get_by_name( $m[5], false, false ) )
		{	// The Slug is not found in system, Keep it as is without optimization:
			return $m[0];
		}

		// Return short link tag only with slug(without absolute or relative path):
		return $m[1].$m[5].$m[6].$m[7];
	}
}
?>