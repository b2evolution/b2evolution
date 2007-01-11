<?php
/**
 * This file implements the UI view for the widgets installed on a blog.
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

	$Table = & new Table();

	$Table->title = T_($container).$legend_suffix;

	$Table->global_icon( T_('Add new widget...'), 'new',
			regenerate_url( '', 'action=new&amp;container='.rawurlencode($container) ), T_('Add widget'), 3, 4 );

	$Table->cols = array(
			array( 'th' => T_('Widget') ),
			array( 'th' => T_('Type') ),
			array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
		);

	$Table->display_init();

	$Table->display_list_start();

	// TITLE / COLUMN HEADERS:
	$Table->display_head();

	// BODY START:
	$Table->display_body_start();

	if( empty($container_Widget_array[$container]) )
	{	// TODO: cleanup
		$Table->display_line_start( true );
		$Table->display_col_start();
		echo T_('There is no widget in this container yet.');
		$Table->display_col_end();
		$Table->display_line_end();
	}
	else
	{
		foreach( $container_Widget_array[$container] as $ComponentWidget )
		{
			$Table->display_line_start();

			$Table->display_col_start();
			echo $ComponentWidget->get_name();
			$Table->display_col_end();

			// Note: this is totally useless, but we need more cols for the screen to feel "right"
			$Table->display_col_start();
			echo $ComponentWidget->type;
			$Table->display_col_end();

			$Table->display_col_start();
			echo action_icon( T_('Remove this widget!'), 'delete', regenerate_url( '', 'action=delete&amp;wi_ID='.$ComponentWidget->ID ) );
			$Table->display_col_end();

			$Table->display_line_end();
		}
	}

	// BODY END:
	$Table->display_body_end();

	$Table->display_list_end();
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
 * Revision 1.3  2007/01/11 02:25:06  fplanque
 * refactoring of Table displays
 * body / line / col / fadeout
 *
 * Revision 1.2  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>