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

if( $mode == 'customizer' )
{	// Display customizer tabs to switch between skin and widgets in special div on customizer mode:
	$AdminUI->display_customizer_tabs( array(
			'path' => array( 'coll', 'widgets' ),
		) );

	// Start of customizer content:
	echo '<div class="evo_customizer__content">';

	$form_title = '';
}
else
{
	$form_title = $edited_WidgetContainer->get_type_title( get_param( 'container_type' ) );
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
$Form->hidden( 'wico_coll_ID', intval( $edited_WidgetContainer->get( 'coll_ID' ) ) );

$Form->begin_fieldset( T_('Container Properties') );

	$container_type = get_param( 'container_type' );
	if( $edited_WidgetContainer->ID > 0 || $container_type === NULL )
	{	// Use type of the edited container or if it is not defined for new creating container:
		$container_type = $edited_WidgetContainer->get_type();
	}

	$Form->hidden( 'container_type', $container_type );

	switch( $container_type )
	{
		case 'shared':
		case 'shared-sub':
			// Suggect to select container type only for shared containers:
			$Form->radio( 'wico_container_type',
					$edited_WidgetContainer->get( 'main' ) ? 'main' : 'sub',
					array(
							array( 'main', T_('Shared main container') ),
							array( 'sub',  T_('Shared sub-container') ),
						),
					T_( 'Container type' ), true, '', true
				);
			break;

		case 'page':
			// Selector for Page Container:
			$Form->output = false;
			$Form->switch_layout( 'none' );
			$ItemTypeCache = & get_ItemTypeCache();
			$ItemTypeCache->clear();
			$ItemTypeCache->load_where( 'ityp_page_container = 1' );
			$item_types = array( '' => T_('None') );
			foreach( $ItemTypeCache->cache as $ItemType )
			{
				$item_types[ $ItemType->ID ] = $ItemType->get_name();
			}
			$container_ityp_ID_select_input = $Form->select_input_array( 'container_ityp_ID', get_param( 'container_ityp_ID' ), $item_types, '', '', array( 'force_keys_as_values' => true ) );
			$wico_item_ID_text_input = $Form->text( 'wico_item_ID', $edited_WidgetContainer->get( 'item_ID' ), 5, '' );
			$Form->switch_layout( NULL );
			$Form->output = true;
			$container_page_type = get_param( 'container_page_type' );
			if( empty( $container_page_type ) && $edited_WidgetContainer->ID > 0 )
			{	// For editing of page container we should select this option by default:
				$container_page_type = 'item';
			}
			$Form->radio_input( 'container_page_type', $container_page_type, array(
					array(
						'value' => 'type',
						'label' => T_('For a new page of type').': '.$container_ityp_ID_select_input ),
					array(
						'value' => 'item',
						'label' => T_('For an existing page').': '.$wico_item_ID_text_input ),
				), T_('Page container type'), array( 'lines' => true, 'required' => true ) );
			break;
	}

	$Form->text_input( 'wico_name', $edited_WidgetContainer->get( 'name' ), 40, T_('Name'), '', array( 'required' => true, 'maxlength' => 40 ) );

	$Form->text_input( 'wico_code', $edited_WidgetContainer->get( 'code' ), 40, T_('Code'), T_('Used for calling from skins. Must be unique.'), array( 'required' => true, 'maxlength' => 32 ) );

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

	$Form->text_input( 'wico_order', $edited_WidgetContainer->get( 'order' ), 10, T_('Order'), T_('For manual ordering of the containers.'), array( 'required' => !$creating, 'maxlength' => 10 ) );

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