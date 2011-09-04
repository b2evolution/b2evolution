<?php
/**
 * This file implements the UI view for the cron log form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cjob_row;

$Form = new Form( NULL, 'cronlog' );

$Form->global_icon( T_('Close sheet'), 'close', regenerate_url( 'action,cjob_ID' ) );

$Form->begin_form( 'fform', T_('Scheduled job') );

	$Form->begin_fieldset( T_('Job details').get_manual_link('scheduler_job_info') );

		$Form->info( T_('Job #'), $cjob_row->ctsk_ID );
		$Form->info( T_('Job name'), $cjob_row->ctsk_name );
		$Form->info( T_('Scheduled at'), mysql2localedatetime($cjob_row->ctsk_start_datetime) );
		$cjob_repeat_after = '';
		if( $cjob_repeat_after_days = floor( $cjob_row->ctsk_repeat_after / 86400 ) )
		{
			$cjob_repeat_after .= $cjob_repeat_after_days.' '.T_('days').' ';
		}
		if( $cjob_repeat_after_hours = floor( ($cjob_row->ctsk_repeat_after % 86400 ) / 3600 ) )
		{
			$cjob_repeat_after .= $cjob_repeat_after_hours.' '.T_('hours').' ';
		}
		if( $cjob_repeat_after_minutes = floor( ($cjob_row->ctsk_repeat_after % 3600 ) / 60 ) )
		{
			$cjob_repeat_after .= $cjob_repeat_after_minutes.' '.T_('minutes');
		}

		$Form->info( T_('Repeat every'), $cjob_repeat_after );

	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Execution details').get_manual_link('scheduler_execution_info') );

		if( empty( $cjob_row->clog_status ) )
		{
			$Form->info( T_('Status'), 'pending' );
		}
		else
		{
			$Form->info( T_('Status'), '<span class="cron_'.$cjob_row->clog_status.'">'.$cjob_row->clog_status.'</span>' );
			$Form->info( T_('Real start time'), mysql2localedatetime($cjob_row->clog_realstart_datetime) );
			$Form->info( T_('Real stop time'), mysql2localedatetime($cjob_row->clog_realstop_datetime) );
			$Form->info( T_('Messages'), str_replace( "\n", "<br />\n", $cjob_row->clog_messages ) );
		}

	$Form->end_fieldset();

$Form->end_form();


/*
 * $Log$
 * Revision 1.7  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.6  2010/02/08 17:52:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.5  2010/01/30 18:55:23  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.4  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:28  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/12 21:00:31  fplanque
 * UI improvements
 *
 * Revision 1.1  2007/06/25 10:59:49  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/04/26 00:11:09  fplanque
 * (c) 2007
 *
 * Revision 1.2  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.1  2006/06/26 23:09:34  fplanque
 * Really working cronjob environment :)
 *
 */
?>