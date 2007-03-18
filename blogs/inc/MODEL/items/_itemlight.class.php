<?php
/**
 * This file implements the ItemLight class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';


/**
 * ItemLight Class
 *
 * Basically, all we want to achieve here is:
 * - permalinks
 * - last mod dates
 *
 * This object SHOULD NOT be saved.
 *
 * @package evocore
 */
class ItemLight extends DataObject
{
	/**
	 * Publish date ("Y-m-d H:i:s"). This may be in the future.
	 * This should get compared to {@link $localtimenow}.
	 * @var string
	 */
	var $issue_date;
	var $mod_date;

	var $urltitle;

	/**
	 * @var integer
	 */
	var $main_cat_ID = 0;
	/**
	 * @var Chapter
	 */
	var $main_Chapter;

	/**
	 * Derived from $main_cat_ID
	 *
	 * @var integer
	 */
	var $blog_ID;
	/**
	 * The Blog of the Item (lazy filled, use {@link get_Blog()} to access it.
	 * @access protected
	 * @var Blog
	 */
	var $Blog;


	/**
	 * Constructor
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
	function ItemLight( $db_row = NULL, $dbtable = 'T_posts', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'ItemLight',
	               $datecreated_field = '', $datemodified_field = 'datemodified',
	               $creator_field = '', $lasteditor_field = '' )
	{
		global $object_def, $localtimenow, $default_locale, $current_User;

		// Call parent constructor:
		parent::DataObject( $dbtable, $dbprefix, $dbIDname, $datecreated_field, $datemodified_field,
												$creator_field, $lasteditor_field );

		$this->delete_restrictions = array(
				array( 'table'=>'T_links', 'fk'=>'link_dest_itm_ID', 'msg'=>T_('%d links to source items') ),
				array( 'table'=>'T_posts', 'fk'=>'post_parent_ID', 'msg'=>T_('%d links to child items') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_links', 'fk'=>'link_itm_ID', 'msg'=>T_('%d links to destination items') ),
				array( 'table'=>'T_postcats', 'fk'=>'postcat_post_ID', 'msg'=>T_('%d links to extra categories') ),
				array( 'table'=>'T_comments', 'fk'=>'comment_post_ID', 'msg'=>T_('%d comments') ),
			);

		$this->objtype = $objtype;

		if( $db_row == NULL )
		{ // New item:
			$this->ID = 0;
			$this->set( 'issue_date', date('Y-m-d H:i:s', $localtimenow) );
		}
		else
		{
			$this->ID = $db_row->$dbIDname;
			$this->datemodified = $db_row->post_datemodified; // Needed for history display
			$this->issue_date = $db_row->post_datestart;
			$this->mod_date = $db_row->post_datemodified;
			$this->main_cat_ID = $db_row->post_main_cat_ID;
			$this->urltitle = $db_row->post_urltitle;

			// Derived vars
			$ChapterCache = & get_Cache( 'ChapterCache' );
			$this->main_Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID );

			$this->blog_ID = $this->main_Chapter->blog_ID;
		}
	}


	/**
	 * Generate the permalink for the item.
	 *
	 * Note: Each item has an unique permalink at any given time.
	 * Some admin settings may however change the permalinks for previous items.
	 * Note: This actually only returns the URL, to get a real link, use {@link Item::get_permanent_link()}
	 *
	 * @todo archives modes in clean URL mode
	 *
	 * @param string 'urltitle', 'pid', 'archive#id', 'archive#title' or '' to use default setting
	 * @param string url to use
	 * @param boolean true to force single post on destination page
	 * @param string glue between url params
	 */
	function get_permanent_url( $permalink_type = '', $blogurl = '', $force_single = false, $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings;

		if( empty( $permalink_type ) )
		{	// Use default from settings:
			$permalink_type = $Settings->get( 'permalink_type' );
		}

		if( $force_single && (strpos( $permalink_type, 'archive' ) !== false) )
		{ // We don't want a page full of posts:
			$permalink_type = 'force_single';
		}

		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		$post_date = $this->issue_date;

		switch( $permalink_type )
		{
			case 'archive#id':
				// Link to an archive page:
				// Determine type of archive page:
				$this->get_Blog();
				$dest_type = $this->Blog->get_setting('archive_mode');
				$anchor = $this->ID;
				$urltail = 'p'.$this->ID;
				break;

			case 'archive#title':
				// Link to an archive page:
				// Determine type of archive page:
				$this->get_Blog();
				$dest_type = $this->Blog->get_setting('archive_mode');
				$anchor = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $this->title );
				$urltail = 'p'.$this->ID;
				break;

			case 'force_single':
				// Forced Link to individual post:
				$dest_type = 'postbypost';
				$urlparam = 'p='.$this->ID.'&amp;redir=no';
				$urltail = 'p'.$this->ID.'?redir=no';
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

		switch( $dest_type )
		{
			case 'monthly':
				// Link to a monthly archive page:
				if( $Settings->get('links_extrapath') == 'disabled' )
				{ // Use params:
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2), $glue ).'#'.$anchor;
				}
				else
				{ // Use extra path info:
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m/", $post_date) ).'#'.$anchor;
				}
				break;

			case 'weekly':
				// Link to a weekly archive page:
				if((!isset($cacheweekly)) || (empty($cacheweekly[$post_date])))
				{
					$cacheweekly[$post_date] = $DB->get_var( 'SELECT '.$DB->week( $DB->quote($post_date), locale_startofweek() ) );
				}
				if( $Settings->get('links_extrapath') == 'disabled' )
				{ // Use params:
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).$glue.'w='.$cacheweekly[$post_date], $glue ).'#'.$anchor;
				}
				else
				{ // Use extra path info:
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/", $post_date).'w'.$cacheweekly[$post_date] ).'/#'.$anchor;
				}
				break;

			case 'daily':
				// Link to a daily archive page:
				if( $Settings->get('links_extrapath') == 'disabled' )
				{ // Use params:
					$permalink = url_add_param( $blogurl, 'm='.substr($post_date,0,4).substr($post_date,5,2).substr($post_date,8,2), $glue ).'#'.$anchor;
				}
				else
				{ // Use extra path info:
					$permalink = url_add_tail( $blogurl, mysql2date("/Y/m/d/", $post_date) ).'#'.$anchor;
				}
				break;

			case 'postbypost':
			default:
				// Link to a specific post:
				switch( $Settings->get('links_extrapath') )
				{
					case 'disabled':
						// Use params:
						$permalink = url_add_param( $blogurl, $urlparam.$glue.'more=1'.$glue.'c=1'.$glue.'tb=1'.$glue.'pb=1', $glue );
						break;

					case 'short':
						$permalink = url_add_tail( $blogurl, '/'.$urltail );
						break;

					case 'y':
						$permalink = url_add_tail( $blogurl, mysql2date('/Y/', $post_date).$urltail );
						break;

					case 'ym':
						$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/', $post_date).$urltail );
						break;

					case 'ymd':
						$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/d/', $post_date).$urltail );
						break;

 					case 'subchap':
						$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->urlname.'/'.$urltail );
						break;

 					case 'chapters':
						$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->get_url_path().$urltail );
						break;

					default:
						debug_die('extra path mode not supported (yet)');
				}
				break;
		}

		return $permalink;
	}


	/**
	 * Template function: list all the category names
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
			$before_main='', $after_main='',
			$before_other='', $after_other='',
			$before_external='<em>', $after_external='</em>',
			$separator = ', ',
			$format = 'htmlbody'
		)
	{
		if( $link_title == '#' )
		{ /* TRANS: When the categories for a specific post are displayed, the user can click
					on these cats to browse them, this is the default href title displayed there */
			$link_title = T_('Browse category');
		}

		$categoryNames = array();
		foreach( $this->get_Chapters() as $Chapter )
		{
			$cat_name = $Chapter->dget( 'name' );

			if( !empty($link_title) )
			{ // we want to display links
				$lBlog = & $Chapter->get_Blog();
				$cat_name = '<a href="'.$Chapter->get_permanent_url().'" title="'.htmlspecialchars($link_title).'">'.$cat_name.'</a>';
			}

			if( $Chapter->ID == $this->main_cat_ID )
			{ // We are displaying the main cat!
				if( $before_main == 'hide' )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $before_main.$cat_name.$after_main;
			}
			elseif( $Chapter->blog_ID == $this->blog_ID )
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

		echo format_to_output( implode( $separator, $categoryNames ), $format);
	}


	/**
	 * Template function: display main category name
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function main_category( $format = 'htmlbody' )
	{
		$Chapter = & $this->get_main_Chapter();
		$Chapter->disp( 'name', $format );
	}


	/**
	 * Get list of Chapter objects.
	 *
	 * @return array of {@link Chapter chapters} (references)
	 */
	function get_Chapters()
	{
		global $cache_postcats;

		$ChapterCache = & get_Cache( 'ChapterCache' );

		// Load cache for category associations with current posts
		cat_load_postcats_cache();

		if( isset($cache_postcats[$this->ID]) )
		{ // dh> may not be set! (demo logs)
			$categoryIDs = $cache_postcats[$this->ID];
		}
		else $categoryIDs = array();

		$chapters = array();
		foreach( $categoryIDs as $cat_ID )
		{
			$chapters[] = & $ChapterCache->get_by_ID( $cat_ID );
		}

		return $chapters;
	}


	/**
	 * Get the main Chapter.
	 *
	 * @return Chapter
	 */
	function & get_main_Chapter()
	{
		$ChapterCache = & get_Cache( 'ChapterCache' );
		/**
		 * @var Chapter
		 */
		return $ChapterCache->get_by_ID( $this->main_cat_ID );
	}


	/**
	 * returns issue date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function get_issue_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			$format = locale_datefmt();

		return mysql2date( $format, $this->issue_date, $useGM);
	}


	/**
	 * Template function: display issue date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function issue_date( $format = '', $useGM = false )
	{
		echo $this->get_issue_date( $format, $useGM );
	}


	/**
	 * Template function: display issue time (datetime) of Item
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
	 */
	function lang()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template function: display locale for item
	 */
	function locale()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template function: display language name for item
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
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function mod_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->mod_date, $useGM );
		else
			echo mysql2date( $format, $this->mod_date, $useGM );
	}


	/**
	 * Template function: display last mod time (datetime) of Item
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
	 * Note: This actually only outputs the URL, to display a real link, use {@link Item::permanent_link()}
	 *
	 * @param string 'post', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permanent_url( $mode = '', $blogurl='' )
	{
		echo $this->get_permanent_url( $mode, $blogurl );
	}


	/**
	 * Returns a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::get_permanent_url()}
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#', '#title#' '... $title$ ...'
	 * @param string link title
	 * @param string class name
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '' )
	{
		global $current_User;

		switch( $text )
		{
			case '#':
				$text = get_icon( 'permalink' ).T_('Permalink');
				break;

			case '#icon#':
				$text = get_icon( 'permalink' );
				break;

			case '#text#':
				$text = T_('Permalink');
				break;

			case '#title#':
				$text = format_to_output( $this->title );
				break;
		}

		if( $title == '#' ) $title = T_('Permanent link to full entry');

		$url = $this->get_permanent_url();

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.str_replace( '$title$', format_to_output( $this->title ), $text ).'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::permanent_url()}
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#', '#title#'
	 * @param string link title
	 * @param string class name
	 */
	function permanent_link( $text = '#', $title = '#', $class = '' )
	{
		echo $this->get_permanent_link( $text, $title, $class );
	}


	/**
	 * Template function: display title for item and link to related URL
	 *
	 * @param string String to display before the title if there is something to display
	 * @param string String to display after the title if there is something to display
	 * @param boolean false if you don't want to link to related URL (if applicable)
	 * @param string Output format, see {@link format_to_output()}
	 */
	function title(
		$before = '',        // HTML/text to be displayed before title
		$after = '',         // HTML/text to be displayed after title
		$add_link = true,    // Add li  nk to this title?
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
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @todo extra_cat_IDs recording
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'main_cat_ID':
				$r = $this->set_param( 'main_cat_ID', 'number', $parvalue, false );
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				// Update derived property:
				$this->blog_ID = get_catblog( $this->main_cat_ID ); // This is a derived var
				return $r;

			case 'extra_cat_IDs':
				// ARRAY! We do not record this change (yet)
				$this->extra_cat_IDs = $parvalue;
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				break;

			case 'issue_date':
			case 'datestart':
				$this->issue_date = $parvalue;
				return $this->set_param( 'datestart', 'date', $parvalue, false );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get the Blog object for the Item.
	 *
	 * @return Blog
	 */
	function & get_Blog()
	{
		if( is_null($this->Blog) )
		{
			$this->load_Blog();
		}

		return $this->Blog;
	}


	/**
	 * Load the Blog object for the Item, without returning it.
	 *
	 * This is needed for {@link Results} object callbacks.
	 */
	function load_Blog()
	{
		if( is_null($this->Blog) )
		{
			$BlogCache = & get_Cache( 'BlogCache' );
			$this->Blog = & $BlogCache->get_by_ID( $this->blog_ID );
		}
	}

}


/*
 * $Log$
 * Revision 1.2  2007/03/18 03:49:20  fplanque
 * fix
 *
 * Revision 1.1  2007/03/18 03:43:19  fplanque
 * EXPERIMENTAL
 * Splitting Item/ItemLight and ItemList/ItemListLight
 * Goal: Handle Items with less footprint than with their full content
 * (will be even worse with multiple languages/revisions per Item)
 *
 * Revision 1.162  2007/03/11 23:57:07  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.161  2007/03/06 12:18:08  fplanque
 * got rid of dirty Item::content()
 * Advantage: the more link is now independant. it can be put werever people want it
 *
 * Revision 1.160  2007/03/05 04:52:42  fplanque
 * better precision for viewcounts
 *
 * Revision 1.159  2007/03/05 04:49:17  fplanque
 * better precision for viewcounts
 *
 * Revision 1.158  2007/03/05 02:13:26  fplanque
 * improved dashboard
 *
 * Revision 1.157  2007/03/05 01:47:50  fplanque
 * splitting up Item::content() - proof of concept.
 * needs to be optimized.
 *
 * Revision 1.156  2007/03/03 01:14:11  fplanque
 * new methods for navigating through posts in single item display mode
 *
 * Revision 1.155  2007/03/02 04:40:38  fplanque
 * fixed/commented a lot of stuff with the feeds
 *
 * Revision 1.154  2007/03/02 03:09:12  fplanque
 * rss length doesn't make sense since it doesn't apply to html format anyway.
 * clean solutionwould be to handle an "excerpt" field separately
 *
 * Revision 1.153  2007/02/23 19:16:07  blueyed
 * MFB: Fixed handling of Item::content for pre-rendering (it gets passed by reference!)
 *
 * Revision 1.152  2007/02/18 22:51:26  waltercruz
 * Fixing a little confusion with quotes and string concatenation
 *
 * Revision 1.151  2007/02/08 03:45:40  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.150  2007/02/05 13:32:49  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.149  2007/01/26 04:52:53  fplanque
 * clean comment popups (skins 2.0)
 *
 * Revision 1.148  2007/01/26 02:12:06  fplanque
 * cleaner popup windows
 *
 * Revision 1.147  2007/01/23 03:46:24  fplanque
 * cleaned up presentation
 *
 * Revision 1.146  2007/01/19 10:45:42  fplanque
 * images everywhere :D
 * At this point the photoblogging code can be considered operational.
 *
 * Revision 1.145  2007/01/11 19:29:50  blueyed
 * Fixed E_NOTICE when using the "excerpt" feature
 *
 * Revision 1.144  2006/12/26 00:08:29  fplanque
 * wording
 *
 * Revision 1.143  2006/12/21 22:35:28  fplanque
 * No regression. But a change in usage. The more link must be configured in the skin.
 * Renderers cannot side-effect on the more tag any more and that actually makes the whole thing safer.
 *
 * Revision 1.142  2006/12/20 13:57:34  blueyed
 * TODO about regression because of pre-rendering and the <!--more--> tag
 *
 * Revision 1.141  2006/12/18 13:31:12  fplanque
 * fixed broken more tag
 *
 * Revision 1.140  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.139  2006/12/15 22:59:05  fplanque
 * doc
 *
 * Revision 1.138  2006/12/14 22:26:31  blueyed
 * Fixed E_NOTICE and displaying of pings into $Messages (though "hackish")
 *
 * Revision 1.137  2006/12/12 02:53:56  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.136  2006/12/07 23:13:11  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.135  2006/12/06 23:55:53  fplanque
 * hidden the dead body of the sidebar plugin + doc
 *
 * Revision 1.134  2006/12/05 14:28:29  blueyed
 * Fixed wordcount==0 handling; has been saved as NULL
 *
 * Revision 1.133  2006/12/05 06:38:40  blueyed
 * doc
 *
 * Revision 1.132  2006/12/05 00:39:56  fplanque
 * fixed some more permalinks/archive links
 *
 * Revision 1.131  2006/12/05 00:34:39  blueyed
 * Implemented custom "None" option text in DataObjectCache; Added for $ItemStatusCache, $GroupCache, UserCache and BlogCache; Added custom text for Item::priority_options()
 *
 * Revision 1.130  2006/12/04 20:52:40  blueyed
 * typo
 *
 * Revision 1.129  2006/12/04 19:57:58  fplanque
 * How often must I fix the weekly archives until they stop bugging me?
 *
 * Revision 1.128  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.127  2006/12/03 18:15:32  fplanque
 * doc
 *
 * Revision 1.126  2006/12/01 20:04:31  blueyed
 * Renamed Plugins_admin::validate_list() to validate_renderer_list()
 *
 * Revision 1.125  2006/12/01 19:46:42  blueyed
 * Moved Plugins::validate_list() to Plugins_admin class; added stub in Plugins, because at least the starrating_plugin uses it
 *
 * Revision 1.124  2006/11/28 20:04:11  blueyed
 * No edit link, if ID==0 to avoid confusion in preview, see http://forums.b2evolution.net/viewtopic.php?p=47422#47422
 *
 * Revision 1.123  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.122  2006/11/22 20:48:58  blueyed
 * Added Item::get_Chapters() and Item::get_main_Chapter(); refactorized
 *
 * Revision 1.121  2006/11/22 20:12:18  blueyed
 * Use $format param in Item::categories()
 *
 * Revision 1.120  2006/11/19 22:17:42  fplanque
 * minor / doc
 *
 * Revision 1.119  2006/11/19 16:07:31  blueyed
 * Fixed saving empty renderers list. This should also fix the saving of "default" instead of the explicit renderer list
 *
 * Revision 1.118  2006/11/17 18:36:23  blueyed
 * dbchanges param for AfterItemUpdate, AfterItemInsert, AfterCommentUpdate and AfterCommentInsert
 *
 * Revision 1.117  2006/11/13 20:49:52  fplanque
 * doc/cleanup :/
 *
 * Revision 1.116  2006/11/10 20:14:11  blueyed
 * doc, fix
 *
 * Revision 1.115  2006/11/02 16:12:49  blueyed
 * MFB
 *
 * Revision 1.114  2006/11/02 16:01:00  blueyed
 * doc
 *
 * Revision 1.113  2006/10/29 18:33:23  blueyed
 * doc fix
 *
 * Revision 1.112  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.111  2006/10/18 00:03:51  blueyed
 * Some forgotten url_rel_to_same_host() additions
 */
?>