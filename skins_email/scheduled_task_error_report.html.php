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
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $htsrv_url, $admin_url, $baseurl;

// Default params:
$params = array_merge( array(
		'tasks' => array(),
	), $params );

echo '<p'.emailskin_style( '.p' ).'>'.T_('The following scheduled tasks have ended with error:')."</p>\n";
if( is_array( $params['tasks'] ) && count( $params['tasks'] ) )
{
	echo '<p'.emailskin_style( '.p' ).'><ul>';
	foreach( $params['tasks'] as $task )
	{
		echo '<li>'.$task['name'].': '.$task['message'].'</li>';
	}
	echo "</ul></p>\n";
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=crontab&ctst_timeout=1&ctst_error=1', T_( 'Review tasks with errors' ), 'div.buttons a+a.button_yellow' )."\n";
echo "</div>\n";


// Footer vars:
$params['unsubscribe_text'] = T_( 'You are a scheduled task admin, and you are receiving notifications when a scheduled tasks ends with error or timeout.' )."<br />\n"
			.T_( 'If you don\'t want to receive any more notifications about scheduled task errors, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=cronjob_error&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>