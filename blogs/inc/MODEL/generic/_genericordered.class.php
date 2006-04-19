<?php
/**
 * This file implements the generic ordered class.
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
 * User property;
 *
 * Generic Ordered of users with specific permissions.
 *
 * @package evocore
 */
class GenericOrdered extends GenericElement 
{
	// Order object
	var $order;


	/**
	 * Constructor
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param object DB row
	 */
	function GenericOrdered( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::GenericElement( $tablename, $prefix, $dbIDname, $db_row );

		if( $db_row != NULL )
		{
			$this->order = $db_row->{$prefix.'order'};
		}

		$Debuglog->add( "Created element <strong>$this->name</strong>", 'dataobjects' );
	}

	
	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * {@internal Contact::set(-)}}
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
 			case 'order':
				$this->set_param( $parname, 'integer', $parvalue );
				break;

			case 'name':
			default:
				$this->set_param( $parname, 'string', $parvalue );
		}
	}
	
	
	/**
	 * Insert object into DB based on previously recorded changes
	 */
	function dbinsert()
	{
		global $DB, $Request;

		$DB->begin();
	
		if( $max_order = $DB->get_var( 'SELECT MAX('.$this->dbprefix.'order) 
																			FROM '.$this->dbtablename ) ) 
		{	// The new element order must be the lastest
			$max_order++;
		}
		else 
		{ // There are no elements in the database yet, so his order is set to 1. 
			$max_order = 1;
		}
		
		// Set Object order:
		$this->set( 'order', $max_order );
		
		parent::dbinsert();

		$DB->commit();
	}	
		
}

?>