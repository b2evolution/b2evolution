<?php
/**
 * This file implements the UI view for the skin selection when creating a blog.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $kind, $admin_url, $action, $AdminUI;

$kind_title = get_collection_kinds( $kind );

echo action_icon( T_('Abort creating new collection'), 'close', $admin_url.'?ctrl=dashboard', ' '.sprintf( T_('Abort new "%s" collection'), $kind_title ), 3, 3, array( 'class' => 'action_icon floatright' ) );

echo '<h2 class="page-title">'.sprintf( T_('New %s'), $kind_title ).':</h2>';

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