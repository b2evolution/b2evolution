<?php
/**
 * This file implements the UI view for the User Agents stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url, $current_User, $UserSettings;

global $dtyp_normal, $dtyp_searcheng, $dtyp_aggregator, $dtyp_email, $dtyp_unknown;

// For the referring domains list:
param( 'dtyp_normal', 'integer', 0, true );
param( 'dtyp_searcheng', 'integer', 0, true );
param( 'dtyp_aggregator', 'integer', 0, true );
param( 'dtyp_email', 'integer', 0, true );
param( 'dtyp_unknown', 'integer', 0, true );

if( !$dtyp_normal && !$dtyp_searcheng && !$dtyp_aggregator && !$dtyp_email && !$dtyp_unknown )
{	// Set default status filters:
	$dtyp_normal = 1;
	$dtyp_searcheng = 1;
	$dtyp_aggregator = 1;
	$dtyp_email = 1;
	$dtyp_unknown = 1;
}


echo '<h2>'.T_('Referring domains').'</h2>';

$SQL = new SQL();

$selected_agnt_types = array();
if( $dtyp_normal ) $selected_agnt_types[] = "'normal'";
if( $dtyp_searcheng ) $selected_agnt_types[] = "'searcheng'";
if( $dtyp_aggregator ) $selected_agnt_types[] = "'aggregator'";
if( $dtyp_email ) $selected_agnt_types[] = "'email'";
if( $dtyp_unknown ) $selected_agnt_types[] = "'unknown'";
$SQL->WHERE( 'dom_type IN ( ' . implode( ', ', $selected_agnt_types ) . ' )' );

// Exclude hits of type "self" and "admin":
// TODO: fp>implement filter checkboxes, not a hardwired filter
//$where_clause .= ' AND hit_referer_type NOT IN ( "self", "admin" )';

if( !empty($blog) )
{
	$SQL->WHERE_and( 'hit_blog_ID = ' . $blog );
}

$SQL->SELECT( 'SQL_NO_CACHE COUNT( hit_ID ) AS hit_count' );
$SQL->FROM( 'T_basedomains LEFT OUTER JOIN T_hitlog ON dom_ID = hit_referer_dom_ID' );

$total_hit_count = $DB->get_var( $SQL->get(), 0, 0, 'Get total hit count - referred hits only' );


// Create result set:
$SQL->SELECT( 'SQL_NO_CACHE dom_name, dom_status, dom_type, COUNT( hit_ID ) AS hit_count' );
$SQL->GROUP_BY( 'dom_ID' );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( DISTINCT dom_ID )' );
$CountSQL->FROM( $SQL->get_from( '' ) );
$CountSQL->WHERE( $SQL->get_where( '' ) );

$Results = new Results( $SQL->get(), 'refdom_', '---D', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_basedomains( & $Form )
{
	global $blog, $dtyp_normal, $dtyp_searcheng, $dtyp_aggregator, $dtyp_unknown;

	$Form->checkbox( 'dtyp_normal', $dtyp_normal, T_('Regular sites') );
	$Form->checkbox( 'dtyp_searcheng', $dtyp_searcheng, T_('Search engines') );
	$Form->checkbox( 'dtyp_aggregator', $dtyp_aggregator, T_('Feed aggregators') );
	$Form->checkbox( 'dtyp_email', $dtyp_aggregator, T_('Email domains') );
	$Form->checkbox( 'dtyp_unknown', $dtyp_unknown, T_('Unknown') );
}
$Results->filter_area = array(
	'callback' => 'filter_basedomains',
	'url_ignore' => 'results_refdom_page,dtyp_normal,dtyp_searcheng,dtyp_aggregator,dtyp_unknown',	// ignore page param and checkboxes
	'presets' => array(
			'browser' => array( T_('Regular'), '?ctrl=stats&amp;tab=domains&amp;dtyp_normal=1&amp;blog='.$blog ),
			'robot'   => array( T_('Search engines'), '?ctrl=stats&amp;tab=domains&amp;dtyp_searcheng=1&amp;blog='.$blog ),
			'rss'     => array( T_('Aggregators'), '?ctrl=stats&amp;tab=domains&amp;dtyp_aggregator=1&amp;blog='.$blog ),
			'email'   => array( T_('Email'), '?ctrl=stats&amp;tab=domains&amp;dtyp_email=1&amp;blog='.$blog ),
			'unknown' => array( T_('Unknown'), '?ctrl=stats&amp;tab=domains&amp;dtyp_unknown=1&amp;blog='.$blog ),
			'all'     => array( T_('All'), '?ctrl=stats&amp;tab=domains&amp;dtyp_normal=1&amp;dtyp_searcheng=1&amp;dtyp_aggregator=1&amp;dtyp_unknown=1&amp;blog='.$blog ),
		)
	);


$Results->title = T_('Referring domains').get_manual_link('referring-domains-tab');

$Results->cols[] = array(
						'th' => T_('Domain name'),
						'order' => 'dom_name',
						'td' => '$dom_name$',
						'total' => '<strong>'.T_('Global total').'</strong>',
					);

if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[] = array(
							'th' => T_('Type'),
							'order' => 'dom_type',
							'td_class' => 'dom_type_edit',
							'td' => '<a href="#" rel="$dom_type$">%stats_dom_type_title( #dom_type# )%</a>',
							'total' => '',
						);
}
else
{
	$Results->cols[] = array(
							'th' => T_('Type'),
							'order' => 'dom_type',
							'td_class' => 'dom_type_edit',
							'td' => '%stats_dom_type_title( #dom_type# )%',
							'total' => '',
						);
}
$Results->cols[] = array(
						'th' => T_('Status'),
						'order' => 'dom_status',
						'td' => '$dom_status$',
						'total' => '',
					);

$Results->cols[] = array(
						'th' => T_('Hit count'),
						'order' => 'hit_count',
						'td_class' => 'right',
						'total_class' => 'right',
						'td' => '$hit_count$',
						'total' => $total_hit_count,
					);

$Results->cols[] = array(
						'th' => T_('Hit %'),
						'order' => 'hit_count',
						'td_class' => 'right',
						'total_class' => 'right',
						'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
						'total' => '%percentage( 100, 100 )%',
					);

// Display results:
$Results->display();

if( $current_User->check_perm( 'stats', 'edit' ) )
{
?>
<script type="text/javascript">
	jQuery(document).ready(function(){
	jQuery('.dom_type_edit').editable( htsrv_url+'async.php?action=dom_type_edit&<?php echo url_crumb( 'domtype' )?>', {
	data	: function(value, settings){
			value = ajax_debug_clear( value );
			var re =  /rel="(.*)"/;
			var result = value.match(re);
			return {'unknown':'<?php echo stats_dom_type_title( 'unknown', true ) ?>','normal':'<?php echo stats_dom_type_title( 'normal', true ) ?>','searcheng':'<?php echo stats_dom_type_title( 'searcheng', true ) ?>', 'aggregator':'<?php echo stats_dom_type_title( 'aggregator', true ) ?>', 'email':'<?php echo stats_dom_type_title( 'email', true ) ?>', 'selected' : result[1]}
			},
	type     : 'select',
	name     : 'new_dom_type',
	tooltip  : 'Click to edit',
	event    : 'click',
	callback : function (settings, original){
			evoFadeSuccess(this);
		},
	onsubmit: function(settings, original) {},
	submitdata : function(value, settings) {
			var name =  jQuery(':first',jQuery(this).parent()).text();
			return {dom_name: name}
		},
	onerror : function(settings, original, xhr) {
			evoFadeFailure(original);
		}
	});

	});
</script>
<?php
}

?>