<?php
/**
 * This file implements the UI view for the Available skins.
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

global $Blog;

global $container_Widget_array;

global $container_list;


// Load widgets for current collection:
// EXPERIMENTAL:

$container_Widget_array = array();

$sql = 'SELECT *
		      FROM T_widget
		     WHERE wi_coll_ID = '.$Blog->ID.'
		     ORDER BY wi_sco_name, wi_order';
$widget_rs = $DB->get_results( $sql, OBJECT, 'Get list of widgets for collection' );
foreach( $widget_rs as $row )
{
	$container_Widget_array[$row->wi_sco_name][] = & new ComponentWidget( $row );
}
// pre_dump($container_Widget_array);

function display_container( $container, $legend_suffix = '' )
{
	global $container_Widget_array;

 	echo '<fieldset>';
	echo '<legend>'.T_($container).$legend_suffix.'</legend>';
	if( empty($container_Widget_array[$container]) )
	{
		echo '<p>'.T_('There is no widget in this container yet.').'</p>';
	}
	else
	{
		echo '<ul>';
		foreach( $container_Widget_array[$container] as $ComponentWidget )
		{
			echo '<li>'.$ComponentWidget->get_name().'</li>';
		}
		echo '</ul>';
	}

	echo '<p>'.action_icon( T_('Add new widget...'), 'new',
			regenerate_url( '', 'action=new&amp;container='.rawurlencode($container) ), T_('Add widget'), 3, 4 ).'</p>';

	echo '</fieldset>';
}

// Dislplay containers for current skin:
foreach( $container_list as $container )
{
	display_container( $container );
}

// Display containers not in current skin:
foreach( $container_Widget_array as $container=>$dummy )
{
	if( !in_array( $container, $container_list ) )
	{
		display_container( $container, ' '.T_('[NOT INCLUDED IN SELECTED SKIN!]') );
	}
}


/*
 * $Log$
 * Revision 1.2  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>