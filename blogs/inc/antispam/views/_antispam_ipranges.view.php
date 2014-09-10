<?php
/**
 * This file display the Antispam IP ranges
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _antispam_ipranges.view.php 849 2012-02-16 09:09:09Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $UserSettings, $Plugins, $tab;

$tab_param = empty( $tab ) ? '' : '&amp;tab='.$tab;

$ip_address = param( 'ip_address', 'string', '', true );

$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_antispam__iprange' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( aipr_ID )' );
$count_SQL->FROM( 'T_antispam__iprange' );

if( !empty( $ip_address ) )
{ // Filter by IP address
	$ip_address = ip2int( $ip_address );

	$SQL->WHERE( 'aipr_IPv4start <= '.$DB->quote( $ip_address ) );
	$SQL->WHERE_and( 'aipr_IPv4end >= '.$DB->quote( $ip_address ) );

	$count_SQL->WHERE( 'aipr_IPv4start <= '.$DB->quote( $ip_address ) );
	$count_SQL->WHERE_and( 'aipr_IPv4end >= '.$DB->quote( $ip_address ) );
}

// Create result set:
$Results = new Results( $SQL->get(), 'aipr_', 'A', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('IP Ranges').' ('.$Results->get_total_rows().')'.get_manual_link( 'ip-ranges' );
$Results->Cache = get_IPRangeCache();

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_email_blocked( & $Form )
{
	$Form->text_input( 'ip_address', get_param( 'ip_address' ), 40, T_('IP address') );
}
$Results->filter_area = array(
	'callback' => 'filter_email_blocked',
	'presets' => array(
		'all' => array( T_('All'), $admin_url.'?ctrl=antispam'.$tab_param.'&amp;tab3=ipranges' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('ID'),
		'td' => '$aipr_ID$',
		'order' => 'aipr_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('Status'),
		'td' => /* Check permission: */$current_User->check_perm( 'spamblacklist', 'edit' ) ?
			/* Current user can edit IP ranges */'<a href="#" rel="$aipr_status$">%aipr_status_title( #aipr_status# )%</a>' :
			/* No edit, only view the status */'%aipr_status_title( #aipr_status# )%',
		'td_class' => 'iprange_status_edit',
		'order' => 'aipr_status',
		'extra' => array ( 'style' => 'background-color: %aipr_status_color( "#aipr_status#" )%;', 'format_to_output' => false )
	);

$Results->cols[] = array(
		'th' => T_('IP Range Start'),
		'td' => /* Check permission: */$current_User->check_perm( 'spamblacklist', 'edit' ) ?
			/* Current user can edit IP ranges */'<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;iprange_ID=$aipr_ID$&amp;action=iprange_edit">%int2ip( #aipr_IPv4start# )%</a>' :
			/* No edit, only view the IP address */'%int2ip( #aipr_IPv4start# )%',
		'order' => 'aipr_IPv4start',
	);

$Results->cols[] = array(
		'th' => T_('IP Range End'),
		'td' => /* Check permission: */$current_User->check_perm( 'spamblacklist', 'edit' ) ?
			/* Current user can edit IP ranges */'<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;iprange_ID=$aipr_ID$&amp;action=iprange_edit">%int2ip( #aipr_IPv4end# )%</a>' :
			/* No edit, only view the IP address */'%int2ip( #aipr_IPv4end# )%',
		'order' => 'aipr_IPv4end',
	);

$Results->cols[] = array(
		'th' => T_('User count'),
		'td' => '$aipr_user_count$',
		'order' => 'aipr_user_count',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('Anon. contact form submits'),
		'td' => '$aipr_contact_email_count$',
		'order' => 'aipr_contact_email_count',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('Block count'),
		'td' => '$aipr_block_count$',
		'order' => 'aipr_block_count',
		'default_dir' => 'D',
	);

// Get additional columns from the Plugins
$Plugins->trigger_event( 'GetAdditionalColumnsTable', array(
	'table'   => 'ipranges',
	'column'  => 'aipr_IPv4start',
	'Results' => $Results ) );

if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{ // Check permission to edit IP ranges:

	/**
	 * Get actions links for IP range
	 *
	 * @param integer IP range ID
	 * @param string Current tab value
	 * @return string HTML links to edit and delete IP range
	 */
	function antispam_ipranges_actions( $aipr_ID, $tab_param )
	{
		// A link to edit IP range
		$r = action_icon( T_('Edit this IP range...'), 'properties',
						$admin_url.'?ctrl=antispam'.$tab_param.'&amp;tab3=ipranges&amp;iprange_ID='.$aipr_ID.'&amp;action=iprange_edit' );

		// A link to delete IP range
		$r .= action_icon( T_('Delete this IP range!'), 'delete',
						regenerate_url( 'iprange_ID,action', 'iprange_ID='.$aipr_ID.'&amp;action=iprange_delete&amp;'.url_crumb( 'iprange' ) ) );

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => '%antispam_ipranges_actions( #aipr_ID#, "'.$tab_param.'" )%',
		);
}

$Results->global_icon( T_('Add a new IP range...'), 'new', regenerate_url( 'action', 'action=iprange_new'), T_('New IP range').' &raquo;', 3, 4  );

$Results->display();

if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{ // Check permission to edit IP ranges:
	// Print JS to edit status of IP range
	echo_editable_column_js( array(
		'column_selector' => '.iprange_status_edit',
		'ajax_url'        => get_secure_htsrv_url().'async.php?action=iprange_status_edit&'.url_crumb( 'iprange' ),
		'options'         => aipr_status_titles(),
		'new_field_name'  => 'new_status',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'iprange_ID',
		'colored_cells'   => true ) );
}

?>