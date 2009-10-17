<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Updater class
 */
class Updater
{
	/**
	 * URL to check available updates
	 * @var string
	 */
	var $url;

	/**
	 * Updates list
	 * @var list
	 */
	var $updates;


	/**
	 * Constructor
	 */
	function Updater()
	{
	}


	/**
	 * Check for available updates
	 *
	 * @return array or empty array
	 */
	function check_for_updates()
	{
		// The following array represents test result
		$available_update = array();
		$available_update['name'] = 'Update Name';
		$available_update['description'] = 'Update Description';
		$available_update['version'] = '1.0.0';
		$available_update['url'] = 'http://b2evolution.net/downloads/b2evolution_1_0_0.zip';

		$this->updates = array();
		$this->updates[] = $available_update;

		return $this->updates;
	}


	/**
	 * Start upgrade
	 *
	 * @return boolean
	 */
	function start_upgrade()
	{
		global $Messages;

		// TODO: upgrade files and database

		$Messages->add( T_('B2evolution has been successfully upgraded !'), 'success' );
		return true;
	}
}

?>