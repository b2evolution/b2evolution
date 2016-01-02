<?php
/**
 * This file implements the UI view for Tools > Email > Sent
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

global $MailReturn;

$Form = new Form( NULL, 'mail_returns', 'post', 'compact' );

$Form->global_icon( T_('Cancel viewing!'), 'close', regenerate_url( 'blog' ) );

$Form->begin_form( 'fform', sprintf( T_('Returned mail ID#%s'), $MailReturn->emret_ID ) );

$Form->info( T_('Date'), mysql2localedatetime_spans( $MailReturn->emret_timestamp, 'Y-m-d', 'H:i:sP' ) );

$Form->info( T_('Error Type'), dre_decode_error_type( $MailReturn->emret_errtype ) );

$Form->info( T_('Address'), '<pre class="email_log"><span>'.htmlspecialchars($MailReturn->emret_address).'</span></pre>' );

$Form->info( T_('Error'), '<pre class="email_log"><span>'.htmlspecialchars($MailReturn->emret_errormsg).'</span></pre>' );

$Form->info( T_('Headers'), '<pre class="email_log_scroll"><span>'.htmlspecialchars($MailReturn->emret_headers).'</span></pre>' );

$Form->info( T_('Message'), '<pre class="email_log_scroll"><span>'.htmlspecialchars($MailReturn->emret_message).'</span></pre>' );

$Form->end_form();

?>