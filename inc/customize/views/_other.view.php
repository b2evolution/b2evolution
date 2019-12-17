<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $AdminUI, $Settings, $current_User, $admin_url;

// Display customizer tabs to switch between site/collection skins and widgets in special div on customizer mode:
$AdminUI->display_customizer_tabs( array(
		'path' => 'other',
	) );

echo '<div class="evo_customizer__content">';

// Check if current User can edit site options:
$can_edit_site_options = ( $Settings->get( 'site_skins_enabled' ) && $current_User->check_perm( 'options', 'edit' ) );

if( $can_edit_site_options )
{	// Start of list of sites:
	echo '<ul class="evo_customizer__other_lists">'
		.'<li><a href="'.get_customizer_url().'?view=site_skin&amp;blog='.get_working_blog().'" target="_parent">'.T_('Site').'</a></li>';
}

// List of collections:
$BlogCache = & get_BlogCache();
$BlogCache->clear();
$BlogCache->load_user_blogs( 'blog_properties', 'edit' );
if( count( $BlogCache->cache ) > 1 )
{
	echo '<ul'.( $can_edit_site_options ? '' : ' class="evo_customizer__other_lists"' ).'>';
	foreach( $BlogCache->cache as $other_Blog )
	{
		if( get_working_blog() != $other_Blog->ID )
		{
			echo '<li><a href="'.$other_Blog->get( 'customizer_url', array( 'customizing_url' => '#baseurl#' ) ).'" target="_parent">'.$other_Blog->get( 'shortname' ).'</a></li>';
		}
	}
	echo '</ul>';
}

if( $can_edit_site_options )
{	// End of list of sites:
	echo '</ul>';
}

echo '</div>';

?>