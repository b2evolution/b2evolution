<?php
/**
 * This is the install file for the polls module
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	'T_polls__question' => array(
		'Creating table for Poll questions',
		"CREATE TABLE T_polls__question (
			pqst_ID            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			pqst_owner_user_ID INT(11) UNSIGNED NOT NULL,
			pqst_question_text VARCHAR(2000) NULL,
			PRIMARY KEY (pqst_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_polls__option' => array(
		'Creating table for Poll options',
		"CREATE TABLE T_polls__option (
			popt_ID          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			popt_pqst_ID     INT(11) UNSIGNED NOT NULL,
			popt_option_text VARCHAR(2000) NULL,
			popt_order       INT(11) NOT NULL,
			PRIMARY KEY (popt_ID)
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),

	'T_polls__answer' => array(
		'Creating table for Poll answers',
		"CREATE TABLE T_polls__answer (
			pans_ID      INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			pans_pqst_ID INT(11) UNSIGNED NOT NULL,
			pans_user_ID INT(11) UNSIGNED NOT NULL,
			pans_popt_ID INT(11) UNSIGNED NOT NULL,
			PRIMARY KEY (pans_ID),
			UNIQUE pans_pqst_user_ID ( pans_pqst_ID, pans_user_ID )
		) ENGINE = innodb DEFAULT CHARSET = $db_storage_charset" ),
) );

?>