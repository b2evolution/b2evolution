<?php
/**
 * This file implements the File class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * represents a file/dir
 */
class File
{
	var $path;
	var $name;


	/**
	 * Constructor
	 */
	function File( $name, $path = NULL )
	{
		$this->name = $name;
		$this->path = trailing_slash( $path === NULL ? getcwd() : $path );

		if( is_dir( $path.$name ) )
		{
			$this->set_type( 'dir' );
			$this->set_size( NULL );
		}
		else
		{
			$this->set_type( 'file' );
			$this->set_size( @filesize( $this->path.$name ) );
		}

		// for files and dirs
		$this->set_lastm( @filemtime( $this->path.$name ) );
		$this->set_perms( @fileperms( $this->path.$name ) );

	}


	function set_type( $type )
	{
		if( in_array( $type, array( 'file', 'dir' ) ) )
		{
			$this->type = $type;
			return true;
		}
		return false;
	}


	/**
	 * sets size
	 * @param mixed either size as integer or NULL for directories, when no full dir size requested
	 */
	function set_size( $size )
	{
		if( is_numeric( $size ) || $size === NULL )
		{
			$this->size = $size;
			return true;
		}
		$this->size = false;
		return false;
	}


	function set_lastm( $timestamp )
	{
		$this->lastm = $timestamp;
		return true;
	}


	function set_perms( $perms )
	{
		$this->perms = $perms;
		return true;
	}


	/**
	 * get the entries name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * get the entries type
	 * @return string either 'dir' or 'file'
	 */
	function get_type()
	{
		return $this->type;
	}


	function get_size()
	{
		return $this->size;
	}


	function get_lastmod()
	{
		return date_i18n( locale_datefmt().' '.locale_timefmt(), $this->lastm );
	}


	function get_perms( $type = 'octal' )
	{
		switch( $type )
		{
			case 'raw':   return $this->perms;
			case 'octal': return substr( sprintf('%o', $this->perms), -3 );
			case 'lsl':   return $this->translatePerm( $this->current_entry['perms'] );
		}

		return false;
	}

	/**
	 * get size of an image or false if not an image
	 *
	 * @param string {@link imgsize()}
	 */
	function get_imgsize( $param = 'widthxheight' )
	{
		return imgsize( $this->get_path( true ), $param );
	}


	/**
	 * get path
	 * @param boolean full path with name?
	 */
	function get_path( $withname = false )
	{
		return $withname ? $this->path.$this->name : $this->path;
	}


	/**
	 * get the file extension
	 *
	 * @return string the extension
	 */
	function get_ext()
	{
		if( preg_match('/\.([^.]+)$/', $this->name, $match) )
		{
			return $match[1];
		}
		else
		{
			return '';
		}
	}


	/**
	 * get nice size of the file
	 *
	 * @return string size as b/kb/mb/gd; or '&lt;dir&gt;'
	 */
	function get_nicesize()
	{
		if( $this->size === NULL )
		{
			return /* TRANS: short for '<directory>' */ T_('&lt;dir&gt;');
		}
		else
		{
			return bytesreadable( $this->size );
		}
	}


	/**
	 * Rename the file
	 *
	 * @param string new name (without path!)
	 * @return boolean true on success, false on failure
	 */
	function rename( $newname )
	{
		if( rename( $this->get_path( true ), $this->get_path().$newname ) )
		{
			$this->name = $newname;
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Change permissions of the file
	 *
	 * @param string chmod (octal format, eg '777')
	 * @return mixed new permissions on success (octal format), false on failure
	 */
	function chmod( $chmod )
	{
		$chmod = '0'.$chmod;
		if( chmod( $this->get_path(true), $chmod) )
		{
			clearstatcache();
			// update current entry
			$this->set_perms( fileperms( $this->get_path(true) ) );
			return $this->get_perms();
		}
		else
		{
			return false;
		}
	}
}

?>