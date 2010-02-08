<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

/**
 * @var Blog
 */
global $Blog;


$Form = new Form( NULL, 'skin_settings_checkchanges' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'collection' );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', 'skin_settings' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'blog', $Blog->ID );

	$Form->begin_fieldset( T_('Current skin') );

		Skin::disp_skinshot( $edited_Skin->folder, $edited_Skin->name );

		$Form->info( T_('Skin name'), $edited_Skin->name );

		$Form->info( T_('Skin type'), $edited_Skin->type );

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

	$skin_params = $edited_Skin->get_param_definitions( $tmp_params = array('for_editing'=>true) );

	$Form->begin_fieldset( T_('Params') );

		if( empty($skin_params) )
		{	// Advertise this feature!!
			echo '<p>'.T_('This skin does not provide any configurable settings.').'</p>';
		}
		else
		{
			load_funcs( 'plugins/_plugin.funcs.php' );

			// Loop through all widget params:
			foreach( $skin_params as $l_name => $l_meta )
			{
				// Display field:
				autoform_display_field( $l_name, $l_meta, $Form, 'Skin', $edited_Skin );
			}
		}

	$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.6  2010/02/08 17:54:43  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.5  2010/01/13 22:48:57  fplanque
 * Missing crumbs
 *
 * Revision 1.4  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.3  2009/05/26 19:48:29  fplanque
 * Version bump.
 *
 * Revision 1.2  2009/05/26 18:42:51  sam2kb
 * Hide skin params fieldset if no custom params defined
 *
 * Revision 1.1  2009/05/23 22:49:10  fplanque
 * skin settings
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