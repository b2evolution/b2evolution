<?php
/**
 * This file display the menu entry form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs( 'files/model/_image.funcs.php' );

global $edited_SiteMenuEntry, $admin_url;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'menu_checkchanges', 'post', 'compact' );

$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action,ment_ID,blog', 'action=edit' ) );

$Form->begin_form( 'fform', ( $creating ?  TB_('New Menu Entry') : TB_('Menu Entry') ).get_manual_link( 'menu-entry-form' ) );

	$Form->add_crumb( 'menuentry' );
	$Form->hidden( 'action',  $creating ? 'create_entry' : 'update_entry' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ment_ID' : '' ) ) );

	$SiteMenuCache = & get_SiteMenuCache();
	$SiteMenuCache->load_all();
	$Form->select_input_object( 'ment_menu_ID', $edited_SiteMenuEntry->get( 'menu_ID' ), $SiteMenuCache, TB_('Menu'), array( 'required' => true ) );

	$SiteMenuEntryCache = & get_SiteMenuEntryCache();
	$Form->select_input_options( 'ment_parent_ID', $SiteMenuEntryCache->recurse_select( $edited_SiteMenuEntry->get( 'parent_ID' ), $edited_SiteMenuEntry->get( 'menu_ID' ), true, NULL, 0, array( $edited_SiteMenuEntry->ID ) ), TB_('Parent') );

	$Form->text_input( 'ment_order', $edited_SiteMenuEntry->get( 'order' ), 11, TB_('Order'), '', array( 'maxlength' => 11 ) );

	$Form->select_input_array( 'ment_type', $edited_SiteMenuEntry->get( 'type' ), get_site_menu_types(), TB_('Type') );

	$Form->select_input_array( 'ment_coll_logo_size', $edited_SiteMenuEntry->get( 'coll_logo_size' ), get_available_thumb_sizes( TB_('No logo') ), TB_('Collection logo before link text'), NULL, array(
			'hide' => in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'item', 'admin', 'url', 'text' ) ),
		) );

	$Form->select_input_array( 'ment_user_pic_size', $edited_SiteMenuEntry->get( 'user_pic_size' ), get_available_thumb_sizes( TB_('No picture') ), TB_('Profile picture before text'), NULL, array(
			'hide' => ! in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'logout', 'myprofile', 'visits', 'profile', 'avatar', 'useritems', 'usercomments' ) ),
		) );

	$Form->text_input( 'ment_text', $edited_SiteMenuEntry->get( 'text' ), 50, TB_('Text'), ( $edited_SiteMenuEntry->get( 'type' ) != 'text' ? TB_('Leave empty for default').( $edited_SiteMenuEntry->ID > 0 ? ': <code>'.$edited_SiteMenuEntry->get_text( true ).'</code>' : '' ) : '' ), array( 'maxlength' => 128 ) );

	$Form->checkbox_input( 'ment_show_badge', $edited_SiteMenuEntry->get( 'show_badge' ), TB_('Show Badge'), array(
			'note' => TB_('Show a badge with count.'),
			'hide' => ! in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'messages', 'flagged' ) )
		) );

	$msg_Blog = & get_setting_Blog( 'msg_blog_ID' );
	$coll_id_is_disabled = in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'ownercontact', 'owneruserinfo', 'myprofile', 'profile', 'avatar', 'messages', 'contacts' ) );
	$Form->text_input( 'ment_coll_ID', $edited_SiteMenuEntry->get( 'coll_ID' ), 11, TB_('Collection ID'), '', array(
			'maxlength' => 11,
			'hide' => in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'item', 'admin', 'url', 'text' ) ),
			'disabled' => $coll_id_is_disabled,
			'note' => T_( 'Leave empty for current collection.' )
				.( $msg_Blog ? ' <span class="evo_setting_coll_disabled red"'.( $coll_id_is_disabled ? '' : ' style="display:none"' ).'>'
					.sprintf( T_('The site is <a %s>configured</a> to always use collection %s for profiles/messaging functions.'),
						'href="'.$admin_url.'?ctrl=collections&amp;tab=site_settings"',
						'<b>'.$msg_Blog->get( 'name' ).'</b>' ).'</span>' : '' ),
		) );

	$Form->text_input( 'ment_cat_ID', $edited_SiteMenuEntry->get( 'cat_ID' ), 11, TB_('Category ID'), '', array( 'maxlength' => 11, 'hide' => ! in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'recentposts', 'postnew' ) ) ) );

	$Form->text_input( 'ment_item_ID', $edited_SiteMenuEntry->get( 'item_ID' ), 11, TB_('Item ID'), '', array( 'maxlength' => 11, 'hide' => ( $edited_SiteMenuEntry->get( 'type' ) != 'item' ) ) );
	
	$Form->text_input( 'ment_item_slug', $edited_SiteMenuEntry->get( 'item_slug' ), 25, TB_('Item slug'), '', array( 'maxlength' => 255, 'hide' => ( $edited_SiteMenuEntry->get( 'type' ) != 'item' ) ) );

	$Form->text_input( 'ment_url', $edited_SiteMenuEntry->get( 'url' ), 128, TB_('URL'), '', array( 'maxlength' => 2000, 'hide' => ( $edited_SiteMenuEntry->get( 'type' ) != 'url' ) ) );

	$Form->radio_input( 'ment_access', $edited_SiteMenuEntry->get( 'access' ),
		array(
			array( 'value' => 'any', 'label' => TB_('All users') ),
			array( 'value' => 'loggedin', 'label' => TB_('Logged in users') ),
			array( 'value' => 'perms', 'label' => TB_('Users with permissions only') ),
		), TB_('Show to'), array(
			'lines' => true,
			'hide' => ! in_array( $edited_SiteMenuEntry->get( 'type' ), array( 'messages', 'contacts' ) )
		) );

	$Form->radio( 'ment_highlight', $edited_SiteMenuEntry->get( 'highlight' ),
		array(
			array( 1, TB_('Highlight the current item') ),
			array( 0, TB_('Do not try to highlight') )
		), TB_('Highlight'), true );

	$Form->text_input( 'ment_class', $edited_SiteMenuEntry->get( 'class' ), 50, TB_('Extra CSS classes'), '', array( 'maxlength' => 128 ) );

	$Form->checkbox_input( 'ment_hide_empty', $edited_SiteMenuEntry->get( 'hide_empty' ), TB_('Hide if empty'), array(
			'note' => TB_('Check to hide this menu if the list is empty.'),
			'hide' => $edited_SiteMenuEntry->get( 'type' ) != 'flagged',
		) );

	$Form->radio( 'ment_visibility', $edited_SiteMenuEntry->get( 'visibility' ),
		array(
			array( 'always', TB_( 'Always show') ),
			array( 'access', TB_( 'Only show if access is allowed' ) )
		), TB_('Visibility'), true );

	$buttons = array();
	if( check_user_perm( 'options', 'edit' ) )
	{	// Allow to save menu if current User has a permission:
		$buttons[] = array( 'submit', 'submit', ( $creating ? TB_('Record') : TB_('Save Changes!') ), 'SaveButton' );
	}

$Form->end_form( $buttons );
?>

<script>
jQuery( '#ment_type' ).change( function()
{
	var link_type_value = jQuery( this ).val();
	// Hide/Show Profile picture size:
	jQuery( '#ffield_ment_user_pic_size' ).toggle( link_type_value == 'logout' ||
		link_type_value == 'myprofile' ||
		link_type_value == 'visits' ||
		link_type_value == 'profile' ||
		link_type_value == 'avatar' ||
		link_type_value == 'useritems' ||
		link_type_value == 'usercomments' );
	if( link_type_value == 'myprofile' && jQuery( '#ment_user_pic_size' ).val() == '' )
	{	// Set default picture size for "View my profile":
		jQuery( '#ment_user_pic_size' ).val( 'crop-top-15x15' );
	}
	// Hide/Show collection ID:
	jQuery( '#ffield_ment_coll_ID, #ffield_ment_coll_logo_size' ).toggle( link_type_value != 'item' && link_type_value != 'admin' && link_type_value != 'url' && link_type_value != 'text' );
	if( jQuery( '.evo_setting_coll_disabled' ).length )
	{	// Hide/Show info for disabled collection:
		var coll_disabled = link_type_value == 'ownercontact' ||
			link_type_value == 'owneruserinfo' ||
			link_type_value == 'myprofile' ||
			link_type_value == 'profile' ||
			link_type_value == 'avatar' ||
			link_type_value == 'messages' ||
			link_type_value == 'contacts';
		jQuery( '.evo_setting_coll_disabled' ).toggle( coll_disabled );
		jQuery( '#ment_coll_ID' ).prop( 'disabled', coll_disabled );
	}
	// Hide/Show category ID:
	jQuery( '#ffield_ment_cat_ID' ).toggle( link_type_value == 'recentposts' || link_type_value == 'postnew' );
	// Hide/Show item ID:
	jQuery( '#ffield_ment_item_ID' ).toggle( link_type_value == 'item' );
	jQuery( '#ffield_ment_item_slug' ).toggle( link_type_value == 'item' );
	// Hide/Show URL:
	jQuery( '#ffield_ment_url' ).toggle( link_type_value == 'url' );
	// Hide/Show setting "Show to":
	jQuery( '#ffield_ment_access' ).toggle( link_type_value == 'messages' || link_type_value == 'contacts' );
	// Hide/Show setting "Show Badge":
	jQuery( '#ffield_ment_show_badge' ).toggle( link_type_value == 'messages' || link_type_value == 'flagged' );
	// Hide/Show setting "Hide if empty":
	jQuery( '#ffield_ment_hide_empty' ).toggle( link_type_value == 'flagged' );
} );
</script>
