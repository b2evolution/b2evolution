<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that one or more ((ScheduledTasks)) failed to execute properly.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url, $admin_url, $baseurl;

// Default params:
$params = array_merge( array(
		'tasks' => array(),
	), $params );

echo T_('The following scheduled tasks have ended with error:')."\n";
if( is_array( $params['tasks'] ) && count( $params['tasks'] ) )
{
	foreach( $params['tasks'] as $task )
	{
		echo '- '.$task['name'].': '.$task['message']."\n";
	}
}
echo "\n";

$tasks_url = $admin_url.'?ctrl=crontab&ctst_timeout=1&ctst_error=1';
echo sprintf( T_('To see more information about these tasks click here: %s'), $tasks_url );

// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a scheduled task admin, and you are receiving notifications when a scheduled tasks ends with error or timeout.' )."\n".
		T_( 'If you don\'t want to receive any more notifications about scheduled task errors, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=cronjob_error&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>