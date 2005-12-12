<?php
/**
 * This file implements the Link class, which manages extra links on items.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobject.class.php';

/**
 * Item Link
 *
 * @package evocore
 */
class Link extends DataObject
{
	var $ltype_ID = 0;
	var $Item;
	var $Contact;
	var $Firm;

	/**
	 * Constructor
	 *
	 * {@internal Link::Link(-) }}
	 *
	 * @param table Database row
	 */
	function Link( $db_row = NULL )
	{
		global $ItemCache, $FileCache;

		// Call parent constructor:
		parent::DataObject( 'T_links', 'link_', 'link_ID',
													'datecreated', 'datemodified', 'creator_user_ID', 'lastedit_user_ID' );

		if( $db_row != NULL )
		{
			$this->ID       = $db_row->link_ID;
			$this->ltype_ID = $db_row->link_ltype_ID;

			// source of link:
			$this->Item     = & $ItemCache->get_by_ID( $db_row->link_item_ID );

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

		if( !is_null($this->Contact) )
		{
			return 'contact';
		}

 		if( !is_null($this->Establishment) )
		{
			return 'establishment';
		}

 		if( !is_null($this->Firm) )
		{
			return 'firm';
		}

 		if( !is_null($this->Task) )
		{
			return 'task';
		}

		return 'unkown';
	}

}

/*
 * $Log$
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