<?php
/**
 * This file implements the Short Links plugin for b2evolution
 *
 * Creates wiki links
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
class shortlinks_plugin extends Plugin
{
	var $code = 'b2evWiLi';
	var $name = 'Short Links';
	var $priority = 35;
	var $version = '6.9.3';
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
						array( 'relative_urls',         sprintf( $this->T_('Relative URLs (starting with %s followed by a letter or digit) in brackets'), '<code>/</code>' ), 0 ),
						array( 'anchor',                sprintf( $this->T_('Current page anchor URLs (starting with %s) in brackets'), '<code>#</code>' ), 1 ),
						array( 'cat_slugs',             $this->T_('Category slugs in brackets'), 1 ),
						array( 'item_slugs',            $this->T_('Item slugs in brackets'), 1 ),
						array( 'item_id',               $this->T_('Item ID in brackets'), 1 ),
						array( 'cat_without_brackets',  $this->T_('WikiWords without brackets matching category slugs'), 0 ),
						array( 'item_without_brackets', $this->T_('WikiWords without brackets matching item slugs'), 0 ),
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
						$replace_links[] = $this->get_broken_link( $WikiWord, $wiki_word, $WikiWord );
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

		// Clear custom link target:
		$custom_link_target = utf8_trim( $m[5] );

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
				return $this->get_broken_link( $WikiWord, $this->current_wiki_word, ( empty( $custom_link_text ) ? $link_text : $custom_link_text ), $custom_link_class );
			}
		}
	}


	/**
	 * Get HTML code for broken link
	 *
	 * @param string Post title
	 * @param string Post slug
	 * @param string Link/Span text
	 * @param string Link/Span class
	 * @return string
	 */
	function get_broken_link( $post_title, $post_slug, $text, $class = '' )
	{
		global $blog, $admin_url, $evo_charset;

		// Add regexp modifier 'u' to work with UTF-8 strings correctly:
		$regexp_modifier = ( $evo_charset == 'utf-8' ) ? 'u' : '';

		$class = empty( $class ) ? '' : $class.' ';

		if( is_numeric( $post_slug ) && ! is_numeric( $text ) )
		{	// Try to use custom text if it is provided instead of post ID to suggest a link to create new post:
			$post_slug = preg_replace( array( '*([^\p{Lu}#_])([\p{Lu}#])*'.$regexp_modifier, '*([^0-9])([0-9])*'.$regexp_modifier ), '$1-$2', utf8_strtolower( $text ) );
			$post_title = $text;
		}

		if( isset( $blog ) && ! is_numeric( $post_slug ) )
		{	// Suggest to create new post from given word:
			$post_title = preg_replace( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '$1 $2', $post_title );
			$post_title = ucfirst( str_replace( '-', ' ', $post_title ) );

			$before_wikiword = '<a'
				.' href="'.$admin_url.'?ctrl=items&amp;action=new&amp;blog='.$blog.'&amp;post_title='.urlencode( $post_title ).'&amp;post_urltitle='.urlencode( $post_slug ).'"'
				.' title="'.format_to_output( T_('Create').'...', 'htmlattr' ).'"'
				.' class="'.$class.'evo_shortlink_broken">';
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

		?><script type="text/javascript">
		//<![CDATA[
		function shortlinks_toolbar( title, prefix )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) ); ?>' + title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) ); ?>'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>'

				+ '<input type="button" title="<?php echo TS_('Link to a Post') ?>"'
				+ ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>"'
				+ ' data-func="shortlinks_load_window|' + prefix + '" value="<?php echo TS_('Link to a Post') ?>" />'

				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';

				jQuery( '.' + prefix + '<?php echo $this->code ?>_toolbar' ).html( r );
		}

		/**
		 * Load main modal window to select collection and posts
		 */
		function shortlinks_load_window( prefix )
		{
			openModalWindow( '<div id="shortlinks_wrapper"></div>', 'auto', '', true,
				'<?php echo TS_('Link to a Post'); ?>', // Window title
				[ '-', 'shortlinks_post_buttons' ], // Fake button that is hidden by default, Used to build buttons "Back" and "Insert [[post-url-name]]"
				true );

			// Load collections:
			shortlinks_load_colls( '<?php echo empty( $Blog ) ? '' : format_to_js( $Blog->get( 'urlname' ) ); ?>', prefix );

			// Set max-height to keep the action buttons on screen:
			var modal_window = jQuery( '#shortlinks_wrapper' ).parent();
			var modal_height = jQuery( window ).height() - 20;
			if( modal_window.hasClass( 'modal-body' ) )
			{	// Extract heights of header and footer:
				modal_height -= 55 + 64 +
					parseInt( modal_window.css( 'padding-top' ) ) + parseInt( modal_window.css( 'padding-bottom' ) );
			}
			modal_window.css( {
				'display': 'block',
				'overflow': 'auto',
				'max-height': modal_height
			} );

			// To prevent link default event:
			return false;
		}

		/**
		 * Get an error of fail request
		 *
		 * @param string Object selector
		 * @param object Error data: 'message', 'code', 'data.status'
		 */
		function shortlinks_api_print_error( obj_selector, error )
		{
			if( typeof( error ) != 'string' && typeof( error.code ) == 'undefined' )
			{
				error = typeof( error.responseJSON ) == 'undefined' ? error.statusText : error.responseJSON;
			}

			if( typeof( error.code ) == 'undefined' )
			{	// Unknown non-json response:
				var error_text = '<h4 class="text-danger">Unknown error: ' + error + '</h4>';
			}
			else
			{	// JSON error data accepted:
				var error_text ='<h4 class="text-danger">' + error.message + '</h4>';
				<?php
				if( $debug )
				{ // Display additional error info in debug mode only:
				?>
				error_text += '<div><b>Code:</b> ' + error.code + '</div>'
					+ '<div><b>Status:</b> ' + error.data.status + '</div>';
				<?php } ?>
			}

			shortlinks_end_loading( obj_selector, error_text );
		}

		/**
		 * Execute REST API request
		 *
		 * @param string REST API path
		 * @param string Object selector
		 * @param function Function on success request
		 * @param array Additional params
		 */
		function shortlinks_api_request( api_path, obj_selector, func, params )
		{
			shortlinks_start_loading( obj_selector );

			if( typeof( params ) == 'undefined' )
			{
				params = {};
			}

			jQuery.ajax(
			{
				url: restapi_url + api_path,
				data: params
			} )
			.then( func, function( jqXHR )
			{	// Error request, Display the error data:
				shortlinks_api_print_error( obj_selector, jqXHR );
			} );
		}

		/**
		 * Set style during loading new content
		 *
		 * @param string Object selector
		 */
		function shortlinks_start_loading( obj_selector )
		{
			jQuery( obj_selector ).addClass( 'shortlinks_loading' )
				.append( '<div class="shortlinks_loader">loading...</div>' );
		}

		/**
		 * Remove style after loading new content
		 *
		 * @param string Object selector
		 * @param string New content
		 */
		function shortlinks_end_loading( obj_selector, content )
		{
			jQuery( obj_selector ).removeClass( 'shortlinks_loading' )
				.html( content )
				.find( '.shortlinks_loader' ).remove();
		}

		/**
		 * Build a pagination from response data
		 *
		 * @param array Response data
		 * @param string Search keyword
		 * @return string Pagination
		 */
		function shortlinks_get_pagination( data, search_keyword )
		{
			var r = '';

			if( typeof( data.pages_total ) == 'undefined' || data.pages_total < 2 )
			{	// No page for this request:
				return r;
			}

			var search_keyword_attr = typeof( search_keyword ) == 'undefined' ? '' :
				' data-search="' + search_keyword.replace( '"', '\"' ) + '"';

			var current_page = data.page;
			var total_pages = data.pages_total;
			var page_list_span = 11; // Number of visible pages on navigation line
			var page_list_start, page_list_end;

			// Initialize a start of pages list:
			if( current_page <= parseInt( page_list_span / 2 ) )
			{	// the current page number is small
				page_list_start = 1;
			}
			else if( current_page > total_pages - parseInt( page_list_span / 2 ) )
			{	// the current page number is big
				page_list_start = Math.max( 1, total_pages - page_list_span + 1 );
			}
			else
			{	// the current page number can be centered
				page_list_start = current_page - parseInt( page_list_span / 2 );
			}

			// Initialize an end of pages list:
			if( current_page > total_pages - parseInt( page_list_span / 2 ) )
			{ //the current page number is big
				page_list_end = total_pages;
			}
			else
			{
				page_list_end = Math.min( total_pages, page_list_start + page_list_span - 1 );
			}

			r += '<ul class="shortlinks_pagination pagination"' + search_keyword_attr + '>';

			if( current_page > 1 )
			{	// A link to previous page:
				r += '<li><a href="#" data-page="' + ( current_page - 1 ) + '">&lt;&lt;</a></li>';
			}

			if( page_list_start > 1 )
			{ // The pages list doesn't contain the first page
				// Display a link to first page:
				r += '<li><a href="#" data-page="1">1</a></li>';

				if( page_list_start > 2 )
				{ // Display a link to previous pages range:
					r += '<li><a href="#" data-page="' + Math.ceil( page_list_start / 2 ) + '">...</a></li>';
				}
			}

			for( p = page_list_start; p <= page_list_end; p++ )
			{
				if( current_page == p )
				{	// Current page:
					r += '<li class="active"><span>' + p + '</span></li>';
				}
				else
				{
					r += '<li><a href="#" data-page="' + p + '">' + p + '</a></li>';
				}
			}

			if( page_list_end < total_pages )
			{	// The pages list doesn't contain the last page
				if( page_list_end < total_pages - 1 )
				{	// Display a link to next pages range:
					r += '<li><a href="#" data-page="' + ( page_list_end + Math.floor( ( total_pages - page_list_end ) / 2 ) ) + '">...</a></li>';
				}

				// Display a link to last page:
				r += '<li><a href="#" data-page="' + total_pages + '">' + total_pages + '</a></li>';
			}

			if( current_page < total_pages )
			{	// A link to next page:
				r += '<li><a href="#" data-page="' + ( current_page + 1 ) + '">&gt;&gt;</a></li>';
			}

			r += '</ul>';

			return r;
		}


		/**
		 * Load all available collections for current user:
		 *
		 * @param string Current collection urlname
		 * @param string Prefix to use several toolbars on one page
		 */
		function shortlinks_load_colls( current_coll_urlname, prefix )
		{
			shortlinks_api_request( 'collections', '#shortlinks_wrapper', function( data )
			{	// Display the colllections on success request:
				var coll_urlname = '';
				var coll_name = '';

				// Initialize html code to view the loaded collections:
				var r = '<div id="shortlinks_colls_list">'
					+ '<h2><?php echo TS_('Collections'); ?></h2>'
					+ '<select class="form-control">';
				for( var c in data.colls )
				{
					var coll = data.colls[c];
					r += '<option value="' + coll.urlname + '"'
						+ ( current_coll_urlname == coll.urlname ? ' selected="selected"' : '' )+ '>'
						+ coll.name + '</option>';
					if( coll_urlname == '' || coll.urlname == current_coll_urlname )
					{	// Set these vars to load posts of the selected or first collection:
						coll_urlname = coll.urlname;
						coll_name = coll.name;
					}
				}
				r += '</select>'
					+ '</div>'
					+ '<div id="shortlinks_posts_block"></div>'
					+ '<div id="shortlinks_post_block"></div>'
					+ '<input type="hidden" id="shortlinks_hidden_prefix" value="' + ( prefix ? prefix : '' ) + '" />'
					+ '<div id="shortlinks_post_form" style="display:none">'
						+ '<input type="hidden" id="shortlinks_hidden_ID" />'
						+ '<input type="hidden" id="shortlinks_hidden_cover_link" />'
						+ '<input type="hidden" id="shortlinks_hidden_teaser_link" />'
						+ '<input type="hidden" id="shortlinks_hidden_urltitle" />'
						+ '<input type="hidden" id="shortlinks_hidden_title" />'
						+ '<input type="hidden" id="shortlinks_hidden_excerpt" />'
						+ '<input type="hidden" id="shortlinks_hidden_teaser" />'
						+ '<p><label><input type="checkbox" id="shortlinks_form_full_cover" /> <?php echo TS_('Insert full cover image'); ?></label><p>'
						+ '<p><label><input type="checkbox" id="shortlinks_form_title" checked="checked" /> <?php echo TS_('Insert title'); ?></label><p>'
						+ '<p><label><input type="checkbox" id="shortlinks_form_thumb_cover" checked="checked" /> <?php echo TS_('Insert thumbnail of cover or first image'); ?></label><p>'
						+ '<p><label><input type="checkbox" id="shortlinks_form_excerpt" checked="checked" /> <?php echo TS_('Insert excerpt'); ?></label><p>'
						+ '<p><label><input type="checkbox" id="shortlinks_form_teaser" /> <?php echo TS_('Insert teaser'); ?></label><p>'
						+ '<p><label><input type="checkbox" id="shortlinks_form_more" checked="checked" /> <?php echo TS_('Insert "Read more" link'); ?></label><p>'
					+ '</div>'
					+ '<div id="shortlinks_post_options" class="form-horizontal" style="display:none">'
						+ '<div class="form-group"><label class="control-label col-sm-2"><?php echo TS_('Slug'); ?>:</label><div class="controls col-sm-10"><input type="text" id="shortlinks_opt_slug" class="form-control" style="width:100%" /></div></div>'
						+ '<div class="form-group"><label class="control-label col-sm-2"><?php echo TS_('Mode'); ?>:</label><div class="controls col-sm-10">'
							+ '<div class="radio"><label><input type="radio" name="shortlinks_opt_mode" id="shortlinks_opt_mode_title" checked="checked"><code>[[...]]</code> <?php echo TS_('Use title of destination post as link text'); ?></label></div>'
							+ '<div class="radio"><label><input type="radio" name="shortlinks_opt_mode" id="shortlinks_opt_mode_slug"><code>((...))</code> <?php echo TS_('Use slug words as link text'); ?></label></div>'
						+ '</div></div>'
						+ '<div class="form-group"><label class="control-label col-sm-2"><?php echo TS_('Classes'); ?>:</label><div class="controls col-sm-10"><input type="text" id="shortlinks_opt_classes" class="form-control" style="width:100%" /></div></div>'
						+ '<div class="form-group"><label class="control-label col-sm-2"><?php echo TS_('Target'); ?>:</label><div class="controls col-sm-10">'
							+ '<select id="shortlinks_opt_target" class="form-control">'
								+ '<option value=""><?php echo TS_('None'); ?></option>'
								+ '<option value="_blank"><?php echo TS_('Blank'); ?></option>'
								+ '<option value="_parent"><?php echo TS_('Parent'); ?></option>'
								+ '<option value="_top"><?php echo TS_('Top'); ?></option>'
							+ '</select>'
						+ '</div></div>'
						+ '<div class="form-group"><label class="control-label col-sm-2"><?php echo TS_('Text'); ?>:</label><div class="controls col-sm-10"><input type="text" id="shortlinks_opt_text" class="form-control" style="width:100%" /></div></div>'
					+ '</div>';

				shortlinks_end_loading( '#shortlinks_wrapper', r );

				if( coll_urlname != '' )
				{	// Load posts list of the current or first collection:
					shortlinks_load_coll_posts( coll_urlname, coll_name );
				}
			} );
		}

		/**
		 * Load posts list with search form of the collection:
		 *
		 * @param string Collection urlname
		 * @param string Collection name
		 * @param string Predefined Search keyword
		 */
		function shortlinks_display_search_form( coll_urlname, coll_name, search_keyword )
		{
			var r = '<h2>' + coll_name + '</h2>' +
				'<form class="form-inline" id="shortlinks_search__form" data-urlname="' + coll_urlname + '">' +
					'<div class="input-group">' +
						'<input type="text" id="shortlinks_search__input" class="form-control" value="' + ( typeof( search_keyword ) == 'undefined' ? '' : search_keyword ) + '">' +
						'<span class="input-group-btn"><button id="shortlinks_search__submit" class="btn btn-primary"><?php echo TS_('Search'); ?></button></span>' +
					'</div> ' +
					'<button id="shortlinks_search__clear" class="btn btn-default"><?php echo TS_('Clear'); ?></button>' +
				'</form>' +
				'<div id="shortlinks_posts_list"></div>';

			jQuery( '#shortlinks_posts_block' ).html( r );
		}

		/**
		 * Load posts list with search form of the collection:
		 *
		 * @param string Collection urlname
		 * @param string Collection name
		 * @param integer Page
		 */
		function shortlinks_load_coll_posts( coll_urlname, coll_name, page )
		{
			if( typeof( coll_name ) != 'undefined' && coll_name !== false )
			{
				shortlinks_display_search_form( coll_urlname, coll_name );
			}

			var page_param = ( typeof( page ) == 'undefined' || page < 2 ) ? '' : '&paged=' + page;

			shortlinks_api_request( 'collections/' + coll_urlname + '/items&orderby=datemodified&order=DESC' + page_param, '#shortlinks_posts_list', function( data )
			{	// Display the posts on success request:
				var r = '<ul>';
				for( var p in data.items )
				{
					var post = data.items[p];
					r += '<li><a href="#" data-id="' + post.id + '" data-urlname="' + coll_urlname + '">' + post.title + '</a></li>';
				}
				r += '</ul>';
				r += shortlinks_get_pagination( data );
				shortlinks_end_loading( '#shortlinks_posts_list', r );
			} );
		}

		/**
		 * Load the searched posts list:
		 *
		 * @param string Collection urlname
		 * @param string Search keyword
		 * @param integer Page
		 */
		function shortlinks_load_coll_search( coll_urlname, search_keyword, page )
		{
			var page_param = ( typeof( page ) == 'undefined' || page < 2 ) ? '' : '&page=' + page;

			shortlinks_api_request( 'collections/' + coll_urlname + '/search/' + search_keyword + '&kind=item' + page_param, '#shortlinks_posts_list', function( data )
			{	// Display the post data in third column on success request:
				if( typeof( data.code ) != 'undefined' )
				{	// Error code was responsed:
					shortlinks_api_print_error( '#shortlinks_posts_list', data );
					return;
				}

				var r = '<ul>';
				for( var s in data.results )
				{
					var search_item = data.results[s];
					if( search_item.kind != 'item' )
					{	// Dsiplay only items and skip all other:
						continue;
					}
					r += '<li>';
					//r += '<a href="' + search_item.permalink + '" target="_blank"><?php echo get_icon( 'permalink' ); ?></a> ';
					r += '<a href="#" data-id="' + search_item.id + '" data-urlname="' + coll_urlname + '">' + search_item.title + '</a>';
					r += '</li>';
				}
				r += '</ul>';
				r += shortlinks_get_pagination( data, search_keyword );
				shortlinks_end_loading( '#shortlinks_posts_list', r );
			} );
		}

	if( typeof( b2evo_shortlinks_plugin_initialized ) == 'undefined' )
	{	// Initialize the code below only once:

		b2evo_shortlinks_plugin_initialized = true;

		// Load the posts of the selected collection:
		jQuery( document ).on( 'change', '#shortlinks_colls_list select', function()
		{
			shortlinks_load_coll_posts( jQuery( this ).val(), jQuery( 'option:selected', this ).text() );

			// To prevent link default event:
			return false;
		} );

		// Submit a search form:
		jQuery( document ).on( 'submit', '#shortlinks_search__form', function()
		{
			var coll_urlname = jQuery( this ).data( 'urlname' );
			var search_keyword = jQuery( '#shortlinks_search__input' ).val();

			shortlinks_load_coll_search( coll_urlname, search_keyword );

			// To prevent link default event:
			return false;
		} );

		// Clear the search results:
		jQuery( document ).on( 'click', '#shortlinks_search__clear', function()
		{
			shortlinks_load_coll_posts( jQuery( this ).closest( 'form' ).data( 'urlname' ) );

			// Clear search input field:
			jQuery( '#shortlinks_search__input' ).val( '' );

			// To prevent link default event:
			return false;
		} );

		// Load the data of the selected post:
		jQuery( document ).on( 'click', '#shortlinks_posts_list a[data-id]', function()
		{
			var coll_urlname = jQuery( this ).data( 'urlname' );
			var post_id = jQuery( this ).data( 'id' );

			// Hide the lists of collectionss and posts:
			jQuery( '#shortlinks_colls_list, #shortlinks_posts_block' ).hide();

			// Show the post preview block, because it can be hidded after prevous preview:
			jQuery( '#shortlinks_post_block' ).show();

			if( jQuery( '#shortlinks_post_block' ).data( 'post' ) == post_id )
			{	// If user loads the same post, just display the cached content to save ajax calls:
				// Show the action buttons:
				jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_insert' ).show();
			}
			else
			{	// Load new post:
				jQuery( '#shortlinks_post_block' ).html( '' ); // Clear previous cached content
				shortlinks_api_request( 'collections/' + coll_urlname + '/items/' + post_id, '#shortlinks_post_block', function( post )
				{	// Display the post data on success request:
					jQuery( '#shortlinks_post_block' ).data( 'post', post.id );

					// Store item field values in hidden inputs to use on insert complex link:
					jQuery( '#shortlinks_hidden_ID' ).val( post.id );
					jQuery( '#shortlinks_hidden_urltitle' ).val( post.urltitle );
					jQuery( '#shortlinks_hidden_title' ).val( post.title );
					jQuery( '#shortlinks_hidden_excerpt' ).val( post.excerpt );
					jQuery( '#shortlinks_hidden_teaser' ).val( post.teaser );
					jQuery( '#shortlinks_hidden_cover_link' ).val( '' );
					jQuery( '#shortlinks_hidden_teaser_link' ).val( '' );

					// Item title:
					var item_content = '<h2>' + post.title + '</h2>';
					// Item attachments, Only images:
					if( typeof( post.attachments ) == 'object' && post.attachments.length > 0 )
					{
						item_content += '<div id="shortlinks_post_attachments">';
						for( var a in post.attachments )
						{
							var attachment = post.attachments[a];
							if( attachment.type == 'image' )
							{	// Use only images:
								if( attachment.position == 'teaser' ||
								    attachment.position == 'teaserperm' ||
								    attachment.position == 'teaserlink' )
								{	// Add teaser image to post content:
									item_content += '<img src="' + attachment.url + '" />';
								}
								if( attachment.position == 'cover' )
								{	// Store link ID of cover image in hidden field to use on insert complex link:
									jQuery( '#shortlinks_hidden_cover_link' ).val( attachment.link_ID );
								}
								if( jQuery( '#shortlinks_hidden_teaser_link' ).val() == '' )
								{	// Store link ID of any first image in hidden field to use on insert complex link:
									jQuery( '#shortlinks_hidden_teaser_link' ).val( attachment.link_ID );
								}
							}
						}
						item_content += '</div>';
					}
					// Item content:
					item_content += '<div id="shortlinks_post_content">' + post.content + '</div>';

					shortlinks_end_loading( '#shortlinks_post_block', item_content );

					// Display the buttons to back and insert a post link to textarea:
					var buttons_side_obj = jQuery( '.shortlinks_post_buttons' ).length ?
						jQuery( '.shortlinks_post_buttons' ) :
						jQuery( '#shortlinks_post_content' );
					jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).remove();
					buttons_side_obj.after( '<button id="shortlinks_btn_back_to_list" class="btn btn-default">&laquo; <?php echo TS_('Back'); ?></button>'
						+ '<button id="shortlinks_btn_insert" class="btn btn-primary"><?php echo sprintf( /* TRANS: %s is a shortlink preview like [[url-slug]] */ TS_('Insert %s'), '[[\' + post.urltitle + \']]' ); ?></button>'
						+ '<button id="shortlinks_btn_options" class="btn btn-default"><?php echo TS_('Insert with options').'...'; ?></button>'
						+ '<button id="shortlinks_btn_form" class="btn btn-info"><?php echo TS_('Insert Snippet + Link').'...'; ?></button>' );
					jQuery( '#shortlinks_opt_slug' ).val( post.urltitle );
				} );
			}

			// To prevent link default event:
			return false;
		} );

		// Insert a post link to textarea:
		jQuery( document ).on( 'click', '#shortlinks_btn_insert', function()
		{
			shortlinks_insert_link_text( '[[' + jQuery( '#shortlinks_hidden_urltitle' ).val() + ']]' );
		} );

		// Insert a post link with options to textarea:
		jQuery( document ).on( 'click', '#shortlinks_btn_insert_with_options', function()
		{
			// Select what brakets to use:
			if( jQuery( '#shortlinks_opt_mode_title' ).is( ':checked' ) )
			{
				var brakets_start = '[[';
				var brakets_end = ']]';
			}
			else
			{
				var brakets_start = '((';
				var brakets_end = '))';
			}

			var link_text = brakets_start;

			// Slug:
			link_text += jQuery( '#shortlinks_opt_slug' ).val();

			// Classes:
			var classes = jQuery( '#shortlinks_opt_classes' ).val().trim();
			classes = classes.replace( /^\./g, '' ).replace( /\s+/g, '.' ).replace( /\.+/g, '.' );
			if( classes != '' )
			{
				link_text += ' .' + classes;
			}

			// Link target:
			var target = jQuery( '#shortlinks_opt_target option:selected' ).val().trim();
			if( target != '' )
			{
				link_text += ' ' + target;
			}

			// Custom text:
			var custom_text = jQuery( '#shortlinks_opt_text' ).val().trim();
			if( custom_text != '' )
			{
				link_text += ' ' + custom_text;
			}

			link_text += brakets_end;

			shortlinks_insert_link_text( link_text );
		} );

		// Display a form to insert complex link:
		jQuery( document ).on( 'click', '#shortlinks_btn_form', function()
		{
			// Set proper title for modal window:
			jQuery( '#modal_window .modal-title' ).html( '<?php echo TS_('Insert Snippet + Link'); ?>' );

			// Hide the post preview block:
			jQuery( '#shortlinks_post_block, #shortlinks_btn_insert' ).hide();

			// Show the form to select options before insert complex link:
			jQuery( '#shortlinks_post_form' ).show();

			// Display the buttons to back and insert a complex link to textarea:
			var buttons_side_obj = jQuery( '.shortlinks_post_buttons' ).length ?
				jQuery( '.shortlinks_post_buttons' ) :
				jQuery( '#shortlinks_post_content' );
			jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).hide();
			buttons_side_obj.after( '<button id="shortlinks_btn_back_to_post" class="btn btn-default">&laquo; <?php echo TS_('Back'); ?></button>'
				+ '<button id="shortlinks_btn_insert_complex" class="btn btn-primary"><?php echo TS_('Insert Snippet + Link'); ?></button>' );

			// To prevent link default event:
			return false;
		} );

		// Insert complex link:
		jQuery( document ).on( 'click', '#shortlinks_btn_insert_complex', function()
		{
			if( ! jQuery( 'input[id^=shortlinks_form_]' ).is( ':checked' ) )
			{	// Display message if no item option checkbox is checked:
				alert( '<?php echo TS_('Please select at least one item option to insert.'); ?>' );
				return false;
			}

			var dest_type = false;
			var dest_object_ID = false;
			if( jQuery( 'input[type=hidden][name=temp_link_owner_ID]' ).length )
			{	// New object form:
				dest_type = 'temporary';
				dest_object_ID = jQuery( 'input[type=hidden][name=temp_link_owner_ID]' ).val();
			}
			else if( jQuery( 'input[type=hidden][name=post_ID]' ).length && jQuery( 'input[type=hidden][name=item_typ_ID]' ).length )
			{	// Item form:
				dest_type = 'item';
				dest_object_ID = jQuery( 'input[type=hidden][name=post_ID]' ).val();
			}
			else if( jQuery( 'input[type=hidden][name=comment_ID]' ).length || jQuery( 'input[type=hidden][name=comment_item_ID]' ).length )
			{	// Comment form:
				dest_type = 'comment';
				if( jQuery( 'input[type=hidden][name=comment_ID]' ).length )
				{
					dest_object_ID = jQuery( 'input[type=hidden][name=comment_ID]' ).val();
				}
			}
			else if( jQuery( 'input[type=hidden][name=ecmp_ID]' ).length )
			{	// Email Campaign form:
				dest_type = 'emailcampaign';
				dest_object_ID = jQuery( 'input[type=hidden][name=ecmp_ID]' ).val();
			}
			else if( jQuery( 'input[name=msg_text]' ).length )
			{	// Message form:
				dest_type = 'message';
				dest_object_ID = 0;
			}

			// Check if at least one image is requested to insert:
			var insert_images = ( jQuery( '#shortlinks_form_full_cover' ).is( ':checked' ) && jQuery( '#shortlinks_hidden_cover_link' ).val() != '' )
				|| ( jQuery( '#shortlinks_form_thumb_cover' ).is( ':checked' ) && jQuery( '#shortlinks_hidden_teaser_link' ).val() != '' );

			if( insert_images && dest_type != false && dest_object_ID > 0 )
			{	// We need to insert at least one image/file inline tag:
				shortlinks_start_loading( '#shortlinks_post_block' );

				// Get first image with any position:
				var source_position = '';
				if( jQuery( '#shortlinks_form_full_cover' ).is( ':checked' ) &&
				  ! jQuery( '#shortlinks_form_thumb_cover' ).is( ':checked' ) )
				{	// Get only cover image:
					source_position = 'cover';
				}

				// Call REST API request to copy the links from the selected Item to the edited object:
				evo_rest_api_request( 'links',
				{
					'action':           'copy',
					'source_type':      'item',
					'source_object_ID': jQuery( '#shortlinks_hidden_ID' ).val(),
					'source_position':  source_position,
					'source_file_type': 'image',
					'dest_type':        dest_type,
					'dest_object_ID':   dest_object_ID,
					'dest_position':    'inline',
					'limit_position':   1,
				}, function( data )
				{
					var full_cover = '';
					var thumb_cover = '';

					for( var l in data.links )
					{
						var link = data.links[l];
						if( link.orig_position == 'cover' && jQuery( '#shortlinks_form_full_cover' ).is( ':checked' ) )
						{	// Build inline tag for full cover image:
							full_cover = '[image:' + link.ID + ']';
						}
						if( jQuery( '#shortlinks_form_thumb_cover' ).is( ':checked' ) )
						{	// Build inline tag for thumbnail cover image:
							thumb_cover = '[thumbnail:' + link.ID + ']';
						}
					}
					shortlinks_insert_complex_link( full_cover, thumb_cover );

					shortlinks_end_loading( '#shortlinks_post_block', jQuery( '#shortlinks_post_block' ).html() );

					// Refresh the attachments block after adding new links:
					var refresh_attachments_button = jQuery( 'a[onclick*=evo_link_refresh_list]' );
					if( refresh_attachments_button.length )
					{
						refresh_attachments_button.click();
					}
				} );
			}
			else
			{	// Insert only simple text without images:
				if( insert_images )
				{	// Display this alert if user wants to insert image for new creating object but it doesn't support:
					alert( 'Please save your ' + dest_type + ' before trying to attach files. This limitation will be removed in a future version of b2evolution.' );
				}
				shortlinks_insert_complex_link();
			}

			// To prevent link default event:
			return false;
		} );

		// Display a form to insert a link with options:
		jQuery( document ).on( 'click', '#shortlinks_btn_options', function()
		{
			// Set proper title for modal window:
			jQuery( '#modal_window .modal-title' ).html( '<?php echo TS_('Insert with options'); ?>...' );

			// Hide the post preview block:
			jQuery( '#shortlinks_post_block, #shortlinks_btn_insert' ).hide();

			// Show the form to select options before insert a link:
			jQuery( '#shortlinks_post_options' ).show();

			// Display the buttons to back and insert a complex link to textarea:
			var buttons_side_obj = jQuery( '.shortlinks_post_buttons' ).length ?
				jQuery( '.shortlinks_post_buttons' ) :
				jQuery( '#shortlinks_post_content' );
			jQuery( '#shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).hide();
			buttons_side_obj.after( '<button id="shortlinks_btn_back_to_post" class="btn btn-default">&laquo; <?php echo TS_('Back'); ?></button>'
				+ '<button id="shortlinks_btn_insert_with_options" class="btn btn-primary"><?php echo TS_('Insert Link'); ?></button>' );

			// To prevent link default event:
			return false;
		} );

		/*
		 * Insert simple link data to content
		 *
		 * @param string Text
		 */
		function shortlinks_insert_link_text( text )
		{
			
			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{	// tinyMCE plugin is active now, we should focus cursor to the edit area:
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
			}
			// Insert tag text in area:
			textarea_wrap_selection( window[ jQuery( '#shortlinks_hidden_prefix' ).val() + 'b2evoCanvas' ], text, '', 0 );
			// Close main modal window:
			closeModalWindow();
		}

		/*
		 * Insert complex link data to content
		 *
		 * @param string Full cover image inline tag
		 * @param string Thumbnail cover image inline tag
		 */
		function shortlinks_insert_complex_link( full_cover, thumb_cover )
		{
			var post_content = '';

			if( typeof( full_cover ) != 'undefined' && full_cover != '' )
			{	// Full cover image:
				post_content += "\r\n" + full_cover;
			}
			if( jQuery( '#shortlinks_form_title' ).is( ':checked' ) )
			{	// Title:
				post_content += "\r\n" + '## [[' + jQuery( '#shortlinks_hidden_urltitle' ).val() + ' ' + jQuery( '#shortlinks_hidden_title' ).val() + ']]';
			}
			if( typeof( thumb_cover ) != 'undefined' && thumb_cover != '' )
			{	// Thumbnail cover image:
				post_content += "\r\n" + thumb_cover;
			}
			if( jQuery( '#shortlinks_form_excerpt' ).is( ':checked' ) )
			{	// Excerpt:
				post_content += ( typeof( thumb_cover ) != 'undefined' && thumb_cover != '' ? ' ' : "\r\n" )
					+ jQuery( '#shortlinks_hidden_excerpt' ).val();
			}
			if( jQuery( '#shortlinks_form_teaser' ).is( ':checked' ) )
			{	// Teaser (text before [teaserbreak]):
				post_content += "\r\n" + jQuery( '#shortlinks_hidden_teaser' ).val();
			}
			if( jQuery( '#shortlinks_form_more' ).is( ':checked' ) )
			{	// "Read more" link:
				post_content += "\r\n" + '[[' + jQuery( '#shortlinks_hidden_urltitle' ).val() + ' <?php echo TS_('Read more'); ?>...]]';
			}
			if( post_content != '' )
			{
				post_content = post_content + "\r\n\r\n";
			}

			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{	// tinyMCE plugin is active now, we should focus cursor to the edit area:
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
			}
			// Insert tag text in area:
			textarea_wrap_selection( window[ jQuery( '#shortlinks_hidden_prefix' ).val() + 'b2evoCanvas' ], post_content, '', 0 );
			// Close main modal window:
			closeModalWindow();
		}

		// Back to previous list:
		jQuery( document ).on( 'click', '#shortlinks_btn_back_to_list', function()
		{
			// Show the lists of collections and posts:
			jQuery( '#shortlinks_colls_list, #shortlinks_posts_block' ).show();

			// Hide the post preview block and action buttons:
			jQuery( '#shortlinks_post_block, #shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).hide();

			// To prevent link default event:
			return false;
		} );

		// Back to previous post preview:
		jQuery( document ).on( 'click', '#shortlinks_btn_back_to_post', function()
		{
			// Set proper title for modal window:
			jQuery( '#modal_window .modal-title' ).html( '<?php echo TS_('Link to a Post'); ?>' );

			// Show the post preview block and action buttons:
			jQuery( '#shortlinks_post_block, #shortlinks_btn_back_to_list, #shortlinks_btn_insert, #shortlinks_btn_form, #shortlinks_btn_options' ).show();

			// Hide the post complex form and action buttons:
			jQuery( '#shortlinks_btn_back_to_post, #shortlinks_btn_insert_complex, #shortlinks_btn_insert_with_options, #shortlinks_post_form, #shortlinks_post_options' ).hide();

			// To prevent link default event:
			return false;
		} );

		// Switch page:
		jQuery( document ).on( 'click', '.shortlinks_pagination a', function()
		{
			var coll_selector = jQuery( '#shortlinks_colls_list select' );
			var pages_list = jQuery( this ).closest( '.shortlinks_pagination' );

			if( pages_list.data( 'search' ) == undefined )
			{	// Load posts/items for selected page:
				shortlinks_load_coll_posts( coll_selector.val(), false, jQuery( this ).data( 'page' ) );
			}
			else
			{	// Load search list for selected page:
				shortlinks_load_coll_search( coll_selector.val(), pages_list.data( 'search' ), jQuery( this ).data( 'page' ) );
			}

			// To prevent link default event:
			return false;
		} );

	}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script type="text/javascript">shortlinks_toolbar( '<?php echo TS_('Short Links:'); ?>', '<?php echo $params['js_prefix']; ?>' );</script><?php

		return true;
	}
}
?>