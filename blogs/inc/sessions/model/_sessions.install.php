<?php
/**
 * This is the install file for the core modules
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
$schema_queries['T_sessions'] = array(
		'Creating table for active sessions',
		"CREATE TABLE T_sessions (
			sess_ID        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			sess_key       CHAR(32) NULL,
			sess_hitcount  INT(10) UNSIGNED NOT NULL DEFAULT 1,
			sess_lastseen  DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			sess_ipaddress VARCHAR(39) NOT NULL DEFAULT '',
			sess_user_ID   INT(10) DEFAULT NULL,
			sess_data      MEDIUMBLOB DEFAULT NULL,
			PRIMARY KEY      ( sess_ID ),
		  KEY sess_user_ID (sess_user_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );
		// NOTE: sess_lastseen is only relevant/used by Sessions class (+ stats) and results in a quite large index (file size wise)
		// NOTE: sess_data is (MEDIUM)BLOB because e.g. serialize() does not completely convert binary data to text

$schema_queries['T_basedomains'] = array(
		'Creating table for base domains',
		"CREATE TABLE T_basedomains (
			dom_ID     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			dom_name   VARCHAR(250) NOT NULL DEFAULT '',
			dom_status ENUM('unknown','whitelist','blacklist') NOT NULL DEFAULT 'unknown',
			dom_type   ENUM('unknown','normal','searcheng','aggregator') NOT NULL DEFAULT 'unknown',
			PRIMARY KEY     (dom_ID),
			UNIQUE dom_name (dom_name),
			INDEX dom_type  (dom_type)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );

$schema_queries['T_track__keyphrase'] = array(
		'Creating table for Hit-Logs',
		"CREATE TABLE T_track__keyphrase (
			keyp_ID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
			keyp_phrase  VARCHAR( 255 ) NOT NULL,
			PRIMARY KEY        ( keyp_ID ),
			UNIQUE keyp_phrase ( keyp_phrase )
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );


$schema_queries['T_hitlog'] = array(
		'Creating table for Hit-Logs',
		"CREATE TABLE T_hitlog (
			hit_ID                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			hit_sess_ID           INT UNSIGNED,
			hit_datetime          DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			hit_uri               VARCHAR(250) DEFAULT NULL,
			hit_referer_type      ENUM('search','blacklist','spam','referer','direct','self','admin') NOT NULL,
			hit_referer           VARCHAR(250) DEFAULT NULL,
			hit_referer_dom_ID    INT UNSIGNED DEFAULT NULL,
			hit_keyphrase_keyp_ID INT UNSIGNED DEFAULT NULL,
			hit_serprank					INT UNSIGNED DEFAULT NULL,
			hit_blog_ID           int(11) UNSIGNED NULL DEFAULT NULL,
			hit_remote_addr       VARCHAR(40) DEFAULT NULL,
			hit_agent_type				ENUM('rss','robot','browser','unknown') DEFAULT 'unknown' NOT NULL,
			PRIMARY KEY              (hit_<?php
/**
 * This is the install file for the core modules
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
$schema_queries['T_sessions'] = array(
		'Creating table for active sessions',
		"CREATE TABLE T_sessions (
			sess_ID        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			sess_key       CHAR(32) NULL,
			sess_hitcount  INT(10) UNSIGNED NOT NULL DEFAULT 1,
			sess_lastseen  DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			sess_ipaddress VARCHAR(39) NOT NULL DEFAULT '',
			sess_user_ID   INT(10) DEFAULT NULL,
			sess_data      MEDIUMBLOB DEFAULT NULL,
			PRIMARY KEY      ( sess_ID ),
		  KEY sess_user_ID (sess_user_ID)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );
		// NOTE: sess_lastseen is only relevant/used by Sessions class (+ stats) and results in a quite large index (file size wise)
		// NOTE: sess_data is (MEDIUM)BLOB because e.g. serialize() does not completely convert binary data to text

$schema_queries['T_basedomains'] = array(
		'Creating table for base domains',
		"CREATE TABLE T_basedomains (
			dom_ID     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			dom_name   VARCHAR(250) NOT NULL DEFAULT '',
			dom_status ENUM('unknown','whitelist','blacklist') NOT NULL DEFAULT 'unknown',
			dom_type   ENUM('unknown','normal','searcheng','aggregator') NOT NULL DEFAULT 'unknown',
			PRIMARY KEY     (dom_ID),
			UNIQUE dom_name (dom_name),
			INDEX dom_type  (dom_type)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );

$schema_queries['T_track__keyphrase'] = array(
		'Creating table for Hit-Logs',
		"CREATE TABLE T_track__keyphrase (
			keyp_ID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
			keyp_phrase  VARCHAR( 255 ) NOT NULL,
			PRIMARY KEY        ( keyp_ID ),
			UNIQUE keyp_phrase ( keyp_phrase )
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );


$schema_queries['T_hitlog'] = array(
		'Creating table for Hit-Logs',
		"CREATE TABLE T_hitlog (
			hit_ID                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			hit_sess_ID           INT UNSIGNED,
			hit_datetime          DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00',
			hit_uri               VARCHAR(250) DEFAULT NULL,
			hit_referer_type      ENUM('search','blacklist','spam','referer','direct','self','admin') NOT NULL,
			hit_referer           VARCHAR(250) DEFAULT NULL,
			hit_referer_dom_ID    INT UNSIGNED DEFAULT NULL,
			hit_keyphrase_keyp_ID INT UNSIGNED DEFAULT NULL,
			hit_serprank					INT UNSIGNED DEFAULT NULL,
			hit_blog_ID           int(11) UNSIGNED NULL DEFAULT NULL,
			hit_remote_addr       VARCHAR(40) DEFAULT NULL,
			hit_agent_type				ENUM('rss','robot','browser','unknown') DEFAULT 'unknown' NOT NULL,
			PRIMARY KEY              (hit_ll the hit data but only CONSOLIDATED data
		//     needed for reports, e-g: this date = this many hits of type browser/robot/rss etc but not necessarilly the detail
		//     MAYBE a 2 step process would make sense?
		//      1) write to MyISAM and cron every x minutes to replicate to indexed table
		//      2) consolidate once a day

$schema_queries['T_track__goal'] = array(
		'Creating goals table',
		"CREATE TABLE T_track__goal(
		  goal_ID int(10) unsigned NOT NULL auto_increment,
		  goal_name varchar(50) default NULL,
		  goal_key varchar(32) default NULL,
		  goal_redir_url varchar(255) default NULL,
		  goal_default_value double default NULL,
		  PRIMARY KEY (goal_ID),
		  UNIQUE KEY goal_key (goal_key)
		) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );

$schema_queries['T_track__goalhit'] = array(
		'Creating goal hits table',
		"CREATE TABLE T_track__goalhit (
		  ghit_ID int(10) unsigned NOT NULL auto_increment,
		  ghit_goal_ID    int(10) unsigned NOT NULL,
		  ghit_hit_ID     int(10) unsigned NOT NULL,
		  ghit_params     TEXT default NULL,
		  PRIMARY KEY  (ghit_ID),
		  KEY ghit_goal_ID (ghit_goal_ID),
		  KEY ghit_hit_ID (ghit_hit_ID)
   ) ENGINE = myisam DEFAULT CHARACTER SET = $db_storage_charset" );

$schema_queries['T_logs__internal_searches'] = array(
		'Creating internal searches table',
		"CREATE TABLE `evo_logs__internal_searches` (
  `isrch_ID` bigint(20) NOT NULL auto_increment,
  `isrch_coll_ID` bigint(20) NOT NULL default '0',
  `isrch_hit_ID` bigint(20) NOT NULL default '0',
  `isrch_keywords` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`isrch_ID`)
) ENGINE=MyISAM DEFAULT CHARSET = $db_storage_charset");
		
/*
 * $Log$
 * Revision 1.22  2011/09/12 15:33:05  lxndral
 * sessions table creation fix
 *
 * Revision 1.21  2011/09/10 21:37:53  fplanque
 * minor
 *
 * Revision 1.20  2011/09/09 23:05:08  lxndral
 * Search for "fp>al" in code to find my comments and please make requested changed
 *
 * Revision 1.19  2011/09/08 11:06:02  lxndral
 * fix for sessions install script (internal searches)
 *
 * Revision 1.18  2011/09/07 22:44:41  fplanque
 * UI cleanup
 *
 * Revision 1.17  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.16  2011/09/04 22:13:18  fplanque
 * copyright 2011
 *
 * Revision 1.15  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 * 
 * Revision 1.14  2009/12/08 22:38:13  fplanque
 * User agent type is now saved directly into the hits table instead of a costly lookup in user agents table
 *
 * Revision 1.13  2009/09/21 03:16:48  fpla