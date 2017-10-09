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
global $edited_WidgetContainer, $Blog;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

if( $edited_WidgetContainer->get( 'coll_ID' ) > 0 )
{	// Collection/skin container:
	if( $edited_WidgetContainer->get( 'main' ) )
	{	// Main container:
		$form_title = T_('Skin container');
	}
	else
	{	// Sub container:
		$form_title = $creating ? T_('New sub-container') : T_('Sub-container');
	}
}
else
{	// Shared container:
	if( $edited_WidgetContainer->get( 'main' ) )
	{	// Shared Main container:
		$form_title = $creating ? T_('New shared container') : T_('Shared container');
	}
	else
	{	// Shared sub-container:
		$form_title = $creating ? T_('New shared sub-container') : T_('Shared sub-container');
	}
}
$Form->begin_form( 'fform', $form_title );

$Form->add_crumb( 'widget_container' );
$Form->hidden( 'action', $creating ? 'create_container' : 'update_container' );
$Form->hiddens_by_key( get_memorized( 'action' ) );
$Form->hidden( 'wico_coll_ID', intval( $edited_WidgetContainer->get( 'coll_ID' ) ) );

$Form->begin_fieldset( T_('Properties') );

	if( $edited_WidgetContainer->get( 'coll_ID' ) == 0 )
	{	// Suggect to select container type only for shared containers:
		$Form->radio( 'wico_container_type',
				$edited_WidgetContainer->get( 'main' ) ? 'main' : 'sub',
				array(
						array( 'main', T_('Shared main container') ),
						array( 'sub',  T_('Shared sub-container') ),
					),
				T_( 'Container type' ), true, '', true
			);
	}

	$Form->text_input( 'wico_name', $edited_WidgetContainer->get( 'name' ), 40, T_('Name'), '', array( 'required' => true, 'maxlength' => 255 ) );

	$Form->text_input( 'wico_code', $edited_WidgetContainer->get( 'code' ), 40, T_('Code'), T_('Used for calling from skins. Must be unique.'), array( 'required' => true, 'maxlength' => 255 ) );

	$Form->radio( 'wico_skin_type',
			$edited_WidgetContainer->get( 'skin_type' ),
			array(
					array( 'normal', T_('Normal'), T_('Normal skin for general browsing') ),
					array( 'mobile', T_('Mobile'), T_('Mobile skin for mobile phones browsers') ),
					array( 'tablet', T_('Tablet'), T_('Tablet skin for tablet browsers') ),
				),
			T_( 'Skin type' ), true, '', true
		);

	$Form->text_input( 'wico_order', $edited_WidgetContainer->get( 'order' ), 40, T_('Order'), T_('For manual ordering of the containers,'), array( 'required' => !$creating, 'maxlength' => 255 ) );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );

?>