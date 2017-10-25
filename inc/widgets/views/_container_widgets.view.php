<?php
/**
 * This file implements the UI view for the widgets installed on a blog.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Skin, $admin_url, $AdminUI, $WidgetContainer;

// Display customizer tabs to switch between skin and widgets in special div on customizer mode:
$AdminUI->display_customizer_tabs( array(
		'path' => array( 'coll', 'widgets' ),
	) );

// Start of customizer content:
echo '<div class="evo_customizer__content">';

$Form = new Form( $admin_url.'?ctrl=widgets&blog='.$Blog->ID );

$Form->add_crumb( 'widget' );
$Form->hidden( 'wico_ID', $WidgetContainer->ID );
$Form->hidden( 'mode', 'customizer' );
$Form->hidden( 'skin_type', get_param( 'skin_type' ) );

$Form->begin_form( NULL, '', array( 'target' => '_self' ) );

// fp> what browser do we need a fielset for?
echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

display_container( $WidgetContainer );

echo '</fieldset>'."\n";

// Display action buttons for widgets list:
echo '<div class="evo_customizer__buttons evo_customizer__buttons_widget_actions">';
display_widgets_action_buttons( $Form );
echo '</div>';

$Form->end_form();

// End of customizer content:
echo '</div>';
?>