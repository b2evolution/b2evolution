<?php
/**
 * This file implements the Wiki links plugin for b2evolution
 *
 * Creates wiki links
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class wikilinks_plugin extends Plugin
{
	var $code = 'b2evWcko';
	var $name = 'Wiki Links';
	var $priority = 35;
	var $apply_when = 'opt-in';
	var $apply_to_html = true; 
	var $apply_to_xml = false; // Leave the markup
	var $short_desc;
	var $long_desc;

	/**
	 * Constructor
	 *
	 * {@internal gmcode_Rendererplugin::gmcode_Rendererplugin(-)}}
	 */
	function wikilinks_plugin()
	{
		$this->short_desc = T_('Wiki Links converter');
		$this->long_desc = T_('WikiWord links ((link)) [[link ]]');
	}


	/**
	 * Perform rendering
	 *
	 * {@internal gmcode_Rendererplugin::render(-)}} 
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		global $ItemCache, $admin_url, $blog;
	
		if( ! parent::render( $content, $format ) )
		{	// We cannot render the required format
			return false;
		}
	
		// Regular links:
		$search = array(	
			// [[http://url]] :
			'#\[\[((http|https|mailto)://([^, <>{}\n\r]+?))\]\]#i',
			// [[http://url text]] :
			'#\[\[((http|https|mailto)://([^, <>{}\n\r]+?)) ([^\n\r]+?)\]\]#i',
			// ((http://url)) :
			'#\(\(((http|https|mailto)://([^, <>{}\n\r]+?))\)\)#i',
			// ((http://url text)) :
			'#\(\(((http|https|mailto)://([^, <>{}\n\r]+?)) ([^\n\r]+?)\)\)#i'																
		);
		$replace = array( 
			'<a href="$1">$1</a>',
			'<a href="$1">$4</a>',
			'<a href="$1">$1</a>',
			'<a href="$1">$4</a>'
		);
		
		$content = preg_replace( $search, $replace,	$content );
		
/*
	$ret = preg_replace("#([\n ])aim:([^,< \n\r]+)#i", "\\1<a href=\"aim:goim?screenname=\\2\\3&message=Hello\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])icq:([^,< \n\r]+)#i", "\\1<a href=\"http://wwp.icq.com/scripts/search.dll?to=\\2\\3\">\\2\\3</a>", $ret);

	$ret = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\">www.\\2.\\3\\4</a>", $ret);

	$ret = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([^,< \n\r]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret); */
	
	
		// WIKIWORDS:
	
		$search_wikiwords = array();
		$replace_links = array();
	
		// STANDALONE WIKIWORDS:
		$search = '/
				(?<= \s | ^ )													# Lookbehind for whitespace
				([A-Z]+[a-z0-9_]+([A-Z]+[A-Za-z0-9_]+)+)	# WikiWord or WikiWordLong
				(?= [\.,:;!\?] \s | \s | $ )											# Lookahead for whitespace or punctuation
			/x';	// x = extended (spaces + comments allowed)

		if( preg_match_all( $search, $content, $matches, PREG_SET_ORDER) )
		{ 
			// Construct array of wikiwords to look up in post urltitles
			$wikiwords = array();
			foreach( $matches as $match )
			{
				// Convert the WikiWord to an urltitle
				$WikiWord = $match[0];
				$Wiki_Word = preg_replace( '*([^A-Z_])([A-Z])*', '$1_$2', $WikiWord );
				$wiki_word = strtolower( $Wiki_Word );
				// echo '<br />Match: [', $WikiWord, '] -> [', $wiki_word, ']';
				$wikiwords[ $WikiWord ] = $wiki_word;
			}

			// Lookup all urltitles at once in DB and preload cache:
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
				if( ($Item = $ItemCache->get_by_urltitle( $wiki_word, false )) !== false )
				{ // Item Found
					$permalink = $Item->gen_permalink();
		
					// WikiWord
					$replace_links[] = '<a href="'.$permalink.'">'.$WikiWord.'</a>';
					
				}
				else
				{	// Item not found
	
					$create_link = isset($blog) ? ('<a href="'.$admin_url.'b2edit.php?blog='.$blog.'&amp;post_title='.preg_replace( '*([^A-Z_])([A-Z])*', '$1%20$2', $WikiWord ).'&amp;post_urltitle='.$wiki_word.'" title="Create...">?</a>') : '';
	
					// WikiWord
					$replace_links[] = '<span class="NonExistentWikiWord">'.$WikiWord.$create_link.'</span>';
				
				}
			}
		}
		
		// BRACKETED WIKIWORDS:
		$search = '/
				(?<= \(\( | \[\[ )										# Lookbehind for (( or [[
				([A-Z]+[A-Za-z0-9_]*)									# Anything from Wikiword to WikiWordLong
				(?= ( \s .*? )? ( \)\) | \]\] ) )			# Lookahead for )) or ]]
			/x';	// x = extended (spaces + comments allowed)

		if( preg_match_all( $search, $content, $matches, PREG_SET_ORDER) )
		{ 
		// Construct array of wikiwords to look up in post urltitles
		$wikiwords = array();
		foreach( $matches as $match )
		{
			// Convert the WikiWord to an urltitle
			$WikiWord = $match[0];
			$Wiki_Word = preg_replace( '*([^A-Z_])([A-Z])*', '$1_$2', $WikiWord );
			$wiki_word = strtolower( $Wiki_Word );
			// echo '<br />Match: [', $WikiWord, '] -> [', $wiki_word, ']';
			$wikiwords[ $WikiWord ] = $wiki_word;
		}

		// Lookup all urltitles at once in DB and preload cache:
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


			// Find matching Item:
			if( ($Item = $ItemCache->get_by_urltitle( $wiki_word, false )) !== false )
			{ // Item Found
				$permalink = $Item->gen_permalink();

				// [[WikiWord text]]
				$replace_links[] = '<a href="'.$permalink.'">$1</a>';
	
				// ((WikiWord text))
				$replace_links[] = '<a href="'.$permalink.'">$1</a>';
	
				// [[Wikiword]]
				$replace_links[] = '<a href="'.$permalink.'">'.$WikiWord.'</a>';

				// ((Wikiword))
				$replace_links[] = '<a href="'.$permalink.'">'.$WikiWord.'</a>';
			}
			else
			{	// Item not found

				$create_link = isset($blog) ? ('<a href="'.$admin_url.'b2edit.php?blog='.$blog.'&amp;post_title='.preg_replace( '*([^A-Z_])([A-Z])*', '$1%20$2', $WikiWord ).'&amp;post_urltitle='.$wiki_word.'" title="Create...">?</a>') : '';
	
				// [[WikiWord text]]
				$replace_links[] = '<span class="NonExistentWikiWord">$1'.$create_link.'</span>';
	
				// ((WikiWord text))
				$replace_links[] = '<span class="NonExistentWikiWord">$1'.$create_link.'</span>';
	
				// [[Wikiword]]
				$replace_links[] = '<span class="NonExistentWikiWord">'.$WikiWord.$create_link.'</span>';

				// ((Wikiword))
				$replace_links[] = '<span class="NonExistentWikiWord">'.$WikiWord.$create_link.'</span>';
			}
		}
		}

		// echo '<br />---';

		// pre_dump( $search_wikiwords );

		if( count( $search_wikiwords ) )
		{	// We have set some links to replace:
			$content = preg_replace( $search_wikiwords, $replace_links, $content );
		}

		return true;
	}
}

?>