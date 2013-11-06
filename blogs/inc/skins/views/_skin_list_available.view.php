<?php
/**
 * This file implements the UI view for the Available skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
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

$filename_params = array(
		'inc_files'		=> false,
		'recurse'		=> false,
		'basename'		=> true,
	);
// Get all skin folder names:
$skin_folders = get_filenames( $skins_path, $filename_params );

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
	$disp_params = array(
		'function' => 'install',
		'function_url' => '?ctrl=skins&amp;action=create&amp;skin_folder='.rawurlencode($skin_folder).'&amp;redirect_to='.rawurlencode($redirect_to).'&amp;'.url_crumb('skin')
	);
	Skin::disp_skinshot( $skin_folder, $skin_folder, $disp_params );
}

echo '<div class="clear"></div>';
$block_item_Widget->disp_template_replaced( 'block_end' );

/*
 * $Log$
 * Revision 1.16  2013/11/06 08:04:46  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>