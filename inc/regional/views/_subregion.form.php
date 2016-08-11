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

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var Sub-region
 */
global $edited_Subregion;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'subregion_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this sub-region!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('subregion') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New sub-region') : T_('Sub-region') ).get_manual_link( 'subregions-editing' ) );

	$Form->add_crumb( 'subregion' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',subrg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$CountryCache = & get_CountryCache();
	$Form->select_country( 'subrg_ctry_ID', $edited_Subregion->ctry_ID, $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );

	$Form->select_input_options( 'subrg_rgn_ID', get_regions_option_list( $edited_Subregion->ctry_ID, $edited_Subregion->rgn_ID, array( 'none_option_text' => T_('Unknown') ) ), T_('Region'), '', array( 'required' => true ) );

	$Form->text_input( 'subrg_code', $edited_Subregion->code, 6, T_('Code'), '', array( 'maxlength'=> 6, 'required'=>true ) );

	$Form->text_input( 'subrg_name', $edited_Subregion->name, 40, T_('Name'), '', array( 'maxlength'=> 40, 'required'=>true ) );

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
jQuery( '#subrg_ctry_ID' ).change( function ()
{	// Load option list with regions for seleted country
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_htsrv_url(); ?>anon_async.php',
	data: 'action=get_regions_option_list&page=edit&ctry_id=' + jQuery( this ).val(),
	success: function( result )
		{
			jQuery( '#subrg_rgn_ID' ).html( ajax_debug_clear( result ) );
		}
	} );
} );
</script>