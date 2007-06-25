<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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


$Form = & new Form( NULL, 'blogadvanced_checkchanges' );

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
  $Form->global_icon( T_('Manage installed skins...'), 'properties', 'admin.php?ctrl=skins', T_('Manage skins'), 3, 4 );
  $Form->global_icon( T_('Install new skin...'), 'new', 'admin.php?ctrl=skins&amp;action=new', T_('Install new'), 3, 4 );
}

$Form->begin_form( 'fform', T_('Choose a skin') );

	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'tab', 'skin' );
	$Form->hidden( 'blog',$edited_Blog->ID );

	$Form->begin_fieldset( T_('Installed skins') );

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

	$Form->end_fieldset( );

$Form->end_form();

/*
 * $Log$
 * Revision 1.1  2007/06/25 11:01:36  fplanque
 * MODULES (refactored MVC)
 *
 */
?>