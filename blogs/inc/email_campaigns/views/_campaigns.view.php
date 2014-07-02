<?php
/**
 * This file implements the UI view for Emails > Campaigns
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
 * @version $Id: _campaigns.view.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $UserSettings;

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE ecmp_ID, ecmp_date_ts, ecmp_name, ecmp_email_title, ecmp_email_html, ecmp_email_text, ecmp_sent_ts' );
$SQL->FROM( 'T_email__campaign' );
$SQL->GROUP_BY( 'ecmp_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( ecmp_ID )' );
$count_SQL->FROM( 'T_email__campaign' );

$Results = new Results( $SQL->get(), 'emcmp_', 'D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Email campaigns');

if( $current_User->check_perm( 'emails', 'edit' ) )
{ // User must has a permission to edit emails
	$Results->global_icon( T_('Create new campaign...'), 'new', $admin_url.'?ctrl=campaigns&amp;action=new', T_('Create new campaign').' &raquo;', 3, 4 );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ecmp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$ecmp_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Date'),
		'order' => 'ecmp_date_ts',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #ecmp_date_ts#, "M-d" )%',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ecmp_name',
		'td' => '$ecmp_name$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('Email title'),
		'order' => 'ecmp_email_title',
		'td' => '$ecmp_email_title$',
	);

$Results->cols[] = array(
		'th' => T_('Sent'),
		'order' => 'ecmp_sent_ts',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #ecmp_sent_ts#, "M-d" )%',
	);

$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Edit this email campaign...'), 'properties', $admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$' )
			.action_icon( T_('Delete this email address!'), 'delete', regenerate_url( 'ecmp_ID,action', 'ecmp_ID=$ecmp_ID$&amp;action=delete&amp;'.url_crumb('campaign') ) )
	);

// Display results:
$Results->display();

?>