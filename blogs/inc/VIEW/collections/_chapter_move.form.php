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
 * @var BlogCache
 */
global $BlogCache;

global $action, $subset_ID;

$Form = & new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel move!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Move category') );

$Form->hidden( 'action', 'update_move' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( T_('Properties') );

	$Form->info( T_('Name'), $edited_Chapter->name );

	// We're essentially double checking here...
	$edited_Blog = & $edited_Chapter->get_Blog();

	$Form->select_input_options( $edited_Chapter->dbprefix.'coll_ID', $BlogCache->get_option_list( $edited_Blog->ID ), T_('Attached to blog'), T_('If you select a new blog, you will be able to choose a position within this blog on the next screen.') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.3  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.2  2006/12/10 22:28:33  fplanque
 * improved moving chapters a little bit
 *
 * Revision 1.1  2006/12/09 17:59:34  fplanque
 * started "moving chapters accross blogs" feature
 *
 * Revision 1.5  2006/12/09 02:37:44  fplanque
 * Prevent user from creating loops in the chapter tree
 * (still needs a check before writing to DB though)
 *
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