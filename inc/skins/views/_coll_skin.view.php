<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
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
$kind_title = get_collection_kinds( $edited_Blog->type );

$SkinCache = & get_SkinCache();
$SkinCache->load_all();

// Group together skins based on how well they support the selected collection type
$skins = array();
while( ( $skin = & $SkinCache->get_next() ) != NULL )
{
	if( $skin->type != $skin_type && $skin->type != 'rwd' )
	{	// This skin cannot be used here...
		continue;
	}
	$skins[$skin->supports_coll_kind( $edited_Blog->type )][] = $skin;
}

// Skins that fully support the selected collection type
$block_item_Widget = new Widget( 'block_item' );
$display_same_as_normal = false;

switch( $skin_type )
{
	case 'normal':
		$block_item_Widget->title = sprintf( T_('Recommended skins for a "%s" collection'), $kind_title ).get_manual_link( 'skins-for-this-blog' );
		break;

	case 'mobile':
		$block_item_Widget->title = sprintf( T_('Recommended Mobile Phone skins for a "%s" collection'), $kind_title ).get_manual_link( 'skins-for-this-blog' );
		$display_same_as_normal = true;
		break;

	case 'tablet':
		$block_item_Widget->title = sprintf( T_('Recommended Tablet skins for a "%s" collection'), $kind_title ).get_manual_link( 'skins-for-this-blog' );
		$display_same_as_normal = true;
		break;

	default:
		debug_die( 'Invalid skin type!' );
}

// Get what is the current skin ID from this kind of skin type
$current_skin_ID = $edited_Blog->get( $skin_type.'_skin_ID', array( 'real_value' => true ) );

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$block_item_Widget->global_icon( T_('Install new skin...'), 'new', $admin_url.'?ctrl=skins&amp;tab=current_skin&amp;blog='.$edited_Blog->ID.'&amp;action=new&amp;skin_type='.$skin_type.'&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','skinpage=selection','','&'), $admin_url)), T_('Install new').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	$block_item_Widget->global_icon( T_('Keep current skin!'), 'close', regenerate_url( 'skinpage' ), ' '.T_('Don\'t change'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="skin_selector_block">';

	if( $current_User->check_perm( 'options', 'edit', false ) )
	{ // A link to install new skin:
		echo '<a href="'.$admin_url.'?ctrl=skins&amp;tab=current_skin&amp;action=new&amp;skin_type='.$skin_type.'&amp;redirect_to='.rawurlencode( url_rel_to_same_host( regenerate_url( '','skinpage=selection','','&' ), $admin_url ) ).'" class="skinshot skinshot_new">'
				.get_icon( 'new' )
				.T_('Install New').' &raquo;'
			.'</a>';
	}

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

	if( isset( $skins['yes'] ) )
	{
		foreach( $skins['yes'] as $skin )
		{
			$selected = ( $current_skin_ID == $skin->ID );
			$blog_skin_param = $skin_type.'_skin_ID=';
			$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$blog_skin_param.$skin->ID.'&amp;'.url_crumb('collection');
			$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($skin->folder) );

			$disp_params = array(
				'function'     => 'select',
				'selected'     => $selected,
				'select_url'   => $select_url,
				'function_url' => $preview_url,
				'highlighted'  => ( is_array( $fadeout_array ) && isset( $fadeout_array['skin_ID'] ) && in_array( $skin->ID, $fadeout_array['skin_ID'] ) ),
			);
			// Display skinshot:
			Skin::disp_skinshot( $skin->folder, $skin->name, $disp_params );
		}
	}

	// Flush fadeout
	$Session->delete( 'fadeout_array');

	echo '<div class="clear"></div>';
	echo '</div>';

$block_item_Widget->disp_template_replaced( 'block_end' );


// Skins that partially support the selected collection type
if( isset( $skins['partial'] ) )
{
	$block_item_Widget = new Widget( 'block_item' );
	$block_item_Widget->title = sprintf( T_('Skins that are not optimal for a "%s" collection'), $kind_title );
	$block_item_Widget->disp_template_replaced( 'block_start' );
		echo '<div class="skin_selector_block">';
		$fadeout_array = $Session->get( 'fadeout_array' );


		foreach( $skins['partial'] as $skin )
		{
			$selected = ( $current_skin_ID == $skin->ID );
			$blog_skin_param = $skin_type.'_skin_ID=';
			$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$blog_skin_param.$skin->ID.'&amp;'.url_crumb('collection');
			$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($skin->folder) );

			$disp_params = array(
				'function'     => 'select',
				'selected'     => $selected,
				'select_url'   => $select_url,
				'function_url' => $preview_url,
				'highlighted'  => ( is_array( $fadeout_array ) && isset( $fadeout_array['skin_ID'] ) && in_array( $skin->ID, $fadeout_array['skin_ID'] ) ),
			);
			// Display skinshot:
			Skin::disp_skinshot( $skin->folder, $skin->name, $disp_params );
		}

		// Flush fadeout
		$Session->delete( 'fadeout_array');

		echo '<div class="clear"></div>';
		echo '</div>';
	$block_item_Widget->disp_template_replaced( 'block_end' );
}


// Skins that maybe support the selected collection type
if( isset( $skins['maybe'] ) )
{
	$block_item_Widget = new Widget( 'block_item' );
	$block_item_Widget->title = sprintf( T_('Other skins that might work for a "%s" collection'), $kind_title );
	$block_item_Widget->disp_template_replaced( 'block_start' );
		echo '<div class="skin_selector_block">';
		$fadeout_array = $Session->get( 'fadeout_array' );

		foreach( $skins['maybe'] as $skin )
		{
			$selected = ( $current_skin_ID == $skin->ID );
			$blog_skin_param = $skin_type.'_skin_ID=';
			$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$blog_skin_param.$skin->ID.'&amp;'.url_crumb('collection');
			$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($skin->folder) );

			$disp_params = array(
				'function'     => 'select',
				'selected'     => $selected,
				'select_url'   => $select_url,
				'function_url' => $preview_url,
				'highlighted'  => ( is_array( $fadeout_array ) && isset( $fadeout_array['skin_ID'] ) && in_array( $skin->ID, $fadeout_array['skin_ID'] ) ),
			);
			// Display skinshot:
			Skin::disp_skinshot( $skin->folder, $skin->name, $disp_params );
		}

		// Flush fadeout
		$Session->delete( 'fadeout_array');

		echo '<div class="clear"></div>';
		echo '</div>';
	$block_item_Widget->disp_template_replaced( 'block_end' );
}
?>