<?php
/**
 * This file implements the UI view for the widgets custmoizer mode.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $AdminUI;

// Display customizer tabs to switch between site/collection skins and widgets in special div on customizer mode:
$AdminUI->display_customizer_tabs( array(
		'active_submenu' => 'widgets',
	) );

echo '<div class="evo_customizer__content">';

echo '<br /><p class="text-center">'.T_('To edit a widget click on it in the right panel.').'</p>';

echo '</div>';
?>