<?php
/**
 * This file implements the UI view for the skin selection when creating a blog.
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

global $kind;

echo '<h2>'.sprintf( T_('New %s'), get_collection_kinds($kind) ).':</h2>';

echo '<h3>'.T_('Pick a skin:').'</h3>';

$SkinCache = & get_SkinCache();
$SkinCache->load_all();

// TODO: this is like touching private parts :>
foreach( $SkinCache->cache as $Skin )
{
	if( $Skin->type != 'normal' )
	{	// This skin cannot be used here...
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

?>