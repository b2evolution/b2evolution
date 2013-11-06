<?php
/**
 * This file implements the test Cron controller
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $dbm_tables_count;

load_funcs('tools/model/_dbmaintenance.funcs.php');

$simple_keys = array( 0, 1, 2, 3 );
$failed_results = array();

// Execute query to get results of CHECK command
$results = dbm_check_tables( false, false );

foreach( $results as $result )
{
	if( $result->Msg_type != 'status' )
	{ // Add different result types then 'status' to the failed resulsts array, so they can be display on the cron task view
		// Convert keys to simple integer values to decrease a size of the data
		$failed_results[] = array_combine( $simple_keys, (array)$result );
	}
}

// Optimize MyISAM & InnoDB tables
$results = dbm_optimize_tables( false, false );

foreach( $results as $result )
{
	if( $result->Msg_text != 'OK' && ( ! ( $result->Msg_type == 'note' && $result->Msg_text == 'Table does not support optimize, doing recreate + analyze instead' ) ) )
	{ // Save only results which are not OK and they are different notes then 'Table does not support optimize ...' because that is normal for innoDb tables
		// Convert keys to simple integer values to decrease a size of the data
		$failed_results[] = array_combine( $simple_keys, (array)$result );
	}
}

$result_message = array(
	'message' => sprintf( T_('The commands CHECK & OPTIMIZE have been executed for all %d tables.'), $dbm_tables_count ),
	'table_cols' => array(
		T_('Table'),
		T_('Operation'),
		T_('Result'),
		T_('Message ')
	),
	'table_data' => $failed_results
);

return 1; /* ok */

/*
 * $Log$
 * Revision 1.1  2013/11/06 08:04:07  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>