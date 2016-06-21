<?php
/**
 * This file display the keyword form
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

global $central_antispam_Module, $edited_CaKeyword, $UserSettings, $DB;

// Form to edit the keyword:
$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cakeyword' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'keywords' );
$Form->hidden( 'cakw_ID', $edited_CaKeyword->ID );

$Form->begin_fieldset( $central_antispam_Module->T_('Edit keyword') );

$Form->info( $central_antispam_Module->T_('Keyword'), $edited_CaKeyword->keyword );

$Form->select_input_array( 'cakw_status', $edited_CaKeyword->status, ca_get_keyword_statuses(), $central_antispam_Module->T_('Status') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'actionArray[keyword_save]', $central_antispam_Module->T_('Save changes!'), 'SaveButton' ) ) );


// Reports of the edited keyword:
$SQL = new SQL();
$SQL->SELECT( 'casrc_baseurl, carpt_ts' );
$SQL->FROM( 'T_centralantispam__report' );
$SQL->FROM_add( 'INNER JOIN T_centralantispam__source ON carpt_casrc_ID = casrc_ID' );
$SQL->WHERE( 'carpt_cakw_ID = '.$DB->quote( $edited_CaKeyword->ID ) );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( carpt_cakw_ID )' );
$CountSQL->FROM( 'T_centralantispam__report' );
$CountSQL->WHERE( 'carpt_cakw_ID = '.$DB->quote( $edited_CaKeyword->ID ) );

$Results = new Results( $SQL->get(), 'carpt_', '-D', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

$Results->title = $central_antispam_Module->T_('Reports');

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Url'),
		'order' => 'casrc_baseurl',
		'td' => '$casrc_baseurl$',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Date'),
		'th_class' => 'shrinkwrap',
		'order' => 'carpt_ts',
		'td' => '%mysql2localedatetime_spans( #carpt_ts# )%',
		'td_class' => 'timestamp',
	);

// Display results:
$Results->display();
?>