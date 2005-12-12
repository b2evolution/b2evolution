<?php
/**
 * This file implements the Filelist class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 *
 * @version $Id$
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Includes
 */
require_once dirname(__FILE__).'/_file.class.php';


/**
 * Holds a list of File objects.
 *
 * Can hold an arbitrary list of Files.
 * Can list files in a directory by itself.
 * Can walk recursively down a directory tree to list in "flat mode".
 * Can sort file list.
 * Can iterate through list.
 * Cannot hold files with different root type/ID.
 *
 * @see File
 * @package evocore
 */
class Filelist
{
	/**
	 * Root type: 'user', 'group' or 'collection'.
	 *
	 * All files in this list MUST have that same root type. Adding will fail otherwise.
	 *
	 * @var string
	 * @access protected
	 */
	var $_root_type;

	/**
	 * Root ID: ID of the user, the group or the collection the file belongs to...
	 *
	 * All files in this list MUST have that same root ID. Adding will fail otherwise.
	 *
	 * @var integer
	 * @access protected
	 */
	var $_root_ID = 0;

	/**
	 * path to root directory with ending slash, except for absolute.
	 *
	 * @todo remove exception for absolute
	 * @param string
	 * @access protected
	 */
	var $_ads_root_path;

	/**
	 * Path to list with trailing slash.
	 *
	 * false if we are constructing an arbitrary list (i-e not tied to a single directory)
	 *
	 * @var boolean|string
	 * @access protected
	 */
	var $_ads_list_path = false;

	/**
	 * Path to list reltive to root, with trailing slash
	 *
	 * false if we are constructing an arbitrary list (i-e not tied to a single directory)
	 *
	 * @param boolean|string
	 * @access protected
	 */
	var $_rds_list_path = false;

	/**
	 * Filename filter pattern
	 *
	 * Will be matched against the filename part (not the path)
	 * NULL if disabled
	 *
	 * @var NULL|string
	 * @access protected
	 */
	var $_filter = NULL;

	/**
	 * Is the filter a regular expression?
	 *
	 *  NULL if disabled
	 *
	 * @see Filelist::_filter
	 * @var NULL|boolean
	 * @access protected
	 */
	var $_filter_is_regexp = NULL;

	/**
	 * The list of Files.
	 * @var array of File objects
	 * @access protected
	 */
	var $_entries = array();

	/**
	 * Index on File IDs (id => {@link $_entries} key).
	 *
	 * Note: fplanque>> what's the purpose of the md5 IDs??
	 *
	 * @todo make these direct links to &File objects
	 * @var array
	 * @access protected
	 */
	var $_md5_ID_index = array();

	/**
	 * Index on full paths (path => {@link $_entries} key).
	 * @todo make these direct links to &File objects
	 * @var array
	 * @access protected
	 */
	var $_full_path_index = array();

	/**
	 * Index on sort order (order # => {@link $_entries} key).
	 * @todo make these direct links to &File objects
	 * @var array
	 * @access protected
	 */
	var $_order_index = array();

	/**
	 * Number of entries in the {@link $_entries} array
	 *
	 * Note: $_total_entries = $_total_dirs + $_total_files
	 *
	 * @var integer
	 * @access protected
	 */
	var $_total_entries = 0;

	/**
	 * @var integer Number of directories
	 * @access protected
	 */
	var $_total_dirs = 0;

	/**
	 * @var integer Number of files
	 * @access protected
	 */
	var $_total_files = 0;

	/**
	 * @var integer Number of bytes
	 * @access protected
	 */
	var $_total_bytes = 0;

	/**
	 * Index of the current iterator position.
	 *
	 * This is the key of {@link $_order_index}
	 *
	 * @var integer
	 * @access protected
	 */
	var $_current_idx = -1;

	/**
	 * What column is the list ordered on?
	 *
	 * Possible values are: 'name', 'path', 'type', 'size', 'lastmod', 'perms'
	 *
	 * @var string
	 * @access protected
	 */
	var $_order = NULL;

	/**
	 * Are we sorting ascending (or descending).
	 *
	 * NULL is default and means ascending for 'name', descending for the rest
	 *
	 * @todo fplanque>> document possible values!!
	 * @var mixed
	 * @access protected
	 */
	var $_order_asc = NULL;

	/**
	 * User preference: Sort dirs not at top
	 *
	 * @var boolean
	 */
	var $_dirs_not_at_top = false;

	/**
	 * User preference: Load and show hidden files?
	 *
	 * "Hidden files" are prefixed with a dot .
	 *
	 * @var boolean
	 */
	var $_show_hidden_files = true;

	/**
	 * User preference: recursive size of dirs?
	 *
	 * The load() & sort() methods use this.
	 *
	 * @var boolean
	 * @access protected
	 */
	var $_use_recursive_dirsize = false;


	/**
	 * Constructor
	 *
	 * @param boolean|string Default path for the files, false if you want to create an arbitrary list
	 * @param string Root type: 'user', 'group' or 'collection' (has to be the same for all files..)
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 */
	function Filelist( $path, $root_type, $root_ID )
	{
		global $FileRootCache;

		if( !is_null($path) )
		{
			$this->_ads_list_path = $path;
		}

		if( !is_null($root_type) )
		{	// We want to set a root different from default:
			$this->_root_type = $root_type;
			$this->_root_ID = $root_ID;
			$this->_ads_root_path = $FileRootCache->get_root_dir( $root_type, $root_ID );
		}

		if( !empty($this->_ads_list_path) )
		{
			// Get the subpath relative to root
			$this->_rds_list_path = $this->rdfs_relto_root_from_adfs( $this->_ads_list_path );
		}
	}


	/**
	 * Loads or reloads the filelist entries.
	 *
	 * NOTE: this does not work for arbitrary lists!
	 *
	 * @param boolean use flat mode (all files recursive without directories)
	 */
	function load( $flatmode = false )
	{
		global $Messages;

		if( !$this->_ads_list_path )
		{	// We have no path to load from: (happens when FM finds no available root OR we have an arbitrary)
			// echo 'Cannot load a filelist with no list path' ;
			return false;
		}

		// Clears the list (for RE-loads):
		$this->_total_entries = 0;
		$this->_total_bytes = 0;
		$this->_total_files = 0;
		$this->_total_dirs = 0;
		$this->_entries = array();
		$this->_md5_ID_index = array();
		$this->_full_path_index = array();
		$this->_order_index = array();

		// Attempt list files for requested directory: (recursively if flat mode):
		if( ($filepath_array = get_filenames( $this->_ads_list_path, true, true, true, $flatmode )) === false )
		{
			$Messages->add( sprintf( T_('Cannot open directory &laquo;%s&raquo;!'), $this->_ads_list_path ), 'fl_error' );
			return false;
		}

		// Loop through file list:
		foreach( $filepath_array as $adfp_path )
		{
			// Extract the filename from the full path
			$name = basename( $adfp_path );

			// Check for hidden status...
			if( (! $this->_show_hidden_files) && (substr($name, 0, 1) == '.') )
			{ // Do not load & show hidden files (prefixed with .)
				continue;
			}

			// Check filter...
			if( $this->_filter !== NULL )
			{ // Filter: must match filename
				if( $this->_filter_is_regexp )
				{ // Filter is a reg exp:
					if( !preg_match( '#'.str_replace( '#', '\#', $this->_filter ).'#', $name ) )
					{ // does not match the regexp filter
						continue;
					}
				}
				else
				{ // Filter is NOT a regexp:
					if( !my_fnmatch( $this->_filter, $name ) )
					{
						continue;
					}
				}
			}

			// Extract the file's relative path to the root
			$rdfp_path_relto_root = $this->rdfs_relto_root_from_adfs( $adfp_path );
			// echo '<br>'.$rdfp_rel_path;

			// Add the file into current list:
			$this->add_by_subpath( $rdfp_path_relto_root, true );
		}
	}


	/**
	 * Add a File object to the list (by reference).
	 *
	 * @param File File object (by reference)
	 * @param boolean Has the file to exist to get added?
	 * @return boolean true on success, false on failure
	 */
	function add( & $File, $mustExist = false )
	{
		if( !is_a( $File, 'file' ) )
		{	// Passed object is not a File!! :(
			return false;
		}

		// Integrity check:
		if( $File->_root_type != $this->_root_type || $File->_root_ID != $this->_root_ID )
		{
			debug_die( 'Adding file '.$File->_root_type.':'.$File->_root_ID.':'.$File->get_rdfs_rel_path().' to filelist '.$this->_root_type.':'.$this->_root_ID.' : root mismatch!' );
		}

		if( $mustExist && !$File->exists() )
		{	// File does not exist..
			return false;
		}


		$this->_entries[$this->_total_entries] = & $File;
		$this->_md5_ID_index[$File->get_md5_ID()] = $this->_total_entries;
		$this->_full_path_index[$File->get_full_path()] = $this->_total_entries;
		// add file to the end of current list:
		$this->_order_index[$this->_total_entries] = $this->_total_entries;

		// Count 1 more entry (file or dir)
		$this->_total_entries++;

		if( $File->is_dir() )
		{	// Count 1 more directory
			$this->_total_dirs++;

			// fplanque>> TODO: get this outta here??
			if( $this->_use_recursive_dirsize )
			{ // We want to use recursive directory sizes
				// won't be done in the File constructor
				$File->setSize( get_dirsize_recursive( $File->get_full_path() ) );
			}
		}
		else
		{	// Count 1 more file
			$this->_total_files++;
		}

		// Count total bytes in this dir
		$this->_total_bytes += $File->get_size();

		return true;
	}


	/**
	 * Update the name dependent caches
	 *
	 * This is especially useful after a name change of one of the files in the list
	 */
	function update_caches()
	{
		$this->_md5_ID_index = array();
		$this->_full_path_index = array();

		$count = 0;
		foreach( $this->_entries as $loop_File )
		{
			$this->_md5_ID_index[$loop_File->get_md5_ID()] = $count;
			$this->_full_path_index[$loop_File->get_full_path()] = $count;
			$count++;
		}
	}


	/**
	 * Add a file to the list, by filename.
	 *
	 * This is a stub for {@link Filelist::add()}.
	 *
	 * @param string Subpath for this file/folder, relative the associated root, including trailing slash (if directory)
	 * @param boolean Has the file to exist to get added?
	 * @return boolean true on success, false on failure (path not allowed,
	 *                 file does not exist)
	 */
	function add_by_subpath( $rel_path, $mustExist = false )
	{
		global $FileCache;

		$NewFile = & $FileCache->get_by_root_and_path( $this->_root_type, $this->_root_ID, $rel_path );

		return $this->add( $NewFile, $mustExist );
	}


	/**
	 * Sort the entries by sorting the internal {@link $_order_index} array.
	 *
	 * @param string The order to use ('name', 'type', 'lastmod', .. )
	 * @param boolean Ascending (true) or descending
	 * @param boolean Sort directories at top?
	 */
	function sort( $order = NULL, $orderasc = NULL, $dirsattop = NULL )
	{
		if( !$this->_total_entries )
		{
			return false;
		}

		if( $order !== NULL )
		{
			$this->_order = $order;
		}
		if( $orderasc !== NULL )
		{
			$this->_order_asc = $orderasc;
		}
		if( $dirsattop !== NULL )
		{
			$this->_dirs_not_at_top = ! $dirsattop;
		}

		usort( $this->_order_index, array( $this, '_sort_callback' ) );

		// Reset the iterator:
		$this->restart();
	}


	/**
	 * usort callback function for {@link Filelist::sort()}
	 *
	 * @access protected
	 * @return integer
	 */
	function _sort_callback( $a, $b )
	{
		$FileA = & $this->_entries[$a];
		$FileB = & $this->_entries[$b];

		// What colmun are we sorting on?
		switch( $this->_order )
		{
			case 'size':
				if( $this->_use_recursive_dirsize )
				{	// We are using recursive directory sizes:
					$r = $FileA->get_size() - $FileB->get_size();
				}
				else
				{
					$r = $FileA->is_dir() && $FileB->is_dir() ?
									strcasecmp( $FileA->get_name(), $FileB->get_name() ) :
									( $FileA->get_size() - $FileB->get_size() );
				}
				break;

			case 'path': // group by dir
				$r = strcasecmp( $FileA->get_dir(), $FileB->get_dir() );
				if( $r == 0 )
				{
					$r = strcasecmp( $FileA->get_name(), $FileB->get_name() );
				}
				break;

			case 'lastmod':
				$r = $FileB->get_lastmod_ts() - $FileA->get_lastmod_ts();
				break;

			case 'perms':
				// This will use literal representation ( 'r', 'r+w' / octal )
				$r = strcasecmp( $FileA->get_perms(), $FileB->get_perms() );
				break;

			default:
			case 'name':
				$r = strcasecmp( $FileA->get_name(), $FileB->get_name() );
				if( $r == 0 )
				{ // same name: look at path
					$r = strcasecmp( $FileA->get_dir(), $FileB->get_dir() );
				}
				break;
		}


		if( ! $this->_order_asc )
		{ // We want descending order: switch order
			$r = - $r;
		}

		if( ! $this->_dirs_not_at_top )
		{	// We want dirs to be on top, always:
			if( $FileA->is_dir() && !$FileB->is_dir() )
			{
				$r = -1;
			}
			elseif( $FileB->is_dir() && !$FileA->is_dir() )
			{
				$r = 1;
			}
		}

		return $r;
	}


	/**
	 * Reset the iterator
	 */
	function restart()
	{
		$this->_current_idx = -1;
	}


	/**
	 * Are we sorting ascending?
	 *
	 * @param string The type (empty for current order type)
	 * @return integer 1 for ascending sorting, 0 for descending
	 */
	function is_sorting_asc( $col = '' )
	{
		if( $this->_order_asc === NULL )
		{ // We have not specified a sort order by now, use default:
			if( empty($col) )
			{
				$col = $this->_order;
			}
			return ( $col == 'name' || $col == 'path' ) ? 1 : 0;
		}
		else
		{	// Use previsously specified sort order:
			return ( $this->_order_asc ) ? 1 : 0;
		}
	}


	/**
	 * Is a filter active?
	 *
	 * @return boolean
	 */
	function is_filtering()
	{
		return ($this->_filter !== NULL);
	}


	/**
	 * Does the list contain a specific File?
	 *
	 * @param File the File object to look for
	 * @return boolean
	 */
	function contains( & $File )
	{
		return isset( $this->_md5_ID_index[ $File->get_md5_ID() ] );
	}


	/**
	 * Get the order the list is sorted by.
	 *
	 * @return NULL|string
	 */
	function get_sort_order()
	{
		return $this->_order;
	}


	/**
	 * Return the current filter
	 *
	 * @param boolean add a note when it's a regexp or no filter?
	 * @return string the filter
	 */
	function get_filter( $verbose = true )
	{
		if( $this->_filter === NULL )
		{	// Filtering is not active
			return $verbose ? T_('No filter') : '';
		}
		else
		{	// Filtering is active
			return $this->_filter
							.( $verbose && $this->_filter_is_regexp ? ' ('.T_('regular expression').')' : '' );
		}
	}


	/**
	 * Is the current Filter a regexp?
	 *
	 * @return NULL|boolean true if regexp, NULL if no filter set
	 */
	function is_filter_regexp()
	{
		return $this->_filter_is_regexp;
	}


	/**
	 * Get the total number of entries in the list.
	 *
	 * @return integer
	 */
	function count()
	{
		return $this->_total_entries;
	}


	/**
	 * Get the total number of directories in the list
	 *
	 * @return integer
	 */
	function count_dirs()
	{
		return $this->_total_dirs;
	}


	/**
	 * Get the total number of files in the list
	 *
	 * @return integer
	 */
	function count_files()
	{
		return $this->_total_files;
	}


	/**
	 * Get the total number of bytes of all files in the list
	 *
	 * @return integer
	 */
	function count_bytes()
	{
		return $this->_total_bytes;
	}


	/**
	 * Get the next entry and increment internal counter.
	 *
	 * @param string can be used to query only 'file's or 'dir's.
	 * @return boolean|File object (by reference) on success, false on end of list
	 */
	function & get_next( $type = '' )
	{
		/*
		 * DEBUG: return the same file 10 times, useful for profiling
			static $debugMakeLonger = 0;
			if( $debugMakeLonger-- == 0 )
			{
				$this->_current_idx++;
				$debugMakeLonger = 9;
			}
		*/

		if( !isset($this->_order_index[$this->_current_idx + 1]) )
		{	// End of list:
			$r = false;
			return $r;
		}
		$this->_current_idx++;

		$index = $this->_order_index[$this->_current_idx];

		if( $type != '' )
		{
			if( $type == 'dir' && !$this->_entries[ $index ]->is_dir() )
			{ // we want a dir
				$r = $this->get_next( 'dir' );
				return $r;
			}
			elseif( $type == 'file' && $this->_entries[ $index ]->is_dir() )
			{ // we want a file
				$r = $this->get_next( 'file' );
				return $r;
			}
		}

		return $this->_entries[ $index ];
	}


	/**
	 * Get a file by its full path.
	 *
	 * @param string the full path
	 * @return mixed File object (by reference) on success, false on failure.
	 */
	function & get_by_path( $path )
	{
		$path = str_replace( '\\', '/', $path );

		if( isset( $this->_full_path_index[ $path ] ) )
		{
			return $this->_entries[ $this->_full_path_index[ $path ] ];
		}
		else
		{
			$r = false;
			return $r;
		}
	}


	/**
	 * Get a file by it's ID.
	 *
	 * @param string the ID (MD5 of path and name)
	 * @return mixed File object (by reference) on success, false on failure.
	 */
	function & get_by_md5_ID( $md5id )
	{
		if( isset( $this->_md5_ID_index[ $md5id ] ) )
		{
			return $this->_entries[ $this->_md5_ID_index[ $md5id ] ];
		}
		else
		{
			$r = false;
			return $r;
		}
	}


	/**
	 * Get a file by index.
	 *
	 * @param integer Index of the entries (starting with 0)
	 * @return false|File
	 */
	function & getFileByIndex( $index )
	{
		if( isset( $this->_order_index[ $index ] ) )
		{
			return $this->_entries[ $this->_order_index[ $index ] ];
		}
		else
		{
			$r = false;
			return $r;
		}
	}


	function get_root_type()
	{
		return $this->_root_type;
	}


	function get_root_ID()
	{
		return $this->_root_ID;
	}


	/**
	 * Get absolute path to list.
	 */
	function get_ads_list_path()
	{
		return $this->_ads_list_path;
	}

	/**
	 * Get path to list relative to root.
	 */
	function get_rds_list_path()
	{
		return $this->_rds_list_path;
	}


	/**
	 * Get the path (and name) of a {@link File} relative to the {@link Filelist::_ads_root_path}.
	 *
	 * @param string
	 * @return string
	 */
	function rdfs_relto_root_from_adfs( $adfs_path )
	{
		// Check that the file is inside root:
		if( substr( $adfs_path, 0, strlen($this->_ads_root_path) ) != $this->_ads_root_path )
		{
			debug_die( 'rdfs_relto_root_from_adfs: Path is NOT inside of root!' );
		}

		// Return only the relative part:
 		return substr( $adfs_path, strlen($this->_ads_root_path) );
	}


	/**
	 * Removes a {@link File} from the entries list.
	 *
	 * This handles indexes and number of total entries, bytes, files/dirs.
	 *
	 * @return boolean true on success, false if not found in list.
	 */
	function remove( & $File )
	{
		if( isset( $this->_md5_ID_index[ $File->get_md5_ID() ] ) )
		{
			$this->_total_entries--;
			$this->_total_bytes -= $File->get_size();

			if( $File->is_dir() )
			{
				$this->_total_dirs--;
			}
			else
			{
				$this->_total_files--;
			}

			// unset from indexes
			$index = $this->_full_path_index[ $File->get_full_path() ]; // current index
			unset( $this->_entries[ $this->_md5_ID_index[ $File->get_md5_ID() ] ] );
			unset( $this->_md5_ID_index[ $File->get_md5_ID() ] );
			unset( $this->_full_path_index[ $File->get_full_path() ] );

			// get the ordered index right: move all next files downwards
			$order_key = array_search( $index, $this->_order_index );
			unset( $this->_order_index[$order_key] );
			$this->_order_index = array_values( $this->_order_index );

			if( $this->_current_idx > -1 && $this->_current_idx >= $order_key )
			{ // We have removed a file before or at the $order_key'th position
				$this->_current_idx--;
			}
			return true;
		}
		return false;
	}


	/**
	 * Get the list of File entries.
	 *
	 * You can use a method on each object to get this as result instead of the object
	 * itself.
	 *
	 * @param string Use this method on every File and put the result into the list.
	 * @return array The array with the File objects or method results
	 */
	function get_array( $method = NULL )
	{
		$r = array();

		if( is_string($method) )
		{
			foreach( $this->_order_index as $index )
			{
				$r[] =& $this->_entries[ $index ]->$method();
			}
		}
		else
		{
			foreach( $this->_order_index as $index )
			{
				$r[] =& $this->_entries[ $index ];
			}
		}

		return $r;
	}


	/**
	 * Get a MD5 checksum over the entries.
	 * Used to identify a unique filelist.
	 *
	 * @return string md5 hash
	 */
	function md5_checksum()
	{
		return md5( serialize( $this->_entries ) );
	}


	/**
	 * Attempt to load meta data for all files in the list.
	 *
	 * Will attempt only once per file and cache the result.
	 */
	function load_meta()
	{
		global $DB, $Debuglog, $FileCache;

		$to_load = array();

		foreach( $this->_entries as $loop_File )
		{	// For each file:
			// echo $loop_File->get_full_path();

			if( $loop_File->meta != 'unknown' )
			{ // We have already loaded meta data:
				continue;
			}

			$to_load[] = $DB->quote( $loop_File->get_rdfp_rel_path() );
		}

		if( ! count( $to_load ) )
		{	// We don't need to load anything...
			return false;
		}

		if( ! $rows = $DB->get_results( "SELECT *
																			 FROM T_files
																			WHERE file_root_type = '$this->_root_type'
																				AND file_root_ID = $this->_root_ID
																				AND file_path IN (".implode( ',', $to_load ).')',
																			OBJECT, 'Load FileList meta data' ) )
		{ // We haven't found any meta data...
			return false;
		}

		// Go through rows of loaded meta data...
		foreach( $rows as $row )
		{
			// Retrieve matching File object:
			$loop_File = & $FileCache->get_by_root_and_path( $row->file_root_type, $row->file_root_ID, $row->file_path );

			// Associate meta data to File object:
			$loop_File->load_meta( false, $row );
		}

		return true;
	}
}

/*
 * $Log$
 * Revision 1.44  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.43  2005/12/09 16:16:03  blueyed
 * remove(): fix _current_idx decrementing; tightened $_order_index
 *
 * Revision 1.42  2005/11/27 22:52:28  blueyed
 * Fix returning by reference.
 *
 * Revision 1.41  2005/11/22 04:16:53  blueyed
 * fixed remove(); root type 'absolute' not supported!
 *
 * Revision 1.40  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 * Revision 1.36  2005/11/18 07:53:05  blueyed
 * use $_FileRoot / $FileRootCache for absolute path, url and name of roots.
 *
 * Revision 1.35  2005/11/03 18:23:44  fplanque
 * minor
 *
 * Revision 1.34  2005/11/02 20:11:19  fplanque
 * "containing entropy"
 *
 * Revision 1.33  2005/11/01 21:55:54  blueyed
 * Renamed retrieveFiles() to get_filenames(), added $basename parameter and fixed inner recursion (wrong params where given)
 *
 * Revision 1.32  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.30  2005/05/24 15:26:52  fplanque
 * cleanup
 *
 * Revision 1.29  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.28  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.27  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.26  2005/05/11 17:53:47  fplanque
 * started multiple roots handling in file meta data
 *
 * Revision 1.25  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.24  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.23  2005/04/27 19:05:46  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.21  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.20  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.19  2005/01/26 17:55:23  blueyed
 * catching up..
 *
 * Revision 1.17  2005/01/08 22:10:43  blueyed
 * really fixed filelist (hopefully)
 *
 * Revision 1.16  2005/01/08 12:54:03  blueyed
 * fixed/refactored sort()
 *
 * Revision 1.15  2005/01/08 01:24:19  blueyed
 * filelist refactoring
 *
 * Revision 1.14  2005/01/06 15:45:35  blueyed
 * Fixes..
 *
 * Revision 1.13  2005/01/06 11:31:45  blueyed
 * bugfixes
 *
 * Revision 1.12  2005/01/06 10:15:45  blueyed
 * FM upload and refactoring
 *
 * Revision 1.11  2005/01/05 03:04:01  blueyed
 * refactored
 *
 * Revision 1.5  2004/11/03 00:58:02  blueyed
 * update
 *
 * Revision 1.4  2004/10/24 22:55:12  blueyed
 * upload, fixes, ..
 *
 * Revision 1.3  2004/10/21 00:14:44  blueyed
 * moved
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.12  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>