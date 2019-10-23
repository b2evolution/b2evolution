<?php
/**
 * This file display the site menus list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url;

$SQL = new SQL( 'Get menus' );

$SQL->SELECT( '*' );
$SQL->FROM( 'T_menus__menu' );

$Results = new Results( $SQL->get(), 'menu_', 'A' );

$Results->title = T_('Menus').' ('.$Results->get_total_rows().')' . get_manual_link( 'menus-list' );

$Results->cols[] = array(
		'th' => T_('Name'),
		'td' => '<a href="'.$admin_url.'?ctrl=menus&amp;action=edit&amp;menu_ID=$menu_ID$">$menu_name$</a>',
		'order' => 'menu_name',
	);

$Results->cols[] = array(
		'th' => T_('Locale'),
		'td' => '%locale_flag( #menu_locale#, "", "flag", "", false )% $menu_locale$',
		'order' => 'menu_locale',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap small',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( TS_('Edit this menu...'), 'properties', $admin_url.'?ctrl=menus&amp;action=edit&amp;menu_ID=$menu_ID$' )
				.action_icon( T_('Delete this menu!'), 'delete', regenerate_url( 'menu_ID,action', 'menu_ID=$menu_ID$&amp;action=delete&amp;'.url_crumb( 'menu' ) ) ),
		);

	$Results->global_icon( T_('New menu'), 'new', regenerate_url( 'action', 'action=new' ), T_('New menu').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->display();

?>