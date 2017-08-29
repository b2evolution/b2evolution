<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Skin
 */
global $edited_Skin;


$Form = new Form( NULL, 'skin_checkchanges' );

$Form->global_icon( T_('Uninstall this skin!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('skin') ) );
$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Skin properties') );

	$Form->add_crumb( 'skin' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'skin_ID', $edited_Skin->ID );

	$Form->begin_fieldset( T_('Skin properties').get_manual_link( 'skin-system-settings' ) );

		echo '<div class="skin_settings well">';
			$disp_params = array( 'skinshot_class' => 'coll_settings_skinshot' );
			Skin::disp_skinshot( $edited_Skin->folder, $edited_Skin->name, $disp_params );

			// Skin name
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Skin name').':</label>';
				echo '<span>'.$edited_Skin->name.'</span>';
			echo '</div>';


			// Skin version
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Skin version').':</label>';
				echo '<span>'.( isset( $edited_Skin->version ) ? $edited_Skin->version : 'unknown' ).'</span>';
			echo '</div>';

			// Skin type
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Skin type').':</label>';
				echo '<span>'.$edited_Skin->type.'</span>';
			echo '</div>';

			// Containers
			if( $skin_containers = $edited_Skin->get_containers() )
			{
				$container_ul = '<ul><li>'.implode( '</li><li>', $skin_containers ).'</li></ul>';
			}
			else
			{
				$container_ul = '-';
			}
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Containers').':</label>';
				echo '<span>'.$container_ul.'</span>';
			echo '</div>';

		echo '</div>';
		echo '<div class="skin_settings_form">';
			$Form->begin_fieldset( T_('System Settings for this skin').get_manual_link( 'skin-system-settings' ) );

			$Form->text_input( 'skin_name', $edited_Skin->name, 32, T_('Skin name'), T_('As seen by blog owners'), array( 'required'=>true ) );

			$Form->radio( 'skin_type',
										$edited_Skin->type,
										 array(
														array( 'normal', T_( 'Normal' ), T_( 'Normal skin for general browsing' ) ),
														array( 'mobile', T_( 'Mobile' ), T_( 'Mobile skin for mobile phones browsers' ) ),
														array( 'tablet', T_( 'Tablet' ), T_( 'Tablet skin for tablet browsers' ) ),
														array( 'rwd', T_( 'RWD' ), T_( 'Skin can be used for general, mobile phones and tablet browsers' ) ),
														array( 'feed', T_( 'XML Feed' ), T_( 'Special system skin for XML feeds like RSS and Atom' ) ),
														array( 'sitemap', T_( 'XML Sitemap' ), T_( 'Special system skin for XML sitemaps' ) ),
													),
											T_( 'Skin type' ),
											true // separate lines
									 );
			$Form->end_fieldset();

		echo '</div>';

	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>