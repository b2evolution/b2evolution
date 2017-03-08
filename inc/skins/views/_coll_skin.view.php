<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
global $admin_url, $Session, $Settings;

$skin_type = param( 'skin_type', 'string', 'normal' );

// If the edited Blog is defined then we choose a skin for collection otherwise for site:
$is_collection_skin = isset( $edited_Blog );

$kind_title = $is_collection_skin ? get_collection_kinds( $edited_Blog->type ) : '';

$SkinCache = & get_SkinCache();
$SkinCache->load_all();

// Group together skins based on how well they support the selected collection type
$skins = array();
while( ( $iterator_Skin = & $SkinCache->get_next() ) != NULL )
{
	if( ( $iterator_Skin->type != $skin_type && $iterator_Skin->type != 'rwd' ) ||
	    ( $is_collection_skin && ! $iterator_Skin->provides_collection_skin() ) ||
	    ( ! $is_collection_skin && ! $iterator_Skin->provides_site_skin() ) )
	{	// This skin cannot be used here...
		continue;
	}
	$supported_kind = $is_collection_skin ? $iterator_Skin->supports_coll_kind( $edited_Blog->type ) : 'yes';
	$skins[ $supported_kind ][] = $iterator_Skin;
}

// Skins that fully support the selected collection type
$block_item_Widget = new Widget( 'block_item' );
$display_same_as_normal = false;

switch( $skin_type )
{
	case 'normal':
		$block_item_Widget->title = $is_collection_skin ?
			sprintf( T_('Recommended skins for a "%s" collection'), $kind_title ) :
			T_('Choose a skin');
		break;

	case 'mobile':
		$block_item_Widget->title = $is_collection_skin ?
			sprintf( T_('Recommended Mobile Phone skins for a "%s" collection'), $kind_title ) :
			T_('Choose a Mobile Phone skin');
		$display_same_as_normal = true;
		break;

	case 'tablet':
		$block_item_Widget->title = $is_collection_skin ?
			sprintf( T_('Recommended Tablet skins for a "%s" collection'), $kind_title ) :
			T_('Choose a Tablet skin');
		$display_same_as_normal = true;
		break;

	default:
		debug_die( 'Invalid skin type!' );
}
$block_item_Widget->title .= get_manual_link( $is_collection_skin ? 'skins-for-this-blog' : 'skins-for-this-site' );

// Get what is the current skin ID from this kind of skin type
$current_skin_ID = $is_collection_skin ? $edited_Blog->get_setting( $skin_type.'_skin_ID', true ) : $Settings->get( $skin_type.'_skin_ID', true );

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$block_item_Widget->global_icon( T_('Install new skin...'), 'new', $admin_url.'?ctrl=skins&amp;tab='.( $is_collection_skin ? 'coll_skin&amp;blog='.$edited_Blog->ID : 'site_skin' ).'&amp;action=new&amp;skin_type='.$skin_type.'&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','skinpage=selection','','&'), $admin_url)), T_('Install new').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	$block_item_Widget->global_icon( T_('Keep current skin!'), 'close', regenerate_url( 'skinpage' ), ' '.T_('Don\'t change'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="skin_selector_block">';

	if( $current_User->check_perm( 'options', 'edit', false ) )
	{ // A link to install new skin:
		echo '<a href="'.$admin_url.'?ctrl=skins&amp;tab='.( $is_collection_skin ? 'coll_skin' : 'site_skin' ).'&amp;action=new&amp;skin_type='.$skin_type.'&amp;redirect_to='.rawurlencode( url_rel_to_same_host( regenerate_url( '','skinpage=selection','','&' ), $admin_url ) ).'" class="skinshot skinshot_new">'
				.get_icon( 'new' )
				.T_('Install New').' &raquo;'
			.'</a>';
	}

	if( $display_same_as_normal )
	{
		$skinshot_title = T_('Same as normal skin');
		if( $is_collection_skin )
		{	// Collection skin:
			$select_url = $admin_url.'?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$skin_type.'_skin_ID=0&amp;'.url_crumb( 'collection' );
		}
		else
		{	// Site skin:
			$select_url = $admin_url.'?ctrl=collections&amp;tab=site_skin&amp;action=update_site_skin&amp;skinpage=selection&amp;'.$skin_type.'_skin_ID=0&amp;'.url_crumb( 'siteskin' );
		}
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
		foreach( $skins['yes'] as $iterator_Skin )
		{
			$selected = ( $current_skin_ID == $iterator_Skin->ID );
			if( $is_collection_skin )
			{	// Collection skin:
				$select_url = $admin_url.'?ctrl=coll_settings&amp;tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;'.$skin_type.'_skin_ID='.$iterator_Skin->ID.'&amp;'.url_crumb('collection');
				$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode( $iterator_Skin->folder ) );
			}
			else
			{	// Site skin:
				$select_url = $admin_url.'?ctrl=collections&amp;tab=site_skin&amp;action=update_site_skin&amp;skinpage=selection&amp;'.$skin_type.'_skin_ID='.$iterator_Skin->ID.'&amp;'.url_crumb( 'siteskin' );
				$preview_url = '';
			}

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


		foreach( $skins['partial'] as $iterator_Skin )
		{
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

		foreach( $skins['maybe'] as $iterator_Skin )
		{
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

		echo '<div class="clear"></div>';
		echo '</div>';
	$block_item_Widget->disp_template_replaced( 'block_end' );
}
?>