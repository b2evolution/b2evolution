<?php
/**
 * This file implements ther UI controler for chapters management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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

$current_User->check_perm( 'blog_cats', 'edit', true, $blog );



$BlogCache = & get_Cache( 'BlogCache' );
$edited_Blog = & $BlogCache->get_by_ID( $blog );
$Blog = & $edited_Blog; // used for "Exit to blogs.." link


$AdminUI->set_path( 'blogs', 'chapters' );


/**
 * Delete restrictions
 */
$delete_restrictions = array(
							array( 'table'=>'T_categories', 'fk'=>'cat_parent_ID', 'msg'=>T_('%d sub categories') ),
							array( 'table'=>'T_posts', 'fk'=>'post_main_cat_ID', 'msg'=>T_('%d posts within category through main cat') ),
							array( 'table'=>'T_postcats', 'fk'=>'postcat_cat_ID', 'msg'=>T_('%d posts within category through extra cat') ),
					);

$restrict_title = T_('Cannot delete category');	 //&laquo;%s&raquo;

// mb> Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

load_class( '/MODEL/collections/_chaptercache.class.php' );
$GenericCategoryCache = & new ChapterCache();


/**
 * Display page header, menus & messages:
 */
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_cats', '',
											'?ctrl='.$ctrl.'&amp;blog=%d',
											T_('List'), '?ctrl=collections&amp;blog=0' );

// Restrict to chapters of the specific blog:
$subset_ID = $blog;

$list_title = T_('Categories for blog:').' '.$Blog->dget('name');
$permission_to_edit = $current_User->check_perm( 'blog_cats', '', false, $blog );

// The form will be on its own page:
$form_below_list = false;
$edit_view_path = 'collections/_chapter.form.php';


require $control_path.'generic/inc/_generic_recursive_listeditor.php';

/*
 * $Log$
 * Revision 1.4  2006/09/10 17:33:02  fplanque
 * started to steam up the categories/chapters
 *
 */
?>