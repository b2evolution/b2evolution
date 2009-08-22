<?php
/**
 * This is the admin config file
 *
 * This sets how the back-office/admin interface works
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Cross posting
 *
 * Possible values:
 *   - -1 if you don't want to use categories at all
 *   - 0 if you want users to post to a single category only
 *   - 1 if you want to be able to cross-post among multiple categories
 *   - 2 if you want to be able to cross-post among multiple blogs/categories
 *   - 3 if you want to be able to change main cat among blogs (which will move the
 *       posts from one blog to another; use with caution)
 *
 * @todo fp>This should be moved to the backoffice.
 * In the BO, this should actually be split into:
 * App Settings:
 *  checkbox         [] allow cross posting
 *  another checkbox [] allow moving posting between different blogs
 * Each blog's settings: radio between:
 *    o One category per post
 *    o Multiple categories per post (requires transparent handling of main cat)
 *    o Main cat + extra cats
 *    o Don't use categories  (this requires to transparently manage a default category)
 *
 * @global int $allow_cross_posting
 */
$allow_cross_posting = 1;


/**
 * Locked post type IDs.
 *
 * These post types can't be edited or deleted in the post type editor.
 * They're needed by certain b2evolution features, so
 * don't remove any IDs from this array.
 *
 * @global array $posttypes_locked_IDs
 */
$posttypes_locked_IDs = array( 1000, 1500, 1520, 1530, 1570, 1600, 2000, 3000 );

/**
 * Reserved post type IDs.
 *
 * These post types are reserved for future use and can't be edited or
 * deleted in the post type editor. It also is not possible to select
 * them when creating a new post.
 * Do not remove any IDs from this array.
 *
 * @global array $posttypes_reserved_IDs
 */
$posttypes_reserved_IDs = array( 4000, 5000 );

/**
 * Post types that can be restricted on a per-blog basis using the blog
 * user/group permissions.
 *
 * The key of each array element is the part of the corresponding permission
 * name without the "blog_" prefix (e. g. a value of 'page' means the
 * permission 'blog_page').
 * The value of each array element is an array containing the post type
 * IDs whose usage is controlled by the respective permission.
 *
 * @see check_perm_posttype()
 * @see ItemTypeCache::get_option_list_usable_only()
 *
 * @global array $posttypes_perms
 */
$posttypes_perms = array(
	'page' => array(
		1000,
	),
	'intro' => array(
		1500,
		1520,
		1530,
		1570,
		1600,
	),
	'podcast' => array(
		2000,
	),
	'sidebar' => array(
		3000,
	),
);

?>
