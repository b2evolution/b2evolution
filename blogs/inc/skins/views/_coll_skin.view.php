<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $admin_url;

$block_item_Widget = & new Widget( 'block_item' );

$block_item_Widget->title = T_('Choose a skin');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
  $block_item_Widget->global_icon( T_('Manage installed skins...'), 'properties', 'admin.php?ctrl=skins', T_('Manage skins'), 3, 4 );
  $block_item_Widget->global_icon( T_('Install new skin...'), 'new', 'admin.php?ctrl=skins&amp;action=new&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','','','&'), $admin_url)), T_('Install new'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

	$SkinCache = & get_Cache( 'SkinCache' );
	$SkinCache->load_all();

	// TODO: this is like touching private parts :>
	foreach( $SkinCache->cache as $Skin )
	{
		if( $Skin->type != 'normal' )
		{	// This skin cannot be used here...
			continue;
		}

		$selected = ($edited_Blog->skin_ID == $Skin->ID);
		$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;blog_skin_ID='.$Skin->ID;
		$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($Skin->folder) );

		// Display skinshot:
		Skin::disp_skinshot( $Skin->folder, 'select', $selected, $select_url, $preview_url );
	}

	echo '<div class="clear"></div>';

$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log$
 * Revision 1.3  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/29 03:42:12  fplanque
 * skin install UI improvements
 *
 * Revision 1.1  2007/06/25 11:01:36  fplanque
 * MODULES (refactored MVC)
 *
 */
?>