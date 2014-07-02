<?php
/**
 * This file implements the (blocked) email address class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _emailaddress.class.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );
load_funcs( 'tools/model/_email.funcs.php' );


/**
 * Email Address Class
 *
 * @package evocore
 */
class EmailAddress extends DataObject
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
	function EmailAddress( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_email__address', 'emadr_', 'emadr_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->emadr_ID;
			$this->address = $db_row->emadr_address;
			$this->status = $db_row->emadr_status;
			$this->sent_count = $db_row->emadr_sent_count;
			$this->sent_last_returnerror = $db_row->emadr_sent_last_returnerror;
			$this->prmerror_count = $db_row->emadr_prmerror_count;
			$this->tmperror_count = $db_row->emadr_tmperror_count;
			$this->spamerror_count = $db_row->emadr_spamerror_count;
			$this->othererror_count = $db_row->emadr_othererror_count;
			$this->last_sent_ts = $db_row->emadr_last_sent_ts;
			$this->last_error_ts = $db_row->emadr_last_error_ts;
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
		global $emadr_address;
		param_string_not_empty( 'emadr_address', T_('Please enter email address.') );
		$emadr_address = evo_strtolower( get_param( 'emadr_address' ) );
		param_check_email( 'emadr_address', true );
		if( $existing_emadr_ID = $this->dbexists( 'emadr_address', get_param( 'emadr_address' ) ) )
		{	// Check if a email address already exists with the same address
			global $admin_url;
			param_error( 'emadr_address', sprintf( T_('This email address already exists. Do you want to <a %s>edit the existing email address</a>?'),
				'href="'.$admin_url.'?ctrl=email&amp;tab=blocked&amp;emadr_ID='.$existing_emadr_ID.'"' ) );
		}
		$this->set_from_Request( 'address' );

		// Status
		$emadr_status = param( 'emadr_status', 'string', true );
		if( !empty( $emadr_status ) )
		{
			$this->set( 'status', $emadr_status );
		}

		// Sent count
		param( 'emadr_sent_count', 'integer', '' );
		param_check_number( 'emadr_sent_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'sent_count', 'emadr_sent_count', true );

		// Sent count since last error
		param( 'emadr_sent_last_returnerror', 'integer', '' );
		param_check_number( 'emadr_sent_last_returnerror', T_('The count must be a number.'), true );
		$this->set_from_Request( 'sent_last_returnerror', 'emadr_sent_last_returnerror', true );

		// Permanent errors count
		param( 'emadr_prmerror_count', 'integer', '' );
		param_check_number( 'emadr_prmerror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'prmerror_count', 'emadr_prmerror_count', true );

		// Permanent errors count
		param( 'emadr_tmperror_count', 'integer', '' );
		param_check_number( 'emadr_tmperror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'tmperror_count', 'emadr_tmperror_count', true );

		// Permanent errors count
		param( 'emadr_spamerror_count', 'integer', '' );
		param_check_number( 'emadr_spamerror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'spamerror_count', 'emadr_spamerror_count', true );

		// Permanent errors count
		param( 'emadr_othererror_count', 'integer', '' );
		param_check_number( 'emadr_othererror_count', T_('The count must be a number.'), true );
		$this->set_from_Request( 'othererror_count', 'emadr_othererror_count', true );

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
			$available_statuses = emadr_get_statuses_less_level( $new_status );

			if( in_array( $this->get( 'status' ), $available_statuses ) )
			{	// Check if we can update this status
				$this->set( 'status', $new_status );
			}
		}
	}
}

?>