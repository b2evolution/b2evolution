<?php
/**
 * This file displays the admin submenu / subtabs.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE.
 *
 * @todo Ultimate goal here is to have a reconfigurable admin structure...
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

switch( $admin_tab )
{ // Submenu depends on main admin page:

	case 'options':
		// Settings screen:
		$submenu = array(
			'general' => array( T_('General'), regenerate_url( '', 'tab=general', 'b2options.php' ) ),
			'regional' => array( T_('Regional'), regenerate_url( '', array( 'tab=regional', 'notransext='.(int)(isset($notransext) && $notransext) ), 'b2options.php' ) ),
			'files' => array( T_('Files'), regenerate_url( '', '', 'fileset.php' ) ),
			'statuses' => array( T_('Post statuses'), regenerate_url( '', '', 'statuses.php' ) ),
			'types' => array( T_('Post types'), regenerate_url( '', '', 'types.php' ) ),
			'plugins' => array( T_('Plug-ins'), regenerate_url( '', '', 'plugins.php' ) ),
			);
		break;

	case 'blogs':
		// Blog properties screen:
		$submenu = array(
			'general' => array( T_('General'), regenerate_url( '', array( 'tab=general', 'action=edit' ), 'blogs.php' ) ),
			'perm' => array( T_('Permissions'), regenerate_url( '', array( 'tab=perm', 'action=edit' ), 'blogs.php' ) ),
			'advanced' => array( T_('Advanced'), regenerate_url( '', array( 'tab=advanced', 'action=edit' ), 'blogs.php' ) ),
			);
		break;

	case 'stats':
		// Stats screens:
		$submenu = array(
			'summary' => array( T_('Summary'), regenerate_url( '', 'tab=summary', 'b2stats.php' ) ),
			'other' => array( T_('Direct Accesses'), regenerate_url( '', 'tab=other', 'b2stats.php' ) ),
			'referers' => array( T_('Referers'), regenerate_url( '', 'tab=referers', 'b2stats.php' ) ),
			'refsearches' => array( T_('Refering Searches'), regenerate_url( '', 'tab=refsearches', 'b2stats.php' ) ),
			'syndication' => array( T_('Syndication'), regenerate_url( '', 'tab=syndication', 'b2stats.php' ) ),
			'useragents' => array( T_('User Agents'), regenerate_url( '', 'tab=useragents', 'b2stats.php' ) ),
			);
		break;

	default:
		// NO SUBMENU!
		echo '<div class="panelblock">';
		return;
}

// Include submenu display
require dirname(__FILE__).'/'.$adminskins_subdir.$admin_skin.'/_submenu.php';

echo '<div class="tabbedpanelblock">';

/*
 * $Log$
 * Revision 1.7  2005/02/08 21:35:52  blueyed
 * use regenerate_url()
 *
 * Revision 1.6  2004/12/17 20:38:51  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.5  2004/11/22 17:48:05  fplanque
 * skin cosmetics
 *
 */
?>
