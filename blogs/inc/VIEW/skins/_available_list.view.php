<?php
/**
 * This file implements the UI view for the Available skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $skins_path;

/**
 * @var SkinCache
 */
$SkinCache = & get_Cache( 'SkinCache' );
$SkinCache->load_all();

echo '<h2>'.T_('Skins available for installation').'</h2>';

$skin_folders = get_filenames( $skins_path, false, true, true, false, true );

foreach( $skin_folders as $skin_folder )
{
  if( $SkinCache->get_by_folder( $skin_folder, false ) )
	{	// Already installed...
		continue;
	}

	Skin::disp_skinshot( $skin_folder, 'install' );
}


echo '<div class="clear"></div>';

/*
 * $Log:
 */
?>