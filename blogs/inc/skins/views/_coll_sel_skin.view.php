<?php
/**
 * This file implements the UI view for the skin selection when creating a blog.
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
 * @version $Id: _coll_sel_skin.view.php 6493 2014-04-17 05:45:28Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $kind, $admin_url, $action, $AdminUI;

$kind_title = get_collection_kinds( $kind );

echo action_icon( sprintf( T_('Abort New %s'), $kind_title ), 'close', $admin_url.'?ctrl=collections&amp;tab=list', ' '.sprintf( T_('Abort New %s'), $kind_title ), 3, 3, array( 'class' => 'action_icon floatright' ) );

echo '<h2>'.sprintf( T_('New %s'), $kind_title ).':</h2>';

if( $action == 'new-selskin' )
{ // Select an existing skin
	echo '<h3>'.sprintf( T_('Pick an existing skin below: (or <a %s>install a new one now</a>)'), 'href="'.$admin_url.'?ctrl=collections&amp;action=new-installskin&amp;kind='.$kind.'&amp;skin_type=normal"' ).'</h3>';

	$SkinCache = & get_SkinCache();
	$SkinCache->load_all();

	// TODO: this is like touching private parts :>
	foreach( $SkinCache->cache as $Skin )
	{
		if( $Skin->type != 'normal' )
		{ // This skin cannot be used here...
			continue;
		}

		$disp_params = array(
			'function' => 'pick',
			'select_url' => '?ctrl=collections&amp;action=new-name&amp;kind='.$kind.'&amp;skin_ID='.$Skin->ID
		);

		// Display skinshot:
		Skin::disp_skinshot( $Skin->folder, $Skin->name, $disp_params );
	}

	echo '<div class="clear"></div>';
}
elseif( $action == 'new-installskin' )
{ // Display a form to install new skin
	set_param( 'redirect_to', $admin_url.'?ctrl=collections&action=new-selskin&kind='.$kind );
	$AdminUI->disp_view( 'skins/views/_skin_list_available.view.php' );
}

?>