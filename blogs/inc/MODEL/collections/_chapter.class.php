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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
		parent::load_from_Request();

/*		if( param( $this->dbprefix.'parent_ID', 'integer', -1 ) !== -1 )
		{
			$this->set_from_Request( 'parent_ID' );
		}
*/
		return ! param_errors_detected();
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
}


/*
 * $Log$
 * Revision 1.1  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 */
?>