<?php
/**
 * This file implements items
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

/**
 * Item Class
 */
class Item extends DataObject
{
	var $Author;
	var $date;
	var $scope;
	var $lang;
	var $title;
	var $content;
	var $wordcount = 0;
	var $main_cat_ID = 0;
	var $flags;
	var $comments;			// Comments status
	var $url;					// Should move
	var $autobr = 0;		// Should move
	/**
	 * @access private
	 */
	var $blog_ID;

	/** 
	 * Constructor
	 *
	 * {@internal Item::Item(-)}}
	 */
	function Item( $db_row = NULL )
	{
		global $tablecomments;
		
		// Call parent constructor:
		parent::DataObject( $tablecomments, 'post_', 'ID' );
	
		if( $db_row == NULL )
		{
			$this->ID = 0;
			$this->flags = array();
		}
		else
		{
			$this->ID = $db_row->ID;
			$authordata = get_userdata($db_row->post_author);
			$this->Author = new User( $authordata ); // COPY!
			$this->date = $db_row->post_date;
			$this->scope = $db_row->post_status;
			$this->lang = $db_row->post_lang;
			$this->title = $db_row->post_title;
			$this->content = $db_row->post_content;
			$this->wordcount = $db_row->post_wordcount;
			$this->main_cat_ID = $db_row->post_category;
			$this->flags = $db_row->post_flags;
			$this->comments = $db_row->post_comments;			// Comments status
			$this->url = $db_row->post_trackbacks;					// Should move
			$this->autobr = $db_row->post_autobr;				// Should move
			// Private vars
			$this->blog_ID = get_catblog( $this->main_cat_ID );
		}
	}	


	/** 
	 * Template function: display anchor for permalinks to refer to
	 *
	 * {@internal Item::anchor(-) }}
	 *
	 * @todo archives modes in clean mode
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function anchor( $mode = 'id' ) 
	{
		switch( $mode )
		{
			case 'title':
				$title = preg_replace( '/[^a-zA-Z0-9_\.-]/', '_', $this->title );
				echo '<a name="'.$title.'"></a>';
				break;
				
			default:
				echo '<a name="'.$this->ID.'"></a>';
				break;
		}
	}
	

	/** 
	 * Template function: list all the category names
	 *
	 * {@internal Item::categories(-) }}
	 *
	 * @param string link title, '#' for default, false if you want no links
	 * @param string string fo display before the MAIN category, 'hide' to ignore main cat
	 * @param string string fo display after the MAIN category, 'hide' to ignore main cat 
	 * @param string string fo display before OTHER categories, 'hide' to ignore other cats
	 * @param string string fo display after OTHER categories, 'hide' to ignore other cats
	 * @param string string fo display before EXTERNAL categories, 'hide' to ignore external cats
	 * @param string string fo display after EXTERNAL categories, 'hide' to ignore external cats
	 * @param string separator string
	 * @param string Output format for each cat, see {@link format_to_output()}
	 */
	function categories( 
		$link_title = '#',
		$before_main='<strong>', $after_main='</strong>',
		$before_other='', $after_other='',
		$before_external='<em>', $after_external='</em>',
		$separator = ', ', 
		$format = 'htmlbody'
	 )
	{
		global $cache_postcats;
	
		if( $link_title == '#' ) 
		{	/* TRANS: When the categories for a specific post are displayed, the user can click
					on these cats to browse them, this is the default href title displayed there */
			$link_title = T_('Browse category');
		}
	
		cat_load_postcats_cache();
		$categoryIDs = $cache_postcats[$this->ID];
		
		$categoryNames = array();
		foreach( $categoryIDs as $cat_ID )
		{
			$cat = get_the_category_by_ID($cat_ID);
			$cat_name = format_to_output( $cat["cat_name"], $format );
			
			if( $link_title )
			{	// we want to display links
				$curr_blogparams = get_blogparams_by_ID( $cat['cat_blog_ID'] );
				$cat_name = '<a href="'.get_bloginfo('blogurl', $curr_blogparams).'?cat='.$cat_ID.'" title="'.$link_title.'">'.$cat_name.'</a>';
			}
	
			if( $cat_ID == $this->main_cat_ID )
			{	// We are displaying the main cat!
				if( $before_main == 'hide' )
				{	// ignore main cat !!!
					continue;
				}
				$cat_name = $before_main.$cat_name.$after_main;
			}
			elseif( $cat['cat_blog_ID'] == $this->blog_ID )
			{ // We are displaying another cat in the same blog
				if( $before_other == 'hide' )
				{	// ignore main cat !!!
					continue;
				}
				$cat_name = $before_other.$cat_name.$after_other;
			}
			else
			{	// We are displaying an external cat (in another blog)
				if( $before_external == 'hide' )
				{	// ignore main cat !!!
					continue;
				}
				$cat_name = $before_external.$cat_name.$after_external;
			}
	
			$categoryNames[] = $cat_name;
		}
		echo implode( $separator, $categoryNames );
	}
	

	/** 
	 * Template function: display main category name
	 *
	 * {@internal Item::main_category(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function main_category( $format = 'htmlbody' ) 
	{
		echo format_to_output( get_catname( $this->main_cat_ID ), $format );
	}


	/** 
	 * Template function: display content of item
	 *
	 * WARNING: parameter order is different from deprecated the_content(...)
	 *
	 * {@internal Item::content(-) }}
	 *
	 * @todo Param order and cleanup
	 * @param mixed page number to display specific page, # for url parameter
	 * @param mixed true to display 'more' text, false not to display, # for url parameter
	 * @param string text to display as the more link
	 * @param string text to display as the more anchor (once the more link has been clicked)
	 * @param string string to display before more link/anchor
	 * @param string string to display after more link/anchor
	 * @param string Output format, see {@link format_to_output()}
	 * @param integer max number of words 
	 * @param boolean true if you don't want to repeat teaser after more link was pressed
	 * @param string filename to use to display more
	 */
	function content( 
		$disppage = '#',
		$dispmore = '#',
		$more_link_text = '#', 
		$more_anchor = '#', 
		$before_more = '#', 
		$after_more = '#', 
		$format = 'htmlbody', 
		$cut = 0,
		$stripteaser = false, 
		$more_file = ''
		) 
	{
		global $use_textile;
		
		// echo $format,'-',$cut,'-',$dispmore,'-',$disppage;
		
		if( $more_link_text == '#' ) 
		{	// TRANS: this is the default text for the extended post "more" link
			$more_link_text = '=> '.T_('Read more!');
		}
	
		if( $more_anchor == '#' ) 
		{	// TRANS: this is the default text displayed once the more link has been activated
			$more_anchor = '['.T_('More:').']';
		}
	
		if( $before_more == '#' ) 
			$before_more = '<p class="bMore">';
	
		if( $after_more == '#' ) 
			$after_more = '</p>';
		
		if( $dispmore === '#' )
		{ // We want to display more if requested by user:
			global $more;
			$dispmore = $more;
		}
	
		if ($more_file != '') 
			$file = $more_file;
		else
			$file = get_bloginfo('blogurl');

		$content = $this->content;
		$numpages = 1;
		
		if( preg_match('/<!--nextpage-->/', $content ) )
		{	// This is a multipage post
			if ($page > 1) $dispmore=1;
			$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
			$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
			$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);
			$pages = explode('<!--nextpage-->', $content);
			$numpages = count($pages);
			if( $disppage === '#' )
			{ // We want to display the page requested by the user:
				global $page;
				$disppage = $page;
			}
			if( $disppage > $numpages ) 
				$disppage = $numpages;	
			$content = $pages[$disppage-1];
		}
		
		$content = explode('<!--more-->', $content);
	
		if( count($content)>1 ) 
		{	// This is an extended post (has a more section):
			if( $dispmore )   
			{	// Viewer has already asked for more
				if( $stripteaser || preg_match('/<!--noteaser-->/', $content ) )
				{	// We want to strip the teaser:
					$content[0] = '';
				}
				$output = $content[0];
				if( !empty($more_anchor) ) $output .= $before_more;
				$output .= '<a id="more'.$id.'" name="more'.$id.'"></a>'.$more_anchor;
				if( !empty($more_anchor) ) $output .= $after_more;
				$output .= $content[1];
			} 
			else 
			{ // We are offering to read more
				$more_link = gen_permalink( $file, $this->ID, 'id', 'single', 1 );
				$output = $content[0];
				$output .= $before_more;
				$output .= '<a href="'.$more_link.'#more'.$this->ID.'">'.$more_link_text.'</a>';
				$output .= $after_more;
			}
		}
		else
		{ // Regular post
			$output = $content[0];
		}

		if( $use_textile ) $output = textile( $output );
	
		$output = format_to_output( $output, $format );
		
		if( ($format == 'xml') && $cut )
		{	// Let's cut this down...
			$blah = explode(' ', $output);
			if (count($blah) > $cut) 
			{
				for ($i=0; $i<$cut; $i++) 
				{
					$excerpt .= $blah[$i].' ';
				}
				$output = $excerpt . '...';
			}
		}

		echo $output;
	}

	
	/** 
	 * Template function: display date (datetime) of Item
	 *
	 * {@internal Item::date(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function date( $format = '', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_datefmt(), $this->date, $useGM);
		else
			echo mysql2date( $format, $this->date, $useGM);
	}

	/** 
	 * Template function: display time (datetime) of Item
	 *
	 * {@internal Item::time(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function time( $format = '', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_timefmt(), $this->date, $useGM );
		else
			echo mysql2date( $format, $this->date, $useGM );
	}

	/** 
	 * Template function: display language code for item
	 *
	 * {@internal Item::lang(-) }}
	 */
	function lang() 
	{
		$this->disp( 'lang', 'raw' );
	}


	/** 
	 * Template function: display language name for item
	 *
	 * {@internal Item::language(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function language( $format = 'htmlbody' ) 
	{
		global $languages;
		echo format_to_output( $languages[ $this->lang ], $format );
	}


	/** 
	 * Template function: display permalink for item
	 *
	 * {@internal Item::permalink(-) }}
	 *
	 * @param string 'id' or 'title'
	 * @param string filename to use
	 */
	function permalink( $mode = 'id', $file='' )
	{
		if( empty($file) ) 
			$file = get_bloginfo('blogurl', get_blogparams_by_ID( $this->blog_ID ) );
		echo gen_permalink( $file, $this->ID, $mode );
	}


	/** 
	 * Template function: display scope of item
	 *
	 * Scopes:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * {@internal Item::scope(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function scope( $format = 'htmlbody' ) 
	{
		global $post_statuses;

		if( $format == 'raw' )
		{
			$this->disp( 'scope', 'raw' );
		}
		else
		{
			echo format_to_output( T_( $post_statuses[$this->scope] ), $format );
		}
	}


	/** 
	 * Template function: display title for item and link to related URL
	 *
	 * {@internal Item::title(-) }}
	 *
	 * @param string String to display before the title if there is something to display
	 * @param string String to display after the title if there is something to display
	 * @param boolean false if you don't want to link to URL
	 * @param string Output format, see {@link format_to_output()}
	 */
	function title( 
		$before='',						// HTML/text to be displayed before title
		$after='', 						// HTML/text to be displayed after title
		$add_link = true, 		// Added link to this title?
		$format = 'htmlbody' ) 
	{
		if( empty($this->title) && $add_link )
			$title = $this->url;
		else
			$title = $this->title;

		if( empty($title) )
		{	// Nothing to display
			return;
		}
		
		$title = format_to_output( $title, $format );
		
		if( $add_link && (!empty($this->url)) )
		{
			$title = '<a href="'.$this->url.'">'.$title.'</a>';
		}
	
		echo $before;
		echo $title;
		echo $after;
	}

	/** 
	 * Template function: Display link to item related url
	 *
	 * {@internal Item::url_link(-) }}
	 *
	 * @param string string to display before the url (if exists)
	 * @param string string to display after the url (if exists)
	 * @param string Output format, see {@link format_to_output()}
	 */
	function url_link( $before='', $after='', $format = 'htmlbody' ) 
	{
		if( !empty( $this->url ) )
		{
			echo $before;
			echo '<a href="', $this->url, '">';
			echo format_to_output( $this->url, $format );
			echo '</a>';
			echo $after;
		}
	}

	/** 
	 * Template function: Display the number of words in the post
	 *
	 * {@internal Item::wordcount(-) }}
	 */
	function wordcount()
	{
		echo $this->wordcount;
	}

}
?>
