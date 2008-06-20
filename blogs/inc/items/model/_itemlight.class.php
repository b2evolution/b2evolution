<?php
/**
 * This file implements the ItemLight class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class('_core/model/dataobjects/_dataobject.class.php');

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

 	var $title;

 	var $excerpt;

	var $urltitle;

	/**
	 * External URL the item links to (if any).
	 * @var string
	 */
	var $url;

 	var $ptyp_ID;

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
	 * This is set at instanciation.
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
	function ItemLight( $db_row = NULL, $dbtable = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'ItemLight',
	               $datecreated_field = '', $datemodified_field = 'datemodified',
	               $creator_field = '', $lasteditor_field = '' )
	{
		global $localtimenow, $default_locale, $current_User;

		// Call parent constructor:
		parent::DataObject( $dbtable, $dbprefix, $dbIDname, $datecreated_field, $datemodified_field,
												$creator_field, $lasteditor_field );

		$this->delete_restrictions = array(
				array( 'table'=>'T_links', 'fk'=>'link_dest_itm_ID', 'msg'=>T_('%d links to source items') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_parent_ID', 'msg'=>T_('%d links to child items') ),
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
			$this->title = $db_row->post_title;
			$this->excerpt = $db_row->post_excerpt;
			$this->ptyp_ID = $db_row->post_ptyp_ID;
			$this->url = $db_row->post_url;

			// Derived vars
			$ChapterCache = & get_Cache( 'ChapterCache' );
			$this->main_Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID );

			$this->blog_ID = $this->main_Chapter->blog_ID;
		}
	}


  /**
	 * Generate a single post link for the item
	 *
	 * @param boolean allow redir to permalink, true | false | 'auto' to prevent redit only if single isn't the current permalink type
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_single_url( $allow_redir = true, $blogurl = '', $glue = '&amp;' )
	{
		$this->get_Blog();

		if( empty( $blogurl ) )
		{
			$blogurl = $this->Blog->gen_blogurl();
		}

		$single_links = $this->Blog->get_setting('single_links');

 		if( !empty( $this->urltitle ) && $single_links != 'param_num' )
		{	// We can and we want to use the url title:
			$urlparam = 'title='.$this->urltitle;
			$urltail = $this->urltitle;
		}
		else
		{
			$urlparam = 'p='.$this->ID;
			$urltail = 'p'.$this->ID;
		}

		switch( $single_links )
		{
			case 'param_num':
			case 'param_title':
				$permalink = url_add_param( $blogurl, $urlparam.$glue.'more=1'.$glue.'c=1'.$glue.'tb=1'.$glue.'pb=1', $glue );
				break;

			case 'y':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/', $this->issue_date).$urltail );
				break;

			case 'ym':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/', $this->issue_date).$urltail );
				break;

			case 'ymd':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/d/', $this->issue_date).$urltail );
				break;

 			case 'subchap':
				$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->urlname.'/'.$urltail );
				break;

 			case 'chapters':
				$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->get_url_path().$urltail );
				break;

			case 'short':
			default:
				$permalink = url_add_tail( $blogurl, '/'.$urltail );
				break;
		}

		if( $allow_redir == 'auto' )
		{	// We allow redir only if the permalink is already single.
			// In other words: we implicitely allow redir if there is no need to redir!
			// and more useful: we explicitely prevent redir if we know it would take place.
			$allow_redir = ($this->Blog->get_setting( 'permalinks' ) == 'single');
		}

		if( ! $allow_redir )
		{
			$permalink = url_add_param( $permalink, 'redir=no', $glue );
		}

		return $permalink;
	}


  /**
	 * Generate a link to the post in the archives
	 *
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_archive_url( $blogurl = '', $glue = '&amp;' )
	{
		$this->get_Blog();

		if( empty( $blogurl ) )
		{
			$blogurl = $this->Blog->gen_blogurl();
		}

		$permalink = $this->Blog->get_archive_url( $this->issue_date, $glue );

		return $permalink.'#item_'.$this->ID;
	}


  /**
	 * Generate a link to the post in the category
	 *
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_chapter_url( $blogurl = '', $glue = '&amp;' )
	{
		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		$permalink = url_add_tail( $blogurl, '/'.$this->main_Chapter->get_url_path() );

		return $permalink.'#item_'.$this->ID;
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
	 * @param string single, archive, subchap
	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_permanent_url( $permalink_type = '', $blogurl = '', $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings;

		if( $this->ptyp_ID == 1000 )
		{	// Page: force use of single url:
			$permalink_type = 'single';
		}
		elseif( empty( $permalink_type ) )
		{ // Use default from collection settings:
			$this->get_Blog();
			$permalink_type = $this->Blog->get_setting( 'permalinks' );
		}

		switch( $permalink_type )
		{
			case 'archive':
				return $this->get_archive_url( $blogurl, $glue );

			case 'subchap':
				return $this->get_chapter_url( $blogurl, $glue );

			case 'single':
			default:
				return $this->get_single_url( true, $blogurl, $glue );
		}
	}


	/**
	 * Template function: list all the category names
	 *
	 * @param string Output format for each cat, see {@link format_to_output()}
	 */
	function categories( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'          => ' ',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'before_main'     => '',       // string fo display before the MAIN category,
				'after_main'      => '',       // string fo display after the MAIN category
				'before_other'    => '',       // string fo display before OTHER categories
				'after_other'     => '',       // string fo display after OTHER categories
				'before_external' => '<em>',   // string fo display before EXTERNAL categories
				'after_external'  => '</em>',  // string fo display after EXTERNAL categories,
				'separator'       => ', ',
				'link_categories' => true,
				'link_title'      => '#',
				'format'          => 'htmlbody',
			), $params );


		if( $params['link_title'] == '#' )
		{ /* TRANS: When the categories for a specific post are displayed, the user can click
					on these cats to browse them, this is the default href title displayed there */
			$params['link_title'] = T_('Browse category');
		}

		$categoryNames = array();
		foreach( $this->get_Chapters() as $Chapter )
		{
			$cat_name = $Chapter->dget( 'name' );

			if( $params['link_categories'] )
			{ // we want to display links
				$lBlog = & $Chapter->get_Blog();
				$cat_name = '<a href="'.$Chapter->get_permanent_url().'" title="'.htmlspecialchars($params['link_title']).'">'.$cat_name.'</a>';
			}

			if( $Chapter->ID == $this->main_cat_ID )
			{ // We are displaying the main cat!
				if( !$params['include_main'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_main'].$cat_name.$params['after_main'];
			}
			elseif( $Chapter->blog_ID == $this->blog_ID )
			{ // We are displaying another cat in the same blog
				if( !$params['include_other'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_other'].$cat_name.$params['after_other'];
			}
			else
			{ // We are displaying an external cat (in another blog)
				if( !$params['include_external'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_external'].$cat_name.$params['after_external'];
			}

			$categoryNames[] = $cat_name;
		}

		echo $params['before'];
		echo format_to_output( implode( $params['separator'], $categoryNames ), $params['format'] );
 		echo $params['after'];
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
	 */
	function get_issue_date( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'date_format' => '#',
				'use_GMT'     => false,
			), $params );

		if( $params['date_format'] == '#' )
		{
			$params['date_format'] = locale_datefmt();
		}

		return $params['before'].mysql2date( $params['date_format'], $this->issue_date, $params['use_GMT'] ).$params['after'];
	}


	/**
	 * Template function: display issue date (datetime) of Item
	 *
	 */
	function issue_date( $params = array() )
	{
		echo $this->get_issue_date( $params );
	}


	/**
	 * Template function: display issue time (datetime) of Item
	 *
	 */
	function issue_time( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'time_format' => '#',
			), $params );

		if( !isset($params['date_format']) )
		{
			$params['date_format'] = $params['time_format'];
		}

		if( $params['date_format'] == '#' )
		{
			$params['date_format'] = locale_timefmt();
		}

		echo $this->get_issue_date( $params );
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
	 * Template tag
	 */
	function locale_flag( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'collection'  => 'h10px',
				'format'      => 'htmlbody',
				'class'       => 'flag',
				'align'       => '',
			), $params );

		echo $params['before'];
		echo locale_flag( $this->locale, $params['collection'], $params['class'], $params['align'] );
		echo $params['after'];
	}


	/**
	 * Template function: Temporarily switch to this post's locale
	 */
	function locale_temp_switch()
	{
		locale_temp_switch( $this->locale );
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
				$text = get_icon( 'permalink', 'imgtag', array('class'=>'icon') ).T_('Permalink');
				break;

			case '#icon#':
				$text = get_icon( 'permalink', 'imgtag', array('class'=>'icon') );
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
	 * @param string link text or special value:
	 * @param string link title
	 * @param string class name
	 */
	function permanent_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'text'        => '#',	// possible special values: '#', '#icon#', '#text#', '#title#'
				'title'       => '#',
				'class'       => '',
			//	'format'      => 'htmlbody',
			), $params );

		$link = $this->get_permanent_link( $params['text'], $params['title'], $params['class'] );

		if( !empty( $link ) )
		{
			echo $params['before'];
			echo $link;
			echo $params['after'];
		}
	}


	/**
	 * Template function: display title for item and link to related URL
	 */
	function title( $params = array() )
	{
		echo $this->get_title($params);
	}


	/**
	 * Get "nice" title of the Item
	 * @return string
	 */
	function get_title( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'format'      => 'htmlbody',
				'link_type'   => '#',
			), $params );

		$title = format_to_output( $this->title, $params['format'] );

		if( empty( $title ) )
		{
			return;
		}

		if( $params['link_type'] == '#' )
		{	// Use default link type from settings:
			$this->get_Blog();
			$params['link_type'] = $this->Blog->get_setting( 'title_link_type' );
		}

		switch( $params['link_type'] )
		{
			case 'permalink':
				$url = $this->get_permanent_url();
				break;

			case 'linkto_url':
				$url = $this->url;
				break;

			case 'admin_view':
				$url = '?ctrl=items&amp;blog='.$this->blog_ID.'&amp;p='.$this->ID;
				break;

			case 'none':
			default:
		}

		$r = $params['before'];
		if( !empty($url) )
		{
			$r .= '<a href="'.$url.'">'.$title.'</a>';
		}
		else
		{
			$r .= $title;
		}
		$r .= $params['after'];
		return $r;
	}


	/**
	 * Template function: display type of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function type( $before = '', $after = '', $format = 'htmlbody' )
	{
		$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
		$Element = & $ItemTypeCache->get_by_ID( $this->ptyp_ID, true, false );
		if( !$Element )
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
	 * Template function: get excerpt
	 *
	 * @todo do we want excerpts in itemLight or not?
	 *
	 * @param string filename to use to display more
	 * @return string
	 */
	function get_excerpt( $format = 'htmlbody' )
	{
		// Character conversions
		return format_to_output( $this->excerpt, $format );
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

			case 'ptyp_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

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
 * Revision 1.9  2008/06/20 01:22:04  blueyed
 * Add ItemLight::get_title(). Make ItemLight::title() use this.
 *
 * Revision 1.8  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.6  2007/11/03 23:54:38  fplanque
 * skin cleanup continued
 *
 * Revision 1.5  2007/11/03 21:04:27  fplanque
 * skin cleanup
 *
 * Revision 1.4  2007/11/03 04:56:04  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.3  2007/09/09 12:51:58  fplanque
 * cleanup
 *
 * Revision 1.2  2007/09/09 09:15:59  yabs
 * validation
 *
 * Revision 1.1  2007/06/25 11:00:26  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/06/21 00:44:37  fplanque
 * linkblog now a widget
 *
 * Revision 1.8  2007/05/14 02:47:23  fplanque
 * (not so) basic Tags framework
 *
 * Revision 1.7  2007/05/13 22:53:31  fplanque
 * allow feeds restricted to post excerpts
 *
 * Revision 1.6  2007/05/13 22:02:07  fplanque
 * removed bloated $object_def
 *
 * Revision 1.5  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.4  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.3  2007/03/24 20:41:16  fplanque
 * Refactored a lot of the link junk.
 * Made options blog specific.
 * Some junk still needs to be cleaned out. Will do asap.
 *
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
