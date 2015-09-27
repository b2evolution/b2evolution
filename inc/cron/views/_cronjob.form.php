<?php
/**
 * This file implements the UI view for the cron job form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $localtimenow, $edited_Cronjob;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'cronjob' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ? T_('New scheduled job') : T_('Edit scheduled job') );

	$Form->add_crumb( 'crontask' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );
	$Form->hidden( 'action', $creating ? 'create' : 'update' );

	$Form->begin_fieldset( T_('Job details').get_manual_link('scheduled-job-form') );

		if( $creating && $action != 'copy' )
		{ // New cronjob
			$cron_jobs_names = get_cron_jobs_config( 'name' );
			// Exclude these cron jobs from manual creating
			unset( $cron_jobs_names['send-post-notifications'] );
			unset( $cron_jobs_names['send-comment-notifications'] );
			$Form->select_input_array( 'cjob_type', get_param( 'cjob_type' ), $cron_jobs_names, T_('Job type') );
		}
		else
		{ // Edit cronjob
			if( $action == 'edit' )
			{
				$Form->info( T_('Job #'), $edited_Cronjob->ID );
			}

			$Form->info( T_('Default job name'), cron_job_name( $edited_Cronjob->key, '', $edited_Cronjob->params ) );

			$Form->text_input( 'cjob_name', $edited_Cronjob->name, 50, T_('Job name'), '', array( 'maxlength' => 255 ) );
		}

		$Form->date_input( 'cjob_date', date2mysql( $edited_Cronjob->start_timestamp ), T_('Schedule date'), array(
							 'required' => true ) );

		$Form->time_input( 'cjob_time', date2mysql( $edited_Cronjob->start_timestamp ), T_('Schedule time'), array(
							 'required' => true ) );

		$Form->duration_input( 'cjob_repeat_after', $edited_Cronjob->repeat_after, T_('Repeat every'), 'days', 'minutes', array( 'minutes_step' => 1 ) );

	$Form->end_fieldset();

	if( !$creating )
	{	// We can edit only pending cron jobs, Show this field just for info
		$Form->begin_fieldset( T_('Execution details').get_manual_link('scheduled-job-execution-details') );

			$Form->info( T_('Status'), 'pending' );

		$Form->end_fieldset();
	}

$Form->end_form( array( array( 'submit', 'submit', $creating ? T_('Create') : T_('Save Changes!'), 'SaveButton' ) ) );

?>