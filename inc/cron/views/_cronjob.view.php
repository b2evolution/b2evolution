<?php
/**
 * This file implements the UI view for the cron log form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cjob_row, $current_User, $admin_url;

$Form = new Form( NULL, 'cronlog' );

if( empty( $cjob_row->clog_status ) && $current_User->check_perm( 'options', 'edit', false, NULL ) )
{ // User can edit this job:
	$Form->global_icon( T_('Edit this job'), 'edit', $admin_url.'?ctrl=crontab&amp;action=edit&amp;ctsk_ID='.$cjob_row->ctsk_ID, T_('Edit this job').'...', 3, 3 );
}

$Form->global_icon( T_('Close sheet'), 'close', regenerate_url( 'action,cjob_ID' ) );

$manual_link = cron_job_manual_link( $cjob_row->ctsk_key );

$Form->begin_form( 'fform', T_('Scheduled job') );

	$Form->begin_fieldset( T_('Job details').$manual_link );

		$Form->info( T_('Job #'), $cjob_row->ctsk_ID );
		$Form->info( T_('Job name'), cron_job_name( $cjob_row->ctsk_key, $cjob_row->ctsk_name, $cjob_row->ctsk_params ).$manual_link );
		$Form->info( T_('Scheduled at'), mysql2localedatetime($cjob_row->ctsk_start_datetime) );
		$Form->begin_line( T_('Repeat every'), NULL, 'info' );
			$Form->info( '', seconds_to_period( $cjob_row->ctsk_repeat_after ) );
			$Form->info( T_('+/- variation of:'), seconds_to_period( $cjob_row->ctsk_repeat_variation ) );
		$Form->end_line( NULL, 'info' );

	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Execution details').get_manual_link('scheduled-job-execution-details') );

		if( empty( $cjob_row->clog_status ) )
		{
			$Form->info( T_('Status'), 'pending' );
		}
		else
		{
			$duration_seconds = strtotime( $cjob_row->clog_realstop_datetime ) - strtotime( $cjob_row->clog_realstart_datetime );
			$duration_icon = ( $duration_seconds > 60 ) ? ' '.get_icon( 'warning_yellow', 'imgtag', array( 'title' => T_('This job took more than 60 seconds to execute!') ) ) : '';

			$Form->info( T_('Status'), '<span style="background-color:'.cron_status_color ( $cjob_row->clog_status ).';padding:0 5px;">'.$cjob_row->clog_status.'</span>'.$duration_icon );
			$Form->info( T_('Real start time'), mysql2localedatetime( $cjob_row->clog_realstart_datetime ) );
			$Form->info( T_('Real stop time'), mysql2localedatetime( $cjob_row->clog_realstop_datetime ) );
			$Form->info( T_('Duration'), seconds_to_period( $duration_seconds ) );
			$cron_messages_data = @unserialize( $cjob_row->clog_messages );
			if( !is_array( $cron_messages_data ) )
			{	// Simple messages
				$Form->info( T_('Messages'), str_replace( "\n", "<br />\n", $cjob_row->clog_messages ) );
			}
			else
			{	// Serialized data
				if( isset( $cron_messages_data['message'] ) )
				{	// Display message
					$Form->info( T_('Messages'), str_replace( "\n", "<br />\n", $cron_messages_data['message'] ) );
				}

				if( isset( $cron_messages_data['table_cols'], $cron_messages_data['table_data'] ) && ( !empty( $cron_messages_data['table_data'] ) ) )
				{	// Display table with report
					$Table = new Table( NULL, 'cron_' );

					$Table->cols = array();
					if( !empty( $cron_messages_data['table_cols'] ) )
					{
						foreach( $cron_messages_data['table_cols'] as $col_name )
						{
							$Table->cols[] = array( 'th' => $col_name );
						}
					}

					$Table->display_init();

					$Table->display_list_start();

					// COLUMN HEADERS:
					$Table->display_col_headers();

					// BODY START:
					$Table->display_body_start();

					// Display table rows
					foreach( $cron_messages_data['table_data'] as $data_row )
					{
						$Table->display_line_start( false, false );

						foreach( $data_row as $row_value )
						{
							$Table->display_col_start();
							echo $row_value;
							$Table->display_col_end();
						}

						$Table->display_line_end();
					}

					// BODY END:
					$Table->display_body_end();

					$Table->display_list_end();
				}
			}
		}

	$Form->end_fieldset();

$Form->end_form();

?>