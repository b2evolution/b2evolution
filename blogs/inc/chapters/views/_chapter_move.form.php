<?php
/**
 * This file implements the Chapter form
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
 * @version $Id: _chapter_move.form.php 6135 2014-03-08 07:54:05Z manuel $
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

global $action;

$Form = new Form( NULL, 'form' );

$Form->global_icon( T_('Cancel move!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('Move category') );

$Form->add_crumb( 'element' );
$Form->hidden( 'action', 'update_move' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( T_('Properties') );

	$Form->info( T_('Name'), $edited_Chapter->name );

	// We're essentially double checking here...
	$edited_Blog = & $edited_Chapter->get_Blog();

	$Form->select_input_options( $edited_Chapter->dbprefix.'coll_ID', $BlogCache->get_option_list( $edited_Blog->ID ), T_('Attached to blog'), T_('If you select a new blog, you will be able to choose a position within this blog on the next screen.') );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>