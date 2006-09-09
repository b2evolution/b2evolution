<?php
/**
 * This file implements ther UI controler for chapters management.
 */

$current_User->check_perm( 'blog_cats', 'edit', true, $blog );



$BlogCache = & get_Cache( 'BlogCache' );
$edited_Blog = & $BlogCache->get_by_ID( $blog );
$Blog = & $edited_Blog; // used for "Exit to blogs.." link


$AdminUI->set_path( 'blogs', 'chapters' );

 param( 'action', 'string', 'list' );

$list_title = T_('Categories for blog:').' '.$Blog->dget('name');
$default_col_order = '-A';
$edited_name_maxlen = 40;
$perm_name = 'prod';
$perm_level = 'edit';

// The form will be on its own page:
$form_below_list = false;

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
load_class( '/MODEL/generic/_genericcategory.class.php' );
$GenericElementCache = & new ChapterCache();


/**
 * Display page header, menus & messages:
 */
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_cats', '',
											'?ctrl='.$ctrl.'&amp;blog=%d',
											T_('List'), '?ctrl=collections&amp;blog=0' );

// Restrict to chapters of the specific blog:
$subset_ID = $blog;

require $control_path.'generic/inc/_generic_recursive_listeditor.php';
?>