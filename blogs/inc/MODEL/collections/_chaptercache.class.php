<?php
/**
 * This file implements the ChapterCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '/MODEL/generic/_genericcategorycache.class.php' );

/**
 * Blog Cache Class
 *
 * @package evocore
 */
class ChapterCache extends GenericCategoryCache
{

	/**
	 * Constructor
	 */
	function ChapterCache()
	{
		parent::GenericCategoryCache( 'GenericCategory', false, 'T_categories', 'cat_', 'cat_ID', 'cat_name' );
	}


	/**
	 * Load a keyed subset of the cache
	 *
 	 * @param integer|NULL NULL for all subsets
	 */
	function load_subset( $subset_ID )
	{
		global $DB, $Debuglog;

		if( isset( $this->loaded_subsets[$subset_ID] ) )
		{ // Already loaded
			return false;
		}

		// fp> TODO: we may need to support this!
		// $this->clear( true );

		$Debuglog->add( 'ChapterCache - Loading <strong>chapters('.$subset_ID.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
							FROM T_categories
						 WHERE cat_blog_ID = '.$subset_ID.'
						 ORDER BY cat_name';

		foreach( $DB->get_results( $sql ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_subsets[$subset_ID] = true;

		return true;
	}

}

/*
 * $Log$
 * Revision 1.1  2006/09/09 22:28:08  fplanque
 * ChapterCache Restricts categories to a specific blog
 *
 */
?>