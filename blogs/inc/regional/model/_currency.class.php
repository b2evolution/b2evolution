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
 */
class Currency extends DataObject
{
	var $code = '';
	var $shortcut = '';
	var $name = '';
	var $enabled = 1;

	/**
	 * Constructor
	 *
	 * @param object database row
	 */
	function Currency( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_currency', 'curr_', 'curr_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_country', 'fk'=>'ctry_curr_ID', 'msg'=>T_('%d related countries') ),
			);

		$this->delete_cascades = array();

 		if( $db_row )
		{
			$this->ID            = $db_row->curr_ID;
			$this->code          = $db_row->curr_code;
			$this->shortcut      = $db_row->curr_shortcut;
			$this->name          = $db_row->curr_name;
			$this->enabled       = $db_row->curr_enabled;
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
		param_check_regexp( 'curr_code', '#^[A-Za-z]{3}$#', T_('Currency code must be 3 letters.') );
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
			case 'enabled':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Check existence of specified currency code in curr_code unique field.
	 *
	 * @return int ID if currency code exists otherwise NULL/false
	 */
	function dbexists()
	{
		return parent::dbexists('curr_code', $this->code);
	}


	/**
	 * Get currency unique name (code).
	 *
	 * @return string currency code
	 */
	function get_name()
	{
		return $this->code;
	}


	/**
	 * Get link to Countries, where this Currencie is used
	 * Use when try to delete a currencie
	 *  
	 * @param array restriction array 
	 * @return string link to currency's countries
	 */
	function get_restriction_link( $restriction )
	{
		global $DB, $admin_url;

		if( $restriction['fk'] != 'ctry_curr_ID' )
		{ // currency restriction exists only for countries
			debug_die( 'Restriction does not exists' );
		}

		// link to country object
		$link = '<a href="'.$admin_url.'?ctrl=countries&action=edit&ctry_ID=%d">%s</a>';
		// set sql to get country ID and name
		$objectID_query = 'SELECT ctry_ID, ctry_name'
						.' FROM '.$restriction['table']
						.' WHERE '.$restriction['fk'].' = '.$this->ID;

		$result_link = '';
		$query_result = $DB->get_results( $objectID_query );
		foreach( $query_result as $row )
		{
			$result_link .= '<br/>'.sprintf( $link, $row->ctry_ID, $row->ctry_name );
		}

		$result_link = sprintf( $restriction['msg'].$result_link, count($query_result) );
		return $result_link;
	}
}


/*
 * $Log$
 * Revision 1.16  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.15  2010/04/07 08:26:11  efy-asimo
 * Allow multiple slugs per post - update & fix
 *
 * Revision 1.14  2010/03/19 09:48:59  efy-asimo
 * file deleting restrictions - task
 *
 * Revision 1.13  2010/01/17 04:14:40  fplanque
 * minor / fixes
 *
 * Revision 1.12  2010/01/15 17:27:28  efy-asimo
 * Global Settings > Currencies - Add Enable/Disable column
 *
 * Revision 1.11  2009/09/20 20:07:18  blueyed
 *  - DataObject::dbexists quotes always
 *  - phpdoc fixes
 *  - style fixes
 *
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