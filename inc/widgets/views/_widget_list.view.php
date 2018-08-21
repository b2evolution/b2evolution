<?php
/**
 * This file implements the UI view for the widgets installed on a blog.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Skin, $admin_url;

global $container_Widget_array;

// Load widgets for current collection:
$WidgetCache = & get_WidgetCache();
$container_Widget_array = & $WidgetCache->get_by_coll_ID( $Blog->ID, false, get_param( 'skin_type' ) );

$Form = new Form( $admin_url.'?ctrl=widgets&blog='.$Blog->ID );

$Form->add_crumb( 'widget' );

$Form->begin_form();

// fp> what browser do we need a fielset for?
echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

echo '<div class="row">';

echo '<div class="col-md-6 col-sm-12">';
	echo '<h4 class="pull-left">'.T_('Skin Containers').'</h4>';
	if( $current_User->check_perm( 'options', 'edit', false ) )
	{	// Display a button to scan skin for widgets if current User has a permission:
		echo action_icon( T_('Reload container definitions'), 'reload',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=reload&amp;skin_type='.get_param( 'skin_type' ).'&amp;'.url_crumb('widget'), T_('Reload container definitions'), 3, 4, array( 'class' => 'action_icon hoverlink btn btn-info pull-right' ) );
	}
	echo '<div class="clearfix"></div>';
	display_containers( get_param( 'skin_type' ), true );
echo '</div>';

echo '<div class="col-md-6 col-sm-12">';
	echo '<h4 class="pull-left">'.T_('Sub-Containers').'</h4>';
	if( $current_User->check_perm( 'options', 'edit', false ) )
	{	// Display a button to add new sub-container if current User has a permission:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;skin_type='.get_param( 'skin_type' ), T_('Add container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
	}
	echo '<div class="clearfix"></div>';
	display_containers( get_param( 'skin_type' ), false );
echo '</div>';

echo '</div>';

echo '</fieldset>'."\n";

// Display action buttons for widgets list:
display_widgets_action_buttons( $Form );

$Form->end_form();

echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );

?>