<?php
/**
 * This file implements the Cronjob class, which manages a single cron job as registered in the DB.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'MODEL/dataobjects/_dataobject.class.php' );

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
			$this->repeat_after    = $db_row->ctsk_repeat_after;
			$this->name            = $db_row->ctsk_name;
			$this->controller      = $db_row->ctsk_controller;
			$this->params          = $db_row->ctsk_params;
		}
		else
		{	// New object:

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
}

/*
 * $Log$
 * Revision 1.2  2006/09/21 15:26:28  blueyed
 * Fixed dependency (and tests)
 *
 * Revision 1.1  2006/08/24 00:43:28  fplanque
 * scheduled pings part 2
 *
 */
?>