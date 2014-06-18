<?php
/**
 * This file implements the Cronjob class, which manages a single cron job as registered in the DB.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _cronjob.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Cronjob
 *
 * Manages a single cron job as registered in the DB.
 *
 * @package evocore
 */
class Cronjob extends DataObject
{
	var $start_datetime;
	var $repeat_after = NULL;
	var $name;
	var $controller;

	/**
	 * @var array
	 */
	var $params;

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Cronjob( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_cron__task', 'ctsk_', 'ctsk_ID', '', '', '', '' );

		if( $db_row != NULL )
		{	// Loading an object from DB:
			$this->ID              = $db_row->ctsk_ID;
			$this->start_datetime  = $db_row->ctsk_start_datetime;
			$this->start_timestamp = strtotime( $db_row->ctsk_start_datetime );
			$this->repeat_after    = $db_row->ctsk_repeat_after;
			$this->name            = $db_row->ctsk_name;
			$this->controller      = $db_row->ctsk_controller;
			$this->params          = $db_row->ctsk_params;
		}
		else
		{	// New object:
			global $localtimenow;
			$this->start_timestamp = $localtimenow;
		}
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
			case 'params':
				return $this->set_param( 'params', 'string', serialize($parvalue), false );

			case 'name':
				return $this->set_param( $parname, 'string', evo_substr( $parvalue, 0, 255 ), false );
		}

		return $this->set_param( $parname, 'string', $parvalue, $make_null );
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'params':
				return unserialize( $this->params );
		}

		return parent::get( $parname );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $cron_job_names = array(), $cron_job_params = array() )
	{
		if( $this->ID > 0 || get_param( 'ctsk_ID' ) > 0 )
		{	// Update or copy cron job
			$cjob_name = param( 'cjob_name', 'string', true );
			param_check_not_empty( 'cjob_name', T_('Please enter job name') );
		}
		else
		{	// Create new cron job
			$cjob_type = param( 'cjob_type', 'string', true );
			if( !isset( $cron_job_params[$cjob_type] ) )
			{ // This cron job type doesn't exist, so this is an invalid state
				debug_die('Invalid job type received');
				$cjob_name = '';
			}
			else
			{
				$cjob_name = $cron_job_names[$cjob_type];
			}
		}

		// start datetime:
		param_date( 'cjob_date', T_('Please enter a valid date.'), true );
		param_time( 'cjob_time' );
		$this->set( 'start_datetime', form_date( get_param( 'cjob_date' ), get_param( 'cjob_time' ) ) );

		// repeat after:
		$cjob_repeat_after = param_duration( 'cjob_repeat_after' );
		if( $cjob_repeat_after == 0 )
		{
			$cjob_repeat_after = NULL;
		}
		$this->set( 'repeat_after', $cjob_repeat_after );

		// name:
		if( !empty( $cjob_name ) && $cjob_name != $this->get( 'name' ) )
		{
			$this->set( 'name', $cjob_name );
		}

		if( $this->ID == 0 && get_param( 'ctsk_ID' ) == 0 )
		{	// Set these params only on creating and copying actions
			// controller:
			$this->set( 'controller', $cron_job_params[$cjob_type]['ctrl'] );

			// params:
			$this->set( 'params', $cron_job_params[$cjob_type]['params'] );
		}

		return ! param_errors_detected();
	}


	/**
	 * Get status
	 *
	 * @return string Status
	 */
	function get_status()
	{
		global $DB;;

		if( $this->ID > 0 )
		{
			$SQL = new SQL( 'Get status of scheduled job' );
			$SQL->SELECT( 'clog_status' );
			$SQL->FROM( 'T_cron__log' );
			$SQL->WHERE( 'clog_ctsk_ID = '.$DB->quote( $this->ID ) );
			$status = $DB->get_var( $SQL->get() );
		}

		if( empty( $status ) )
		{	// Set default status for new cron jobs and for cron jobs without log
			$status = 'pending';
		}

		return $status;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		if( $this->get_status() == 'pending' )
		{	// Update crob jobs only with "pending" status
			$result = parent::dbupdate();
		}
		else
		{	// Don't update this cron job
			$DB->rollback();
			return false;
		}

		$DB->commit();

		return $result;
	}
}

?>