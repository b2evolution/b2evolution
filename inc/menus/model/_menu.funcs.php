<?php
/**
 * This file implements Menu functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
				'password'     => T_('Change my password').' (disp=pwdchange)',
				'userprefs'    => T_('Change my preferences').' (disp=userprefs)',
				'usersubs'     => T_('Notifications & Subscriptions').' (disp=subs)',
				'useritems'    => T_('View my posts/items').' (disp=useritems)',
				'usercomments' => T_('View my comments').' (disp=usercomments)',
			),
			T_('Messaging') => array(
				'messages' => T_('Private messages'),
				'contacts' => T_('Messaging contacts'),
			),
			T_('Other') => array(
				'flagged' => T_('Flagged Items'),
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


/**
 * Get Site Menu ID by menu name or create default/demo Menu with entries
 *
 * @param string Site Menu name
 * @return integer|FALSE Site Menu ID, FALSE if new menu cannot be created
 */
function get_default_site_menu_ID( $menu_name )
{
	global $DB;

	if( ! $DB->get_var( 'SHOW TABLES LIKE "T_menus__menu"' ) )
	{	// The Menus tables still doesn't exist, probably this is a call from old upgrade version:
		return false;
	}

	$SiteMenuCache = & get_SiteMenuCache();
	if( $SiteMenu = & $SiteMenuCache->get_by_name( $menu_name, false, false ) )
	{	// Use existing Menu:
		return $SiteMenu->ID;
	}

	load_class( 'menus/model/_sitemenuentry.class.php', 'SiteMenuEntry' );

	// Try to create Menu to Sitemap:
	$SiteMenu = new SiteMenu();
	$SiteMenu->set( 'name', $menu_name );
	if( ! $SiteMenu->dbinsert() )
	{
		return false;
	}

	// Set default/demo menu entries depending on requested name:
	switch( $menu_name )
	{
		case 'Site Map - Common links':
			$menu_entries = array(
				array( 'type' => 'home', 'text' => T_('Home') ),
				array( 'type' => 'recentposts', 'text' => T_('Recently') ),
				array( 'type' => 'arcdir', 'text' => T_('Archives') ),
				array( 'type' => 'mediaidx', 'text' => T_('Photo index') ),
				array( 'type' => 'latestcomments', 'text' => T_('Latest comments') ),
				array( 'type' => 'owneruserinfo', 'text' => T_('Owner details') ),
				array( 'type' => 'ownercontact', 'text' => T_('Contact') ),
			);
			break;
	}

	if( isset( $menu_entries ) )
	{	// Create default/demo menu entries:
		$menu_entry_order = 10;
		foreach( $menu_entries as $menu_entry )
		{
			$SiteMenuEntry = new SiteMenuEntry();
			$SiteMenuEntry->set( 'menu_ID', $SiteMenu->ID );
			$SiteMenuEntry->set( 'type', $menu_entry['type'] );
			$SiteMenuEntry->set( 'text', $menu_entry['text'] );
			$SiteMenuEntry->set( 'order', $menu_entry_order );
			$SiteMenuEntry->dbinsert();
			$menu_entry_order += 10;
		}
	}

	return $SiteMenu->ID;
}
?>