<?php
/**
 * This file implements the SkinCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'skins/model/_skin.class.php', 'Skin' );

load_funcs( 'skins/_skin.funcs.php' );

/**
 * Skin Cache Class
 *
 * @package evocore
 */
class SkinCache extends DataObjectCache
{
	/**
	 * Cache by folder
	 * @var array
	 */
	var $cache_by_folder = array();

	var $loaded_types = array();


	/**
	 * Constructor
	 */
	function SkinCache()
	{
		parent::DataObjectCache( 'Skin', false, 'T_skins__skin', 'skin_', 'skin_ID', 'skin_name', NULL,
			/* TRANS: "None" select option */ T_('No skin') );
	}


	/**
	 * Add object to cache, handling our own indices.
	 *
	 * @param Skin
	 * @return boolean True on add, false if already existing.
	 */
	function add( & $Skin )
	{
		$this->cache_by_folder[ $Skin->folder ] = & $Skin;

		return parent::add( $Skin );
	}


	/**
	 * Get an object from cache by its folder name.
	 *
	 * Load the object into cache, if necessary.
	 *
	 * This is used to get a skin for an RSS/Aom type; also to check if a skin is installed.
	 *
	 * @param string folder name of object to load
	 * @param boolean false if you want to return false on error
	 * @return Skin A Skin object on success, false on failure (may also halt!)
	 */
	function & get_by_folder( $req_folder, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( isset($this->cache_by_folder[$req_folder]) )
		{
			return $this->cache_by_folder[$req_folder];
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_folder)</strong> into cache", 'dataobjects' );
		$sql = "
				SELECT *
				  FROM $this->dbtablename
				 WHERE skin_folder = ".$DB->quote($req_folder);
		$row = $DB->get_row( $sql );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
			$r = false;
			return $r;
		}

		$Skin = new Skin( $row ); // COPY!
		$this->add( $Skin );

		return $Skin;
	}


	/**
	 * Load the cache by type
	 *
	 * @param string
 	 */
	function load_by_type( $type )
	{
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog;

		if( $this->all_loaded || !empty($this->loaded_types[$type]) )
		{ // Already loaded
			return false;
		}

		$Debuglog->add( get_class($this).' - Loading <strong>'.$this->objtype.'('.$type.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
							FROM T_skins__skin
						 WHERE skin_type = '.$DB->quote($type).'
						 ORDER BY skin_name';

		foreach( $DB->get_results( $sql, OBJECT, 'Loading Skins('.$type.') into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_types[$type] = true;

		return true;
	}


	/**
	 * Instanciate a new object within this cache
	 */
	function & new_obj( $row = NULL, $skin_folder = NULL )
	{
		if( is_null($skin_folder) )
		{	// This happens when using the default skin
			$skin_folder = $row->skin_folder;
		}

		// Check if we have a custom class derived from Skin:
		if( skin_file_exists( $skin_folder, '_skin.class.php' ) )
		{
			global $skins_path;
			require_once( $skins_path.$skin_folder.'/_skin.class.php' );
			$objtype = $skin_folder.'_Skin';
			if( ! class_exists($objtype) )
			{
				debug_die( 'There seems to be a _skin.class.php file in the skin directory ['.$skin_folder.'], but it does not contain a properly named class. Expected class name is: '.$objtype );
			}
		}
		else
		{
			$objtype = 'Skin';
		}

		// Instantiate a custom object
		$obj = new $objtype( $row, $skin_folder ); // COPY !!

		return $obj;
	}

}

/*
 * $Log$
 * Revision 1.9  2011/09/04 22:13:20  fplanque
 * copyright 2011
 *
 * Revision 1.8  2010/02/26 21:24:09  fplanque
 * rollback - did not seem right
 *
 * Revision 1.7  2010/02/08 17:54:42  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2010/01/30 18:55:34  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.5  2009/09/14 13:42:44  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.4  2009/05/23 20:20:18  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 * Revision 1.3  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:34  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.5  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.4  2007/01/14 01:33:34  fplanque
 * losely restrict to *installed* XML feed skins
 *
 * Revision 1.3  2007/01/11 20:42:37  fplanque
 * no message
 *
 * Revision 1.2  2007/01/07 23:38:20  fplanque
 * discovery of skin containers
 *
 * Revision 1.1  2006/12/29 01:10:06  fplanque
 * basic skin registering
 */
?>