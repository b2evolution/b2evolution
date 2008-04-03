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
 * Default controller to use.
 *
 * This determines the default page when you access the admin.
 */
$default_ctrl = 'dashboard';


/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings = array(
		'antispam'     => 'antispam/antispam_list.ctrl.php',
		'chapters'     => 'chapters/chapters.ctrl.php',
		'collections'  => 'collections/collections.ctrl.php',
		'coll_settings'=> 'collections/coll_settings.ctrl.php',
		'comments'     => 'comments/_comments.ctrl.php',
		'crontab'      => 'cron/cronjobs.ctrl.php',
		'dashboard'    => 'dashboard/dashboard.ctrl.php',
		'features'     => 'settings/features.ctrl.php',
		'files'        => 'files/files.ctrl.php',
		'fileset'      => 'files/file_settings.ctrl.php',
		'filetypes'    => 'files/file_types.ctrl.php',
		'items'        => 'items/items.ctrl.php',
		'itemstatuses' => 'items/item_statuses.ctrl.php',
		'itemtypes'    => 'items/item_types.ctrl.php',
		'locales'      => 'locales/locales.ctrl.php',
		'mtimport'     => 'tools/mtimport.ctrl.php',
		'plugins'      => 'plugins/plugins.ctrl.php',
		'settings'     => 'settings/settings.ctrl.php',
		'set_antispam' => 'antispam/antispam_settings.ctrl.php',
		'skins'        => 'skins/skins.ctrl.php',
		'stats'        => 'sessions/stats.ctrl.php',
		'system'       => 'tools/system.ctrl.php',
		'tools'        => 'tools/tools.ctrl.php',
		'users'        => 'users/users.ctrl.php',
		'upload'       => 'files/upload.ctrl.php',
		'widgets'      => 'widgets/widgets.ctrl.php',
		'wpimport'     => 'tools/wpimport.ctrl.php',
	);


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
 * Default status for new posts:
 *
 * Possible values: 'published', 'deprecated', 'protected', 'private', 'draft', 'redirected'
 *
 * @todo fp>This should be moved to the backoffice. Select list for each blog.
 *
 * @global string $default_post_status
 */
$default_post_status = 'draft';


/**
 * set this to 1 if you want to use the 'preview' function
 *
 * @todo fp>This should be moved to the backoffice. Checbox for each blog (features). Useful when a blog has no public skin. (Tracker)
 *
 * @global boolean $use_preview
 */
$use_preview = 1;


/**
 * Do you want to be able to link each post to an URL ?
 *
 * @todo fp>This should be moved to the backoffice. Checbox for each blog (features).
 *
 * @global boolean $use_post_url
 */
$use_post_url = 1;  // 1 to enable, 0 to disable


/**
 * When banning, do you want to be able to report abuse to the
 * centralized ban list at b2evolution.net?
 *
 * @global boolean $report_abuse
 */
$report_abuse = 1;

?>