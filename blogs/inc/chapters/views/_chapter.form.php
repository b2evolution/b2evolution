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
 * @version $Id: _chapter.form.php 6135 2014-03-08 07:54:05Z manuel $
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

global $Settings, $action, $subset_ID;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'form' );

$close_url = get_chapter_redirect_url( get_param( 'redirect_page' ), $edited_Chapter->parent_ID, $edited_Chapter->ID );
$Form->global_icon( T_('Cancel editing!'), 'close', $close_url );

$Form->begin_form( 'fform', $creating ?  T_('New category') : T_('Category') );

$Form->add_crumb( 'element' );
$Form->hidden( 'action', $creating ? 'create' : 'update' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( T_('Properties') );

	// We're essentially double checking here...
	$edited_Blog = & $edited_Chapter->get_Blog();
	$move = '';
	if( $Settings->get('allow_moving_chapters') && ( ! $creating ) )
	{ // If moving cats between blogs is allowed:
		$move = ' '.action_icon( T_('Move to a different blog...'), 'file_move', regenerate_url( 'action,cat_ID', 'cat_ID='.$edited_Chapter->ID.'&amp;action=move' ), T_('Move') );
	}
	$Form->info( T_('Blog'), $edited_Blog->get_maxlen_name().$move );

	$Form->select_input_options( 'cat_parent_ID',
				$GenericCategoryCache->recurse_select( $edited_Chapter->parent_ID, $subset_ID, true, NULL, 0, array($edited_Chapter->ID) ), T_('Parent category') );

	$Form->text_input( 'cat_name', $edited_Chapter->name, 40, T_('Name'), '', array( 'required' => true, 'maxlength' => 255 ) );

	$Form->text_input( 'cat_urlname', $edited_Chapter->urlname, 40, T_('URL "slug"'), T_('Used for clean URLs. Must be unique.'), array( 'maxlength' => 255 ) );

	$Form->text_input( 'cat_description', $edited_Chapter->description, 40, T_('Description'), T_('May be used as a title tag and/or meta description.'), array( 'maxlength' => 255 ) );

	if( $Settings->get('chapter_ordering') == 'manual' )
	{
		$Form->text_input( 'cat_order', $edited_Chapter->order, 5, T_('Order'), T_('For manual ordering of the categories.'), array( 'maxlength' => 11 ) );
	}

	$Form->checkbox_input( 'cat_meta', $edited_Chapter->meta, T_('Meta category'), array( 'note' => T_('If you check this box you will not be able to put any posts into this category.') ) );

	$Form->checkbox_input( 'cat_lock', $edited_Chapter->lock, T_('Locked category'), array( 'note' => T_('Check this to lock all posts under this category. (Note: for posts with multiple categories, the post is only locked if *all* its categories are locked.)') ) );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );

?>