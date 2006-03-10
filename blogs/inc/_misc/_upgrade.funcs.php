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
 * {@link db_delta()} is based on dbDelta() from {@link http://wordpress.com Wordpress}, see
 * {@link http://trac.wordpress.org/file/trunk/wp-admin/upgrade-functions.php}.
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
 * @author Wordpress team
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
 * NOTE:
 *   - You should use single quotes (') to give string type values (this is in fact
 *     required for ENUM and SET fields).
 *   - KEYs for AUTO_INCREMENT fields should be defined in column_definition, otherwise
 *     we had to detect the key type from the INDEX query and add it to the ALTER/ADD query.
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
 *  - 'drop_index'
 * NOTE: it may be needed to merge an 'add_index' or 'drop_index' type query into an
 *       'add_column'/'change_column' query (adding "AUTO_INCREMENT" for example)!
 *
 * @author Originally taken from Wordpress, heavily enhanced and modified by blueyed
 *
 * @todo Handle COMMENT for tables?!
 *
 * @see http://dev.mysql.com/doc/refman/4.1/en/create-table.html
 *
 * @param array The list of queries for which the DB should be adjusted
 * @param boolean Execute generated queries?  TODO: get this outta here!!!! (sooooo bloated!)
 * @param array Exclude query types (see list above). Defaults to drop_column.
 * @return array The generated queries.
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
		$exclude_types = array('drop_column', 'drop_index');
	}

	/**
	 * Generated query items, indexed by table name.
	 */
	$items = array();


	// Split the queries into $items, by their type:
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


	/**
	 * @global array Hold the indices we want to create/have
	 */
	$indices = array();

	/**
	 * @global array Initially all existing indices. Any index, that does not get unset here, generates a 'drop_index' type query.
	 */
	$obsolete_indices = array();


	/**
	 * @global array Available tables in the current database
	 */
	$tables = $DB->get_col('SHOW TABLES');

	if( ! empty($tables) )
	{ // Loop through existing tables and check which tables and fields exist
		foreach($tables as $table)
		{ // For every table in the database
			$table_lowered = strtolower($table);  // table names are treated case insensitive

			if( ! isset( $items[$table_lowered] ) )
			{ // This table exists in the database, but not in the creation queries.
				continue;
			}

			/**
			 * @global array Fields of the existing primary key (if any)
			 */
			$existing_primary_fields = array();

			/**
			 * @global array Fields of existing keys (including PRIMARY), lowercased (if any)
			 */
			$existing_key_fields = array();

			/**
			 * @global array Column field names of PRIMARY KEY, lowercased (if any)
			 */
			$primary_key_fields = array();

			/**
			 * @global array of col_names that have KEYs (lowercased). We use this for AUTO_INCREMENT magic.
			 */
			$fields_with_keys = array();


			/**
			 * @global array List of fields (and definition from query)
			 *   <code>fieldname (lowercase) => array(
			 *         'field' => "column_definition",
			 *         'where' => "[FIRST|AFTER xxx]" )
			 *   </code>
			 */
			$cfields = array();

			/**
			 * @global boolean Do we have any variable-length fields? (see http://dev.mysql.com/doc/refman/4.1/en/silent-column-changes.html)
			 */
			$has_variable_length_field = false;


			// Get all of the field names in the query from between the parens
			preg_match( '|\((.*)\)|s', $items[$table_lowered][0]['query'], $match );
			$qryline = trim($match[1]);

			// Separate field lines into an array
			$flds = preg_split( '~,(\r?\n|\r)~', $qryline, -1, PREG_SPLIT_NO_EMPTY );

			//echo "<hr/><pre>\n".print_r(strtolower($table), true).":\n".print_r($items, true)."</pre><hr/>";

			$prev_fld = '';
			foreach( $flds as $fld )
			{ // For every field line specified in the query
				// Extract the field name
				preg_match( '|^([^\s(]+)|', trim($fld), $match );
				$fieldname = $match[1];
				$fieldname_lowered = strtolower($match[1]);

				$fld = trim($fld, ", \r\n\t");
				if( in_array( $fieldname_lowered, array( '', 'primary', 'index', 'fulltext', 'unique', 'key' ) ) )
				{ // index (not in column_definition - this gets added later)
					$indices[] = $fld;

					preg_match( '~\((.*?)\)$~', $fld, $match );
					$index_fields = explode( ',', $match[1] );
					foreach( $index_fields as $k => $v )
					{
						$index_fields[$k] = strtolower(trim($v));
					}

					if( $fieldname_lowered == 'primary' )
					{ // Remember PRIMARY KEY fields to be indexed (used for NULL check)
						$primary_key_fields = $index_fields;
					}
					$fields_with_keys = array_merge( $fields_with_keys, $index_fields );
				}
				else
				{ // "normal" field, add it to the field array
					$cfields[ strtolower($fieldname_lowered) ] = array(
							'field' => $fld,
							'where' => ( empty($prev_fld) ? 'FIRST' : 'AFTER '.$prev_fld ),
						);
					$prev_fld = $fieldname;

					if( preg_match( '~^\S+\s+(VARCHAR|TEXT|BLOB)~i', $fld ) )
					{
						$has_variable_length_field = true;
					}
				}
			}


			// INDEX STUFF:
			/**
			 * @global array Holds the existing indices (with array's key UPPERcased)
			 */
			$index_ary = array();

			// Fetch the table index structure from the database
			$tableindices = $DB->get_results( 'SHOW INDEX FROM '.$table );

			if( ! empty($tableindices) )
			{
				// For every index in the table
				foreach( $tableindices as $tableindex )
				{
					// Add the index to the index data array
					$keyname = strtoupper($tableindex->Key_name);

					$index_ary[$keyname]['name'] = $tableindex->Key_name; // original case
					$index_ary[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
					$index_ary[$keyname]['unique'] = ($tableindex->Non_unique == 0) ? true : false;
				}

				$obsolete_indices = $index_ary; // will get unset as found
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

					foreach( $indices as $k => $index )
					{
						if( preg_match( '~'.$index_pattern.'~i', trim($index) ) )
						{ // This index already exists: remove the index from our indices to create
							unset($indices[$k]);
							unset($obsolete_indices[$index_name]);

							#echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/>Found index:".$index.' ('.$index_pattern.")</pre>\n";
							break;
						}
					}
					if( isset($obsolete_indices[$index_name]) )
					{
						#pre_dump( 'TABLEINDICES', $tableindices );
						#echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/><b>Did not find index:</b>".$index_pattern."<br/>".print_r($indices, true)."</pre>\n";
					}
				}

				foreach( $index_ary as $l_key_name => $l_key_info )
				{
					$l_key_fields = array();
					foreach( $l_key_info['columns'] as $l_col )
					{
						$l_key_fields[] = strtolower($l_col['fieldname']);
					}
					if( $l_key_name == 'PRIMARY' )
					{ // Remember _existing_ PRIMARY KEYs
						$existing_primary_fields = $l_key_fields;
					}

					$existing_key_fields = array_merge( $existing_key_fields, $l_key_fields );
				}
				#pre_dump( 'existing_primary_fields', $existing_primary_fields );
				#pre_dump( 'existing_key_fields', $existing_key_fields );
			}


			// Pre-run KEYs defined in "column_definition" for AUTO_INCREMENT handling
			foreach( $cfields as $fieldname_lowered => $field_info )
			{
				$fld = $field_info['field'];
				if( preg_match( '~ \b (?: (UNIQUE) (\s+ KEY)? | (PRIMARY \s+)? KEY ) \b ~ix', $fld, $match ) )
				{
					if( empty($match[1]) )
					{
						$primary_key_fields = array($fieldname_lowered);
					}
					$fields_with_keys[] = $fieldname_lowered;
				}
			}


			// Fetch the table column structure from the database
			$tablefields = $DB->get_results( 'DESCRIBE '.$table );

			// For every field in the existing table
			foreach($tablefields as $tablefield)
			{
				$fieldname_lowered = strtolower($tablefield->Field);

				if( ! isset($cfields[ $fieldname_lowered ]) )
				{ // This field exists in the table, but not in the creation queries?
					$items[$table_lowered][] = array(
						'query' => 'ALTER TABLE '.$table.' DROP COLUMN '.$tablefield->Field,
						'note' => 'Dropped '.$table.'.'.$tablefield->Field,
						'type' => 'drop_column' );
					continue;
				}

				$column_definition = trim( $cfields[$fieldname_lowered]['field'] );

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
					$len = trim($match[2]);

					$fieldtype = $match[1].'('.$len.')';
					if( ! empty($match[3]) )
					{ // "binary", "ascii", "unicode"
						$fieldtype .= ' '.$match[3];
					}
					$field_to_parse = $match[5];

					if( strtoupper($match[1]) == 'VARCHAR' )
					{
						if( $len < 4 )
						{ // VARCHAR shorter than 4 get converted to CHAR (but reported as VARCHAR in MySQL 5.0)
							$type_matches = preg_match( '~^(VAR)?CHAR\('.$len.'\)'.( $match[3] ? ' '.$match[3] : '' ).'$~i', $tablefield->Type );
						}
					}
					elseif( $has_variable_length_field && strtoupper($match[1]) == 'CHAR' )
					{ // CHARs in a row with variable length fields get silently converted to VARCHAR (but reported as CHAR in MySQL 5.0)
						$type_matches = preg_match( '~^(VAR)?'.preg_quote( $fieldtype, '~' ).'$~i', $tablefield->Type );
					}
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


				// DEFAULT
				$has_default = false;
				if( preg_match( '~(.*?) \b DEFAULT ( (?: (["\']?) .*? $3 ) | \w+ ) \b (.*)$~ix', $field_to_parse, $match ) )
				{
					$has_default = $match[2];
					$field_to_parse = $match[1].$match[4];
				}


				// KEY
				if( preg_match( '~^(.*) (?: \b (UNIQUE) (?:\s+ KEY)? | (?:PRIMARY \s+)? KEY \b ) (.*)$~ix', $field_to_parse, $match ) )
				{
					$field_to_parse = $match[1].$match[3];
					if( empty($match[2]) )
					{ // PRIMARY
						unset( $obsolete_indices['PRIMARY'] );
					}

					// TODO: obsolete/unfinished?
				}


				// AUTO_INCREMENT (with special index handling: AUTO_INCREMENT fields need to be PRIMARY or UNIQUE)
				$is_auto_increment = false;
				if( preg_match( '~(.*?) \b AUTO_INCREMENT \b (.*)$~ix', $field_to_parse, $match ) )
				{
					$is_auto_increment = true;
					$field_to_parse = $match[1].$match[2];

					if( ! preg_match( '~\bAUTO_INCREMENT\b~i', $tablefield->Extra ) )
					{ // not AUTO_INCREMENT yet
						$type_matches = false;
					}

					if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
					{ // no KEY defined (but required for AUTO_INCREMENT fields)
						debug_die('No KEY/INDEX defined for AUTO_INCREMENT column!');
					}

					if( ! in_array( $fieldname_lowered, $existing_key_fields ) )
					{ // a key for this AUTO_INCREMENT field does not exist yet, we search it in $indices
						foreach( $indices as $k_index => $l_index )
						{ // go through the indexes we want to have
							if( ! preg_match( '~^(PRIMARY(?:\s+KEY)|UNIQUE(?:\s+INDEX)?|KEY|INDEX) (?:\s+(\w+))? (\s+USING \w+)? \s* \((.*)\)$~ix', $l_index, $match ) )
							{ // invalid type, should not happen
								debug_die( 'Invalid type in $indices: '.$l_index );
							}
							$index_keyword = $match[1];
							$index_name = strtoupper($match[2]);
							$index_type = $match[3]; // "USING [type_name]"
							$index_col_names = explode( ',', $match[4] );
							foreach( $index_col_names as $k => $v )
							{
								$index_col_names[$k] = strtolower(trim($v));
							}

							if( array_search( $fieldname_lowered, $index_col_names ) === false )
							{ // this is not an index for our column
								continue;
							}

							// this index definition affects us, we have to add it to our ALTER statement..

							// See if we need to drop it, before adding it:
							if( preg_match( '~PRIMARY(\s+KEY)~i', $index_keyword ) )
							{ // Part of a PRIMARY key..
								if( ! empty( $existing_primary_fields ) )
								{ // and a PRIMARY key exists already
									$column_definition .= ', DROP PRIMARY KEY';
								}
								$existing_primary_fields = array(); // we expect no existing primary key anymore
								$primary_key_fields = $index_col_names; // this becomes our primary key
							}
							elseif( isset( $index_ary[$index_name] ) )
							{ // this index already exists, drop it:
								$column_definition .= ', DROP INDEX '.$index_ary[$index_name]; // original case
								unset( $index_ary[$index_name] ); // we expect that it does not exist anymore
								if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
								{ // add te field to the list of keys we want/expect to have:
									$fields_with_keys[] = $fieldname_lowered;
								}
							}

							// Merge the INDEX creation into our ALTER query:
							$column_definition .= ', ADD '.$l_index;
							unset( $indices[$k_index] );
						}
					}
				}


				// "[NOT] NULL" (requires $primary_key_fields to be finalized)
				if( preg_match( '~(.*?) \b (NOT\s+)? NULL \b (.*)$~ix', $field_to_parse, $match ) )
				{
					if( strtoupper($fieldtype) == 'TIMESTAMP' )
					{ // "[NOT] NULL" gets ignored for TIMESTAMP by MySQL and can always be NULL assigned to
						$want_null = true;
					}
					else
					{ // if "NOT" not matched it's NULL
						$want_null = empty($match[2]);
					}
					$field_to_parse = $match[1].$match[3];
				}
				else
				{ // not specified: "NULL" is default
					$want_null = true;
				}

				if( in_array($fieldname_lowered, $primary_key_fields) || $is_auto_increment )
				{ // If part of PRIMARY KEY or AUTO_INCREMENT field "NULL" is implicit
					$change_null = false; // implicit NULL
					$want_null = 'IMPLICIT';
				}
				elseif( in_array($fieldname_lowered, $existing_primary_fields) && ! in_array($fieldname_lowered, $primary_key_fields) )
				{ // the field was in PRIMARY KEY, but is no longer. It should get altered only if we want "NOT NULL"
					$change_null = ( ! $want_null && $tablefield->Null == 'YES' );
					#pre_dump( $want_null );
					#$want_null = 'IMPLICIT2';
					#pre_dump( $primary_key_fields );
				}
				else
				{
					if( $tablefield->Null == 'YES' )
					{
						$change_null = ! $want_null;
					}
					else
					{ // I've seen '' and 'NO' for no..
						$change_null = $want_null;
					}
				}


				if( ! isset($type_matches) )
				{ // not tried to match before
					$type_matches = ( strtolower($tablefield->Type) == strtolower($fieldtype) );
				}

				#pre_dump( 'change_null ($change_null, $tablefield, $want_null)', $change_null, $tablefield, $want_null );
				#pre_dump( 'type_matches', $type_matches, strtolower($tablefield->Type), strtolower($fieldtype) );

				// Is actual field type different from the field type in query?
				if( ! $type_matches || $change_null )
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

						if( $existing_default != $default_value ) // DEFAULT is case-sensitive
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
					elseif( ! empty($tablefield->Default) )
					{ // No DEFAULT given, but it exists one, so drop it
						if( $tablefield->Type != 'timestamp' && $tablefield->Type != 'datetime' && ! preg_match( '~^enum|set\s*\(~', $tablefield->Type ) )
						{
							$items[$table_lowered][] = array(
								'query' => 'ALTER TABLE '.$table.' ALTER COLUMN '.$tablefield->Field.' DROP DEFAULT',
								'note' => "Dropped default value of {$table}.{$tablefield->Field}",
								'type' => 'change_default' ); // might be also 'drop_default'
						}
					}
				}

				// Remove the field from the array (so it's not added)
				unset($cfields[$fieldname_lowered]);
			}

			// For every remaining field specified for the table
			foreach($cfields as $fieldname_lowered => $fielddef)
			{
				$column_definition = $fielddef['field'].' '.$fielddef['where'];

				// AUTO_INCREMENT (with special index handling: AUTO_INCREMENT fields need to be PRIMARY or UNIQUE)
				if( preg_match( '~(.*?) \b AUTO_INCREMENT \b (.*)$~ix', $fielddef['field'], $match ) )
				{
					if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
					{ // no KEY defined (but required for AUTO_INCREMENT fields)
						debug_die('No KEY/INDEX defined for AUTO_INCREMENT column!');
					}

					foreach( $indices as $k_index => $l_index )
					{ // go through the indexes we want to have
						if( ! preg_match( '~^(PRIMARY(?:\s+KEY)|UNIQUE(?:\s+INDEX)?|KEY|INDEX) (?:\s+(\w+))? (\s+USING \w+)? \s* \((.*)\)$~ix', $l_index, $match ) )
						{ // invalid type, should not happen
							debug_die( 'Invalid type in $indices: '.$l_index );
						}
						$index_keyword = $match[1];
						$index_name = strtoupper($match[2]);
						$index_type = $match[3]; // "USING [type_name]"
						$index_col_names = explode( ',', $match[4] );
						foreach( $index_col_names as $k => $v )
						{
							$index_col_names[$k] = strtolower(trim($v));
						}

						if( array_search( $fieldname_lowered, $index_col_names ) === false )
						{ // this is not an index for our column
							continue;
						}

						// this index definition affects us, we have to add it to our ALTER statement..

						// See if we need to drop it, before adding it:
						if( preg_match( '~PRIMARY(\s+KEY)~i', $index_keyword ) )
						{ // Part of a PRIMARY key..
							if( ! empty( $existing_primary_fields ) )
							{ // and a PRIMARY key exists already
								$column_definition .= ', DROP PRIMARY KEY';
							}
							$existing_primary_fields = array(); // we expect no existing primary key anymore
							$primary_key_fields = $index_col_names; // this becomes our primary key
						}
						elseif( isset( $index_ary[$index_name] ) )
						{ // this index already exists, drop it:
							$column_definition .= ', DROP INDEX '.$index_ary[$index_name]; // original case
							unset( $index_ary[$index_name] ); // we expect that it does not exist anymore
							if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
							{ // add te field to the list of keys we want/expect to have:
								$fields_with_keys[] = $fieldname_lowered;
							}
						}

						// Merge the INDEX creation into our ALTER query:
						$column_definition .= ', ADD '.$l_index;
						unset( $indices[$k_index] );
					}
				}

				// Push a query line into $items that adds the field to that table
				$items[$table_lowered][] = array(
					'query' => 'ALTER TABLE '.$table.' ADD COLUMN '.$column_definition,
					'note' => 'Added column '.$table.'.'.$fielddef['field'],
					'type' => 'add_column' );
			}


			// Remove the original table creation query from processing
			array_shift( $items[$table_lowered] );


			// For every remaining index specified for the table
			foreach( $indices as $index )
			{
				$query = 'ALTER TABLE '.$table;
				if( preg_match( '~^(PRIMARY(\s+KEY)?)~i', $index, $match ) && $existing_primary_fields )
				{
					$query .= ' DROP '.$match[1].',';
					unset( $obsolete_indices['PRIMARY'] );
				}
				// Push a query line into $items that adds the index to that table
				$items[$table_lowered][] = array(
					'query' => $query.' ADD '.$index,
					'note' => 'Added index '.$index,
					'type' => 'add_index' );
			}


			foreach( $obsolete_indices as $index_info )
			{
				// Push a query line into $items that drops the index from the table
				$items[$table_lowered][] = array(
					'query' => "ALTER TABLE {$table} DROP ".( $index_info['name'] == 'PRIMARY' ? 'PRIMARY KEY' : 'INDEX '.$index_info['name'] ),
					'note' => 'Dropped index '.$index_info['name'],
					'type' => 'drop_index' );
			}
		}
	}


	// Filter types we want to exclude:
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

	// Unset empty table indices:
	foreach( $items as $table => $itemlist )
	{
		if( empty($itemlist) )
		{
			unset( $items[$table] );
			continue;
		}
	}

	if( $execute )
	{
		foreach( $items as $table => $itemlist )
		{
			foreach( $itemlist as $item )
			{
				#pre_dump( $item['query'] );
				$DB->query( $item['query'] );
			}
		}
	}

	return $items;
}


/**
 * Alter the DB schema to match the current expected one ({@link $schema_queries}).
 *
 * @todo if used by install only, then put it into the install folde!!!
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
 * Revision 1.6  2006/03/10 17:20:24  blueyed
 * Support silent column specification changes (part of)
 *
 * Revision 1.5  2006/03/10 06:13:13  blueyed
 * Split fields for Mac..?!
 *
 * Revision 1.4  2006/03/10 06:03:40  blueyed
 * db_delta fixes
 *
 * Revision 1.3  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.2  2006/03/03 20:10:21  blueyed
 * doc
 *
 * Revision 1.1  2006/02/24 19:13:09  blueyed
 * Welcome the magic of db_delta()..
 *
 * }}}
 */
?>