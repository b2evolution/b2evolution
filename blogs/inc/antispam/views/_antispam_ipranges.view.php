<?php
/**
 * This file display the Antispam IP ranges
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $UserSettings, $Plugins;

$ip_address = param( 'ip_address', 'string', '', true );

$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_antispam__iprange' );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( aipr_ID )' );
$CountSQL->FROM( 'T_antispam__iprange' );

if( !empty( $ip_address ) )
{ // Filter by IP address
	$ip_address = ip2int( $ip_address );

	$SQL->WHERE( 'aipr_IPv4start <= '.$DB->quote( $ip_address ) );
	$SQL->WHERE_and( 'aipr_IPv4end >= '.$DB->quote( $ip_address ) );

	$CountSQL->WHERE( 'aipr_IPv4start <= '.$DB->quote( $ip_address ) );
	$CountSQL->WHERE_and( 'aipr_IPv4end >= '.$DB->quote( $ip_address ) );
}

// Create result set:
$Results = new Results( $SQL->get(), 'aipr_', 'A', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

$Results->title = T_('IP Ranges').' ('.$Results->total_rows.')'.get_manual_link('ip-ranges-tab');
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
		'all' => array( T_('All'), $admin_url.'?ctrl=antispam&amp;tab3=ipranges' ),
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
	);

$Results->cols[] = array(
		'th' => T_('Block count'),
		'td' => '$aipr_block_count$',
		'order' => 'aipr_block_count',
	);

// Get additional columns from the Plugins
$Plugins->trigger_event( 'GetAdditionalColumnsTable', array(
	'table'   => 'ipranges',
	'column'  => 'aipr_IPv4start',
	'Results' => $Results ) );

if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{	// Check permission to edit IP ranges:
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( TS_('Edit this IP range...'), 'properties',
					$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;iprange_ID=$aipr_ID$&amp;action=iprange_edit' )
					.action_icon( T_('Delete this IP range!'), 'delete',
						regenerate_url( 'iprange_ID,action', 'iprange_ID=$aipr_ID$&amp;action=iprange_delete&amp;'.url_crumb('iprange') ) ),
		);
}

$Results->global_icon( T_('Add a new IP range...'), 'new', regenerate_url( 'action', 'action=iprange_new'), T_('New IP range').' &raquo;', 3, 4  );

$Results->display();

if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
{	// Check permission to edit IP ranges:
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '.iprange_status_edit' ).editable( htsrv_url + 'async.php?action=iprange_status_edit&<?php echo url_crumb( 'iprange' ) ?>',
	{
	data : function( value, settings )
		{
			value = ajax_debug_clear( value );
			var re =  /rel="(.*)"/;
			var result = value.match(re);
			return {
				''         : '<?php echo aipr_status_title( '' ) ?>',
				'trusted'  : '<?php echo aipr_status_title( 'trusted' ) ?>',
				'suspect'  : '<?php echo aipr_status_title( 'suspect' ) ?>',
				'blocked'  : '<?php echo aipr_status_title( 'blocked' ) ?>',
				'selected' : result[1]
			}
		},
	type     : 'select',
	name     : 'new_status',
	tooltip  : 'Click to edit',
	event    : 'click',
	callback : function( settings, original )
		{
			//evoFadeSuccess( this );
			jQuery( this ).html( ajax_debug_clear( settings ) );
			var link = jQuery( this ).find( 'a' );
			jQuery( this ).css( 'background-color', link.attr( 'color' ) );
			link.removeAttr( 'color' );
		},
	onsubmit: function( settings, original ) {
	},
	submitdata: function( value, settings ) {
			var id = jQuery( ':first', jQuery( this ).parent() ).text();
			return { iprange_ID: id }
		},
	onerror : function( settings, original, xhr ) {
			evoFadeFailure( original );
		}
	} );
} );
</script>
<?php
}

?>