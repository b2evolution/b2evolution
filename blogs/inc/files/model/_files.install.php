<?php
/**
 * This is the install file for the files module
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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


/*
 * $Log$
 * Revision 1.7  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.6  2011/03/10 14:54:18  efy-asimo
 * Allow file types modification & add m4v file type
 *
 * Revision 1.5  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.4.2.3  2010/10/19 01:04:48  fplanque
 * doc
 *
 * Revision 1.3  2009/08/30 12:31:44  tblue246
 * Fixed CVS keywords
 *
 * Revision 1.1  2009/08/30 00:30:52  fplanque
 * increased modularity
 *
 */
?>
