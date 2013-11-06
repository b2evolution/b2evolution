<?php
/**
 * This is the install file for the files module
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @version $Id$
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
			file_ID        int(11) unsigned  not null AUTO_INCREMENT,
			file_root_type enum('absolute','user','collection','shared','skins') not null default 'absolute',
			file_root_ID   int(11) unsigned  not null default 0,
			file_path      varchar(255)      not null default '',
			file_title     varchar(255),
			file_alt       varchar(255),
			file_desc      text,
			file_hash      char(32) default NULL,
			primary key (file_ID),
			unique file (file_root_type, file_root_ID, file_path)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_filetypes'] = array(
		'Creating table for file types',
		"CREATE TABLE T_filetypes (
			ftyp_ID int(11) unsigned NOT NULL auto_increment,
			ftyp_extensions varchar(30) NOT NULL,
			ftyp_name varchar(30) NOT NULL,
			ftyp_mimetype varchar(50) NOT NULL,
			ftyp_icon varchar(20) default NULL,
			ftyp_viewtype varchar(10) NOT NULL,
			ftyp_allowed enum('any','registered','admin') NOT NULL default 'admin',
			PRIMARY KEY (ftyp_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );

$schema_queries['T_files__vote'] = array(
		'Creating table for file votes',
		"CREATE TABLE T_files__vote (
			fvot_file_ID       int(11) UNSIGNED NOT NULL,
			fvot_user_ID       int(11) UNSIGNED NOT NULL,
			fvot_like          tinyint(1),
			fvot_inappropriate tinyint(1),
			fvot_spam          tinyint(1),
			primary key (fvot_file_ID, fvot_user_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );


/*
 * $Log$
 * Revision 1.9  2013/11/06 08:04:08  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>