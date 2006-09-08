<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * check_db_version(-)
 *
 * Note: version number 8000 once meant 0.8.00.0, but I decided to switch to sequential
 * increments of 10 (in case we ever need to introduce intermediate versions for intermediate
 * bug fixes...)
 */
function check_db_version()
{
	global $DB, $old_db_version, $new_db_version;

	echo '<p>'.T_('Checking DB schema version...').' ';
	$DB->query( 'SELECT * FROM T_settings LIMIT 1' );

	if( $DB->get_col_info('name', 0) == 'set_name' )
	{ // we have new table format (since 0.9)
		$old_db_version = $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = "db_version"' );
	}
	else
	{
		$old_db_version = $DB->get_var( 'SELECT db_version FROM T_settings' );
	}

	if( empty($old_db_version) ) debug_die( T_('NOT FOUND! This is not a b2evolution database.') );

	echo $old_db_version, ' : ';

	if( $old_db_version < 8000 ) debug_die( T_('This version is too old!') );
	if( $old_db_version > $new_db_version ) debug_die( T_('This version is too recent! We cannot downgrade to it!') );
	echo "OK.<br />\n";
}


/**
 * Clean up extra quotes in posts
 */
function cleanup_post_quotes()
{
  global $DB;

	echo "Checking for extra quote escaping in posts... ";
	$query = "SELECT ID, post_title, post_content
							FROM T_posts
						 WHERE post_title LIKE '%\\\\\\\\\'%'
						 		OR post_title LIKE '%\\\\\\\\\"%'
						 		OR post_content LIKE '%\\\\\\\\\'%'
						 		OR post_content LIKE '%\\\\\\\\\"%' ";
	/* FP: the above looks overkill, but MySQL is really full of surprises...
					tested on 4.0.14-nt */
	// echo $query;
	$rows = $DB->get_results( $query, ARRAY_A );
	if( $DB->num_rows )
	{
		echo 'Updating '.$DB->num_rows.' posts... ';
		foreach( $rows as $row )
		{
			// echo '<br />'.$row['post_title'];
			$query = "UPDATE T_posts
								SET post_title = ".$DB->quote( stripslashes( $row['post_title'] ) ).",
										post_content = ".$DB->quote( stripslashes( $row['post_content'] ) )."
								WHERE ID = ".$row['ID'];
			// echo '<br />'.$query;
			$DB->query( $query );
		}
	}
	echo "OK.<br />\n";

}

/**
 * Clean up extra quotes in comments
 */
function cleanup_comment_quotes()
{
  global $DB;

	echo "Checking for extra quote escaping in comments... ";
	$query = "SELECT comment_ID, comment_content
							FROM T_comments
						 WHERE comment_content LIKE '%\\\\\\\\\'%'
						 		OR comment_content LIKE '%\\\\\\\\\"%' ";
	/* FP: the above looks overkill, but MySQL is really full of surprises...
					tested on 4.0.14-nt */
	// echo $query;
	$rows = $DB->get_results( $query, ARRAY_A );
	if( $DB->num_rows )
	{
		echo 'Updating '.$DB->num_rows.' comments... ';
		foreach( $rows as $row )
		{
			$query = "UPDATE T_comments
								SET comment_content = ".$DB->quote( stripslashes( $row['comment_content'] ) )."
								WHERE comment_ID = ".$row['comment_ID'];
			// echo '<br />'.$query;
			$DB->query( $query );
		}
	}
	echo "OK.<br />\n";

}


/**
 * Validate install requirements.
 *
 * @return array List of errors, empty array if ok.
 */
function install_validate_requirements()
{
	$errors = array();

	return $errors;
}


/*
 * $Log$
 * Revision 1.15  2006/09/08 15:35:36  blueyed
 * Completely nuked tokenizer dependency - removed commented out block
 *
 * Revision 1.14  2006/08/20 20:54:31  blueyed
 * Removed dependency on tokenizer. Quite a few people don't have it.. see http://forums.b2evolution.net//viewtopic.php?t=8664
 *
 * Revision 1.13  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.12  2006/06/19 20:59:38  fplanque
 * noone should die anonymously...
 *
 * Revision 1.11  2006/04/06 08:52:27  blueyed
 * Validate install "misc" requirements ("tokenizer" support for now)
 *
 * Revision 1.10  2005/12/30 18:08:24  fplanque
 * no message
 *
 */
?>