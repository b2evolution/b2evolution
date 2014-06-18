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
 * @version $Id: _city.form.php 9 2011-10-24 22:32:00Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var City
 */
global $edited_City;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'city_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this city!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('city') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New city') : T_('City') );

	$Form->add_crumb( 'city' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',city_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'city_ctry_ID', $edited_City->ctry_ID, $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->select_input_options( 'city_rgn_ID', get_regions_option_list( $edited_City->ctry_ID, $edited_City->rgn_ID, array( 'none_option_text' => T_('Unknown') ) ), T_('Region') );

	$Form->select_input_options( 'city_subrg_ID', get_subregions_option_list( $edited_City->rgn_ID, $edited_City->subrg_ID, array( 'none_option_text' => T_('Unknown') ) ), T_('Sub-region') );

	$Form->text_input( 'city_postcode', $edited_City->postcode, 12, T_('Post code'), '', array( 'maxlength'=> 12, 'required'=>true ) );

	$Form->text_input( 'city_name', $edited_City->name, 40, T_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>
<script type="text/javascript">
jQuery( '#city_ctry_ID' ).change( function ()
{	// Load option list with regions for seleted country
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_regions_option_list&page=edit&mode=load_subregions&ctry_id=' + jQuery( this ).val(),
	success: function( result )
		{
			result = ajax_debug_clear( result );
			var options = result.split( '-##-' );

			jQuery( '#city_rgn_ID' ).html( options[0] );
			jQuery( '#city_subrg_ID' ).html( options[1] );
		}
	} );
} );

jQuery( '#city_rgn_ID' ).change( function ()
{	// Change option list with sub-regions
	load_subregions( jQuery( this ).val() );
} );

function load_subregions( region_ID )
{	// Load option list with sub-regions for seleted region
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_subregions_option_list&page=edit&rgn_id=' + region_ID,
	success: function( result )
		{
			jQuery( '#city_subrg_ID' ).html( ajax_debug_clear( result ) );
		}
	} );
}
</script>