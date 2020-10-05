<?php
/**
 * This is the install file for the menus module
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


global $db_storage_charset;


/**
 * The b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * Please see {@link db_delta()} for things to take care of.
 */
$schema_queries = array_merge( $schema_queries, array(
	'T_menus__menu' => array(
		'Creating table for Menus',
		"CREATE TABLE T_menus__menu (
			menu_ID     INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			menu_translates_menu_ID INT(10) UNSIGNED NULL DEFAULT NULL,
			menu_name   VARCHAR(128) COLLATE utf8mb4_unicode_ci NOT NULL,
			menu_locale VARCHAR(20) COLLATE ascii_general_ci NOT NULL DEFAULT 'en-US',
			PRIMARY KEY (menu_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_menus__entry' => array(
		'Creating table for Menu entries',
		"CREATE TABLE T_menus__entry (
			ment_ID             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			ment_menu_ID        INT(10) UNSIGNED NOT NULL,
			ment_parent_ID      INT(10) UNSIGNED NULL,
			ment_order          INT(11) NULL,
			ment_user_pic_size  VARCHAR(32) COLLATE ascii_general_ci NULL,
			ment_text           VARCHAR(128) COLLATE utf8mb4_unicode_ci NULL,
			ment_type           VARCHAR(32) COLLATE ascii_general_ci NULL,
			ment_coll_logo_size VARCHAR(32) COLLATE ascii_general_ci NULL,
			ment_coll_ID        INT(10) UNSIGNED NULL,
			ment_cat_ID         INT(10) UNSIGNED NULL,
			ment_item_ID        INT(10) UNSIGNED NULL,
			ment_item_slug      VARCHAR(255) COLLATE ascii_general_ci NULL,
			ment_url            VARCHAR(2000) COLLATE utf8mb4_unicode_ci NULL,
			ment_visibility     ENUM( 'always', 'access' ) COLLATE ascii_general_ci NOT NULL DEFAULT 'always',
			ment_access         ENUM( 'any', 'loggedin', 'perms' ) COLLATE ascii_general_ci NOT NULL DEFAULT 'perms',
			ment_show_badge     TINYINT(1) NOT NULL DEFAULT 1,
			ment_highlight      TINYINT(1) NOT NULL DEFAULT 1,
			ment_hide_empty     TINYINT(1) NOT NULL DEFAULT 0,
			ment_class          VARCHAR(128) COLLATE ascii_general_ci NULL,
			PRIMARY KEY          (ment_ID),
			INDEX ment_menu_ID   (ment_menu_ID),
			INDEX ment_parent_ID (ment_parent_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),
) );

?>
