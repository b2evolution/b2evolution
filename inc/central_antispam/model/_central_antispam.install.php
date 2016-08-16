<?php
/**
 * This is the install file for the central antispam module
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
$schema_queries['T_centralantispam__keyword'] = array(
		'Creating table central antispam keywords...',
		"CREATE TABLE T_centralantispam__keyword (
			cakw_ID              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			cakw_keyword         VARCHAR(2000) NULL,
			cakw_status          ENUM('new', 'published', 'revoked', 'ignored') NOT NULL DEFAULT 'new',
			cakw_statuschange_ts TIMESTAMP NULL,
			cakw_lastreport_ts   TIMESTAMP NULL,
			PRIMARY KEY (cakw_ID),
			INDEX cakw_keyword (cakw_keyword(255)),
			INDEX cakw_statuschange_ts (cakw_statuschange_ts),
			INDEX cakw_lastreport_ts (cakw_lastreport_ts)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );


$schema_queries['T_centralantispam__source'] = array(
		'Creating table central antispam sources...',
		"CREATE TABLE T_centralantispam__source (
			casrc_ID      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			casrc_baseurl VARCHAR(2000) NULL,
			casrc_status  ENUM ('trusted', 'promising', 'unknown', 'suspect', 'blocked') NOT NULL DEFAULT 'unknown',
			PRIMARY KEY (casrc_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );


$schema_queries['T_centralantispam__report'] = array(
		'Creating table central antispam reports...',
		"CREATE TABLE T_centralantispam__report (
			carpt_cakw_ID  INT(10) UNSIGNED NOT NULL,
			carpt_casrc_ID INT(10) UNSIGNED NOT NULL,
			carpt_ts       TIMESTAMP NULL,
			PRIMARY KEY carpt_PK (carpt_cakw_ID, carpt_casrc_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" );


?>