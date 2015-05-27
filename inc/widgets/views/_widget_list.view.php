<?php
/**
 * This file implements the UI view for the widgets installed on a blog.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $admin_url;

global $container_Widget_array;

global $container_list;

if( $current_User->check_perm( 'options', 'edit', false ) )
{
	echo '<div class="pull-right" style="margin-bottom:10px">';
	echo action_icon( TS_('Reload containers!'), 'reload',
	                        '?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=reload&amp;'.url_crumb('widget'), T_('Reload containers'), 3, 4, array( 'class' => 'action_icon hoverlink btn btn-info' ) );
	echo '</div>';
}

// Load widgets for current collection:
$WidgetCache = & get_WidgetCache();
$container_Widget_array = & $WidgetCache->get_by_coll_ID( $Blog->ID );

/**
 * @param string Title of the container. This gets passed to T_()!
 * @param string Suffix of legend
 */
function display_container( $container, $legend_suffix = '' )
{
	global $Blog;
	global $Session;

	$Table = new Table();

	$Table->title = '<span class="container_name">'.T_($container).'</span>'.$legend_suffix;

	// Table ID - fp> needs to be handled cleanly by Table object
	$table_id = str_replace( array( ' ', ':' ), array( '_', '-' ), $container ); // fp> Using the container name which has special chars is a bad idea. Counter would be better

	$Table->global_icon( T_('Add a widget...'), 'new',
			regenerate_url( '', 'action=new&amp;container='.rawurlencode($container) ), /* TRANS: ling used to add a new widget */ T_('Add widget').' &raquo;', 3, 4, array( 'id' => 'add_new_'.$table_id ) );

	$Table->cols = array(
			array(
				'th' => '', // checkbox
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
			array(
				'th' => /* TRANS: shortcut for enabled */ T_( 'En' ),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
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
	//enable fadeouts here
	$Table->display_init( array(
				'list_attrib' => 'id="'.$table_id.'"',
				'list_class'  => 'widget_container_list'
			),
			array( 'fadeouts' => true )
		);

	/*
	if( $legend_suffix )
	{	// add jQuery no-drop -- fp> what do we need this one for?
		$Table->params['head_title'] = str_replace( 'class="grouped"', 'class="grouped no-drop"', $Table->params['head_title'] );
	}
	*/

	// Dirty hack for bootstrap skin
	$Table->params['list_start'] = str_replace( '<div class="', '<div class="panel panel-default ', $Table->params['list_start'] );

	$Table->display_list_start();

	// TITLE / COLUMN HEADERS:
	$Table->display_head();

	// BODY START:
	$Table->display_body_start();

	/**
	 * @var WidgetCache
	 */
	$WidgetCache = & get_WidgetCache();
	$Widget_array = & $WidgetCache->get_by_coll_container( $Blog->ID, $container );

	if( empty($Widget_array) )
	{	// TODO: cleanup
		$Table->display_line_start( true );
		$Table->display_col_start( array( 'colspan' => 6 ) );
		echo '<span class="new_widget">'.T_('There is no widget in this container yet.').'</span>';
		$Table->display_col_end();
		$Table->display_line_end();
	}
	else
	{
		$widget_count = 0;
		foreach( $Widget_array as $ComponentWidget )
		{
			$widget_count++;
			$enabled = $ComponentWidget->get( 'enabled' );

			$fadeout_id = $Session->get( 'fadeout_id' );
			if( isset($fadeout_id) && $ComponentWidget->ID == $fadeout_id )
			{
				$fadeout = true;
				$Session->delete( 'fadeout_id' );
			}
			else
			{
				$fadeout = false;
			}

			$Table->display_line_start( false, $fadeout );

			$Table->display_col_start();
			echo '<input type="checkbox" name="widgets[]" value="'.$ComponentWidget->ID.'" />';
			$Table->display_col_end();

			$Table->display_col_start();
			if ( $enabled )
			{
				// Indicator for the JS UI:
				echo '<span class="widget_is_enabled">';
				echo action_icon( T_( 'The widget is enabled.' ), 'enabled', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
				echo '</span>';
			}
			else
			{
				echo action_icon( T_( 'The widget is disabled.' ), 'disabled', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			$Table->display_col_end();

			$Table->display_col_start();
			$ComponentWidget->init_display( array() );
			echo '<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ).'" class="widget_name">'
						.$ComponentWidget->get_desc_for_list().'</a> '
						.$ComponentWidget->get_help_link();
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
				echo action_icon( T_('Move up!'), 'move_up', regenerate_url( 'blog', 'action=move_up&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			else
			{
				echo get_icon( 'nomove', 'imgtag', array( 'class'=>'action_icon' ) );
			}
			if( $widget_count < count($Widget_array))
			{
				echo action_icon( T_('Move down!'), 'move_down', regenerate_url( 'blog', 'action=move_down&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			else
			{
				echo get_icon( 'nomove', 'imgtag', array( 'class'=>'action_icon' ) );
			}
			$Table->display_col_end();

			// Actions
			$Table->display_col_start();
			if ( $enabled )
			{
				echo action_icon( T_( 'Disable this widget!' ), 'deactivate', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			else
			{
				echo action_icon( T_( 'Enable this widget!' ), 'activate', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			echo '<span class="edit_icon_hook">'.action_icon( T_('Edit widget settings!'), 'edit', regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ) ).'</span>';
			echo '<span class="delete_icon_hook">'.action_icon( T_('Remove this widget!'), 'delete', regenerate_url( 'blog', 'action=delete&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) ).'</span>';
			$Table->display_col_end();

			$Table->display_line_end();
		}
	}

	// BODY END:
	$Table->display_body_end();

	$Table->display_list_end();
}

$Form = new Form( $admin_url.'?ctrl=widgets&blog='.$Blog->ID );

$Form->add_crumb( 'widget' );

$Form->begin_form();

// fp> what browser do we need a fielset for?
echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

// Display ALL containers (fp> I removed the things that thought it knew which containers are part of the current skin because cearly, in i7, nobody knows)
foreach( $container_Widget_array as $container=>$dummy )
{
	display_container( $container );
}

echo '</fieldset>'."\n";

echo '<span class="btn-group">';
$Form->button( array( 'type' => 'button', 'value' => T_('Check All'), 'id' => 'widget_button_check_all' ) );
$Form->button( array( 'type' => 'button', 'value' => T_('Uncheck All'), 'id' => 'widget_button_uncheck_all' ) );
echo '</span>';

echo '<span class="btn-group">';
$Form->button( array( 'type' => 'button', 'value' => T_('Check Active'), 'id' => 'widget_button_check_active' ) );
$Form->button( array( 'type' => 'button', 'value' => T_('Check Inactive'), 'id' => 'widget_button_check_inactive' ) );
echo '</span>';

echo ' '.T_('With checked do:');
echo '<span class="btn-group">';
$Form->button( array( 'type' => 'submit', 'value' => T_('Activate'), 'name' => 'actionArray[activate]' ) );
$Form->button( array( 'type' => 'submit', 'value' => T_('De-activate'), 'name' => 'actionArray[deactivate]' ) );
echo '</span>';

$Form->end_form();

echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );

?>