<?php
/**
 * This file implements the Chapter form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @var Chapter
 */
global $edited_GenericCategory;
/**
 * @var Chapter
 */
$edited_Chapter = & $edited_GenericCategory;

/**
 * @var GenericCategoryCache
 */
global $GenericCategoryCache;

global $action, $subset_ID;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = & new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New chapter') : T_('Chapter') );

$Form->hidden( 'action', $creating ? 'create' : 'update' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( T_('Properties') );

	// We're essentially double checking here...
	$edited_Blog = & $edited_Chapter->get_Blog();
	$Form->info( T_('Blog'), $edited_Blog->dget('name') );

	$Form->select_input_options( $edited_Chapter->dbprefix.'parent_ID',
				$GenericCategoryCache->recurse_select( $edited_Chapter->parent_ID, $subset_ID, true ), T_('Parent') );

	$Form->text_input( $edited_Chapter->dbprefix.'name', $edited_Chapter->name, 40, T_('Name'), '', array( 'required' => true, 'maxlength' => 255 ) );

	$Form->text_input( $edited_Chapter->dbprefix.'urlname', $edited_Chapter->urlname, 40, T_('URL name'), '', array( 'required' => true, 'maxlength' => 255,
														'note' => T_('Used for clean URLs. Must be unique.') ) );

$Form->end_fieldset();

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


/*
 * $Log$
 * Revision 1.4  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.3  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.2  2006/09/10 19:32:32  fplanque
 * completed chapter URL name editing
 *
 * Revision 1.1  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 */
?>