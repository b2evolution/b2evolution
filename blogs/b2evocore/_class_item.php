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
	var $issue_date;
	var $mod_date;
	var $scope;
	var $lang;
	var $title;
	var $urltitle;
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
			$this->issue_date = $db_row->post_issue_date;
			$this->mod_date = $db_row->post_mod_date;
			$this->scope = $db_row->post_status;
			$this->locale = $db_row->post_locale;
			$this->title = $db_row->post_title;
			$this->urltitle = $db_row->post_urltitle;
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
 	 * generate permalink for item
	 *
	 * {@internal Item::gen_permalink(-)}}
	 *
	 * @todo archives modes in clean mode
	 * 
	 * @param string 'post', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function gen_permalink( $mode, $blogurl )
	{
		global $cacheweekly;

		if( empty( $mode ) )
			$mode = get_settings( 'pref_permalink_type' );
	
		if( empty( $blogurl ) ) 
			$blogurl = get_bloginfo('blogurl', get_blogparams_by_ID( $this->blog_ID ) );

		$post_date = $this->issue_date;

		switch( $mode )
		{
			case 'archive#id':
				// Link to an archive page:
				$dest_type = get_settings('archive_mode');
				$anchor = $this->ID;
				$urltail = 'p'.$this->ID;
				break;

			case 'archive#title':
				// Link to an archive page:
				$dest_type = get_settings('archive_mode');
				$anchor = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $this->title );
				$urltail = 'p'.$this->ID;
				break;
			
			case 'pid':
				// Link to individual post:
				$dest_type = 'postbypost';
				$urlparam = 'p='.$this->ID;
				$urltail = 'p'.$this->ID;
				break;
				
			case 'urltitle':
			default:
				// Link to individual post:
				$dest_type = 'postbypost';
				if( !empty( $this->urltitle ) )
				{
					$urlparam = 'title='.$this->urltitle;
					$urltail = $this->urltitle;
				}
				else
				{
					$urlparam = 'p='.$this->ID;
					$urltail = 'p'.$this->ID;
				}
		}

		if( ! get_settings('pref_links_extrapath') )
		{	// We reference by Query: Dirty but explicit permalinks
	
			switch( $dest_type ) 
			{
				case 'monthly':
					$permalink = $blogurl.'?m='.substr($post_date,0,4).substr($post_date,5,2).'#'.$anchor;
					break;
					
				case 'weekly':
					if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date]))) 
					{
						$sql = "SELECT WEEK('".$post_date."')";
						$result = mysql_query($sql);
						$row = mysql_fetch_row($result);
						$cacheweekly[$post_date] = $row[0];
					}
					$permalink = $blogurl.'?m='.substr($post_date,0,4).'&amp;w='.$cacheweekly[$post_date].'#'.$anchor;
					break;
					
				case 'daily':
					$permalink = $blogurl.'?m='.substr($post_date,0,4).substr($post_date,5,2).substr($post_date,8,2).'#'.$anchor;
					break;
					
				case 'postbypost':
				default:
					$permalink = $blogurl.'?'.$urlparam.'&amp;more=1&amp;c=1&amp;tb=1&amp;pb=1';
					break;
			}
		}
		else
		{	// We reference by path (CLEAN permalinks!)
			switch( $dest_type ) 
			{
				case 'monthly':
					$permalink = $blogurl.mysql2date("/Y/m", $post_date).'#'.$anchor;
					break;
					
				case 'weekly':
					if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date]))) 
					{
						$sql = "SELECT WEEK('".$post_date."')";
						$result = mysql_query($sql);
						$row = mysql_fetch_row($result);
						$cacheweekly[$post_date] = $row[0];
					}
					$permalink = $blogurl.mysql2date("/Y/", $post_date).'w'.$cacheweekly[$post_date].'#'.$anchor;
					break;
					
				case 'daily':
					$permalink = $blogurl.mysql2date("/Y/m/d", $post_date).'#'.$anchor;
					break;
					
				case 'postbypost':
				default:
					// This is THE CLEANEST available: RECOMMENDED!
					$permalink = $blogurl.mysql2date("/Y/m/d/", $post_date).$urltail;
					break;
			}
		}
		
		return $permalink;
	}


	/** 
	 * Template function: display anchor for permalinks to refer to
	 *
	 * {@internal Item::anchor(-) }}
	 *
	 * @todo archives modes in clean mode
	 *
	 * @param string 'id' or 'title'
	 */
	function anchor( $mode = '' ) 
	{
		if( empty( $mode ) )
			$mode = get_settings( 'pref_permalink_type' );

		switch( $mode )
		{
			case 'archive#title': // permalink_type
			case 'title': // explicit choice
				$title = preg_replace( '/[^a-zA-Z0-9_\.-]/', '_', $this->title );
				echo '<a name="'.$title.'"></a>';
				break;
				
			case 'archive#id': // permalink_type
			case 'id': // explicit choice
				echo '<a name="'.$this->ID.'"></a>';
				break;


			case 'pid': // permalink type where we need no ID
			case 'urltitle': // permalink type where we need no ID
			default:
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
	 * Template function: Check if user can leave comment on this post or display error
	 *
	 * {@internal Item::can_comment(-) }}
	 *
	 * @param string string to display before any error message
	 * @param string string to display after any error message
	 * @param string error message for non published posts, '#' for default
	 * @param string error message for closed comments posts, '#' for default
	 * @return boolean true if user can post
	 */
	function can_comment( 
						$before_error = '<p><em>', 
						$after_error = '</p></em>', 
						$non_published_msg = '#',
						$closed_msg = '#'
						) 
	{
		if( ($this->scope == 'draft') || ($this->scope == 'deprecated' ) )
		{	// Post is not published
			if( $non_published_msg == '#' )
				$non_published_msg = T_( 'This post is not published. You cannot leave comments.' );
		
			echo $before_error;
			echo $non_published_msg;
			echo $after_error;

			return false;
		}

		if( $this->comments != 'open'  )
		{	// Comments are not open on this post
			if( $closed_msg == '#' )
				$closed_msg = T_( 'Comments are closed for this post.' );
		
			echo $before_error;
			echo $closed_msg;
			echo $after_error;

			return false;
		}

		return true; // OK, user can comment!
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
	 * Template function: display issue date (datetime) of Item
	 *
	 * {@internal Item::issue_date(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function issue_date( $format = '', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_datefmt(), $this->issue_date, $useGM);
		else
			echo mysql2date( $format, $this->issue_date, $useGM);
	}

	/** 
	 * Template function: display issue time (datetime) of Item
	 *
	 * {@internal Item::issue_time(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function issue_time( $format = '', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_timefmt(), $this->issue_date, $useGM );
		else
			echo mysql2date( $format, $this->issue_date, $useGM );
	}


	/** 
	 * Template function: display language code for item
	 *
	 * {@internal Item::lang(-) }}
	 */
	function lang() 
	{
		$this->disp( 'locale', 'raw' );
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
	 * Template function: display last mod date (datetime) of Item
	 *
	 * {@internal Item::mod_date(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function mod_date( $format = '', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_datefmt(), $this->mod_date, $useGM);
		else
			echo mysql2date( $format, $this->mod_date, $useGM);
	}

	/** 
	 * Template function: display last mod time (datetime) of Item
	 *
	 * {@internal Item::mod_time(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function mod_time( $format = '', $useGM = false )
	{
		if( empty($format) ) 
			echo mysql2date( locale_timefmt(), $this->mod_time, $useGM );
		else
			echo mysql2date( $format, $this->mod_time, $useGM );
	}


	/** 
	 * Template function: display permalink for item
	 *
	 * {@internal Item::permalink(-) }}
	 *
	 * @param string 'post', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permalink( $mode = '', $blogurl='' )
	{
		echo $this->gen_permalink( $mode, $blogurl );
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
	 * Template function: Displays trackback autodiscovery information
	 *
	 * {@internal Item::trackback_rdf(-) }}
	 */
	function trackback_rdf() 
	{
		// if (!stristr($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator')) {
		// fplanque WARNING: this isn't a very clean way to validate :/
		// fplanque added: html comments (not perfect but better way of validating!)
		echo "<!--\n";
		echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" '."\n";
		echo '  xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n";
		echo '  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'."\n";
		echo '<rdf:Description'."\n";
		echo '  rdf:about="';
		$this->permalink( 'single' );
		echo '"'."\n";
		echo '  dc:identifier="';
		$this->permalink( 'single' );
		echo '"'."\n";
		$this->title( '  dc:title="', '"'."\n", false, 'xmlattr' );
		echo '  trackback:ping="';
		$this->trackback_url();
		echo '" />'."\n";
		echo '</rdf:RDF>';
		echo "-->\n";
		// }
	}


	/** 
	 * Template function: displays url to use to trackback this item
	 *
	 * {@internal Item::trackback_url(-) }}
	 */
	function trackback_url() 
	{
		global $htsrv_url;

		if( get_settings('pref_links_extrapath') ) 
		{
			echo "$htsrv_url/trackback.php/$this->ID";
		}
		else 
		{
			echo "$htsrv_url/trackback.php?tb_id=$this->ID";
		}
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
