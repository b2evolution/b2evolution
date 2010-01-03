<?php
/**
 * This file implements the generic category form
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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

$Form = & new Form( NULL, 'form' );

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
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->hidden( $edited_GenericCategory->dbIDname, $edited_GenericCategory->ID );
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


/*
 * $Log$
 * Revision 1.4  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.3  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:18  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.4  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.3  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.2  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>