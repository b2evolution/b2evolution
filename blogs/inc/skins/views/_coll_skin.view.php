<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id: _coll_skin.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $admin_url, $dispatcher;

$skin_type = param( 'skin_type', 'string', 'normal' );

$block_item_Widget = new Widget( 'block_item' );
$display_same_as_normal = false;

switch( $skin_type )
{
	case 'normal':
		$block_item_Widget->title = T_('Choose a skin');
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
{	// We have permission to modify:
	$block_item_Widget->global_icon( T_('Install new skin...'), 'new', $dispatcher.'?ctrl=skins&amp;action=new&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','skinpage=selection','','&'), $admin_url)), T_('Install new'), 3, 4 );
	$block_item_Widget->global_icon( T_('Keep current skin!'), 'close', regenerate_url( 'skinpage' ), ' '.T_('Don\'t change'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

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
			'function_url' => $preview_url
		);
		// Display skinshot:
		Skin::disp_skinshot( $iterator_Skin->folder, $iterator_Skin->name, $disp_params );
	}

	echo '<div class="clear"></div>';

$block_item_Widget->disp_template_replaced( 'block_end' );

?>