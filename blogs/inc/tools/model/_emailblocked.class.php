<?php
/**
 * This file implements the (blocked) email address class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );
load_funcs( 'tools/model/_email.funcs.php' );


/**
 * Email Blcoked Class
 *
 * @package evocore
 */
class EmailBlocked extends DataObject
{
	var $address;

	var $status = 'unknown';

	var $sent_count = 0;

	var $sent_last_returnerror = 0;

	var $prmerror_count = 0;

	var $tmperror_count = 0;

	var $spamerror_count = 0;

	var $othererror_count = 0;

	var $last_sent_ts;

	var $last_error_ts;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function EmailBlocked( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_email__blocked', 'emblk_', 'emblk_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->emblk_ID;
			$this->address = $db_row->emblk_address;
			$this->status = $db_row->emblk_status;
			$this->sent_count = $db_row->emblk_sent_count;
			$this->sent_last_returnerror = $db_row->emblk_sent_last_returnerror;
			$this->prmerror_count = $db_row->emblk_prmerror_count;
			$this->tmperror_count = $db_row->emblk_tmperror_count;
			$this->spamerror_count = $db_row->emblk_spamerror_count;
			$this->othererror_count = $db_row->emblk_othererror_count;
			$this->last_sent_ts = $db_row->emblk_last_sent_ts;
			$this->last_error_ts = $db_row->emblk_last_error_ts;
		}
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		return $this->set_param( $parname, 'string', $parvalue, $make_null );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Address
		param_string_not_empty( 'emblk_address', T_('Please enter email address.') );
		param_check_email( 'emblk_address', true );
		if( $existing_emblk_ID = $this->dbexists( 'emblk_address', get_param( 'emblk_address' ) ) )
		{	// Check if a email address already exists with the same address
			global $admin_url;
			param_error( 'emblk_address', sprintf( T_('This email address already exists. Do you want to <a %s>edit the existing email address</a>?'),
				'href="'.$admin_url.'?ctrl=email&amp;tab=blocked&amp;emblk_ID='.$existing_emblk_ID.'"' ) );
		}
		$this->set_from_Request( 'address' );

		// Status
		$emblk_status = param( 'emblk_status', 'string', true );
		if( !empty( $emblk_status ) )
		{
			$this->set( 'status', $emblk_status );
		}

		// Sent count
		param( 'emblk_sent_count', 'integer', '' );
		param_check_number( 'emblk_sent_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'sent_count', 'emblk_sent_count', true );

		// Sent count since last error
		param( 'emblk_sent_last_returnerror', 'integer', '' );
		param_check_number( 'emblk_sent_last_returnerror', T_('The count must be a number.'), true );
		$this->set_from_Request( 'sent_last_returnerror', 'emblk_sent_last_returnerror', true );

		// Permanent errors count
		param( 'emblk_prmerror_count', 'integer', '' );
		param_check_number( 'emblk_prmerror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'prmerror_count', 'emblk_prmerror_count', true );

		// Permanent errors count
		param( 'emblk_tmperror_count', 'integer', '' );
		param_check_number( 'emblk_tmperror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'tmperror_count', 'emblk_tmperror_count', true );

		// Permanent errors count
		param( 'emblk_spamerror_count', 'integer', '' );
		param_check_number( 'emblk_spamerror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'spamerror_count', 'emblk_spamerror_count', true );

		// Permanent errors count
		param( 'emblk_othererror_count', 'integer', '' );
		param_check_number( 'emblk_othererror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'othererror_count', 'emblk_othererror_count', true );

		return ! param_errors_detected();
	}

	/**
	 * Increase a counter field
	 *
	 * @param string Counter name ( 'prmerror', 'tmperror', 'spamerror', 'othererror' )
	 */
	function increase_counter( $counter_name )
	{
		$counter_name .= '_count';
		if( isset( $this->$counter_name ) )
		{
			$this->set( $counter_name, $this->get( $counter_name ) + 1 );
			if( $counter_name != 'sent_count' )
			{	// Update last error date when we increase an error counter
				global $servertimenow;
				$this->set( 'last_error_ts', date( 'Y-m-d H:i:s', $servertimenow ) );
				$this->set( 'sent_last_returnerror', '0' );
			}
		}
	}


	/**
	 * Set status
	 *
	 * @param string New status value
	 */
	function set_status( $new_status )
	{
		if( $this->ID == 0 )
		{	// New record is creating, we can assign any status without level restrictions
			$this->set( 'status', $new_status );
		}
		else
		{	// The record is updating, we should update only status which has hight level then old status

			// Get statuses which we can update depending on levels of the statuses
			$available_statuses = emblk_get_statuses_less_level( $new_status );

			if( in_array( $this->get( 'status' ), $available_statuses ) )
			{	// Check if we can update this status
				$this->set( 'status', $new_status );
			}
		}
	}
}

?>