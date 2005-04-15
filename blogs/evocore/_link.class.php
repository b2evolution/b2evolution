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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

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
	var $Item = NULL;
	var $Contact = NULL;
	var $Firm = NULL;

	/** 
	 * Constructor
	 *
	 * {@internal Link::Link(-) }}
	 *
	 * @param table Database row
	 */
	function Link( $db_row = NULL )
	{
		global $ItemCache;
		
		// Call parent constructor:
		parent::DataObject( 'T_links', 'link_', 'link_ID' );

 		if( $db_row != NULL )
		{
			$this->ID       = $db_row->link_ID      ;
			$this->ltype_ID = $db_row->link_ltype_ID;

			// source of link:
			$this->Item     = & $ItemCache->get_by_ID( $db_row->link_item_ID );

			/*
			global $TaskCache, $ContactCache, $EstablishmentCache, $FirmCache, $TaskCache;

			// source of link:
			$this->Item     = & $TaskCache->get_by_ID( $db_row->link_item_ID );

			// Possible destinations:
			$this->Contact       = & $ContactCache->get_by_ID( $db_row->link_cont_ID, true, false );
			$this->Establishment = & $EstablishmentCache->get_by_ID( $db_row->link_etab_ID, true, false );
			$this->Firm          = & $FirmCache->get_by_ID( $db_row->link_firm_ID, true, false );
			$this->Task          = & $TaskCache->get_by_ID( $db_row->link_dest_item_ID, true, false );
			*/
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