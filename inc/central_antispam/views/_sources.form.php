<?php
/**
 * This file display the source form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $central_antispam_Module, $edited_CaSource, $UserSettings, $DB;

// Form to edit the source:
$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'casource' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'reporters' );
$Form->hidden( 'casrc_ID', $edited_CaSource->ID );

$Form->begin_fieldset( T_('Edit reporter') );

$Form->info( T_('URL'), $edited_CaSource->baseurl );

$Form->select_input_array( 'casrc_status', $edited_CaSource->status, ca_get_source_statuses(), T_('Status') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[source_save]', T_('Save changes!'), 'SaveButton' ) ) );


// Reports of the edited source:
$SQL = new SQL();
$SQL->SELECT( 'cakw_ID, cakw_keyword, carpt_ts' );
$SQL->FROM( 'T_centralantispam__report' );
$SQL->FROM_add( 'INNER JOIN T_centralantispam__keyword ON carpt_cakw_ID = cakw_ID' );
$SQL->WHERE( 'carpt_casrc_ID = '.$DB->quote( $edited_CaSource->ID ) );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( carpt_casrc_ID )' );
$CountSQL->FROM( 'T_centralantispam__report' );
$CountSQL->WHERE( 'carpt_casrc_ID = '.$DB->quote( $edited_CaSource->ID ) );

$Results = new Results( $SQL->get(), 'carpt_', '-D', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

$Results->title = T_('Reports');

function get_link_for_keyword( $id, $keyword )
{
	global $current_User;

	if( $current_User->check_perm( 'centralantispam', 'edit' ) )
	{ // Not reserved id AND current User has permission to edit the global settings
		$ret_keyword = '<a href="'.regenerate_url( 'action,tab,casrc_ID', 'action=keyword_edit&amp;cakw_ID='.$id ).'">'.$keyword.'</a>';
	}
	else
	{
		$ret_keyword = $keyword;
	}

	return '<strong>'.$ret_keyword.'</strong>';
}

$Results->cols[] = array(
		'th' => T_('Keyword'),
		'order' => 'cakw_keyword',
		'td' => '%get_link_for_keyword( #cakw_ID#, #cakw_keyword# )%',
	);

$Results->cols[] = array(
		'th' => T_('Date'),
		'th_class' => 'shrinkwrap',
		'order' => 'carpt_ts',
		'td' => '%mysql2localedatetime_spans( #carpt_ts# )%',
		'td_class' => 'timestamp',
	);

// Display results:
$Results->display();
?>