<?php
/**
 * This file implements the Item Type class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package gsbcore
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

/**
 * ItemType Class
 *
 * @package gsbcore
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
		parent::DataObject( 'T_itemtypes', 'ptyp_', 'ptyp_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_ityp_col', 'fk'=>'itco_ityp_ID', 'msg'=>T_('%d related collections') ), // "Lignes de missions"
				array( 'table'=>'T_items', 'fk'=>'itm_ityp_ID', 'msg'=>T_('%d related items') ), // "Lignes de visit reports"
			);

 		if( $db_row != NULL )
		{
			$this->ID      		 = $db_row->ptyp_ID 		;
			$this->name  			 = $db_row->ptyp_name 	;
		}
	}

	/**
	 * Template function: return name of item
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name_return( $format = 'htmlbody' )
	{
		$r = $this->dget( 'name', $format );
		return $r;
	}
}
?>