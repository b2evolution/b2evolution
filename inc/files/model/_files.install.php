<?php
/**
 * This is the install file for the files module
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
$schema_queries['T_files'] = array(
		'Creating table for File Meta Data',
		"CREATE TABLE T_files (
			file_ID                  int(11) unsigned  not null AUTO_INCREMENT,
			file_type                enum('image', 'audio', 'other') COLLATE ascii_general_ci NULL DEFAULT NULL,
			file_root_type           enum('absolute','user','collection','shared','skins','import') COLLATE ascii_general_ci NOT NULL DEFAULT 'absolute',
			file_root_ID             int(11) unsigned not null default 0,
			file_path                varchar(767) not null default '',
			file_title               varchar(255),
			file_alt                 varchar(255),
			file_desc                text,
			file_hash                binary(16) default NULL,
			file_path_hash           binary(16) default NULL,
			file_can_be_main_profile TINYINT(1) NOT NULL DEFAULT 1,
			primary key (file_ID),
			unique file_path (file_path_hash)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_filetypes'] = array(
		'Creating table for file types',
		"CREATE TABLE T_filetypes (
			ftyp_ID int(11) unsigned NOT NULL auto_increment,
			ftyp_extensions varchar(30) COLLATE ascii_general_ci NOT NULL,
			ftyp_name varchar(30) NOT NULL,
			ftyp_mimetype varchar(50) NOT NULL,
			ftyp_icon varchar(20) default NULL,
			ftyp_viewtype varchar(10) COLLATE ascii_general_ci NOT NULL,
			ftyp_allowed enum('any','registered','admin') COLLATE ascii_general_ci NOT NULL default 'admin',
			PRIMARY KEY (ftyp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

?>