<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Skin
 */
global $edited_Skin;


$Form = & new Form( NULL, 'skin_checkchanges' );

$Form->global_icon( T_('Uninstall this skin!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Skin properties') );

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

$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.6  2009/09/26 13:41:54  tblue246
 * If XML feeds are disabled for a blog, still allow accessing "sitemap" skins.
 *
 * Revision 1.5  2009/05/23 20:20:18  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 * Revision 1.4  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/03 20:07:50  blueyed
 * Fixed display of empty container lists in "Skins install" detail form
 *
 * Revision 1.1  2007/06/25 11:01:36  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.2  2007/01/07 23:38:20  fplanque
 * discovery of skin containers
 *
 * Revision 1.1  2007/01/07 05:32:11  fplanque
 * added some more DB skin handling (install+uninstall+edit properties ok)
 * still useless though :P
 * next step: discover containers in installed skins
 */
?>
