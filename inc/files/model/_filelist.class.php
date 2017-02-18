<?php
/**
 * This file implements the Filelist class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'files/model/_file.class.php', 'File' );


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
	 * Flat mode? (all files recursive without dirs)
	 * @param boolean
	 */
	var $flatmode;

	/**
	 * Do we want to include directories? (This gets used by {@link load()}).
	 * @var boolean
	 */
	var $include_dirs = true;

	/**
	 * Do we want to include files? (This gets used by {@link load()}).
	 * @var boolean
	 */
	var $include_files = true;

	/**
	 * The root of the file list.
	 *
	 * All files in this list MUST have that same FileRoot. Adding will fail otherwise.
	 *
	 * @var FileRoot
	 */
	var $_FileRoot;

	/**
	 * Path to list with trailing slash.
	 *
	 * false if we are constructing an arbitrary list (i-e not tied to a single directory)
	 * fp> should use NULL instead of false
	 *
	 * @var string
	 * @access protected
	 */
	var $_ads_list_path = false;

	/**
	 * Path to list reltive to root, with trailing slash
	 *
	 * false if we are constructing an arbitrary list (i-e not tied to a single directory)
	 * fp> should use NULL instead of false
	 *
	 * @param string
	 * @access protected
	 */
	var $_rds_list_path = false;

	/**
	 * Filename filter pattern
	 *
	 * Will be matched against the filename part (not the path)
	 * NULL if disabled
	 *
	 * Can be a regular expression (see {@link Filelist::$_filter_is_regexp}), internally with delimiters/modifiers!
	 *
	 * Use {@link set_filter()} to set it.
	 *
	 * @var string
	 * @access protected
	 */
	var $_filter = NULL;

	/**
	 * Is the filter a regular expression? NULL if unknown
	 *
	 * Use {@link set_filter()} to set it.
	 *
	 * @see Filelist::$_filter
	 * @var boolean
	 * @access protected
	 */
	var $_filter_is_regexp = NULL;

	/**
	 * The list of Files.
	 * @var array
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
	 * Index on (r)elative (s)lash terminated (f)ile/(d)irectory paths (rdfs_path => key into {@link $_entries}).
	 *
	 * @todo make these direct links to &File objects
	 *
	 * @var array
	 * @access protected
	 */
	var $_rdfs_rel_path_index = array();

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
	 * Number of directories
	 * @var integer
	 * @access protected
	 */
	var $_total_dirs = 0;

	/**
	 * Number of files
	 * @var integer
	 * @access protected
	 */
	var $_total_files = 0;

	/**
	 * Number of bytes
	 * @var integer
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
	 * Are we sorting ascending (or descending)?
	 *
	 * NULL is default and means ascending for 'name', descending for the rest
	 *
	 * @var boolean
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
	var $_show_hidden_files = false;

	/**
	 * User preference: Load and show _evocache folder?
	 *
	 * @var boolean
	 */
	var $_show_evocache = false;

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
	 * @param FileRoot See FileRootCache::get_by_type_and_ID()
	 * @param boolean|string Default path for the files, false if you want to create an arbitrary list; NULL for the Fileroot's ads_path.
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 */
	function __construct( $FileRoot, $path = NULL )
	{
		global $AdminUI;

		if( ! is_object($FileRoot) )
		{
			debug_die( 'Fatal: $FileRoot is no object!' );
		}

		if( is_null($path) )
		{
			$path = $FileRoot->ads_path;
		}
		$this->_ads_list_path = $path;
		$this->_FileRoot = & $FileRoot;

		if( ! empty($this->_ads_list_path) )
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
	 * @uses $flatmode
	 * @return boolean True on sucess, false on failure (not accessible)
	 */
	function load()
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
		$this->_rdfs_rel_path_index = array();
		$this->_order_index = array();

		// Attempt to list files for requested directory: (recursively if flat mode):
		$filename_params = array(
				'inc_files'		=> $this->include_files,
				'inc_dirs'		=> $this->include_dirs,
				'recurse'		=> $this->flatmode,
				'inc_hidden'	=> $this->_show_hidden_files,
				'inc_evocache'	=> $this->_show_evocache,
			);
		if( ($filepath_array = get_filenames( $this->_ads_list_path, $filename_params )) === false )
		{
			$Messages->add( sprintf( T_('Cannot open directory &laquo;%s&raquo;!'), $this->_ads_list_path ), 'error' );
			return false;
		}

		// Loop through file list:
		foreach( $filepath_array as $adfp_path )
		{
			// Extract the filename from the full path
			$name = basename( $adfp_path );

			// Check for hidden status...
			if( ( ! $this->_show_hidden_files) && (substr($name, 0, 1) == '.') )
			{ // Do not load & show hidden files (prefixed with .)
				continue;
			}

			// Check for _evocache...
			if( ( ! $this->_show_evocache ) && ( $name == '_evocache') )
			{ // Do not load & show _evocache folder
				continue;
			}

			// Check filter...
			if( $this->_filter !== NULL )
			{ // Filter: must match filename
				if( $this->_filter_is_regexp )
				{ // Filter is a reg exp:
					if( ! preg_match( $this->_filter, $name ) )
					{ // does not match the regexp filter
						continue;
					}
				}
				else
				{ // Filter is NOT a regexp:
					if( ! fnmatch( $this->_filter, $name ) )
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

		return true;
	}


	/**
	 * Add a File object to the list (by reference).
	 *
	 * @param object File object (by reference)
	 * @param boolean Has the file to exist to get added?
	 * @return boolean true on success, false on failure
	 */
	function add( $File, $mustExist = false )
	{
		if( !( $File instanceof file ) )
		{	// Passed object is not a File!! :(
			return false;
		}

		// Integrity check:
		if( $File->_FileRoot->ID != $this->_FileRoot->ID )
		{
			debug_die( 'Adding file '.$File->_FileRoot->ID.':'.$File->get_rdfs_rel_path().' to filelist '.$this->_FileRoot->ID.' : root mismatch!' );
		}

		if( $mustExist && ! $File->exists() )
		{	// File does not exist..
			return false;
		}


		$this->_entries[$this->_total_entries] = $File;
		$this->_md5_ID_index[$File->get_md5_ID()] = $this->_total_entries;
		$this->_full_path_index[$File->get_full_path()] = $this->_total_entries;
		$this->_rdfs_rel_path_index[$File->get_rdfs_rel_path()] = $this->_total_entries;
		// add file to the end of current list:
		$this->_order_index[$this->_total_entries] = $this->_total_entries;

		// Count 1 more entry (file or dir)
		$this->_total_entries++;

		if( $File->is_dir() )
		{	// Count 1 more directory
			$this->_total_dirs++;
		}
		else
		{	// Count 1 more file
			$this->_total_files++;
		}

		// Count total bytes in this dir
		$this->_total_bytes += $this->get_File_size($File);

		return true;
	}


	/**
	 * Get the size of a given File, according to $_use_recursive_dirsize.
	 * @param File
	 * @return int bytes
	 */
	function get_File_size($File)
	{
		if( $this->_use_recursive_dirsize )
		{
			return $File->get_recursive_size();
		}
		else
		{
			return $File->get_size();
		}
	}


	/**
	 * Get size of the file/dir, formatted to nearest unit (kb, mb, etc.)
	 *
	 * @uses bytesreadable()
	 * @param File
	 * @return string size as b/kb/mb/gd; or '&lt;dir&gt;'
	 */
	function get_File_size_formatted($File)
	{
		if( $this->_use_recursive_dirsize || ! $File->is_dir() )
		{
			return bytesreadable($File->get_recursive_size());
		}

        return /* TRANS: short for '<directory>' */ T_('&lt;dir&gt;');
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
		$this->_rdfs_rel_path_index = array();

		$count = 0;
		foreach( $this->_entries as $loop_File )
		{
			$this->_md5_ID_index[$loop_File->get_md5_ID()] = $count;
			$this->_full_path_index[$loop_File->get_full_path()] = $count;
			$this->_rdfs_rel_path_index[$loop_File->get_rdfs_rel_path()] = $count;
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
		$FileRoot = & $this->get_FileRoot();

		if( $FileRoot->contains( $rel_path ) )
		{	// If a file is really contained in the FileRoot of this list:
			$FileCache = & get_FileCache();
			$NewFile = & $FileCache->get_by_root_and_path( $this->_FileRoot->type, $this->_FileRoot->in_type_ID, $rel_path );

			// Add a file to this list:
			return $this->add( $NewFile, $mustExist );
		}
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
		if( ! $this->_total_entries )
		{
			return false;
		}

		if( $order !== NULL )
		{ // New order
			$this->_order = $order;
		}
		elseif( $this->_order === NULL )
		{ // Init
			$this->_order = 'name';
		}

		if( $orderasc !== NULL )
		{ // New ascending/descending setting
			$this->_order_asc = $orderasc;
		}
		elseif( $this->_order_asc === NULL )
		{ // Init: ascending for 'name' and 'path', else descending
			$this->_order_asc = ( ( $this->_order == 'name' || $this->_order == 'path' ) ? 1 : 0 );
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

		// What column are we sorting on?
		// TODO: dh> this should probably fallback to sorting by name always if $r==0
		switch( $this->_order )
		{
			case 'size':
				$r = $this->get_File_size($FileA) - $this->get_File_size($FileB);
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

			case 'fsowner':
				$r = strcasecmp( $FileA->get_fsowner_name(), $FileB->get_fsowner_name() );
				break;

			case 'fsgroup':
				$r = strcasecmp( $FileA->get_fsgroup_name(), $FileB->get_fsgroup_name() );
				break;

			case 'type':
				if( $r = strcasecmp( $FileA->get_type(), $FileB->get_type() ) )
				{
					break;
				}
				// same type: continue to name:
			default:
			case 'name':
				$r = strnatcmp( $FileA->get_name(), $FileB->get_name() );
				if( $r == 0 )
				{ // same name: look at path
					$r = strnatcmp( $FileA->get_dir(), $FileB->get_dir() );
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
	 * Get the used order.
	 *
	 * @return string
	 */
	function get_sort_order()
	{
		return $this->translate_order( $this->_order );
	}


	/**
	 * Get the link to sort by a column. Handle current order and appends an
	 * icon to reflect the current state (ascending/descending), if the column
	 * is the same we're sorting by.
	 *
	 * @todo get this outta here. This is NOT a displayable object.
	 * We might want to have a "FileListResults" object that derives from Widget/Results/FilteredResults (the more the better)
	 * This object is what the SQL or the ItemQuery object is to Results or to ItemList2. The model and the display should not be mixed.
	 * IF NOT doing the clean objects, move this at least to file.funcs.
	 *
	 * @param string The type (name, path, size, ..)
	 * @param string The text for the anchor.
	 * @return string
	 */
	function get_sort_link( $type, $atext )
	{
		global $AdminUI;

		$newAsc = $this->_order == $type ? (1 - $this->is_sorting_asc()) :  1;

		$r = '<a href="'.regenerate_url( 'fm_order,fm_orderasc', 'fm_order='.$type.'&amp;fm_orderasc='.$newAsc ).'" title="'.T_('Change Order').'"';

		/**
		 * @todo get this outta here. This is NOT a displayable object.
		 * We might want to have a "FileListResults" object that derives from Widget/Results/FilteredResults (the more the better)
		 * This object is what the SQL or the ItemQuery object is to Results or to ItemList2. The model and the display should not be mixed.
		 * IF NOT doing the clean objects, move this at least to file.funcs.
		 */
		$result_params = $AdminUI->get_template('Results');


		// Sorting icon:
		if( $this->_order != $type )
		{ // Not sorted on this column:
			$r .= ' class="basic_sort_link">'.$result_params['basic_sort_off'];
		}
		elseif( $this->is_sorting_asc($type) )
		{ // We are sorting on this column , in ascneding order:
			$r .=	' class="basic_current">'.$result_params['basic_sort_asc'];
		}
		else
		{ // Descending order:
			$r .=	' class="basic_current">'.$result_params['basic_sort_desc'];
		}

		$r .= ' '.$atext;


		return $r.'</a>';
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
	 * Set the filter.
	 *
	 * @param string Filter string (for regular expressions, if no delimiter/modifiers are included, we try magically adding them)
	 * @param boolean Is the filter a regular expression? (it's a glob pattern otherwise)
	 */
	function set_filter( $filter_string, $filter_is_regexp )
	{
		global $Messages;

		$this->_filter_is_regexp = $filter_is_regexp;

		if( $this->_filter_is_regexp && ! empty($filter_string) )
		{
			if( ! is_regexp( $filter_string, true ) )
			{
				// Try with adding delimiters:
				$filter_string_delim = '~'.str_replace( '~', '\~', $filter_string ).'~';
				if( is_regexp( $filter_string_delim, true ) )
				{
					$filter_string = $filter_string_delim;
				}
				else
				{
					$Messages->add( sprintf( T_('The filter &laquo;%s&raquo; is not a regular expression.'), $filter_string ), 'error' );
					$filter_string = '~.*~';
				}
			}
		}

		$this->_filter = empty($filter_string) ? NULL : $filter_string;
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
	 * @return File object (by reference) on success, false on end of list
	 */
	function & get_next( $type = '' )
	{
		for(;;)
		{
			if( !isset($this->_order_index[$this->_current_idx + 1]) )
			{	// End of list:
				$r = false;
				return $r;
			}
			$this->_current_idx++;

			$index = $this->_order_index[$this->_current_idx];
			if( $type != '' )
			{
				$is_dir = $this->_entries[$index]->is_dir();
				if( ($type == 'dir' && ! $is_dir )
					|| ($type == 'file' && $is_dir) )
				{ // we want another type
					continue;
				}
			}
			break;
		}

		return $this->_entries[ $index ];
	}


	/**
	 * Get a file by its relative (to root) path.
	 *
	 * @param string the RELATIVE path (with ending slash for directories)
	 * @return mixed File object (by reference) on success, false on failure.
	 */
	function & get_by_rdfs_path( $rdfs_path )
	{
		// We probably don't need the windows backslashes replacing any more but leave it for safety because it doesn't hurt:
		$path = str_replace( '\\', '/', $rdfs_path );

		if( isset( $this->_rdfs_rel_path_index[ $rdfs_path ] ) )
		{
			return $this->_entries[ $this->_rdfs_rel_path_index[ $rdfs_path ] ];
		}
		else
		{
			$r = false;
			return $r;
		}
	}


	/**
	 * Get a file by its full path.
	 *
	 * @param string the full/absolute path (with ending slash for directories)
	 * @return mixed File object (by reference) on success, false on failure.
	 */
	function & get_by_full_path( $adfs_path )
	{
		// We probably don't need the windows backslashes replacing any more but leave it for safety because it doesn't hurt:
		$path = str_replace( '\\', '/', $adfs_path );

		if( isset( $this->_full_path_index[ $adfs_path ] ) )
		{
			return $this->_entries[ $this->_full_path_index[ $adfs_path ] ];
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
	 * @param boolean added by fp (set to false when it's a problem)
	 * @return File
	 */
	function & get_by_idx( $index, $halt_on_error = true )
	{
		if( isset( $this->_order_index[ $index ] ) )
		{
			return $this->_entries[ $this->_order_index[ $index ] ];
		}
		elseif( !$halt_on_error )
		{
			$r = false;
			return $r;
		}

		debug_die( 'Requested file does not exist!' );
	}


	/**
	 * Get the FileLists FileRoot
	 *
	 * @return FileRoot
	 */
	function & get_FileRoot()
	{
		return $this->_FileRoot;
	}


	/**
	 * Get the FileLists root type.
	 *
	 * @return string
	 */
	function get_root_type()
	{
		return $this->_FileRoot->type;
	}


	/**
	 * Get the FileLists root ID (in_type_ID).
	 *
	 * @return FileRoot
	 */
	function get_root_ID()
	{
		return $this->_FileRoot->in_type_ID;
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
	 * Get the path (and name) of a {@link File} relative to the {@link Filelist::$_FileRoot::$ads_path}.
	 *
	 * @param string
	 * @return string
	 */
	function rdfs_relto_root_from_adfs( $adfs_path )
	{
		// Check that the file is inside root:
		if( substr( $adfs_path, 0, strlen($this->_FileRoot->ads_path) ) != $this->_FileRoot->ads_path )
		{
			debug_die( 'rdfs_relto_root_from_adfs: Path is NOT inside of root!' );
		}

		// Return only the relative part:
		return substr( $adfs_path, strlen($this->_FileRoot->ads_path) );
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
			$this->_total_bytes -= $this->get_File_size($File);

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
			unset( $this->_rdfs_rel_path_index[ $File->get_rdfs_rel_path() ] );

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
				$r[] = $this->_entries[ $index ]->$method();
			}
		}
		else
		{
			foreach( $this->_order_index as $index )
			{
				$r[] = & $this->_entries[ $index ];
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
		global $DB, $Debuglog;

		$to_load = array();

		for( $i=0; $i<count($this->_entries); $i++ )
		{	// For each file:
			$loop_File = & $this->_entries[$i];
			// echo '<br>'.$loop_File->get_full_path();

			if( $loop_File->meta != 'unknown' )
			{ // We have already loaded meta data:
				continue;
			}

			$to_load[] = $DB->quote( md5( $this->_FileRoot->type.$this->_FileRoot->in_type_ID.$loop_File->get_rdfp_rel_path(), true ) );
		}

		if( count( $to_load ) )
		{	// We have something to load...
			/**
			 * @var FileCache
			 */
			$FileCache = & get_FileCache();

			$rows = $DB->get_results( "
				SELECT *
				  FROM T_files
				 WHERE file_path_hash IN (".implode( ',', $to_load ).")",
				OBJECT, 'Load FileList meta data' );

			if( count($rows) )
			{ // Go through rows of loaded meta data...
				foreach( $rows as $row )
				{
					// Retrieve matching File object:
					/**
					 * @var File
					 */
					$loop_File = & $FileCache->get_by_root_and_path( $row->file_root_type, $row->file_root_ID, $row->file_path );

					// Associate meta data to File object:
					$loop_File->load_meta( false, $row );
				}
			}
		}

		// For all Files that still have no meta data, memorize that we could not find any meta data
		for( $i=0; $i<count($this->_entries); $i++ )
		{	// For each file:
			$loop_File = & $this->_entries[$i];

			if( $loop_File->meta == 'unknown' )
			{
				$loop_File->meta = 'notfound';
			}
		}

		// Has sth been loaded?
		return count( $to_load ) && count($rows);
	}


	/**
	 * Returns cwd, where the accessible directories (below root) are clickable
	 *
	 * @return string cwd as clickable html
	 */
	function get_cwd_clickable( $clickableOnly = true )
	{
		if( empty($this->_ads_list_path) )
		{
			return ' -- '.T_('No directory.').' -- ';
		}

		// Get the part of the path which is not clickable:
		$r = substr( $this->_FileRoot->ads_path, 0, strrpos( substr($this->_FileRoot->ads_path, 0, -1), '/' )+1 );

		// get the part that is clickable
		$clickabledirs = explode( '/', substr( $this->_ads_list_path, strlen($r) ) );

		if( $clickableOnly )
		{
			$r = '';
		}

		$cd = '';
		foreach( $clickabledirs as $nr => $dir )
		{
			if( empty($dir) )
			{
				break;
			}
			if( $nr )
			{
				$cd .= $dir.'/';
			}
			$r .= '<a href="'.regenerate_url( 'path', 'path='.rawurlencode( $cd ) )
					.'" title="'.T_('Change to this directory').'">'.$dir.'</a>/';
		}

		return $r;
	}

}

?>