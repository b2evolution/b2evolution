<?php
/**
 * This file implements the UI view for the settings of cron jobs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Settings, $current_User;

$Form = new Form( NULL, 'cron_settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cronsettings' );
$Form->hidden( 'ctrl', 'crontab' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'action', 'settings' );

$cron_jobs = get_cron_jobs_config( 'name' );
foreach( $cron_jobs as $cron_job_key => $cron_job_name )
{
	$Form->begin_fieldset( $cron_job_name.cron_job_manual_link( $cron_job_key ) );

		$Form->duration_input( 'cjob_timeout_'.$cron_job_key, $Settings->get( 'cjob_timeout_'.$cron_job_key ), T_('Max execution time'), 'days', 'minutes', array( 'note' => T_( 'Leave empty for no limit' ) ) );

		// Additional settings per cron job:
		switch( $cron_job_key )
		{
			case 'send-email-campaign':
				// Send a chunk of x emails for the campaign:
				if( $current_User->check_perm( 'emails', 'edit' ) )
				{	// Allow to edit email cron settings only if user has a permission:
					$Form->text_input( 'email_campaign_chunk_size', $Settings->get( 'email_campaign_chunk_size' ), 5, T_('Chunk Size'), T_('emails at a time'), array( 'maxlength' => 10 ) );
				}
				elseif( $current_User->check_perm( 'emails', 'view' ) )
				{	// Only display setting value:
					$Form->info( T_('Chunk Size'), $Settings->get( 'email_campaign_chunk_size' ), T_('emails at a time') );
				}
				$Form->duration_input( 'email_campaign_cron_repeat', $Settings->get( 'email_campaign_cron_repeat' ), T_('Delay between chunks'), 'days', 'minutes', array( 'note' => T_('timing between scheduled job runs') ) );
				$Form->duration_input( 'email_campaign_cron_limited', $Settings->get( 'email_campaign_cron_limited' ), T_('Delay in case all remaining recipients have reached max # of emails for the current day'), 'days', 'minutes', array( 'note' => T_('timing between scheduled job runs') ) );
				break;

			case 'prune-old-hits-and-sessions':
				// Prune old hits & sessions (includes OPTIMIZE):
				$Form->text_input( 'auto_prune_stats', $Settings->get( 'auto_prune_stats' ), 5, T_('Keep detailed hitlog for'), T_('days. How many days of hits & sessions do you want to keep in the database for stats?') );
				break;

			case 'prune-recycled-comments':
				// Prune recycled comments:
				$Form->text_input( 'auto_empty_trash', $Settings->get( 'auto_empty_trash' ), 5, T_('Prune recycled comments after'), T_('days').'.' );
				break;

			case 'cleanup-scheduled-jobs':
				// Clean up scheduled jobs older than a threshold:
				$Form->text_input( 'cleanup_jobs_threshold', $Settings->get( 'cleanup_jobs_threshold' ), 5, T_('Cleanup scheduled jobs threshold'), T_('days').'. '.T_('The scheduled jobs older than number of entered days will be removed.') );
				break;

			case 'send-non-activated-account-reminders':
				// Send reminders about non-activated accounts:
				$Form->duration_input( 'activate_account_reminder_threshold', $Settings->get( 'activate_account_reminder_threshold' ), T_('Account activation reminder threshold'), 'days', 'minutes', array( 'note' => T_('A user may receive Account activation reminder if the account was created at least the selected period ago.') ) );
				break;

			case 'send-unmoderated-comments-reminders':
				// Send reminders about comments awaiting moderation:
				$Form->duration_input( 'comment_moderation_reminder_threshold', $Settings->get( 'comment_moderation_reminder_threshold' ), T_('Comment moderation reminder threshold'), 'days', 'minutes', array( 'note' => T_('A moderator user may receive Comment moderation reminder if there are comments awaiting moderation which were created at least the selected period ago.') ) );
				break;

			case 'send-unmoderated-posts-reminders':
				// Send reminders about posts awaiting moderation:
				$Form->duration_input( 'post_moderation_reminder_threshold', $Settings->get( 'post_moderation_reminder_threshold' ), T_('Post moderation reminder threshold'), 'days', 'minutes', array( 'note' => T_('A moderator user may receive Post moderation reminder if there are posts awaiting moderation which were created at least the selected period ago.') ) );
				break;

			case 'send-unread-messages-reminders':
				// Send reminders about unread messages:
				$Form->duration_input( 'unread_messsage_reminder_threshold', $Settings->get( 'unread_messsage_reminder_threshold' ), T_('Unread private messages reminder threshold'), 'days', 'minutes', array( 'note' => T_('A user may receive unread message reminder if it has unread private messages at least as old as the selected period.') ) );
				break;
		}

	$Form->end_fieldset();
}

$buttons = array();
if( $current_User->check_perm( 'options', 'edit' ) )
{	// Allow to save cron settings only if user has a permission:
	$buttons[] = array( 'submit', '', T_('Save Changes!'), 'SaveButton' );
}

$Form->end_form( $buttons );
?>