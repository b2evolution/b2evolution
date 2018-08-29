<?php
/**
 * This file implements the UI for the widgets container create/edit form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id: _widget_container.form.php 10060 2016-03-09 10:40:31Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var WidgetContainer
 */
global $edited_WidgetContainer, $Blog, $AdminUI, $mode;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$form_title = $creating ?  T_('New container') : T_('Container');

if( $mode == 'customizer' )
{	// Display customizer tabs to switch between skin and widgets in special div on customizer mode:
	$AdminUI->display_customizer_tabs( array(
			'path' => array( 'coll', 'widgets' ),
		) );

	// Start of customizer content:
	echo '<div class="evo_customizer__content">';

	$form_title = '';
}

$Form = new Form( NULL, 'form' );

if( $mode != 'customizer' )
{
	$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );
}

$Form->begin_form( 'fform', $form_title );

$Form->add_crumb( 'widget_container' );
$Form->hidden( 'action', $creating ? 'create_container' : 'update_container' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( T_('Container Properties') );

	$Form->text_input( 'wico_name', $edited_WidgetContainer->get( 'name' ), 40, T_('Name'), '', array( 'required' => true, 'maxlength' => 255 ) );

	$Form->text_input( 'wico_code', $edited_WidgetContainer->get( 'code' ), 40, T_('Code'), T_('Used for calling from skins. Must be unique.'), array( 'required' => true, 'maxlength' => 255 ) );

	if( $edited_WidgetContainer->ID == 0 )
	{	// Allow to set skin type only on creating new widget container:
		$Form->radio( 'wico_skin_type',
				$edited_WidgetContainer->get( 'skin_type' ),
				array(
						array( 'normal', T_('Standard'), T_('Standard skin for general browsing') ),
						array( 'mobile', T_('Phone'), T_('Mobile skin for mobile phones browsers') ),
						array( 'tablet', T_('Tablet'), T_('Tablet skin for tablet browsers') ),
					),
				T_( 'Skin type' ), true, '', true
			);
	}

	$Form->text_input( 'wico_order', $edited_WidgetContainer->get( 'order' ), 40, T_('Order'), T_('For manual ordering of the containers.'), array( 'required' => !$creating, 'maxlength' => 255 ) );

$Form->end_fieldset();

$buttons = array();
$buttons[] = array( 'submit', 'save', ( $creating ? T_('Record') : ( $mode == 'customizer' ? T_('Apply Changes!') : T_('Save Changes!') ) ), 'SaveButton' );
if( $mode == 'customizer' )
{	// Display buttons in special div on customizer mode:
	echo '<div class="evo_customizer__buttons">';
	$Form->buttons( $buttons );
	echo '</div>';
	// Clear buttons to don't display them twice:
	$buttons = array();
}

$Form->end_form( $buttons );

if( $mode == 'customizer' )
{	// End of customizer content:
	echo '</div>';
}
?>