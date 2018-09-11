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

global $Collection, $Blog, $admin_url;

// Get skin ID for the selected widgets type:
$skin_ID = $Blog->get( get_param( 'skin_type' ).'_skin_ID', array( 'real_value' => true ) );

if( empty( $skin_ID ) && get_param( 'skin_type' ) != 'normal' )
{	// Don't allow to control widgets if same skin is used for mobile/tablet:
	echo '<div>'.sprintf( T_('If you want control widgets differently for mobile/tablet, <a %s>select a specific skin here</a>.'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;skinpage=selection&amp;skin_type='.get_param( 'skin_type' ).'&amp;blog='.$Blog->ID.'"' ).'</div>';
}
else
{	// Allow to control widgets if different skin is used for mobile/tablet:

	// Load widgets for current collection:
	$WidgetCache = & get_WidgetCache();
	$container_Widget_array = & $WidgetCache->get_by_coll_ID( $Blog->ID, false, get_param( 'skin_type' ) );

	$Form = new Form( $admin_url.'?ctrl=widgets&blog='.$Blog->ID );

	$Form->add_crumb( 'widget' );

	$Form->begin_form();

	// fp> what browser do we need a fielset for?
	echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

	echo '<div class="row">';

	// Skin Containers:
	echo '<div class="col-md-4 col-sm-12">';
		echo '<h4 class="pull-left">'.T_('Skin Containers').'</h4>';
		// Display a button to scan skin for widgets:
		echo action_icon( T_('Reload container definitions'), 'reload',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=reload&amp;skin_type='.get_param( 'skin_type' ).'&amp;'.url_crumb('widget'), T_('Reload container definitions'), 3, 4, array( 'class' => 'action_icon hoverlink btn btn-info pull-right' ) );
		echo '<div class="clearfix"></div>';
		display_containers( get_param( 'skin_type' ), true, false );
	echo '</div>';

	// Sub-Containers & Page Containers:
	echo '<div class="col-md-4 col-sm-12">';
		// Sub-Containers:
		echo '<h4 class="pull-left">'.T_('Sub-Containers').'</h4>';
		// Display a button to add new sub-container:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;container_type=sub&amp;skin_type='.get_param( 'skin_type' ), T_('Add Sub-Container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
		echo '<div class="clearfix"></div>';
		display_containers( get_param( 'skin_type' ), false, false );

		// Page Containers:
		echo '<h4 class="pull-left">'.T_('Page Containers').'</h4>';
		// Display a button to add new page container:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;container_type=page&amp;skin_type='.get_param( 'skin_type' ), T_('Add Page Container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
		echo '<div class="clearfix"></div>';
		display_containers( get_param( 'skin_type' ), false, false, true );
	echo '</div>';

	// Shared Main and Sub Containers:
	echo '<div class="col-md-4 col-sm-12">';
		echo '<h4 class="pull-left">'.T_('Shared Containers').'</h4>';
		// Display a button to add new shared container:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;container_type=shared&amp;skin_type='.get_param( 'skin_type' ), T_('Add Shared Container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
		echo '<div class="clearfix"></div>';
		display_containers( get_param( 'skin_type' ), true, true );

		echo '<h4 class="pull-left">'.T_('Shared Sub-Containers').'</h4>';
		echo '<div class="clearfix"></div>';
		display_containers( get_param( 'skin_type' ), false, true );
	echo '</div>';

	echo '</div>';

	echo '</fieldset>'."\n";

	// Display action buttons for widgets list:
	display_widgets_action_buttons( $Form );

	$Form->end_form();
}

echo '<br />';

?>