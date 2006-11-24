<?php
/**
 * This file implements the GenericCategory class.
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'MODEL/generic/_genericelement.class.php' );


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
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
 			case 'parent_ID':
				$this->set_param( $parname, 'string', $parvalue, true );
				break;

			case 'name':
			default:
				$this->set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Add a child
	 * @todo dh> "children" is plural..!
	 * @param GenericCategory
	 */
	function add_children( & $GenericCategory )
	{
		$this->children[] = & $GenericCategory;
	}

}


/*
 * $Log$
 * Revision 1.12  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.11  2006/11/22 20:45:36  blueyed
 * doc/todo
 *
 * Revision 1.10  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 */
?>