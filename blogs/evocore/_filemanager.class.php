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
 *
 * @todo Permissions!
 * @todo Performance
 * @todo favorite folders/bookmarks
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_filelist.class.php';


/**
 * Extends {@link Filelist} and provides file management functionality.
 *
 * @package admin
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
	 * User preference: sort dirs not at top
	 * @var boolean
	 */
	var $dirsnotattop = false;

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
	 * order files by what? (name/path/type/size/lastmod/perms)
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
	 * Remember the mode we're in ('upload')
	 * @var string
	 * @access protected
	 */
	var $mode = NULL;

	/**
	 * Remember the Filemanager mode we're in ('fm_upload', 'fm_cmr')
	 * @var string
	 * @access protected
	 */
	var $fm_mode = NULL;


	/**
	 * Flat mode? (all files recursive without dirs)
	 * @var boolean
	 * @access protected
	 */
	var $flatmode = NULL;


	/**
	 * Force display of Filemanager also when in file_upload mode etc.?
	 * // Is also a usersetting.
	 * @var boolean
	 * @access protected
	 */
	var $forceFM = NULL;


	/**
	 * These are variables that get considered when regenerating a URL
	 * @var array
	 * @access private
	 */
	var $_internalGlobals = array(
			'root', 'path', 'filterString', 'filterIsRegexp', 'order', 'orderasc',
			'mode', 'fm_mode', 'fm_sources', 'cmr_keepsource', 'flatmode', 'forceFM'
		);

	/**
	 * A list of selected files. Gets build on first call to
	 * {@link getFilelistSelected()}.
	 *
	 * @var Filelist
	 * @access private
	 */
	var $_selectedFiles;

	// }}}


	/**
	 * Constructor
	 *
	 * @param User the current User {@link User}}
	 * @param string the URL where the object is included (for generating links)
	 * @param string the root directory ('user', 'user_X', 'blog_X')
	 * @param string the dir of the Filemanager object (relative to root)
	 * @param string order files by what? (NULL means 'name')
	 * @param boolean order ascending or descending? NULL means ascending for 'name', descending for other
	 * @param string filter files by what?
	 * @param boolean is the filter a regular expression (default is glob pattern)
	 * @param boolean filter in subdirs (search)
	 */
	function FileManager( &$cUser, $url, $root, $path = '', $order = NULL, $orderasc = NULL,
												$filterString = NULL, $filterIsRegexp = NULL, $flatmode = NULL )
	{
		global $basepath, $baseurl, $media_subdir, $admin_subdir, $admin_url;
		global $mode;
		global $BlogCache, $UserCache;
		global $Debuglog;

		$this->User =& $cUser;
		$this->Messages =& new Log( 'error' );


		// {{{ -- get/translate root directory ----
		$root_A = explode( '_', $root );

		if( count($root_A) == 2 && $root_A[1] !== '' )
		{
			switch( $root_A[0] )
			{
				case 'blog':
					$tBlog = $BlogCache->get_by_ID( $root_A[1] );
					$this->root_dir = $tBlog->get( 'mediadir' );
					$this->root_url = $tBlog->get( 'mediaurl' );
					$this->root = 'blog_'.$root_A[1];
					break;

				case 'user':
					$tUser = & $UserCache->get_by_ID($root_A[1]);
					$this->root_dir = $tUser->getMediaDir();
					$this->root_url = $tUser->getMediaUrl();
					$this->root = 'user_'.$root_A[1];
					break;
			}
		}
		else switch( $root_A[0] )
		{
			case NULL:
			case 'user':
				$this->root_dir = $this->User->getMediaDir();
				$this->root_url = $this->User->getMediaUrl();
				$this->root = 'user';
				break;
		}

		list( $real_root_dir, $real_root_dir_exists ) = str2path( $this->root_dir );
		$Debuglog->add( 'real_root_dir: '.var_export( $real_root_dir, true ), 'filemanager' );

		if( !$this->root_dir )
		{
			$this->Messages->add( T_('No access to root directory.'), 'error' );
			$this->cwd = NULL;
		}
		elseif( !$real_root_dir_exists )
		{
			$this->Messages->add( sprintf( T_('The root directory &laquo;%s&raquo; does not exist.'), $this->root_dir ), 'error' );
			$this->cwd = NULL;
		}
		else
		{
			$this->cwd = trailing_slash( $this->root_dir.$path );
			// get real cwd

			list( $realpath, $realpath_exists ) = str2path( $this->cwd );


			if( !preg_match( '#^'.$this->root_dir.'#', $realpath ) )
			{ // cwd is not below root!
				$this->Messages->add( T_( 'You are not allowed to go outside your root directory!' ) );
				$this->cwd = $this->root_dir;
			}
			else
			{ // allowed
				if( !$realpath_exists )
				{ // does not exist
					$this->Messages->add( sprintf( T_('The directory &laquo;%s&raquo; does not exist.'), $this->cwd ) );
					$this->cwd = NULL;
				}
				else
				{
					$this->cwd = $realpath;
				}
			}
		}

		// Get the subpath relative to root
		$this->path = preg_replace( '#^'.$this->root_dir.'#', '', $this->cwd );

		// }}}


		/**
		 * base URL, used for created links
		 * @var string
		 */
		$this->url = $url;
		/**
		 * Remember mode from passed global.
		 * @var string
		 */
		$this->mode = $mode;
		/**
		 * Get FM mode from params.
		 * @var string
		 */
		$this->fm_mode = param( 'fm_mode', 'string', NULL );


		if( $this->fm_mode && $this->fm_sources = param( 'fm_sources', 'array', array() ) )
		{
			if( $this->SourceList =& new Filelist() )
			{ // TODO: should fail for non-existant sources, or sources where no read-perm

				foreach( $this->fm_sources as $lSource )
				{
					$this->SourceList->addFileByPath( urldecode($lSource) );
				}
				$this->cmr_keepsource = param( 'cmr_keepsource', 'integer', 0 );
			}
			else
			{
				$this->fm_mode = false;
			}
		}
		else
		{
			$this->SourceList = false;
			$this->fm_sources = NULL;
			$this->cmr_keepsource = NULL;
		}


		$this->filterString = !empty($filterString) ?
														$filterString :
														NULL;
		$this->filterIsRegexp = $filterIsRegexp;

		if( $this->filterIsRegexp && !is_regexp( $this->filterString ) )
		{
			$this->Messages->add( sprintf( T_('The filter &laquo;%s&raquo; is not a regular expression.'), $this->filterString ) );
			$this->filterString = '.*';
		}
		$this->order = ( in_array( $order, array( 'name', 'path', 'type', 'size', 'lastmod', 'perms' ) ) ? $order : NULL );
		$this->orderasc = ( $orderasc === NULL  ? NULL : (bool)$orderasc );


		$this->loadSettings();


		if( !empty($file) )
		{ // a file is given as parameter
			$curFile =& $Fileman->getFileByName( $file );

			if( !$curFile )
			{
				$Fileman->Messages->add( sprintf( T_('The file &laquo;%s&raquo; could not be accessed!'), $file ) );
			}
		}
		else
		{
			$curFile = false;
		}


		$Debuglog->add( 'root: '.var_export( $this->root, true ), 'filemanager' );
		$Debuglog->add( 'root_dir: '.var_export( $this->root_dir, true ), 'filemanager' );
		$Debuglog->add( 'root_url: '.var_export( $this->root_url, true ), 'filemanager' );
		$Debuglog->add( 'cwd: '.var_export( $this->cwd, true ), 'filemanager' );
		$Debuglog->add( 'path: '.var_export( $this->path, true ), 'filemanager' );

		$this->flatmode = $flatmode;

		if( !$this->forceFM )
		{ // allow override per param
			$this->forceFM = param( 'forceFM', 'integer', NULL );
		}

		$this->load();
	}


	/**
	 * Calls the parent constructor, loads and rewinds the filelist.
	 */
	function load()
	{
		parent::Filelist( $this->cwd );
		parent::load( $this->flatmode );
		parent::restart();

		#debug: pre_dump( $this->_entries );
	}


	/**
	 * Sort the Filelist entries.
	 */
	function sort()
	{
		parent::sort( $this->translate_order( $this->order ),
									$this->translate_asc( $this->orderasc, $this->translate_order( $this->order ) ),
									!$this->dirsnotattop );
	}


	/**
	 * Display button (icon) to change to the parent directory.
	 */
	function dispButtonParent()
	{
		if( $link = $this->getLinkParent() )
		{
			echo '<a title="'.T_('Go to parent folder').'" href="'.$link.'">'
						.getIcon( 'folder_parent' ).'</a>';
		}
	}


	/**
	 * Display a button to edit a file.
	 *
	 * @param File the File, defaults to current.
	 */
	function dispButtonFileEdit( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}
		if( $link = $this->getLinkFileEdit() )
		{
			echo '<a title="'.T_('Edit the file').'" href="'.$link.'">'.getIcon( 'file_edit' ).'</a>';
		}
	}


	/**
	 * Display a button to edit the permissions of the current file.
	 */
	function dispButtonFileEditPerms()
	{
		if( $link = $this->getLinkFileEditPerms() )
		{
			echo '<a title="'.T_('Edit permissions').'" href="'.$link.'">'
						.$this->curFile->getPerms( $this->permlikelsl ? 'lsl' : '' ).'</a>';
		}
	}


	/**
	 * Displays a button to enter upload mode.
	 *
	 * @param string title for the button
	 * @param string optional HTML attribs for the input button
	 */
	function dispButtonUploadPopup( $title = NULL, $attribs = '' )
	{
		if( $title === NULL )
		{
			$title = T_('Upload a file/image');
		}

		$url = $this->getCurUrl( array( 'fm_mode' => 'file_upload', 'mode' => 'upload' ) );

		echo '<input type="button" class="ActionButton"' // JS-only.. bleh..
					.' onclick="'.$this->getJsPopupCode( $url, 'fileman_upload' ).'"'
					.' value="'.format_to_output( $title, 'formvalue' ).'"'
					.' />';
	}


	/**
	 * Display a button to a mode where we choose the destination (name and/or
	 * folder) of the file or folder.
	 *
	 * @param string 'copy', 'move' or 'rename'
	 */
	function dispButtonFileCopyMoveRename( $mode, $linkTitle = NULL )
	{
		if( $mode != 'copy' && $mode != 'move' && $mode != 'rename' )
		{
			return false;
		}
		$url = $this->getCurUrl( array( 'fm_mode' => 'file_cmr',
																		'fm_sources' => false,
																		'cmr_keepsource' => (int)($mode == 'copy') ) );
		$url .= '&amp;fm_sources[]='.urlencode( $this->curFile->getPath() );

		echo '<a href="'.$url.'" target="fileman_copymoverename" onclick="'
					.$this->getJsPopupCode( $url, 'fileman_copymoverename' )
					.'" title="';

		if( $linkTitle === NULL )
		{
			switch( $mode )
			{
				case 'copy': echo T_('Copy'); break;
				case 'move': echo T_('Move'); break;
				case 'rename': echo T_('Rename'); break;
			}
		}
		else
		{
			echo $linkTitle;
		}

		echo '">'.getIcon( 'file_'.$mode ).'</a>';
	}


	/**
	 * Display a button to copy the current File.
	 */
	function dispButtonFileCopy()
	{
		$this->dispButtonFileCopyMoveRename( 'copy' );
	}


	/**
	 * Display a button to move the current File.
	 */
	function dispButtonFileMove()
	{
		$this->dispButtonFileCopyMoveRename( 'move' );
	}


	/**
	 * Display a button to rename the current File.
	 *
	 * @param string Title to display for the link (default is 'Rename')
	 */
	function dispButtonFileRename( $linkTitle = NULL )
	{
		$this->dispButtonFileCopyMoveRename( 'rename', $linkTitle );
	}


	/**
	 * Display a button to delete a File. When the action is confirmed using
	 * Javascript the GET param confirmed gets appended and set to 1.
	 *
	 * @param File|NULL the File to delete
	 */
	function dispButtonFileDelete( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}

		if( $url = $this->getLinkFileDelete( $File ) )
		{
			echo '<a title="'.T_('Delete').'" href="'.$url.'" onclick="if( confirm(\''
				.sprintf( /* TRANS: Warning this is a javascript string */ T_('Do you really want to delete &laquo;%s&raquo;?'),
				format_to_output( $File->getName(), 'formvalue' ) ).'\') )
				{
					this.href += \'&amp;confirmed=1\';
					return true;
				}
				else return false;">'.getIcon( 'file_delete' ).'</a>';
		}
	}


	/**
	 * Get current working directory.
	 *
	 * @return string the current working directory
	 */
	function getCwd()
	{
		return $this->cwd;
	}


	/**
	 * Get the path of the Filemanager, relative to root.
	 *
	 * @return string the path
	 */
	function getPath()
	{
		return $this->path;
	}


	/**
	 * Get current mode.
	 *
	 * @return string|NULL 'file_cmr', 'file_upload'
	 */
	function getMode()
	{
		return $this->fm_mode;
	}


	/**
	 * Get the current url, with all relevant GET params (root, path, filterString,
	 * filterIsRegexp, order, orderasc).
	 * Params can be overridden / disabled.
	 *
	 * @uses $_internalGlobals
	 * @param array override/disable internal globals {@link $_internalGlobals} or
	 *              add own key => value pairs as URL params
	 * @return string the resulting URL
	 */
	function getCurUrl( $override = array() )
	{
		$r = $this->url;

		$toAppend = array();
		foreach( $this->_internalGlobals as $check )
		{
			if( isset( $override[$check] ) )
			{
				$overrideValue = $override[$check];
				unset( $override[$check] );

				if( $overrideValue === false )
				{ // don't include
					continue;
				}

				$toAppend[$check] = $overrideValue;
			}
			elseif( $this->$check !== NULL )
			{
				$toAppend[$check] = $this->$check;
			}
		}
		while( list( $lKey, $lValue ) = each( $override ) )
		{ // Additional params to add, no internal globals
			$toAppend[$lKey] = $lValue;
		}

		$strAppend = '';
		while( list( $lName, $lValue ) = each( $toAppend ) )
		{
			if( is_array( $lValue ) )
			{
				$strAppend .= ( !empty($strAppend) ? '&amp;' : '' )
										.$lName.'[]='.implode( '&amp;'.$lName.'[]=', $lValue );
			}
			else
			{
				$strAppend .= ( !empty($strAppend) ? '&amp;' : '' )
										.$lName.'='.$lValue;
			}
		}

		$r = url_add_param( $r, $strAppend );

		return $r;
	}


	/**
	 * Generates hidden input fields for forms, based on {@link getCurUrl()}
	 *
	 * @return string
	 */
	function getFormHiddenInputs( $override = array() )
	{
		// get current Url, remove leading URL and '?'
		$params = preg_split( '/&amp;/',
														substr( $this->getCurUrl( $override ), strlen( $this->url )+1 ) );

		$r = '';
		foreach( $params as $lparam )
		{
			if( $pos = strpos($lparam, '=') )
			{
				$r .= '<input type="hidden" name="'.substr( $lparam, 0, $pos )
							.'" value="'.format_to_output( substr( $lparam, $pos+1 ), 'formvalue' )."\" />\n";
			}
		}

		return $r;
	}


		function getmicrotime(){
			 list($usec, $sec) = explode(" ",microtime());
			 return ((float)$usec + (float)$sec);
			 }

	/**
	 * Generate hidden input fields for the selected files.
	 *
	 * @return string
	 */
	function getFormHiddenSelectedFiles()
	{
		$r = '';

		reset( $this->_selectedFiles->_entries );
		while( list($lKey, $lFile) = each($this->_selectedFiles->_entries) )
		{
			$r .= '<input type="hidden" name="fm_selected[]" value="'
						.$this->_selectedFiles->_entries[$lKey]->getID()."\" />\n";
		}

		return $r;
	}


	/**
	 * Get an array of available roots.
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
										'id' => 'blog_'.$blog_ID,
										'name' => $Blog->get( 'shortname' ),
										'path' => $Blog->getMediaDir() );
		}

		// the user's root
		$r[] = array( 'type' => 'user',
									'id' => 'user',
									'name' => T_('My media folder'),
									'path' => $this->User->getMediaDir() );

		return $r;
	}


	/**
	 * Get the link to sort by a column. Handle current order and appends an
	 * icon to reflect the current state (ascending/descending), if the column
	 * is where we're sorting by.
	 *
	 * @param string The type (name, path, size, ..)
	 * @param string The text for the anchor.
	 * @return string
	 */
	function getLinkSort( $type, $atext )
	{
		$newAsc = $this->order == $type ?
								(1 - $this->isSortingAsc()) : // change asc/desc
								1;


		$r = '<a href="'
					.$this->getCurUrl( array( 'order' => $type,
																		'orderasc' => $newAsc ) );

		$r .= '" title="'
					.( $newAsc ?
							/* TRANS: %s gets replaced with column names 'Name', 'Type', .. */
							sprintf( T_('Sort ascending by: %s'), $atext ) :
							/* TRANS: %s gets replaced with column names 'Name', 'Type', .. */
							sprintf( T_('Sort descending by: %s'), $atext ) )
					.'">'.$atext;

		if( $this->translate_order($this->order) == $type )
		{ // add asc/desc image to represent current state
			$r .= ' '.( $this->isSortingAsc($type) ?
										getIcon( 'ascending' ) :
										getIcon( 'descending' ) );
		}

		return $r.'</a>';
	}


	/**
	 * Get the next File in the Filelist by reference.
	 *
	 * @param string can be used to query only 'file's or 'dir's.
	 * @return boolean File object on success, false on end of list
	 */
	function &getNextFile( $type = '' )
	{
		$this->curFile =& parent::getNextFile( $type );

		return $this->curFile;
	}


	/**
	 * Get the URL of a file.
	 *
	 * @param File the File object
	 */
	function getFileUrl( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}

		return $this->root_url.$this->getFileSubpath( $File );
	}


	function getFileImageSize( $param = 'widthxheight', $File = NULL )
	{
		if( !$this->getImageSizes )
		{
			return false;
		}

		if( $File === NULL )
		{
			$File =& $this->curFile;
		}

		return $File->getImageSize( $param );
	}


	/**
	 * Get the path (and name) of a {@link File} relative to the {@link $root_dir root dir}.
	 *
	 * @param File the File object
	 * @param boolean appended with name? (folders will get an ending slash)
	 * @return string path (and optionally name)
	 */
	function getFileSubpath( &$File, $withName = true )
	{
		return parent::getFileSubpath( $File, $withName, $this->root_dir );
	}


	/**
	 * Get the link to access a file or folder.
	 *
	 * @param File file object
	 * @param boolean force link to a folder (default is to change into that folder).
	 */
	function getLinkFile( &$File, $folderAsParam = false )
	{
		if( $File->isDir() && !$folderAsParam )
		{
			if( !isset( $File->cache['linkFile_1'] ) )
			{
				$File->cache['linkFile_1'] = $this->getCurUrl( array( 'path' => $this->getFileSubpath( $File ) ) );
			}

			return $File->cache['linkFile_1'];
		}
		else
		{
			if( !isset( $File->cache['linkFile_2'] ) )
			{
				$File->cache['linkFile_2'] = $this->getCurUrl().'&amp;action=default&amp;fm_selected[]='.$File->getID();
			}
			return $File->cache['linkFile_2'];
		}
	}


	function getLinkFileEditPerms()
	{
		return $this->getLinkFile( $this->curFile, true ).'&amp;action=editperm';
	}


	function getLinkFileEdit()
	{
		if( $this->curFile->isDir() )
		{
			return false;
		}
		return $this->getLinkFile( $this->curFile ).'&amp;action=edit';
	}


	/**
	 * Get link to rename a file or folder.
	 *
	 * @return string the URL
	 */
	function getLinkFileRename()
	{
		return $this->getLinkFile( NULL, true ).'&amp;action=rename';
	}


	/**
	 * Get link to delete a file or folder.
	 *
	 * @return string the URL
	 */
	function getLinkFileDelete( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}
		return $this->getLinkFile( $File, true ).'&amp;action=delete';
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
		return $this->getCurUrl( array( 'path' => $this->path.'..' ) );
	}


	/**
	 * Get the link to current root's home directory
	 */
	function getLinkHome()
	{
		return $this->getCurUrl( array( 'root' => 'user',
																		'path' => false ) );
	}


	/**
	 * Get a list of selected files, by using the 'fm_selected' param.
	 *
	 * @return array of File by reference
	 */
	function &getFilelistSelected()
	{
		if( is_null($this->_selectedFiles) )
		{
			$this->_selectedFiles = new Filelist();

			$fm_selected = param( 'fm_selected', 'array', array() );

			foreach( $fm_selected as $lSelectedID )
			{
				if( $File =& $this->getFileByID( $lSelectedID ) )
				{
					$this->_selectedFiles->addFile( $File );
				}
			}
		}

		return $this->_selectedFiles;
	}


	/**
	 * Get the directories of the supplied path as a radio button tree.
	 *
	 * @param string the root path to use
	 * @param NULL|array list of root IDs (defaults to all)
	 * @return string
	 */
	function getDirectoryTreeRadio( $rootID = NULL, $path = NULL, $rootSubpath = NULL, $rootName = NULL )
	{
		static $js_closeClickIDs; // clickopen IDs that should get closed

		if( $rootID === NULL )
		{
			$js_closeClickIDs = array();

			$_roots = $this->getRootList();

			$r = '<ul class="clicktree">';
			foreach( $_roots as $lRoot )
			{
				$subR = $this->getDirectoryTreeRadio( $lRoot['id'], $lRoot['path'], '', $lRoot['name'] );
				if( !empty( $subR['string'] ) )
				{
					$r .= '<li>'.$subR['string'].'</li>';
				}
			}

			$r .= '</ul>
						<script type="text/javascript">
						toggle_clickopen( \''
						.implode( "' );\ntoggle_clickopen( '", $js_closeClickIDs )
						."' );\n
						</script>";

			return $r;
		}

		$Nodelist = new Filelist( $path );
		$Nodelist->load();


		$rootIDAndPath = format_to_output( serialize( array( 'id' => $rootID, 'path' => $rootSubpath ) ), 'formvalue' );
		$id_path = md5( $path );

		$r['string'] = '<input type="radio"
														name="rootIDAndPath"
														value="'.$rootIDAndPath.'"
														id="radio_'.$id_path.'"
														'.( $rootID == $this->root && $rootSubpath == $this->path ? ' checked="checked"' : '' ).'
														/> ';

		$label = '<label for="radio_'.$id_path.'">'
							.'<a href="'.$this->getCurUrl( array( 'root' => $rootID, 'path' => $rootSubpath, 'forceFM' => 1 ) ).'"
								title="'.T_('Open this directory in the Filemanager').'">'
							.( is_string( $rootName ) ? $rootName : basename( $path ) )
							.'</a>'
							.'</label>';

		$r['opened'] = ( $rootID == $this->root && $rootSubpath == $this->path ) ?
										true :
										NULL;



		if( !$Nodelist->countDirs() )
		{
			$r['string'] .= $label;
			return $r;
		}
		else
		{ // Process subdirs
			$r['string'] .= '<img src="'.getIcon( 'collapse', 'url' ).'"'
											.' onclick="toggle_clickopen(\''.$id_path.'\');"'
											.' id="clickimg_'.$id_path.'" alt="+ / -" />
										'.$label.'
										<ul class="clicktree" id="clickdiv_'.$id_path.'">

										';

			while( $lFile =& $Nodelist->getNextFile( 'dir' ) )
			{
				$rSub = $this->getDirectoryTreeRadio( $rootID, $lFile->getPath(), $rootSubpath.$Nodelist->getFileSubpath( $lFile ) );

				if( $rSub['opened'] )
				{
					$r['opened'] = $rSub['opened'];
				}

				$r['string'] .= '<li>'.$rSub['string'].'</li>';
			}

			if( !$r['opened'] )
			{
				$js_closeClickIDs[] = $id_path;
			}
			$r['string'] .= '</ul>';
		}

		return $r;
	}


	/**
	 * Is the File in the list of selected files?
	 *
	 * @return boolean
	 */
	function isSelected( $File )
	{
		return $this->_selectedFiles->holdsFile( $File );
	}


	/**
	 * Creates a directory or file.
	 *
	 * @param string type; 'dir' or 'file'
	 * @param string name of the directory or file
	 * @param string path of the directory or file, defaults to cwd
	 * @param integer permissions for the new directory/file (octal format)
	 * @return false|File false on failure, File on success
	 */
	function createDirOrFile( $type, $name, $path = NULL, $chmod = NULL )
	{
		if( $type != 'dir' && $type != 'file' )
		{
			return false;
		}
		$path = $path === NULL ? $this->cwd : trailing_slash( $path );

		if( $chmod == NULL )
		{
			$chmod = $type == 'dir' ?
								$this->default_chmod_dir :
								$this->default_chmod_file;
		}

		if( empty($name) )
		{
			$this->Messages->add( $type == 'dir' ?
														T_('Cannot create a directory without name.') :
														T_('Cannot create a file without name.') );
			return false;
		}
		elseif( !isFilename($name) )
		{
			$this->Messages->add( sprintf( ($type == 'dir' ?
																			T_('&laquo;%s&raquo; is not a valid directory.') :
																			T_('&laquo;%s&raquo; is not a valid filename.') ), $name) );
			return false;
		}


		$newFile =& getFile( $name, $path );

		if( $newFile->exists() )
		{
			$this->Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $name ) );
			return false;
		}

		if( $newFile->create( $type ) )
		{
			if( $type == 'file' )
			{
				$this->Messages->add( sprintf( T_('The file &laquo;%s&raquo; has been created.'), $name ), 'note' );
			}
			else
			{
				$this->Messages->add( sprintf( T_('The directory &laquo;%s&raquo; has been created.'), $name ), 'note' );
			}

			$this->addFile( $newFile );
		}
		else
		{
			if( $type == 'file' )
			{
				$this->Messages->add( sprintf( T_('Could not create file &laquo;%s&raquo; in &laquo;%s&raquo;.'), $name, $path ) );
			}
			else
			{
				$this->Messages->add( sprintf( T_('Could not create directory &laquo;%s&raquo; in &laquo;%s&raquo;.'), $name, $path ) );
			}
		}

		return $newFile;
	}


	/**
	 * Returns cwd, where the accessible directories (below root) are clickable
	 *
	 * @return string cwd as clickable html
	 */
	function getCwdClickable( $clickableOnly = true )
	{
		if( !$this->cwd )
		{
			return ' -- '.T_('No directory.').' -- ';
		}
		// not clickable
		$r = substr( $this->root_dir, 0, strrpos( substr($this->root_dir, 0, -1), '/' )+1 );

		// get the part that is clickable
		$clickabledirs = explode( '/', substr( $this->cwd, strlen($r) ) );

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
			$r .= '<a href="'.$this->getCurUrl( array( 'path' => $cd ) )
					.'" title="'.T_('Change to this directory').'">'.$dir.'/</a>';
		}

		return $r;
	}


	function getJsPopupCode( $href = NULL, $target = 'fileman_default',
														$width = NULL, $height = NULL )
	{
		if( $href === NULL )
		{
			$href = $this->getLinkFile( $this->curFile ).'&amp;mode=browse';
		}
		if( $width === NULL )
		{
			$width = 800;
		}
		if( $height === NULL )
		{
			$height = 800;
		}

		$r = "opened = window.open('$href','$target','scrollbars=yes,"  // ."status=yes,toolbar=1,location=yes,"
					.( $width ? "width=$width," : '' )
					.( $height ? "height=$height," : '' )
					.'resizable=yes'
					."'); opened.focus();"
					."if( typeof(openedWindows) == 'undefined' )"
					."{ openedWindows = new Array(opened); }"
					."else"
					."{ openedWindows.push(opened); }"
					."return false;";

		return $r;
	}


	/**
	 * Load user's preferences from {@link $UserSettings}
	 *
	 * @return void
	 */
	function loadSettings()
	{
		global $UserSettings;

		$UserSettings->get_cond( $this->dirsnotattop,     'fm_dirsnotattop',     $this->User->ID );
		$UserSettings->get_cond( $this->permlikelsl,      'fm_permlikelsl',      $this->User->ID );
		$UserSettings->get_cond( $this->getImageSizes,    'fm_getimagesizes',    $this->User->ID );
		$UserSettings->get_cond( $this->recursivedirsize, 'fm_recursivedirsize', $this->User->ID ); // TODO: check for permission (Server load)
		$UserSettings->get_cond( $this->showhidden,       'fm_showhidden',       $this->User->ID );
		$UserSettings->get_cond( $this->forceFM,          'fm_forceFM',          $this->User->ID );
	}


	/**
	 * Check permissions
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
	 * Translates $asc parameter, if it's NULL.
	 *
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
			return ($order == 'name' || $order == 'path') ? 1 : 0;
		}
	}


	/**
	 * Translates $order parameter, if it's NULL.
	 *
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
		elseif( $this->flatmode )
		{
			return 'path';
		}
		else
		{
			return 'name';
		}
	}


	/**
	 * Get the used order.
	 *
	 * @return string
	 */
	function getOrder()
	{
		return $this->translate_order( $this->order );
	}


	/**
	 * Unlinks (deletes!) a file.
	 *
	 * @param File file object
	 * @return boolean true on success, false on failure
	 */
	function unlink( &$File, $delsubdirs = false )
	{
		// TODO: permission check

		if( !is_a( $File, 'file' ) )
		{
			return false;
		}

		$unlinked = true;

		if( $File->isDir() && $delsubdirs )
		{
			if( $unlinked = deldir_recursive( $File->getPath() ) )
			{
				$this->Messages->add( sprintf( T_('The directory &laquo;%s&raquo; and its subdirectories have been deleted.'), $File->getName() ),
															'note' );
			}
			else
			{
				$this->Messages->add( sprintf( T_('The directory &laquo;%s&raquo; could not be deleted recursively.'), $File->getName() ) );
			}
			$this->load(); // Reload!
		}
		elseif( $unlinked = $File->unlink() )
		{ // remove from list
			$this->Messages->add( sprintf( ( $File->isDir() ?
																					T_('The directory &laquo;%s&raquo; has been deleted.') :
																					T_('The file &laquo;%s&raquo; has been deleted.') ),
																			$File->getName() ),
														'note' );
			$this->removeFromList( $File );
		}
		else
		{
			$this->Messages->add( sprintf( ( $File->isDir() ?
																				T_('Could not delete the directory &laquo;%s&raquo; (not empty?).') :
																				T_('Could not delete the file &laquo;%s&raquo;.') ),
																				$File->getName() ) );
		}

		return $unlinked;
	}


	/**
	 * Copies a File object physically to another File object
	 *
	 * @param File the source file (expected to exist)
	 * @param File the target file (expected to not exist)
	 * @return boolean true on success, false on failure
	 */
	function copyFileToFile( $SourceFile, &$TargetFile )
	{
		if( !$SourceFile->exists() || $TargetFile->exists() )
		{
			return false;
		}
		else
		{
			if( $r = copy( $SourceFile->getPath(), $TargetFile->getPath() ) )
			{
				$TargetFile->refresh();
				if( $this->holdsFile( $TargetFile ) === false )
				{ // File not in filelist (expected)
					$this->addFile( $TargetFile );
				}
			}
			return $r;
		}
	}

}

/*
 * $Log$
 * Revision 1.18  2005/01/09 05:36:38  blueyed
 * fileupload
 *
 * Revision 1.17  2005/01/08 01:24:19  blueyed
 * filelist refactoring
 *
 * Revision 1.16  2005/01/06 15:45:35  blueyed
 * Fixes..
 *
 * Revision 1.15  2005/01/06 11:31:45  blueyed
 * bugfixes
 *
 * Revision 1.14  2005/01/06 10:15:45  blueyed
 * FM upload and refactoring
 *
 * Revision 1.13  2005/01/05 03:04:01  blueyed
 * refactored
 *
 * Revision 1.10  2004/11/10 22:44:26  blueyed
 * small fix
 *
 * Revision 1.9  2004/11/09 00:25:12  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.6  2004/11/03 00:58:02  blueyed
 * update
 *
 * Revision 1.5  2004/10/24 22:55:12  blueyed
 * upload, fixes, ..
 *
 * Revision 1.4  2004/10/21 00:41:20  blueyed
 * made JsPopup nice again
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
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