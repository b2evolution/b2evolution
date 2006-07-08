<?php
/**
 * This file implements cron (scheduled tasks) handling functions.
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

function cron_log( $message )
{
	global $is_web;

	if( $is_web )
	{
		echo '<p>'.$message.'</p>';
	}
	else
	{
		echo "\n".$message."\n";
	}
}



function call_job( $job_name, $job_params = array() /* gets not used! */ )
{
	global $DB, $control_path;

	global $result_message, $result_status, $timestop, $time_difference;

	$result_message = NULL;
	$result_status = 'error';

	$controller = $control_path.$job_name;
	if( ! is_file( $controller ) )
	{
		$result_message = 'Controller ['.$job_name.'] does not exist.';
		cron_log( $result_message );
	}
	else
	{
		$error_code = require $controller;

		if( $error_code != 1 )
		{	// We got an error
			$result_status = 'error';
			$result_message = '[Error code: '.$error_code.' ] '.$result_message;
		}
		else
		{
			$result_status = 'finished';
		}

		$timestop = time() + $time_difference;
		cron_log( 'Task finished at '.date( 'H:i:s', $timestop ).' with status: '.$result_status.' Message: '.$result_message );
	}
}

/*
 * $Log$
 * Revision 1.3  2006/07/08 16:37:48  blueyed
 * doc/note
 *
 * Revision 1.2  2006/06/13 21:52:44  blueyed
 * Added files from 1.8 branch
 *
 * Revision 1.1.2.1  2006/06/12 20:00:37  fplanque
 * one too many massive syncs...
 *
 */
?>