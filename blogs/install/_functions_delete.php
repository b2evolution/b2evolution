<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
function db_delete()
{
	global $tableposts, $tableusers, $tablesettings, $tablecategories, $tablecomments, $tableblogs,
        $tablepostcats, $tablehitlog, $tableantispam, $tablegroups;

	echo "Droping Antispam table...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableantispam";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Hit-Logs...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablehitlog";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Comments...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablecomments";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Categories-to-Posts relationships...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablepostcats";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Categories...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablecategories";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Posts...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableposts";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Users...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableusers";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Groups...<br />\n";
	$query = "DROP TABLE IF EXISTS $tablegroups";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Blogs...<br />\n";
	$query = "DROP TABLE IF EXISTS $tableblogs";
	$q = mysql_query($query) or mysql_oops( $query );
	
	echo "Droping Settings...</p>\n";
	$query = "DROP TABLE IF EXISTS $tablesettings";
	$q = mysql_query($query) or mysql_oops( $query );
}

?>