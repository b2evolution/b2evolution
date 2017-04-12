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

$keywords = param( 'keywords', 'string', '', true );
$status = param( 'status', 'string', '', true );

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'cakw_ID, cakw_keyword, cakw_status, cakw_statuschange_ts, cakw_lastreport_ts' );
$SQL->SELECT_add( ', COUNT( carpt_cakw_ID ) as report_num_total' );
$SQL->SELECT_add( ', COUNT( s1.casrc_ID ) as report_num_trusted' );
$SQL->SELECT_add( ', COUNT( s2.casrc_ID ) as report_num_promising' );
$SQL->SELECT_add( ', COUNT( s3.casrc_ID ) as report_num_unknown' );
$SQL->FROM( 'T_centralantispam__keyword' );
$SQL->FROM_add( 'LEFT JOIN T_centralantispam__report ON carpt_cakw_ID = cakw_ID' );
$SQL->FROM_add( 'LEFT JOIN T_centralantispam__source s1 ON carpt_casrc_ID = s1.casrc_ID AND s1.casrc_status = "trusted"' );
$SQL->FROM_add( 'LEFT JOIN T_centralantispam__source s2 ON carpt_casrc_ID = s2.casrc_ID AND s2.casrc_status = "promising"' );
$SQL->FROM_add( 'LEFT JOIN T_centralantispam__source s3 ON carpt_casrc_ID = s3.casrc_ID AND s3.casrc_status = "unknown"' );
$SQL->GROUP_BY( 'cakw_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( cakw_ID )' );
$count_SQL->FROM( 'T_centralantispam__keyword' );

if( ! empty( $keywords ) )
{	// Filter by keywords:
	$SQL->add_search_field( 'cakw_keyword' );
	$SQL->WHERE_kw_search( $keywords, 'AND' );
	$count_SQL->add_search_field( 'cakw_keyword' );
	$count_SQL->WHERE_kw_search( $keywords, 'AND' );
}

if( ! empty( $status ) )
{	// Filter by status:
	$SQL->WHERE_and( 'cakw_status = '.$DB->quote( $status ) );
	$count_SQL->WHERE_and( 'cakw_status = '.$DB->quote( $status ) );
}

$Results = new Results( $SQL->get(), 'cakw_', '---D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Keywords');

$Results->global_icon( T_('Add keyword'), 'new', regenerate_url( 'action', 'action=keyword_new' ), T_('Add keyword'), 3, 4, array( 'class' => 'action_icon btn-primary' ) );
$Results->global_icon( T_('Import from local antispam list...'), 'new', regenerate_url( 'action', 'action=import' ), T_('Import from local antispam list...'), 3, 4, array( 'class' => 'action_icon btn-default' ) );


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_central_antispam( & $Form )
{
	$Form->text( 'keywords', get_param( 'keywords' ), 20, T_('Keywords'), T_('Separate with space'), 50 );

	$Form->select_input_array( 'status', get_param( 'status' ), array( '' => T_('All') ) + ca_get_keyword_statuses(), T_('Status') );
}
$Results->filter_area = array(
	'callback' => 'filter_central_antispam',
	'presets' => array(
		'all' => array( T_('All keywords'), $admin_url.'?ctrl=central_antispam' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'cakw_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$cakw_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Keyword'),
		'order' => 'cakw_keyword',
		'td' => '<a href="'.$admin_url.'?ctrl=central_antispam&amp;action=keyword_edit&amp;cakw_ID=$cakw_ID$">$cakw_keyword$</a>',
	);

$Results->cols[] = array(
		'th' => T_('Status'),
		'order' => 'cakw_status',
		'th_class' => 'shrinkwrap',
		'td_class' => 'jeditable_cell cakeyword_status_edit',
		'td' =>  /* Check permission: */$current_User->check_perm( 'centralantispam', 'edit' ) ?
			/* Current user can edit keyword */'<a href="#" rel="$cakw_status$" style="color:#FFF">%ca_get_keyword_status_title( #cakw_status# )%</a>' :
			/* No edit, only view the status */'%ca_get_keyword_status_title( #cakw_status# )%',
		'extra' => array ( 'style' => 'background-color: %ca_get_keyword_status_color( "#cakw_status#" )%;color:#FFF', 'format_to_output' => false ),
	);

$Results->cols[] = array(
		'th' => T_('Last change'),
		'th_class' => 'shrinkwrap',
		'order' => 'cakw_statuschange_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #cakw_statuschange_ts# )%',
	);

$Results->cols[] = array(
		'th_group' => T_('Reports'),
		'th' => T_('Trusted'),
		'order' => 'report_num_trusted',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$report_num_trusted$',
	);

$Results->cols[] = array(
		'th_group' => T_('Reports'),
		'th' => T_('Promising'),
		'order' => 'report_num_promising',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$report_num_promising$',
	);

$Results->cols[] = array(
		'th_group' => T_('Reports'),
		'th' => T_('Unknown'),
		'order' => 'report_num_unknown',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$report_num_unknown$',
	);

$Results->cols[] = array(
		'th_group' => T_('Reports'),
		'th' => T_('Total'),
		'order' => 'report_num_total',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$report_num_total$',
	);

$Results->cols[] = array(
		'th' => T_('Last report'),
		'th_class' => 'shrinkwrap',
		'order' => 'cakw_lastreport_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp compact_data',
		'td' => '%mysql2localedatetime_spans( #cakw_lastreport_ts# )%',
	);

function ac_results_keyword_actions( $cakw_ID )
{
	global $central_antispam_Module, $admin_url;

	return action_icon( T_('Edit this keyword...'), 'edit', $admin_url.'?ctrl=central_antispam&amp;action=keyword_edit&amp;cakw_ID='.$cakw_ID );
}
$Results->cols[] = array(
		'th' => T_('Actions'),
		'td_class' => 'shrinkwrap',
		'td' => '%ac_results_keyword_actions( #cakw_ID# )%'
	);


// Display results:
$Results->display();

if( $current_User->check_perm( 'centralantispam', 'edit' ) )
{	// Check permission to edit central antispam keyword:
	// Print JS to edit status of central antispam keyword:
	echo_editable_column_js( array(
		'column_selector' => '.cakeyword_status_edit',
		'ajax_url'        => get_htsrv_url().'action.php?mname=central_antispam&action=cakeyword_status_edit&'.url_crumb( 'cakeyword' ),
		'options'         => ca_get_keyword_statuses(),
		'new_field_name'  => 'new_status',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'cakw_ID',
		'colored_cells'   => true,
		'callback_code'   => 'jQuery( this ).next().html( jQuery( this ).find( "a" ).attr( "date" ) );'."\r\n"
		                    .'evoFadeSuccess( jQuery( this ).next().get( 0 ) );' ) );
}
?>