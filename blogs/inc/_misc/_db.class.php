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
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Justin Vincent - {@link http://php.justinvincent.com}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Origin:
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
 * 2004-10-14 (email): Justin VINCENT grants Francois PLANQUE the right to relicense
 * this modified class under other licenses. "Just include a link to where you got it from."
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
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

	/**
	 * Column information about the last query.
	 * Note: {@link DB::log_queries} must be enabled for this to work.
	 * @see DB::get_col_info()
	 */
	var $col_info;

	var $vardump_called;
	var $insert_id = 0;

	var $last_result;

	/**
	 * Number of rows in result set (after a select)
	 */
	var $num_rows = 0;

	/**
	 * Number of rows affected by insert, delete, update or replace
	 */
	var $rows_affected = 0;

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
	 * This gets appended to every "CREATE TABLE" query.
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
	 * You need to use InnoDB in order to enable this.  See the {@link $db_config "table_options" key}.
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
	 * @see select()
	 */
	var $dbname;

	/**
	 * @var string Database hostname
	 */
	var $dbhost = 'localhost';

	/**
	 * @var string Current connection charset
	 * @see DB::set_connection_charset()
	 */
	var $connection_charset;


	// DEBUG:

  /**
   * Do we want to log queries?
	 * @todo dh> shouldn't this be false by default??
   * @var boolean
   */
	var $log_queries = true;

	/**
	 * Log of queries:
	 * @var array
	 */
	var $queries = array();

	/**
	 * Do we want to explain joins?
	 * @var boolean (Default: false)
	 */
	var $debug_explain_joins = false;

	/**
	 * Do we want to output a function backtrace for every query?
	 * @var integer|boolean Number of stack entries to show (from last to first) (Default: 0); true means 'all'.
	 */
	var $debug_dump_function_trace_for_queries = 0;

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
	 *    OR
	 *    - 'handle': a MySQL Database handle (from a previous {@link mysql_connect()})
	 *   Optional:
	 *    - 'name': the name of the default database, see {@link DB::select()}
	 *    - 'host': host of the database; Default: 'localhost'
	 *    - 'show_errors': Display SQL errors? (true/false); Default: don't change member default ({@link $show_errors})
	 *    - 'halt_on_error': Halt on error? (true/false); Default: don't change member default ({@link $halt_on_error})
	 *    - 'table_options': sets {@link $table_options}
	 *    - 'use_transactions': sets {@link $use_transactions}
	 *    - 'aliases': Aliases for tables (array( alias => table name )); Default: no aliases.
	 *    - 'new_link': create a new link to the DB, even if there was a mysql_connect() with
	 *       the same params before.
	 *    - 'client_flags': optional settings like compression or SSL encryption. See {@link http://www.php.net/manual/en/ref.mysql.php#mysql.client-flags}.
	 */
	function DB( $params )
	{
		//pre_dump( $params );

		// Mandatory parameters:
		if( isset( $params['handle'] ) )
		{ // DB-Link provided:
			$this->dbhandle = $params['handle'];
		}
		else
		{
			$this->dbuser = $params['user'];
			$this->dbpassword = $params['password'];
		}

		// Optional parameters (Allow overriding through $params):
		if( isset($params['name']) ) $this->dbname = $params['name'];
		if( isset($params['host']) ) $this->dbhost = $params['host'];
		if( isset($params['show_errors']) ) $this->show_errors = $params['show_errors'];
		if( isset($params['halt_on_error']) ) $this->halt_on_error = $params['halt_on_error'];
		if( isset($params['table_options']) ) $this->table_options = $params['table_options'];
		if( isset($params['use_transactions']) ) $this->use_transactions = $params['use_transactions'];
		if( isset($params['debug_dump_rows']) ) $this->debug_dump_rows = $params['debug_dump_rows'];
		if( isset($params['debug_explain_joins']) ) $this->debug_explain_joins = $params['debug_explain_joins'];
		if( isset($params['debug_dump_function_trace_for_queries']) ) $this->debug_dump_function_trace_for_queries = $params['debug_dump_function_trace_for_queries'];

		if( ! extension_loaded('mysql') )
		{ // The mysql extension is not loaded, try to dynamically load it:
			$mysql_ext_file = strtoupper(substr(PHP_OS, 0, 3) == 'WIN') ? 'php_mysql.dll' : 'mysql.so';
			@dl( $mysql_ext_file );

			if( ! extension_loaded('mysql') )
			{ // Still not loaded:
				$this->print_error( 'The PHP MySQL module could not be loaded.', '
					<p>You must edit your php configuration (php.ini) and enable this module ('.$mysql_ext_file.').</p>
					<p>Do not forget to restart your webserver (if necessary) after editing the PHP conf.</p>', false );
				return;
			}
		}

		$new_link = isset( $params['new_link'] ) ? $params['new_link'] : false;
		$client_flags = isset( $params['client_flags'] ) ? $params['client_flags'] : 0;

		if( ! isset($params['handle']) )
		{ // Connect to the Database:
			$this->dbhandle = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
		}

		if( ! $this->dbhandle )
		{
			$this->print_error( 'Error establishing a database connection!', '
				<p>('.mysql_error().')</p>
				<ol>
					<li>Are you sure you have typed the correct user/password?</li>
					<li>Are you sure that you have typed the correct hostname?</li>
					<li>Are you sure that the database server is running?</li>
				</ol>', false );
		}
		elseif( isset($this->dbname) )
		{
			$this->select($this->dbname);
		}


		if( !empty($params['connection_charset']) )
		{	// Specify which charset we are using on the client:
			$this->set_connection_charset($params['connection_charset']);
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
		if( !@mysql_select_db($db, $this->dbhandle) )
		{
			$this->print_error( 'Error selecting database ['.$db.']!', '
				<ol>
					<li>Are you sure the database exists?</li>
					<li>Are you sure the DB user is allowed to use that database?</li>
					<li>Are you sure there is a valid database connection?</li>
				</ol>', false );
		}
	}


	/**
	 * Format a string correctly for safe insert under all PHP conditions
	 */
	function escape($str)
	{
		return mysql_real_escape_string($str, $this->dbhandle);
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
			return "'".mysql_real_escape_string($str, $this->dbhandle)."'";
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
	 * @link http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html
	 *
	 * @todo disable when MySQL < 4
	 * @param string will be used as is
	 * @param integer 0 for sunday, 1 for monday
	 */
	function week( $date, $startofweek )
	{
		if( $startofweek == 1 )
		{ // Week starts on Monday, week 1 must have a monday in this year:
			return ' WEEK( '.$date.', 5 ) ';
		}

		// Week starts on Sunday, week 1 must have a sunday in this year:
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
	 *
	 * TODO: fp> bloated: it probably doesn't make sense to display errors if we don't stop. Any use case?
	 *       dh> Sure. Local testing (and test cases).
	 *
	 * @param string Short error (no HTML)
	 * @param string Extended description/help for the error (for HTML)
	 * @param string|false Query title; false if {@link DB::last_query} should not get displayed
	 */
	function print_error( $title = '', $html_str = '', $query_title = '' )
	{
		// All errors go to the global error array $EZSQL_ERROR..
		global $EZSQL_ERROR, $is_cli;

		$this->error = true;

		// If no special error string then use mysql default..
		$this->last_error = empty($title) ? ( mysql_error($this->dbhandle).'(Errno='.mysql_errno($this->dbhandle).')' ) : $title;

		// Log this error to the global array..
		$EZSQL_ERROR[] = array(
			'query' => $this->last_query,
			'error_str'  => $this->last_error
		);

		if( ! ( $this->halt_on_error || $this->show_errors ) )
		{ // no reason to generate a nice message:
			return;
		}

		if( $is_cli )
		{ // Clean error message for command line interface:
			$err_msg = "MySQL error! {$this->last_error}\n";
			if( ! empty($this->last_query) && $query_title !== false )
			{
				$err_msg .= "Your query: $query_title\n";
				$err_msg .= $this->format_query( $this->last_query, false );
			}
		}
		else
		{
			$err_msg = '<p class="error">MySQL error!</p>'."\n";
			$err_msg .= "<div><p><strong>{$this->last_error}</strong></p>\n";
			if( !empty($this->last_query) && $query_title !== false )
			{
				$err_msg .= '<p class="error">Your query: '.$query_title.'</p>';
				$err_msg .= '<pre>';
				$err_msg .= $this->format_query( $this->last_query, ! $is_cli );
				$err_msg .= '</pre>';
			}
			$err_msg .= "</div>\n";
		}

		if( $this->halt_on_error )
		{
			if( function_exists( 'debug_die' ) )
			{
				debug_die( $err_msg );
			}
			else
			{
				die( $err_msg );
			}
		}
		elseif ( $this->show_errors )
		{ // If there is an error then take note of it
			echo '<div class="error">';
			echo $err_msg;
			echo '</div>';
		}

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
	}


	/**
	 * Basic Query
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
		if( ! empty($this->dbaliases) )
		{
			// TODO: this should only replace the table name part(s), not the whole query!
			// blueyed> I've changed it to replace in table name parts for UPDATE, INSERT and REPLACE, because
			//          it corrupted serialized data..
			//          IMHO, a cleaner solution would be to use {T_xxx} in the queries and replace it here. In object properties (e.g. DataObject::dbtablename), only "T_xxx" would get used and surrounded by "{..}" in the queries it creates.

			if( preg_match( '~^\s*(UPDATE\s+)(.*?)(\sSET\s.*)$~is', $query, $match ) )
			{ // replace only between UPDATE and SET:
				$query = $match[1].preg_replace( $this->dbaliases, $this->dbreplaces, $match[2] ).$match[3];
			}
			elseif( preg_match( '~^\s*(INSERT|REPLACE\s+)(.*?)(\s(VALUES|SET)\s.*)$~is', $query, $match ) )
			{ // replace only between INSERT|REPLACE and VALUES|SET:
				$query = $match[1].preg_replace( $this->dbaliases, $this->dbreplaces, $match[2] ).$match[3];
			}
			else
			{ // replace in whole query:
				$query = preg_replace( $this->dbaliases, $this->dbreplaces, $query );

				if( ! empty($this->table_options) && preg_match( '#^ \s* create \s* table \s #ix', $query) )
				{ // Query is a table creation, we add table options:
					$query = preg_replace( '~;\s*$~', '', $query ); // remove any ";" at the end
					$query .= ' '.$this->table_options;
				}
			}
		}
		elseif( ! empty($this->table_options) )
		{ // No aliases, but table_options:
			if( preg_match( '#^ \s* create \s* table \s #ix', $query) )
			{ // Query is a table creation, we add table options:
				$query .= $this->table_options;
			}
		}
		// echo '<p>'.$query.'</p>';

		// Keep track of the last query for debug..
		$this->last_query = $query;

		// Perform the query via std mysql_query function..
		$this->num_queries++;

		if( $this->log_queries )
		{	// We want to log queries:
			$this->queries[ $this->num_queries - 1 ] = array(
				'title' => $title,
				'sql' => $query,
				'rows' => -1,
				'time' => 'unknown',
				'results' => 'unknown' );
		}

		if( is_object($Timer) )
		{
			// Resume global query timer
			$Timer->resume( 'sql_queries' );
			// Start a timer for this particular query:
			$Timer->start( 'sql_query', false );

			// Run query:
			$this->result = @mysql_query( $query, $this->dbhandle );

			if( $this->log_queries )
			{	// We want to log queries:
				// Get duration for last query:
				$this->queries[ $this->num_queries - 1 ]['time'] = $Timer->get_duration( 'sql_query', 10 );
			}

			// Pause global query timer:
			$Timer->pause( 'sql_queries' );
		}
		else
		{
			// Run query:
			$this->result = @mysql_query( $query, $this->dbhandle );
		}

		// If there is an error then take note of it..
		if( mysql_error($this->dbhandle) )
		{
			$this->print_error( '', '', $title );
			return false;
		}

		if( preg_match( '#^ \s* (insert|delete|update|replace) \s #ix', $query) )
		{ // Query was an insert, delete, update, replace:

			$this->rows_affected = mysql_affected_rows($this->dbhandle);
			if( $this->log_queries )
			{	// We want to log queries:
				$this->queries[ $this->num_queries - 1 ]['rows'] = $this->rows_affected;
			}

			// Take note of the insert_id
			if ( preg_match("/^\\s*(insert|replace) /i",$query) )
			{
				$this->insert_id = mysql_insert_id($this->dbhandle);
			}

			// Return number of rows affected
			$return_val = $this->rows_affected;
		}
		else
		{ // Query was a select, alter, etc...:
			$num_rows = 0;

			if( is_resource($this->result) )
			{ // It's not a resource for CREATE or DROP for example and can even trigger a fatal error (see http://forums.b2evolution.net//viewtopic.php?t=9529)

				if( $this->log_queries )
				{
					// Take note of column info
					$i = 0;
					while( $i < mysql_num_fields($this->result) )
					{
						$this->col_info[$i] = mysql_fetch_field($this->result);
						$i++;
					}
				}

				// Store Query Results
				while( $row = mysql_fetch_object($this->result) )
				{
					// Store relults as an objects within main array
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}

				mysql_free_result($this->result);
			}

			// Log number of rows the query returned
			$this->num_rows = $num_rows;
			if( $this->log_queries )
			{	// We want to log queries:
				$this->queries[ $this->num_queries - 1 ]['rows'] = $this->num_rows;
			}

			// Return number of rows selected
			$return_val = $this->num_rows;
		}


		if( $this->log_queries )
		{	// We want to log queries:
			if( $this->debug_dump_function_trace_for_queries )
			{
				$this->queries[ $this->num_queries - 1 ]['function_trace'] = debug_get_backtrace( $this->debug_dump_function_trace_for_queries, array( array( 'class' => 'DB' ) ), 1 ); // including first stack entry from class DB
			}
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

			if( $this->log_queries )
			{	// We want to log queries:
				$this->queries[ $this->num_queries - 1 ]['explain'] = $this->debug_get_rows_table( 100, true );
			}

			// Restore:
			$this->last_result = $saved_last_result;
			$this->col_info = $saved_col_info;
			$this->num_rows = $saved_num_rows;
		}


		if( $this->log_queries )
		{	// We want to log queries:
			if( $this->debug_dump_rows )
			{
				$this->queries[ $this->num_queries - 1 ]['results'] = $this->debug_get_rows_table( $this->debug_dump_rows );
			}
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
				: NULL;
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
			$this->print_error('DB::get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N', '', false);
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
	 * Function to get the second column from the cached result indexed by the first column
	 *
	 * @return array [col_0] => col_1
	 */
	function get_assoc( $query = NULL, $title = '' )
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
			$key = $this->get_var( NULL, 0, $i );

			$new_array[$key] = $this->get_var( NULL, 1, $i );
		}

		return $new_array;
	}


	/**
	 * Get a column as comma-separated list.
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
	 *
	 * Note: {@link DB::log_queries} must be enabled for this to work.
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

		if( ! $this->col_info )
		{
			return '<p>No Results.</p>';
		}

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

		return $r;
	}


	/**
	 * Format a SQL query
	 * @static
	 * @todo dh> Steal the code from phpMyAdmin :)
	 * @param string SQL
	 * @param boolean Format with/for HTML?
	 */
	function format_query( $sql, $html = true )
	{
		$sql = str_replace("\t", '  ', $sql );
		if( $html )
		{
			$sql = htmlspecialchars( $sql );
			$replace_prefix = "<br />\n";
		}
		else
		{
			$replace_prefix = "\n";
		}

		$search = array(
			'~(FROM|WHERE|GROUP BY|ORDER BY|LIMIT|VALUES)~',
			'~(AND |OR )~',
			);
		$replace = array(
				$replace_prefix.'$1',
				$replace_prefix.'&nbsp; $1',
			);
		$sql = preg_replace( $search, $replace, $sql );

		return $sql;
	}


	/**
	 * Displays all queries that have been executed
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

		$count_queries = 0;
		$count_rows = 0;

		echo '<strong>DB queries:</strong> '.$this->num_queries."<br />\n";

		foreach( $this->queries as $query )
		{
			$count_queries++;
			echo '<h4>Query #'.$count_queries.': '.$query['title']."</h4>\n";
			echo '<code>';
			echo $this->format_query( $query['sql'] );
			echo "</code>\n";

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
		echo "\n<strong>Total rows:</strong> $count_rows<br />\n";
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


	/**
	 * Set the charset of the connection.
	 *
	 * WARNING: this will fail on MySQL 3.23
	 *
	 * @staticvar array "regular charset => mysql charset map"
	 * @param string Charset
	 * @param boolean Use the "regular charset => mysql charset map"?
	 * @return boolean true on success, false on failure
	 */
	function set_connection_charset( $charset, $use_map = false )
	{
		global $Debuglog;

		/**
		 * This is taken from phpMyAdmin (libraries/select_lang.lib.php).
		 */
		static $mysql_charset_map = array(
				'big5'         => 'big5',
				'cp-866'       => 'cp866',
				'euc-jp'       => 'ujis',
				'euc-kr'       => 'euckr',
				'gb2312'       => 'gb2312',
				'gbk'          => 'gbk',
				'iso-8859-1'   => 'latin1',
				'iso-8859-2'   => 'latin2',
				'iso-8859-7'   => 'greek',
				'iso-8859-8'   => 'hebrew',
				'iso-8859-8-i' => 'hebrew',
				'iso-8859-9'   => 'latin5',
				'iso-8859-13'  => 'latin7',
				'iso-8859-15'  => 'latin1',
				'koi8-r'       => 'koi8r',
				'shift_jis'    => 'sjis',
				'tis-620'      => 'tis620',
				'utf-8'        => 'utf8',
				'windows-1250' => 'cp1250',
				'windows-1251' => 'cp1251',
				'windows-1252' => 'latin1',
				'windows-1256' => 'cp1256',
				'windows-1257' => 'cp1257',
			);

		$charset = strtolower($charset);

		if( $use_map )
		{
			if( ! isset($mysql_charset_map[$charset]) )
			{
				return false;
			}

			$charset = $mysql_charset_map[$charset];
		}

		$r = true;
		if( $charset != $this->connection_charset )
		{
			// SET NAMES is not supported by MySQL 3.23 and for a non-supported charset even not in MySQL 5 probably..

			$save_show_errors = $this->show_errors;
			$save_halt_on_error = $this->halt_on_error;
			$this->show_errors = false;
			$this->halt_on_error = false;
			$last_error = $this->last_error;
			if( $this->query( 'SET NAMES '.$charset ) === false )
			{
				$Debuglog->add( 'Could not "SET NAMES '.$charset.'"! (MySQL error: '.strip_tags($this->last_error).')', 'locale' );

				$r = false;
			}
			else
			{
				$Debuglog->add( 'Set DB connection charset: '.$charset, 'locale' );
			}
			$this->show_errors = $save_show_errors;
			$this->halt_on_error = $save_halt_on_error;
			$this->last_error = $last_error;

			$this->connection_charset = $charset;
		}

		return $r;
	}

}

/*
 * $Log$
 * Revision 1.28  2006/10/28 15:05:25  blueyed
 * CLI/non-HTML support for print_error() and format_query()
 *
 * Revision 1.27  2006/10/14 03:05:59  blueyed
 * MFB: fix
 *
 * Revision 1.26  2006/10/10 21:42:42  blueyed
 * Optimization: only collect $col_info, if $log_queries is enabled. TODO.
 *
 * Revision 1.25  2006/10/10 21:24:29  blueyed
 * Fix for the optimization
 *
 * Revision 1.24  2006/10/10 21:21:40  blueyed
 * Fixed possible SQL error, if table_options get used and theres a semicolon at the end of query; +optimization
 *
 * Revision 1.23  2006/10/10 21:17:42  blueyed
 * Fixed possible fatal error while collecting col_info for CREATE and DROP queries
 *
 * Revision 1.22  2006/08/24 00:36:54  fplanque
 * doc
 *
 * Revision 1.21  2006/08/07 09:34:48  blueyed
 * Removed comment - extended DB class instead
 *
 * Revision 1.20  2006/08/02 23:49:29  blueyed
 * comment
 *
 * Revision 1.19  2006/07/24 01:22:14  blueyed
 * minor/doc
 *
 * Revision 1.18  2006/07/23 22:33:58  blueyed
 * comment
 *
 * Revision 1.17  2006/07/23 22:16:19  fplanque
 * Using MySQL 3.23 is not an "error"
 *
 * Revision 1.16  2006/07/19 19:55:12  blueyed
 * Fixed charset handling (especially windows-1251)
 *
 * Revision 1.15  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.14  2006/06/14 17:24:14  fplanque
 * A little better debug_die()... useful for bozos.
 * Removed bloated trace on error param from DB class. KISS (Keep It Simple Stupid)
 *
 * Revision 1.12  2006/05/31 20:22:34  blueyed
 * cleanup
 *
 * Revision 1.11  2006/05/31 15:04:35  blueyed
 * cleanup
 *
 * Revision 1.10  2006/05/31 14:23:38  blueyed
 * Optimize, if no aliases.
 *
 * Revision 1.9  2006/05/31 13:43:06  blueyed
 * "handle"-param to provide an existing connection-link/-resource from a previous mysql_connect(). This is useful when integrating DB class based functions in another framework, e.g. Typo3.
 *
 * Revision 1.8  2006/05/30 21:53:06  blueyed
 * Replaced $EvoConfig->DB with $db_config
 *
 * Revision 1.7  2006/05/19 17:03:59  blueyed
 * locale activation fix from v-1-8, abstraction of setting DB connection charset
 *
 * Revision 1.6  2006/05/02 22:18:26  blueyed
 * replace aliases not in with values
 *
 * Revision 1.5  2006/04/19 19:44:25  fplanque
 * added get_assoc()
 *
 * Revision 1.4  2006/03/13 19:44:35  fplanque
 * no message
 *
 * Revision 1.3  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/02/23 22:33:58  blueyed
 * doc
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.54  2006/02/13 00:40:25  blueyed
 * new_link and client_flags param for mysql_connect(). Also pass the $dbhandle to every mysql_*() function.
 *
 * Revision 1.53  2006/02/05 19:04:48  blueyed
 * doc fixes
 *
 * Revision 1.52  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.50  2006/01/15 18:56:02  blueyed
 * Made error on loading extension clearer; do not display function backtrace twice with print_error().
 *
 * Revision 1.49  2006/01/11 23:39:19  blueyed
 * Enhanced backtrace-debugging for queries
 *
 * Revision 1.48  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.47  2005/12/12 01:18:04  blueyed
 * Counter for $Timer; ignore absolute times below 0.005s; Fix for Timer::resume().
 *
 * Revision 1.46  2005/12/05 16:04:35  blueyed
 * get_row(): return NULL on empty results for OBJECT-type return value.
 *
 * Revision 1.44  2005/11/18 18:46:27  fplanque
 * factorized query formatting
 *
 * Revision 1.43  2005/11/16 19:34:19  fplanque
 * plug_ID should be unsigned
 *
 * Revision 1.42  2005/11/14 17:23:41  blueyed
 * Moved query timer back around just mysql_query()
 *
 * Revision 1.41  2005/11/05 07:16:51  blueyed
 * Use query timer for whole DB::query(), not just the call to mysql_query therein. Gives a more realistic impression of DB query durations.
 *
 * Revision 1.40  2005/10/31 23:20:45  fplanque
 * keeping things straight...
 *
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