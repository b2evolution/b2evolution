<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

// Get params from request
$s = param( 's', 'string', '', true ); // Search keyword
$c = param( 'c', 'integer', 0, true ); // Country
$r = param( 'r', 'integer', 0, true ); // Region

// Create query
$SQL = new SQL();
$SQL->SELECT( 'subrg_ID, subrg_code, subrg_name, subrg_enabled, subrg_preferred, ctry_ID, ctry_name, rgn_ID, rgn_name' );
$SQL->FROM( 'T_regional__subregion' );
$SQL->FROM_add( 'LEFT JOIN T_regional__region ON subrg_rgn_ID=rgn_ID' );
$SQL->FROM_add( 'LEFT JOIN T_regional__country ON rgn_ctry_ID=ctry_ID' );
$SQL->ORDER_BY( '*, ctry_name, rgn_name' );

$sql_where = array();
if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$sql_where[] = 'CONCAT_WS( " ", subrg_code, subrg_name ) LIKE "%'.$DB->escape($s).'%"';
}
if( $c > 0 )
{	// Filter by country:
	$sql_where[] = 'ctry_ID = "'.$DB->escape($c).'"';
}
if( $r > 0 )
{	// Filter by region:
	$sql_where[] = 'subrg_rgn_ID = "'.$DB->escape($r).'"';
}

if( count( $sql_where ) > 0 )
{	// Some filters are applied
	$SQL->WHERE( implode( ' AND ', $sql_where ) );
}

// Create result set:
$Results = new Results( $SQL->get(), 'subrg_', '-D' );

$Results->title = T_('Sub-regions/Departments/Counties').get_manual_link('countries_list');

/*
 * STATUS TD:
 */
function subrg_td_enabled( $subrg_enabled, $subrg_ID )
{

	global $dispatcher;

	$r = '';

	if( $subrg_enabled == true )
	{
		$r .= action_icon( T_('Disable the sub-region!'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_subregion&amp;subrg_ID='.$subrg_ID.'&amp;'.url_crumb('subregion') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the sub-region!'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_subregion&amp;subrg_ID='.$subrg_ID.'&amp;'.url_crumb('subregion') ) );
	}
	return $r;

}

function subrg_td_preferred( $subrg_preferred, $subrg_ID )
{

	global $dispatcher;

	$r = '';

	if( $subrg_preferred == true )
	{
		$r .= action_icon( T_('Remove from preferred sub-regions'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_subregion_pref&amp;subrg_ID='.$subrg_ID.'&amp;'.url_crumb('subregion') ) );
	}
	else
	{
		$r .= action_icon( T_('Add to preferred sub-regions'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_subregion_pref&amp;subrg_ID='.$subrg_ID.'&amp;'.url_crumb('subregion') ) );
	}
	return $r;

}



$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'th_title' => T_('Enabled'),
		'order' => 'subrg_enabled',
		'td' => '%subrg_td_enabled( #subrg_enabled#, #subrg_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => /* TRANS: shortcut for preferred */ T_('Pref'),
		'th_title' => T_('Preferred'),
		'order' => 'subrg_preferred',
		'default_dir' => 'D',
		'td' => '%subrg_td_preferred( #subrg_preferred# , #subrg_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_subregions( & $Form )
{
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache( NT_('All') );
	$Form->select_country( 'c', get_param('c'), $CountryCache, T_('Country'), array( 'allow_none' => true ) );
	
	$Form->select_input_options( 'r', get_regions_option_list( get_param('c'), get_param('r') ), T_('Region') );

	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_subregions',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=subregions' ),
		)
	);


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Country'),
							'order' => 'ctry_name',
							'td' => '<a href="?ctrl=countries&amp;ctry_ID=$ctry_ID$&amp;action=edit" title="'.T_('Edit this country...').'"><strong>$ctry_name$</strong></a>',
						);
	$Results->cols[] = array(
							'th' => T_('Region'),
							'order' => 'rgn_name',
							'td' => '<a href="?ctrl=regions&amp;rgn_ID=$rgn_ID$&amp;action=edit" title="'.T_('Edit this region...').'"><strong>$rgn_name$</strong></a>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Country'),
							'order' => 'ctry_name',
							'td' => '$ctry_name$',
						);
	$Results->cols[] = array(
							'th' => T_('Region'),
							'order' => 'rgn_name',
							'td' => '$rgn_name$',
						);
}


$Results->cols[] = array(
						'th' => T_('Code'),
						'td_class' => 'center',
						'order' => 'subrg_code',
						'td' => '<strong>$subrg_code$</strong>',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap'
					);


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'subrg_name',
							'td' => '<a href="?ctrl=subregions&amp;subrg_ID=$subrg_ID$&amp;action=edit" title="'.T_('Edit this sub-region...').'"><strong>$subrg_name$</strong></a>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'subrg_name',
							'td' => '$subrg_name$',
						);

}

/*
 * ACTIONS TD:
 */
function subrg_td_actions($subrg_enabled, $subrg_ID )
{
	global $dispatcher;

	$r = '';

	if( $subrg_enabled == true )
	{
		$r .= action_icon( T_('Disable the sub-region!'), 'deactivate', 
										regenerate_url( 'action', 'action=disable_subregion&amp;subrg_ID='.$subrg_ID.'&amp;'.url_crumb('subregion') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the sub-region!'), 'activate',
										regenerate_url( 'action', 'action=enable_subregion&amp;subrg_ID='.$subrg_ID.'&amp;'.url_crumb('subregion') ) );
	}
	$r .= action_icon( T_('Edit this sub-region...'), 'edit',
										regenerate_url( 'action', 'subrg_ID='.$subrg_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Duplicate this sub-region...'), 'copy',
										regenerate_url( 'action', 'subrg_ID='.$subrg_ID.'&amp;action=new' ) );
	$r .= action_icon( T_('Delete this sub-region!'), 'delete',
										regenerate_url( 'action', 'subrg_ID='.$subrg_ID.'&amp;action=delete&amp;'.url_crumb('subregion') ) );

	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td' => '%subrg_td_actions( #subrg_enabled#, #subrg_ID# )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->global_icon( T_('Create a new sub-region...'), 'new',
				regenerate_url( 'action', 'action=new'), T_('New sub-region').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->display();

?>
<script type="text/javascript">
jQuery( '#c' ).change( function ()
{	// Load option list with regions for seleted country
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_regions_option_list&ctry_id=' + jQuery( this ).val(),
	success: function( result )
		{
			jQuery( '#r' ).html( ajax_debug_clear( result ) );
		}
	} );
} );
</script>