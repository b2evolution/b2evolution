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

load_class( 'MODEL/generic/_genericcategorycache.class.php' );
load_class( 'MODEL/collections/_chapter.class.php' );

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
		parent::GenericCategoryCache( 'Chapter', false, 'T_categories', 'cat_', 'cat_ID', 'cat_name', 'blog_ID' );
	}


	/**
	 * Get an object from cache by ID
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param integer ID of object to load
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
 	 * @param integer|NULL NULL for all subsets
	 * @return reference on cached object
	 */
	function & get_by_ID( $req_ID, $halt_on_error = true, $halt_on_empty = true, $subset_ID = NULL )
	{
		global $DB, $Debuglog;

		if( empty($req_ID) )
		{
			if($halt_on_empty)
			{
				debug_die( "Requested Chapter from $this->dbtablename without ID!" );
			}
			$r = NULL;
			return $r;
		}

		if( !empty( $this->cache[ $req_ID ] ) )
		{ // Already in cache
			$Debuglog->add( "Accessing Chapter($req_ID) from cache", 'dataobjects' );
			return $this->cache[ $req_ID ];
		}
		elseif( !$this->all_loaded )
		{ // Not in cache, but not everything is loaded yet
			if( $this->load_all || is_null($subset_ID) )
			{ // It's ok to just load everything:
				$this->load_all();
			}
			else
			{ // Load just the requested object:
				$Debuglog->add( "Loading <strong>$this->objtype($req_ID)</strong> into cache", 'dataobjects' );
				// Note: $req_ID MUST be an unsigned integer. This is how DataObject works.
				$sql = "SELECT *
				          FROM $this->dbtablename
				         WHERE $this->dbIDname = $req_ID
				           AND cat_blog_ID = ".$subset_ID;

				if( $row = $DB->get_row( $sql, OBJECT, 0, 'ChapterCache::get_by_ID()' ) )
				{
					if( ! $this->instantiate( $row ) )
					{
						$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
					}
				}
				else
				{
					$Debuglog->add( 'Could not get DataObject by ID. Query: '.$sql, 'dataobjects' );
				}
			}
		}

		if( empty( $this->cache[ $req_ID ] ) )
		{ // Requested object does not exist
			// $Debuglog->add( 'failure', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( "Requested $this->objtype does not exist!" );
			}
			$r = false;
			return $r;
		}

		return $this->cache[ $req_ID ];
	}


	/**
	 * Load a keyed subset of the cache
	 *
 	 * @param integer|NULL NULL for all subsets
	 */
	function load_subset( $subset_ID )
	{
		global $DB, $Debuglog;

		if( $this->all_loaded || isset( $this->loaded_subsets[$subset_ID] ) )
		{ // Already loaded
			return false;
		}

		// fp> TODO: This kills other subsets. BAD if we want to handle multiple subsets independently
		$this->clear( true );

		$Debuglog->add( 'ChapterCache - Loading <strong>chapters('.$subset_ID.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
							FROM T_categories
						 WHERE cat_blog_ID = '.$subset_ID.'
						 ORDER BY cat_name';

		foreach( $DB->get_results( $sql, OBJECT, 'Loading chapters('.$subset_ID.') into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_subsets[$subset_ID] = true;

		return true;
	}


	/**
	 * Instanciate a new object within this cache
 	 *
 	 * @param object|NULL
 	 * @param integer|NULL subset to use for new object
	 */
	function & new_obj( $row = NULL, $subset_ID = NULL )
	{
		// Instantiate a custom object
		$Chapter = new Chapter( $row, $subset_ID ); // Copy

		return $Chapter;
	}
}

/*
 * $Log$
 * Revision 1.5  2006/09/11 19:34:34  fplanque
 * fully powered the ChapterCache
 *
 * Revision 1.4  2006/09/10 23:35:56  fplanque
 * new permalink styles
 * (decoding not implemented yet)
 *
 * Revision 1.3  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 * Revision 1.2  2006/09/10 00:16:53  fplanque
 * cleaned up a lot of MB's crap
 * + allowed moving chapters inside of blog
 *
 * Revision 1.1  2006/09/09 22:28:08  fplanque
 * ChapterCache Restricts categories to a specific blog
 *
 */
?>