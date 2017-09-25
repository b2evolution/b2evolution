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
	$Form->hidden( 'tab', get_param( 'tab' ) );

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

			// Site Skin:
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Site Skin').':</label>';
				echo '<span>'.( $edited_Skin->provides_site_skin() ? T_('Yes') : T_('No') ).'</span>';
			echo '</div>';

			// Collection Skin:
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Collection Skin').':</label>';
				echo '<span>'.( $edited_Skin->provides_collection_skin() ? T_('Yes') : T_('No') ).'</span>';
			echo '</div>';

			// Skin format:
			echo '<div class="skin_setting_row">';
				echo '<label>'.T_('Skin format').':</label>';
				echo '<span>'.$edited_Skin->type.'</span>';
			echo '</div>';

			// Containers
			if( $skin_containers = $edited_Skin->get_containers() )
			{
				$skin_containers_names = array();
				foreach( $skin_containers as $skin_container_data )
				{
					$skin_containers_names[] = $skin_container_data[0];
				}
				$container_ul = '<ul><li>'.implode( '</li><li>', $skin_containers_names ).'</li></ul>';
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

			$sql = 'SELECT cset_coll_ID, blog_name, cset_name,
						CASE cset_name
							WHEN "normal_skin_ID" THEN "'.T_('Normal').'"
							WHEN "mobile_skin_ID" THEN "'.T_('Mobile').'"
							WHEN "tablet_skin_ID" THEN "'.T_('Tablet').'"
							WHEN "rwd_skin_ID" THEN "'.T_('RWD').'"
							WHEN "feed_skin_ID" THEN "'.T_('XML Feed').'"
							WHEN "sitemap_skin_ID" THEN "'.T_('XML Sitemap').'"
							ELSE "'.T_('Unknown').'" END AS skin_type
					FROM T_coll_settings
					LEFT JOIN T_blogs ON blog_ID = cset_coll_ID
					WHERE cset_name LIKE "%_skin_ID"
						AND cset_value = '.$edited_Skin->ID;
			$Results = new Results( $sql, '', '', 1000 );
			$Results->title = T_('Used by').'...';
			$Results->cols[] = array(
				'th' => T_('Collection ID'),
				'td_class' => 'shrinkwrap',
				'td' => '$cset_coll_ID$',
			);

			function display_skin_setting_link( $row )
			{
				if( empty( $row->blog_name ) )
				{
					return;
				}
				$url_params = 'tab=skin&amp;blog='.$row->cset_coll_ID;

				if( in_array( $row->cset_name, array( 'mobile_skin_ID', 'tablet_skin_ID', 'rwd_skin_ID', 'feed_skin_ID', 'sitemap_skin_ID' )  ) )
				{
					$url_params .= '&amp;skin_type='.str_replace( '_skin_ID', '', $row->cset_name );
				}

				return '<a href="'.get_dispctrl_url( 'coll_settings', $url_params ).'">'.$row->blog_name.'</a>';
			}

			$Results->cols[] = array(
				'th' => T_('Collection name'),
				'td' => '%display_skin_setting_link( {row} )%',
			);

			$Results->cols[] = array(
				'th' => T_('Skin type'),
				'td' => '$skin_type$',
				'td_class' => 'text-center'
			);

			$Results->display();

		echo '</div>';

	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>