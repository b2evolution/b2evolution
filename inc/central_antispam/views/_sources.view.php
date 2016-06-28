<?php
/**
 * This file implements the UI view for Central Antispam > Reportes
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

global $central_antispam_Module, $UserSettings, $admin_url;

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'casrc_ID, casrc_baseurl, casrc_status, COUNT( carpt_casrc_ID ) as report_num' );
$SQL->FROM( 'T_centralantispam__source' );
$SQL->FROM_add( 'LEFT JOIN T_centralantispam__report ON carpt_casrc_ID = casrc_ID' );
$SQL->GROUP_BY( 'casrc_ID' );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( casrc_ID )' );
$CountSQL->FROM( 'T_centralantispam__source' );

$Results = new Results( $SQL->get(), 'casrc_', '-A', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

$Results->title = $central_antispam_Module->T_('Reporters');

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('ID'),
		'order' => 'casrc_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$casrc_ID$',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('URL'),
		'order' => 'casrc_baseurl',
		'td' => '<a href="'.$admin_url.'?ctrl=central_antispam&amp;tab=sources&amp;action=source_edit&amp;casrc_ID=$casrc_ID$">$casrc_baseurl$</a>',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Status'),
		'order' => 'casrc_status',
		'th_class' => 'shrinkwrap',
		'td' => '%ca_get_source_status_title( #casrc_status# )%',
	);

$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Reports'),
		'order' => 'report_num',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$report_num$',
	);

function ac_results_source_actions( $casrc_ID )
{
	global $central_antispam_Module, $admin_url;

	return action_icon( $central_antispam_Module->T_('Edit this reporter...'), 'edit', $admin_url.'?ctrl=central_antispam&amp;tab=sources&amp;action=source_edit&amp;casrc_ID='.$casrc_ID );
}
$Results->cols[] = array(
		'th' => $central_antispam_Module->T_('Actions'),
		'td_class' => 'shrinkwrap',
		'td' => '%ac_results_source_actions( #casrc_ID# )%'
	);


// Display results:
$Results->display();
?>