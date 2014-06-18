<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _country_list.view.php 6264 2014-03-19 12:23:26Z yura $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_currency.class.php', 'Currency' );
load_funcs( 'regional/model/_regional.funcs.php' );

// Get params from request
$s = param( 's', 'string', '', true );

// Create query
$SQL = new SQL();
$SQL->SELECT( 'ctry_ID, ctry_code, ctry_name, curr_shortcut, curr_code, ctry_enabled, ctry_preferred, ctry_status, ctry_block_count' );
$SQL->FROM( 'T_regional__country' );
$SQL->FROM_add( 'LEFT JOIN T_regional__currency ON ctry_curr_ID=curr_ID' );
$SQL->ORDER_BY( '*, ctry_code ASC' );

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE( 'CONCAT_WS( " ", ctry_code, ctry_name, curr_code ) LIKE "%'.$DB->escape($s).'%"' );
}

// Create result set:
$Results = new Results( $SQL->get(), 'ctry_', '-D' );

$Results->title = T_('Countries').get_manual_link('countries_list');

/*
 * STATUS TD:
 */
function ctry_td_enabled( $ctry_enabled, $ctry_ID )
{
	$r = '';
	$redirect_ctrl = param( 'ctrl', 'string', 'countries' );

	if( $ctry_enabled == true )
	{
		$r .= action_icon( T_('Disable the country!'), 'bullet_full',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=disable_country&amp;ctry_ID='.$ctry_ID.'&amp;redirect_ctrl='.$redirect_ctrl.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the country!'), 'bullet_empty',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=enable_country&amp;ctry_ID='.$ctry_ID.'&amp;redirect_ctrl='.$redirect_ctrl.'&amp;'.url_crumb('country') ) );
	}
	return $r;

}

function ctry_td_preferred( $ctry_preferred, $ctry_ID )
{
	$r = '';
	$redirect_ctrl = param( 'ctrl', 'string', 'countries' );

	if( $ctry_preferred == true )
	{
		$r .= action_icon( T_('Remove from preferred countries'), 'bullet_full',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=disable_country_pref&amp;ctry_ID='.$ctry_ID.'&amp;redirect_ctrl='.$redirect_ctrl.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Add to preferred countries'), 'bullet_empty',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=enable_country_pref&amp;ctry_ID='.$ctry_ID.'&amp;redirect_ctrl='.$redirect_ctrl.'&amp;'.url_crumb('country') ) );
	}
	return $r;

}



$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'th_title' => T_('Enabled'),
		'order' => 'ctry_enabled',
		'td' => '%ctry_td_enabled( #ctry_enabled#, #ctry_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => /* TRANS: shortcut for preferred */ T_('Pref'),
		'th_title' => T_('Preferred'),
		'order' => 'ctry_preferred',
		'default_dir' => 'D',
		'td' => '%ctry_td_preferred( #ctry_preferred# , #ctry_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => T_('Status'),
		'td' => /* Check permission: */$current_User->check_perm( 'options', 'edit' ) ?
			/* Current user can edit Country */'<a href="#" rel="$ctry_status$">%ctry_status_title( #ctry_status# )%</a>' :
			/* No edit, only view the status */'%ctry_status_title( #ctry_status# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'country_status_edit',
		'order' => 'ctry_status',
		'extra' => array ( 'id' => '#ctry_ID#', 'style' => 'background-color: %ctry_status_color( "#ctry_status#" )%;', 'format_to_output' => false )
	);

if( $ctrl == 'antispam' )
{ // Under the antispam main menu add column to show the blocked requests by this country
	$Results->cols[] = array(
		'th' => T_('Block count'),
		'td' => '$ctry_block_count$',
		'th_class' => 'shrinkwrap',
		'order' => 'ctry_block_count'
	);
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_countries( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_countries',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=countries' ),
		)
	);

$Results->cols[] = array(
						'th' => T_('Code'),
						'td_class' => 'center',
						'order' => 'ctry_code',
						'td' => '<strong>$ctry_code$</strong>',
					);


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'ctry_name',
							'td' => '<a href="?ctrl=countries&amp;ctry_ID=$ctry_ID$&amp;action=edit" title="'.T_('Edit this country...').'">
									%country_flag( #ctry_code#, #ctry_name# )% <strong>$ctry_name$</strong>
								</a>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'ctry_name',
							'td' => '%country_flag( #ctry_code#, #ctry_name# )% $ctry_name$',
						);

}

function country_regions_count( $country_ID )
{
	global $DB, $admin_url;

	$regions_count = $DB->get_var( '
		SELECT COUNT(rgn_ID)
		  FROM T_regional__region
		 WHERE rgn_ctry_ID = "'.$country_ID.'"' );

	if( $regions_count > 0 )
	{
		$regions_count = '<a href="'.$admin_url.'?ctrl=regions&amp;c='.$country_ID.'">'.$regions_count.'</a>';
	}

	return $regions_count;
}

$Results->cols[] = array(
						'th' => T_('Regions'),
						'td_class' => 'center',
						'td' => '%country_regions_count( #ctry_ID# )%',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap'
					);

$Results->cols[] = array(
						'th' => T_('Default Currency'),
						'td_class' => 'center',
						'order' => 'curr_code',
						'td' => '$curr_shortcut$ $curr_code$',
					);

/*
 * ACTIONS TD:
 */
function ctry_td_actions($ctry_enabled, $ctry_ID )
{
	$r = '';
	$redirect_ctrl = param( 'ctrl', 'string', 'countries' );

	if( $ctry_enabled == true )
	{
		$r .= action_icon( T_('Disable the country!'), 'deactivate',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=disable_country&amp;ctry_ID='.$ctry_ID.'&amp;redirect_ctrl='.$redirect_ctrl.'&amp;'.url_crumb('country') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the country!'), 'activate',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=enable_country&amp;ctry_ID='.$ctry_ID.'&amp;redirect_ctrl='.$redirect_ctrl.'&amp;'.url_crumb('country') ) );
	}
	$r .= action_icon( T_('Edit this country...'), 'edit',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;ctry_ID='.$ctry_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Duplicate this country...'), 'copy',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;ctry_ID='.$ctry_ID.'&amp;action=new' ) );
	$r .= action_icon( T_('Delete this country!'), 'delete',
										regenerate_url( 'ctrl,action', 'ctrl=countries&amp;ctry_ID='.$ctry_ID.'&amp;action=delete&amp;'.url_crumb('country') ) );

	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td' => '%ctry_td_actions( #ctry_enabled#, #ctry_ID# )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->global_icon( T_('Create a new country ...'), 'new',
				regenerate_url( 'ctrl,action', 'ctrl=countries&amp;action=new'), T_('New country').' &raquo;', 3, 4  );
}

$Results->display();

if( $current_User->check_perm( 'options', 'edit' ) )
{ // Check permission to edit Country:
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '.country_status_edit' ).editable( htsrv_url + 'async.php?action=country_status_edit&<?php echo url_crumb( 'country' ) ?>',
	{
	data : function( value, settings )
		{
			value = ajax_debug_clear( value );
			var re =  /rel="(.*)"/;
			var result = value.match(re);
			return {
				''         : '<?php echo ctry_status_title( '' ) ?>',
				'trusted'  : '<?php echo ctry_status_title( 'trusted' ) ?>',
				'suspect'  : '<?php echo ctry_status_title( 'suspect' ) ?>',
				'blocked'  : '<?php echo ctry_status_title( 'blocked' ) ?>',
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
			var id = jQuery( this ).attr( 'id' );
			console.log( id );
			return { ctry_ID: id }
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