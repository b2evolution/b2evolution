<?php
/**
 * This file implements the UI view for Tools > Email > Sent
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @version $Id: _email_sent_details.view.php 349 2011-11-18 11:18:14Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $MailReturn;

$Form = new Form( NULL, 'mail_returns', 'post', 'compact' );

$Form->global_icon( T_('Cancel viewing!'), 'close', regenerate_url( 'blog' ) );

$Form->begin_form( 'fform', sprintf( T_('Returned mail ID#%s'), $MailReturn->emret_ID ) );

$Form->info( T_('Date'), mysql2localedatetime_spans( $MailReturn->emret_timestamp, 'Y-m-d', 'H:i:sP' ) );

$Form->info( T_('Error Type'), dre_decode_error_type( $MailReturn->emret_errtype ) );

$Form->info( T_('Address'), '<pre class="email_log"><span>'.evo_htmlspecialchars($MailReturn->emret_address).'</span></pre>' );

$Form->info( T_('Error'), '<pre class="email_log"><span>'.evo_htmlspecialchars($MailReturn->emret_errormsg).'</span></pre>' );

$Form->info( T_('Headers'), '<pre class="email_log_scroll"><span>'.evo_htmlspecialchars($MailReturn->emret_headers).'</span></pre>' );

$Form->info( T_('Message'), '<pre class="email_log_scroll"><span>'.evo_htmlspecialchars($MailReturn->emret_message).'</span></pre>' );

$Form->end_form();

?>