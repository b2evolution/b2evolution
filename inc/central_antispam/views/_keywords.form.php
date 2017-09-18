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

param_action( '', true );
$creating = $edited_CaKeyword->ID == 0;

// Form to edit the keyword:
$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cakeyword' );
$Form->hidden_ctrl();
$Form->hidden( 'tab', 'keywords' );
if( ! $creating )
{
	$Form->hidden( 'cakw_ID', $edited_CaKeyword->ID );
}

$Form->begin_fieldset( $creating ? T_('Add keyword') : T_('Edit keyword') );

$Form->text_input( 'cakw_keyword', $edited_CaKeyword->keyword, 32, T_('Keyword'), '', array( 'maxlength' => 2000, 'required' => true ) );

$Form->select_input_array( 'cakw_status', $edited_CaKeyword->status, ca_get_keyword_statuses(), T_('Status') );

$Form->end_fieldset();

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[keyword_create]', T_('Add keyword'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[keyword_save]', T_('Save changes!'), 'SaveButton' ) ) );
}


if( ! $creating )
{
	// Reports of the edited keyword:
	$SQL = new SQL();
	$SQL->SELECT( 'casrc_ID, casrc_baseurl, carpt_ts' );
	$SQL->FROM( 'T_centralantispam__report' );
	$SQL->FROM_add( 'INNER JOIN T_centralantispam__source ON carpt_casrc_ID = casrc_ID' );
	$SQL->WHERE( 'carpt_cakw_ID = '.$DB->quote( $edited_CaKeyword->ID ) );

	$CountSQL = new SQL();
	$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( carpt_cakw_ID )' );
	$CountSQL->FROM( 'T_centralantispam__report' );
	$CountSQL->WHERE( 'carpt_cakw_ID = '.$DB->quote( $edited_CaKeyword->ID ) );

	$Results = new Results( $SQL->get(), 'carpt_', '-D', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

	$Results->title = T_('Reports');

	function get_link_for_url( $id, $url )
	{
		global $current_User;

		if( $current_User->check_perm( 'centralantispam', 'edit' ) )
		{ // Not reserved id AND current User has permission to edit the global settings
			$ret_url = '<a href="'.regenerate_url( 'action,tab,cakw_ID', 'action=source_edit&amp;tab=reporters&amp;casrc_ID='.$id ).'">'.$url.'</a>';
		}
		else
		{
			$ret_url = $url;
		}

		return '<strong>'.$ret_url.'</strong>';
	}

	$Results->cols[] = array(
			'th' => T_('Url'),
			'order' => 'casrc_baseurl',
			'td' => '%get_link_for_url( #casrc_ID#, #casrc_baseurl# )%',
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
}
?>