<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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

global $admin_url, $dispatcher;

$block_item_Widget = new Widget( 'block_item' );

$block_item_Widget->title = T_('Choose a skin');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
  $block_item_Widget->global_icon( T_('Manage installed skins...'), 'properties', $dispatcher.'?ctrl=skins', T_('Manage skins'), 3, 4 );
  $block_item_Widget->global_icon( T_('Install new skin...'), 'new', $dispatcher.'?ctrl=skins&amp;action=new&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','skinpage=selection','','&'), $admin_url)), T_('Install new'), 3, 4 );
  $block_item_Widget->global_icon( T_('Keep current skin!'), 'close', regenerate_url( 'skinpage' ), ' '.T_('Don\'t change'), 3, 4 );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

	$SkinCache = & get_SkinCache();
	$SkinCache->load_all();

	// TODO: this is like touching private parts :>
	foreach( $SkinCache->cache as $Skin )
	{
		if( $Skin->type != 'normal' )
		{	// This skin cannot be used here...
			continue;
		}

		$selected = ($edited_Blog->skin_ID == $Skin->ID);
		$select_url = '?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;skinpage=selection&amp;blog_skin_ID='.$Skin->ID.'&amp;'.url_crumb('collection');
		$preview_url = url_add_param( $edited_Blog->gen_blogurl(), 'tempskin='.rawurlencode($Skin->folder) );

		// Display skinshot:
		Skin::disp_skinshot( $Skin->folder, $Skin->name, 'select', $selected, $select_url, $preview_url );
	}

	echo '<div class="clear"></div>';

$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log$
 * Revision 1.13  2010/03/03 15:59:46  fplanque
 * minor/doc
 *
 * Revision 1.12  2010/02/26 15:52:20  efy-asimo
 * combine skin and skin settings tab into one single tab
 *
 * Revision 1.11  2010/02/08 17:54:42  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.10  2010/01/30 18:55:34  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.9  2010/01/13 22:48:57  fplanque
 * Missing crumbs
 *
 * Revision 1.8  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.7  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.6  2009/07/06 23:52:25  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.5  2009/05/23 20:20:18  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 * Revision 1.4  2009/03/08 23:57:45  fplanque
 * 2009
 *
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