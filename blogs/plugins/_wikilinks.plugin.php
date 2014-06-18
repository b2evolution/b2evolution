<?php
/**
 * This file implements the Wiki links plugin for b2evolution
 *
 * Creates wiki links
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class wikilinks_plugin extends Plugin
{
	var $code = 'b2evWiLi';
	var $name = 'Wiki Links';
	var $priority = 35;
	var $version = '5.0.0';
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
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_values = array(
				'default_post_rendering' => 'opt-in'
			);

		if( !empty( $params['blog_type'] ) && $params['blog_type'] != 'forum' )
		{	// Set the default settings depends on blog type
			$default_values['default_comment_rendering'] = 'never';
		}

		$default_params = array_merge( $params, $default_values );
		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'link_without_brackets' => array(
					'label' => $this->T_('Links without brackets'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
					'note' => $this->T_('Enable this to create the links from words like WikiWord without brackets [[]]'),
				)
			) );
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
		else
		{ // Item and Comment are not defined, Exit here
			return;
		}
		$item_Blog = & $Item->get_Blog();

		return $this->render_content( $content, $item_Blog );
	}


	/**
	 * Render content of Item, Comment, Message
	 *
	 * @todo get rid of global $blog
	 *
	 * @param string Content
	 * @param object Blog
	 * @param boolean Allow empty Blog
	 * return boolean
	 */
	function render_content( & $content, $item_Blog = NULL, $allow_null_blog = false )
	{
		global $ItemCache, $admin_url, $blog, $evo_charset;

		$regexp_modifier = '';
		if( $evo_charset == 'utf-8' )
		{ // Add this modifier to work with UTF-8 strings correctly
			$regexp_modifier = 'u';
		}

		// Regular links:
		$search = array(
			// [[http://url]] :
			'#\[\[((https?|mailto)://((?:[^<>{}\s\]]|,(?!\s))+?))\]\]#i',
			// [[http://url text]] :
			'#\[\[((https?|mailto)://([^<>{}\s\]]+)) ([^\n\r]+?)\]\]#i',
			// ((http://url)) :
			'#\(\(((https?|mailto)://((?:[^<>{}\s\]]|,(?!\s))+?))\)\)#i',
			// ((http://url text)) :
			'#\(\(((https?|mailto)://([^<>{}\s\]]+)) ([^\n\r]+?)\)\)#i',
		);
		$replace = array(
			'<a href="$1">$1</a>',
			'<a href="$1">$4</a>',
			'<a href="$1">$1</a>',
			'<a href="$1">$4</a>'
		);

		$content = replace_content_outcode( $search, $replace, $content );

/* QUESTION: fplanque, implementation of this planned? then use make_clickable() - or remove this comment
	$ret = preg_replace("#([\n ])aim:([^,< \n\r]+)#i", "\\1<a href=\"aim:goim?screenname=\\2\\3&message=Hello\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])icq:([^,< \n\r]+)#i", "\\1<a href=\"http://wwp.icq.com/scripts/search.dll?to=\\2\\3\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\">www.\\2.\\3\\4</a>", $ret);

	$ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^,< \n\r]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret); */

		// To use function replace_special_chars()
		load_funcs('locales/_charset.funcs.php');

		// WIKIWORDS:

		$search_wikiwords = array();
		$replace_links = array();

		if( $this->get_coll_setting( 'link_without_brackets', $item_Blog, $allow_null_blog ) )
		{	// Create the links from standalone WikiWords

			// STANDALONE WIKIWORDS:
			$search = '/
					(?<= \s | ^ )													# Lookbehind for whitespace
					([\p{Lu}]+[\p{Ll}0-9_]+([\p{Lu}]+[\p{L}0-9_]+)+)	# WikiWord or WikiWordLong
					(?= [\.,:;!\?] \s | \s | $ )											# Lookahead for whitespace or punctuation
				/x'.$regexp_modifier;	// x = extended (spaces + comments allowed)

			if( preg_match_all( $search, $content, $matches, PREG_SET_ORDER) )
			{
				// Construct array of wikiwords to look up in post urltitles
				$wikiwords = array();
				foreach( $matches as $match )
				{
					// Convert the WikiWord to an urltitle
					$WikiWord = $match[0];
					$Wiki_Word = preg_replace( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '$1-$2', $WikiWord );
					$wiki_word = evo_strtolower( $Wiki_Word );
					// echo '<br />Match: [', $WikiWord, '] -> [', $wiki_word, ']';
					$wiki_word = replace_special_chars( $wiki_word );
					$wikiwords[ $WikiWord ] = $wiki_word;
				}

				// Lookup all urltitles at once in DB and preload cache:
				$ItemCache = & get_ItemCache();
				$ItemCache->load_urltitle_array( $wikiwords );

				// Construct arrays for replacing wikiwords by links:
				foreach( $wikiwords as $WikiWord => $wiki_word )
				{
					// WikiWord
					$search_wikiwords[] = '/
						(?<= \s | ^ ) 						# Lookbehind for whitespace or start
						(?<! <span\ class="NonExistentWikiWord"> )
						'.$WikiWord.'							# Specific WikiWord to replace
						(?= [\.,:;!\?] \s | \s | $ )							# Lookahead for whitespace or end of string
						/sx';	// s = dot matches newlines, x = extended (spaces + comments allowed)


					// Find matching Item:
					if( ($Item = & $ItemCache->get_by_urltitle( $wiki_word, false )) !== false )
					{ // Item Found
						$permalink = $Item->get_permanent_url();

						// WikiWord
						$replace_links[] = '<a href="'.$permalink.'">'.$Item->get( 'title' ).'</a>';

					}
					else
					{ // Item not found

						$create_link = isset($blog) ? ('<a href="'.$admin_url.'?ctrl=items&amp;action=new&amp;blog='.$blog.'&amp;post_title='.preg_replace( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '$1%20$2', $WikiWord ).'&amp;post_urltitle='.$wiki_word.'" title="Create...">?</a>') : '';

						// WikiWord
						$replace_links[] = '<span class="NonExistentWikiWord">'.$WikiWord.$create_link.'</span>';

					}
				}
			}
		}

		// BRACKETED WIKIWORDS:
		$search = '/
				(?<= \(\( | \[\[ )										# Lookbehind for (( or [[
				([\p{L}0-9]+[\p{L}0-9_\-]*)									# Anything from Wikiword to WikiWordLong
				(?= ( \s .*? )? ( \)\) | \]\] ) )			# Lookahead for )) or ]]
			/x'.$regexp_modifier;	// x = extended (spaces + comments allowed)

		if( preg_match_all( $search, $content, $matches, PREG_SET_ORDER) )
		{
			// Construct array of wikiwords to look up in post urltitles
			$wikiwords = array();
			foreach( $matches as $match )
			{
				// Convert the WikiWord to an urltitle
				$WikiWord = $match[0];
				if( preg_match( '/^[\p{Ll}0-9_\-]+$/'.$regexp_modifier, $WikiWord ) )
				{	// This WikiWord already matches a slug format
					$Wiki_Word = $WikiWord;
					$wiki_word = $Wiki_Word;
				}
				else
				{	// Convert WikiWord to slug format
					$Wiki_Word = preg_replace( array( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '*([^0-9])([0-9])*'.$regexp_modifier ), '$1-$2', $WikiWord );
					$wiki_word = evo_strtolower( $Wiki_Word );
				}
				// echo '<br />Match: [', $WikiWord, '] -> [', $wiki_word, ']';
				$wiki_word = replace_special_chars( $wiki_word );
				$wikiwords[ $WikiWord ] = $wiki_word;
			}

			// Lookup all urltitles at once in DB and preload cache:
			$ChapterCache = & get_ChapterCache();
			$ChapterCache->load_urlname_array( $wikiwords );
			$ItemCache = & get_ItemCache();
			$ItemCache->load_urltitle_array( $wikiwords );

			// Construct arrays for replacing wikiwords by links:
			foreach( $wikiwords as $WikiWord => $wiki_word )
			{
				// [[WikiWord text]]
				$search_wikiwords[] = '*
					\[\[
					'.$WikiWord.'							# Specific WikiWord to replace
					\s (.+?)
					\]\]
					*sx';	// s = dot matches newlines, x = extended (spaces + comments allowed)

				// ((WikiWord text))
				$search_wikiwords[] = '*
					\(\(
					'.$WikiWord.'							# Specific WikiWord to replace
					\s (.+?)
					\)\)
					*sx';	// s = dot matches newlines, x = extended (spaces + comments allowed)

				// [[Wikiword]]
				$search_wikiwords[] = '*
					\[\[
					'.$WikiWord.'							# Specific WikiWord to replace
					\]\]
					*sx';	// s = dot matches newlines, x = extended (spaces + comments allowed)

				// ((Wikiword))
				$search_wikiwords[] = '*
					\(\(
					'.$WikiWord.'							# Specific WikiWord to replace
					\)\)
					*sx';	// s = dot matches newlines, x = extended (spaces + comments allowed)


				// Find matching Chapter or Item:
				$permalink = '';
				$link_text = preg_replace( array( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '*([^0-9])([0-9])*'.$regexp_modifier ), '$1 $2', $WikiWord );
				$link_text = ucwords( str_replace( '-', ' ', $link_text ) );
				if( ($Chapter = & $ChapterCache->get_by_urlname( $wiki_word, false )) !== false )
				{ // Chapter is found
					$permalink = $Chapter->get_permanent_url();
					$existing_link_text = $Chapter->get( 'name' );
				}
				elseif( ($Item = & $ItemCache->get_by_urltitle( $wiki_word, false )) !== false )
				{ // Item is found
					$permalink = $Item->get_permanent_url();
					$existing_link_text = $Item->get( 'title' );
				}

				if( !empty( $permalink ) )
				{	// Chapter or Item are found
					// [[WikiWord text]]
					$replace_links[] = '<a href="'.$permalink.'">$1</a>';

					// ((WikiWord text))
					$replace_links[] = '<a href="'.$permalink.'">$1</a>';

					// [[Wikiword]]
					$replace_links[] = '<a href="'.$permalink.'">'.$existing_link_text.'</a>';

					// ((Wikiword))
					$replace_links[] = '<a href="'.$permalink.'">'.$link_text.'</a>';
				}
				else
				{	// Chapter and Item are not found
					$create_link = isset($blog) ? ('<a href="'.$admin_url.'?ctrl=items&amp;action=new&amp;blog='.$blog.'&amp;post_title='.preg_replace( '*([^\p{Lu}_])([\p{Lu}])*'.$regexp_modifier, '$1%20$2', $WikiWord ).'&amp;post_urltitle='.$wiki_word.'" title="Create...">?</a>') : '';

					// [[WikiWord text]]
					$replace_links[] = '<span class="NonExistentWikiWord">$1'.$create_link.'</span>';

					// ((WikiWord text))
					$replace_links[] = '<span class="NonExistentWikiWord">$1'.$create_link.'</span>';

					// [[Wikiword]]
					$replace_links[] = '<span class="NonExistentWikiWord">'.$link_text.$create_link.'</span>';

					// ((Wikiword))
					$replace_links[] = '<span class="NonExistentWikiWord">'.$link_text.$create_link.'</span>';
				}
			}
		}

		// echo '<br />---';

		// pre_dump( $search_wikiwords );

		$content = replace_content_outcode( $search_wikiwords, $replace_links, $content );

		return true;
	}
}
?>