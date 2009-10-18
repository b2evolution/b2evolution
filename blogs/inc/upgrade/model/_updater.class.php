<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Updater class
 *
 * @todo fp> I think we really don't need this class at all.
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