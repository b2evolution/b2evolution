<?php
/**
 * This file implements the UI view for the widgets installed on a blog.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog;

global $container_Widget_array;

global $container_list;


// Load widgets for current collection:
$WidgetCache = & get_Cache( 'WidgetCache' );
$container_Widget_array = & $WidgetCache->get_by_coll_ID( $Blog->ID );

/**
 * @param string Title of the container. This gets passed to T_()!
 * @param string Suffix of legend
 */
function display_container( $container, $legend_suffix = '' )
{
	global $Blog;
	global $edited_ComponentWidget;

	$Table = & new Table();

	$Table->title = T_($container).$legend_suffix;

	$Table->global_icon( T_('Add a widget...'), 'new',
			regenerate_url( '', 'action=new&amp;container='.rawurlencode($container) ), /* TRANS: note this is NOT a NEW widget */ T_('Add widget').' &raquo;', 3, 4 );

	$Table->cols = array(
			array( 'th' => T_('Widget') ),
			array( 'th' => T_('Type') ),
			array(
				'th' => T_('Move'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
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

  /**
	 * @var WidgetCache
	 */
	$WidgetCache = & get_Cache( 'WidgetCache' );
	$Widget_array = & $WidgetCache->get_by_coll_container( $Blog->ID, $container );

	if( empty($Widget_array) )
	{	// TODO: cleanup
		$Table->display_line_start( true );
		$Table->display_col_start();
		echo T_('There is no widget in this container yet.');
		$Table->display_col_end();
		$Table->display_line_end();
	}
	else
	{
		$widget_count = 0;
		foreach( $Widget_array as $ComponentWidget )
		{
			$widget_count++;

			if( isset($edited_ComponentWidget) && $edited_ComponentWidget->ID == $ComponentWidget->ID )
			{
				$fadeout = true;
			}
			else
			{
				$fadeout = false;
			}

			$Table->display_line_start( false, $fadeout );

			$Table->display_col_start();
			$ComponentWidget->init_display( array() );
			$widget_name =  '<strong>'.$ComponentWidget->disp_params[ 'widget_name' ].'</strong>';
			if( $ComponentWidget->disp_params[ 'widget_name' ] != $ComponentWidget->get_short_desc() )
			{	// The name is customized and the short desc may be relevant additional info
				$widget_name .= ' ('.$ComponentWidget->get_short_desc().')';
			}
			echo '<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID).'">'.$widget_name.'</a>';
			$Table->display_col_end();

			// Note: this is totally useless, but we need more cols for the screen to feel "right":
			$Table->display_col_start();
			echo $ComponentWidget->type;
			$Table->display_col_end();

			// Move
			$Table->display_col_start();
			//echo $ComponentWidget->order.' ';
			if( $widget_count > 1 )
			{
				echo action_icon( T_('Move up!'), 'move_up', regenerate_url( 'blog', 'action=move_up&amp;wi_ID='.$ComponentWidget->ID ) );
			}
			else
			{
				echo get_icon( 'nomove', 'imgtag', array( 'class'=>'action_icon' ) );
			}
			if( $widget_count < count($Widget_array))
			{
				echo action_icon( T_('Move down!'), 'move_down', regenerate_url( 'blog', 'action=move_down&amp;wi_ID='.$ComponentWidget->ID ) );
			}
			else
			{
				echo get_icon( 'nomove', 'imgtag', array( 'class'=>'action_icon' ) );
			}
			$Table->display_col_end();

			// Actions
			$Table->display_col_start();
			echo action_icon( T_('Edit widget settings!'), 'edit', regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ) );
			echo action_icon( T_('Remove this widget!'), 'delete', regenerate_url( 'blog', 'action=delete&amp;wi_ID='.$ComponentWidget->ID ) );
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

global $rsc_url;

echo '<img src="'.$rsc_url.'img/blank.gif" width="1" height="1" /><!-- for IE -->';

// Fadeout javascript
echo '<script type="text/javascript" src="'.$rsc_url.'js/fadeout.js"></script>';
echo '<script type="text/javascript">addEvent( window, "load", Fat.fade_all, false);</script>';

/*
 * $Log$
 * Revision 1.9  2008/01/05 17:17:36  blueyed
 * Fix output of rsc_url
 *
 * Revision 1.8  2007/12/23 15:07:07  fplanque
 * Clean widget name
 *
 * Revision 1.7  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.6  2007/12/22 23:24:45  yabs
 * cleanup from adding core params
 *
 * Revision 1.5  2007/12/22 16:57:01  yabs
 * adding core parameters for css id/classname and widget list title
 *
 * Revision 1.4  2007/09/29 08:18:21  yabs
 * UI - added edit to actions
 *
 * Revision 1.3  2007/09/08 20:23:04  fplanque
 * action icons / wording
 *
 * Revision 1.2  2007/09/03 19:36:06  fplanque
 * chicago admin skin
 *
 * Revision 1.1  2007/06/25 11:02:01  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/06/18 21:25:48  fplanque
 * one class per core widget
 *
 * Revision 1.8  2007/06/11 22:01:54  blueyed
 * doc fixes
 *
 * Revision 1.7  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.6  2007/03/26 17:12:41  fplanque
 * allow moving of widgets
 *
 * Revision 1.5  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 *
 * Revision 1.4  2007/01/11 02:57:25  fplanque
 * implemented removing widgets from containers
 *
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
 */
?>
