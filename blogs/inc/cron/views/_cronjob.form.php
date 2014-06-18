<?php
/**
 * This file implements the UI view for the cron job form.
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
 * @package admin
 *
 * @version $Id: _cronjob.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $localtimenow, $cron_job_names, $edited_Cronjob;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'cronjob' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ? T_('New scheduled job') : T_('Edit scheduled job') );

	$Form->add_crumb( 'crontask' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );
	$Form->hidden( 'action', $creating ? 'create' : 'update' );

	$Form->begin_fieldset( T_('Job details').get_manual_link('scheduler_job_form') );

		if( $creating && $action != 'copy' )
		{	// New cronjob
			$Form->select_input_array( 'cjob_type', get_param( 'cjob_type' ), $cron_job_names, T_('Job type') );
		}
		else
		{	// Edit cronjob
			if( $action == 'edit' )
			{
				$Form->info( T_('Job #'), $edited_Cronjob->ID );
			}

			$Form->text_input( 'cjob_name', $edited_Cronjob->name, 25, T_('Job name'), '', array( 'maxlength' => 255, 'required' => true ) );
		}

		$Form->date_input( 'cjob_date', date2mysql( $edited_Cronjob->start_timestamp ), T_('Schedule date'), array(
							 'required' => true ) );

		$Form->time_input( 'cjob_time', date2mysql( $edited_Cronjob->start_timestamp ), T_('Schedule time'), array(
							 'required' => true ) );

		$Form->duration_input( 'cjob_repeat_after', $edited_Cronjob->repeat_after, T_('Repeat every'), 'days', 'minutes', array( 'minutes_step' => 1 ) );

	$Form->end_fieldset();

	if( !$creating )
	{	// We can edit only pending cron jobs, Show this field just for info
		$Form->begin_fieldset( T_('Execution details').get_manual_link('scheduler_execution_info') );

			$Form->info( T_('Status'), 'pending' );

		$Form->end_fieldset();
	}

$Form->end_form( array( array( 'submit', 'submit', $creating ? T_('Create') : T_('Save Changes!'), 'SaveButton' ) ) );

?>