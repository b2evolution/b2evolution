<?php
/**
 * This file implements the File class. {{{
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$ }}}
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


	/**
	 * Is the File a directory?
	 */
	function is_dir()
	{
		return ($this->type == 'dir');
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
	 * Set size
	 *
	 * @param mixed either size as integer or NULL for directories, when no full dir size requested
	 * @return boolean false if param $size is invalid, true on success
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


	function get_perms( $type = NULL )
	{
		switch( $type )
		{
			case 'raw':
				return $this->perms;
			case 'lsl':
				return translatePerm( $this->perms );
			case NULL:
				if( is_windows() )
				{
					if( $this->perms & 0x0080 )
					{
						return 'r+w';
					}
					else return 'r';
				}
			case 'octal':
				return substr( sprintf('%o', $this->perms), -3 );
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
		if( @rename( $this->get_path( true ), $this->get_path().$newname ) )
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
	 * @param string chmod (three-digit-format, eg '777')
	 * @return mixed new permissions on success (octal format), false on failure
	 */
	function chmod( $chmod )
	{
		$chmod = octdec( $chmod );
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