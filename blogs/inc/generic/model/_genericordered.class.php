<?php
/**
 * This file implements the generic ordered class.
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

load_class('generic/model/_genericelement.class.php', 'GenericElement' );

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
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
 			case 'order':
				return $this->set_param( $parname, 'number', $parvalue, $make_null );

			case 'name':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 */
	function dbinsert()
	{
		global $DB;

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


/*
 * $Log$
 * Revision 1.6  2009/09/14 12:25:47  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.5  2009/08/30 17:27:03  fplanque
 * better NULL param handling all over the app
 *
 * Revision 1.4  2009/07/18 18:43:50  tblue246
 * DataObject::set_param() does not accept "integer" as the 2nd param (has to be "number").
 *
 * Revision 1.3  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:30  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:17  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.8  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.7  2006/11/26 01:42:09  fplanque
 * doc
 */
?>
