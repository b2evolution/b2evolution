<?php
/**
 * This file implements the DB class.
 *
 * Based on ezSQL - Class to make it very easy to deal with MySQL database connections.
 * b2evo Additions:
 * - symbolic table names
 * - query log
 * - get_list
 * - dynamic extension loading
 * and more...
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Justin Vincent - {@link http://php.justinvincent.com}
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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

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
	var $trace = false;      // same as $debug_all
	var $debug_all = false;  // same as $trace
	var $show_errors = true;
	var $halt_on_error = true;
	var $error = false;		// no error yet
	var $num_queries = 0;
	var $last_query = '';		// last query SQL string
	var $last_error = '';			// last DB error string
	var $col_info;
	var $debug_called;
	var $vardump_called;
	var $insert_id = 0;
	var $num_rows = 0;
	var $rows_affected = 0;
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
	 */
	var $dbtableoptions;


	/**
	 * DB Constructor
	 *
	 * connects to the server and selects a database
	 */
	function DB( $dbuser, $dbpassword, $dbname, $dbhost, $dbaliases, $dbtableoptions = '', $halt_on_error = true )
	{
		$this->halt_on_error = $halt_on_error;

		if( !extension_loaded('mysql') )
		{	// The mysql extension is not loaded, try to dynamically load it:
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
		$this->dbh = @mysql_connect($dbhost,$dbuser,$dbpassword);

		if( ! $this->dbh )
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
			$this->select($dbname);
		}

		// Prepare aliases for replacements:
		foreach( $dbaliases as $dbalias => $dbreplace )
		{
			$this->dbaliases[] = '#\b'.$dbalias.'\b#'; // \b = word boundary
			$this->dbreplaces[] = $dbreplace;
			// echo '<br />'.'#\b'.$dbalias.'\b#';
		}
		// echo count($this->dbaliases);


		$this->dbtableoptions = $dbtableoptions;
	}


	/**
	 * Select a DB (if another one needs to be selected)
	 */
	function select($db)
	{
		if ( !@mysql_select_db($db,$this->dbh))
		{
			$this->print_error( '<strong>'.sprintf( T_('Error selecting database [%s]!'), $db ).'</strong>
				<ol>
					<li>Are you sure the database exists?</li>
					<li>Are you sure there is a valid database connection?</li>
				</ol>' );
		}
	}


	/**
	 * Format a string correctly for safe insert under all PHP conditions
	 */
	function escape($str)
	{
		return mysql_real_escape_string($str);
	}


	function quote($str)
	{
		if( $str === NULL )
			return 'NULL';
		else
			return "'".mysql_real_escape_string($str)."'";
	}


	function null($val)
	{
		if( $val === NULL )
			return 'NULL';
		else
			return $val;
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
	function print_error( $str = '' )
	{
		// All errors go to the global error array $EZSQL_ERROR..
		global $EZSQL_ERROR;

		$this->error = true;

		// If no special error string then use mysql default..
		$this->last_error = empty($str) ? ( mysql_error().'(Errno='.mysql_errno().')' ) : $str;

		// Log this error to the global array..
		$EZSQL_ERROR[] = array
						(
							'query' => $this->last_query,
							'error_str'  => $this->last_error
						);

		// Is error output turned on or not..
		if ( $this->show_errors )
		{
			// If there is an error then take note of it
			echo '<div class="error">';
			echo '<p class="error">', T_('MySQL error!'), '</p>';
			echo '<p>', $this->last_error, '</p>';
			if( !empty($this->last_query) ) echo '<p class="error">Your query:<br /><code>'. $this->last_query. '</code></p>';
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
		{	// Query is a table creation, we add table options:
			$query .= $this->dbtableoptions;
		}

		// Keep track of the last query for debug..
		$this->last_query = $query;

		// Perform the query via std mysql_query function..
		$this->num_queries++;
		$this->queries[ $this->num_queries - 1 ] = array(
																									'title' => $title,
																									'sql' => $query,
																									'rows' => -1 );

		$this->result = @mysql_query($query,$this->dbh);

		// If there is an error then take note of it..
		if ( mysql_error() )
		{
			$this->print_error();
			return false;
		}

		if( preg_match( '#^ \s* (insert|delete|update|replace) \s #ix', $query) )
		{ // Query was an insert, delete, update, replace:

			// echo 'insert, delete, update, replace';

			$this->rows_affected = mysql_affected_rows();
			$this->queries[ $this->num_queries - 1 ]['rows'] = $this->rows_affected;

			// Take note of the insert_id
			if ( preg_match("/^\\s*(insert|replace) /i",$query) )
			{
				$this->insert_id = mysql_insert_id($this->dbh);
			}

			// Return number fo rows affected
			$return_val = $this->rows_affected;
		}
		else
		{ // Query was a select:

			// echo 'select';

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

		// If debug ALL queries
		if( $this->trace || $this->debug_all )
		{
			$this->debug();
		}

		return $return_val;
	}


	/**
	 * Get one variable from the DB - see docs for more detail
	 *
	 * @return false|mixed false if not found, the value otherwise
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

		return false;
	}


	/**
	 * Get one row from the DB - see docs for more detail
	 *
	 * @return array
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
			$this->print_error(" \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N");
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
		for ( $i = 0, $count = count($this->last_result); $i < $count; $i++ )
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
	function get_list( $query = NULL, $x = 0 )
	{
		return implode( ',', $this->get_col( $query, $x = 0 ) );
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
		echo '<p><table><tr><td bgcolor="fff"><blockquote style="color:#000090">';
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
	 * Displays the last query string that was sent to the database & a
	 * table listing results (if there were any).
	 * (abstracted into a seperate file to save server overhead).
	 */
	function debug()
	{
		echo '<blockquote>';

		// Only show ezSQL credits once..
		if ( ! $this->debug_called )
		{
			echo "<font color=800080 face=arial size=2><b>ezSQL</b> (v".EZSQL_VERSION.") <b>Debug..</b></font><p>\n";
		}
		echo "<font face=arial size=2 color=000099><b>Query</b> [$this->num_queries] <b>--</b> ";
		echo "[<font color=000000><b>$this->last_query</b></font>]</font><p>";

			echo "<font face=arial size=2 color=000099><b>Query Result..</b></font>";
			echo "<blockquote>";

		if ( $this->col_info )
		{
			// =====================================================
			// Results top rows

			echo "<table cellpadding=5 cellspacing=1 bgcolor=555555>";
			echo "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>";


			for ( $i = 0, $count = count($this->col_info); $i < $count; $i++ )
			{
				echo "<td nowrap align=left valign=top><font size=1 color=555599 face=arial>{$this->col_info[$i]->type} {$this->col_info[$i]->max_length}</font><br><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>{$this->col_info[$i]->name}</span></td>";
			}

			echo "</tr>";

			// ======================================================
			// print main results

		if( $this->last_result )
		{

			$i=0;
			foreach( $this->get_results(NULL,ARRAY_N) as $one_row )
			{
				$i++;
				echo "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i</font></td>";

				foreach ( $one_row as $item )
				{
					echo "<td nowrap><font face=arial size=2>$item</font></td>";
				}

				echo "</tr>";
			}

		} // if last result
		else
		{
			echo "<tr bgcolor=ffffff><td colspan=".(count($this->col_info)+1)."><font face=arial size=2>No Results</font></td></tr>";
		}

		echo "</table>";

		} // if col_info
		else
		{
			echo "<font face=arial size=2>No Results</font>";
		}

		echo '</blockquote></blockquote><hr style="border:none;height:1px;border-top:1px solid #ebebd8;margin:2ex 0;">';


		$this->debug_called = true;
	}


	/**
	 * Displays all queries that have been exectuted
	 *
	 * {@internal DB::dump_queries(-) }}
	 */
	function dump_queries()
	{
		foreach( $this->queries as $query )
		{
			echo '<p><strong>Query: '.$query['title'].'</strong></p>';
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
			echo '</code><br />';
			echo 'Rows: ', $query['rows'];
		}
	}


	/**
	 * @todo implement transactions!
	 */
	function begin()
	{
	}


	/**
	 * @todo implement transactions!
	 */
	function commit()
	{
	}


	/**
	 * @todo implement transactions!
	 */
	function rollback()
	{
	}

}

/*
 * $Log$
 * Revision 1.8  2005/02/17 19:36:23  fplanque
 * no message
 *
 * Revision 1.7  2005/02/08 01:11:21  blueyed
 * stoopid parse error fixed
 *
 * Revision 1.6  2005/02/08 00:43:15  blueyed
 * doc, whitespace, html, get_results() and get_row() now always return array, get_var() return false in case of error
 *
 * Revision 1.5  2004/11/09 00:25:11  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.4  2004/11/05 00:36:43  blueyed
 * no message
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