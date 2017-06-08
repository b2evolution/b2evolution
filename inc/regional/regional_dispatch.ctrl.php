<?php
/**
 * Dispatch to the last used controller in Global Settings -> Regional
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Store/retrieve preferred tab from UserSettings:
$UserSettings->param_Request( 'tab', 'pref_glob_regional_tab', 'string', 'locales', true /* memorize */, true /* force */ );

// Avoid infernal loop:
if( $tab == 'regional' )
{
	$ctrl = 'locales';
}
else
{
	$ctrl = $tab;
}

// Check matching controller file:
if( !isset($ctrl_mappings[$ctrl]) )
{
	debug_die( 'The requested controller ['.$ctrl.'] does not exist.' );
}

// Call the requested controller:
require $inc_path.$ctrl_mappings[$ctrl];

?>