<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Currency Class
 *
 */
class Currency extends DataObject
{
	var $code = '';
	var $shortcut = '';
	var $name = '';

	/**
	 * Constructor
	 *
	 * @param db_row database row
	 */
	function Currency( $db_row = NULL )
	{

		// Call parent constructor:
		parent::DataObject( 'T_currency', 'curr_', 'curr_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_country', 'fk'=>'ctry_curr_ID', 'msg'=>T_('%d related countries') ),
			);

  		$this->delete_cascades = array();

 		if( $db_row != NULL )
		{
			$this->ID            = $db_row->curr_ID;
			$this->code          = $db_row->curr_code;
			$this->shortcut      = $db_row->curr_shortcut;
			$this->name          = $db_row->curr_name;
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

		// Shortcut
		$this->set_string_from_param( 'shortcut', true );

		// Code
		param( 'curr_code', 'string' );
		param_check_regexp( 'curr_code', '#^[A-Za-z]{3}$#', T_('Currency code must be 3 letters parameter.') );
		$this->set_from_Request( 'code', 'curr_code', true  );

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
				$parvalue = strtoupper($parvalue);
			case 'shortcut':
			case 'name':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}

	/**
	 * Check existing of specified currency code in curr_code unique field.
	 *
	 * @return ID if currency code exists otherwise NULL/false
	 */
	function dbexists()
	{
		return parent::dbexists('curr_code', $this->code);
	}

	/**
	 * Get currency unique name (code).
	 *
	 * @return currency code
	 */
	function get_name()
	{
		return $this->code;
	}
}

/*
 * $Log$
 * Revision 1.10  2009/09/14 13:31:36  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.9  2009/09/07 12:40:57  efy-maxim
 * Ability to select the default currency when editing a country
 *
 * Revision 1.8  2009/09/05 14:39:48  efy-maxim
 * Delete Restrictions for currency
 *
 * Revision 1.7  2009/09/04 19:00:05  efy-maxim
 * currency/country codes validators have been improved using param_check_regexp() function
 *
 * Revision 1.6  2009/09/03 18:29:29  efy-maxim
 * currency/country code validators
 *
 * Revision 1.5  2009/09/03 07:24:58  efy-maxim
 * 1. Show edit screen again if current currency/goal exists in database.
 * 2. Convert currency code to uppercase
 *
 * Revision 1.4  2009/09/02 23:29:34  fplanque
 * doc
 *
 */
?>