<?php
/**
 * This file implements items
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

/**
 * Item Class
 *
 * @package evocore
 */
class Item extends DataObject
{
	var $Author;
	var $issue_date;
	var $mod_date;
	var $status;
	var $locale;
	var $title;
	var $urltitle;
	var $content;
	var $wordcount = 0;
	var $main_cat_ID = 0;
	var $flags;
	var $renderers;
	var $comments;			// Comments status
	var $url;					// Should move
	var $autobr = 0;		// Should move
	var $views = 0;
	// Derived:
	var $blog_ID;

	/**
	 * Constructor
	 *
	 * {@internal Item::Item(-)}}
	 */
	function Item( $db_row = NULL )
	{
		global $tableposts;

		// Call parent constructor:
		parent::DataObject( $tableposts, 'post_', 'ID' );

		if( $db_row == NULL )
		{
			$this->ID = 0;
			$this->flags = array();
			$this->renderers = array();
		}
		else
		{
			$this->ID = $db_row->ID;
			$authordata = get_userdata($db_row->post_author);
			$this->Author = new User( $authordata ); // COPY!
			$this->issue_date = $db_row->post_issue_date;
			$this->mod_date = $db_row->post_mod_date;
			$this->status = $db_row->post_status;
			$this->locale = $db_row->post_locale;
			$this->title = $db_row->post_title;
			$this->urltitle = $db_row->post_urltitle;
			$this->content = $db_row->post_content;
			$this->wordcount = $db_row->post_wordcount;
			$this->main_cat_ID = $db_row->post_category;
			$this->flags = $db_row->post_flags;
			$this->comments = $db_row->post_comments;			// Comments status
			// echo 'renderers=', $db_row->post_renderers;
			$this->renderers = explode( '.', $db_row->post_renderers );
			$this->views = $db_row->post_views; 
			$this->url = $db_row->post_url;				// Should move
			$this->autobr = $db_row->post_autobr;					// Should move
			// Derived vars
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
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function gen_permalink( $mode = '', $blogurl = '', $force_single = false )
	{
		global $DB, $BlogCache, $cacheweekly, $Settings;

		if( empty( $mode ) )
			$mode = $Settings->get( 'permalink_type' );

		if( $force_single && (strpos( $mode, 'archive' ) !== false) )
		{	// Comments cannot be displayed in archive mode
			$mode = 'pid';
		}

		if( empty( $blogurl ) ) 
		{
			$current_Blog = $BlogCache->get_by_ID( $this->blog_ID );
			$blogurl = $current_Blog->gen_blogurl();
		}

		$post_date = $this->issue_date;

		switch( $mode )
		{
			case 'archive#id':
				// Link to an archive page:
				$dest_type = $Settings->get('archive_mode');
				$anchor = $this->ID;
				$urltail = 'p'.$this->ID;
				break;

			case 'archive#title':
				// Link to an archive page:
				$dest_type = $Settings->get('archive_mode');
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

		if( ! $Settings->get('links_extrapath') )
		{	// We reference by Query: Dirty but explicit permalinks

			switch( $dest_type )
			{
				case 'monthly':
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2) ).'#'.$anchor;
					break;

				case 'weekly':
					if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date])))
					{
						$cacheweekly[$post_date] = $DB->get_var( "SELECT WEEK('".$post_date."')" );
					}
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).'&amp;w='.$cacheweekly[$post_date] ).'#'.$anchor;
					break;

				case 'daily':
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2).substr($post_date,8,2) ).'#'.$anchor;
					break;

				case 'postbypost':
				default:
					$permalink = url_add_param( $blogurl, $urlparam.'&amp;more=1&amp;c=1&amp;tb=1&amp;pb=1' );
					break;
			}
		}
		else
		{	// We reference by path (CLEAN permalinks!)
			switch( $dest_type )
			{
				case 'monthly':
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m", $post_date) ).'#'.$anchor;
					break;

				case 'weekly':
					if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date])))
					{
						$cacheweekly[$post_date] = $DB->get_var( "SELECT WEEK('".$post_date."')" );
					}
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/", $post_date).'w'.$cacheweekly[$post_date] ).'#'.$anchor;
					break;

				case 'daily':
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m/d", $post_date) ).'#'.$anchor;
					break;

				case 'postbypost':
				default:
					// This is THE CLEANEST available: RECOMMENDED!
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m/d/", $post_date).$urltail );
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
		global $Settings;

		if( empty( $mode ) )
			$mode = $Settings->get( 'permalink_type' );

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
				$cat_name = '<a href="'.url_add_param( get_bloginfo('blogurl', $curr_blogparams), 'cat='.$cat_ID ).'" title="'.$link_title.'">'.$cat_name.'</a>';
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
	 * Check if user can see comments on this post
	 *
	 * {@internal Item::can_see_comments(-) }}
	 */
	function can_see_comments()
	{
		if( $this->comments == 'disabled'  )
		{	// Comments are disabled on this post
			return false;
		}

		return true; // OK, user can see comments
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
		if( $this->comments == 'disabled'  )
		{	// Comments are disabled on this post
			return false;
		}

		if( $this->comments == 'closed'  )
		{	// Comments are closed on this post
			if( $closed_msg == '#' )
				$closed_msg = T_( 'Comments are closed for this post.' );

			echo $before_error;
			echo $closed_msg;
			echo $after_error;

			return false;
		}

		if( ($this->status == 'draft') || ($this->status == 'deprecated' ) )
		{	// Post is not published
			if( $non_published_msg == '#' )
				$non_published_msg = T_( 'This post is not published. You cannot leave comments.' );

			echo $before_error;
			echo $non_published_msg;
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
		global $Renderer, $uri_reloaded, $more, $preview;
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
			$dispmore = $more;
		}
		
		if( $dispmore && !$preview )
		{ // increment view counter
			if( !$uri_reloaded )
			{
				$this->set_param( 'views', 'number', ($this->views + 1) );
				$this->dbupdate();  // move to end of method, if we should have more params to be changed someday
			}
		}
		
		$content = $this->content;
		$numpages = 1;

		if( preg_match('/<!--nextpage-->/', $content ) )
		{	// This is a multipage post
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
			if($disppage > 1) $dispmore=1;
		}

		$content_parts = explode('<!--more-->', $content);

		if( count($content_parts)>1 )
		{	// This is an extended post (has a more section):
			if( $dispmore )
			{	// Viewer has already asked for more
				if( $stripteaser || preg_match('/<!--noteaser-->/', $content ) )
				{	// We want to strip the teaser:
					$output = '';
				}
				else
				{ // We keep the teaser:
					$output = $content_parts[0];
					if( !empty($more_anchor) ) $output .= $before_more;
					$output .= '<a id="more'.$this->ID.'" name="more'.$this->ID.'"></a>'.$more_anchor;
					if( !empty($more_anchor) ) $output .= $after_more;
				}
				if( count($content_parts) > 2 )
				{ // we have additional <!--more--> tags somewhere
					array_shift($content_parts);
					$output .= implode('', $content_parts);
				}
				else $output .= $content_parts[1];
			}
			else
			{ // We are offering to read more
				$output = $content_parts[0];
				$output .= $before_more .
										'<a href="'.$this->gen_permalink( 'pid', $more_file ).'#more'.$this->ID.'">'.
										$more_link_text.'</a>' .
										$after_more;
			}
		}
		else
		{ // Regular post
			$output = $content_parts[0];
		}

		// Apply rendering
		$post_renderers = $Renderer->validate_list( $this->renderers );
		$output = $Renderer->render( $output, $post_renderers, $format );

		// Character conversions
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
	 * Template function: display locale for item
	 *
	 * {@internal Item::lang(-) }}
	 */
	function lang()
	{
		$this->disp( 'locale', 'raw' );
	}

	/**
	 * Template function: display locale for item
	 *
	 * {@internal Item::locale(-) }}
	 */
	function locale()
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
		global $locales;
		$locale = $locales[ $this->locale ];
		echo format_to_output( $locale['name'], $format );
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
			echo mysql2date( locale_timefmt(), $this->mod_date, $useGM );
		else
			echo mysql2date( $format, $this->mod_date, $useGM );
	}


	/**
	 * Template function: display permalink for item
	 *
	 * {@internal Item::permalink(-)}}
	 *
	 * @param string 'post', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permalink( $mode = '', $blogurl='' )
	{
		echo $this->gen_permalink( $mode, $blogurl );
	}


	/*
	 * Template function: Displays link to feedback page (under some conditions)
	 *
	 * {@internal Item::feedback_link(-)}}
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param boolean true to use a popup windows ('#' to use if comments_popup_windows() is there)
	 * @param boolean true to hide if no feedback ('#' for default)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments
	 * @param string Link title
	 * @param string 'pid' or 'title'
	 * @param string url to use
	 */
	function feedback_link( $type = 'feedbacks', $before = '', $after = '',
													$zero='#', $one='#', $more='#', $title='#',
													$use_popup = '#',
													$hideifnone = '#', $mode = '', $blogurl='' )
	{
		global $b2commentsjavascript, $BlogCache;

		switch( $type )
		{
			case 'feedbacks':
				if( $hideifnone == '#' ) $hideifnone = false;
				if( $title == '#' ) $title = T_('Display feedback / Leave a comment');
				if( $zero == '#' ) $zero = T_('Send feedback');
				if( $one == '#' ) $one = T_('1 feedback');
				if( $more == '#' ) $more = T_('%d feedbacks');
				break;

			case 'comments':
				if( ! $this->can_see_comments() )
					return false;
				if( $hideifnone == '#' )
				{
					if( $this->can_comment( '', '', '', '' ) )
						$hideifnone = false;
					else
						$hideifnone = true;
				}
				if( $title == '#' ) $title = T_('Display comments / Leave a comment');
				if( $zero == '#' ) $zero = T_('Leave a comment');
				if( $one == '#' ) $one = T_('1 comment');
				if( $more == '#' ) $more = T_('%d comments');
				break;

			case 'trackbacks':
				$current_Blog = $BlogCache->get_by_ID( $this->blog_ID );
				if( ! $current_Blog->get( 'allowtrackbacks' ) )
				{	// Trackbacks not allowed on this blog:
					return;
				}
				if( $hideifnone == '#' ) $hideifnone = false;
				if( $title == '#' ) $title = T_('Display trackbacks / Get trackback address for this post');
				if( $zero == '#' ) $zero = T_('Trackback (0)');
				if( $one == '#' ) $one = T_('Trackback (1)');
				if( $more == '#' ) $more = T_('Trackbacks (%d)');
				break;

			case 'pingbacks':
				$current_Blog = $BlogCache->get_by_ID( $this->blog_ID );
				if( ! $current_Blog->get( 'allowpingbacks' ) )
				{	// Pingbacks not allowed on this blog:
					return;
				}
				if( $hideifnone == '#' ) $hideifnone = true;
				if( $title == '#' ) $title = T_('Display pingbacks');
				if( $zero == '#' ) $zero = T_('Pingback (0)');
				if( $one == '#' ) $one = T_('Pingback (1)');
				if( $more == '#' ) $more = T_('Pingbacks (%d)');
				break;

			default:
				die( "Unkown feedback type [$type]" );
		}

		if( $use_popup == '#' )
		{	// Use popups if javascript is included in page
			$use_popup = $b2commentsjavascript;
		}

		$number = generic_ctp_number($this->ID, $type);

		if( ($number == 0) && $hideifnone )
			return false;

		$url = $this->gen_permalink( $mode, $blogurl, true );
		if( $use_popup )
		{ // We need to tell b2evo to use the popup template
			$url = url_add_param( $url, 'template=popup' );
		}

		echo $before;

		echo '<a href="', $url;
		echo '#', $type, '" ';	// Position on feedback
		echo 'title="', $title, '"';
		if( $use_popup ) echo '" onclick="b2open(this.href); return false"';
		echo '>';

		if( $number == 0 )
			echo $zero;
		elseif( $number == 1 )
			echo $one;
		elseif( $number > 1 )
			echo str_replace( '%d', $number, $more );

		echo '</a>';

		echo $after;

	}


	/**
	 * Provide link to edit a post if user has edit rights
	 *
	 * {@internal Item::edit_link(-)}}
	 *
	 * @param string to display before link
	 * @param string to display after link 
	 * @param string link text 
	 * @param string link title 
	 */
	function edit_link( $before = '', $after = '', $text = '#', $title = '#' )
	{
		global $current_User, $admin_url;
		
		if( ! is_logged_in() ) return false;
	
		if( ! $current_User->check_perm( 'blog_post_statuses', $this->status, false, 
																			$this->blog_ID ) )
		{	// User has no right to edit this post
			return false;
		}
	
		if( $text == '#' ) $text = T_('Edit');
		if( $title == '#' ) $title = T_('Edit this post');
		
		echo $before;
		echo '<a href="'.$admin_url.'/b2edit.php?action=edit&amp;post='.$this->ID;
		echo '" title="'.$title.'">'.$text.'</a>';
		echo $after;
	
		return true;
	}

	/**
	 * Template function: display status of item
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * {@internal Item::status(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function status( $format = 'htmlbody' )
	{
		global $post_statuses;

		if( $format == 'raw' )
		{
			$this->disp( 'status', 'raw' );
		}
		else
		{
			echo format_to_output( T_( $post_statuses[$this->status] ), $format );
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
		global $htsrv_url, $Settings;
		
		if( $Settings->get('links_extrapath') )
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
			echo format_to_output( '<a href="'.$this->url.'">'.$this->url.'</a>', $format );
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


	/**
	 * Template function: Display the number of views to the item
	 *
	 * {@internal Item::views(-) }}
	 */
	function views()
	{
		echo $this->views;
	}

}
?>
