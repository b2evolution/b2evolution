<?php
/**
 * This file implements the Filelist class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * @todo: method add() to allow specific file (outside path)
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * Includes
 */
require_once dirname(__FILE__).'/_class_file.php';
require_once dirname(__FILE__).'/_functions_files.php';


/**
 *
 */
class Filelist
{
	var $listpath = '';
	var $filter = NULL;
	var $filter_regexp = NULL;


	/* -- PRIVATE -- */

	/**
	 * the list of Files
	 * @var array
	 */
	var $entries = array();

	/**
	 * the current index of the directory items (looping)
	 * @var integer
	 * @access protected
	 */
	var $current_file_idx = -1;


	/**
	 * default order
	 * @var string
	 * @access protected
	 */
	var $order = NULL;
	/**
	 * are we sorting ascending (or descending). default is asc for 'name', desc for the rest
	 * @var mixed
	 * @access protected
	 */
	var $orderasc = NULL;


	/**
	 * User preference: recursive size of dirs?
	 *
	 * The load() method uses this.
	 *
	 * @var boolean
	 */
	var $recursivedirsize = false;


	/**
	 * to be extended by Filemanager class
	 * @var Log
	 */
	var $Messages;


	/**
	 * Constructor
	 *
	 * @param string the path for the files
	 */
	function Filelist( $path, $filter = NULL, $filter_regexp = NULL, $showhidden = false )
	{
		$this->listpath = trailing_slash( $path );
		$this->showhidden = (bool)$showhidden;
		if( empty($path) )
		{
			$this->Messages->add( 'No valid path provided.', 'fl_error' );
			$this->listpath = false;
		}
	}


	/**
	 * loads the filelist entries
	 *
	 * @param boolean get recursive size for directories?
	 */
	function load( $recursivedirsize = NULL )
	{
		if( !$this->listpath )
		{
			return false;
		}
		if( $recursivedirsize === NULL )
		{
			$recursivedirsize = $this->recursivedirsize;
		}

		$this->entries = array();

		if( $this->filter === NULL || $this->filter_regexp )
		{
			$dir = @dir( $this->listpath );
		}
		else
		{
			$oldcwd = getcwd();
			$dir = @chdir( $this->listpath );
			if( $dir )
			{
				$dir = glob( $this->listpath.$this->filter, GLOB_BRACE ); // GLOB_BRACE allows {a,b,c} to match a, b or c
			}
			chdir( $oldcwd );
		}

		if( $dir === false )
		{
			$this->Messages->add( sprintf( T_('Cannot open directory [%s]!'), $this->listpath ) );
			return false;
		}
		else
		{ // read the directory
			if( $dir === false )
			{ // glob-$dir is empty/false
				return false;
			}
			$i = -1;
			while( ( ($this->filter === NULL || $this->filter_regexp) && ($entry = $dir->read()) )
						|| ($this->filter !== NULL && !$this->filter_regexp && ( $entry = each( $dir ) ) && ( $entry = str_replace( array( '/', '\\' ), '', $entry[1] ) ) ) )
			{
				if( $entry == '.' || $entry == '..'
						|| ( !$this->showhidden && substr($entry, 0, 1) == '.' )  // hidden files (prefixed with .)
						|| ( $this->filter !== NULL && $this->filter_regexp && !preg_match( '#'.str_replace( '#', '\#', $this->filter ).'#', $entry ) ) // does not match the regexp filter
					)
				{ // don't use these
					continue;
				}

				$this->entries[ ++$i ] = new File( $entry, $this->listpath );

				if( $recursivedirsize && is_dir( $this->listpath.$entry ) )
				{
					$this->entries[ $i ]->set_size( get_dirsize_recursive( $this->listpath.$entry ) );
				}
			}

			if( $this->filter === NULL || $this->filter_regexp )
			{ // close the handle
				$dir->close();
			}
		}
	}


	/**
	 * Sorts the entries.
	 *
	 * @param string the entries key
	 * @param boolean ascending (true) or descending
	 * @param boolean sort directories at top?
	 */
	function sort( $order, $asc, $dirsattop )
	{
		if( !count($this->entries) )
		{
			return false;
		}

		if( $this->order == 'size' )
		{
			if( $this->recursivedirsize )
			{
				$sortfunction = '$r = ( $a->get_size() - $b->get_size() );';
			}
			else
			{
				$sortfunction = '$r = ($a->get_type().$b->get_type() == \'dirdir\') ?
															strcasecmp( $a->get_name(), $b->get_name() )
															: ( $a->get_size() - $b->get_size() );';
			}
		}
		elseif( $this->order == 'type' )
		{ // stupid dirty hack: copy the whole Filemanager into global array to access filetypes // TODO: optimize
			global $typetemp;
			$typetemp = $this;
			$sortfunction = 'global $typetemp; $r = strcasecmp( $typetemp->cget_file($a[\'name\'], \'type\'), $typetemp->cget_file($b[\'name\'], \'type\') );';
		}
		else
			$sortfunction = '$r = strcasecmp( $a->get_'.$order.'(), $b->get_'.$order.'() );';

		if( !$asc )
		{ // switch order
			$sortfunction .= '$r = -$r;';
		}

		if( $this->dirsattop )
		{
			$sortfunction .= 'if( $a->get_type() == \'dir\' && $b->get_type() != \'dir\' )
													$r = -1;
												elseif( $b->get_type() == \'dir\' && $a->get_type() != \'dir\' )
													$r = 1;';
		}
		$sortfunction .= 'return $r;';

		#echo $sortfunction;
		usort( $this->entries, create_function( '$a, $b', $sortfunction ) );

		// Restart the list
		$this->restart();
	}


	/**
	 * Restart the list
	 */
	function restart()
	{
		$this->current_file_idx = -1;
	}


	/**
	 * @return integer 1 for ascending sorting, 0 for descending
	 */
	function is_sortingasc( $type = '' )
	{
		if( empty($type) )
		{
			$type = $this->order;
		}

		if( $this->orderasc == '#' )
		{ // default
			return ( $type == 'name' ) ? 1 : 0;
		}
		else
		{
			return ( $this->orderasc ) ? 1 : 0;
		}
	}


	/**
	 * Is a filter active?
	 * @return boolean
	 */
	function is_filtering()
	{
		return $this->filter !== NULL;
	}


	/**
	 * return the current filter
	 *
	 * @param boolean add a note when it's a regexp?
	 * @return string the filter
	 */
	function get_filter( $note = true )
	{
		if( $this->filter === NULL )
		{
			return T_('no filter');
		}
		else
		{
			$r = $this->filter;
			if( $note && $this->filter_regexp )
			{
				$r .= ' ('.T_('regular expression').')';
			}
			return $r;
		}
	}


	/**
	 * go to next entry
	 *
	 * @param string can be used to query only 'file's or 'dir's.
	 * @return boolean File object (by reference) on success, false on end of list
	 */
	function &get_File_next( $type = '' )
	{
		$this->current_file_idx++;
		if( !count($this->entries) || $this->current_file_idx >= count( $this->entries ) )
		{
			return false;
		}

		if( $type != '' )
		{
			if( $type == 'dir' && $this->entries[ $this->current_file_idx ]->get_type() != 'dir' )
			{ // we want a dir
				return $this->get_next( 'dir' );
			}
			elseif( $this->entries[ $this->current_file_idx ]->get_type() != 'file' )
			{
				return $this->get_next( 'file' );
			}
		}
		else
		{
			return $this->entries[ $this->current_file_idx ];
		}
	}


	/**
	 * loads a specific file as current file and saves current one (can be nested).
	 *
	 * (for restoring see {@link Fileman::restorec()})
	 *
	 * @param string the filename (in cwd)
	 * @return mixed File object (by reference) on success, false on failure.
	 */
	function &get_File_by_filename( $filename )
	{
		$this->save_idx[] = $this->current_file_idx;

		if( ($this->current_file_idx = $this->findkey( $filename )) === false )
		{ // file could not be found
			$this->current_file_idx = array_pop( $this->save_idx );
			return false;
		}
		else
		{
			return $this->entries[ $this->current_file_idx ];
		}
	}


	/**
	 * restores the previous current entry (see {@link Fileman::loadc()})
	 * @return boolean true on success, false on failure (if there are no entries to restore on the stack)
	 */
	function restorec()
	{
		if( count($this->save_idx) )
		{
			$this->current_file_idx = array_pop( $this->save_idx );
			if( $this->current_file_idx != -1 )
			{
				$this->current_entry = $this->entries[ $this->current_file_idx ];
			}
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * wrapper to get properties of a specific file.
	 *
	 * @param string the file (in cwd)
	 * @param string what to get
	 * @param mixed optional parameter
	 */
	function cget_file( $file, $what, $param = '', $displayiftrue = '' )
	{
		echo 'obsolete call to cget_file!';
		return false;
		if( $this->loadc( $file ) )
		{
			$r = $this->cget( $what, $param, $displayiftrue );
		}
		else
		{
			return false;
		}

		$this->restorec();
		return $r;
	}


	/**
	 * finds an entry ('name' field) in the entries array
	 *
	 * @access protected
	 * @param string needle
	 * @return integer the key of the entries array
	 */
	function findkey( $find )
	{
		foreach( $this->entries as $key => $File )
		{
			if( $File->get_name() == $find )
			{
				return $key;
			}
		}
		return false;
	}


	/**
	 * Unlinks (deletes) a file
	 *
	 * @param File file object
	 * @return boolean true on success, false on failure
	 */
	function unlink( $File )
	{
		foreach( $this->entries as $lkey => $lentry )
		{
			if( $lentry == $File )
			{
				$unlinked = @unlink( $lentry->get_path(true) );
				if( !$unlinked )
				{
					return false;
				}

				unset( $this->entries[$lkey] );
				return true;
			}
		}
		return false;
	}


}

?>