<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that one or more ((ScheduledTasks)) failed to execute properly.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url;

// Default params:
$params = array_merge( array(
		'timeout_tasks' => array(),
		'error_task'    => NULL,
	), $params );

if( $params['error_task'] !== NULL )
{	// Display an error task:
	echo T_('The following scheduled task has ended with an error:')."\n";
	echo $params['error_task']['name'].' (#'.$params['error_task']['ID'].' - '.$admin_url.'?ctrl=crontab&action=view&cjob_ID='.$params['error_task']['ID'].'):'."\n";
	echo ltrim( $params['error_task']['message'], "\n" );
	echo "\n\n";
}

if( is_array( $params['timeout_tasks'] ) && count( $params['timeout_tasks'] ) )
{	// Display timeout tasks:
	echo T_('The following scheduled tasks have timed out:')."\n";
	foreach( $params['timeout_tasks'] as $task_ID => $task_name )
	{
		echo '- '.$task_name.' (#'.$task_ID.' - '.$admin_url.'?ctrl=crontab&action=view&cjob_ID='.$task_ID.')'."\n";
	}
}
echo "\n";

$tasks_url = $admin_url.'?ctrl=crontab&ctst_status[]=warning&ctst_status[]=timeout&ctst_status[]=error&ctst_status[]=imap_error';
echo sprintf( T_('To see more information about these tasks click here: %s'), $tasks_url );

// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a scheduled task admin, and you are receiving notifications when a scheduled tasks ends with error or timeout.' )."\n".
		T_( 'If you don\'t want to receive any more notifications about scheduled task errors, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=cronjob_error&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>