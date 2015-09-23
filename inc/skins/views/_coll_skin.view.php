<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $admin_url, $Session;

$skin_type = param( 'skin_type', 'string', 'normal' );

$block_item_Widget = new Widget( 'block_item' );
$display_same_as_normal = false;

switch( $skin_type )
{
	case 'normal':
		$block_item_Widget->title = T_('Choose a skin').get_manual_link( 'skins-for-this-blog' );
		break;

	case 'mobile':
		$block_item_Widget->title = T_('Choose a Mobile Phone skin');
		$display_same_as_normal = true;
		break;

	case 'tablet':
		$block_item_Widget->title = T_('Choose a Tablet skin');
		$display_same_as_normal = true;
		break;

	default:
		debug_die( 'Invalid skin type!' );
}

// Get what is the current skin ID from this kind of skin type
$current_skin_ID = $edited_Blog->get_setting( $skin_type.'_skin_ID', true );

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$block_item_Widget->global_icon( T_('Install new skin...'), 'new', $admin_url.'?ctrl=skins&amp;action=new&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','skinpage=selection','','&'), $admin_url)), T_('Install new').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	$block_item_Widget->global_icon( T_('Keep current skin!'), 'close', regenerate_url( 'skinpage' ), ' '.T_('Don\'t change'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="skin_selector_block">';

	$SkinCache = & get_SkinCache();
	$SkinCache->load_all();

	if( $display_same_as_normal )
	{
		$skinshot_title = T_('Same as normal skin');
		$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$skin_type.'_skin_ID=0&amp;'.url_crumb('collection');
		$disp_params = array(
			'function'     => 'select',
			'selected'     => $current_skin_ID == '0',
			'select_url'   => $select_url,
		);
		Skin::disp_skinshot( $skinshot_title, $skinshot_title, $disp_params );
	}

	$fadeout_array = $Session->get( 'fadeout_array' );

	$SkinCache->rewind();
	while( ( $iterator_Skin = & $SkinCache->get_next() ) != NULL )
	{
		if( $iterator_Skin->type != $skin_type )
		{	// This skin cannot be used here...
			continue;
		}

		$selected = ( $current_skin_ID == $iterator_Skin->ID );
		$blog_skin_param = $skin_type.'_skin_ID=';
		$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$blog_skin_param.$iterator_Skin->ID.'&amp;'.url_crumb('collection');
		$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($iterator_Skin->folder) );

		$disp_params = array(
			'function'     => 'select',
			'selected'     => $selected,
			'select_url'   => $select_url,
			'function_url' => $preview_url,
			'highlighted'  => ( is_array( $fadeout_array ) && isset( $fadeout_array['skin_ID'] ) && in_array( $iterator_Skin->ID, $fadeout_array['skin_ID'] ) ),
		);
		// Display skinshot:
		Skin::disp_skinshot( $iterator_Skin->folder, $iterator_Skin->name, $disp_params );
	}

	// Flush fadeout
	$Session->delete( 'fadeout_array');

	if( $current_User->check_perm( 'options', 'edit', false ) )
	{ // A link to install new skin:
		echo '<a href="'.$admin_url.'?ctrl=skins&amp;action=new&amp;redirect_to='.rawurlencode( url_rel_to_same_host( regenerate_url( '','skinpage=selection','','&' ), $admin_url ) ).'" class="skinshot skinshot_new">'
				.get_icon( 'new' )
				.T_('Install New').' &raquo;'
			.'</a>';
	}

	echo '<div class="clear"></div>';
	echo '</div>';

$block_item_Widget->disp_template_replaced( 'block_end' );

?>