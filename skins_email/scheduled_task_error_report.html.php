<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that one or more ((ScheduledTasks)) failed to execute properly.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url;

// Default params:
$params = array_merge( array(
		'timeout_tasks' => array(),
		'error_task'    => NULL,
	), $params );

if( $params['error_task'] !== NULL )
{	// Display an error task:
	echo '<p'.emailskin_style( '.p' ).'>'.T_('The following scheduled task has ended with an error:')."</p>\n";
	echo '<p'.emailskin_style( '.p' ).'>';
	echo '<b>'.$params['error_task']['name'].'</b> ('.get_link_tag( $admin_url.'?ctrl=crontab&action=view&cjob_ID='.$params['error_task']['ID'], '#'.$params['error_task']['ID'], '.a' ).'):<br>';
	echo str_replace( "\n", '<br>', ltrim( $params['error_task']['message'], "\n" ) );
	echo "</p>\n";
}

if( is_array( $params['timeout_tasks'] ) && count( $params['timeout_tasks'] ) )
{	// Display timeout tasks:
	echo '<p'.emailskin_style( '.p' ).'>'.T_('The following scheduled tasks have timed out:')."</p>\n";
	echo '<p'.emailskin_style( '.p' ).'><ul>';
	foreach( $params['timeout_tasks'] as $task_ID => $task_name )
	{
		echo '<li><b>'.$task_name.'</b> ('.get_link_tag( $admin_url.'?ctrl=crontab&action=view&cjob_ID='.$task_ID, '#'.$task_ID, '.a' ).')</li>';
	}
	echo "</ul></p>\n";
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=crontab&ctst_status[]=warning&ctst_status[]=timeout&ctst_status[]=error&ctst_status[]=imap_error', T_( 'Review tasks with errors' ), 'div.buttons a+a.btn-primary' )."\n";
echo "</div>\n";


// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a scheduled task admin, and you are receiving notifications when a scheduled tasks ends with error or timeout.' )."<br />\n"
			.T_( 'If you don\'t want to receive any more notifications about scheduled task errors, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=cronjob_error&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>