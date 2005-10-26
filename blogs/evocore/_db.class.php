<?php
/**
 * This file implements the DB class.
 *
 * Based on ezSQL - Class to make it very easy to deal with MySQL database connections.
 * b2evo Additions:
 * - nested transactions
 * - symbolic table names
 * - query log
 * - get_list
 * - dynamic extension loading
 * - Debug features (EXPLAIN...)
 * and more...
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Justin Vincent - {@link http://php.justinvincent.com}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * This file is based on the following package (excerpt from ezSQL's readme.txt):
 * =======================================================================
 * Author:  Justin Vincent (justin@visunet.ie)
 * Web: 	 http://php.justinvincent.com
 * Name: 	 ezSQL
 * Desc: 	 Class to make it very easy to deal with database connections.
 * License: FREE / Donation (LGPL - You may do what you like with ezSQL - no exceptions.)
 * =======================================================================
 * A $10 donation has been made to Justin VINCENT on behalf of the b2evolution team.
 * The package has been relicensed as GPL based on
 * "You may do what you like with ezSQL - no exceptions."
 * 2004-10-14 (email): Justin VINCENT grants François PLANQUE the right to relicense
 * this modified class under other licenses. "Just include a link to where you got it from."
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 * @author Justin VINCENT
 *
 * @version $Id$
 * @todo transaction support
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * ezSQL Constants
 */
define( 'EZSQL_VERSION', '1.25' );
define( 'OBJECT', 'OBJECT', true );
define( 'ARRAY_A', 'ARRAY_A', true);
define( 'ARRAY_N', 'ARRAY_N', true);

if( ! function_exists( 'mysql_real_escape_string' ) )
{ // Function only available since PHP 4.3.0
	function mysql_real_escape_string( $unescaped_string )
	{
		return mysql_escape_string( $unescaped_string );
	}
}

/**
 * The Main Class
 *
 * @package evocore
 */
class DB
{
	var $show_errors = true;
	var $halt_on_error = true;
	var $error = false;		// no error yet
	var $num_queries = 0;
	var $last_query = '';		// last query SQL string
	var $last_error = '';			// last DB error string
	var $col_info;

	var $vardump_called;
	var $insert_id = 0;
	var $num_rows = 0;
	var $rows_affected = 0;
	var $last_result;
	/**
	 * Log of queries:
	 */
	var $queries = array();
	/**
	 * Aliases that will be replaced in queries:
	 */
	var $dbaliases = array();
	/**
	 * Strings that will replace the aliases in queries:
	 */
	var $dbreplaces = array();

	/**
	 * CREATE TABLE options.
	 *
	 * Edit those if you have control over you MySQL server and want a more professional
	 * database than what is commonly offered by popular hosting providers.
	 *
	 * Recommended settings: ' ENGINE=InnoDB '
	 * Development settings: ' ENGINE=InnoDB DEFAULT CHARSET=utf8 '
	 * @var string Default: ''
	 */
	var $table_options = '';

	/**
	 * Use transactions in DB?
	 *
	 * You need to use InnoDB in order to enable this. {@see $table_options}
	 */
	var $use_transactions = false;

	/**
	 * How many transactions are currently nested?
	 */
	var $transaction_nesting_level = 0;

	/**
	 * Rememeber if we have to rollback at the end of a nested transaction construct
	 */
	var $rollback_nested_transaction = false;

	/**
	 * @var object MySQL Database handle
	 */
	var $dbhandle;


	/**
	 * @var string Database username
	 */
	var $dbuser;

	/**
	 * @var string Database username's password
	 */
	var $dbpassword;

	/**
	 * @var string Database name
	 * @see {@link select()}
	 */
	var $dbname;

	/**
	 * @var string Database hostname
	 */
	var $dbhost = 'localhost';


	// DEBUG:

	/**
	 * Do we want to explain joins?
	 * @var bool (Default: false)
	 */
	var $debug_explain_joins = false;

	/**
	 * Do we want to output a function backtrace for every query?
	 * @var integer|boolean Number of stack entries to show (from last to first) (Default: 0); true means 'all'.
	 */
	var $debug_dump_function_trace_for_queries = 0;

	/**
	 * Do we want to output a function backtrace for errors?
	 * NOTE: This is only used when {@link $show_errors} is enabled.
	 * @var integer|boolean Number of stack entries to show (from last to first) (Default: true); true means 'all'.
	 */
	var $debug_dump_function_trace_for_errors = true;

	/**
	 * Number of rows we want to dump in debug output (0 disables it)
	 * @var integer (Default: 0)
	 */
	var $debug_dump_rows = 0;

	/**
	 * Time in seconds that is considered a fast query (green).
	 * @var float
	 * @see dump_queries()
	 */
	var $query_duration_fast = 0.05;

	/**
	 * Time in seconds that is considered a slow query (red).
	 * @var float
	 * @see dump_queries()
	 */
	var $query_duration_slow = 0.3;


	/**
	 * DB Constructor
	 *
	 * Connects to the server and selects a database.
	 *
	 * @param array An array of parameters.
	 *   Manadatory:
	 *    - 'user': username to connect with
	 *    - 'password': password to connect with
	 *    - 'name': the name of the default database, see {@link select()}
	 *   Optional:
	 *    - 'host': host of the database; Default: 'localhost'
	 *    - 'show_errors': Display SQL errors? (true/false); Default: don't change member default ({@link $show_errors})
	 *    - 'halt_on_error': Halt on error? (true/false); Default: don't change member default ({@link $halt_on_error})
	 *    - 'table_options': sets {@link $table_options}
	 *    - 'use_transactions': sets {@link $use_transactions}
	 *    - 'aliases': Aliases for tables (array( alias => table name )); Default: no aliases.
	 */
	function DB( $params )
	{
		//pre_dump( $params );

		// Mandatory parameters:
		$this->dbuser = $params['user'];
		$this->dbpassword = $params['password'];
		$this->dbname = $params['name'];

		// Optional parameters (Allow overriding through $params, because we cannot hack the $DB object after creation without using a "@include"):
		if( isset($params['host']) ) $this->dbhost = $params['host'];
		if( isset($params['show_errors']) ) $this->show_errors = $params['show_errors'];
		if( isset($params['halt_on_error']) ) $this->halt_on_error = $params['halt_on_error'];
		if( isset($params['table_options']) ) $this->table_options = $params['table_options'];
		if( isset($params['use_transactions']) ) $this->use_transactions = $params['use_transactions'];
		if( isset($params['debug_dump_rows']) ) $this->debug_dump_rows = $params['debug_dump_rows'];
		if( isset($params['debug_explain_joins']) ) $this->debug_explain_joins = $params['debug_explain_joins'];
		if( isset($params['debug_dump_function_trace_for_errors']) ) $this->debug_dump_function_trace_for_errors = $params['debug_dump_function_trace_for_errors'];
		if( isset($params['debug_dump_function_trace_for_queries']) ) $this->debug_dump_function_trace_for_queries = $params['debug_dump_function_trace_for_queries'];

		if( !extension_loaded('mysql') )
		{ // The mysql extension is not loaded, try to dynamically load it:
			if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
			{
				@dl('php_mysql.dll');
			}
			else
			{
				@dl('mysql.so');
			}

			if( !extension_loaded('mysql') )
			{ // Still not loaded:
				$this->print_error( '<p><strong>The PHP MySQL module could not be loaded.</strong></p>
					<p>You must edit your php configuration (php.ini) and enable this module.</p>
					<p>Do not forget to restart your webserver (if necessary) after editing the PHP conf.</p>' );
				return;
			}
		}

		// Connect to the Database:
		$this->dbhandle = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword );

		if( ! $this->dbhandle )
		{
			$this->print_error( '<p><strong>Error establishing a database connection!</strong></p>
				<p>('.mysql_error().')</p>
				<ol>
					<li>Are you sure you have typed the correct user/password?</li>
					<li>Are you sure that you have typed the correct hostname?</li>
					<li>Are you sure that the database server is running?</li>
				</ol>' );
		}
		else
		{
			$this->select($this->dbname);
		}


		if( !empty($params['connection_charset']) )
		{	// Specify which charset we are using on the client:
			$this->query( 'SET NAMES '.$params['connection_charset'] );
		}

		/*
		echo '<br />Server: '.$this->get_var( 'SELECT @@character_set_server' );
		echo '<br />Database: '.$this->get_var( 'SELECT @@character_set_database' );
		echo '<br />Connection: '.$this->get_var( 'SELECT @@character_set_connection' );
		echo '<br />Client: '.$this->get_var( 'SELECT @@character_set_client' );
		echo '<br />Results: '.$this->get_var( 'SELECT @@character_set_results' );
		*/


		if( isset($params['aliases']) )
		{ // Prepare aliases for replacements:
			foreach( $params['aliases'] as $dbalias => $dbreplace )
			{
				$this->dbaliases[] = '#\b'.$dbalias.'\b#'; // \b = word boundary
				$this->dbreplaces[] = $dbreplace;
				// echo '<br />'.'#\b'.$dbalias.'\b#';
			}
			// echo count($this->dbaliases);
		}
	}


	/**
	 * Select a DB (if another one needs to be selected)
	 */
	function select($db)
	{
		global $Timer;

		if( is_object($Timer) )
		{
			$Timer->resume( 'sql_queries' );
		}

		if( !@mysql_select_db($db, $this->dbhandle) )
		{
			$this->print_error( '<p><strong>Error selecting database ['.$db.']!</strong></p>
				<ol>
					<li>Are you sure the database exists?</li>
					<li>Are you sure there is a valid database connection?</li>
				</ol>' );
		}

		if( is_object($Timer) )
		{
			$Timer->pause( 'sql_queries' );
		}
	}


	/**
	 * Format a string correctly for safe insert under all PHP conditions
	 */
	function escape($str)
	{
		return mysql_real_escape_string($str);
	}


	/**
	 * Quote a value, either in single quotes (and escaped) or if it's NULL as 'NULL'.
	 *
	 * @return string Quoted (and escaped) value or 'NULL'.
	 */
	function quote($str)
	{
		if( $str === NULL )
			return 'NULL';
		else
			return "'".mysql_real_escape_string($str)."'";
	}


	/**
	 * @return string Return the given value or 'NULL', if it's === NULL.
	 */
	function null($val)
	{
		if( $val === NULL )
			return 'NULL';
		else
			return $val;
	}


	/**
	 * Returns the correct WEEK() function to get the week number for the given date.
	 *
	 * {@see http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html}
	 *
	 * @todo disable when MySQL < 4
	 * @param string will be used as is
	 * @param integer 0 for sunday, 1 for monday
	 */
	function week( $date, $startofweek )
	{
		if( $startofweek == 1 )
		{ // Week starts on Monday:
			return ' WEEK( '.$date.', 5 ) ';
		}

		// Week starts on Sunday:
		return ' WEEK( '.$date.', 0 ) ';
	}


	/**
	 * Returns the appropriate string to compare $val in a WHERE clause.
	 *
	 * @param mixed Value to create a "compare-String" for
	 * @return string Either 'IS NULL', 'IN ("a", "b", "c")' or " = 'a'".
	 */
	function compString( $val )
	{
		if( $val === NULL )
		{
			return 'IS NULL';
		}
		elseif( is_array($val) )
		{
			return 'IN ("'.implode('","', $val).'")';
		}
		else
		{
			return " = '$root'";
		}
	}


	/**
	 * Print SQL/DB error.
	 */
	function print_error( $str = '', $query_title = '' )
	{
		// All errors go to the global error array $EZSQL_ERROR..
		global $EZSQL_ERROR;

		$this->error = true;

		// If no special error string then use mysql default..
		$this->last_error = empty($str) ? ( '<p>'.mysql_error().'(Errno='.mysql_errno().')</p>' ) : $str;

		// Log this error to the global array..
		$EZSQL_ERROR[] = array(
			'query' => $this->last_query,
			'error_str'  => $this->last_error
		);

		// Is error output turned on or not..
		if ( $this->show_errors )
		{
			// If there is an error then take note of it
			echo '<div class="error">';
			echo '<p class="error">MySQL error!</p>';
			echo '<div>'.$this->last_error.'</div>';
			if( !empty($this->last_query) )
			{
				echo '<p class="error">Your query: '.$query_title.'<br /><pre>'.htmlspecialchars( str_replace("\t", '  ', $this->last_query) ).'</pre></p>';
			}

			if( $this->debug_dump_function_trace_for_errors )
			{
				echo debug_get_backtrace( $this->debug_dump_function_trace_for_errors );
			}

			echo '</div>';
		}

		if( $this->halt_on_error ) die();
	}


	/**
	 * Kill cached query results
	 */
	function flush()
	{
		// Get rid of these
		$this->last_result = NULL;
		$this->col_info = NULL;
		$this->last_query = NULL;
		$this->current_idx = 0;
	}


	/**
	 * Basic Query
	 *
	 * {@internal DB::query(-) }}
	 *
	 * @param string SQL query
	 * @param string title for debugging
	 * @return mixed # of rows affected or false if error
	 */
	function query( $query, $title = '' )
	{
		global $Timer;

		// initialise return
		$return_val = 0;

		// Flush cached values..
		$this->flush();

		// Log how the function was called
		$this->func_call = '$db->query("'.$query.'")';
		// echo $this->func_call, '<br />';

		// Replace aliases:
		$query = preg_replace( $this->dbaliases, $this->dbreplaces, $query );

		if( preg_match( '#^ \s* create \s* table \s #ix', $query) )
		{ // Query is a table creation, we add table options:
			$query .= $this->table_options;
		}

		// Keep track of the last query for debug..
		$this->last_query = $query;

		// Perform the query via std mysql_query function..
		$this->num_queries++;
		$this->queries[ $this->num_queries - 1 ] = array(
			'title' => $title,
			'sql' => $query,
			'rows' => -1,
			'time' => 'unknown',
			'results' => 'unknown' );

		if( is_object( $Timer ) )
		{
			// Resume global query timer
			$Timer->resume( 'sql_queries' );
			// Start a timer for this paritcular query:
			$Timer->start( 'query', false );
			// Run query:
			$this->result = @mysql_query( $query, $this->dbhandle );
			// Get duration for last query:
			$this->queries[ $this->num_queries - 1 ]['time'] = $Timer->get_duration( 'query', 10 );
			// Pause global query timer:
			$Timer->pause( 'sql_queries' );
		}
		else
		{
			$this->result = @mysql_query($query,$this->dbhandle);
		}

		// If there is an error then take note of it..
		if ( mysql_error() )
		{
			$this->print_error( '', $title );
			return false;
		}

		if( preg_match( '#^ \s* (insert|delete|update|replace) \s #ix', $query) )
		{ // Query was an insert, delete, update, replace:

			$this->rows_affected = mysql_affected_rows();
			$this->queries[ $this->num_queries - 1 ]['rows'] = $this->rows_affected;

			// Take note of the insert_id
			if ( preg_match("/^\\s*(insert|replace) /i",$query) )
			{
				$this->insert_id = mysql_insert_id($this->dbhandle);
			}

			// Return number fo rows affected
			$return_val = $this->rows_affected;
		}
		else
		{ // Query was a select, alter, etc...:

			// Take note of column info
			$i = 0;
			while( $i < @mysql_num_fields($this->result) )
			{
				$this->col_info[$i] = @mysql_fetch_field($this->result);
				$i++;
			}

			// Store Query Results
			$num_rows = 0;
			while( $row = @mysql_fetch_object($this->result) )
			{
				// Store relults as an objects within main array
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			@mysql_free_result($this->result);

			// Log number of rows the query returned
			$this->num_rows = $num_rows;
			$this->queries[ $this->num_queries - 1 ]['rows'] = $this->num_rows;

			// Return number of rows selected
			$return_val = $this->num_rows;
		}


		if( $this->debug_dump_function_trace_for_queries )
		{
			$this->queries[ $this->num_queries - 1 ]['function_trace'] = debug_get_backtrace( $this->debug_dump_function_trace_for_queries, array( array( 'class' => 'DB', 'function' => 'query' ) ) );
		}


		// EXPLAIN JOINS ??
		if( $this->debug_explain_joins && preg_match( '#^ \s* select \s #ix', $query) )
		{ // Query was a select, let's try to explain joins...

			// save values:
			$saved_last_result = $this->last_result;
			$saved_col_info = $this->col_info;
			$saved_num_rows = $this->num_rows;

			$this->last_result = NULL;
			$this->col_info = NULL;
			$this->num_rows = 0;

			$this->result = @mysql_query( 'EXPLAIN '.$query, $this->dbhandle );
			// Take note of column info
			$i = 0;
			while( $i < @mysql_num_fields($this->result) )
			{
				$this->col_info[$i] = @mysql_fetch_field($this->result);
				$i++;
			}

			// Store Query Results
			$num_rows = 0;
			while( $row = @mysql_fetch_object($this->result) )
			{
				// Store results as an objects within main array
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			@mysql_free_result($this->result);

			// Log number of rows the query returned
			$this->num_rows = $num_rows;

			$this->queries[ $this->num_queries - 1 ]['explain'] = $this->debug_get_rows_table( 100, true );

			// Restore:
			$this->last_result = $saved_last_result;
			$this->col_info = $saved_col_info;
			$this->num_rows = $saved_num_rows;
		}


		// If debug ALL queries
		if( $this->debug_dump_rows )
		{
			$this->queries[ $this->num_queries - 1 ]['results'] = $this->debug_get_rows_table( $this->debug_dump_rows );
		}

		return $return_val;
	}


	/**
	 * Get one variable from the DB - see docs for more detail
	 *
	 * Note: To be sure that you received NULL from the DB and not "no rows" check
	 *       for {@link $num_rows}.
	 *
	 * @return mixed NULL if not found, the value otherwise (which may also be NULL).
	 */
	function get_var( $query = NULL, $x = 0, $y = 0, $title = '' )
	{
		// Log how the function was called
		$this->func_call = "\$db->get_var(\"$query\",$x,$y)";

		// If there is a query then perform it if not then use cached results..
		if( $query )
		{
			$this->query($query, $title);
		}

		// Extract var out of cached results based x,y vals
		if( $this->last_result[$y] )
		{
			$values = array_values(get_object_vars($this->last_result[$y]));
		}

		if( isset($values[$x]) )
		{
			return $values[$x];
		}

		return NULL;
	}


	/**
	 * Get one row from the DB - see docs for more detail
	 *
	 * @return array|object
	 */
	function get_row( $query = NULL, $output = OBJECT, $y = 0, $title = '' )
	{
		// Log how the function was called
		$this->func_call = "\$db->get_row(\"$query\",$output,$y)";
		// echo $this->func_call, '<br />';

		// If there is a query then perform it if not then use cached results..
		if ( $query )
		{
			$this->query($query, $title);
		}

		// If the output is an object then return object using the row offset..
		if ( $output == OBJECT )
		{
			return $this->last_result[$y]
				? $this->last_result[$y]
				: array();
		}
		// If the output is an associative array then return row as such..
		elseif ( $output == ARRAY_A )
		{
			return $this->last_result[$y]
				? get_object_vars( $this->last_result[$y] )
				: array();
		}
		// If the output is an numerical array then return row as such..
		elseif ( $output == ARRAY_N )
		{
			return $this->last_result[$y]
				? array_values( get_object_vars($this->last_result[$y]) )
				: array();
		}
		// If invalid output type was specified..
		else
		{
			$this->print_error('<p>DB::get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N</p>');
		}
	}


	/**
	 * Function to get 1 column from the cached result set based in X index
	 * see docs for usage and info
	 *
	 * @return array
	 */
	function get_col( $query = NULL, $x = 0, $title = '' )
	{
		// If there is a query then perform it if not then use cached results..
		if( $query )
		{
			$this->query( $query, $title );
		}

		// Extract the column values
		$new_array = array();
		for( $i = 0, $count = count($this->last_result); $i < $count; $i++ )
		{
			$new_array[$i] = $this->get_var( NULL, $x, $i );
		}

		return $new_array;
	}


	/**
	 * Get a column as comma-seperated list.
	 *
	 * @param string|NULL Query to execute
	 * @param integer Column of the result set
	 * @return string
	 */
	function get_list( $query = NULL, $x = 0, $title = '' )
	{
		return implode( ',', $this->get_col( $query, $x, $title ) );
	}


	/**
	 * Return the the query as a result set - see docs for more details
	 *
	 * @return array
	 */
	function get_results( $query = NULL, $output = OBJECT, $title = '' )
	{
		// Log how the function was called
		$this->func_call = "\$db->get_results(\"$query\", $output)";

		// If there is a query then perform it if not then use cached results..
		if( $query )
		{
			$this->query($query, $title);
		}

		// Send back array of objects. Each row is an object
		if ( $output == OBJECT )
		{
			return $this->last_result ? $this->last_result : array();
		}
		elseif ( $output == ARRAY_A || $output == ARRAY_N )
		{
			$new_array = array();

			if( $this->last_result )
			{
				$i = 0;

				foreach( $this->last_result as $row )
				{
					$new_array[$i] = get_object_vars($row);

					if ( $output == ARRAY_N )
					{
						$new_array[$i] = array_values($new_array[$i]);
					}

					$i++;
				}

				return $new_array;
			}
			else
			{
				return array();
			}
		}
	}


	/**
	 * Function to get column meta data info pertaining to the last query
	 * see docs for more info and usage
	 */
	function get_col_info( $info_type = 'name', $col_offset = -1 )
	{
		if( $this->col_info )
		{
			if( $col_offset == -1 )
			{
				$i = 0;
				foreach($this->col_info as $col )
				{
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			}
			else
			{
				return $this->col_info[$col_offset]->{$info_type};
			}
		}
	}


	/**
	 * Dumps the contents of any input variable to screen in a nicely
	 * formatted and easy to understand way - any type: Object, Var or Array
	 *
	 * @param mixed Variable to dump
	 */
	function vardump( $mixed = '' )
	{
		echo '<p><table summary="Variable Dump"><tr><td bgcolor="fff"><blockquote style="color:#000090">';
		echo '<pre style="font-family:Arial">';

		if ( ! $this->vardump_called )
		{
			echo '<span style="color:#800080"><strong>ezSQL</strong> (v'.EZSQL_VERSION.") <strong>Variable Dump..</strong></span>\n\n";
		}

		$var_type = gettype ($mixed);
		print_r( ( $mixed ? $mixed : '<span style="color:#f00">No Value / False</span>') );
		echo "\n\n<strong>Type:</strong> ".ucfirst( $var_type )."\n"
				."<strong>Last Query</strong> [$this->num_queries]<strong>:</strong> "
				.( $this->last_query ? $this->last_query : "NULL" )."\n"
				.'<strong>Last Function Call:</strong> '.( $this->func_call ? $this->func_call : 'None' )."\n"
				.'<strong>Last Rows Returned:</strong> '.count( $this->last_result )."\n"
				.'</pre></blockquote></td></tr></table>';
		echo "\n<hr size=1 noshade color=dddddd>";

		$this->vardump_called = true;
	}


	/**
	 * Alias for {@link vardump()}
	 *
	 * @param mixed Variable to dump
	 */
	function dumpvar( $mixed )
	{
		$this->vardump( $mixed );
	}


	/**
	 * Get a table (or "<p>No Results.</p>") for the SELECT query results.
	 *
	 * @return string HTML table or "No Results" if the
	 */
	function debug_get_rows_table( $max_lines, $break_at_comma = false )
	{
		$r = '';

		if( $this->col_info )
		{
			// =====================================================
			// Results top rows
			$r .= '<table cellspacing="0" summary="Results for query"><tr>';
			for( $i = 0, $count = count($this->col_info); $i < $count; $i++ )
			{
				$r .= '<th><span class="type">'.$this->col_info[$i]->type.' '.$this->col_info[$i]->max_length.'</span><br />'
							.$this->col_info[$i]->name.'</th>';
			}
			$r .= '</tr>';

			$i=0;

			// ======================================================
			// print main results
			if( $this->last_result )
			{
				foreach( $this->get_results(NULL,ARRAY_N) as $one_row )
				{
					$i++;
					if( $i >= $max_lines )
					{
						break;
					}
					$r .= '<tr>';
					foreach( $one_row as $item )
					{
						if( $i % 2 )
						{
							$r .= '<td class="odd">';
						}
						else
						{
							$r .= '<td>';
						}

						if( $break_at_comma )
						{
							$item = str_replace( ',', '<br />', $item );
							$item = str_replace( ';', '<br />', $item );
							$r .= $item;
						}
						else
						{
							if( strlen( $item ) > 50 )
							{
								$item = substr( $item, 0, 50 ).'...';
							}
							$r .= htmlspecialchars($item);
						}
						$r .= '</td>';
					}

					$r .= '</tr>';
				}

			} // if last result
			else
			{
				$r .= '<tr><td colspan="'.(count($this->col_info)+1).'">No Results</td></tr>';
			}
			if( $i >= $max_lines )
			{
				$r .= '<tr><td colspan="'.(count($this->col_info)+1).'">Max number of dumped rows has been reached.</td></tr>';
			}

			$r .= '</table>';

		} // if col_info
		else
		{
			$r .= '<p>No Results.</p>';
		}

		return $r;
	}


	/**
	 * Displays all queries that have been executed
	 *
	 * {@internal DB::dump_queries(-) }}
	 */
	function dump_queries()
	{
		global $Timer;
		if( is_object( $Timer ) )
		{
			$time_queries = $Timer->get_duration( 'sql_queries' );
		}
		else
		{
			$time_queries = 0;
		}

		$count_queries = $count_rows = 0;

		echo '<strong>DB queries:</strong> '.$this->num_queries.'<br />';

		foreach( $this->queries as $query )
		{
			$count_queries++;
			echo '<h4>Query #'.$count_queries.': '.$query['title'].'</h4>';
			echo '<code>';
			$sql = str_replace( 'FROM', '<br />FROM', htmlspecialchars($query['sql']) );
			$sql = str_replace( 'WHERE', '<br />WHERE', $sql );
			$sql = str_replace( 'GROUP BY', '<br />GROUP BY', $sql );
			$sql = str_replace( 'ORDER BY', '<br />ORDER BY', $sql );
			$sql = str_replace( 'LIMIT', '<br />LIMIT', $sql );
			$sql = str_replace( 'AND ', '<br />&nbsp; AND ', $sql );
			$sql = str_replace( 'OR ', '<br />&nbsp; OR ', $sql );
			$sql = str_replace( 'VALUES', '<br />VALUES', $sql );
			echo $sql;
			echo '</code>';

			// Color-Format duration: long => red, fast => green, normal => black
			if( $query['time'] > $this->query_duration_slow )
			{
				$style_time_text = 'color:red;font-weight:bold;';
				$style_time_graph = 'background-color:red;';
			}
			elseif( $query['time'] < $this->query_duration_fast )
			{
				$style_time_text = 'color:green;';
				$style_time_graph = 'background-color:green;';
			}
			else
			{
				$style_time_text = '';
				$style_time_graph = 'background-color:black;';
			}

			// Number of rows with time (percentage and graph, if total time available)
			echo '<div class="query_info">';
			echo 'Rows: '.$query['rows'];

			echo ' &ndash; Time: ';
			if( $style_time_text )
			{
				echo '<span style="'.$style_time_text.'">';
			}
			echo number_format( $query['time'], 4 ).'s';

			if( $time_queries > 0 )
			{ // We have a total time we can use to calculate percentage:
				echo ' ('.number_format( 100/$time_queries * $query['time'], 2 ).'%)';
			}

			if( $style_time_text )
			{
				echo '</span>';
			}

			if( $time_queries > 0 )
			{ // We have a total time we can use to display a graph/bar:
				echo '<div style="margin:0; padding:0; height:12px; width:'.( round( 100/$time_queries * $query['time'] ) ).'%;'.$style_time_graph.'"></div>';
			}
			echo '</div>';


			// Explain:
			if( isset($query['explain']) )
			{
				echo $query['explain'];
			}

			// Results:
			if( $query['results'] != 'unknown' )
			{
				echo $query['results'];
			}

			// Function trace:
			if( isset($query['function_trace']) )
			{
				echo $query['function_trace'];
			}

			$count_rows += $query['rows'];
		}
		echo '<strong>Total rows:</strong> '.$count_rows.'<br />';
	}


	/**
	 * BEGIN A TRANSCATION
	 *
	 * Note:  By default, MySQL runs with autocommit mode enabled.
	 * This means that as soon as you execute a statement that updates (modifies)
	 * a table, MySQL stores the update on disk.
	 * Once you execute a BEGIN, the updates are "pending" until you execute a
	 * {@link DB::commit() COMMIT} or a {@link DB:rollback() ROLLBACK}
	 *
	 * Note 2: standard syntax would be START TRANSACTION but it's not supported by older
	 * MySQL versions whereas BEGIN is...
	 *
	 * Note 3: The default isolation level is REPEATABLE READ.
	 */
	function begin()
	{
		if( $this->use_transactions )
		{
			$this->query( 'BEGIN', 'BEGIN transaction' );

			$this->transaction_nesting_level++;
		}
	}


	/**
	 * Commit current transaction
	 */
	function commit()
	{
		if( $this->use_transactions )
		{
			if( $this->transaction_nesting_level == 1 )
			{ // Only COMMIT if there are no remaining nested transactions:
				if( $this->rollback_nested_transaction )
				{
					$this->query( 'ROLLBACK', 'ROLLBACK transaction because there was a failure somewhere in the nesting of transactions' );
				}
				else
				{
					$this->query( 'COMMIT', 'COMMIT transaction' );
				}
				$this->rollback_nested_transaction = false;
			}
			$this->transaction_nesting_level--;
		}
	}


	/**
	 * @todo implement transactions!
	 */
	function rollback()
	{
		if( $this->use_transactions )
		{
			if( $this->transaction_nesting_level == 1 )
			{ // Only ROLLBACK if there are no remaining nested transactions:
				$this->query( 'ROLLBACK', 'ROLLBACK transaction' );
				$this->rollback_nested_transaction = false;
			}
			else
			{ // Remember we'll have to roll back at the end!
				$this->rollback_nested_transaction = true;
			}
			$this->transaction_nesting_level--;
		}
	}

}

/*
 * $Log$
 * Revision 1.38  2005/10/26 11:25:38  blueyed
 * Slightly changed behaviour of $debug_dump_function_trace_for_*
 *
 * Revision 1.37  2005/10/24 15:36:12  blueyed
 * Code layout / whitespace
 *
 * Revision 1.36  2005/10/13 22:32:30  blueyed
 * Use debug_get_backtrace(); Remove obsolete method which used xdebug for that.
 *
 * Revision 1.35  2005/10/13 22:02:37  blueyed
 * Summary for tables; select() included in query timer.
 *
 * Revision 1.34  2005/10/06 17:03:02  fplanque
 * allow to set a specific charset for the MySQL connection.
 * This allows b2evo to work internally in a charset different from the database charset.
 *
 * Revision 1.33  2005/09/30 18:48:54  fplanque
 * xhtml validity
 *
 * Revision 1.32  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.31  2005/09/26 23:09:10  blueyed
 * Use $EvoConfig->DB for $DB parameters.
 *
 * Revision 1.30  2005/09/26 18:15:25  fplanque
 * no message
 *
 * Revision 1.29  2005/09/25 16:17:59  blueyed
 * Debugging enhanced: $debug_dump_function_trace_for_queries / $debug_dump_function_trace_for_errors (that was there before, but is now configurable)
 *
 * Revision 1.28  2005/09/20 23:23:56  blueyed
 * Added colorization of query durations (graph bar).
 *
 * Revision 1.27  2005/09/18 01:49:41  blueyed
 * Doc, whitespace.
 *
 * Revision 1.26  2005/09/11 23:46:31  fplanque
 * no message
 *
 * Revision 1.25  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.24  2005/09/02 21:31:34  fplanque
 * enhanced query debugging features
 *
 * Revision 1.23  2005/08/22 18:30:29  fplanque
 * minor
 *
 * Revision 1.22  2005/08/21 22:54:18  blueyed
 * Updated documentation for get_var().
 *
 * Revision 1.21  2005/08/21 22:44:32  blueyed
 * Removed dependencies on T_() and $Timer.
 *
 * Revision 1.20  2005/08/17 16:20:54  fplanque
 * rollback! I can't see a damn good reason to break existing code just because it happens that MySQL does not have a real boolean type!
 *
 * Revision 1.19  2005/07/22 13:54:45  blueyed
 * Better format for queries in print_error(); return value of get_var() is false, if nothing found (a DB cannot return false [boolean], but NULL?!)
 *
 * Revision 1.18  2005/07/15 18:11:16  fplanque
 * output debug context
 *
 * Revision 1.17  2005/07/12 23:05:36  blueyed
 * Added Timer class with categories 'main' and 'sql_queries' for now.
 *
 * Revision 1.16  2005/06/02 18:50:52  fplanque
 * no message
 *
 * Revision 1.15  2005/04/21 12:10:50  blueyed
 * minor
 *
 * Revision 1.14  2005/04/20 18:37:59  fplanque
 * Relocation of javascripts and CSS files to their proper places...
 *
 * Revision 1.13  2005/04/19 18:04:38  fplanque
 * implemented nested transactions for MySQL
 *
 * Revision 1.12  2005/03/08 20:32:07  fplanque
 * small fixes; slightly enhanced WEEK() handling
 *
 * Revision 1.11  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.10  2005/02/23 22:32:44  blueyed
 * output xdebug function stack in case of error
 *
 * Revision 1.6  2005/02/08 00:43:15  blueyed
 * doc, whitespace, html, get_results() and get_row() now always return array, get_var() return false in case of error
 *
 * Revision 1.5  2004/11/09 00:25:11  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.3  2004/10/28 11:11:09  fplanque
 * MySQL table options handling
 *
 * Revision 1.2  2004/10/14 16:28:41  fplanque
 * minor changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.21  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>