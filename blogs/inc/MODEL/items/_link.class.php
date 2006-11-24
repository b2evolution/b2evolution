<?php
/**
 * This file implements the Link class, which manages extra links on items.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

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
 * Revision 1.5  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.4  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.3  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.2  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.14  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.13  2006/01/02 19:43:57  fplanque
 * just a little new year cleanup
 *
 * Revision 1.12  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.11  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.10  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.9  2005/11/04 21:42:22  blueyed
 * Use setter methods to set parameter values! dataobject::set_param() won't pass the parameter to dbchange() if it is already set to the same member value.
 *
 * Revision 1.8  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.7  2005/05/04 18:16:55  fplanque
 * Normalizing
 *
 * Revision 1.6  2005/04/19 16:23:03  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.4  2005/04/15 18:02:59  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.3  2005/03/21 17:38:00  fplanque
 * results/table layout refactoring
 *
 * Revision 1.2  2005/03/15 19:19:47  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.1  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 */
?>