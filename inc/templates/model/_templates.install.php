<?php
/**
 * This is the install file for the templates module
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
	'T_templates' => array(
		'Creating table for Templates',
		"CREATE TABLE T_templates (
			tpl_ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			tpl_name VARCHAR(128) COLLATE utf8mb4_unicode_ci NOT NULL,
			tpl_code VARCHAR(128) COLLATE ascii_general_ci NULL DEFAULT NULL,
			tpl_parent_tpl_ID INT(10) UNSIGNED NULL DEFAULT NULL,
			tpl_locale VARCHAR(20) COLLATE ascii_general_ci NOT NULL DEFAULT 'en-US',
			tpl_template_code MEDIUMTEXT NULL,
			PRIMARY KEY (tpl_ID),
			UNIQUE tpl_code( tpl_code )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),
) );

?>
