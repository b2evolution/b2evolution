<?php
/**
 * This file display the menu entry form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_SiteMenuEntry;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'menu_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,ment_ID,blog', 'action=edit' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New Menu Entry') : T_('Menu Entry') ).get_manual_link( 'menu-entry-form' ) );

	$Form->add_crumb( 'menuentry' );
	$Form->hidden( 'action',  $creating ? 'create_entry' : 'update_entry' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ment_ID' : '' ) ) );

	$SiteMenuCache = & get_SiteMenuCache();
	$SiteMenuCache->load_all();
	$Form->select_input_object( 'ment_menu_ID', $edited_SiteMenuEntry->get( 'menu_ID' ), $SiteMenuCache, T_('Menu'), array( 'required' => true ) );

	$SiteMenuEntryCache = & get_SiteMenuEntryCache();
	$Form->select_input_options( 'ment_parent_ID', $SiteMenuEntryCache->recurse_select( $edited_SiteMenuEntry->get( 'parent_ID' ), $edited_SiteMenuEntry->get( 'menu_ID' ), true, NULL, 0, array( $edited_SiteMenuEntry->ID ) ), T_('Parent') );

	$Form->text_input( 'ment_order', $edited_SiteMenuEntry->get( 'order' ), 11, T_('Order'), '', array( 'maxlength' => 11 ) );

	$Form->text_input( 'ment_text', $edited_SiteMenuEntry->get( 'text' ), 50, T_('Text'), ( $edited_SiteMenuEntry->get( 'type' ) != 'text' ? T_('Leave empty for default').( $edited_SiteMenuEntry->ID > 0 ? ': <code>'.$edited_SiteMenuEntry->get_text( true ).'</code>' : '' ) : '' ), array( 'maxlength' => 128 ) );

	$Form->select_input_array( 'ment_type', $edited_SiteMenuEntry->get( 'type' ), get_site_menu_types(), T_('Type') );

	load_funcs( 'files/model/_image.funcs.php' );
	$Form->select_input_array( 'ment_coll_logo_size', $edited_SiteMenuEntry->get( 'coll_logo_size' ), get_available_thumb_sizes( T_('No logo') ), T_('Collection logo before link text') );

	$Form->text_input( 'ment_coll_ID', $edited_SiteMenuEntry->get( 'coll_ID' ), 11, T_('Collection ID'), '', array( 'maxlength' => 11, 'hide' => in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'item', 'admin', 'url', 'text' ) ) ) );

	$Form->text_input( 'ment_cat_ID', $edited_SiteMenuEntry->get( 'cat_ID' ), 11, T_('Category ID'), '', array( 'maxlength' => 11, 'hide' => ! in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'recentposts', 'postnew' ) ) ) );

	$Form->text_input( 'ment_item_ID', $edited_SiteMenuEntry->get( 'item_ID' ), 11, T_('Item ID'), '', array( 'maxlength' => 11, 'hide' => ( $edited_SiteMenuEntry->get( 'type' ) != 'item' ) ) );
	
	$Form->text_input( 'ment_item_slug', $edited_SiteMenuEntry->get( 'item_slug' ), 25, T_('Item slug'), '', array( 'maxlength' => 255, 'hide' => ( $edited_SiteMenuEntry->get( 'type' ) != 'item' ) ) );

	$Form->text_input( 'ment_url', $edited_SiteMenuEntry->get( 'url' ), 128, T_('URL'), '', array( 'maxlength' => 2000, 'hide' => ( $edited_SiteMenuEntry->get( 'type' ) != 'url' ) ) );

	$Form->radio( 'ment_visibility', $edited_SiteMenuEntry->get( 'visibility' ),
		array(
			array( 'always', T_( 'Always show') ),
			array( 'access', T_( 'Only show if access is allowed' ) )
		), T_('Visibility'), true );

	$Form->radio( 'ment_highlight', $edited_SiteMenuEntry->get( 'highlight' ),
		array(
			array( 1, T_('Highlight the current item') ),
			array( 0, T_('Do not try to highlight') )
		), T_('Highlight'), true );

	$buttons = array();
	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Allow to save menu if current User has a permission:
		$buttons[] = array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' );
	}

$Form->end_form( $buttons );
?>

<script>
jQuery( '#ment_type' ).change( function()
{
	var link_type_value = jQuery( this ).val();
	// Hide/Show collection ID:
	jQuery( '#ffield_ment_coll_ID' ).toggle( link_type_value != 'item' && link_type_value != 'admin' && link_type_value != 'url' && link_type_value != 'text' );
	// Hide/Show category ID:
	jQuery( '#ffield_ment_cat_ID' ).toggle( link_type_value == 'recentposts' || link_type_value == 'postnew' );
	// Hide/Show item ID:
	jQuery( '#ffield_ment_item_ID' ).toggle( link_type_value == 'item' );
	jQuery( '#ffield_ment_item_slug' ).toggle( link_type_value == 'item' );
	// Hide/Show URL:
	jQuery( '#ffield_ment_url' ).toggle( link_type_value == 'url' );
} );
</script>
