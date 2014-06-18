<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _country.class.php 6264 2014-03-19 12:23:26Z yura $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Country Class
 */
class Country extends DataObject
{
	var $code = '';
	var $name = '';
	var $curr_ID = '';
	var $enabled = 1;
	var $preferred = 0;
	var $status = '';
	var $block_count = 0;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function Country( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_regional__country', 'ctry_', 'ctry_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_users', 'fk'=>'user_ctry_ID', 'msg'=>T_('%d related users') ),
				array( 'table'=>'T_regional__region', 'fk'=>'rgn_ctry_ID', 'msg'=>T_('%d related regions') ),
				array( 'table'=>'T_regional__city', 'fk'=>'city_ctry_ID', 'msg'=>T_('%d related cities') ),
			);

		$this->delete_cascades = array();

		if( $db_row )
		{
			$this->ID          = $db_row->ctry_ID;
			$this->code        = $db_row->ctry_code;
			$this->name        = $db_row->ctry_name;
			$this->curr_ID     = $db_row->ctry_curr_ID;
			$this->enabled     = $db_row->ctry_enabled;
			$this->preferred   = $db_row->ctry_preferred;
			$this->status      = $db_row->ctry_status;
			$this->block_count = $db_row->ctry_block_count;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		$this->set_string_from_param( 'name', true );

		// Code
		param( 'ctry_code', 'string' );
		param_check_regexp( 'ctry_code', '#^[A-Za-z]{2}$#', T_('Country code must be 2 letters.') );
		$this->set_from_Request( 'code', 'ctry_code' );

		// Currency Id
		param( 'ctry_curr_ID', 'integer' );
		param_check_number( 'ctry_curr_ID', T_('Please select a currency') );
		$this->set_from_Request( 'curr_ID', 'ctry_curr_ID', true );

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
			case 'code':
				$parvalue = strtolower($parvalue);
			case 'name':
			case 'curr_ID':
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get country name.
	 *
	 * @return string currency code
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Check existence of specified country code in ctry_code unique field.
	 *
	 * @return int ID if country code exists otherwise NULL/false
	 */
	function dbexists()
	{
		return parent::dbexists('ctry_code', $this->code);
	}
}

?>