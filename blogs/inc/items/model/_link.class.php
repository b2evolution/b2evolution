<?php
/**
 * This file implements the Link class, which manages extra links on items.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobject.class.php');

/**
 * Item Link
 *
 * @package evocore
 */
class Link extends DataObject
{
	var $ltype_ID = 0;
	var $Item;
	var $File;


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Link( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_links', 'link_', 'link_ID',
													'datecreated', 'datemodified', 'creator_user_ID', 'lastedit_user_ID' );

		if( $db_row != NULL )
		{
			$this->ID       = $db_row->link_ID;
			$this->ltype_ID = $db_row->link_ltype_ID;

			// source of link:
			$ItemCache = & get_Cache( 'ItemCache' );
			$this->Item     = & $ItemCache->get_by_ID( $db_row->link_itm_ID );

			$FileCache = & get_Cache( 'FileCache' );
			$this->File     = & $FileCache->get_by_ID( $db_row->link_file_ID, true, false );
		}
		else
		{	// New object:

		}
	}


	/**
	 * Return type of target for this Link:
	 *
	 * @todo incomplete
	 */
	function target_type()
	{
 		if( !is_null($this->File) )
		{
			return 'file';
		}


		return 'unkown';
	}

}

/*
 * $Log$
 * Revision 1.1  2007/06/25 11:00:28  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.6  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.5  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>