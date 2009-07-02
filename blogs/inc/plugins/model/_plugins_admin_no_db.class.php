<?php
/**
 * This file implements the {@link Plugins_admin_no_DB} class, which gets used for administrative
 * handling of the {@link Plugin Plugins}, but without database.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class('plugins/model/_plugins_admin.class.php');


/**
 * A sub-class of {@link Plugins_admin} which will not load any DB info (i-e: Plugins and Events).
 *
 * This is useful for displaying a list of available plugins which can be installed.
 * This is also useful during installation in order to have a global $Plugins object that does not interfere with the installation process.
 *
 * {@internal This is probably quicker and cleaner than using a member boolean in {@link Plugins_admin} itself.}}
 *
 * @package plugins
 */
class Plugins_admin_no_DB extends Plugins_admin
{
	/**
	 * No-operation.
	 */
	function load_plugins_table()
	{
	}

	/**
	 * No-operation.
	 */
	function load_events()
	{
	}
}


/*
 * $Log$
 * Revision 1.4  2009/07/02 21:57:11  blueyed
 * doc fix: move files and classes to the plugins package
 *
 * Revision 1.3  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:50  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.4  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.3  2006/12/03 16:22:15  fplanque
 * doc
 *
 * Revision 1.2  2006/11/30 06:20:57  blueyed
 * load_class(parent)
 *
 * Revision 1.1  2006/11/30 05:43:40  blueyed
 * Moved Plugins::discover() to Plugins_admin::discover(); Renamed Plugins_no_DB to Plugins_admin_no_DB (and deriving from Plugins_admin)
 */
?>
