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
 * CaKeyword Class
 */
class CaKeyword extends DataObject
{
	var $keyword = '';
	var $status = '';
	var $statuschange_ts = '';
	var $lastreport_ts = '';

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function __construct( $db_row = NULL )
	{
		global $central_antispam_Module;

		// Call parent constructor:
		parent::__construct( 'T_centralantispam__keyword', 'cakw_', 'cakw_ID' );

		$this->delete_restrictions = array();

		$this->delete_cascades = array();

		if( $db_row )
		{	// Edit existing keyword:
			$this->ID              = $db_row->cakw_ID;
			$this->keyword         = $db_row->cakw_keyword;
			$this->status          = $db_row->cakw_status;
			$this->statuschange_ts = $db_row->cakw_statuschange_ts;
			$this->lastreport_ts   = $db_row->cakw_lastreport_ts;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Keyword
		param( 'cakw_keyword', 'string' );
		param_check_not_empty( 'cakw_keyword', T_('You must provide a keyword!') );
		$this->set_from_Request( 'keyword' );

		// Status
		param( 'cakw_status', 'string' );
		$this->set_from_Request( 'status' );

		if( isset( $this->dbchanges['cakw_status'] ) )
		{	// Update statuschange_ts field only when status has been changed:
			global $localtimenow;
			$this->set_param( 'statuschange_ts', 'date', date( 'Y-m-d H:i:s', $localtimenow ) );
		}

		return ! param_errors_detected();
	}


	/**
	 * Get keyword name.
	 *
	 * @return string Keyword
	 */
	function get_name()
	{
		return $this->keyword;
	}
}

?>