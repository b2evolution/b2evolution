<?php
/**
 * Group handling functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
require_once dirname(__FILE__).'/_class_group.php';


/*
 * groups_load_cache(-)
 */
function groups_load_cache()
{
	global $tablegroups, $querycount, $cache_Groups, $use_cache;
	
	if( empty($cache_Groups) || !$use_cache )  
	{
		$cache_Groups = array();	
		$sql = "SELECT * FROM $tablegroups ORDER BY grp_ID";
		$result = mysql_query($sql) or mysql_oops( $sql );
		$querycount++;
		while( $row = mysql_fetch_object($result) )
		{
			$cache_Groups[$row->grp_ID] = new Group( $row ); // COPY!
		}
	}
}

/*
 * Group_get_by_ID(-)
 */
function Group_get_by_ID( $grp_ID ) 
{
	global $cache_Groups, $use_cache;
	
	if((empty($cache_Groups)) OR (!$use_cache)) 
	{
		groups_load_cache();
	}

	if( empty( $cache_Groups[ $grp_ID ] ) ) die('Requested group does not exist!');

	return $cache_Groups[ $grp_ID ];
}

/*
 * groups_options(-)
 */
function groups_options( $default = 0 )
{
	global $cache_Groups;

	groups_load_cache();
	
	foreach( $cache_Groups as $loop_Group )
	{
		echo '<option value="'.$loop_Group->ID.'"';
		if( $loop_Group->ID == $default ) echo ' selected="selected"';
		echo '>'.$loop_Group->name.'</option>';
	}
}


?>
