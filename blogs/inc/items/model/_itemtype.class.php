<?php
/**
 * This file implements the Item Type class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 * @author efy-sergey: Evo Factory / Sergey.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * ItemType Class
 *
 * @package evocore
 */
class ItemType extends DataObject
{
	var $name;


	/**
	 * Constructor
	 *
	 *
	 * @param table Database row
	 */
	function ItemType( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_items__type', 'ptyp_', 'ptyp_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		$this->delete_restrictions = array(
				array( 'table'=>'T_ityp_col', 'fk'=>'itco_ityp_ID', 'msg'=>T_('%d related collections') ), // "Lignes de missions"
				array( 'table'=>'T_items__item', 'fk'=>'post_ptyp_ID', 'msg'=>T_('%d related items') ), // "Lignes de visit reports"
			);

 		if( $db_row != NULL )
		{
			$this->ID      		 = $db_row->ptyp_ID 		;
			$this->name  			 = $db_row->ptyp_name 	;
		}
	}

	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// get new ID
		if( param( 'new_ptyp_ID', 'string', NULL ) !== NULL )
		{
			param_check_number( 'new_ptyp_ID', T_('ID must be a number.'), true );
			$this->set_from_Request( 'ID', 'new_ptyp_ID' );
		}

		// Name
		param_string_not_empty( 'ptyp_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		return ! param_errors_detected();
	}

	/**
	 * Get the name of the ItemType
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}

	/**
	 * Check existence of specified item type ID in ptyp_ID unique field.
	 *
	 * @return int ID if item type exists otherwise NULL/false
	 */
	function dbexists()
	{
		global $DB;

		$sql = "SELECT $this->dbIDname
						  FROM $this->dbtablename
					   WHERE $this->dbIDname = $this->ID";

		return $DB->get_var( $sql );
	}

	/**
	 *  Returns array, which determinate the lower and upper limit of protected ID's
	 *
	 *  @return array
	 */
	function get_reserved_ids()
	{
		return array( 1000, 5000 );
	}
}

/*
 * $Log$
 * Revision 1.7  2009/12/07 23:59:09  blueyed
 * Punctuation.
 *
 * Revision 1.6  2009/09/29 18:43:58  fplanque
 * doc
 *
 * Revision 1.5  2009/09/25 11:36:44  efy-sergey
 * Replaced "simple list" manager for Post types. Also allow to edit ID for Item types
 *
 * Revision 1.4  2009/09/14 13:17:28  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.3  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:28  fplanque
 * MODULES (refactored MVC)
 *
 */
?>
