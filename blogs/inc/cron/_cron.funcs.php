<?php
/**
 * This file implements cron (scheduled tasks) handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Log a message from cron.
 * @param string Message
 * @param integer Level of importance. The higher the more important.
 *        (if $quiet (number of "-q" params passed to cron_exec.php) is lower than this,
 *         the message gets skipped)
 */
function cron_log( $message, $level = 0 )
{
	global $is_web, $quiet;

	if( $quiet < $level )
	{
		return;
	}

	if( $is_web )
	{
		echo '<p>'.$message.'</p>';
	}
	else
	{
		echo "\n".$message."\n";
	}
}


/**
 * Call a cron job.
 *
 * @param string Name of the job
 * @param string Params for the job
 */
function call_job( $job_name, $job_params = array() )
{
	global $DB, $inc_path, $Plugins;

	global $result_message, $result_status, $timestop, $time_difference;

	$result_message = NULL;
	$result_status = 'error';

	if( preg_match( '~^plugin_(\d+)_(.*)$~', $job_name, $match ) )
	{ // Cron job provided by a plugin:
		if( ! is_object($Plugins) )
		{
      load_class('plugins/model/_plugins.class.php');
			$Plugins = & new Plugins();
		}

		$Plugin = & $Plugins->get_by_ID( $match[1] );
		if( ! $Plugin )
		{
			$result_message = 'Plugin for controller ['.$job_name.'] could not get instantiated.';
			cron_log( $result_message, 3 );
			return;
		}

		// CALL THE PLUGIN TO HANDLE THE JOB:
		$tmp_params = array( 'ctrl' => $match[2], 'params' => $job_params );
		$sub_r = $Plugins->call_method( $Plugin->ID, 'ExecCronJob', $tmp_params );

		$error_code = (int)$sub_r['code'];
		$result_message = $sub_r['message'];
	}
	else
	{
		$controller = $inc_path.$job_name;
		if( ! is_file( $controller ) )
		{
			$result_message = 'Controller ['.$job_name.'] does not exist.';
			cron_log( $result_message, 3 );
			return;
		}

		// INCLUDE THE JOB FILE AND RUN IT:
		$error_code = require $controller;
	}

	if( $error_code != 1 )
	{	// We got an error
		$result_status = 'error';
		$result_message = '[Error code: '.$error_code.' ] '.$result_message;
		$cron_log_level = 3;
	}
	else
	{
		$result_status = 'finished';
		$cron_log_level = 2;
	}

	$timestop = time() + $time_difference;
	cron_log( 'Task finished at '.date( 'H:i:s', $timestop ).' with status: '.$result_status
		."\nMessage: $result_message", $cron_log_level );
}


/*
 * $Log$
 * Revision 1.3  2008/10/28 19:59:16  blueyed
 * Cron: implement different levels of quietness. Passing '-q -q' to cron_exec.php is now silent on successful execution.
 *
 * Revision 1.2  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:43  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.9  2006/11/24 18:27:24  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.8  2006/09/02 00:14:43  blueyed
 * Display cron job message on a single line
 *
 * Revision 1.7  2006/08/30 18:10:01  blueyed
 * Re-use existing $Plugins event
 *
 * Revision 1.6  2006/08/28 20:16:29  blueyed
 * Added GetCronJobs/ExecCronJob Plugin hooks.
 *
 * Revision 1.5  2006/08/24 00:43:28  fplanque
 * scheduled pings part 2
 *
 * Revision 1.4  2006/07/16 23:07:19  fplanque
 * no message
 *
 * Revision 1.2  2006/06/13 21:52:44  blueyed
 * Added files from 1.8 branch
 *
 * Revision 1.1.2.1  2006/06/12 20:00:37  fplanque
 * one too many massive syncs...
 *
 */
?>