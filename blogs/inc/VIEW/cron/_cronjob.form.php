<?php
/**
 * This file implements the UI view for the cron job form.
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $localtimenow, $cron_job_names;

$Form = & new Form( NULL, 'cronjob' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('New scheduled job') );

	$Form->hiddens_by_key( get_memorized( 'action' ) );
	$Form->hidden( 'action', 'create' );

	$Form->begin_fieldset( T_('Job details') . get_web_help_link('scheduler_job_form') );

		$Form->select_input_array( 'cjob_type', $cron_job_names, T_('Job type') );

		$Form->date_input( 'cjob_date', date( locale_datefmt(), $localtimenow ), T_('Schedule date'), array(
							 'required' => true ) );

		$Form->time_input( 'cjob_time', '2000-01-01 '.date( locale_timefmt(), $localtimenow ), T_('Schedule time'), array(
							 'required' => true ) );

		$Form->duration_input( 'cjob_repeat_after', 0, T_('Repeat every'), array( 'minutes_step' => 1 ) );

	$Form->end_fieldset();

$Form->end_form( array(
			array( 'submit', 'submit', T_('Create'), 'SaveButton' ),
			array( 'reset', '', T_('Reset'), 'ResetButton' ),
		) );


/*
 * $Log$
 * Revision 1.1  2006/06/26 23:09:34  fplanque
 * Really working cronjob environment :)
 *
 */
?>