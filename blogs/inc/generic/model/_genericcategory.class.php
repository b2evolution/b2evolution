<?php
/**
 * This file implements the GenericCategory class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _genericcategory.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class('generic/model/_genericelement.class.php', 'GenericElement');


/**
 * GenericCategory Class
 *
 * @package evocore
 */
class GenericCategory extends GenericElement
{
	var $parent_ID;
	/**
	 * To display parent name in form
	 */
	var $parent_name;

	/**
	 * Category children list
	 */
	var $children = array();

	/**
	 * Constructor
	 *
	 * @param string Table name
	 * @param string
	 * @param string DB ID name
	 * @param array|NULL Database row
	 */
	function GenericCategory( $tablename, $prefix = '', $dbIDname = 'ID', $db_row = NULL )
	{
		global $Debuglog;

		// Call parent constructor:
		parent::GenericElement( $tablename, $prefix, $dbIDname, $db_row );

		if( $db_row != NULL )
		{
			$parentIDfield = $prefix.'parent_ID';
			$this->parent_ID = $db_row->$parentIDfield;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @todo fp> check that we are not creating a loop!
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_request()
	{
		parent::load_from_Request();

		if( param( $this->dbprefix.'parent_ID', 'integer', -1 ) !== -1 )
		{
			$this->set_from_Request( 'parent_ID' );
		}

		return ! param_errors_detected();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
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
 			case 'parent_ID':
				return $this->set_param( $parname, 'string', $parvalue, true );

			case 'name':
			case 'urlname':
			case 'description':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Add a child
	 * @param GenericCategory
	 */
	function add_child_category( & $GenericCategory )
	{
		if( !isset( $this->children[$GenericCategory->ID] ) )
		{ // Add only if it was not added yet
			$this->children[$GenericCategory->ID] = & $GenericCategory;
		}
	}

}

?>