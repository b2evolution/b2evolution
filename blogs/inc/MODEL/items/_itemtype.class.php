<?php
/**
 * This file implements the Item Type class.
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
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
				array( 'table'=>'T_posts', 'fk'=>'post_ptyp_ID', 'msg'=>T_('%d related items') ), // "Lignes de visit reports"
			);

 		if( $db_row != NULL )
		{
			$this->ID      		 = $db_row->ptyp_ID 		;
			$this->name  			 = $db_row->ptyp_name 	;
		}
	}

	/**
	 * Get the name of the ItemType
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}
}
?>