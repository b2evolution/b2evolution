<?php
/**
 * This file implements the generic category form
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _generic_category.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var GenericCategory
 */
global $edited_GenericCategory;

/**
 * @var GenericCategoryCache
 */
global $GenericCategoryCache;

global $action, $subset_ID, $edited_name_maxlen;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New category') : T_('Category') );

	$Form->add_crumb( 'element' );
	$Form->hidden( 'action', $creating ? 'create' : 'update' );
	$Form->hidden( 'ctrl', $ctrl );
	$Form->hiddens_by_key( get_memorized( 'action, ctrl' ) );

$Form->begin_fieldset( T_('Properties') );

	$Form->select_input_options( $edited_GenericCategory->dbprefix.'parent_ID',
				$GenericCategoryCache->recurse_select( $edited_GenericCategory->parent_ID, $subset_ID, true ), T_('Parent') );

	$Form->text_input( $edited_GenericCategory->dbprefix.'name', $edited_GenericCategory->name, $edited_name_maxlen, T_('name'), '', array( 'required' => true ) );

$Form->end_fieldset();

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ) ) );
}
else
{
	$Form->hidden( $edited_GenericCategory->dbIDname, $edited_GenericCategory->ID );
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>