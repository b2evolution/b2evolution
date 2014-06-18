<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _skin.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Skin
 */
global $edited_Skin;


$Form = new Form( NULL, 'skin_checkchanges' );

$Form->global_icon( T_('Uninstall this skin!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('skin') ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Skin properties') );

	$Form->add_crumb( 'skin' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'skin_ID', $edited_Skin->ID );

	$Form->begin_fieldset( T_('Skin properties') );

		Skin::disp_skinshot( $edited_Skin->folder, $edited_Skin->name );

		$Form->text_input( 'skin_name', $edited_Skin->name, 32, T_('Skin name'), T_('As seen by blog owners'), array( 'required'=>true ) );

		$Form->radio( 'skin_type',
									$edited_Skin->type,
									 array(
													array( 'normal', T_( 'Normal' ), T_( 'Normal skin for general browsing' ) ),
													array( 'mobile', T_( 'Mobile' ), T_( 'Mobile skin for mobile phones browsers' ) ),
													array( 'tablet', T_( 'Tablet' ), T_( 'Tablet skin for tablet browsers' ) ),
													array( 'feed', T_( 'XML Feed' ), T_( 'Special system skin for XML feeds like RSS and Atom' ) ),
													array( 'sitemap', T_( 'XML Sitemap' ), T_( 'Special system skin for XML sitemaps' ) ),
												),
										T_( 'Skin type' ),
										true // separate lines
								 );

		if( $skin_containers = $edited_Skin->get_containers() )
		{
			$container_ul = '<ul><li>'.implode( '</li><li>', $skin_containers ).'</li></ul>';
		}
		else
		{
			$container_ul = '-';
		}
		$Form->info( T_('Containers'), $container_ul );

	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>