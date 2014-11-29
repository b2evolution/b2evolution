<?php
/**
 * This file implements regional functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _regional.funcs.php 235 2011-11-08 12:50:06Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get regional option list from given array
 * 
 * @param array rows
 * @param integer ID of selected row
 * @param array field params
 * @return string html tags <option>
 */
function get_regional_option_list( $rows, $selected = 0, $params = array() )
{
	$params = array_merge( array(
			'allow_none' => true,
			'none_option_value' => '',
			'none_option_text' => T_('All'),
			'group_preferred' => true,
			'group_name_preferred' => T_('Frequent'),
			'group_name_other' => T_('Other'),
		), $params );

	$r = '';

	if( $params['allow_none'] )
	{	// we set current value of a country if it is sent to function
		$r .= '<option value="'.$params['none_option_value'].'"';
		if( empty($default) ) $r .= ' selected="selected"';
		$r .= '>'.format_to_output( $params['none_option_text'] ).'</option>'."\n";
	}

	$pref_rows = array(); //preferred countries.
	$rest_rows = array(); // not preffered countries (the rest)

	foreach( $rows as $row_Obj )
	{
		if( $params['group_preferred'] && $row_Obj->preferred == 1 )
		{	// if the country is preferred we add it to selected array.
			$pref_rows[] = $row_Obj;
		}
		$rest_rows[] = $row_Obj;
	}

	if( count( $pref_rows ) )
	{	// if we don't have preferred countries in this case we don't have to show optgroup
		// in option list
		$r .= '<optgroup label="'.$params['group_name_preferred'].'">';
		foreach( $pref_rows as $row_Obj )
		{
			$r .=  '<option value="'.$row_Obj->ID.'"';
			if( $row_Obj->ID == $selected ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= format_to_output( $row_Obj->name, 'htmlbody' );
			$r .=  '</option>'."\n";
		}
		$r .= '</optgroup>';

		if( count( $rest_rows ) )
		{ // if we don't have rest countries we do not create optgroup for them
			$r .= '<optgroup label="'.$params['group_name_other'].'">';
			foreach( $rest_rows as $row_Obj )
			{
				$r .=  '<option value="'.$row_Obj->ID.'"';
				if( $row_Obj->ID == $selected ) $r .= ' selected="selected"';
				$r .= '>';
				$r .= format_to_output( $row_Obj->name, 'htmlbody' );
				$r .=  '</option>'."\n";
			}
			$r .= '</optgroup>';
		}
	}
	else
	{	// if we have only rest countries we get here
		foreach( $rest_rows as $row_Obj )
		{
			$r .=  '<option value="'.$row_Obj->ID.'"';
			if( $row_Obj->ID == $selected ) $r .= ' selected="selected"';
			$r .= '>';
			$r .= format_to_output( $row_Obj->name, 'htmlbody' );
			$r .=  '</option>'."\n";
		}
	}

	return $r;
}

/**
 * Get option list with regions by country ID
 * 
 * @param integer country ID
 * @param integer selected region ID
 * @param array field params
 * @return string html tags <option>
 */
function get_regions_option_list( $country_ID, $region_ID = 0, $params = array() )
{
	$params = array_merge( array(
			'group_name_preferred' => T_('Frequent regions'),
			'group_name_other' => T_('Other regions'),
		), $params );

	global $DB;

	$regions = $DB->get_results( '
		SELECT rgn_ID as ID, rgn_name as name, rgn_preferred as preferred
			FROM T_regional__region
		 WHERE rgn_ctry_ID = "'.$DB->escape( $country_ID ).'"
		   AND rgn_enabled = 1
		 ORDER BY rgn_name' );

	return get_regional_option_list( $regions, $region_ID, $params );
}

/**
 * Get option list with sub-regions by region ID
 * 
 * @param integer region ID
 * @param integer selected sub-region ID
 * @param array field params
 * @return string html tags <option>
 */
function get_subregions_option_list( $region_ID, $subregion_ID = 0, $params = array() )
{
	$params = array_merge( array(
			'group_name_preferred' => T_('Frequent sub-regions'),
			'group_name_other' => T_('Other sub-regions'),
		), $params );

	global $DB;

	$subregions = $DB->get_results( '
		SELECT subrg_ID as ID, subrg_name as name, subrg_preferred as preferred
		  FROM T_regional__subregion
		 WHERE subrg_rgn_ID = "'.$DB->escape( $region_ID ).'"
		   AND subrg_enabled = 1
		 ORDER BY subrg_name' );

	return get_regional_option_list( $subregions, $subregion_ID, $params );
}

/**
 * Get option list with cities by country, region or subregion
 * 
 * @param integer country ID
 * @param integer region ID
 * @param integer subregion ID
 * @param integer selected city ID
 * @param array field params
 * @return string html tags <option>
 */
function get_cities_option_list( $country_ID, $region_ID = 0, $subregion_ID = 0, $city_ID = 0, $params = array() )
{
	$params = array_merge( array(
			'group_name_preferred' => T_('Frequent cities'),
			'group_name_other' => T_('Other cities'),
		), $params );

	global $DB;

	$get_cities = true;
	$sql_where = array();

	// Get city by country
	$sql_where[] = 'city_ctry_ID = "'.$DB->escape( $country_ID ).'"';

	if( $region_ID > 0 )
	{	// Get cities by region
		$sql_where[] = 'city_rgn_ID = "'.$DB->escape( $region_ID ).'"';
	}
	else
	{	// Select all cities from current country
		$regions_count = $DB->get_var( '
			SELECT COUNT( rgn_ID )
			  FROM T_regional__region
			 WHERE rgn_ctry_ID = "'.$DB->escape( $country_ID ).'"
			   AND rgn_enabled = 1' );
		if( $regions_count == 0 )
		{	// If regions don't exist for current country we show all cities of the country
			$sql_where[] = 'city_rgn_ID IS NULL';
		}
		else
		{	// Don't get cities without selected region
			$get_cities = false;
		}
	}

	if( $region_ID > 0 )
	{	// Select sub-regions only when region_ID is defined
		if( $subregion_ID > 0 )
		{	// Get cities by sub-region
			$sql_where[] = 'city_subrg_ID = "'.$DB->escape( $subregion_ID ).'"';
		}
		else
		{	// Select all cities from current region
			$subregions_count = $DB->get_var( '
				SELECT COUNT( subrg_ID )
				  FROM T_regional__subregion
				 WHERE subrg_rgn_ID = "'.$DB->escape( $region_ID ).'"
			     AND subrg_enabled = 1' );
			if( $subregions_count == 0 )
			{	// If sub-regions don't exist for current region we show all cities of the region
				$sql_where[] = 'city_subrg_ID IS NULL';
			}
			else
			{	// Don't get cities without selected sub-region
				$get_cities = false;
			}
		}
	}

	if( $get_cities )
	{	// Get cities from DB
		$sql_where[] = 'city_enabled = 1';
		$cities = $DB->get_results( '
			SELECT city_ID as ID, CONCAT( city_name, " (", city_postcode, ")" ) as name, city_preferred as preferred
			  FROM T_regional__city
			 WHERE '.implode( ' AND ', $sql_where ).'
			 ORDER BY city_name' );
	}
	else
	{
		$cities = array();
	}

	return get_regional_option_list( $cities, $city_ID, $params );
}


/**
 * Import cities from CSV file
 * 
 * @param integer country ID
 * @param string 
 * @return array (
 *   'inserted' => Count of inserted cities,
 *   'updated'  => Count of updated cities );
 */
function import_cities( $country_ID, $file_name )
{
	global $DB;

	// Begin transaction
	$DB->begin();

	// Get all sub-regions of the current country
	$subregions_data = $DB->get_results( '
		SELECT subrg_ID, subrg_code, rgn_ID, rgn_ctry_ID
		  FROM T_regional__subregion
		  LEFT JOIN T_regional__region ON subrg_rgn_ID = rgn_ID
		 WHERE rgn_ctry_ID = '.$DB->quote( $country_ID ) );

	$subregions = array();
	foreach( $subregions_data as $subregion )
	{
		$subregions[$subregion->subrg_code] = $subregion;
	}
	unset( $subregions_data );


	// Open file
	$file_handle = fopen( $file_name, 'r' );

	$c = 0;
	$cities_insert_values = array();
	$cities_update_values = array();
	while( $data = fgetcsv( $file_handle, 1024, ";" ) )
	{
		if( $c == 0 )
		{	// Skip first row with titles
			$c++;
			continue;
		}

		$postcode = trim( $data[0], " \xA0" ); // \xA0 - ASCII Non-breaking space
		$name = trim( $data[1], " \xA0" );
		$subregion_code = '';
		if( isset( $data[2] ) )
		{	// Optional field
			$subregion_code = trim( $data[2], " \xA0" );
		}

		if( empty( $postcode ) && empty( $name ) )
		{	// Skip empty row
			continue;
		}

		/*if( empty( $subregion_code ) )
		{	// If field subregion_code is NOT defined, we get it from city postcode ( 2 first letters )
			$subregion_code = substr( $postcode, 0, 2 );
		}*/

		$city = array(
			'ctry_ID'  => $country_ID,
			'postcode' => $DB->quote( $postcode ),
			'name'     => $DB->quote( $name ),
		);

		if( empty( $subregion_code ) || ! isset( $subregions[$subregion_code] ) )
		{	// Subregion is not defined and not found in DB
			$city['rgn_ID'] = 'NULL';
			$city['subrg_ID'] = 'NULL';
		}
		else
		{	// Set region ID & subregion ID for current city
			$city['rgn_ID'] = $subregions[$subregion_code]->rgn_ID;
			$city['subrg_ID'] = $subregions[$subregion_code]->subrg_ID;
		}

		// Get city from DB with current country, postcode & name
		$existing_city = $DB->get_row( '
			SELECT city_ID, city_rgn_ID, city_subrg_ID
			  FROM T_regional__city
			 WHERE city_ctry_ID = '.$city['ctry_ID'].'
			   AND city_postcode = '.$city['postcode'].'
			   AND city_name = '.$city['name'] );

		if( !empty( $existing_city ) )
		{	// City already exist with such country, postcode & name
			if( $city['subrg_ID'] != $existing_city->city_subrg_ID )
			{	// Set new sub-region & region for current city
				$cities_update_values[] = 'city_rgn_ID = '.$city['rgn_ID'].', city_subrg_ID = '.$city['subrg_ID'].'
					WHERE city_ID = '.$existing_city->city_ID;
			}
		}
		else
		{	// Insert a new city
			$cities_insert_values[] = '( '.implode( ', ', $city ).' )';
		}

		$c++;
	}

	// Close file pointer
	fclose( $file_handle );

	$count_insert_cities = count( $cities_insert_values );
	$count_update_cities = count( $cities_update_values );

	if( $count_insert_cities > 0 )
	{	// New cities are exist to import
		// Split an insert data to avoid big sql queries
		$cities_insert_values = array_chunk( $cities_insert_values, 1000 );

		foreach( $cities_insert_values as $insert_values )
		{
			$insert_values = implode( ', ', $insert_values );

			// Insert new cities into DB
			$DB->query( '
				INSERT INTO T_regional__city
				( city_ctry_ID, city_postcode, city_name, city_rgn_ID, city_subrg_ID )
				VALUES '.$insert_values );
		}
	}

	if( $count_update_cities > 0 )
	{	// Cities to update
		foreach( $cities_update_values as $update_sql )
		{	// Update a existing city
			$DB->query( '
				UPDATE T_regional__city
				SET '.$update_sql );
		}
	}

	// Commit transaction
	$DB->commit();

	return array(
		'inserted' => $count_insert_cities,
		'updated'  => $count_update_cities );
}


/**
 * Initialize JavaScript for AJAX loading of regions, subregions and cities
 *
 * @param string Prefix of fields group
 * @param boolean TRUE if region is visible (subregion & city also are visible)
 */
function echo_regional_js( $prefix, $region_visible )
{
	if( !$region_visible )
	{	// If region is NOT visible we don't need in these ajax functions
		return;
	}
?>
<script type="text/javascript">
<?php /*jQuery( document ).ready( function()
{
	if( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val() > 0 && jQuery( '#<?php echo $prefix; ?>_rgn_ID option' ).length == 1 )
	{	// Preload a regions for case when country is selected as default but not saved in DB
		load_regions( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val(), jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).val() );
	}
} );
*/ ?>
jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).change( function ()
{	// Load option list with regions for seleted country
	load_regions( jQuery( this ).val(), 0 );
} );

jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).change( function ()
{	// Change option list with sub-regions
	load_subregions( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val(), jQuery( this ).val() );
} );

jQuery( '#<?php echo $prefix; ?>_subrg_ID' ).change( function ()
{	// Change option list with cities
	load_cities( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val(), jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).val(), jQuery( this ).val() );
} );


jQuery( '#button_refresh_region' ).click( function ()
{	// Button - Refresh regions
	load_regions( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val(), 0 );
	return false;
} );

jQuery( '#button_refresh_subregion' ).click( function ()
{	// Button - Refresh sub-regions
	load_subregions( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val(), jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).val() );
	return false;
} );

jQuery( '#button_refresh_city' ).click( function ()
{	// Button - Refresh cities
	load_cities( jQuery( '#<?php echo $prefix; ?>_ctry_ID' ).val(), jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).val(), jQuery( '#<?php echo $prefix; ?>_subrg_ID' ).val() );
	return false;
} );


function load_regions( country_ID, region_ID )
{	// Load option list with regions for seleted country
	jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).next().find( 'button' ).hide().next().show();
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_regions_option_list&page=edit&mode=load_all&ctry_id=' + country_ID + '&rgn_id=' + region_ID,
	success: function( result )
		{
			jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).next().find( 'button' ).show().next().hide();

			result = ajax_debug_clear( result );
			var options = result.split( '-##-' );

			jQuery( '#<?php echo $prefix; ?>_rgn_ID' ).html( options[0] );
			jQuery( '#<?php echo $prefix; ?>_subrg_ID' ).html( options[1] );
			jQuery( '#<?php echo $prefix; ?>_city_ID' ).html( options[2] );
		}
	} );
}

function load_subregions( country_ID, region_ID )
{	// Load option list with sub-regions for seleted region
	jQuery( '#<?php echo $prefix; ?>_subrg_ID' ).next().find( 'button' ).hide().next().show();
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_subregions_option_list&page=edit&mode=load_all&ctry_id=' + country_ID + '&rgn_id=' + region_ID,
	success: function( result )
		{
			jQuery( '#<?php echo $prefix; ?>_subrg_ID' ).next().find( 'button' ).show().next().hide();

			result = ajax_debug_clear( result );
			var options = result.split( '-##-' );

			jQuery( '#<?php echo $prefix; ?>_subrg_ID' ).html( options[0] );
			jQuery( '#<?php echo $prefix; ?>_city_ID' ).html( options[1] );
		}
	} );
}

function load_cities( country_ID, region_ID, subregion_ID )
{	// Load option list with cities for seleted region or sub-region
	jQuery( '#<?php echo $prefix; ?>_city_ID' ).next().find( 'button' ).hide().next().show();
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_cities_option_list&page=edit&ctry_id=' + country_ID + '&rgn_id=' + region_ID + '&subrg_id=' + subregion_ID,
	success: function( result )
		{
			jQuery( '#<?php echo $prefix; ?>_city_ID' ).html( ajax_debug_clear( result ) );
			jQuery( '#<?php echo $prefix; ?>_city_ID' ).next().find( 'button' ).show().next().hide();
		}
	} );
}
</script>
<?php
}


/**
 * Initialize JavaScript for required locations
 *
 * @param string Prefix of fields group
 */
function echo_regional_required_js( $prefix )
{
?>
<script type="text/javascript">
jQuery( 'input[name=<?php echo $prefix; ?>city][value=required]' ).click( function ()
{	// when city is required make subregion is required
	set_subregion_required();
} );
jQuery( 'input[name=<?php echo $prefix; ?>subregion][value=required]' ).click( function ()
{	// when subregion is required make region is required
	set_region_required();
} );
jQuery( 'input[name=<?php echo $prefix; ?>region][value=required]' ).click( function ()
{	// when region is required make country is required
	set_country_required();
} );

function set_subregion_required()
{
	jQuery( 'input[name=<?php echo $prefix; ?>subregion][value=required]' ).attr( 'checked', 'checked' );
	set_region_required();
}
function set_region_required()
{
	jQuery( 'input[name=<?php echo $prefix; ?>region][value=required]' ).attr( 'checked', 'checked' );
	set_country_required();
}
function set_country_required()
{
	jQuery( 'input[name=<?php echo $prefix; ?>country][value=required]' ).attr( 'checked', 'checked' );
}
</script>
<?php
}


/**
 * Check the existing countries in DB
 *
 * @param integer Country ID
 * @return boolean TRUE - countries exist
 */
function countries_exist()
{
	global $DB;

	$countries_count = $DB->get_var( '
		SELECT COUNT( ctry_ID )
		  FROM T_regional__country
		 WHERE ctry_enabled = 1' );

	return $countries_count > 0;
}


/**
 * Check the existing regions for selected country
 *
 * @param integer Country ID
 * @param boolean TRUE - don't count regions from all countries (when $country_ID = 0)
 * @return boolean TRUE - regions exist
 */
function regions_exist( $country_ID, $exact_existance = false )
{
	global $DB;

	$sql_where = '';
	if( $country_ID > 0 )
	{	// Restrict regions with selected country
		$sql_where = ' AND rgn_ctry_ID = '.$DB->quote( $country_ID );
	}
	else if( $exact_existance )
	{	// Don't count regions with not selected country
		return false;
	}

	$regions_count = $DB->get_var( '
		SELECT COUNT( rgn_ID )
		  FROM T_regional__region
		 WHERE rgn_enabled = 1'.$sql_where );

	return $regions_count > 0;
}


/**
 * Check the existing subregions for selected region
 *
 * @param integer Region ID
 * @param boolean TRUE - don't count subregions from all regions (when $region_ID = 0)
 * @return boolean TRUE - subregions exist
 */
function subregions_exist( $region_ID, $exact_existance = false )
{
	global $DB;

	if( $region_ID == 0 && $exact_existance )
	{	// Don't count subregions with not selected region
		return false;
	}

	$subregions_count = $DB->get_var( '
		SELECT COUNT( subrg_ID )
		  FROM T_regional__subregion
		 WHERE subrg_enabled = 1
		   AND subrg_rgn_ID = '.$DB->quote( $region_ID ) );

	return $subregions_count > 0;
}


/**
 * Check the existing cities for selected country, region & subregion
 *
 * @param integer City ID
 * @param boolean TRUE - don't count cities from all region (when $region_ID = 0)
 * @return boolean TRUE - cities exist
 */
function cities_exist( $country_ID, $region_ID, $subregion_ID, $exact_existance = false )
{
	global $DB;

	$sql_where = array();
	$sql_where[] = 'city_enabled = 1';

	if( $country_ID > 0 )
	{	// Restrict cities with selected country
		$sql_where[] = 'city_ctry_ID = '.$DB->quote( $country_ID );
	}
	else if( $exact_existance )
	{	// Don't count cities with not selected country
		return false;
	}

	if( $region_ID > 0 )
	{	// Restrict cities with selected region
		$sql_where[] = 'city_rgn_ID = '.$DB->quote( $region_ID );
	}
	else if( $exact_existance )
	{	// Restrict cities with not defined region
		$sql_where[] = 'city_rgn_ID IS NULL';
	}

	if( $subregion_ID > 0 )
	{	// Restrict cities with selected subregion
		$sql_where[] = 'city_subrg_ID = '.$DB->quote( $subregion_ID );
	}
	else if( $exact_existance )
	{	// Restrict cities with not defined subregion
		$sql_where[] = 'city_subrg_ID IS NULL';
	}

	$cities_count = $DB->get_var( '
		SELECT COUNT(city_ID)
		  FROM T_regional__city
		 WHERE '.implode( ' AND ', $sql_where ) );

	return $cities_count > 0;
}

/**
 * Template function: Display/Get country flag
 *
 * @todo factor with locale_flag()
 *
 * @param string country code to use
 * @param string country name to use
 * @param string collection name (subdir of img/flags)   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param string name of class for IMG tag   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param string deprecated HTML align attribute   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param boolean to echo or not
 * @param mixed use absolute url (===true) or path to flags directory   !! OLD PARAM - NOT USED IN THE FUNCTION ANYMORE !!
 * @param string Flag style properties
 * @param boolean TRUE - to display even empty flag
 * @return string Country flag
 */
function country_flag( $country_code, $country_name, $collection = 'w16px', $class = 'flag', $align = '', $disp = true, $absoluteurl = true, $flag_style = '', $display_empty = true )
{
	global $country_flags_bg;

	$flag_attribs = array(
		'class' => 'flag',
		'title' => $country_name,
		'style' => $flag_style,
	);

	if( isset( $country_flags_bg[ $country_code ] ) )
	{	// Set background-position from config
		$flag_attribs['style'] .= 'background-position:'.$country_flags_bg[ $country_code ];
	}

	if( $display_empty || isset( $country_flags_bg[ $country_code ] ) )
	{	// Init a country flag
		$r = '<span'.get_field_attribs_as_string( $flag_attribs ).'>&nbsp;</span>';
	}
	else
	{	// Don't display empty flag when bg-position is not defined in config by country code
		$r = '';
	}

	if( $disp )
		echo $r;   // echo it
	else
		return $r; // return it

}


/**
 * Get status titles of country
 *
 * @param boolean TRUE - to include false statuses, which don't exist in DB
 * @return array Status titles
 */
function ctry_status_titles( $include_false_statuses = true )
{
	$status_titles = array();
	if( $include_false_statuses )
	{ // Include Unknown status
		$status_titles[''] = T_('Unknown ');
	}
	$status_titles['trusted'] = T_('Trusted');
	$status_titles['suspect'] = T_('Suspect');
	$status_titles['blocked'] = T_('Blocked');

	return $status_titles;
}


/**
 * Get status colors of country
 *
 * @return array Color values
 */
function ctry_status_colors()
{
	return array(
			''        => '999999',
			'trusted' => '00CC00',
			'suspect' => 'FFAA00',
			'blocked' => 'FF0000',
		);
}


/**
 * Get status title of country by status value
 *
 * @param string Status value
 * @return string Status title
 */
function ctry_status_title( $status )
{
	$statuses = ctry_status_titles();

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
}


/**
 * Get status color of ip range by status value
 *
 * @param string Status value
 * @return string Color value
 */
function ctry_status_color( $status )
{
	if( $status == 'NULL' )
	{
		$status = '';
	}

	$status_colors = ctry_status_colors();

	return isset( $status_colors[ $status ] ) ? '#'.$status_colors[ $status ] : 'none';
}

?>