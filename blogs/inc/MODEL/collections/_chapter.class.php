<?php
/**
 * This file implements the Chapter class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'MODEL/generic/_genericcategory.class.php' );


/**
 * Chapter Class
 *
 * @package evocore
 */
class Chapter extends GenericCategory
{
	/**
	 * @var integer
	 */
	var $blog_ID;
	/**
	 * The Blog of the Item (lazy filled, use {@link get_Blog()} to access it.
	 * @access protected
	 * @var Blog
	 */
	var $Blog;

	var $urlname;

	/**
	 * Lazy filled
	 * @var Chapter
	 */
	var $parent_Chapter;

	/**
	 * Constructor
	 *
	 * @param table Database row
 	 * @param integer|NULL subset to use for new object
	 */
	function Chapter( $db_row = NULL, $subset_ID = NULL )
	{
		// Call parent constructor:
		parent::GenericCategory( 'T_categories', 'cat_', 'cat_ID', $db_row );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'blog_ID', $subset_ID );
		}
		else
		{	// Wa are loading an object:
			$this->blog_ID = $db_row->cat_blog_ID;
			$this->urlname = $db_row->cat_urlname;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_request()
	{
		global $DB;

		parent::load_from_Request();

		// Check url name
		if( param_string_not_empty( 'cat_urlname', T_('Please enter an urlname.') ) )
		{
			$this->set_from_Request( 'urlname' );
			if( ! preg_match( '|^[A-Za-z0-9\-]+$|', $this->urlname )
					|| preg_match( '|^[A-Za-z][0-9]*$|', $this->urlname ) ) // This is to prevent conflict with things such as week number
			{
				param_error( 'cat_urlname', T_('The url name is invalid.') );
			}
			else
			{
    		if( Chapter::urlname_exists( $this->urlname, $this->ID ) )
				{ // urlname is already in use
					param_error( 'cat_urlname', T_('This URL name is already in use by another category. Please choose another name.') );
				}
			}
		}

		return ! param_errors_detected();
	}


	/**
	 * @static
	 */
	function urlname_exists( $urlname, $ignore_ID = 0 )
	{
		global $DB;

		return $DB->get_var( 'SELECT COUNT(*)
														 FROM T_categories
														WHERE cat_urlname = '.$DB->quote($urlname).'
															AND cat_ID <> '.$ignore_ID
													);
	}


	function & get_parent_Chapter()
	{
		if( ! isset( $this->parent_Chapter ) )
		{	// Not resoleved yet!
			if( empty( $this->parent_ID ) )
			{
				$this->parent_Chapter = NULL;
			}
			else
			{
				$ChapterCache = & get_Cache( 'ChapterCache' );
				$this->parent_Chapter = & $ChapterCache->get_by_ID( $this->parent_ID );
			}
		}

		return $this->parent_Chapter;
	}


	/**
	 * Get URL path (made of URL names) back to the root
	 */
	function get_url_path()
	{
		$r = $this->urlname.'/';

		$parent_Chapter = & $this->get_parent_Chapter();
		if( !is_null( $parent_Chapter ) )
		{	// Recurse:
			$r = $parent_Chapter->get_url_path().$r;
		}

		return $r;
	}


	/**
	 * Generate the URL to access the category.
	 *
	 * @param string|NULL 'param_num', 'subchap', 'chapters'
	 * @param string|NULL url to use
	 * @param string glue between url params
	 */
	function get_permanent_url( $link_type = NULL, $blogurl = NULL, $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings;

		if( empty( $link_type ) )
		{	// Use default from settings:
			$this->get_Blog();
			$link_type = $this->Blog->get_setting( 'chapter_links' );
		}

		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		switch( $link_type )
		{
			case 'param_num':
				return url_add_param( $blogurl, 'cat='.$this->ID, $glue );
				/* break; */

			case 'subchap':
				return url_add_tail( $blogurl, '/'.$this->urlname.'/' );
				/* break; */

			case 'chapters':
			default:
				return url_add_tail( $blogurl, '/'.$this->get_url_path() );
				/* break; */
		}
	}


	/**
	 * Get the Blog object for the Chapter.
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
	 * Load the Blog object for the Chapter, without returning it.
	 */
	function load_Blog()
	{
		if( is_null($this->Blog) )
		{
			$BlogCache = & get_Cache( 'BlogCache' );
			$this->Blog = & $BlogCache->get_by_ID( $this->blog_ID );
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		if( Chapter::urlname_exists( $this->urlname ) )
		{	// We gotta find another name:
			$sql = 'SELECT cat_urlname
								FROM T_categories
							 WHERE cat_urlname REGEXP "^'.$this->urlname.'(-[0-9]+)?$"';
			$highest_number = 0;
			foreach( $DB->get_results( $sql ) as $row )
			{
				if( preg_match( '/-([0-9]+)$/', $row->cat_urlname, $matches ) )
				{ // This one has a number, we extract it:
					$existing_number = (integer) $matches[1];
					if( $existing_number > $highest_number )
					{ // This is the new high
						$highest_number = $existing_number;
					}
				}
			}
			$this->set( 'urlname', $this->urlname.'-'.($highest_number+1) );
		}

		$r = parent::dbinsert();

		$DB->commit();

		return $r;
	}
}


/*
 * $Log$
 * Revision 1.7  2007/01/15 00:38:06  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 * Revision 1.6  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.5  2006/11/24 18:27:23  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.4  2006/09/11 20:53:33  fplanque
 * clean chapter paths with decoding, finally :)
 *
 * Revision 1.3  2006/09/10 23:35:56  fplanque
 * new permalink styles
 * (decoding not implemented yet)
 *
 * Revision 1.2  2006/09/10 19:32:32  fplanque
 * completed chapter URL name editing
 *
 * Revision 1.1  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 */
?>