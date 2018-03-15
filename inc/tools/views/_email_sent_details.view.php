<?php
/**
 * This file implements the UI view for Tools > Email > Sent
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

global $MailLog;

$Form = new Form( NULL, 'mail_log', 'post', 'compact' );

$Form->global_icon( T_('Cancel viewing!'), 'close', regenerate_url( 'blog' ) );

$Form->begin_form( 'fform', sprintf( T_('Mail log ID#%s'), $MailLog->emlog_ID ) );

$Form->info( T_('Result'), emlog_result_info( $MailLog->emlog_result, array(), $MailLog->emlog_last_open_ts, $MailLog->emlog_last_click_ts ) );

$Form->info( T_('Date'), mysql2localedatetime_spans( $MailLog->emlog_timestamp ) );

$deleted_user_note = '';
if( $MailLog->emlog_user_ID > 0 )
{
	$UserCache = & get_UserCache();
	if( $User = $UserCache->get_by_ID( $MailLog->emlog_user_ID, false ) )
	{
		$Form->info( T_('To User'), $User->get_identity_link() );
	}
	else
	{
		$deleted_user_note = '( '.T_( 'Deleted user' ).' )';
	}
}

$Form->info( T_('To'), '<pre class="email_log"><span>'.htmlspecialchars($MailLog->emlog_to).$deleted_user_note.'</span></pre>' );

$Form->info( T_('Subject'), '<pre class="email_log"><span>'.htmlspecialchars($MailLog->emlog_subject).'</span></pre>' );

$Form->info( T_('Headers'), '<pre class="email_log"><span>'.htmlspecialchars($MailLog->emlog_headers).'</span></pre>' );

$mail_contents = mail_log_parse_message( $MailLog->emlog_headers, $MailLog->emlog_message );

if( !empty( $mail_contents ) )
{
	if( !empty( $mail_contents['text'] ) )
	{ // Display Plain Text content
		$plain_text_content = preg_replace( '~\$secret_content_start\$.*?\$secret_content_end\$~', '***secret-content-removed***', $mail_contents['text']['content'] );
		$plain_text_content = preg_replace( '~\$email_key_start\$(.*?)\$email_key_end\$~', '***prevent-tracking-through-log***$1', $plain_text_content );

		$Form->info( T_('Text content'), $mail_contents['text']['type']
				.'<pre class="email_log_scroll"><span>'.htmlspecialchars( $plain_text_content ).'</span></pre>' );
	}

	if( !empty( $mail_contents['html'] ) )
	{ // Display HTML content

		$html_content = preg_replace( '~\$secret_content_start\$.*?\$secret_content_end\$~', '***secret-content-removed***', $mail_contents['html']['content'] );
		$html_content = preg_replace( '~\$email_key_start\$(.*?)\$email_key_end\$~', '***prevent-tracking-through-log***$1', $html_content );

		if( ! empty( $mail_contents['html']['head_style'] ) )
		{ // Print out all styles of email message
			echo '<style>'.$mail_contents['html']['head_style'].'</style>';
		}
		$div_html_class = empty( $mail_contents['html']['body_class'] ) ? '' : ' '.$mail_contents['html']['body_class'];
		$div_html_style = empty( $mail_contents['html']['body_style'] ) ? '' : ' style="'.$mail_contents['html']['body_style'].'"';
		$Form->info( T_('HTML content'), $mail_contents['html']['type']
				.'<div class="email_log_html'.$div_html_class.'"'.$div_html_style.'>'.$html_content.'</div>' );
	}
}
$emlog_message = preg_replace( '~\$secret_content_start\$.*?\$secret_content_end\$~', '***secret-content-removed***', $MailLog->emlog_message );
$emlog_message = preg_replace( '~\$email_key_start\$(.*?)\$email_key_end\$~', '***prevent-tracking-through-log***$1', $emlog_message );
$Form->info( T_('Raw email source'), '<pre class="email_log_scroll"><span>'.htmlspecialchars( $emlog_message ).'</span></pre>' );

$Form->end_form();

?>