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
 * @author evofactory-test
 * @author fplanque: Francois Planque.
 *
 * @version _userfield.class.php,v 1.5 2009/09/16 18:11:51 fplanque Exp
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Userfield Class
 *
 * @package evocore
 */
class Userfield extends DataObject
{
	/**
	 * Userfield Group ID
	 * @var integer
	 */
	var $ufgp_ID = 0;

	var $type = '';
	var $name = '';
	var $options = NULL;
	var $required = 'optional';
	var $duplicated = 'allowed';
	var $order = '';
	var $suggest = '1';
	var $bubbletip = '';

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function Userfield( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_users__fielddefs', 'ufdf_', 'ufdf_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		$this->delete_restrictions = array();

		$this->delete_cascades = array();

		if( $db_row != NULL )
		{
			$this->ID         = $db_row->ufdf_ID;
			$this->ufgp_ID    = $db_row->ufdf_ufgp_ID;
			$this->type       = $db_row->ufdf_type;
			$this->name       = $db_row->ufdf_name;
			$this->options    = $db_row->ufdf_options;
			$this->required   = $db_row->ufdf_required;
			$this->duplicated = $db_row->ufdf_duplicated;
			$this->order      = $db_row->ufdf_order;
			$this->suggest    = $db_row->ufdf_suggest;
			$this->bubbletip  = $db_row->ufdf_bubbletip;
		}
		else
		{	// Create a new user field:
		}
	}


	/**
	 * Returns array of possible user field types
	 *
	 * @return array
	 */
	function get_types()
	{
		return array(
			'email'  => T_('Email address'),
			'word'   => T_('Single word'),
			'list'   => T_('Option list'),
			'number' => T_('Number'),
			'phone'  => T_('Phone number'),
			'url'    => T_('URL'),
			'text'   => T_('Text'),
		 );
	}


	/**
	 * Returns array of possible user field required types
	 *
	 * @return array
	 */
	function get_requireds()
	{
		return array(
			array( 'value' => 'hidden', 'label' => T_('Hidden') ),
			array( 'value' => 'optional', 'label' => T_('Optional') ),
			array( 'value' => 'recommended', 'label' => T_('Recommended') ),
			array( 'value' => 'require', 'label' => T_('Required') ),
		 );
	}


	/**
	 * Returns array of possible user field duplicated types
	 *
	 * @return array
	 */
	function get_duplicateds()
	{
		return array(
			array( 'value' => 'forbidden', 'label' => T_('Forbidden') ),
			array( 'value' => 'allowed', 'label' => T_('Allowed') ),
			array( 'value' => 'list', 'label' => T_('List style') ),
		 );
	}

	/**
	 * Returns array of user field groups
	 *
	 * @return array
	 */
	function get_groups()
	{
		global $DB;

		return $DB->get_assoc( '
			SELECT ufgp_ID, ufgp_name
			  FROM T_users__fieldgroups
			 ORDER BY ufgp_order, ufgp_ID' );
	}


	/**
	 * Get last order number for current group
	 * Used in the action add a new field OR move fielddef from other group
	 *
	 * @param integer Group ID
	 * @return integer
	 */
	function get_last_order( $group_ID )
	{
		global $DB;

		$order = $DB->get_var( '
			SELECT MAX( ufdf_order )
			  FROM T_users__fielddefs
			 WHERE ufdf_ufgp_ID = '.$DB->quote( $group_ID ) );

		return $order + 1;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Group
		$old_group_ID = $this->ufgp_ID; // Save old group ID to know if it was changed
		param_string_not_empty( 'ufdf_ufgp_ID', T_('Please select a group.') );
		$this->set_from_Request( 'ufgp_ID' );

		// Type
		param_string_not_empty( 'ufdf_type', T_('Please enter a type.') );
		$this->set_from_Request( 'type' );

		// Name
		param_string_not_empty( 'ufdf_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Options
		if( param( 'ufdf_type', 'string' ) == 'list' )
		{	// Save 'Options' only for Field type == 'Option list'
			$ufdf_options = explode( "\n", param( 'ufdf_options', 'text' ) );
			if( count( $ufdf_options ) < 2 )
			{	// We don't want save an option list with one item
				param_error( 'ufdf_options', T_('Please enter at least 2 options on 2 different lines.') );
			}
			$this->set_from_Request( 'options' );
		}
		else
		{ // The 'options' field must be set because it doesn't have a default value
			$this->set( 'options', '' );
		}

		// Required
		param_string_not_empty( 'ufdf_required', 'Please select Hidden, Optional, Recommended or Required.' );
		$this->set_from_Request( 'required' );

		// Duplicated
		param_string_not_empty( 'ufdf_duplicated', 'Please select Forbidden, Allowed or List style.' );
		$this->set_from_Request( 'duplicated' );

		// Order
		if( $old_group_ID != $this->ufgp_ID )
		{ // Group is changing, set order as last
			$this->set( 'order', $this->get_last_order( $this->ufgp_ID ) );
		}

		// Suggest
		if( param( 'ufdf_type', 'string' ) == 'word' )
		{ // Save 'Suggest values' only for Field type == 'Single word'
			param( 'ufdf_suggest', 'integer', 0 );
			$this->set_from_Request( 'suggest' );
		}

		// Bubbletip
		param( 'ufdf_bubbletip', 'text', '' );
		$this->set_from_Request( 'bubbletip' );

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
			case 'type':
			case 'name':
			case 'required':
			default:
				$this->set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Get user field name.
	 *
	 * @return string user field name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Check existence of specified user field ID in ufdf_ID unique field.
	 *
	 * @todo dh> Two returns here!!
	 * @return int ID if user field exists otherwise NULL/false
	 */
	function dbexists()
	{
		global $DB;

		$sql = "SELECT $this->dbIDname
						  FROM $this->dbtablename
					   WHERE $this->dbIDname = $this->ID";

		return $DB->get_var( $sql );

		return parent::dbexists('ufdf_ID', $this->ID);
	}
}


/*
 * _userfield.class.php,v
 * Revision 1.5  2009/09/16 18:11:51  fplanque
 * Readded with -kkv option
 *
 * Revision 1.1  2009/09/11 18:34:06  fplanque
 * userfields editing module.
 * needs further cleanup but I think it works.
 *
 */
?>