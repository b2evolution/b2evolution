<?php
/**
 * This file implements the UI view for Tools > Email > Sent
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_EmailLog, $admin_url;

$Form = new Form( NULL, 'mail_log', 'post', 'compact' );

$Form->global_icon( T_('Cancel viewing!'), 'close', regenerate_url( 'blog' ) );

$Form->begin_form( 'fform', sprintf( T_('Mail log ID#%s'), $edited_EmailLog->ID ) );

$Form->begin_line( T_('Result'), NULL );
$result = emlog_result_info( $edited_EmailLog->result, array(), $edited_EmailLog->last_open_ts, $edited_EmailLog->last_click_ts );
$result .= ' <a href="'.url_add_param( $admin_url, array( 'ctrl' => 'email', 'tab' => 'return', 'email' => $edited_EmailLog->to ) ).'" class="'.button_class().' middle" title="'.format_to_output( T_('Go to return log'), 'htmlattr' ).'">'
		.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('Go to return log') ) ).' '.T_('Returns').'</a>';
$Form->info_field( '', $result );
$Form->end_line( NULL );


$Form->info( T_('Date'), mysql2localedatetime_spans( $edited_EmailLog->timestamp ) );

$deleted_user_note = '';
if( $edited_EmailLog->user_ID > 0 )
{
	$UserCache = & get_UserCache();
	if( $User = $UserCache->get_by_ID( $edited_EmailLog->user_ID, false ) )
	{
		$Form->info( T_('To User'), $User->get_identity_link() );
	}
	else
	{
		$deleted_user_note = '( '.T_( 'Deleted user' ).' )';
	}
}

$Form->begin_line( T_('To'), NULL );
$to_address = htmlspecialchars($edited_EmailLog->to).$deleted_user_note;
$to_address .= ' <a href="'.url_add_param( $admin_url, array( 'ctrl' => 'email', 'tab' => 'sent', 'email' => $edited_EmailLog->to ) ).'" class="'.button_class().' middle" title="'.format_to_output( T_('Go to return log'), 'htmlattr' ).'">'
		.get_icon( 'magnifier', 'imgtag', array( 'title' => T_('Go to send log') ) ).' '.T_('Send Log').'</a>';
$Form->info_field( '', $to_address );
$Form->end_line( NULL );

$Form->info( T_('Subject'), '<pre class="email_log"><span>'.htmlspecialchars($edited_EmailLog->subject).'</span></pre>' );

$Form->info( T_('Headers'), '<pre class="email_log"><span>'.htmlspecialchars($edited_EmailLog->headers).'</span></pre>' );

$mail_contents = mail_log_parse_message( $edited_EmailLog->headers, $edited_EmailLog->message );

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
$emlog_message = preg_replace( '~\$secret_content_start\$.*?\$secret_content_end\$~', '***secret-content-removed***', $edited_EmailLog->message );
$emlog_message = preg_replace( '~\$email_key_start\$(.*?)\$email_key_end\$~', '***prevent-tracking-through-log***$1', $emlog_message );
$Form->info( T_('Raw email source'), '<pre class="email_log_scroll"><span>'.htmlspecialchars( $emlog_message ).'</span></pre>' );

$Form->end_form();

?>