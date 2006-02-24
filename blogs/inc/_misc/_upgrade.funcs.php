<?php
/**
 * This file implements functions useful for upgrading DB schema.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
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
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get the delta query to adjust the current database according to a given (list of)
 * "CREATE TABLE"-, "CREATE DATABASE"-, "INSERT"- or "UPDATE"-statement(s).
 *
 * It's not recommend to use INSERT or UPDATE statements with this function, as they
 * are just handled "as-is".
 *
 * The following query types are generated/marked and can be excluded:
 *  - 'create_table'
 *  - 'create_database'
 *  - 'insert'
 *  - 'update'
 *  - 'drop_column'
 *  - 'change_column'
 *  - 'change_default'
 *  - 'add_column'
 *  - 'add_index'
 *
 * NOTE: You should use single quotes (') to give string type values (this is in fact
 *       required for ENUM and SET fields).
 *
 * @author Originally taken from Wordpress, enhanced and modified by blueyed
 *
 * @todo Handle COMMENT for tables?!
 * @todo drop_index?
 *
 * @see http://dev.mysql.com/doc/refman/4.1/en/create-table.html
 *
 * @param array The list of queries for which the DB should be adjusted
 * @param boolean Execute generated queries?
 * @param array Exclude query types (see list above). Defaults to drop_column.
 * @param array The generated queries.
 *        table_name => array of arrays (queries with keys 'query', 'note' and 'type')
 */
function db_delta( $queries, $execute = false, $exclude_types = NULL )
{
	global $Debuglog, $DB;

	if( ! is_array($queries) )
	{
		$queries = array( $queries );
	}

	if( ! isset($exclude_types) )
	{
		$exclude_types = array('drop_column');
	}

	/**
	 * Query items, indexed by table name.
	 */
	$items = array();

	// Create a tablename index for an array of queries
	foreach( $queries as $qry )
	{
		if( preg_match( '|^\s*(CREATE TABLE\s+)(IF NOT EXISTS\s+)?([^\s(]+)(.*)$|is', $qry, $match) )
		{
			$tablename = preg_replace( $DB->dbaliases, $DB->dbreplaces, $match[3] );
			$qry = $match[1].( empty($match[2]) ? '' : $match[2] ).$tablename.$match[4];

			$items[strtolower($tablename)][] = array(
				'query' => $qry,
				'note' => sprintf( 'Created table &laquo;%s&raquo;', $tablename ),
				'type' => 'create_table' );
		}
		elseif( preg_match( '|^\s*CREATE DATABASE\s([\S]+)|i', $qry, $match) )
		{ // add to the beginning
			array_unshift( $items, array(
				'query' => $qry,
				'note' => sprintf( 'Created database &laquo;%s&raquo;', $match[1] ),
				'type' => 'create_database' ) );
		}
		elseif( preg_match( '|^\s*(INSERT INTO\s+)([\S]+)(.*)$|is', $qry, $match) )
		{
			$tablename = preg_replace( $DB->dbaliases, $DB->dbreplaces, $match[2] );
			$items[strtolower($tablename)][] = array(
				'query' => $match[1].$tablename.$match[3],
				'note' => '',
				'type' => 'insert' );
		}
		elseif( preg_match( '|^\s*(UPDATE\s+)([\S]+)(.*)$|is', $qry, $match) )
		{
			$tablename = preg_replace( $DB->dbaliases, $DB->dbreplaces, $match[2] );
			$items[strtolower($tablename)][] = array(
				'query' => $match[1].$tablename.$match[3],
				'note' => '',
				'type' => 'update' );
		}
		else
		{
			$Debuglog->add( 'db_delta: Unrecognized query type: '.$qry, 'note' );
		}
	}


	$tables = $DB->get_col('SHOW TABLES');

	if( ! empty($tables) )
	{ // Check to see which tables and fields exist
		foreach($tables as $table)
		{ // For every table in the database
			$table_lowered = strtolower($table);
			if( isset( $items[$table_lowered] ) )
			{ // If a table query exists for the database table...
				// Clear the field and index arrays

				/**
				 * @global array Holds fields we want to create in key 'field' and additional 'extra' info (where to add)
				 */
				$cfields = array();
				$indices = array();
				// Get all of the field names in the query from between the parens
				preg_match( '|\((.*)\)|s', $items[$table_lowered][0]['query'], $match );
				$qryline = trim($match[1]);

				// Separate field lines into an array
				$flds = preg_split( '~,\r?\n~', $qryline, -1, PREG_SPLIT_NO_EMPTY );

				//echo "<hr/><pre>\n".print_r(strtolower($table), true).":\n".print_r($items, true)."</pre><hr/>";

				$prev_fld = '';
				foreach( $flds as $fld )
				{ // For every field line specified in the query
					// Extract the field name
					preg_match( '|^(\S+)|', trim($fld), $match );
					$fieldname = $match[1];

					$fld = trim($fld, ", \r\n\t");
					if( in_array( strtolower($fieldname), array( '', 'primary', 'index', 'fulltext', 'unique', 'key' ) ) )
					{ // index
						$indices[] = $fld;
					}
					else
					{ // valid field, add it to the field array
						$cfields[strtolower($fieldname)] = array(
								'field' => $fld,
								'extra' => ( empty($prev_fld) ? 'FIRST' : 'AFTER '.$prev_fld ),
							);
						$prev_fld = $fieldname;
					}

				}

				// Fetch the table column structure from the database
				$tablefields = $DB->get_results( 'DESCRIBE '.$table );

				// For every field in the existing table
				foreach($tablefields as $tablefield)
				{
					if( ! isset($cfields[ strtolower($tablefield->Field) ]) )
					{ // This field exists in the table, but not in the creation queries?
						$items[$table_lowered][] = array(
							'query' => 'ALTER TABLE '.$table.' DROP COLUMN '.$tablefield->Field.' '.$tablefield->Field,
							'note' => 'Dropped '.$table.'.'.$tablefield->Field,
							'type' => 'drop_column' );
						continue;
					}

					$column_definition = trim( $cfields[strtolower($tablefield->Field)]['field'] );

					unset($type_matches); // have we detected the type as matching (for optional length param)
					$fieldtype = '';

					// Get the field type from the query
					if( preg_match( '~^'.$tablefield->Field.'\s+ (TINYINT|SMALLINT|MEDIUMINT|INTEGER|INT|BIGINT|REAL|DOUBLE|FLOAT|DECIMAL|NUMERIC) ( \s* \([\d\s,]+\) )? (\s+ UNSIGNED)? (\s+ ZEROFILL)? (.*)$~ix', $column_definition, $match ) )
					{
						$fieldtype = $match[1];
						if( isset($match[2]) )
						{
							$fieldtype .= preg_replace( '~\s+~', '', $match[2] );
						}
						if( isset($match[3]) )
						{ // "unsigned"
							$fieldtype .= ' '.trim($match[3]);
						}
						if( isset($match[4]) )
						{ // "zerofill"
							$fieldtype .= ' '.trim($match[4]);
						}

						$field_to_parse = $match[5];

						// The length param is optional:
						$matches_pattern = '~'.preg_replace( '~\((\d+)\)~', '(\($1\))?', $tablefield->Type ).'~i';
						$type_matches = preg_match( $matches_pattern, $fieldtype );
					}
					elseif( preg_match( '~^'.$tablefield->Field.'\s+(DATETIME|DATE|TIMESTAMP|TIME|YEAR|TINYBLOB|BLOB|MEDIUMBLOB|LONGBLOB|TINYTEXT|TEXT|MEDIUMTEXT|LONGTEXT) ( \s+ BINARY )? (.*)$~ix', $column_definition, $match ) )
					{
						$fieldtype = $match[1];
						if( isset($match[2]) )
						{ // "binary"
							$fieldtype .= trim($match[2]);
						}
						$field_to_parse = $match[3];
					}
					elseif( preg_match( '~^'.$tablefield->Field.'\s+ (CHAR|VARCHAR|BINARY|VARBINARY) \s* \( ([\d\s]+) \) (\s+ (BINARY|ASCII|UNICODE) )? (.*)$~ix', $column_definition, $match ) )
					{
						$fieldtype = $match[1].'('.trim($match[2]).')';
						if( ! empty($match[3]) )
						{ // "binary", "ascii", "unicode"
							$fieldtype .= ' '.$match[3];
						}
						$field_to_parse = $match[5];
					}
					elseif( preg_match( '~^'.$tablefield->Field.'\s+ (ENUM|SET) \s* \( (.*) \) (.*)$~ix', $column_definition, $match ) )
					{
						$values = preg_split( '~\s*,\s*~', trim($match[2]), -1, PREG_SPLIT_NO_EMPTY ); // TODO: will fail for values containing ","..
						$values = implode( ',', $values );

						$fieldtype = $match[1].'('.$values.')';
						$field_compare = strtolower($match[1]).'('.$values.')';

						// compare case-sensitive
						$type_matches = ( $field_compare == $tablefield->Type );

						$field_to_parse = $match[3];
					}


					// "[NOT] NULL"
					if( strtolower($fieldtype) != 'timestamp' // "[NOT] NULL" gets ignored for TIMESTAMP by MySQL and can always be NULL assigned to
					    && preg_match( '~^(.*?) \s (NOT\s+)? NULL (\s.*)?$~ix', $field_to_parse, $match ) )
					{
						$is_null = empty($match[2]); // if "NOT" not matched it's NULL
						$field_to_parse = $match[1].' '.( isset($match[3]) ? $match[3] : '' );
					}
					else
					{ // "NULL" if not specified
						$is_null = true;
					}
					$null_matches = ( ($tablefield->Null == 'YES' && $is_null) || ($tablefield->Null == 'NO' && ! $is_null) );


					if( ! isset($type_matches) )
					{ // not tried to match before
						$type_matches = ( strtolower($tablefield->Type) == strtolower($fieldtype) );
					}

					/*
					pre_dump( 'null_matches', $null_matches, $tablefield, $is_null );
					pre_dump( 'type_matches', $type_matches );
					*/

					// Is actual field type different from the field type in query?
					if( ! $type_matches || ! $null_matches )
					{ // Change the whole column to $column_definition:
						/*
						echo '<h2>No_Match</h2>';
						pre_dump( $tablefield, $column_definition );
						pre_dump( 'flds', $flds );
						pre_dump( 'cfields', $cfields );
						pre_dump( strtolower($tablefield->Type), strtolower($fieldtype), $column_definition );
						*/

						// Add a query to change the column type
						$items[$table_lowered][] = array(
							'query' => 'ALTER TABLE '.$table.' CHANGE COLUMN '.$tablefield->Field.' '.$column_definition,
							'note' => 'Changed type of '.$table.'.'.$tablefield->Field.' from '.$tablefield->Type.' to '.$column_definition,
							'type' => 'change_column' );
					}
					else
					{
						unset( $set_default );
						if( preg_match( '~^ \s+ DEFAULT \s+ (?:(?:([\'"])(.*?)\1 ) | (\d+) )~isx', $field_to_parse, $match) )
						{ // DEFAULT given
							if( isset($match[3]) )
							{ // integer
								$default_value = $match[3];
							}
							else
							{ // string
								$default_value = $match[2];
								$set_default = $match[1].$match[2].$match[1]; // encapsulate in quotes again
							}

							$existing_default = $tablefield->Default === NULL ? 'NULL' : $tablefield->Default;

							if( strtolower( $existing_default ) != strtolower($default_value) )
							{ // Add a query to change the column's default value
								if( ! isset($set_default) )
								{
									$set_default = $default_value;
								}
								$items[$table_lowered][] = array(
									'query' => 'ALTER TABLE '.$table.' ALTER COLUMN '.$tablefield->Field.' SET DEFAULT '.$set_default,
									'note' => "Changed default value of {$table}.{$tablefield->Field} from $existing_default to $set_default",
									'type' => 'change_default' );
							}
						}
						elseif( ! empty($tablefield->Default) && $tablefield->Type != 'timestamp' )
						{ // No DEFAULT given, but it exists one, so drop it
							$items[$table_lowered][] = array(
								'query' => 'ALTER TABLE '.$table.' ALTER COLUMN '.$tablefield->Field.' DROP DEFAULT',
								'note' => "Dropped default value of {$table}.{$tablefield->Field}",
								'type' => 'change_default' ); // might be also 'drop_default'
						}
					}

					// Remove the field from the array (so it's not added)
					unset($cfields[strtolower($tablefield->Field)]);
				}

				// For every remaining field specified for the table
				foreach($cfields as $fieldname => $fielddef)
				{
					// Push a query line into $items that adds the field to that table
					$items[$table_lowered][] = array(
						'query' => 'ALTER TABLE '.$table.' ADD COLUMN '.$fielddef['field'].' '.$fielddef['extra'],
						'note' => 'Added column '.$table.'.'.$fielddef['field'],
						'type' => 'add_column' );
				}


				// INDEX STUFF:
				// Fetch the table index structure from the database
				$tableindices = $DB->get_results( 'SHOW INDEX FROM '.$table );

				if( ! empty($tableindices) )
				{
					// Clear the index array
					$index_ary = array();

					// For every index in the table
					foreach( $tableindices as $tableindex )
					{
						// Add the index to the index data array
						$keyname = $tableindex->Key_name;
						$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
						$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0) ? true : false;
					}

					// For each actual index in the index array
					foreach( $index_ary as $index_name => $index_data )
					{
						// Build a create string to compare to the query
						$index_pattern = '';
						if( $index_name == 'PRIMARY' )
						{
							$index_pattern .= 'PRIMARY(\s+KEY)?';
						}
						elseif( $index_data['unique'] )
						{
							$index_pattern .= 'UNIQUE(\s+KEY)?';
						}
						else
						{
							$index_pattern .= '(INDEX|KEY)';
						}
						if( $index_name == 'PRIMARY' )
						{ // optional primary key name
							$index_pattern .= '(\s+\w+)?';
						}
						else
						{
							$index_pattern .= '(\s+'.$index_name.')?';
						}

						$index_columns = '';
						// For each column in the index
						foreach( $index_data['columns'] as $column_data )
						{
							if( $index_columns != '' )
							{
								$index_columns .= '\s*,\s*';
							}
							// Add the field to the column list string
							$index_columns .= $column_data['fieldname'];
							if( ! empty($column_data['subpart']) )
							{
								$index_columns .= '\s*\(\s*'.$column_data['subpart'].'\s*\)\s*';
							}
						}
						// Add the column list to the index create string
						$index_pattern .= '\s*\(\s*'.$index_columns.'\s*\)';

						$already_present = false;
						foreach( $indices as $k => $index )
						{
							if( preg_match( '~'.$index_pattern.'~i', trim($index) ) )
							{ // remove the index from our indices to create
								unset($indices[$k]);
								$already_present = true;
								#echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/>Found index:".$index.' ('.$index_pattern.")</pre>\n";
								break;
							}
						}
						if( ! $already_present )
						{
							#pre_dump( 'TABLEINDICES', $tableindices );
							#echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/><b>Did not find index:</b>".$index_pattern."<br/>".print_r($indices, true)."</pre>\n";
						}
					}
				}

				// For every remaining index specified for the table
				foreach( $indices as $index )
				{
					// Push a query line into $items that adds the index to that table
					$items[$table_lowered][] = array(
						'query' => "ALTER TABLE {$table} ADD $index",
						'note' => 'Added index '.$table.' '.$index,
						'type' => 'add_index' );
				}

				// Remove the original table creation query from processing
				array_shift( $items[$table_lowered] );
			}
			else
			{
				// This table exists in the database, but not in the creation queries?
			}
		}
	}

	if( ! empty($exclude_types) )
	{
		foreach( $items as $table => $itemlist )
		{
			foreach( $itemlist as $k => $item )
			{
				if( in_array($item['type'], $exclude_types) )
				{
					unset( $items[$table][$k] );
				}
			}
		}
	}

	$allqueries = array();
	foreach( $items as $table => $itemlist )
	{
		if( empty($itemlist) )
		{
			unset( $items[$table] );
			continue;
		}
		foreach( $itemlist as $item )
		{
			$allqueries[] = $item['query'];
		}
	}

	if( $execute )
	{
		foreach( $allqueries as $query )
		{
			#pre_dump( $query );
			$DB->query( $query );
		}
	}

	return $items;
}


/**
 * Alter the DB schema to match the current expected one ($schema_queries)
 *
 * @param boolean Display what we've done?
 */
function install_make_db_schema_current( $display = true )
{
	global $schema_queries, $DB;

	foreach( $schema_queries as $table => $query_info )
	{
		$items_need_update = db_delta( $query_info[1], false );

		if( empty($items_need_update) )
		{
			continue;
		}

		if( ! $display )
		{ // just execute queries
			foreach( $items_need_update as $table => $itemlist )
			{
				foreach( $itemlist as $item )
				{
					$DB->query( $item['query'] );
				}
			}
		}
		else
		{ // the same, but with output
			foreach( $items_need_update as $table => $itemlist )
			{
				if( count($itemlist) == 1 && $itemlist[0]['type'] == 'create_table' )
				{
					$DB->query($itemlist[0]['query']);
					echo $itemlist[0]['note'].'<br />';
				}
				else
				{
					echo 'Altering table &laquo;'.$table.'&raquo;...';
					echo '<ul>';
					foreach( $itemlist as $item )
					{
						$DB->query( $item['query'] );
						echo '<li>'.$item['note'].'</li>';
					}
					echo "</ul>";
				}
			}
		}
	}
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.1  2006/02/24 19:13:09  blueyed
 * Welcome the magic of db_delta()..
 *
 * }}}
 */
?>