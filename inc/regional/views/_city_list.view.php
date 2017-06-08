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
$sr = param( 'sr', 'integer', 0, true ); // Sub-region

// Create query
$SQL = new SQL();
$SQL->SELECT( 'city_ID, city_postcode, city_name, city_enabled, city_preferred, ctry_ID, ctry_name, rgn_ID, rgn_name, subrg_ID, subrg_name' );
$SQL->FROM( 'T_regional__city' );
$SQL->FROM_add( 'LEFT JOIN T_regional__country ON city_ctry_ID=ctry_ID' );
$SQL->FROM_add( 'LEFT JOIN T_regional__region ON city_rgn_ID=rgn_ID' );
$SQL->FROM_add( 'LEFT JOIN T_regional__subregion ON city_subrg_ID=subrg_ID' );
$SQL->ORDER_BY( '*, ctry_name, rgn_name, subrg_name' );

$sql_where = array();
if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$sql_where[] = 'CONCAT_WS( " ", city_postcode, city_name ) LIKE "%'.$DB->escape($s).'%"';
}
if( $c > 0 )
{	// Filter by country:
	$sql_where[] = 'ctry_ID = "'.$DB->escape($c).'"';
}
if( $r > 0 )
{	// Filter by region:
	$sql_where[] = 'rgn_ID = "'.$DB->escape($r).'"';
}
if( $sr > 0 )
{	// Filter by sub-region:
	$sql_where[] = 'subrg_ID = "'.$DB->escape($sr).'"';
}

if( count( $sql_where ) > 0 )
{	// Some filters are applied
	$SQL->WHERE( implode( ' AND ', $sql_where ) );
}

// Create result set:
//echo $SQL->get();
$Results = new Results( $SQL->get(), 'city_', '-----A' );

$Results->title = T_('Cities').get_manual_link('countries_list');

/*
 * STATUS TD:
 */
function city_td_enabled( $city_enabled, $city_ID )
{

	global $dispatcher;

	$r = '';

	if( $city_enabled == true )
	{
		$r .= action_icon( T_('Disable the city!'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_city&amp;city_ID='.$city_ID.'&amp;'.url_crumb('city') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the city!'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_city&amp;city_ID='.$city_ID.'&amp;'.url_crumb('city') ) );
	}
	return $r;

}

function city_td_preferred( $city_preferred, $city_ID )
{

	global $dispatcher;

	$r = '';

	if( $city_preferred == true )
	{
		$r .= action_icon( T_('Remove from preferred cities'), 'bullet_full',
										regenerate_url( 'action', 'action=disable_city_pref&amp;city_ID='.$city_ID.'&amp;'.url_crumb('city') ) );
	}
	else
	{
		$r .= action_icon( T_('Add to preferred cities'), 'bullet_empty',
										regenerate_url( 'action', 'action=enable_city_pref&amp;city_ID='.$city_ID.'&amp;'.url_crumb('city') ) );
	}
	return $r;

}



$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'th_title' => T_('Enabled'),
		'order' => 'city_enabled',
		'td' => '%city_td_enabled( #city_enabled#, #city_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);

$Results->cols[] = array(
		'th' => /* TRANS: shortcut for preferred */ T_('Pref'),
		'th_title' => T_('Preferred'),
		'order' => 'city_preferred',
		'default_dir' => 'D',
		'td' => '%city_td_preferred( #city_preferred# , #city_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap'
	);


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_cities( & $Form )
{
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache( T_('All') );
	$Form->select_country( 'c', get_param('c'), $CountryCache, T_('Country'), array( 'allow_none' => true ) );
	
	$Form->select_input_options( 'r', get_regions_option_list( get_param('c'), get_param('r') ), T_('Region') );

	$Form->select_input_options( 'sr', get_subregions_option_list( get_param('r'), get_param('sr') ), T_('Sub-region') );

	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_cities',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=cities' ),
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
	$Results->cols[] = array(
							'th' => T_('Sub-region'),
							'order' => 'subrg_name',
							'td' => '<a href="?ctrl=subregions&amp;subrg_ID=$subrg_ID$&amp;action=edit" title="'.T_('Edit this sub-region...').'"><strong>$subrg_name$</strong></a>',
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
	$Results->cols[] = array(
							'th' => T_('Sub-region'),
							'order' => 'subrg_name',
							'td' => '$subrg_name$',
						);
}


$Results->cols[] = array(
						'th' => T_('Post code'),
						'td_class' => 'center',
						'order' => 'city_postcode',
						'td' => '<strong>$city_postcode$</strong>',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap'
					);


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'city_name',
							'td' => '<a href="?ctrl=cities&amp;city_ID=$city_ID$&amp;action=edit" title="'.T_('Edit this city...').'"><strong>$city_name$</strong></a>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'city_name',
							'td' => '$city_name$',
						);

}

/*
 * ACTIONS TD:
 */
function city_td_actions($city_enabled, $city_ID )
{
	global $dispatcher;

	$r = '';

	if( $city_enabled == true )
	{
		$r .= action_icon( T_('Disable the city!'), 'deactivate', 
										regenerate_url( 'action', 'action=disable_city&amp;city_ID='.$city_ID.'&amp;'.url_crumb('city') ) );
	}
	else
	{
		$r .= action_icon( T_('Enable the city!'), 'activate',
										regenerate_url( 'action', 'action=enable_city&amp;city_ID='.$city_ID.'&amp;'.url_crumb('city') ) );
	}
	$r .= action_icon( T_('Edit this city...'), 'edit',
										regenerate_url( 'action', 'city_ID='.$city_ID.'&amp;action=edit' ) );
	$r .= action_icon( T_('Duplicate this city...'), 'copy',
										regenerate_url( 'action', 'city_ID='.$city_ID.'&amp;action=new' ) );
	$r .= action_icon( T_('Delete this city!'), 'delete',
										regenerate_url( 'action', 'city_ID='.$city_ID.'&amp;action=delete&amp;'.url_crumb('city') ) );

	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td' => '%city_td_actions( #city_enabled#, #city_ID# )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->global_icon( T_('Create a new city ...'), 'new',
				regenerate_url( 'action', 'action=new'), T_('New city').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

	$Results->global_icon( T_('Import cities from CSV file ...'), 'new',
				regenerate_url( 'action', 'action=csv'), T_('Import CSV').' &raquo;', 3, 4  );
}

$Results->display();

?>
<script type="text/javascript">
jQuery( '#c' ).change( function ()
{	// Load option list with regions for seleted country
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_htsrv_url(); ?>anon_async.php',
	data: 'action=get_regions_option_list&mode=load_subregions&ctry_id=' + jQuery( this ).val(),
	success: function( result )
		{
			result = ajax_debug_clear( result );
			var options = result.split( '-##-' );

			jQuery( '#r' ).html( options[0] );
			jQuery( '#sr' ).html( options[1] );
		}
	} );
} );

jQuery( '#r' ).change( function ()
{	// Change option list with sub-regions
	load_subregions( jQuery( this ).val() );
} );

function load_subregions( region_ID )
{	// Load option list with sub-regions for seleted region
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_htsrv_url(); ?>anon_async.php',
	data: 'action=get_subregions_option_list&rgn_id=' + region_ID,
	success: function( result )
		{
			jQuery( '#sr' ).html( ajax_debug_clear( result ) );
		}
	} );
}
</script>