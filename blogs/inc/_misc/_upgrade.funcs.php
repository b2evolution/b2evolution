<?php
/**
 * This file implements functions useful for upgrading DB schema.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * {@link db_delta()} is based on dbDelta() from {@link http://wordpress.com Wordpress}, see
 * {@link http://trac.wordpress.org/file/trunk/wp-admin/upgrade-functions.php}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
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
 *   - If a column changes from "NULL" to "NOT NULL" we generate an extra UPDATE query
 *     to prevent "Data truncated for column 'X' at row Y" errors.
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
 * @todo "You can't delete all columns with ALTER TABLE; use DROP TABLE instead(Errno=1090)"
 * @todo Handle COMMENT for tables?!
 *
 * @see http://dev.mysql.com/doc/refman/4.1/en/create-table.html
 *
 * @param array The list of queries for which the DB should be adjusted
 * @param array Exclude query types (see list above).
 * @param boolean Execute generated queries?  TODO: get this outta here!!!! (sooooo bloated!)
 * @return array The generated queries.
 *        table_name => array of arrays (queries with keys 'queries' (array), 'note' (string) and 'type' (string))
 *        There's usually just a single query in "queries", but in some cases additional queries
 *        are needed (e.g., 'UPDATE' before we can change "NULL" setting).
 */
function db_delta( $queries, $exclude_types = array(), $execute = false )
{
	global $Debuglog, $DB;

	if( ! is_array($queries) )
	{
		$queries = array( $queries );
	}

	if( ! is_array($exclude_types) )
	{
		$exclude_types = empty($exclude_types) ? array() : array($exclude_types);
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
				'queries' => array($qry),
				'note' => sprintf( 'Created table &laquo;<strong>%s</strong>&raquo;', $tablename ),
				'type' => 'create_table' );
		}
		elseif( preg_match( '|^\s*CREATE DATABASE\s([\S]+)|i', $qry, $match) )
		{ // add to the beginning
			array_unshift( $items, array(
				'queries' => array($qry),
				'note' => sprintf( 'Created database &laquo;<strong>%s</strong>&raquo;', $match[1] ),
				'type' => 'create_database' ) );
		}
		elseif( preg_match( '|^\s*(INSERT INTO\s+)([\S]+)(.*)$|is', $qry, $match) )
		{
			$tablename = preg_replace( $DB->dbaliases, $DB->dbreplaces, $match[2] );
			$items[strtolower($tablename)][] = array(
				'queries' => array($match[1].$tablename.$match[3]),
				'note' => '',
				'type' => 'insert' );
		}
		elseif( preg_match( '|^\s*(UPDATE\s+)([\S]+)(.*)$|is', $qry, $match) )
		{
			$tablename = preg_replace( $DB->dbaliases, $DB->dbreplaces, $match[2] );
			$items[strtolower($tablename)][] = array(
				'queries' => array($match[1].$tablename.$match[3]),
				'note' => '',
				'type' => 'update' );
		}
		else
		{
			$Debuglog->add( 'db_delta: Unrecognized query type: '.$qry, 'note' );
		}
	}


	/**
	 * @global array Hold the indices we want to create/have, with meta data keys.
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
			 * @global array of col_names that have KEYs (including PRIMARY; lowercased). We use this for AUTO_INCREMENT magic.
			 */
			$fields_with_keys = array();

			/**
			 * @global string Holds the fielddef of an obsolete ("drop_column") AUTO_INCREMENT field. We must alter this with a PK "ADD COLUMN" query.
			 */
			$obsolete_autoincrement = NULL;


			/**
			 * @global array List of fields (and definition from query)
			 *   <code>fieldname (lowercase) => array(
			 *         'field' => "column_definition",
			 *         'where' => "[FIRST|AFTER xxx]" )
			 *   </code>
			 */
			$wanted_fields = array();

			/**
			 * @global boolean Do we have any variable-length fields? (see http://dev.mysql.com/doc/refman/4.1/en/silent-column-changes.html)
			 */
			$has_variable_length_field = false;


			// Get all of the field names in the query from between the parens
			preg_match( '|\((.*)\)|s', $items[$table_lowered][0]['queries'][0], $match ); // we have only one query here
			$qryline = trim($match[1]);

			// Separate field lines into an array
			$flds = preg_split( '~,(\r?\n|\r)~', $qryline, -1, PREG_SPLIT_NO_EMPTY );

			//echo "<hr/><pre>\n".print_r(strtolower($table), true).":\n".print_r($items, true)."</pre><hr/>";

			$prev_fld = '';
			foreach( $flds as $create_definition )
			{ // For every field line specified in the query
				// Extract the field name
				preg_match( '|^([^\s(]+)|', trim($create_definition), $match );
				$fieldname = $match[1];
				$fieldname_lowered = strtolower($match[1]);

				$create_definition = trim($create_definition, ", \r\n\t");

				if( in_array( $fieldname_lowered, array( '', 'primary', 'index', 'fulltext', 'unique', 'key' ) ) )
				{ // INDEX (but not in column_definition - this gets added later)
					$add_index = array(
						'create_definition' => $create_definition,
					);

					if( ! preg_match( '~^(PRIMARY(?:\s+KEY)|UNIQUE(?:\s+(?:INDEX|KEY))?|KEY|INDEX) (?:\s+(\w+))? (\s+USING \w+)? \s* \((.*)\)$~ix', $create_definition, $match ) )
					{ // invalid type, should not happen
						debug_die( 'Invalid type in $indices: '.$create_definition );
					}
					$add_index['keyword'] = $match[1];
					$add_index['name'] = strtoupper($match[2]);
					$add_index['type'] = $match[3]; // "USING [type_name]"
					$add_index['col_names'] = explode( ',', $match[4] );
					foreach( $add_index['col_names'] as $k => $v )
					{
						$add_index['col_names'][$k] = strtolower(trim($v));
					}

					if( $fieldname_lowered == 'primary' )
					{ // Remember PRIMARY KEY fields to be indexed (used for NULL check)
						$primary_key_fields = $add_index['col_names'];
						$add_index['is_PK'] = true;
					}
					else
					{
						$add_index['is_PK'] = false;
					}
					$fields_with_keys = array_unique( array_merge( $fields_with_keys, $add_index['col_names'] ) );

					$indices[] = $add_index;
				}
				else
				{ // "normal" field, add it to the field array
					$wanted_fields[ strtolower($fieldname_lowered) ] = array(
							'field' => $create_definition,
							'where' => ( empty($prev_fld) ? 'FIRST' : 'AFTER '.$prev_fld ),
						);
					$prev_fld = $fieldname;

					if( preg_match( '~^\S+\s+(VARCHAR|TEXT|BLOB)~i', $create_definition ) )
					{
						$has_variable_length_field = true;
					}
				}
			}


			// INDEX STUFF:
			/**
			 * @global array Holds the existing indices (with array's key UPPERcased)
			 */
			$existing_indices = array();

			// Fetch the table index structure from the database
			$tableindices = $DB->get_results( 'SHOW INDEX FROM '.$table );

			if( ! empty($tableindices) )
			{
				// For every index in the table
				foreach( $tableindices as $tableindex )
				{
					// Add the index to the index data array
					$keyname = strtoupper($tableindex->Key_name);

					$existing_indices[$keyname]['name'] = $tableindex->Key_name; // original case
					$existing_indices[$keyname]['columns'][] = array('fieldname' => $tableindex->Column_name, 'subpart' => $tableindex->Sub_part);
					$existing_indices[$keyname]['unique'] = ($tableindex->Non_unique == 0) ? true : false;
				}
				unset($tableindices);


				// Let's see which indices are present already for the table:
				// TODO: use meta data available now in $indices, instead of building a regular expression!?
				$obsolete_indices = $existing_indices; // will get unset as found

				foreach( $existing_indices as $index_name => $index_data )
				{
					// Build a create string to compare to the query
					$index_pattern = '^';
					if( $index_name == 'PRIMARY' )
					{
						$index_pattern .= 'PRIMARY(\s+KEY)?';
					}
					elseif( $index_data['unique'] )
					{
						$index_pattern .= 'UNIQUE(\s+(?:INDEX|KEY))?';
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
						if( preg_match( '~'.$index_pattern.'~i', trim($index['create_definition']) ) )
						{ // This index already exists: remove the index from our indices to create
							unset($indices[$k]);
							unset($obsolete_indices[$index_name]);

							#echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/>Found index:".$index.' ('.$index_pattern.")</pre>\n";
							break;
						}
					}
					if( isset($obsolete_indices[$index_name]) )
					{
						#echo "<pre style=\"border:1px solid #ccc;margin-top:5px;\">{$table}:<br/><b>Did not find index:</b>".$index_name.'/'.$index_pattern."<br/>".print_r($indices, true)."</pre>\n";
					}
				}

				// Set $existing_primary_fields and $existing_key_fields
				foreach( $existing_indices as $l_key_name => $l_key_info )
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
				$existing_key_fields = array_unique($existing_key_fields);
				#pre_dump( 'existing_primary_fields', $existing_primary_fields );
				#pre_dump( 'existing_key_fields', $existing_key_fields );
			}


			// Pre-run KEYs defined in "column_definition" (e.g. used for AUTO_INCREMENT handling)
			foreach( $wanted_fields as $fieldname_lowered => $field_info )
			{
				$fld = $field_info['field'];
				if( preg_match( '~ \b (?: (UNIQUE) (\s+ (?:INDEX|KEY))? | (PRIMARY \s+)? KEY ) \b ~ix', $fld, $match ) )
				{ // This has an "inline" INDEX/KEY:
					$add_index = array(
							'create_definition' => NULL, // "inline"
							'col_names' => array($fld),
							'name' => $fld,
							'keyword' => NULL,
							#'type' => $match[3], // "USING [type_name]"
						);

					if( empty($match[1]) )
					{
						$primary_key_fields = array($fieldname_lowered);
						unset( $obsolete_indices['PRIMARY'] );
						$add_index['is_PK'] = true;
					}
					else
					{
						$add_index['is_PK'] = false;
					}
					$fields_with_keys[] = $fieldname_lowered;

					$indices[] = $add_index;
				}
			}
			$fields_with_keys = array_unique($fields_with_keys);


			// Fetch the table column structure from the database
			$tablefields = $DB->get_results( 'DESCRIBE '.$table );

			// For every field in the existing table
			foreach($tablefields as $tablefield)
			{
				$fieldname_lowered = strtolower($tablefield->Field);

				if( ! isset($wanted_fields[ $fieldname_lowered ]) )
				{ // This field exists in the table, but not in the creation queries

					if( in_array('drop_column', $exclude_types) )
					{
						if( preg_match('~\bAUTO_INCREMENT\b~i', $tablefield->Extra) )
						{ // must be modified with a ADD COLUMN which drops a PK
							$obsolete_autoincrement = $tablefield;
						}
					}
					else
					{
						$items[$table_lowered][] = array(
							'queries' => array('ALTER TABLE '.$table.' DROP COLUMN '.$tablefield->Field),
						'note' => 'Dropped '.$table.'.<strong>'.$tablefield->Field.'</strong>',
							'type' => 'drop_column' );

						// Unset in key indices:
						if( ($k = array_search($fieldname_lowered, $existing_key_fields)) !== false )
						{
							unset($existing_key_fields[$k]);
						}
						if( ($k = array_search($fieldname_lowered, $existing_primary_fields)) !== false )
						{
							unset($existing_primary_fields[$k]);
						}
					}

					continue;
				}

				$column_definition = trim( $wanted_fields[$fieldname_lowered]['field'] );

				unset($type_matches); // have we detected the type as matching (for optional length param)
				$fieldtype = '';

				// Get the field type from the query
				if( preg_match( '~^'.$tablefield->Field.'\s+ (TINYINT|SMALLINT|MEDIUMINT|INTEGER|INT|BIGINT|REAL|DOUBLE|FLOAT|DECIMAL|DEC|NUMERIC) ( \s* \([\d\s,]+\) )? (\s+ UNSIGNED)? (\s+ ZEROFILL)? (.*)$~ix', $column_definition, $match ) )
				{
					$fieldtype = $match[1];

					if( strtoupper($fieldtype) == 'INTEGER' )
					{ // synonym
						$fieldtype = 'INT';
					}
					elseif( strtoupper($fieldtype) == 'DECIMAL' )
					{ // synonym
						$fieldtype = 'DEC';
					}


					if( isset($match[2]) )
					{
						$fieldtype .= preg_replace( '~\s+~', '', $match[2] );
					}
					if( ! empty($match[3]) )
					{ // "unsigned"
						$fieldtype .= ' '.trim($match[3]);
					}
					if( ! empty($match[4]) )
					{ // "zerofill"
						$fieldtype .= ' '.trim($match[4]);
					}

					$field_to_parse = $match[5];

					// The length param is optional:
					$matches_pattern = '~^'.preg_replace( '~\((\d+)\)~', '(\($1\))?', $tablefield->Type ).'$~i';
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
				$want_default = false;
				if( preg_match( '~^(.*?) \s DEFAULT \s+ (?: (?: (["\']) (.*?) \2 ) | (\w+) ) (\s .*)?$~ix', $field_to_parse, $match ) )
				{
					if( isset($match[4]) && $match[4] !== '' )
					{
						$want_default = $match[4];
						$want_default_set = $match[4];
					}
					else
					{
						$want_default = $match[3];
						$want_default_set = $match[2].$match[3].$match[2];  // encapsulate in quotes again
					}

					$field_to_parse = $match[1].( isset($match[5]) ? $match[5] : '' );
				}


				// KEY
				$has_inline_primary_key = false;
				if( preg_match( '~^(.*) \b (?: (UNIQUE) (?:\s+ (?:INDEX|KEY))? | (?:PRIMARY \s+)? KEY ) \b (.*)$~ix', $field_to_parse, $match ) )
				{ // fields got added to primary_key_fields and fields_with_keys before
					$field_to_parse = $match[1].$match[3];
					if( empty($match[2]) )
					{
						$has_inline_primary_key = true; // we need to DROP the PK if this column definition does not match
					}
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

					if( in_array( $fieldname_lowered, $existing_key_fields ) )
					{
						if( ! empty( $primary_key_fields ) )
						{
							$column_definition .= ', DROP PRIMARY KEY';
						}
					}
					else
					{ // a key for this AUTO_INCREMENT field does not exist yet, we search it in $indices
						foreach( $indices as $k_index => $l_index )
						{ // go through the indexes we want to have

							if( array_search( $fieldname_lowered, $l_index['col_names'] ) === false )
							{ // this is not an index for our column
								continue;
							}

							// this index definition affects us, we have to add it to our ALTER statement..

							// See if we need to drop it, before adding it:
							if( $l_index['is_PK'] )
							{ // Part of a PRIMARY key..
								if( ! empty( $existing_primary_fields ) )
								{ // and a PRIMARY key exists already
									$column_definition .= ', DROP PRIMARY KEY';
								}
								$existing_primary_fields = array(); // we expect no existing primary key anymore
								$primary_key_fields = $l_index['col_names']; // this becomes our primary key
							}
							elseif( isset( $existing_indices[$l_index['name']] ) )
							{ // this index already exists, drop it:
								$column_definition .= ', DROP INDEX '.$existing_indices[$l_index['name']]; // original case
								unset( $existing_indices[$l_index['name']] ); // we expect that it does not exist anymore
								if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
								{ // add te field to the list of keys we want/expect to have:
									$fields_with_keys[] = $fieldname_lowered;
								}
							}

							// Merge the INDEX creation into our ALTER query:
							$column_definition .= ', ADD '.$l_index['create_definition'];
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


				// See what DEFAULT we would get or want
				$update_default = NULL;
				$update_default_set = NULL;

				if( $want_default !== false )
				{
					$update_default = $want_default;
					$update_default_set = $want_default_set;
				}
				else
				{ // implicit default, see http://dev.mysql.com/doc/refman/4.1/en/data-type-defaults.html
					if( preg_match( '~^(TINYINT|SMALLINT|MEDIUMINT|INTEGER|INT|BIGINT|REAL|DOUBLE|FLOAT|DECIMAL|DEC|NUMERIC)$~i', $fieldtype ) )
					{ // numeric
						$update_default = '0';
						$update_default_set = '0';
					}
					elseif( strtoupper($fieldtype) == 'TIMESTAMP' )
					{ // TODO: the default should be current date and time for the first field - but AFAICS we won't have NULL fields anyway
					}
					elseif( preg_match( '~^(DATETIME|DATE|TIME|YEAR)$~i', $fieldtype ) )
					{
						$update_default = '0'; // short form for various special "zero" values
						$update_default_set = '0';
					}
					elseif( preg_match( '~^ENUM\(~i', $fieldtype ) )
					{
						$update_default_set = trim(substr( $fieldtype, 5, strpos($fieldtype, ',')-5 )); // first value
						$update_default = preg_replace( '~^(["\'])(.*)\1$~', '$2', $update_default_set ); // without quotes
					}
					else
					{
						$update_default_set = "''"; // empty string for string types
						$update_default = '';
					}
				}


				// Is actual field type different from the field type in query?
				if( ! $type_matches || $change_null )
				{ // Change the whole column to $column_definition:
					/*
					echo '<h2>No_Match</h2>';
					pre_dump( $tablefield, $column_definition );
					pre_dump( 'flds', $flds );
					pre_dump( 'wanted_fields', $wanted_fields );
					pre_dump( strtolower($tablefield->Type), strtolower($fieldtype), $column_definition );
					*/

					$queries = array( 'ALTER TABLE '.$table );

					// Handle inline PRIMARY KEY definition:
					if( $has_inline_primary_key && ! empty($existing_primary_fields) ) // there's a PK that needs to get removed
					{ // the column is part of the PRIMARY KEY, which needs to get dropped before (we already handle that for AUTO_INCREMENT fields)
						$queries[0] .= ' DROP PRIMARY KEY,';
						$existing_primary_fields = array(); // we expect no existing primary key anymore
						unset( $obsolete_indices['PRIMARY'] );
					}

					$queries[0] .= ' CHANGE COLUMN '.$tablefield->Field.' '.$column_definition;

					// Handle changes from "NULL" to "NOT NULL"
					if( $change_null && ! $want_null && isset($update_default_set) )
					{ // Prepend query to update NULL fields to default
						array_unshift( $queries, 'UPDATE '.$table.' SET '.$fieldname.' = '.$update_default_set.' WHERE '.$fieldname.' IS NULL' );

						if( substr( $tablefield->Type, 0, 5 ) == 'enum(' )
						{
							$existing_enum_field_values = preg_split( '~\s*,\s*~', substr( $tablefield->Type, 5, -1 ), -1, PREG_SPLIT_NO_EMPTY );

							foreach( $existing_enum_field_values as $k => $v )
							{
								$existing_enum_field_values[$k] = preg_replace( '~^(["\'])(.*)\1$~', '$2', $v ); // strip quotes
							}

							if( ! in_array( $update_default, $existing_enum_field_values ) )
							{ // we cannot update straight to the new default, because it does not exist yet!

								// Update the column first, without the NULL change
								array_unshift( $queries, 'ALTER TABLE '.$table.' CHANGE COLUMN '.$tablefield->Field.' '.preg_replace( '~\sNOT\s+NULL~i', '', $column_definition ) );
							}
						}
					}

					// Add a query to change the column type
					$items[$table_lowered][] = array(
						'queries' => $queries,
						'note' => 'Changed type of '.$table.'.<strong>'.$tablefield->Field.'</strong> from '.$tablefield->Type.' to '.$column_definition,
						'type' => 'change_column' );
				}
				else
				{ // perhaps alter or drop DEFAULT:
					if( $want_default !== false )
					{ // DEFAULT given
						$existing_default = $tablefield->Default === NULL ? 'NULL' : $tablefield->Default;

						if( $existing_default != $want_default ) // DEFAULT is case-sensitive
						{ // Add a query to change the column's default value
							$items[$table_lowered][] = array(
								'queries' => array('ALTER TABLE '.$table.' ALTER COLUMN '.$tablefield->Field.' SET DEFAULT '.$want_default_set),
								'note' => "Changed default value of {$table}.<strong>{$tablefield->Field}</strong> from $existing_default to $want_default_set",
								'type' => 'change_default' );
						}
					}
					elseif( ! empty($tablefield->Default) && $tablefield->Default != $update_default )
					{ // No DEFAULT given, but it exists one, so drop it (IF not a TIMESTAMP or DATETIME field)
						if( $tablefield->Type != 'timestamp' && $tablefield->Type != 'datetime' )
						{
							$items[$table_lowered][] = array(
								'queries' => array('ALTER TABLE '.$table.' ALTER COLUMN '.$tablefield->Field.' DROP DEFAULT'),
								'note' => "Dropped default value of {$table}.<strong>{$tablefield->Field}</strong>",
								'type' => 'change_default' ); // might be also 'drop_default'
						}
					}
				}

				// Remove the field from the array (so it's not added)
				unset($wanted_fields[$fieldname_lowered]);
			}


			foreach($wanted_fields as $fieldname_lowered => $fielddef)
			{ // For every remaining field specified for the table
				$column_definition = $fielddef['field'].' '.$fielddef['where'];

				$is_auto_increment = false;
				// AUTO_INCREMENT (with special index handling: AUTO_INCREMENT fields need to be PRIMARY or UNIQUE)
				if( preg_match( '~(.*?) \b AUTO_INCREMENT \b (.*)$~ix', $fielddef['field'], $match ) )
				{
					if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
					{ // no KEY defined (but required for AUTO_INCREMENT fields)
						debug_die('No KEY/INDEX defined for AUTO_INCREMENT column!');
					}
					$is_auto_increment = true;


					foreach( $indices as $k_index => $l_index )
					{ // go through the indexes we want to have

						if( array_search( $fieldname_lowered, $l_index['col_names'] ) === false )
						{ // this is not an index for our column
							continue;
						}

						// this index definition affects us, we have to add it to our ALTER statement..

						// See if we need to drop it, before adding it:
						if( $l_index['is_PK'] )
						{ // Part of a PRIMARY key..
							if( ! empty( $existing_primary_fields ) )
							{ // and a PRIMARY key exists already
								$column_definition .= ', DROP PRIMARY KEY';
							}
							$existing_primary_fields = array(); // we expect no existing primary key anymore
							$primary_key_fields = $l_index['col_names']; // this becomes our primary key
						}
						elseif( isset( $existing_indices[$l_index['name']] ) )
						{ // this index already exists, drop it:
							$column_definition .= ', DROP INDEX '.$existing_indices[$l_index['name']]; // original case
							unset( $existing_indices[$l_index['name']] ); // we expect that it does not exist anymore
							if( ! in_array( $fieldname_lowered, $fields_with_keys ) )
							{ // add te field to the list of keys we want/expect to have:
								$fields_with_keys[] = $fieldname_lowered;
							}
						}

						// Merge the INDEX creation into our ALTER query:
						$column_definition .= ', ADD '.$l_index['create_definition'];
						unset( $indices[$k_index] );
					}
				}

				// Push a query line into $items that adds the field to that table
				$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$column_definition;

				// Handle inline PRIMARY KEY definition:
				if( preg_match( '~^(.*) \b (?: (UNIQUE) (?:\s+ (?:INDEX|KEY))? | (?:PRIMARY \s+)? KEY ) \b (.*)$~ix', $column_definition, $match ) // "has_inline_primary_key"
						&& count($existing_primary_fields)
						&& ! in_array($fieldname_lowered, $existing_primary_fields) )
				{ // the column is part of the PRIMARY KEY, which needs to get dropped before (we already handle that for AUTO_INCREMENT fields)
					$query .= ', DROP PRIMARY KEY';
					$existing_primary_fields = array(); // we expect no existing primary key anymore
					unset( $obsolete_indices['PRIMARY'] );

					if( isset($obsolete_autoincrement) )
					{
						$query .= ', MODIFY COLUMN '.$obsolete_autoincrement->Field.' '.$obsolete_autoincrement->Type.' '.( $obsolete_autoincrement->Field == 'YES' ? 'NULL' : 'NOT NULL' );
					}
				}

				$items[$table_lowered][] = array(
					'queries' => array($query),
					'note' => 'Added column '.$table.'.<strong>'.$fielddef['field'].'</strong>',
					'type' => 'add_column' );
			}


			// Remove the original table creation query from processing
			array_shift( $items[$table_lowered] );


			// Add the remaining indeces (which are not "inline" with a column definition and therefor already handled):
			foreach( $indices as $index )
			{
				if( empty($index['create_definition']) )
				{ // skip "inline"
					continue;
				}
				$query = 'ALTER TABLE '.$table;
				if( $index['is_PK'] && $existing_primary_fields )
				{
					$query .= ' DROP PRIMARY KEY,';
					unset( $obsolete_indices['PRIMARY'] );
				}
				// Push a query line into $items that adds the index to that table
				$items[$table_lowered][] = array(
					'queries' => array($query.' ADD '.$index['create_definition']),
					'note' => 'Added index <strong>'.$index['create_definition'].'</strong>',
					'type' => 'add_index' );
			}


			foreach( $obsolete_indices as $index_info )
			{
				// Push a query line into $items that drops the index from the table
				$items[$table_lowered][] = array(
					'queries' => array("ALTER TABLE {$table} DROP ".( $index_info['name'] == 'PRIMARY' ? 'PRIMARY KEY' : 'INDEX '.$index_info['name'] )),
					'note' => 'Dropped index <strong>'.$index_info['name'].'</strong>',
					'type' => 'drop_index' );
			}
		}
	}


	// Filter types we want to exclude:
	if( ! empty($exclude_types) )
	{
		foreach( $items as $table => $itemlist )
		{
			$removed_one = false;
			foreach( $itemlist as $k => $item )
			{
				if( in_array($item['type'], $exclude_types) )
				{
					unset( $items[$table][$k] );
					$removed_one = true;
				}
			}
			if( $removed_one )
			{ // Re-order (0, 1, 2, ..)
				$items[$table] = array_values($items[$table]);
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
				foreach( $item['queries'] as $query )
				{
					#pre_dump( $query );
					$DB->query( $query );
				}
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
	global $schema_queries, $DB, $debug;

	foreach( $schema_queries as $table => $query_info )
	{
		$items_need_update = db_delta( $query_info[1], array('drop_column', 'drop_index') );

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
					foreach( $item['queries'] as $query )
					{
						$DB->query( $query );
					}
				}
			}
		}
		else
		{ // the same, but with output
			foreach( $items_need_update as $table => $itemlist )
			{
				if( count($itemlist) == 1 && $itemlist[0]['type'] == 'create_table' )
				{
					echo $itemlist[0]['note'].'<br />';
					foreach( $itemlist[0]['queries'] as $query )
					{ // should be just one, but just in case
						$DB->query( $query );
					}
				}
				else
				{
					echo 'Altering table &laquo;'.$table.'&raquo;...';
					echo '<ul>';
					foreach( $itemlist as $item )
					{
						echo '<li>'.$item['note'];
						if( $debug )
						{
							pre_dump( $item['queries'] );
						}
						echo '</li>';
						foreach( $item['queries'] as $query )
						{
							$DB->query( $query );
						}
					}
					echo "</ul>";
				}
			}
		}
	}
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.21  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.20  2006/06/10 00:39:36  blueyed
 * Fixed pattern for matching inline keys
 *
 * Revision 1.19  2006/06/01 17:46:45  fplanque
 * higlight output with strong
 *
 * Revision 1.18  2006/05/17 23:35:42  blueyed
 * cleanup
 *
 * Revision 1.17  2006/05/16 23:22:47  blueyed
 * db_delta: more fixes for inline keys and some cleanup
 *
 * Revision 1.16  2006/05/15 23:40:55  blueyed
 * bugfix for (changing) inline PK
 *
 * Revision 1.15  2006/04/20 15:42:56  blueyed
 * Make sure itemlist returned by db_delta() is ordered.
 *
 * Revision 1.14  2006/04/20 14:59:52  blueyed
 * Fixed moving KEY to PK
 *
 * Revision 1.13  2006/03/21 23:17:17  blueyed
 * doc/cleanup
 *
 * Revision 1.12  2006/03/19 18:16:15  blueyed
 * Fix for default updates
 *
 * Revision 1.11  2006/03/19 17:58:12  blueyed
 * fix for DATETIME
 *
 * Revision 1.10  2006/03/19 15:59:10  blueyed
 * More magic: UPDATE on change to NOT NULL (ENUM)
 *
 * Revision 1.9  2006/03/16 00:32:16  blueyed
 * Fixed PK handling for inline definitions
 *
 * Revision 1.8  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.7  2006/03/12 19:38:29  blueyed
 * fixes
 *
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
