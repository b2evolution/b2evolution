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
 * These are the filetypes. The extension is a regular expression that must match the end of the file.
 */
$fm_filetypes = array( // {{{
	'.ai' => T_('Adobe illustrator'),
	'.bmp' => T_('Bmp image'),
	'.bz'  => T_('Bz Archive'),
	'.c' => T_('Source C '),
	'.cgi' => T_('CGI file'),
	'.conf' => T_('Config file'),
	'.cpp' => T_('Source C++'),
	'.css' => T_('Stylesheet'),
	'.exe' => T_('Executable'),
	'.gif' => T_('Gif image'),
	'.gz'  => T_('Gz Archive'),
	'.h' => T_('Header file'),
	'.hlp' => T_('Help file'),
	'.htaccess' => T_('Apache file'),
	'.htm' => T_('Hyper text'),
	'.html' => T_('Hyper text'),
	'.htt' => T_('Windows access'),
	'.inc' => T_('Include file'),
	'.inf' => T_('Config File'),
	'.ini' => T_('Setting file'),
	'.jpe?g' => T_('Jpeg Image'),
	'.js'  => T_('JavaScript'),
	'.log' => T_('Log file'),
	'.mdb' => T_('Access DB'),
	'.midi' => T_('Media file'),
	'.php' => T_('PHP script'),
	'.phtml' => T_('php file'),
	'.pl' => T_('Perl script'),
	'.png' => T_('Png image'),
	'.ppt' => T_('MS Power point'),
	'.psd' => T_('Photoshop Image'),
	'.ra' => T_('Real file'),
	'.ram' => T_('Real file'),
	'.rar' => T_('Rar Archive'),
	'.sql' => T_('SQL file'),
	'.te?xt' => T_('Text document'),
	'.tgz' => T_('Tar gz archive'),
	'.vbs' => T_('MS Vb script'),
	'.wri' => T_('Document'),
	'.xml' => T_('XML file'),
	'.zip' => T_('Zip Archive'),
); // }}}


// load file icons
require( $core_dirout.$admin_subdir.'img/fileicons/fileicons.php' );


/**
 * represents a file/dir
 */
class File
{
	/**
	 * Constructor
	 */
	function File( $name, $path = NULL )
	{
		$this->setName( $name );
		$this->_path = trailing_slash( $path === NULL ? getcwd() : $path );

		if( is_dir( $path.$name ) )
		{
			$this->_isDir = true;
			$this->_size = NULL;
		}
		else
		{
			$this->_isDir = false;
			$this->_size = @filesize( $this->_path.$this->_name );
		}

		// for files and dirs
		$this->_lastm = @filemtime( $this->_path.$this->_name );
		$this->_perms = @fileperms( $this->_path.$this->_name );
	}


	/**
	 * Is the File a directory?
	 */
	function isDir()
	{
		return $this->_isDir;
	}


	/**
	 * get the entries name
	 */
	function getName()
	{
		return $this->_name;
	}


	function getType()
	{
		if( $this->isDir() )
		{
			return T_('directory');
		}
		global $fm_filetypes;

		$filename = $this->getName();
		foreach( $fm_filetypes as $type => $desc )
		{
			if( preg_match('/'.$type.'$/i', $filename) )
			{
				return $desc;
			}
		}
		return T_('unknown');
	}


	function getSize()
	{
		return $this->_size;
	}


	function get_lastmod()
	{
		return date_i18n( locale_datefmt().' '.locale_timefmt(), $this->_lastm );
	}


	function get_perms( $type = NULL )
	{
		switch( $type )
		{
			case 'raw':
				return $this->_perms;
			case 'lsl':
				return translatePerm( $this->_perms );
			case NULL:
				if( is_windows() )
				{
					if( $this->_perms & 0x0080 )
					{
						return 'r+w';
					}
					else return 'r';
				}
			case 'octal':
				return substr( sprintf('%o', $this->_perms), -3 );
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
		return imgsize( $this->getPath( true ), $param );
	}


	/**
	 * get path
	 * @param boolean full path with name?
	 */
	function getPath( $withname = false )
	{
		return $withname ? $this->_path.$this->_name : $this->_path;
	}


	/**
	 * get the file extension
	 *
	 * @return string the extension
	 */
	function getExt()
	{
		if( preg_match('/\.([^.]+)$/', $this->_name, $match) )
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
	function getSizeNice()
	{
		if( $this->_size === NULL )
		{
			return /* TRANS: short for '<directory>' */ T_('&lt;dir&gt;');
		}
		else
		{
			return bytesreadable( $this->_size );
		}
	}


	function setName( $name )
	{
		$this->_name = $name;
	}


	/**
	 * Rename the file
	 *
	 * @param string new name (without path!)
	 * @return boolean true on success, false on failure
	 */
	function rename( $newname )
	{
		if( @rename( $this->getPath( true ), $this->getPath().$newname ) )
		{
			$this->setName( $newname );
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
		if( chmod( $this->getPath(true), $chmod) )
		{
			clearstatcache();
			// update current entry
			$this->set_perms( fileperms( $this->getPath(true) ) );
			return $this->get_perms();
		}
		else
		{
			return false;
		}
	}

}

?>