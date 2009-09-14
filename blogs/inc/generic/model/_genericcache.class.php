<?php
/**
 * This file implements the Generic Cache class.
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

load_class( '_core/model/dataobjects/_dataobjectcache.class.php' ,'DataObjectCache' );

/**
 * GenericCache Class
 * @package evocore
 */
class GenericCache extends DataObjectCache
{
	/**
	 * Constructor
	 */
	function GenericCache( $objtype, $load_all, $tablename, $prefix = '', $dbIDname = 'ID', $name_field = NULL, $order_by = '', $allow_none_text = NULL )
	{
		parent::DataObjectCache( $objtype, $load_all, $tablename, $prefix, $dbIDname, $name_field, $order_by, $allow_none_text );
	}


	/**
	 * Instanciate a new object within this cache
	 *
	 * @param object|NULL
	 */
	function & new_obj( $row = NULL )
	{
		$objtype = $this->objtype;

		// Instantiate a custom object
		$obj = new $objtype( $this->dbtablename, $this->dbprefix, $this->dbIDname, $row ); // Copy

		return $obj;
	}
}

/*
 * $Log$
 * Revision 1.4  2009/09/14 12:25:47  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.3  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:30  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:14  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/06/11 22:01:53  blueyed
 * doc fixes
 *
 * Revision 1.8  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.7  2006/12/05 01:35:27  blueyed
 * Hooray for less complexity and the 8th param for DataObjectCache()
 *
 * Revision 1.6  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>