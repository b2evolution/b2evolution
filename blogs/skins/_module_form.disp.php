<?php
/**
 * This is the template that displays different modules forms.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Session, $modules;

// get requested module name
$module_name = param( 'module', 'string', true );

foreach( $modules as $module )
{
	if( $module == $module_name )
	{ // the requested module was founded
		$Module = & $GLOBALS[$module.'_Module'];
		if( method_exists( $Module, 'display_form' ) )
		{	// Module has handle_htsrv_action function, we can call it
			$Module->display_form();
			break;
		}
	}
	// if the requested module doesn't exists don't display anything
}

/*
 * $Log$
 * Revision 1.1  2011/10/18 09:14:34  efy-asimo
 * Ability to display different module forms and handle module actions  inside the module
 *
 */
?>