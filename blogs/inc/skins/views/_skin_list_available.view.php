<?php
/**
 * This file implements the UI view for the Available skins.
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

global $skins_path;

global $redirect_to;

/**
 * @var SkinCache
 */
$SkinCache = & get_SkinCache();
$SkinCache->load_all();

$block_item_Widget = new Widget( 'block_item' );

$block_item_Widget->title = T_('Skins available for installation').get_manual_link('installing_skins');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
  $block_item_Widget->global_icon( T_('Cancel install!'), 'close', $redirect_to );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

// Get all skin folder names:
$skin_folders = get_filenames( $skins_path, false, true, true, false, true );
// Go through all skin folders:
foreach( $skin_folders as $skin_folder )
{
	if( ! strlen($skin_folder) || $skin_folder[0] == '.' || $skin_folder == 'CVS' )
	{
		continue;
	}
	if( $SkinCache->get_by_folder( $skin_folder, false ) )
	{	// Already installed...
		continue;
	}

	// Display skinshot:
	$function_url = '?ctrl=skins&amp;action=create&amp;skin_folder='.rawurlencode($skin_folder).'&amp;redirect_to='.rawurlencode($redirect_to).'&amp;'.url_crumb('skin');
	Skin::disp_skinshot( $skin_folder, $skin_folder, 'install', false, NULL, $function_url );
}

echo '<div class="clear"></div>';
$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log$
 * Revision 1.13  2010/09/08 15:07:45  efy-asimo
 * manual links
 *
 * Revision 1.12  2010/02/08 17:54:43  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.11  2010/01/30 18:55:34  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.10  2010/01/13 22:48:57  fplanque
 * Missing crumbs
 *
 * Revision 1.9  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.8  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.7  2009/05/23 20:20:18  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 * Revision 1.6  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.5  2008/09/07 09:13:28  fplanque
 * Locale definitions are now included in language packs.
 * A bit experimental but it should work...
 *
 * Revision 1.4  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/09/29 03:42:12  fplanque
 * skin install UI improvements
 *
 * Revision 1.2  2007/09/03 20:11:06  blueyed
 * Skip hidden and CVS folders in $skin_folder
 *
 * Revision 1.1  2007/06/25 11:01:39  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.5  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.4  2007/01/08 21:53:51  fplanque
 * typo
 */
?>