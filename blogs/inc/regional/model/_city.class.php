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
 * @version $Id: _city.class.php 13 2011-10-24 23:42:53Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * City Class
 */
class City extends DataObject
{
	var $ctry_ID = '';
	var $rgn_ID = '';
	var $subrg_ID = '';
	var $postcode = '';
	var $name = '';
	var $enabled = 1;
	var $preferred = 0;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function City( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_regional__city', 'city_', 'city_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_users', 'fk'=>'user_city_ID', 'msg'=>T_('%d related users') ),
			);

		$this->delete_cascades = array();

		if( $db_row )
		{
			$this->ID            = $db_row->city_ID;
			$this->ctry_ID       = $db_row->city_ctry_ID;
			$this->rgn_ID        = $db_row->city_rgn_ID;
			$this->subrg_ID      = $db_row->city_subrg_ID;
			$this->postcode      = $db_row->city_postcode;
			$this->name          = $db_row->city_name;
			$this->enabled       = $db_row->city_enabled;
			$this->preferred     = $db_row->city_preferred;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Country Id
		param( 'city_ctry_ID', 'integer', true );
		param_check_number( 'city_ctry_ID', T_('Please select a country'), true );
		$this->set_from_Request( 'ctry_ID', 'city_ctry_ID', true );

		// Region Id
		$this->set_string_from_param( 'rgn_ID' );

		// Subregion Id
		$this->set_string_from_param( 'subrg_ID' );

		// Name
		$this->set_string_from_param( 'name', true );

		// Code
		param( 'city_postcode', 'string' );
		param_check_regexp( 'city_postcode', '#^[A-Za-z0-9]{1,12}$#', T_('City code must be from 1 to 12 letters.') );
		$this->set_from_Request( 'postcode', 'city_postcode' );

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
			case 'postcode':
			case 'name':
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get city name.
	 *
	 * @return string city name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Get postcode.
	 *
	 * @return string postcode
	 */
	function get_postcode()
	{
		return $this->postcode;
	}
}

?>