<?php
/**
 * This file implements Menu functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get menu types
 *
 * @return array
 */
function get_menu_types()
{
	return array(
			T_('Contents') => array(
				'home'           => T_('Front Page'),
				'recentposts'    => T_('Latest posts').' (disp=posts)',
				'latestcomments' => T_('Latest comments').' (disp=comments)',
				'search'         => T_('Search page').' (disp=search)',
				'item'           => T_('Any item (post, page, etc...)').' (disp=single|page) ',
				'arcdir'         => T_('Archives').' (disp=arcdir)',
				'catdir'         => T_('Categories').' (disp=catdir)',
				'tags'           => T_('Tags').' (disp=tags)',
				'postidx'        => T_('Post index').' (disp=postidx)',
				'mediaidx'       => T_('Photo index').' (disp=mediaidx)',
				'sitemap'        => T_('Site Map').' (disp=sitemap)',
			),
			T_('Communication') => array(
				'ownercontact'  => T_('Collection owner contact form').' (disp=msgform)',
				'owneruserinfo' => T_('Collection owner profile').' (disp=user)',
				'users'         => T_('User directory').' (disp=users)',
			),
			T_('Tools') => array(
				'login'        => T_('Log in form').' (disp=login)',
				'logout'       => T_('Logout'),
				'register'     => T_('Registration form').' (disp=register)',
				'myprofile'    => T_('View my profile').' (disp=user)',
				'visits'       => T_('View my visits').' (disp=visits)',
				'profile'      => T_('Edit my profile').' (disp=profile)',
				'avatar'       => T_('Edit my profile picture').' (disp=avatar)',
				'useritems'    => T_('View my posts/items').' (disp=useritems)',
				'usercomments' => T_('View my comments').' (disp=usercomments)',
			),
			T_('Other') => array(
				'postnew' => T_('Create new Item').' (disp=edit)',
				'admin'   => T_('Go to Back-Office'),
				'url'     => T_('Go to any URL'),
			),
		);
}


/**
 * Get types for Site Menu
 *
 * @return array
 */
function get_site_menu_types( $additional_types = array() )
{
	$menu_types = get_menu_types();
	$menu_types[ T_('Other') ]['text'] = T_('Text');

	return $menu_types;
}


/**
 * Get site menu type title by type key
 *
 * @param string Type key
 * @return string Type title
 */
function get_site_menu_type_title( $type )
{
	$grouped_menu_types = get_site_menu_types();

	foreach( $grouped_menu_types as $menu_type_group => $menu_types )
	{
		if( isset( $menu_types[ $type ] ) )
		{
			return $menu_types[ $type ];
		}
	}

	return $type;
}
?>