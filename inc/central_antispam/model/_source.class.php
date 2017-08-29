<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois Planque.
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * CaSource Class
 */
class CaSource extends DataObject
{
	var $baseurl = '';
	var $status = '';

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function __construct( $db_row = NULL )
	{
		global $central_antispam_Module;

		// Call parent constructor:
		parent::__construct( 'T_centralantispam__source', 'casrc_', 'casrc_ID' );

		$this->delete_restrictions = array();

		$this->delete_cascades = array();

		if( $db_row )
		{	// Edit existing keyword:
			$this->ID      = $db_row->casrc_ID;
			$this->baseurl = $db_row->casrc_baseurl;
			$this->status  = $db_row->casrc_status;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Status
		param( 'casrc_status', 'string' );
		$this->set_from_Request( 'status' );

		return ! param_errors_detected();
	}


	/**
	 * Get name as baseurl.
	 *
	 * @return string Baseurl
	 */
	function get_name()
	{
		return $this->baseurl;
	}
}

?>