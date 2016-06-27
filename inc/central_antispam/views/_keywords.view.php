<?php
/**
 * This file implements the UI view for Central Antispam > Keywords
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $central_antispam_Module, $UserSettings, $admin_url, $current_User;

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'cakw_ID, cakw_keyword, cakw_status, cakw_statuschange_ts, cakw_lastreport_ts, COUNT( carpt_cakw_ID ) as report_num' );
$SQL->FROM( 'T_centralantispam__keyword' );
$SQL->FROM_add( 'LEFT JOIN T_centralantispam__report ON carpt_cakw_ID = cakw_ID' );
$SQL->GROUP_BY( 'cakw_ID' );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( cakw_ID )' );
$CountSQL->FROM( 'T_centralantispam__keyword' );

$Results = new Results( $SQL->get(), 'cakw_', '---D', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

$Results->title = $central_antispam_Module->T_('Keywords');

$Results->global_icon( $central_antispam_Module->T_('Import from local antispam list...'), 'new', regenerate_url( 'action', 'action=import' ), $central_antispam_Module->T_('Import from local antispam list...'), 3, 4, array( 'class' => 'action_icon btn-primary' ) );

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('ID'),
		'order' => 'cakw_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$cakw_ID$',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Keyword'),
		'order' => 'cakw_keyword',
		'td' => '<a href="'.$admin_url.'?ctrl=central_antispam&amp;action=keyword_edit&amp;cakw_ID=$cakw_ID$">$cakw_keyword$</a>',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Status'),
		'order' => 'cakw_status',
		'th_class' => 'shrinkwrap',
		'td_class' => 'cakeyword_status_edit',
		'td' =>  /* Check permission: */$current_User->check_perm( 'spamblacklist', 'edit' ) ?
			/* Current user can edit keyword */'<a href="#" rel="$cakw_status$" style="color:#FFF">%ca_get_keyword_status_title( #cakw_status# )%</a>' :
			/* No edit, only view the status */'%ca_get_keyword_status_title( #cakw_status# )%',
		'extra' => array ( 'style' => 'background-color: %ca_get_keyword_status_color( "#cakw_status#" )%;color:#FFF', 'format_to_output' => false ),
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Last change'),
		'th_class' => 'shrinkwrap',
		'order' => 'cakw_statuschange_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #cakw_statuschange_ts# )%',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Reports'),
		'order' => 'report_num',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$report_num$',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Last report'),
		'th_class' => 'shrinkwrap',
		'order' => 'cakw_lastreport_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #cakw_lastreport_ts# )%',
	);

function ac_results_keyword_actions( $cakw_ID )
{
	global $central_antispam_Module, $admin_url;

	return action_icon( $central_antispam_Module->T_('Edit this keyword...'), 'edit', $admin_url.'?ctrl=central_antispam&amp;action=keyword_edit&amp;cakw_ID='.$cakw_ID );
}
$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Actions'),
		'td_class' => 'shrinkwrap',
		'td' => '%ac_results_keyword_actions( #cakw_ID# )%'
	);


// Display results:
$Results->display();

if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{ // Check permission to edit central antispam keyword:
	// Print JS to edit status of central antispam keyword:
	echo_editable_column_js( array(
		'column_selector' => '.cakeyword_status_edit',
		'ajax_url'        => get_secure_htsrv_url().'async.php?action=cakeyword_status_edit&'.url_crumb( 'cakeyword' ),
		'options'         => ca_get_keyword_statuses(),
		'new_field_name'  => 'new_status',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'cakw_ID',
		'colored_cells'   => true ) );
}
?>