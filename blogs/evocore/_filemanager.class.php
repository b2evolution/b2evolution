<?php
/**
 * This file implements the Filemanager class.
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 * @todo: Permissions!
 * @todo: favorite folders/bookmarks
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_filelist.class.php';


/**
 * TODO: docblock for class
 */
class FileManager extends Filelist
{
	// {{{ class variables
	/**
	 * root ('user', 'user_X' or 'blog_X' - X is id)
	 * @param string
	 */
	var $root;

	/**
	 * root directory
	 * @param string
	 */
	var $root_dir;

	/**
	 * root URL
	 * @param string
	 */
	var $root_url;

	/**
	 * current working directory
	 * @param string
	 */
	var $cwd;


	/**
	 * User preference: sort dirs at top
	 * @var boolean
	 */
	var $dirsattop = true;

	/**
	 * User preference: show hidden files?
	 * @var boolean
	 */
	var $showhidden = true;

	/**
	 * User preference: show permissions like "ls -l" (true) or octal (false)?
	 * @var boolean
	 */
	var $permlikelsl = true;

	// --- going to user options ---
	var $default_chmod_file = 664;
	var $default_chmod_dir = 664;


	/* ----- PRIVATE ----- */
	/**
	 * order files by what? (name/type/size/lastm/perms)
	 * 'name' as default.
	 * @var string
	 * @access protected
	 */
	var $order = NULL;

	/**
	 * files ordered ascending?
	 * NULL is default and means ascending for 'name', descending for the rest
	 * @var boolean
	 * @access protected
	 */
	var $orderasc = NULL;

	/**
	 * relative path
	 * @var string
	 * @access protected
	 */
	var $path = '';

	/**
	 * Remember the mode we're in ('copymove')
	 */
	var $mode = NULL;


	/**#@+
	 * "Constants"
	 */
	var $FM_EXISTS = 2;
	/**#@-*/

	// }}}


	/**
	 * Constructor
	 *
	 * @param User the current User {@link User}}
	 * @param string the URL where the object is included (for generating links)
	 * @param string the root directory ('user', 'user_X', 'blog_X')
	 * @param string the dir of the Filemanager object (relative to root)
	 * @param string filter files by what?
	 * @param boolean is the filter a regular expression (default is glob pattern)
	 * @param string order files by what? (NULL means 'name')
	 * @param boolean order ascending or descending? NULL means ascending for 'name', descending for other
	 */
	function FileManager( &$cUser, $url, $root, $path = '', $filterString = NULL, $filterIsRegexp = NULL, $order = NULL, $asc = NULL )
	{
		global $basepath, $baseurl, $media_subdir, $admin_subdir, $admin_url;
		global $mode;
		global $BlogCache, $UserCache;

		$this->User =& $cUser;
		$this->Messages =& new Log( 'error' );


		// {{{ -- get/translate root directory ----
		$this->root = $root;

		$root_A = explode( '_', $this->root );

		if( count($root_A) == 2 && $root_A[1] !== '' )
		{
			switch( $root_A[0] )
			{
				case 'blog':
					$tBlog = $BlogCache->get_by_ID( $root_A[1] );
					$this->root_dir = $tBlog->get( 'mediadir' );
					$this->root_url = $tBlog->get( 'mediaurl' );
					break;

				case 'user':
					$tUser = & $UserCache->get_by_ID($root_A[1]);
					$this->root_dir = $tUser->getMediaDir();
					$this->root_url = $tUser->getMediaUrl();
					break;
			}
		}
		else switch( $root_A[0] )
		{
			case NULL:
			case 'user':
				$this->root_dir = $this->User->getMediaDir();
				$this->root_url = $this->User->getMediaUrl();
				break;
		}

		list( $real_root_dir, $real_root_dir_exists ) = str2path( $this->root_dir );
		$this->debug( $real_root_dir, 'real_root_dir' );

		if( !$this->root_dir )
		{
			$this->Messages->add( T_('No access to root directory.'), 'error' );
			$this->cwd = NULL;
		}
		elseif( !$real_root_dir_exists )
		{
			$this->Messages->add( sprintf( T_('The root directory [%s] does not exist.'), $this->root_dir ), 'error' );
			$this->cwd = NULL;
		}
		else
		{
			$this->cwd = trailing_slash( $this->root_dir.$path );
			// get real cwd

			list( $realpath, $realpath_exists ) = str2path( $this->cwd );
			$this->debug( $realpath, 'realpath' );


			if( !preg_match( '#^'.$this->root_dir.'#', $realpath ) )
			{ // cwd is not below root!
				$this->Messages->add( T_( 'You are not allowed to go outside your root directory!' ) );
				$this->cwd = $this->root_dir;
			}
			else
			{ // allowed
				if( !$realpath_exists )
				{ // does not exist
					$this->Messages->add( sprintf( T_('The directory [%s] does not exist.'), $this->cwd ) );
					$this->cwd = NULL;
				}
				else
				{
					$this->cwd = $realpath;
				}
			}
		}


		// get the subpath relative to root
		$this->path = preg_replace( '#^'.$this->root_dir.'#', '', $this->cwd );
		// }}}


		$this->url = $url; // base URL, used for created links
		$this->mode = empty($mode) ? NULL : $mode; // from global

		$this->source = urldecode( param( 'source', 'string', '' ) );

		$this->filterString = $filterString;
		$this->filterIsRegexp = $filterIsRegexp;

		if( $this->filterIsRegexp && !is_regexp( $this->filterString ) )
		{
			$this->Messages->add( sprintf( T_('The filter [%s] is not a regular expression.'), $this->filterString ) );
			$this->filterString = '.*';
		}
		$this->order = ( in_array( $order, array( 'name', 'type', 'size', 'lastm', 'perms' ) ) ? $order : NULL );
		$this->orderasc = ( $asc === NULL  ? NULL : (bool)$asc );

		$this->loadSettings();


		// path/url for images (icons)
		$this->imgpath = $basepath.$admin_subdir.'img/fileicons/';
		$this->imgurl = $admin_url.'img/fileicons/';


		$this->debug( $this->root, 'root' );
		$this->debug( $this->root_dir, 'root_dir' );
		$this->debug( $this->root_url, 'root_url' );
		$this->debug( $this->cwd, 'cwd' );
		$this->debug( $this->path, 'path' );


		// the directory entries
		parent::Filelist( $this->cwd );
		parent::load();
		parent::restart();

		$this->debug( $this->entries, 'Filelist' );
	}


	/**
	 * Sort the Filelist entries
	 */
	function sort()
	{
		parent::sort( $this->translate_order( $this->order ),
									$this->translate_asc( $this->orderasc, $this->translate_order( $this->order ) ),
									$this->dirsattop );

	}


	/**
	 * Get the current url, with all relevant GET params (root, path, filterString,
	 * filterIsRegexp, order, orderasc).
	 * Params can be overridden or be forced to
	 *
	 * @param string override root (blog_X or user_X)
	 * @param string override path
	 * @param string override filterString
	 * @param string override filterIsRegexp
	 * @param string override order
	 * @param string override orderasc
	 * @param string override mode
	 * @return string the resulting URL
	 */
	function getCurUrl( $root = NULL, $path = NULL, $filterString = NULL,
											$filterIsRegexp = NULL, $order = NULL, $orderasc = NULL,
											$mode = NULL, $source = NULL )
	{
		$r = $this->url;

		foreach( array('root', 'path', 'filterString', 'filterIsRegexp', 'order',
										'orderasc', 'mode', 'source' ) as $check )
		{
			if( $$check === false )
			{ // don't include
				continue;
			}
			if( $$check !== NULL )
			{ // use local param
				$r = url_add_param( $r, $check.'='.$$check );
			}
			elseif( $this->$check !== NULL )
			{
				$r = url_add_param( $r, $check.'='.$this->$check );
			}
		}

		return $r;
	}


	/**
	 * Generates hidden input fields for forms, based on {@link getCurUrll()}
	 */
	function getFormHiddenInputs( $root = NULL, $path = NULL, $filterString = NULL,
															$filterIsRegexp = NULL, $order = NULL, $asc = NULL,
															$mode = NULL, $source = NULL )
	{
		// get current Url, remove leading URL and '?'
		$params = preg_split( '/&amp;/', substr( $this->getCurUrl( $root, $path, $filterString, $filterIsRegexp, $order, $asc, $mode, $source ), strlen( $this->url )+1 ) );

		$r = '';
		foreach( $params as $lparam )
		{
			if( $pos = strpos($lparam, '=') )
			{
				$r .= '<input type="hidden" name="'.substr( $lparam, 0, $pos ).'" value="'.format_to_output( substr( $lparam, $pos+1 ), 'formvalue' ).'" />';
			}
		}

		return $r;
	}


	/**
	 * Get an array of available root directories.
	 *
	 * @return array of arrays for each root: array( type [blog/user], id, name )
	 */
	function getRootList()
	{
		global $BlogCache;

		$bloglist = $BlogCache->load_user_blogs( 'browse', $this->User->ID );

		$r = array();

		foreach( $bloglist as $blog_ID )
		{
			$Blog = & $BlogCache->get_by_ID( $blog_ID );

			$r[] = array( 'type' => 'blog',
											'id' => $blog_ID,
											'name' => $Blog->get( 'shortname' ) );
		}

		$r[] = array( 'type' => 'user',
										'name' => T_('My media folder') );

		return $r;
	}


	/**
	 *
	 */
	function getLinkSort( $type, $atext )
	{
		$r = '<a href="'
					.$this->getCurUrl( NULL, NULL, NULL, NULL, $type, false );

		if( $this->order == $type )
		{ // change asc
			$r .= '&amp;asc='.(1 - $this->isSortingAsc());
		}

		$r .= '" title="'
					.( ($this->order == $type && !$this->isSortingAsc($type))
						|| ( $this->order != $type && $this->isSortingAsc($type) )
							? T_('Sort ascending by this column') : T_('Sort descending by this column')
					).'">'.$atext;

		if( $this->order == $type )
		{ // add asc/desc image
			if( $this->isSortingAsc() )
				$r .= ' '.$this->getIcon( 'ascending', 'imgtag' );
			else
				$r .= ' '.$this->getIcon( 'descending', 'imgtag' );
		}

		return $r.'</a>';
	}


	/**
	 * get the next File in the Filelist
	 *
	 * @param string can be used to query only 'file's or 'dir's.
	 * @return boolean File object on success, false on end of list
	 */
	function getNextFile( $type = '' )
	{
		$this->curFile = parent::getNextFile( $type );

		return $this->curFile;
	}


	function getFileType( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}
		elseif( is_string( $File ) )
		{{{ // special names
			switch( $File )
			{
				case 'parent':
					return T_('Go to parent directory');
				case 'home':
					return T_('home directory');
				case 'descending':
					return T_('descending');
				case 'ascending':
					return T_('ascending');
				case 'edit':
					return T_('Edit');
				case 'copymove':
					return T_('Copy / Move');
				case 'rename':
					return T_('Rename');
				case 'delete':
					return T_('Delete');
				case 'window_new':
					return T_('Open in new window');
			}
			return false;
		}}}

		return $File->getType();
	}


	/**
	 * get the URL to access a file
	 *
	 * @param File the File object
	 */
	function getFileUrl( $File )
	{
		if( method_exists( $File, 'getName' ) )
		{
			return $this->root_url.$this->path.$File->getName();
		}
		else
		{
			return false;
		}
	}


	function getLinkCurfile( $param = '' )
	{
		if( $this->curFile->isDir() && $param != 'forcefile' )
		{
			return $this->getCurUrl( NULL, $this->path.$this->curFile->getName() );
		}
		else
		{
			return $this->getCurUrl( NULL, $this->path ).'&amp;file='.urlencode( $this->curFile->getName() );
		}
	}


	function getLinkCurfile_editperm()
	{
		return $this->getLinkCurfile('forcefile').'&amp;action=editperm';
	}


	function getLinkCurfile_edit()
	{
		if( $this->curFile->isDir() )
		{
			return false;
		}
		return $this->getLinkCurfile().'&amp;action=edit';
	}


	/**
	 * Get the link to a mode where we choose the destination of the file/dir
	 *
	 * @return string the link
	 */
	function getLinkCurfile_copymove()
	{
		return $this->getCurUrl( NULL, NULL, NULL, NULL, NULL, NULL, 'copymove',
															urlencode( $this->curFile->getPath( true ) ) );
	}


	/**
	 * get link to delete current file
	 * @return string the URL
	 */
	function getLinkCurfile_rename()
	{
		return $this->getLinkCurfile('forcefile').'&amp;action=rename';
	}


	/**
	 * get link to delete current file
	 * @return string the URL
	 */
	function getLinkCurfile_delete()
	{
		return $this->getLinkCurfile('forcefile').'&amp;action=delete';
	}


	/**
	 * Get the link to the parent folder
	 *
	 * @return mixed URL or false if in root
	 */
	function getLinkParent()
	{
		if( empty($this->path) )
		{ // cannot go higher
			return false;
		}
		return $this->getCurUrl( NULL, $this->path.'..' );
	}


	/**
	 * Get the link to current root's home directory
	 */
	function getLinkHome()
	{
		return $this->getCurUrl( 'user', false );
	}


	/**
	 * get properties of a special icon
	 *
	 * @param string icon for what (special puposes or 'cfile' for current file/dir)
	 * @param string what to return for that icon (file, url, size {@link see imgsize()}})
	 * @param string additional parameter (for size)
	 */
	function getIcon( $for, $what = 'imgtag', $param = '' )
	{
		global $fm_fileicons, $fm_fileicons_special;

		if( is_a( $for, 'file' ) )
		{
			if( !$this->curFile )
			{
				$iconfile = false;
			}
			elseif( $this->curFile->isDir() )
			{
				$iconfile = $fm_fileicons_special['folder'];
			}
			else
			{
				$iconfile = $fm_fileicons_special['unknown'];
				$filename = $this->curFile->getName();
				foreach( $fm_fileicons as $ext => $imgfile )
				{
					if( preg_match( '/'.$ext.'$/i', $filename, $match ) )
					{
						$iconfile = $imgfile;
						break;
					}
				}
			}
		}
		elseif( isset( $fm_fileicons_special[$for] ) )
		{
			$iconfile = $fm_fileicons_special[$for];
		}
		else
		{
			$iconfile = false;
		}

		if( !$iconfile || !file_exists( $this->imgpath.$iconfile ) )
		{
			#return '<div class="error">[no image for '.$for.'!]</div>';
			return false;
		}

		switch( $what )
		{
			case 'file':
				$r = $iconfile;
				break;

			case 'url':
				$r = $this->imgurl.$iconfile;
				break;

			case 'size':
				$r = imgsize( $this->imgpath.$iconfile, $param );
				break;

			case 'imgtag':
				$r = '<img class="middle" src="'.$this->getIcon( $for, 'url' ).'" '.$this->getIcon( $for, 'size', 'string' )
				.' alt="';

				if( is_a( $for, 'file' ) )
				{ // extension as alt-tag for cfile-icons
					if( $for->isDir() )
					{
						$r .= /* TRANS short for directory */ T_('[dir]');
					}
					$r .= $for->getExt();
				}

				$r .= '" title="'.$this->getFileType( $for );

				$r .= '" />';
				break;

			default:
				echo 'unknown what: '.$what;
		}

		return $r;
	}


	/**
	 * do actions to a file/dir
	 *
	 * @param string filename (in cwd)
	 * @param string the action (chmod)
	 * @param string parameter for action
	 */
	function obsolete__cdo_file( $filename, $what, $param = '' )
	{
		if( $this->loadc( $filename ) )
		{
			$path = $this->cget( 'path' );
			switch( $what )
			{
				case 'send':
					if( is_dir($path) )
					{ // we cannot send directories!
						return false;
					}
					else
					{
						header('Content-type: application/octet-stream');
						//force download dialog
						header('Content-disposition: attachment; filename="' . $filename . '"');

						header('Content-transfer-encoding: binary');
						header('Content-length: ' . filesize($path));

						//send file contents
						readfile($path);
						exit;
					}
			}
		}
		else
		{
			$this->Messages->add( sprintf( T_('File [%s] not found.'), $filename ) );
			return false;
		}

		$this->restorec();
		return $r;
	}


	/**
	 * Remove a file or directory.
	 *
	 * @param string filename, defaults to current loop entry
	 * @param boolean delete subdirs of a dir?
	 * @return boolean true on success, false on failure
	 */
	function delete( $file = NULL, $delsubdirs = false )
	{
		// TODO: permission check

		if( $file == NULL )
		{ // use current entry
			if( isset($this->current_entry) )
			{
				$entry = $this->current_entry;
			}
			else
			{
				$this->Messages->add('delete: no current file!');
				return false;
			}
		}
		else
		{ // use a specific entry
			if( ($key = $this->findkey( $file )) !== false )
			{
				$entry = $this->entries[$key];
			}
			else
			{
				$this->Messages->add( sprintf(T_('File [%s] not found.'), $file) );
				return false;
			}
		}

		if( $entry['type'] == 'dir' )
		{
			if( $delsubdirs )
			{
				if( deldir_recursive( $this->cwd.'/'.$entry['name'] ) )
				{
					$this->Messages->add( sprintf( T_('Directory [%s] and subdirectories deleted.'), $entry['name'] ), 'note' );
					return true;
				}
				else
				{
					$this->Messages->add( sprintf( T_('Directory [%s] could not be deleted.'), $entry['name'] ) );
					return false;
				}
			}
			elseif( @rmdir( $this->cwd.'/'.$entry['name'] ) )
			{
				$this->Messages->add( sprintf( T_('Directory [%s] deleted.'), $entry['name'] ), 'note' );
				return true;
			}
			else
			{
				$this->Messages->add( sprintf( T_('Directory [%s] could not be deleted (probably not empty).'), $entry['name'] ) );
				return false;
			}
		}
		else
		{
			if( unlink( $this->cwd.'/'.$entry['name'] ) )
			{
				$this->Messages->add( sprintf( T_('File [%s] deleted.'), $entry['name'] ), 'note' );
				return true;
			}
			else
			{
				$this->Messages->add( sprintf( T_('File [%s] could not be deleted.'), $entry['name'] ) );
				return false;
			}
		}
	}


	/**
	 * Create a root dir, while making the suggested name an safe filename.
	 * @param string the path where to create the directory
	 * @param string suggested dirname, will be converted to a safe dirname
	 * @param integer permissions for the new directory (octal format)
	 * @return mixed full path that has been created; false on error
	 */
	function create_rootdir( $path, $suggested_name, $chmod = NULL )
	{
		global $Debuglog;

		if( $chmod === NULL )
		{
			$chmod = $this->default_chmod_dir;
		}

		$realname = safefilename( $suggested_name );
		if( $realname != $suggested_name )
		{
			$Debuglog->add( 'Realname for dir ['.$suggested_name.']: ['.$realname.']', 'fileman' );
		}
		if( $this->createDir( $realname, $path, $chmod ) )
		{
			return $path.'/'.$realname;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Meant to be called by {@link createDir()} or {@link createFile()}
	 */
	function createDirOrFile( $type, $name, $path = NULL, $chmod = NULL )
	{
		if( $type != 'dir' && $type != 'file' )
		{
			return false;
		}
		if( $path == NULL )
		{
			$path = $this->cwd;
		}

		$path = trailing_slash( $path );
		if( $chmod == NULL )
		{
			$chmod = $type == 'dir' ?
								$this->default_chmod_dir :
								$this->default_chmod_file;
		}

		if( empty($name) )
		{
			$this->Messages->add( $type == 'dir' ?
														T_('Cannot create empty directory.') :
														T_('Cannot create empty file.') );
			return false;
		}
		if( !isFilename($name) )
		{
			$this->Messages->add( sprintf( ($type == 'dir' ?
																			T_('[%s] is not a valid directory.') :
																			T_('[%s] is not a valid filename.') ), $name) );
			return false;
		}


		if( file_exists($path.$name) )
		{
			$this->Messages->add( sprintf( ($type == 'dir' ?
																			T_('The directory [%s] already exists.') :
																			T_('The file [%s] already exists.') ), $name) );
			return $this->FM_EXISTS;
		}

		if( $type == 'file' )
		{
			if( !touch( $path.$name ) )
			{
				$this->Messages->add( sprintf( T_('Could not create file [%s] in [%s].'), $name, $path ) );
				return false;
			}
			$this->Messages->add( sprintf( T_('File [%s] created.'), $name ), 'note' );
		}
		else
		{ // dir
			if( !@mkdir( $path.$name, $chmod ) )
			{
				$this->Messages->add( sprintf( T_('Could not create directory [%s] in [%s].'), $name, $path ) );
				return false;
			}
			$this->Messages->add( sprintf( T_('Directory [%s] created.'), $name ), 'note' );
		}

		if( $newFile = $this->addFile( $path.$name ) )
		{
			$newFile->chmod( $chmod );
			return true;
		}

		return false;
	}


	/**
	 * Create a directory
	 *
	 * @param string the name of the directory
	 * @param string path to create the directory in (default is cwd)
	 * @param integer permissions for the new directory (octal format)
	 * @return mixed true on success, false (or ->FM_EXISTS) on failure
	 */
	function createDir( $dirname, $path = NULL, $chmod = NULL )
	{
		return $this->createDirOrFile( 'dir', $dirname, $path, $chmod );
	}


	/**
	 * Create a file
	 *
	 * @param string filename
	 * @param string path to create the file in (default is cwd)
	 * @param integer permissions for the new file (octal format)
	 * @return mixed true on success, false (or ->FM_EXISTS) on failure
	 */
	function createFile( $filename, $path = NULL, $chmod = NULL )
	{
		return $this->createDirOrFile( 'file', $filename, $path, $chmod );
	}


	function debug( $what, $desc, $forceoutput = 0 )
	{
		global $Debuglog;

		ob_start();
		pre_dump( $what, '[Fileman] '.$desc );
		$Debuglog->add( ob_get_contents() );
		if( $forceoutput )
		{
			ob_end_flush();
		}
		else
		{
			ob_end_clean();
		}
	}


	/**
	 * Returns cwd, where the accessible directories (below root) are clickable
	 *
	 * @return string cwd as clickable html
	 */
	function getCwdClickable()
	{
		if( !$this->cwd )
		{
			return ' -- '.T_('No directory.').' -- ';
		}
		// not clickable
		$r = substr( $this->root_dir, 0, strrpos( substr($this->root_dir, 0, -1), '/' )+1 );

		// get the part that is clickable
		$clickabledirs = explode( '/', substr( $this->cwd, strlen($r) ) );

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
			$r .= '<a href="'.$this->getCurUrl( NULL, $cd )
					.'" title="'.T_('Change to this directory').'">'.$dir.'/</a>';
		}

		return $r;
	}


	function getJsPopupCode( $href = NULL, $target = 'fileman_default',
														$width = NULL, $height = NULL )
	{
		if( $href === NULL )
		{
			$href = $this->getLinkCurfile().'&amp;mode=browse';
		}
		if( $width === NULL )
		{
			$width = 800;
		}
		if( $height === NULL )
		{
			$height = 800;
		}

		$r = "opened = window.open('$href','$target','status=yes,toolbar=1,resizable=yes,scrollbars=yes,";

		if( $width )
		{
			$r .= "width=$width,";
		}
		if( $height )
		{
			$r .= "height=$height,";
		}

		return substr( $r, 0, -1 ) // cut last commata
						."'); opened.focus(); return false;";
	}


	/**
	 * get prefs from user's Settings
	 */
	function loadSettings()
	{
		global $UserSettings;

		$UserSettings->get_cond( $this->dirsattop,        'fm_dirsattop',        $this->User->ID );
		$UserSettings->get_cond( $this->permlikelsl,      'fm_permlikelsl',      $this->User->ID );
		$UserSettings->get_cond( $this->recursivedirsize, 'fm_recursivedirsize', $this->User->ID ); // TODO: check for permission (Server load)
		$UserSettings->get_cond( $this->showhidden,       'fm_showhidden',       $this->User->ID );
	}


	/**
	 * check permissions
	 *
	 * @param string for what? (upload)
	 * @return true if permission granted, false if not
	 */
	function perm( $for )
	{
		global $Debuglog;

		switch( $for )
		{
			case 'upload':
				return $this->User->check_perm( 'upload', 'any', false );

			default:  // return false if not defined
				$Debuglog->add( 'Filemanager: permission check for ['.$for.'] not defined!' );
				return false;
		}
	}


	/**
	 * translates $asc parameter, if it's NULL
	 * @param boolean sort ascending?
	 * @return integer 1 for ascending, 0 for descending
	 */
	function translate_asc( $asc, $order )
	{
		if( $asc !== NULL )
		{
			return $asc;
		}
		elseif( $this->orderasc !== NULL )
		{
			return $this->orderasc;
		}
		else
		{
			return ($order == 'name') ? 1 : 0;
		}
	}


	/**
	 * translates $order parameter, if it's NULL
	 * @param string order by?
	 * @return string order by what?
	 */
	function translate_order( $order )
	{
		if( $order !== NULL )
		{
			return $order;
		}
		elseif( $this->order !== NULL )
		{
			return $this->order;
		}
		else
		{
			return 'name';
		}
	}


}

/*
 * $Log$
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.37  2004/10/12 22:32:49  blueyed
 * started 'copymove' mode
 *
 * Revision 1.36  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>