<?php
/**
 * This file implements the UI view to customize widgets and containers from front-office of the selected collection.
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

global $Collection, $Blog, $Skin, $admin_url, $AdminUI, $selected_WidgetContainer;

// Display customizer tabs to switch between skin and widgets in special div on customizer mode:
$AdminUI->display_customizer_tabs( array(
		'path' => array( 'coll', 'widgets' ),
	) );

// Start of customizer content:
echo '<div class="evo_customizer__content">';

$Form = new Form( NULL, 'widgets_checkchanges', 'post', 'accordion' );

$Form->begin_form();

$container_params = array(
		'table_layout'     => 'accordion_table',
		'group_id'         => 'evo_accordion_wico',
		'selected_wico_ID' => empty( $selected_WidgetContainer ) ? NULL : $selected_WidgetContainer->ID,
	);

// fp> what browser do we need a fielset for?
echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

$Form->begin_group( array( 'id' => $container_params['group_id'] ) );

// Display main containers:
display_containers( get_param( 'skin_type' ), true, $container_params );

// Display sub-containers:
display_containers( get_param( 'skin_type' ), false, $container_params );

// Display button/link to edit widgets in back-office:
echo '<div class="evo_customizer__buttons evo_customizer__buttons_widget_actions">';
echo '<a href="'.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;skin_type='.get_param( 'skin_type' ).'" class="btn btn-default" target="_parent">'.T_('Go to Back-office').'</a>';
echo '</div>';

$Form->end_group();

echo '</fieldset>'."\n";

$Form->end_form();

// End of customizer content:
echo '</div>';
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '.panel-collapse:visible:first' ).each( function()
	{	// Scroll to first opened widget container:
		jQuery( 'body' ).scrollTop( jQuery( this ).parent().position().top );
	} );
} );
</script>