<?php
/**
 * This file implements the Item class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 * @author gorgeb: EPISTEMA (Bertrand Gorge).
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobject.class.php';

/**
 * Object definition:
 */
$object_def['Item'] = array( // definition of the object:
			'db_cols' => array(	// maps properties to colums:
										'ID'              => 'ID',
										'creator_user_ID' => 'post_creator_user_ID',
										'lastedit_user_ID'=> 'post_lastedit_user_ID',
										'assigned_user_ID'=> 'post_assigned_user_ID',
										'datecreated'			=> 'post_datecreated',
										'deadline'        => 'post_datedeadline',
										'datestart'       => 'post_datestart',
										'datemodified'    => 'post_datemodified',
										'status'          => 'post_status',
										'locale'          => 'post_locale',
										'content'         => 'post_content',
										'title'           => 'post_title',
										'urltitle'        => 'post_urltitle',
										'url'             => 'post_url',
										'main_cat_ID'     => 'post_main_cat_ID',
										'flags'           => 'post_flags',
										'wordcount'       => 'post_wordcount',
										'comments'        => 'post_comments',
										'views'           => 'post_views',
										'renderers'       => 'post_renderers',
										'st_ID'           => 'post_pst_ID',
										'typ_ID'          => 'post_ptyp_ID',
										'priority'				=> 'post_priority'
									),
			'allow_null' => array( // specifies column nullability:
										'assigned_user_ID'=> true,
									),
		);


/**
 * Item Class
 *
 * @package evocore
 */
class Item extends DataObject
{
	/**
	 * @var User
	 * @access public
	 */
	var $Author;
	var $AssignedUser;
	var $issue_date;
	var $mod_date;
	var $status;
	/**
	 * locale code for the Item content
	 *
	 * examples: en-US, zh-CN-utf-8
	 *
	 * @var string
	 */
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
	var $typ_ID;
	var $st_ID;
	var $deadline = '';
	var $priority = 3;

	/**
	 * Derived from $main_cat_ID
	 *
	 * @var integer
	 */
	var $blog_ID;

	/**
	 * Constructor
	 *
	 * {@internal Item::Item(-)}}
	 *
	 * @param object table Database row
	 * @param string
	 * @param string
	 * @param string
	 * @param string for derived classes
	 * @param string datetime field name
	 * @param string datetime field name
	 * @param string User ID field name
	 * @param string User ID field name
	 */
	function Item( $db_row = NULL, $dbtable = 'T_posts', $dbprefix = 'post_', $dbIDname = 'ID', $objtype = 'Item',
												$datecreated_field = '', $datemodified_field = 'datemodified',
												$creator_field = 'creator_user_ID', $lasteditor_field = '' )
	{
		global $UserCache, $object_def, $localtimenow;

		// Dereference db cols definition for this object:
		$db_cols =  & $object_def[$objtype]['db_cols'];

		// Call parent constructor:
		parent::DataObject( $dbtable, $dbprefix, $dbIDname, $datecreated_field, $datemodified_field,
												$creator_field, $lasteditor_field );

		$this->objtype = $objtype;
		$this->typ_required = false;	// type NOT required
		$this->st_required = false;	// extra status NOT required

		if( $db_row == NULL )
		{
			$this->ID = 0;
			$this->issue_date = date('Y-m-d H:i:s', $localtimenow);
			$this->flags = array();
			$this->renderers = array();
		}
		else
		{
			$this->ID = $db_row->$dbIDname;
			$this->Author = & $UserCache->get_by_ID( $db_row->$db_cols['creator_user_ID'] );
			$this->assign_to( $db_row->$db_cols['assigned_user_ID'], false );
			$this->issue_date = $db_row->$db_cols['datestart'];
			$this->mod_date =$db_row->$db_cols['datemodified'];
			$this->status = $db_row->$db_cols['status'];
			$this->locale = $db_row->$db_cols['locale'];
			$this->title = $db_row->$db_cols['title'];
			$this->urltitle = $db_row->$db_cols['urltitle'];
			$this->content = $db_row->$db_cols['content'];
			$this->wordcount = $db_row->$db_cols['wordcount'];
			$this->main_cat_ID = $db_row->$db_cols['main_cat_ID'];
			$this->flags = $db_row->$db_cols['flags'];
			$this->comments = $db_row->$db_cols['comments'];			// Comments status
			$this->typ_ID = $db_row->$db_cols['typ_ID'];
			$this->st_ID = $db_row->$db_cols['st_ID'];
			$this->deadline = $db_row->$db_cols['deadline'];
			$this->priority = $db_row->$db_cols['priority'];

			// echo 'renderers=', $db_row->post_renderers;
			$this->renderers = explode( '.', $db_row->$db_cols['renderers'] );

			$this->views = $db_row->$db_cols['views'];
			$this->url = $db_row->$db_cols['url'];			// Should move

			// Derived vars
			$this->blog_ID = get_catblog( $this->main_cat_ID );
		}
	}


	/**
	 * @todo use extended dbchange instead of set_param...
	 */
	function assign_to( $user_ID, $dbupdate = true )
	{
		global $UserCache;

		// echo 'assigning user #'.$user_ID;
		if( $user_ID )
		{
			$this->AssignedUser = $UserCache->get_by_ID( $user_ID );
		}
		else
		{
			$this->AssignedUser = NULL;
		}

		if( $dbupdate )
		{ // Record ID for DB:
			$this->set_param( 'assigned_user_ID', 'number', $this->AssignedUser->ID, true );
		}
	}


	/**
	 * Generate the permalink for the item.
	 *
	 * {@internal Item::gen_permalink(-)}}
	 *
	 * @todo archives modes in clean mode
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 * @param boolean true to force single post on destination page
	 * @param string glue between url params
	 */
	function gen_permalink( $mode = '', $blogurl = '', $force_single = false, $glue = '&amp;' )
	{
		global $DB, $BlogCache, $cacheweekly, $Settings;

		if( empty( $mode ) )
			$mode = $Settings->get( 'permalink_type' );

		if( $force_single && (strpos( $mode, 'archive' ) !== false) )
		{ // Comments cannot be displayed in archive mode
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
		{ // We reference by Query: Dirty but explicit permalinks

			switch( $dest_type )
			{
				case 'monthly':
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2), $glue ).'#'.$anchor;
					break;

				case 'weekly':
					if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date])))
					{
						$cacheweekly[$post_date] = $DB->get_var( "SELECT WEEK('".$post_date."')" );
					}
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).$glue.'w='.$cacheweekly[$post_date], $glue ).'#'.$anchor;
					break;

				case 'daily':
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2).substr($post_date,8,2), $glue ).'#'.$anchor;
					break;

				case 'postbypost':
				default:
					$permalink = url_add_param( $blogurl, $urlparam.$glue.'more=1'.$glue.'c=1'.$glue.'tb=1'.$glue.'pb=1', $glue );
					break;
			}
		}
		else
		{ // We reference by path (CLEAN permalinks!)
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
	 * Template function: display asignee of item
	 *
	 * {@internal Item::assigned_to(-) }}
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function assigned_to( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( isset($this->AssignedUser) )
		{
			echo $before;
			$this->AssignedUser->prefered_name( $format );
			echo $after;
		}
	}


	/**
	 * Template function: display list of assigned user options
	 *
	 * {@internal Item::assigned_user_options(-)}}
	 */
	function assigned_user_options()
	{
		global $UserCache, $object_def;

		$UserCache->blog_member_list( $this->blog_ID, $this->AssignedUser->ID,
						$object_def[$this->objtype]['allow_null']['assigned_user_ID'],
						($this->ID != 0) /* if this Item is already serialized we'll load the default anyway */,
						true );
	}


	/**
	 * Template function: get list of assigned user options
	 *
	 * {@internal Item::get_assigned_user_options(-)}}
	 */
	function get_assigned_user_options()
	{
		global $UserCache, $object_def;

		return $UserCache->blog_member_list( $this->blog_ID, $this->AssignedUser->ID,
							$object_def[$this->objtype]['allow_null']['assigned_user_ID'],
							($this->ID != 0) /* if this Item is already serialized we'll load the default anyway */,
							false );
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
		global $BlogCache;

		if( $link_title == '#' )
		{ /* TRANS: When the categories for a specific post are displayed, the user can click
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
			{ // we want to display links
				$lBlog =& $BlogCache->get_by_ID( $cat['cat_blog_ID'] );
				$cat_name = '<a href="'.url_add_param( $lBlog->get('blogurl'), 'cat='.$cat_ID ).'" title="'.$link_title.'">'.$cat_name.'</a>';
			}

			if( $cat_ID == $this->main_cat_ID )
			{ // We are displaying the main cat!
				if( $before_main == 'hide' )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $before_main.$cat_name.$after_main;
			}
			elseif( $cat['cat_blog_ID'] == $this->blog_ID )
			{ // We are displaying another cat in the same blog
				if( $before_other == 'hide' )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $before_other.$cat_name.$after_other;
			}
			else
			{ // We are displaying an external cat (in another blog)
				if( $before_external == 'hide' )
				{ // ignore main cat !!!
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
		{ // Comments are disabled on this post
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
						$after_error = '</em></p>',
						$non_published_msg = '#',
						$closed_msg = '#'
						)
	{
		if( $this->comments == 'disabled'  )
		{ // Comments are disabled on this post
			return false;
		}

		if( $this->comments == 'closed'  )
		{ // Comments are closed on this post
			if( $closed_msg == '#' )
				$closed_msg = T_( 'Comments are closed for this post.' );

			echo $before_error;
			echo $closed_msg;
			echo $after_error;

			return false;
		}

		if( ($this->status == 'draft') || ($this->status == 'deprecated' ) )
		{ // Post is not published
			if( $non_published_msg == '#' )
				$non_published_msg = T_( 'This post is not published. You cannot leave comments.' );

			echo $before_error;
			echo $non_published_msg;
			echo $after_error;

			return false;
		}

		global $BlogCache;
		$current_Blog = $BlogCache->get_by_ID( $this->blog_ID );
		if ($current_Blog->allowcomments == 'never')
			return false;

		return true; // OK, user can comment!
	}


	/**
	 * Template function: display content of item
	 *
	 * Calling this with "MORE" (i-e displaying full content) will increase
	 * the view counter, except on special occasions, see {@link $hit_type}
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
		global $Plugins, $hit_type, $more, $preview;
		// echo $format,'-',$cut,'-',$dispmore,'-',$disppage;

		if( $more_link_text == '#' )
		{ // TRANS: this is the default text for the extended post "more" link
			$more_link_text = '=> '.T_('Read more!');
		}

		if( $more_anchor == '#' )
		{ // TRANS: this is the default text displayed once the more link has been activated
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

		/**
		 * Check if we want to increment view count, see {@link $hit_type}
		 */
		if( $dispmore && !$preview &&
				! in_array( $hit_type, array( 'badchar', 'reload', 'robot', 'preview', 'already_logged' ) ) )
		{ // Increment view counter
			$this->set_param( 'views', 'number', $this->views+1 );
			$this->dbupdate();  // move to end of method, if we should have more params to be changed someday
		}

		$content = $this->content;
		$numpages = 1;

		if( preg_match('/<!--nextpage-->/', $content ) )
		{ // This is a multipage post
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
		{ // This is an extended post (has a more section):
			if( $dispmore )
			{ // Viewer has already asked for more
				if( $stripteaser || preg_match('/<!--noteaser-->/', $content ) )
				{ // We want to strip the teaser:
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
		$post_renderers = $Plugins->validate_list( $this->renderers );
		$output = $Plugins->render( $output, $post_renderers, $format );

		// Character conversions
		$output = format_to_output( $output, $format );

		if( ($format == 'xml') && $cut )
		{ // Let's cut this down...
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
	 * Provide link to message form for this Item's author
	 *
	 * {@internal Item::msgform_link(-)}}
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function msgform_link( $form_url, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		global $img_url;

		if( empty($this->Author->email) )
		{ // We have no email for this Author :(
			return false;
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->Author->ID );
		$form_url = url_add_param( $form_url, 'post_id='.$this->ID );

		if( $text == '#' ) $text = '<img src="'.$img_url.'envelope.gif" height="10" width="13" class="middle" alt="'.T_('EMail').'" />';
		if( $title == '#' ) $title = T_('Send email to post author');

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
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


	/**
	 * Template function: Displays link to feedback page (under some conditions)
	 *
	 * {@internal Item::feedback_link(-)}}
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments (include %d for # of comments)
	 * @param string Link title
	 * @param boolean true to use a popup windows ('#' to use if comments_popup_windows() is there)
	 * @param boolean true to hide if no feedback ('#' for default)
	 * @param string 'pid' or 'title'
	 * @param string url to use
	 */
	function feedback_link( $type = 'feedbacks', $before = '', $after = '',
													$zero='#', $one='#', $more='#', $title='#',
													$use_popup = '#',	$hideifnone = '#', $mode = '', $blogurl='' )
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
				$current_Blog =& $BlogCache->get_by_ID( $this->blog_ID );
				if( ! $current_Blog->get( 'allowtrackbacks' ) )
				{ // Trackbacks not allowed on this blog:
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
				{ // Pingbacks not allowed on this blog:
					return;
				}
				if( $hideifnone == '#' ) $hideifnone = true;
				if( $title == '#' ) $title = T_('Display pingbacks');
				if( $zero == '#' ) $zero = T_('Pingback (0)');
				if( $one == '#' ) $one = T_('Pingback (1)');
				if( $more == '#' ) $more = T_('Pingbacks (%d)');
				break;

			default:
				die( "Unknown feedback type [$type]" );
		}

		if( $use_popup == '#' )
		{ // Use popups if javascript is included in page
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
		if( $use_popup ) echo ' onclick="b2open(this.href); return false"';
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
	 * Displays button for deleting the Item if user has proper rights
	 *
	 * {@internal Item::delete_link(-)}}
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string page url for the delete action
	 */
	function delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '',
												$button  = false, $actionurl = 'edit_actions.php?action=delete&amp;post=' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ! $current_User->check_perm( 'blog_del_post', 'any', false, $this->blog_ID ) )
		{ // User has right to delete this post
			return false;
		}

		if( $text == '#' ) $text = T_('Delete');
		if( $title == '#' ) $title = T_('Delete this post');

		$url = $admin_url.$actionurl.$this->ID;

		echo $before;
		if( $button )
		{ // Display as button
			echo '<input type="button"';
			echo ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
			/* TRANS: Warning this is a javascript string */
			echo T_('You are about to delete this post!\\n\\\'Cancel\\\' to stop, \\\'OK\\\' to delete.');
			echo '\') ) { document.location.href=\''.$url.'\' }"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '/>';
		}
		else
		{ // Display as link
			echo '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
			/* TRANS: Warning this is a javascript string */
			echo T_('You are about to delete this post!\\n\\\'Cancel\\\' to stop, \\\'OK\\\' to delete.');
			echo '\')"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '>'.$text.'</a>';
		}
		echo $after;

		return true;
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
	 * @param string class name
	 * @param string page url for the delete action
	 */
	function edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '',
											$actionurl = 'b2edit.php?action=edit&amp;post=' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ! $current_User->check_perm( 'blog_post_statuses', $this->status, false,
																			$this->blog_ID ) )
		{ // User has no right to edit this post
			return false;
		}

		if( $text == '#' ) $text = T_('Edit');
		if( $title == '#' ) $title = T_('Edit this post');

		echo $before;
		echo '<a href="'.$admin_url.$actionurl.$this->ID;
		echo '" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Provide link to publish a post if user has edit rights
	 *
	 * Note: publishing date will be updated
	 *
	 * {@internal Item::publish_link(-)}}
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ($this->status == 'published') // Already published!
			|| ! ($current_User->check_perm( 'blog_post_statuses', 'published', false, $this->blog_ID ))
			|| ! ($current_User->check_perm( 'edit_timestamp' ) ) )
		{ // User has no right to publish this post now:
			return false;
		}

		if( $text == '#' ) $text = T_('Publish NOW!');
		if( $title == '#' ) $title = T_('Publish now using current date and time.');

		echo $before;
		echo '<a href="'.$admin_url.'edit_actions.php?action=publish'.$glue.'post_ID='.$this->ID;
		echo '" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Template function: display list of priority options
	 *
	 * {@internal Item::priority_options(-)}}
	 */
	function priority_options()
	{
		$priorities = array(	1 => T_('1 - Highest'),
													2 => T_('2 - High'),
													3 => T_('3 - Medium'),
													4 => T_('4 - Low'),
													5 => T_('5 - Lowest') );

		$r = '';

		foreach( $priorities as $i => $name )
		{
			$r .= '<option value="'.$i.'"';
			if( $this->priority == $i )
			{
				$r .= ' selected="selected"';
			}
			$r .= '>'.$name.'</option>';
		}

		return $r;
	}


	/**
	 * Template function: display checkable list of renderers
	 *
	 * {@internal Item::renderer_checkboxes(-)}}
	 */
	function renderer_checkboxes()
	{
		global $Plugins;

		$Plugins->restart(); // make sure iterator is at start position

		$atLeastOneRenderer = false;

		while( $loop_RendererPlugin = $Plugins->get_next() )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;
			if( $loop_RendererPlugin->apply_when == 'stealth'
				|| $loop_RendererPlugin->apply_when == 'never' )
			{ // This is not an option.
				continue;
			}
			$atLeastOneRenderer = true;

			echo '<div>';

			echo '<input type="checkbox" class="checkbox" name="renderers[]" value="';
			$loop_RendererPlugin->code();
			echo '" id="';
			$loop_RendererPlugin->code();
			echo '"';

			switch( $loop_RendererPlugin->apply_when )
			{
				case 'always':
					// echo 'FORCED';
					echo ' checked="checked"';
					echo ' disabled="disabled"';
					break;

				case 'opt-out':
					if( in_array( $loop_RendererPlugin->code, $this->renderers ) // Option is activated
						|| in_array( 'default', $this->renderers ) ) // OR we're asking for default renderer set
					{
						// echo 'OPT';
						echo ' checked="checked"';
					}
					// else echo 'NO';
					break;

				case 'opt-in':
					if( in_array( $loop_RendererPlugin->code, $this->renderers ) ) // Option is activated
					{
						// echo 'OPT';
						echo ' checked="checked"';
					}
					// else echo 'NO';
					break;

				case 'lazy':
					// cannot select
					if( in_array( $loop_RendererPlugin->code, $this->renderers ) ) // Option is activated
					{
						// echo 'OPT';
						echo ' checked="checked"';
					}
					echo ' disabled="disabled"';
					break;
			}

			echo ' title="';
			$loop_RendererPlugin->short_desc();
			echo '" />'.
						'<label for="';
			$loop_RendererPlugin->code();
			echo '" title="';
			$loop_RendererPlugin->short_desc();
			echo '">';
			$loop_RendererPlugin->name();
			echo '</label>';
			echo "</div>\n";
		}

		if( !$atLeastOneRenderer )
		{
			echo T_('No renderer plugins are installed.');
		}
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
	 * Template function: display extra status of item
	 *
	 * {@internal Item::extra_status(-) }}
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function extra_status( $before = '', $after = '', $format = 'htmlbody' )
	{
		global $itemStatusCache;

		if( ! ($Element = $itemStatusCache->get_by_ID( $this->st_ID, false ) ) )
		{ // No status:
			return;
		}

		$extra_status = $Element->get('name');

		if( $format == 'raw' )
		{
			$this->disp( $extra_status, 'raw' );
		}
		else
		{
			echo $before.format_to_output( T_( $extra_status ), $format ).$after;
		}
	}

	/**
	 * Template function: display type of item
	 *
	 * {@internal Item::type(-) }}
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function type( $before = '', $after = '', $format = 'htmlbody' )
	{
		global $itemTypeCache;

		if( ! ($Element = $itemTypeCache->get_by_ID( $this->typ_ID, false) ) )
		{ // No status:
			return;
		}

		$extra_status = $Element->get('name');

		if( $format == 'raw' )
		{
			$this->disp( $extra_status, 'raw' );
		}
		else
		{
			echo $before.format_to_output( T_( $extra_status ), $format ).$after;
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
		{ // Nothing to display
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
			echo $htsrv_url.'trackback.php/'.$this->ID;
		}
		else
		{
			echo $htsrv_url.'trackback.php?tb_id='.$this->ID;
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
	 * Template function: Display the number of times the Item has been viewed
	 *
	 * Note: viewcount is incremented whenever the Item's content is displayed with "MORE"
	 * (i-e full content), see {@link Item::content()}
	 * Viewcount is NOT incremented on page reloads and other special cases, see {@link $hit_type}
	 *
	 * {@internal Item::views(-) }}
	 */
	function views()
	{
		echo $this->views;
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'main_cat_ID':
			case 'wordcount':
				$this->set_param( $parname, 'number', $parvalue, false );
				break;

			case 'typ_ID':
			case 'st_ID':
				$this->set_param( $parname, 'number', $parvalue, true );
				break;

			default:
				$this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Create a new Item/Post and insert it into the DB
	 *
	 * This funtion has to handle all needed DB dependencies!
	 *
	 * {@internal Item::insert(-)}}
	 *
	 * @TODO: cleanup the set() calls
	 */
	function insert(
		$author_user_ID,              // Author
		$post_title,
		$post_content,
		$post_timestamp,              // 'Y-m-d H:i:s'
		$main_cat_ID = 1,             // Main cat ID
		$extra_cat_IDs = array(),     // Table of extra cats
		$post_status = 'published',
		$post_locale = '#',
		$post_trackbacks = '',
		$autobr = 0,                  // OBSOLETE
		$pingsdone = true,
		$post_urltitle = '',
		$post_url = '',
		$post_comments = 'open',
		$post_renderers = array('default'),
		$item_typ_ID = 0,
		$item_st_ID = 0 )
	{
		global $DB, $query;
		global $localtimenow, $default_locale;

		if( $post_locale == '#' ) $post_locale = $default_locale;

		// Handle the flags:
		$post_flags = array();
		if( $pingsdone ) $post_flags[] = 'pingsdone';

		// make sure main cat is in extracat list and there are no duplicates
		$extra_cat_IDs[] = $main_cat_ID;
		$extra_cat_IDs = array_unique( $extra_cat_IDs );

		// TODO: START TRANSACTION

		// validate url title
		$post_urltitle = urltitle_validate( $post_urltitle, $post_title, 0, false, $this->dbprefix, $this->dbIDname );

		// echo 'INSERTING NEW POST ';

		$this->creator_user_ID = $author_user_ID;
		$this->lastedit_user_ID = $author_user_ID;
		$this->set( 'title', $post_title );
		$this->set( 'urltitle', $post_urltitle );
		$this->set( 'content', $post_content );
		$this->set( 'datestart', $post_timestamp );
		$this->set( 'datemodified', date('Y-m-d H:i:s',$localtimenow) );
		$this->set( 'main_cat_ID', $main_cat_ID );
		$this->set( 'status', $post_status );
		$this->set( 'locale', $post_locale );
		$this->set( 'url', $post_url );
		$this->set( 'flags', implode(',',$post_flags) );
		$this->set( 'wordcount', bpost_count_words($post_content) );
		$this->set( 'comments', $post_comments );
		$this->set( 'renderers', implode('.',$post_renderers) );
		$this->set( 'typ_ID', $item_typ_ID );
		$this->set( 'st_ID', $item_st_ID );

		$this->dbinsert();

		// insert new extracats
		$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
		foreach( $extra_cat_IDs as $extra_cat_ID )
		{
			// echo "extracat: $extra_cat_ID <br />";
			$query .= "( $this->ID, $extra_cat_ID ),";
		}
		$query = substr( $query, 0, strlen( $query ) - 1 );
		if( ! $DB->query( $query, 'Associate new post with extra categories' ) ) return 0;

		// TODO: END TRANSACTION

		return $this->ID;
	}


	/**
	 * Update a post and save to DB
	 *
	 * This funtion has to handle all needed DB dependencies!
	 *
	 * {@internal Item::update(-)}}
	 */
	function update(
		$post_title,
		$post_content,
		$post_timestamp = '',         // 'Y-m-d H:i:s'
		$main_cat_ID = 1,             // Main cat ID
		$extra_cat_IDs = array(),     // Table of extra cats
		$post_status = 'published',
		$post_locale = '#',
		$post_trackbacks = '',
		$autobr = 0,                  // OBSOLETE
		$pingsdone = true,
		$post_urltitle = '',
		$post_url = '',
		$post_comments = 'open',
		$post_renderers = array(),
		$item_typ_ID = 0,
		$item_st_ID = 0 )
	{
		global $DB, $query;
		global $localtimenow, $default_locale;

		// Handle the flags:
		$post_flags = array();
		if( $pingsdone ) $post_flags[] = 'pingsdone';

		// make sure main cat is in extracat list and there are no duplicates
		$extra_cat_IDs[] = $main_cat_ID;
		$extra_cat_IDs = array_unique( $extra_cat_IDs );

		// TODO: START TRANSACTION

		// validate url title
		$post_urltitle = urltitle_validate( $post_urltitle, $post_title, $this->ID, false, $this->dbprefix, $this->dbIDname );

		$this->set( 'title', $post_title );
		$this->set( 'urltitle', $post_urltitle );
		$this->set( 'url', $post_url );
		$this->set( 'content', $post_content );
		$this->set( 'datemodified', date('Y-m-d H:i:s', $localtimenow ) );
		$this->set( 'main_cat_ID', $main_cat_ID );
		$this->set( 'status', $post_status );
		$this->set( 'flags', implode( ',', $post_flags) );
		$this->set( 'wordcount', bpost_count_words($post_content) );
		$this->set( 'comments', $post_comments );
		$this->set( 'renderers', implode('.',$post_renderers) );
		$this->set( 'typ_ID', $item_typ_ID );
		$this->set( 'st_ID', $item_st_ID );
		if( $post_locale != '#' )
		{ // only update if it was changed
			$this->set( 'locale', $post_locale );
		}
		if( !empty($post_timestamp) )
		{
			$this->set( 'datestart', $post_timestamp );
		}

		// UPDATE DB:
		$this->dbupdate();

		// delete previous extracats
		$query = 'DELETE FROM T_postcats WHERE postcat_post_ID = '.$this->ID;
		$DB->query( $query );

		// insert new extracats
		$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
		foreach( $extra_cat_IDs as $extra_cat_ID )
		{
			//echo "extracat: $extracat_ID <br />";
			$query .= "( $this->ID, $extra_cat_ID ),";
		}
		$query = substr( $query, 0, strlen( $query ) - 1 );
		$DB->query( $query );

		// TODO: END TRANSACTION
	}


	/**
	 * Delete object from DB
	 *
	 * {@internal Item::dbdelete(-)}}
	 */
	function dbdelete( $del_comments = true )
	{
		global $DB;

		if( $this->ID == 0 ) die( 'Non persistant object cannot be deleted!' );

		// delete extracats
		$DB->query( "DELETE FROM T_postcats WHERE postcat_post_ID = $this->ID" );

		if( $del_comments )
		{ // delete comments
			$DB->query( "DELETE FROM T_comments WHERE comment_post_ID = $this->ID" );
		}

		// delete post
		$DB->query( "DELETE FROM $this->dbtablename WHERE $this->dbIDname = $this->ID" );

	}

}

/*
 * $Log$
 * Revision 1.19  2005/02/15 22:05:06  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.18  2005/01/25 15:07:22  fplanque
 * cleanup
 *
 * Revision 1.17  2005/01/20 20:38:58  fplanque
 * refactoring
 *
 * Revision 1.16  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.15  2005/01/05 02:51:10  blueyed
 * doc
 *
 * Revision 1.14  2005/01/04 12:44:41  fplanque
 * remerged
 *
 * Revision 1.12  2004/12/29 03:48:14  blueyed
 * fixed $actionurl for edit_link(); whitespace
 *
 * Revision 1.11  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.9  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.8  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.7  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.6  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.5  2004/12/13 21:29:58  fplanque
 * refactoring
 *
 * Revision 1.4  2004/12/10 19:45:55  fplanque
 * refactoring
 *
 * Revision 1.3  2004/10/17 20:18:37  fplanque
 * minor changes
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.66  2004/10/12 16:12:17  fplanque
 * Edited code documentation.
 *
 */
?>