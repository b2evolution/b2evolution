<?php
/**
 * This file implements the UI view for Emails > Campaigns > Edit > HTML
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _campaigns_html.form.php 7827 2014-12-17 07:04:16Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $tab;
global $edited_EmailCampaign;

$Form = new Form( NULL, 'campaign_form' );
$Form->begin_form( 'fform' );

$Form->add_crumb( 'campaign' );
$Form->hidden( 'ctrl', 'campaigns' );
$Form->hidden( 'current_tab', $tab );
$Form->hidden( 'ecmp_ID', $edited_EmailCampaign->ID );

$Form->begin_fieldset( T_('HTML message') );
	$Form->info( T_('Name'), $edited_EmailCampaign->get( 'name' ) );
	$Form->info( T_('Email title'), $edited_EmailCampaign->get( 'email_title' ) );
	$Form->info( T_('Campaign created'), mysql2localedatetime_spans( $edited_EmailCampaign->get( 'date_ts' ), 'M-d' ) );
	$Form->info( T_('Last sent'), $edited_EmailCampaign->get( 'sent_ts' ) ? mysql2localedatetime_spans( $edited_EmailCampaign->get( 'sent_ts' ), 'M-d' ) : T_('Not sent yet') );
	$Form->textarea_input( 'ecmp_email_html', $edited_EmailCampaign->get( 'email_html' ), 20, T_('HTML Message'), array( 'required' => true ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Newsletter recipients') );
	$Form->info( T_('Currently selected recipients'), $edited_EmailCampaign->get_users_count(), '('.T_('Accounts which accept newsletter emails').') - <a href="'.$admin_url.'?ctrl=campaigns&amp;action=change_users&amp;ecmp_ID='.$edited_EmailCampaign->ID.'">'.T_('Change selection').' &gt;&gt;</a>' );
	$Form->info( T_('Already received'), $edited_EmailCampaign->get_users_count( 'accept' ), '('.T_('Accounts which have already been sent this newsletter').')' );
	$Form->info( T_('Ready to send'), $edited_EmailCampaign->get_users_count( 'wait' ), '('.T_('Accounts which have not been sent this newsletter yet').')' );
$Form->end_fieldset();

$buttons = array();
if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$buttons[] = array( 'submit', 'actionArray[save]', T_('Save HTML message'), 'SaveButton' );
}
$Form->end_form( $buttons );

?>