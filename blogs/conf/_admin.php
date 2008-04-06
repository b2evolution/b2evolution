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
 * set this to 1 if you want to use the 'preview' function
 *
 * @todo fp>This should be moved to the backoffice. Checbox for each blog (features). Useful when a blog has no public skin. (Tracker)
 *
 * @global boolean $use_preview
 */
$use_preview = 1;
?>