<?php
/**
 * This file implements the UI view for the Available widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $container;

global $core_componentwidget_codes;

echo '<h2><span class="right_icons">'.action_icon( T_('Cancel!'), 'close', regenerate_url( 'container' ) ).'</span>'
	.sprintf(T_('Widgets available for insertion into &laquo;%s&raquo;'), $container ).'</h2>';

echo '<ul>';

foreach( $core_componentwidget_codes as $code )
{
	$ComponentWidget = & new ComponentWidget( NULL, 'core', $code );

	echo '<li>';
	echo '<a href="'.regenerate_url( '', 'action=create&amp;type='.$ComponentWidget->type.'&amp;code='.$ComponentWidget->code ).'" title="'.T_('Add this widget to the container').'">';
	echo get_icon( 'new' ).$ComponentWidget->get_name();
	echo '</a></li>';
}
echo '</ul>';

/*
 * $Log$
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>