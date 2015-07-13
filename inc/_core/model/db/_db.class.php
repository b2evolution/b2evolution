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
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004 by Justin Vincent - {@link http://php.justinvincent.com}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @package evocore
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


/**
 * The Main Class
 *
 * @package evocore
 */
class DB
{
	/**
	 * Show/Print errors?
	 * @var boolean
	 */
	var $show_errors = true;
	/**
	 * Halt on errors?
	 * @var boolean
	 */
	var $halt_on_error = true;
	/**
	 * Log errors using {@link error_log()}?
	 * There's no reason to disable this, apart from when you are expecting
	 * to get an error, like with {@link get_db_version()}.
	 * @var boolean
	 */
	var $log_errors = true;
	/**
	 * Has an error occured?
	 * @var boolean
	 */
	var $error = false;
	/**
	 * Number of done queries.
	 * @var integer
	 */
	var $num_queries = 0;
	/**
	 * last query SQL string
	 * @var string
	 */
	var $last_query = '';
	/**
	 * last DB error string
	 * @var string
	 */
	var $last_error = '';

	/**
	 * Last insert ID
	 * @var integer
	 */
	var $insert_id = 0;

	/**
	 * Last query's resource
	 * @access protected
	 * @var resource
	 */
	var $result;

	/**
	 * Number of rows in result set
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
	 * @todo dh> If the query itself uses already e.g. "CHARACTER SET latin1" it should not get overridden..
	 * @var string
	 */
	var $table_options = '';

	/**
	 * Use transactions in DB?
	 *
	 * You need to use InnoDB in order to enable this.  See the {@link $db_config "table_options" key}.
	 */
	var $use_transactions = false;

	/**
	 * Which transaction isolation level should be used?
	 *
	 * Possible values in case of MySQL: REPEATABLE READ | READ COMMITTED | READ UNCOMMITTED | SERIALIZABLE
	 * Defailt value is REPEATABLE READ
	 */
	var $transaction_isolation_level = 'REPEATABLE READ';

	/**
	 * How many transactions are currently nested?
	 */
	var $transaction_nesting_level = 0;

	/**
	 * Rememeber if we have to rollback at the end of a nested transaction construct
	 */
	var $rollback_nested_transaction = false;

	/**
	 * MySQL Database handle
	 * @var object
	 */
	var $dbhandle;

	/**
	 * Database username
	 * @var string
	 */
	var $dbuser;

	/**
	 * Database username's password
	 * @var string
	 */
	var $dbpassword;

	/**
	 * Database name
	 * @var string
	 * @see select()
	 */
	var $dbname;

	/**
	 * Database hostname
	 * @var string
	 */
	var $dbhost = 'localhost';

	/**
	 * Current connection charset
	 * @var string
	 * @access protected
	 * @see DB::set_connection_charset()
	 */
	var $connection_charset;


	// DEBUG:

	/**
	 * Do we want to log queries?
	 * If null, it gets set according to {@link $debug}.
	 * A subclass may set it by default (e.g. DbUnitTestCase_DB).
	 * This requires {@link $debug} to be true.
	 * @var boolean
	 */
	var $log_queries;

	/**
	 * Log of queries:
	 * @var array
	 */
	var $queries = array();

	/**
	 * Do we want to explain joins?
	 * This requires {@link DB::$log_queries} to be true.
	 *
	 * @todo fp> we'd probably want to group all the advanced debug vars under a single setting now. We might even auto enable it when $debug=2. (And we might actually want to include a $debug="cookie" mode for easy switching with bookmarks or a bookmarklet)
	 *
	 * @var boolean
	 */
	var $debug_explain_joins = false;

	/**
	 * Do we want to profile queries?
	 * This requires {@link DB::$log_queries} to be true.
	 *
	 * This sets "profiling=1" for the session and queries "SHOW PROFILE" after
	 * each query.
	 *
	 * @var boolean
	 */
	var $debug_profile_queries = false;

	/**
	 * Do we want to output a function backtrace for every query?
	 * Number of stack entries to show (from last to first) (Default: 0); true means 'all'.
	 *
	 * This requires {@link DB::$log_queries} to be true.
	 *
	 * @var integer
	 */
	var $debug_dump_function_trace_for_queries = 0;

	/**
	 * Number of rows we want to dump in debug output (0 disables it)
	 * This requires {@link DB::$log_queries} to be true.
	 * @var integer
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
	 *    - 'handle': a MySQL Database handle (from a previous {@link mysqli_init()})
	 *   Optional:
	 *    - 'name': the name of the default database, see {@link DB::select()}
	 *    - 'host': host of the database; Default: 'localhost'
	 *    - 'show_errors': Display SQL errors? (true/false); Default: don't change member default ({@link $show_errors})
	 *    - 'halt_on_error': Halt on error? (true/false); Default: don't change member default ({@link $halt_on_error})
	 *    - 'table_options': sets {@link $table_options}
	 *    - 'use_transactions': sets {@link $use_transactions}
	 *    - 'aliases': Aliases for tables (array( alias => table name )); Default: no aliases.
	 *    - 'new_link': don't use a persistent connection
	 *    - 'client_flags': optional settings like compression or SSL encryption. See {@link http://www.php.net/manual/en/mysqli.constants.php}.
	 *       (requires PHP 4.3)
	 *    - 'log_queries': should queries get logged internally? (follows $debug by default, and requires it to be enabled otherwise)
	 *      This is a requirement for the following options:
	 *    - 'debug_dump_rows': Number of rows to dump
	 *    - 'debug_explain_joins': Explain JOINS? (calls "EXPLAIN $query")
	 *    - 'debug_profile_queries': Profile queries? (calls "SHOW PROFILE" after each query)
	 *    - 'debug_dump_function_trace_for_queries': Collect call stack for queries? (showing where queries have been called)
	 */
	function __construct( $params )
	{
		global $debug, $evo_charset;

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
		if( isset($params['debug_dump_rows']) ) $this->debug_dump_rows = $params['debug_dump_rows']; // Nb of rows to dump
		if( isset($params['debug_explain_joins']) ) $this->debug_explain_joins = $params['debug_explain_joins'];
		if( isset($params['debug_profile_queries']) ) $this->debug_profile_queries = $params['debug_profile_queries'];
		if( isset($params['debug_dump_function_trace_for_queries']) ) $this->debug_dump_function_trace_for_queries = $params['debug_dump_function_trace_for_queries'];
		if( isset($params['log_queries']) )
		{
			$this->log_queries = $debug && $params['log_queries'];
		}
		elseif( isset($debug) && ! isset($this->log_queries) )
		{ // $log_queries follows $debug and respects subclasses, which may define it:
			$this->log_queries = (bool)$debug;
		}

		if( ! extension_loaded('mysqli') )
		{ // The mysql extension is not loaded, try to dynamically load it:
			if( function_exists('dl') )
			{
				$mysql_ext_file = is_windows() ? 'php_mysqli.dll' : 'mysqli.so';
				$php_errormsg = null;
				$old_track_errors = ini_set('track_errors', 1);
				$old_html_errors = ini_set('html_errors', 0);
				@dl( $mysql_ext_file );
				$error_msg = $php_errormsg;
				if( $old_track_errors !== false ) ini_set('track_errors', $old_track_errors);
				if( $old_html_errors !== false ) ini_set('html_errors', $old_html_errors);
			}
			else
			{
				$error_msg = 'The PHP mysqli extension is not installed and we cannot load it dynamically.';
			}
			if( ! extension_loaded('mysqli') )
			{ // Still not loaded:
				$this->print_error( 'The PHP MySQL Improved module could not be loaded.', '
					<p><strong>Error:</strong> '.$error_msg.'</p>
					<p>You probably have to edit your php configuration (php.ini) and enable this module ('.$mysql_ext_file.').</p>
					<p>Do not forget to restart your webserver (if necessary) after editing the PHP conf.</p>', false );
				return;
			}
		}

		$new_link = isset( $params['new_link'] ) ? $params['new_link'] : false;
		$client_flags = isset( $params['client_flags'] ) ? $params['client_flags'] : 0;

		if( ! $this->dbhandle )
		{ // Connect to the Database:
			// echo "mysqli_real_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $this->dbname, $this->dbport, $this->dbsocket, $client_flags  )";
			// mysqli::$connect_error is tied to an established connection
			// if the connection fails we need a different method to get the error message
			$php_errormsg = null;
			$old_track_errors = ini_set('track_errors', 1);
			$old_html_errors = ini_set('html_errors', 0);
			$this->dbhandle = mysqli_init();
			@mysqli_real_connect($this->dbhandle,
				/* Persistent connections are only available in PHP 5.3+ */
				($new_link || version_compare(PHP_VERSION, '5.3', '<')) ? $this->dbhost : 'p:'.$this->dbhost,
				$this->dbuser, $this->dbpassword,
				'', ini_get('mysqli.default_port'), ini_get('mysqli.default_socket'),
				$client_flags );
			$mysql_error = $php_errormsg;
			if( $old_track_errors !== false ) ini_set('track_errors', $old_track_errors);
			if( $old_html_errors !== false ) ini_set('html_errors', $old_html_errors);
		}

		if( 0 != $this->dbhandle->connect_errno )
		{
			$this->print_error( 'Error establishing a database connection!',
				( $mysql_error ? '<p>('.$mysql_error.')</p>' : '' ).'
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

		if( ! empty( $params['connection_charset'] ) )
		{ // Specify which charset we are using on the client:
			$this->set_connection_charset( $params['connection_charset'] );
		}
		elseif( ! empty( $evo_charset ) )
		{ // Use the internal charset if it is defined
			$this->set_connection_charset( DB::php_to_mysql_charmap( $evo_charset ) );
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

		if( $debug )
		{ // Force MySQL strict mode
			// TRADITIONAL mode is only available to mysql > 5.0.2
			$mysql_version = $this->get_version( 'we do this in DEBUG mode only' );
			if( version_compare( $mysql_version, '5.0.2' ) > 0 )
			{
				$this->query( 'SET sql_mode = "TRADITIONAL"', 'we do this in DEBUG mode only' );
			}
		}

		if( $this->debug_profile_queries )
		{
			// dh> this will fail, if it is not supported, but has to be enabled manually anyway.
			$this->query('SET profiling = 1'); // Requires 5.0.37.
		}
	}

	function __destruct()
	{
		@$this->flush();
		@mysqli_close($this->dbhandle);
	}


	/**
	 * Select a DB (if another one needs to be selected)
	 */
	function select($db)
	{
		if( !@mysqli_select_db($this->dbhandle, $db) )
		{
			$this->print_error( 'Error selecting database ['.$db.']!', '
				<ol>
					<li>Are you sure the database exists?</li>
					<li>Are you sure the DB user is allowed to use that database?</li>
					<li>Are you sure there is a valid database connection?</li>
				</ol>', false );
		}
		$this->dbname = $db;
	}


	/**
	 * Escapes text for SQL LIKE special characters % and _
	 */
	function like_escape($str)
	{
		$str = str_replace( array('%', '_'), array('\\%', '\\_'), $str );
		return $this->escape($str);
	}


	/**
	 * Format a string correctly for safe insert under all PHP conditions
	 */
	function escape($str)
	{
		return mysqli_real_escape_string($this->dbhandle, $str);
	}


	/**
	 * Quote a value, either in single quotes (and escaped) or if it's NULL as 'NULL'.
	 *
	 * @param string|array|null
	 * @return string Quoted (and escaped) value or 'NULL'.
	 */
	function quote($str)
	{
		if( is_null( $str ) )
		{
			return 'NULL';
		}
		elseif( is_array( $str ) )
		{
			$r = '';
			foreach( $str as $elt )
			{
				$r .= $this->quote($elt).',';
			}
			return substr( $r, 0, -1 );
		}
		else
		{
			return "'".$this->escape($str)."'";
		}
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
	 * Print SQL/DB error.
	 *
	 * TODO: fp> bloated: it probably doesn't make sense to display errors if we don't stop. Any use case?
	 *       dh> Sure. Local testing (and test cases).
	 *
	 * @param string Short error (no HTML)
	 * @param string Extended description/help for the error (for HTML)
	 * @param string|false Query title; false if {@link DB::$last_nuery} should not get displayed
	 */
	function print_error( $title = '', $html_str = '', $query_title = '' )
	{
		// All errors go to the global error array $EZSQL_ERROR..
		global $EZSQL_ERROR, $is_cli;

		$this->error = true;

		// If no special error string then use mysql default..
		if( ! strlen($title) )
		{
			if( is_object($this->dbhandle) )
			{ // use mysqli_error:
				$this->last_error = $this->dbhandle->error.'(Errno='.$this->dbhandle->errno.')';
			}
			else
			{
				$this->last_error = 'Unknown (and no $dbhandle available)';
			}
		}
		else
		{
			$this->last_error = $title;
		}

		// Log this error to the global array..
		$EZSQL_ERROR[] = array(
			'query' => $this->last_query,
			'error_str'  => $this->last_error
		);


		// Send error to PHP's system logger.
		if( $this->log_errors )
		{
			// TODO: dh> respect $log_app_errors? Create a wrapper, e.g. evo_error_log, which can be used later to write into e.g. a DB table?!
			if( isset($_SERVER['REQUEST_URI']) )
			{
				$req_url = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ? 'https://' : 'http://' )
					.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}
			else
			{
				$req_url = '-';
			}
			$error_text = 'SQL ERROR: '. $this->last_error
					. ', QUERY: "'.trim($this->last_query).'"'
					. ', BACKTRACE: '.trim(strip_tags(debug_get_backtrace()))
					. ', URL: '.$req_url;
			error_log( preg_replace( '#\s+#', ' ', $error_text ) );
		}


		if( ! ( $this->halt_on_error || $this->show_errors ) )
		{ // no reason to generate a nice message:
			return;
		}

		if( $this->halt_on_error && ! $this->show_errors )
		{ // do not show errors, just die:
			die();
		}

		if( $is_cli )
		{ // Clean error message for command line interface:
			$err_msg = "MySQL error!\n{$this->last_error}\n";
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
			$err_msg .= $html_str;
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
			if( function_exists('debug_die') )
			{
				debug_die( $err_msg );
			}
			else
			{
				die( $err_msg );
			}
		}
		elseif( $this->show_errors )
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
		if( isset($this->result) && is_object($this->result) )
		{ // Free last result resource
			mysqli_free_result($this->result);
		}
		$this->result = NULL;
		$this->last_query = NULL;
		$this->num_rows = 0;
	}


	/**
	 * Get MYSQL version
	 */
	function get_version( $query_title = NULL )
	{
		if( isset( $this->version ) )
		{
			return $this->version;
		}

		$this->save_error_state();
		// Blatantly ignore any error generated by potentially unknown function...
		$this->show_errors = false;
		$this->halt_on_error = false;

		if( ($this->version_long = $this->get_var( 'SELECT VERSION()', 0, 0, $query_title ) ) === NULL )
		{	// Very old version ( < 4.0 )
			$this->version = '';
			$this->version_long = '';
		}
		else
		{
			$this->version = preg_replace( '~-.*~', '', $this->version_long );
		}
		$this->restore_error_state();

		return $this->version;
	}


	/**
	 * Save the vars responsible for error handling.
	 * This can be chained.
	 * @see DB::restore_error_state()
	 */
	function save_error_state()
	{
		$this->saved_error_states[] = array(
			'show_errors'   => $this->show_errors,
			'halt_on_error' => $this->halt_on_error,
			'last_error'    => $this->last_error,
			'error'         => $this->error,
			'log_errors'    => $this->log_errors,
		);
	}

	/**
	 * Call this after {@link save_halt_on_error()} to
	 * restore the previous error state.
	 * This can be chained.
	 * @see DB::save_error_state()
	 */
	function restore_error_state()
	{
		if( empty($this->saved_error_states)
			|| ! is_array($this->saved_error_states) )
		{
			return false;
		}
		$state = array_pop($this->saved_error_states);

		foreach( $state as $k => $v )
			$this->$k = $v;
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

		// Replace aliases:
		if( ! empty($this->dbaliases) )
		{
			// TODO: this should only replace the table name part(s), not the whole query!
			// blueyed> I've changed it to replace in table name parts for UPDATE, INSERT and REPLACE, because
			//          it corrupted serialized data..
			//          IMHO, a cleaner solution would be to use {T_xxx} in the queries and replace it here. In object properties (e.g. DataObject::$dbtablename), only "T_xxx" would get used and surrounded by "{..}" in the queries it creates.

			if( preg_match( '~^\s*(UPDATE\s+)(.*?)(\sSET\s.*)$~is', $query, $match ) )
			{ // replace only between UPDATE and SET, but check subqueries:
				$subquery_result = '';
				while( preg_match( '~^(.*SELECT.*FROM\s+)(.*?)(\s.*)$~is', $match[3], $subquery_match ) )
				{ // replace in subquery
					$match[3] = $subquery_match[1];
					$subquery_result = preg_replace( $this->dbaliases, $this->dbreplaces, $subquery_match[2] ).$subquery_match[3].$subquery_result;
				}
				$match[3] = $match[3].$subquery_result;
				if( preg_match( '~^(.*SELECT.*JOIN\s+)(.*?)(\s.*)$~is', $match[3], $subquery_match ) )
				{ // replace in whole subquery, there can be any number of JOIN:
					$match[3] = preg_replace( $this->dbaliases, $this->dbreplaces, $match[3] );
				}
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
				$query = preg_replace( '~;\s*$~', '', $query ); // remove any ";" at the end
				$query .= $this->table_options;
			}
		}
		// echo '<p>'.$query.'</p>';

		// Keep track of the last query for debug..
		$this->last_query = $query;

		// Perform the query via std mysqli_query function..
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
			$Timer->resume( 'SQL QUERIES' , false );
			// Start a timer for this particular query:
			$Timer->start( 'sql_query', false );

			// Run query:
			$this->result = @mysqli_query( $this->dbhandle, $query );

			if( $this->log_queries )
			{	// We want to log queries:
				// Get duration for last query:
				$this->queries[ $this->num_queries - 1 ]['time'] = $Timer->get_duration( 'sql_query', 10 );
			}

			// Pause global query timer:
			$Timer->pause( 'SQL QUERIES' , false );
		}
		else
		{
			// Run query:
			$this->result = @mysqli_query( $this->dbhandle, $query );
		}

		// If there is an error then take note of it..
		if( is_object( $this->dbhandle ) && $this->dbhandle->errno != 0 )
		{
			if( is_object( $this->result ) )
			{
				mysqli_free_result($this->result);
			}
			$last_errno = $this->dbhandle->errno;
			if( $this->use_transactions && ( $this->transaction_isolation_level == 'SERIALIZABLE' ) && ( 1213 == $last_errno ) )
			{ // deadlock exception occured, transaction must be rolled back
				$this->rollback_nested_transaction = true;
				return false;
			}
			$this->print_error( '', '', $title );
			return false;
		}

		if( preg_match( '#^\s*(INSERT|DELETE|UPDATE|REPLACE)\s#i', $query, $match ) )
		{ // Query was an insert, delete, update, replace:

			$this->rows_affected = mysqli_affected_rows($this->dbhandle);
			if( $this->log_queries )
			{	// We want to log queries:
				$this->queries[ $this->num_queries - 1 ]['rows'] = $this->rows_affected;
			}

			// Take note of the insert_id, for INSERT and REPLACE:
			$match[1] = strtoupper($match[1]);
			if( $match[1] == 'INSERT' || $match[1] == 'REPLACE' )
			{
				$this->insert_id = mysqli_insert_id($this->dbhandle);
			}

			// Return number of rows affected
			$return_val = $this->rows_affected;
		}
		else
		{ // Query was a select, alter, etc...:
			if( is_object($this->result) )
			{ // It's not a resource for CREATE or DROP for example and can even trigger a fatal error (see http://forums.b2evolution.net//viewtopic.php?t=9529)
				$this->num_rows = mysqli_num_rows($this->result);
			}

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

			if( $this->debug_dump_rows && $this->num_rows )
			{
				$this->queries[ $this->num_queries - 1 ]['results'] = $this->debug_get_rows_table( $this->debug_dump_rows );
			}

			// Profile queries
			if( $this->debug_profile_queries )
			{
				// save values:
				$saved_last_result = $this->result;
				$saved_num_rows = $this->num_rows;

				$this->num_rows = 0;

				$this->result = @mysqli_query( $this->dbhandle, 'SHOW PROFILE' );
				$this->num_rows = mysqli_num_rows($this->result);

				if( $this->num_rows )
				{
					$this->queries[$this->num_queries-1]['profile'] = $this->debug_get_rows_table( 100, true );

					// Get time information from PROFILING table (which corresponds to "SHOW PROFILE")
					$this->result = mysqli_query( $this->dbhandle, 'SELECT FORMAT(SUM(DURATION), 6) AS DURATION FROM INFORMATION_SCHEMA.PROFILING GROUP BY QUERY_ID ORDER BY QUERY_ID DESC LIMIT 1' );
					$this->queries[$this->num_queries-1]['time_profile'] = array_shift(mysqli_fetch_row($this->result));
				}

				// Free "PROFILE" result resource:
				mysqli_free_result($this->result);


				// Restore:
				$this->result = $saved_last_result;
				$this->num_rows = $saved_num_rows;
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
	 * @param string Optional query to execute
	 * @param integer Column number (starting at and defaulting to 0)
	 * @param integer Row (defaults to NULL for "next"/"do not seek")
	 * @param string Optional title of query
	 * @return mixed NULL if not found, the value otherwise (which may also be NULL).
	 */
	function get_var( $query = NULL, $x = 0, $y = NULL, $title = '' )
	{
		// If there is a query then perform it if not then use cached results..
		if( $query )
		{
			$this->query($query, $title);
		}

		if( $this->num_rows
			&& ( $y === NULL || mysqli_data_seek($this->result, $y) ) )
		{
			$row = mysqli_fetch_row($this->result);

			if( isset($row[$x]) )
			{
				return $row[$x];
			}
		}

		return NULL;
	}


	/**
	 * Get one row from the DB.
	 *
	 * @param string Query (or NULL for previous query)
	 * @param string Output type ("OBJECT", "ARRAY_A", "ARRAY_N")
	 * @param int Row to fetch (or NULL for next - useful with $query=NULL)
	 * @param string Optional title for $query (if any)
	 * @return mixed
	 */
	function get_row( $query = NULL, $output = OBJECT, $y = NULL, $title = '' )
	{
		// If there is a query then perform it if not then use cached results..
		if( $query )
		{
			$this->query($query, $title);
		}

		if( ! $this->num_rows
			|| ( isset($y) && ! mysqli_data_seek($this->result, $y) ) )
		{
			if( $output == OBJECT )
				return NULL;
			else
				return array();
		}

		// If the output is an object then return object using the row offset..
		switch( $output )
		{
		case OBJECT:
			return mysqli_fetch_object($this->result);

		case ARRAY_A:
			return mysqli_fetch_array($this->result, MYSQLI_ASSOC);

		case ARRAY_N:
			return mysqli_fetch_array($this->result, MYSQLI_NUM);

		default:
			$this->print_error('DB::get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N', '', false);
			break;
		}
	}


	/**
	 * Function to get 1 column from the cached result set based on X index
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
		for( $i = 0; $i < $this->num_rows; $i++ )
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
		for( $i = 0; $i < $this->num_rows; $i++ )
		{
			$key = $this->get_var( NULL, 0, $i );

			$new_array[$key] = $this->get_var( NULL, 1, $i );
		}

		return $new_array;
	}


	/**
	 * Return the the query as a result set - see docs for more details
	 *
	 * @return mixed
	 */
	function get_results( $query = NULL, $output = OBJECT, $title = '' )
	{
		// If there is a query then perform it if not then use cached results..
		if( $query )
		{
			$this->query($query, $title);
		}

		$r = array();

		if( $this->num_rows )
		{
			mysqli_data_seek($this->result, 0);
			switch( $output )
			{
			case OBJECT:
				while( $row = mysqli_fetch_object($this->result) )
				{
					$r[] = $row;
				}
				break;

			case ARRAY_A:
				while( $row = mysqli_fetch_array($this->result, MYSQLI_ASSOC) )
				{
					$r[] = $row;
				}
				break;

			case ARRAY_N:
				while( $row = mysqli_fetch_array($this->result, MYSQLI_NUM) )
				{
					$r[] = $row;
				}
				break;
			}
		}
		return $r;
	}


	/**
	 * Get a table (or "<p>No Results.</p>") for the SELECT query results.
	 *
	 * @return string HTML table or "No Results" if the
	 */
	function debug_get_rows_table( $max_lines, $break_at_comma = false )
	{
		$r = '';

		if( ! $this->result || ! $this->num_rows )
		{
			return '<p>No Results.</p>';
		}

		// Get column info:
		$col_info = array();
		$n = mysqli_num_fields($this->result);
		$i = 0;
		while( $i < $n )
		{
			$col_info[$i] = mysqli_fetch_field($this->result);
			$i++;
		}

		// =====================================================
		// Results top rows
		$r .= '<table cellspacing="0" summary="Results for query"><tr>';
		for( $i = 0, $count = count($col_info); $i < $count; $i++ )
		{
			$r .= '<th><span class="type">'.$col_info[$i]->type.' '.$col_info[$i]->max_length.'</span><br />'
						.$col_info[$i]->name.'</th>';
		}
		$r .= '</tr>';


		// ======================================================
		// print main results
		$i=0;
		// fp> TODO: this should NOT try to print binary fields, eg: file hashes in the files table
		// Rewind to first row (should be there already).
		mysqli_data_seek($this->result, 0);
		while( $one_row = $this->get_row(NULL, ARRAY_N) )
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
					$r .= strmaxlen($item, 50, NULL, 'htmlspecialchars');
				}
				$r .= '</td>';
			}

			$r .= '</tr>';
		}
		// Rewind to first row again.
		mysqli_data_seek($this->result, 0);
		if( $i >= $max_lines )
		{
			$r .= '<tr><td colspan="'.(count($col_info)+1).'">Max number of dumped rows has been reached.</td></tr>';
		}

		$r .= '</table>';

		return $r;
	}


	/**
	 * Format a SQL query
	 *
	 * @param string SQL
	 * @param boolean Format with/for HTML?
	 */
	static function format_query( $sql, $html = true, $maxlen = NULL )
	{
		$sql = trim( str_replace("\t", '  ', $sql ) );
		if( $maxlen )
		{
			$sql = strmaxlen($sql, $maxlen, '...');
		}

		$new = '';
		$word = '';
		$in_comment = false;
		$in_literal = false;
		for( $i = 0, $n = strlen($sql); $i < $n; $i++ )
		{
			$c = $sql[$i];
			if( $in_comment )
			{
				if( $in_comment === '/*' && substr($sql, $i, 2) == '*/' )
					$in_comment = false;
				elseif( $c == "\n" )
					$in_comment = false;
			}
			elseif( $in_literal )
			{
				if( $c == $in_literal )
					$in_literal = false;
			}
			elseif( $c == '#' || ($c == '-' && substr($sql, $i, 3) == '-- ') )
			{
				$in_comment = true;
			}
			elseif( ctype_space($c) )
			{
				$uword = strtoupper($word);
				if( in_array($uword, array('SELECT', 'FROM', 'WHERE', 'GROUP', 'ORDER', 'LIMIT', 'VALUES', 'AND', 'OR', 'LEFT', 'RIGHT', 'INNER')) )
				{
					$new = rtrim($new)."\n".str_pad($word, 6, ' ', STR_PAD_LEFT).' ';
					# Remove any trailing whitespace after keywords
					while( ctype_space($sql[$i+1]) ) {
						++$i;
					}
				}
				else
				{
					$new .= $word.$c;
				}
				$word = '';
				continue;
			}
			$word .= $c;
		}
		$sql = trim($new.$word);

		if( $html )
		{ // poor man's indent
			$sql = preg_replace_callback("~^(\s+)~m", create_function('$m', 'return str_replace(" ", "&nbsp;", $m[1]);'), $sql);
			$sql = nl2br($sql);
		}
		return $sql;
	}


	/**
	 * Displays all queries that have been executed
	 *
	 * @param boolean Use HTML.
	 */
	function dump_queries( $html = true )
	{
		if ( $html )
		{
			echo '<strong>DB queries:</strong> '.$this->num_queries."<br />\n";
		}
		else
		{
			echo 'DB queries: '.$this->num_queries."\n\n";
		}

		if( ! $this->log_queries )
		{ // nothing more to do here..
			return;
		}

		global $Timer;
		if( is_object( $Timer ) )
		{
			$time_queries = $Timer->get_duration( 'SQL QUERIES' , 4 );
		}
		else
		{
			$time_queries = 0;
		}

		$count_queries = 0;
		$count_rows = 0;
		$time_queries_profiled = 0;

		if( $html )
		{ // Javascript function to toggle DIVs (EXPLAIN, results, backtraces).
			require_js( 'debug.js', 'rsc_url', false, true );
		}

		foreach( $this->queries as $i => $query )
		{
			$count_queries++;

			$get_md5_query = create_function( '', '
				static $r; if( isset($r) ) return $r;
				global $query;
				$r = md5(serialize($query))."-".rand();
				return $r;' );

			if ( $html )
			{
				echo '<h4>Query #'.$count_queries.': '.$query['title']."</h4>\n";

				$div_id = 'db_query_sql_'.$i.'_'.$get_md5_query();
				if( strlen($query['sql']) > 512 )
				{
					$sql_short = DB::format_query( $query['sql'], true, 512 );
					$sql = DB::format_query( $query['sql'], true );

					echo '<code id="'.$div_id.'" style="display:none">'.$sql_short.'</code>';
					echo '<code id="'.$div_id.'_full">'.$sql.'</code>';
					echo '<script type="text/javascript">debug_onclick_toggle_div("'.$div_id.','.$div_id.'_full", "Show less", "Show more", false);</script>';
				}
				else
				{
					echo '<code>'.DB::format_query( $query['sql'] ).'</code>';
				}
				echo "\n";
			}
			else
			{
				echo '= Query #'.$count_queries.': '.$query['title']." =\n";
				echo DB::format_query( $query['sql'], false )."\n\n";
			}

			// Color-Format duration: long => red, fast => green, normal => black
			if( $query['time'] > $this->query_duration_slow )
			{
				$style_time_text = 'color:red;font-weight:bold;';
				$style_time_graph = 'background-color:red;';
				$plain_time_text = ' [slow]';
			}
			elseif( $query['time'] < $this->query_duration_fast )
			{
				$style_time_text = 'color:green;';
				$style_time_graph = 'background-color:green;';
				$plain_time_text = ' [fast]';
			}
			else
			{
				$style_time_text = '';
				$style_time_graph = 'background-color:black;';
				$plain_time_text = '';
			}

			// Number of rows with time (percentage and graph, if total time available)
			if ( $html )
			{
				echo '<div class="query_info">';
				echo 'Rows: '.$query['rows'];

				echo ' &ndash; Time: ';
			}
			else
			{
				echo 'Rows: '.$query['rows'].' - Time: ';
			}

			if( $html && $style_time_text )
			{
				echo '<span style="'.$style_time_text.'">';
			}
			echo number_format( $query['time'], 4 ).'s';

			if( $time_queries > 0 )
			{ // We have a total time we can use to calculate percentage:
				echo ' ('.number_format( 100/$time_queries * $query['time'], 2 ).'%)';
			}

			if( isset($query['time_profile']) )
			{
				echo ' (real: '.number_format($query['time_profile'], 4).'s)';
				$time_queries_profiled += $query['time_profile'];
			}

			if( $style_time_text || $plain_time_text )
			{
				echo $html ? '</span>' : $plain_time_text;
			}

			if( $time_queries > 0 )
			{ // We have a total time we can use to display a graph/bar:
				$perc = round( 100/$time_queries * $query['time'] );

				if ( $html )
				{
					echo '<div style="margin:0; padding:0; height:12px; width:'.$perc.'%;'.$style_time_graph.'"></div>';
				}
				else
				{	// display an ASCII bar
					printf( "\n".'[%-50s]', str_repeat( '=', $perc / 2 ) );
				}
			}
			echo $html ? '</div>' : "\n\n";

			// EXPLAIN JOINS ??
			if( $this->debug_explain_joins && preg_match( '#^ [\s(]* SELECT \s #ix', $query['sql']) )
			{ // Query was a select, let's try to explain joins...

				$this->result = mysqli_query( $this->dbhandle, 'EXPLAIN '.$query['sql'] );
				if( is_object($this->result) )
				{
					$this->num_rows = mysqli_num_rows($this->result);

					if( $html )
					{
						$div_id = 'db_query_explain_'.$i.'_'.$get_md5_query();
						echo '<div id="'.$div_id.'">';
						echo $this->debug_get_rows_table( 100, true );
						echo '</div>';
						echo '<script type="text/javascript">debug_onclick_toggle_div("'.$div_id.'", "Show EXPLAIN", "Hide EXPLAIN");</script>';
					}
					else
					{ // TODO: dh> contains html.
						echo $this->debug_get_rows_table( 100, true );
					}
				}
				mysqli_free_result($this->result);
			}

			// Profile:
			if( isset($query['profile']) )
			{
				if( $html )
				{
					$div_id = 'db_query_profile_'.$i.'_'.$get_md5_query();
					echo '<div id="'.$div_id.'">';
					echo $query['profile'];
					echo '</div>';
					echo '<script type="text/javascript">debug_onclick_toggle_div("'.$div_id.'", "Show PROFILE", "Hide PROFILE");</script>';
				}
				else
				{ // TODO: dh> contains html.
					echo $this->debug_get_rows_table( 100, true );
				}
			}

			// Results:
			if( $query['results'] != 'unknown' )
			{
				if( $html )
				{
					$div_id = 'db_query_results_'.$i.'_'.$get_md5_query();
					echo '<div id="'.$div_id.'">';
					echo $query['results'];
					echo '</div>';
					echo '<script type="text/javascript">debug_onclick_toggle_div("'.$div_id.'", "Show results", "Hide results");</script>';
				}
				else
				{ // TODO: dh> contains html.
					echo $query['results'];
				}
			}

			// Function trace:
			if( isset($query['function_trace']) )
			{
				if( $html )
				{
					$div_id = 'db_query_backtrace_'.$i.'_'.$get_md5_query();
					echo '<div id="'.$div_id.'">';
					echo $query['function_trace'];
					echo '</div>';
					echo '<script type="text/javascript">debug_onclick_toggle_div("'.$div_id.'", "Show function trace", "Hide function trace");</script>';
				}
				else
				{ // TODO: dh> contains html.
					echo $query['function_trace'];
				}
			}

			echo $html ? '<hr />' : "=============================================\n";

			$count_rows += $query['rows'];
		}

		$time_queries_profiled = number_format($time_queries_profiled, 4);
		$time_diff_percentage = $time_queries_profiled != 0 ? round($time_queries / $time_queries_profiled * 100) : false;
		if ( $html )
		{
			echo "\nTotal rows: $count_rows<br />\n";
			echo "\nMeasured time: {$time_queries}s<br />\n";
			echo "\nProfiled time: {$time_queries_profiled}s<br />\n";
			if( $time_diff_percentage !== false )
			{
				echo "\nTime difference: {$time_diff_percentage}%<br />\n";
			}
		}
		else
		{
			echo 'Total rows: '.$count_rows."\n";
			echo "Measured time: {$time_queries}s\n";
			echo "Profiled time: {$time_queries_profiled}s\n";
			if( $time_diff_percentage !== false )
			{
				echo "Time difference: {$time_diff_percentage}%\n";
			}
		}
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
	 * Note 3: The default isolation level is REPEATABLE READ (Default for InnoDB)
	 *
	 * - REPEATABLE READ: (most frequent use) several SELECTs in the same transaction will always return identical values
	 * - READ COMMITTED: no good use?
	 * - READ UNCOMMITED: dirty reads - no good use?
	 * - SERIALIZABLE: (less frequent use) all SELECTs are automatically changed to SELECT .. LOCK IN SHARE MODE
	 * IMPORTANT: SERIALIZABLE does NOT use the max isolation level which would be SELECT ... LOCK FOR UPDATE which you cna only do by manually changing the SELECTs
	 * ex: SELECT counter_field FROM child_codes FOR UPDATE;
	 *     UPDATE child_codes SET counter_field = counter_field + 1;
	 */
	function begin( $transaction_isolation_level = 'REPEATABLE READ' )
	{
		if( !$this->use_transactions )
		{ // don't use transactions at all
			return;
		}

		$transaction_isolation_level = strtoupper( $transaction_isolation_level );
		if( !in_array( $transaction_isolation_level, array( 'REPEATABLE READ', 'READ COMMITTED', 'READ UNCOMMITTED', 'SERIALIZABLE' ) ) )
		{
			debug_die( 'Invalid transaction isolation level!' );
		}

		if( ( $this->transaction_isolation_level != $transaction_isolation_level ) && ( ( !$this->transaction_nesting_level ) || ( $transaction_isolation_level == 'SERIALIZABLE' ) ) )
		{ // The isolation level was changed and it is the beggining of a new transaction or this is a nested transaction but it needs 'SERIALIZABLE' isolation level
			// Note: We change the transaction isolation level for nested transactions only if the requested isolation level is 'SERIALIZABLE'
			// Set session transaction isolation level to the new value
			$this->transaction_isolation_level = $transaction_isolation_level;
			$this->query( 'SET SESSION TRANSACTION ISOLATION LEVEL '.$transaction_isolation_level, 'Set transaction isolation level' );
		}

		if( !$this->transaction_nesting_level )
		{ // Start a new transaction
			$this->query( 'BEGIN', 'BEGIN transaction' );
		}

		$this->transaction_nesting_level++;
	}


	/**
	 * Commit current transaction
	 *
	 * @return boolean true on success, false otherwise - when a nested transaction called rollback
	 */
	function commit()
	{
		if( !$this->use_transactions )
		{ // don't use transactions at all
			return true;
		}

		$result = true;
		if( $this->transaction_nesting_level == 1 )
		{ // Only COMMIT if there are no remaining nested transactions:
			if( $this->rollback_nested_transaction )
			{
				$this->query( 'ROLLBACK', 'ROLLBACK transaction because there was a failure somewhere in the nesting of transactions' );
				$result = false;
			}
			else
			{
				$this->query( 'COMMIT', 'COMMIT transaction' );
			}
			$this->rollback_nested_transaction = false;
		}

		if( $this->transaction_nesting_level )
		{ // decrease transaction nesting level
			$this->transaction_nesting_level--;
		}

		return $result;
	}


	/**
	 * Rollback current transaction
	 */
	function rollback()
	{
		if( !$this->use_transactions )
		{ // don't use transactions at all
			return;
		}

		if( $this->transaction_nesting_level == 1 )
		{ // Only ROLLBACK if there are no remaining nested transactions:
			$this->query( 'ROLLBACK', 'ROLLBACK transaction' );
			$this->rollback_nested_transaction = false;
		}
		else
		{ // Remember we'll have to roll back at the end!
			$this->rollback_nested_transaction = true;
		}
		if( $this->transaction_nesting_level )
		{
			$this->transaction_nesting_level--;
		}
	}


	/**
	 * Check if some nesed transaction failed
	 */
	function has_failed_transaction()
	{
		return $this->rollback_nested_transaction;
	}


	/**
	 * Convert a PHP charset to its MySQL equivalent.
	 *
	 * @param string PHP charset
	 * @return string MYSQL charset or unchanged
	 */
	static function php_to_mysql_charmap( $php_charset )
	{
		$php_charset = strtolower( $php_charset );

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

		if( isset( $mysql_charset_map[ $php_charset ] ) )
		{
			return $mysql_charset_map[ $php_charset ];
		}

		// for lack of a better answer:
		return $php_charset;
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
	function set_connection_charset( $charset, $use_map = true )
	{
		global $Debuglog;

		// pre_dump( 'set_connection_charset', $charset );

		$charset = strtolower($charset);

		if( $use_map )
		{	// We want to use the map
			$charset = self::php_to_mysql_charmap( $charset );
		}

		$r = true;
		if( $charset != $this->connection_charset )
		{
			$save_show_errors = $this->show_errors;
			$save_halt_on_error = $this->halt_on_error;
			$this->show_errors = false;
			$this->halt_on_error = false;
			$last_error = $this->last_error;
			$error = $this->error;
			if( $this->dbhandle->errno != 0 || $this->dbhandle->set_charset($charset) === false )
			{
				$Debuglog->add( 'Could not set DB connection charset: '.$charset.'"! (MySQL error: '.strip_tags($this->last_error).')', 'locale' );

				$r = false;
			}
			else
			{
				$Debuglog->add( 'Set DB connection charset: '.$charset, 'locale' );

				$this->connection_charset = $charset;
			}
			$this->show_errors = $save_show_errors;
			$this->halt_on_error = $save_halt_on_error;
			// Blatantly ignore any error generated by mysqli::set_charset...
			$this->last_error = $last_error;
			$this->error = $error;
		}

		return $r;
	}

}

?>