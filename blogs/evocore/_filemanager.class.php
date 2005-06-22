<?php
/**
 * This file implements the Filemanager class.
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
 * Daniel HAHLER grants François PLANQUE the right to license
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
 * @todo Permissions!
 * @todo Performance
 * @todo favorite folders/bookmarks
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_filelist.class.php';

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
}

/**
 * Extends {@link Filelist} and provides file management functionality.
 *
 * @todo fplanque>> This object doesn't really make sense, we may get rid of it and move the functionnality back to files.php
 * @author blueyed
 * @package evocore
 */
class FileManager extends Filelist
{
	/**
	 * Root ID ('user' or 'blog_X' - X is an integer ID)
	 * @param string
	 */
	var $root;

	/**
	 * Remember the Filemanager mode we're in ('fm_upload', 'fm_cmr')
	 * @param string
	 */
	var $fm_mode;

	/**
	 * Flat mode? (all files recursive without dirs)
	 * @param boolean
	 */
	var $flatmode;

	/**
	 * Force display of Filemanager also when in file_upload mode etc.?
	 *
	 * Is also a usersetting.
	 *
	 * @param integer
	 */
	var $forceFM;

	/**
	 * root URL
	 * @param string
	 * @access protected
	 */
	var $_root_url;

	/**
	 * User preference: show permissions like "ls -l" (true) or octal (false)?
	 * @param boolean
	 * @access protected
	 */
	var $_disp_permslikelsl = true;

	/**
	 * Obtain & Display size (width, height) for images?
	 * @param boolean
	 * @access protected
	 */
	var $_use_image_sizes = false;

	/**
	 * Default perms for files
	 * @todo move to user options
	 * @access protected
	 */
	var $_default_chmod_file = 664;

	/**
	 * Default perms for dirs
	 * @todo move to user options
	 * @access protected
	 */
	var $_default_chmod_dir = 664;

	/**
	 * Evo Display mode (upload, bookmarklet, etc..)
	 * @todo get rid of this, along with $_url_params
	 * @param string
	 * @access protected
	 */
	var $_evo_mode;

	/**
	 * Item to link on...
	 * @todo get rid of this, along with $_url_params
	 * @param integer
	 * @access protected
	 */
	var $_item_ID;

	/**
	 * A list of selected files. Gets build on first call to
	 * {@link getFilelistSelected()}.
	 *
	 * @param Filelist
	 * @access protected
	 */
	var $_selected_Filelist;

	/**
	 * Display template cache
	 * @param array
	 * @access protected
	 */
	var $_result_params;

	/**
	 * These are variables that get considered when regenerating an URL
	 *
	 * @param array
	 * @access private
	 */
	var $_url_params = array(
			'root'           => 'root',
			'path'           => '_rds_list_path',
			'filterString'   => '_filter',
			'filterIsRegexp' => '_filter_is_regexp',
			'order'          => '_order',
			'orderasc'       => '_order_asc',
			'mode'           => '_evo_mode',
			'fm_mode'        => 'fm_mode',
			'fm_sources'     => 'fm_sources',
			'cmr_keepsource' => 'cmr_keepsource',
			'flatmode'       => 'flatmode',
			'forceFM'        => 'forceFM',
			'item_ID'	       => '_item_ID'	             // Used in fm_mode=link_item
		);


	/**
	 * Constructor, checks permission and initializes everything,
	 * use {@link load()} to load the files.
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
	function FileManager( & $cUser, $url, $root, $path = '', $order = NULL, $orderasc = NULL,
												$filterString = NULL, $filterIsRegexp = NULL, $flatmode = NULL )
	{
		global $basepath, $baseurl, $media_subdir, $admin_subdir, $admin_url;
		global $BlogCache, $UserCache, $Debuglog, $AdminUI, $Messages;

		// Global params to remember:
		global $mode, $item_ID;
		$this->_evo_mode = $mode;
		$this->_item_ID = $item_ID;

		$this->User = & $cUser;

		$this->_result_params = $AdminUI->getMenuTemplate('Results');

		if( empty($root) )
		{ // NO folder requested, get the first one available:
			$root_array = $this->getRootList();

			if( count($root_array) )
			{ // We found at least one media dir:
				$this->_root_type = $root_array[0]['type'];
				$this->_root_ID = $root_array[0]['IDn'];
				$this->_ads_root_path = $root_array[0]['path'];
				$this->_root_url = $root_array[0]['url'];
				$this->root = $root_array[0]['id'];
			}
			else
			{
				$Messages->add( T_('You don\'t have access to any root directory.'), 'error' );
				$this->_ads_list_path = false;
			}
		}
		else
		{ // We have requested a root folder:
			$root_parts = explode( '_', $root );

			if( $root_parts[0] == 'user' )
			{
				$this->_root_type = 'user';
				$this->_root_ID = $this->User->ID;
				$this->_ads_root_path = $this->User->getMediaDir();
				$this->_root_url = $this->User->getMediaUrl();
				$this->root = 'user';
			}
			elseif( $root_parts[0] == 'blog' && isset($root_parts[1]) )
			{
				$tBlog = $BlogCache->get_by_ID( $root_parts[1] );
				$this->_root_type = 'collection';
				$this->_root_ID = $tBlog->ID;
				$this->_ads_root_path = $tBlog->get( 'mediadir' );
				$this->_root_url = $tBlog->get( 'mediaurl' );
				$this->root = 'blog_'.$tBlog->ID;
			}

			if( ! $this->_ads_root_path )
			{
				$Messages->add( T_('You don\'t have access to the requested root directory.'), 'error' );
				$this->_ads_list_path = false;
			}
		}

		if( $this->_ads_root_path )
		{ // We have access to a/requested root dir:

			list( $_ads_real_root_path, $real_root_path_exists ) = check_canonical_path( $this->_ads_root_path );
			$Debuglog->add( 'FM: real_root_dir: '.var_export( $_ads_real_root_path, true ), 'files' );

			if( !$real_root_path_exists )
			{
				$Messages->add( sprintf( T_('The root directory &laquo;%s&raquo; does not exist.'), $this->_ads_root_path ), 'error' );
				$this->_ads_list_path = false;
			}
			else
			{ // Root exists
				// Let's get into requested list dir...
				$this->_ads_list_path = trailing_slash( $this->_ads_root_path.$path );

				// get real cwd
				list( $_ads_real_list_path, $realpath_exists ) = check_canonical_path( $this->_ads_list_path );


				if( ! preg_match( '#^'.$this->_ads_root_path.'#', $_ads_real_list_path ) )
				{ // cwd is not below root!
					$Messages->add( T_( 'You are not allowed to go outside your root directory!' ), 'error' );
					$this->_ads_list_path = $this->_ads_root_path;
				}
				else
				{ // allowed
					if( !$realpath_exists )
					{ // does not exist
						$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; does not exist.'), $this->_ads_list_path ), 'error' );
						$this->_ads_list_path = NULL;
					}
					else
					{ // Okay we can list this directory...
						$this->_ads_list_path = $_ads_real_list_path;
					}
				}
			}

			// Finish initializing:
			parent::Filelist( NULL, NULL, NULL ); // Do not override anuthing
		}


		/**
		 * base URL, used for created links
		 * @var string
		 */
		$this->url = $url;
		/**
		 * Get FM mode from params.
		 * @var string
		 */
		$this->fm_mode = param( 'fm_mode', 'string', NULL, true );


		if( $this->fm_mode && $this->fm_sources = param( 'fm_sources', 'array', array() ) )
		{
			if( $this->SourceList = & new Filelist( $this->_ads_root_path, $this->_root_type, $this->_root_ID ) )
			{ // TODO: should fail for non-existant sources, or sources where no read-perm

				foreach( $this->fm_sources as $lSourcePath )
				{
					// echo '<br>'.$lSourcePath;
					$this->SourceList->add_by_subpath( urldecode($lSourcePath) );
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


		$this->_order = ( in_array( $order, array( 'name', 'path', 'type', 'size', 'lastmod', 'perms' ) ) ? $order : NULL );
		$this->_order_asc = ( $orderasc === NULL  ? NULL : (bool)$orderasc );

		$this->setFilter( $filterString, $filterIsRegexp );


		$this->loadSettings();

		$Debuglog->add( 'FM root: '.var_export( $this->root, true ), 'files' );
		$Debuglog->add( 'FM _ads_root_path: '.var_export( $this->_ads_root_path, true ), 'files' );
		$Debuglog->add( 'FM root_url: '.var_export( $this->_root_url, true ), 'files' );
		$Debuglog->add( 'FM _ads_list_path: '.var_export( $this->_ads_list_path, true ), 'files' );
		$Debuglog->add( 'FM _rds_list_path: '.var_export( $this->_rds_list_path, true ), 'files' );

		$this->flatmode = $flatmode;

		if( !$this->forceFM )
		{ // allow override per param
			$this->forceFM = param( 'forceFM', 'integer', NULL );
		}
	}


	/**
	 * Load current directory contents.
	 *
	 * Loads and rewinds the filelist, loads meta data.
	 */
	function load()
	{
		// Load files from current working dir:
		parent::load( $this->flatmode );
		parent::restart();
		#debug: pre_dump( $this->_entries );

		// Load meta data for the filelist:
		parent::load_meta();
	}


	/**
	 * Set the filter.
	 *
	 * @param string Filter string
	 * @param boolean Is the filter a regular expression?
	 */
	function setFilter( $filterString, $filterIsRegexp = true )
	{
		global $Messages;

		$this->_filter_is_regexp = $filterIsRegexp;

		if( $this->_filter_is_regexp && !isRegexp( $filterString ) )
		{
			$Messages->add( sprintf( T_('The filter &laquo;%s&raquo; is not a regular expression.'), $filterString ), 'error' );
			$filterString = '.*';
		}

		$this->_filter = empty($filterString) ? NULL : $filterString;
	}


	/**
	 * Sort the Filelist entries.
	 */
	function sort()
	{
		parent::sort( $this->translate_order( $this->_order ),
									$this->translate_asc( $this->_order_asc, $this->translate_order( $this->_order ) ),
									!$this->_dirs_not_at_top );
	}


	/**
	 * Display button (icon) to change to the parent directory.
	 */
	function dispButtonParent()
	{
		if( empty($this->_rds_list_path) )
		{ // cannot go higher
			return '&nbsp;';	// for IE
		}

		echo '<a title="'.T_('Go to parent folder').'" href="'.$this->getCurUrl( array( 'path' => $this->_rds_list_path.'..' ) ).'">'
						.get_icon( 'folder_parent' ).'</a>';
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
		if( ! $this->curFile->is_dir() )
		{
			echo '<a title="'.T_('Edit the file').'" href="'.$this->getLinkFile( $this->curFile, 'edit' ).'">'.get_icon( 'file_edit' ).'</a>';
		}
	}


	/**
	 * Display a button to edit the permissions of the current file.
	 */
	function dispButtonFileEditPerms()
	{
		if( $this->User->check_perm( 'files', 'edit' ) )
		{ // User can edit:
			echo '<a title="'.T_('Edit permissions').'" href="'.$this->getLinkFile( $this->curFile, 'editperm' ).'">'
						.$this->curFile->get_perms( $this->_disp_permslikelsl ? 'lsl' : '' ).'</a>';
		}
		else
		{
			echo $this->curFile->get_perms( $this->_disp_permslikelsl ? 'lsl' : '' );
		}
	}


	/*
	 * Displays a button to enter upload mode.
	 *
	 * Removed because it's really overkill to instantiate a whole filemanager just to pop up a window!!!
	 */


	/**
	 * Display a button to a mode where we choose the destination (name and/or
	 * folder) of the file or folder.
	 *
	 * @param string 'copy', 'move' or 'rename'
	 */
	function dispButtonFileCopyMoveRename( $mode, $linkTitle = NULL )
	{
		if( $mode != 'copy' && $mode != 'move' )
		{
			return false;
		}
		$url = $this->getCurUrl( array( 'fm_mode' => 'file_'.$mode,
																		'fm_sources' => false,
																		'cmr_keepsource' => (int)($mode == 'copy') ) );
		$url .= '&amp;fm_sources[]='.urlencode( $this->curFile->get_rdfp_rel_path() );

		echo '<a href="'.$url
					#.'" target="fileman_copymoverename" onclick="'
					#.$this->getJsPopupCode( $url, 'fileman_copymoverename' ).' return false;'
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

		echo '">'.get_icon( 'file_'.$mode ).'</a>';
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
	 * Display a button to delete a File.
	 *
	 * @param File|NULL the File to edit
	 */
	function dispButtonFileProperties( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}

		echo '<a title="'.T_('Edit properties...').'" href="'
					.$this->getLinkFile( $File, 'edit_properties' ).'">'.get_icon( 'edit' ).'</a>';
	}


	/**
	 * Display a button to link a File to an Item.
	 *
	 * @param File|NULL the File to link
	 */
	function dispButtonFileLink( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}

		echo '<a title="'.T_('Link this file!').'" href="'
					.$this->getLinkFile( $File, 'link' ).'">'.get_icon( 'link' ).'</a>';
	}


	/**
	 * Display a button to edit File properties.
	 *
	 * @param File|NULL the File to delete
	 */
	function dispButtonFileDelete( $File = NULL )
	{
		if( $File === NULL )
		{
			$File = $this->curFile;
		}

		echo '<a title="'.T_('Delete').'" href="'.$this->getLinkFile( $File, 'delete' ).'">'.get_icon( 'file_delete' ).'</a>';

		/* No JS: we need to check DB integrity!
			.'" onclick="if( confirm(\''
			.sprintf( TS_('Do you really want to delete &laquo;%s&raquo;?'),
			format_to_output( $File->get_name(), 'formvalue' ) ).'\') )
			{
				this.href += \'&amp;confirmed=1\';
				return true;
			}
			else return false;">'
		*/
	}


	/**
	 * Generate HTML to display an image File framed.
	 *
	 * @movedTo _file_view.inc.php because this is definitely not a FM feature, it's pure image display...
	 */


	/**
	 * Get the current url, with all relevant GET params (root, path, filterString,
	 * filterIsRegexp, order, orderasc).
	 * Params can be overridden / disabled.
	 *
	 * @todo get rid of this and use regenerate_url() instead !!
	 *
	 * @uses $_url_params
	 * @param array override/disable internal globals {@link $_url_params} or
	 *              add own key => value pairs as URL params
	 * @return string the resulting URL
	 */
	function getCurUrl( $override = array() )
	{
		$r = $this->url;

		$toAppend = array();
		foreach( $this->_url_params as $check => $var )
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
			elseif( $this->$var !== NULL )
			{
				$toAppend[$check] = $this->$var;
			}
		}

		// Additional params to add, no internal globals:
		while( list( $lKey, $lValue ) = each( $override ) )
		{
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
		$params = preg_split( '/&amp;/', substr( $this->getCurUrl( $override ), strlen( $this->url )+1 ) );

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


	/**
	 * Generate hidden input fields for the selected files.
	 *
	 * @return string
	 */
	function getFormHiddenSelectedFiles()
	{
		$r = '';

		reset( $this->_selected_Filelist->_entries );
		while( list($lKey, $lFile) = each($this->_selected_Filelist->_entries) )
		{
			$r .= '<input type="hidden" name="fm_selected[]" value="'
						.$this->_selected_Filelist->_entries[$lKey]->get_md5_ID()."\" />\n";
		}

		return $r;
	}


	/**
	 * Get an array of available roots.
	 *
	 * @todo Cache this!
	 * @return array of arrays for each root: array( type [blog/user], id, name )
	 */
	function getRootList()
	{
		global $BlogCache;

		$bloglist = $BlogCache->load_user_blogs( 'browse', $this->User->ID );

		$r = array();

		// blog media dirs:
		foreach( $bloglist as $blog_ID )
		{
			$Blog = & $BlogCache->get_by_ID( $blog_ID );

			if( $blog_media_dir = $Blog->getMediaDir() )
			{ // we got a blog media dir:
 				// echo '<br>got blog media dir for blog #'.$blog_ID;
				$r[] = array( 'type' => 'collection',
											'IDn'  => $blog_ID,
											'id'   => 'blog_'.$blog_ID,
											'name' => $Blog->get( 'shortname' ),
											'path' => $blog_media_dir,
											'url'  => $Blog->get( 'mediaurl' ) );
			}
			// else echo '<br>NO blog media dir for blog #'.$blog_ID;
		}

		// the user's root
		if( $user_media_dir = $this->User->getMediaDir() )
		{ // We got a user media dir:
			$r[] = array( 'type' => 'user',
										'IDn'  => $this->User->ID,
										'id'   => 'user',
										'name' => T_('My folder'),
										'path' => $user_media_dir,
										'url'  => $this->User->getMediaUrl() );
		}
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
		$newAsc = $this->_order == $type ? (1 - $this->is_sorting_asc()) :  1;

		$r = '<a href="'.$this->getCurUrl( array( 'order' => $type,	'orderasc' => $newAsc ) ).'" title="'.T_('Change Order').'"';

		// Sorting icon:
		if( $this->translate_order($this->_order) != $type )
		{ // Not sorted on this column:
			$r .= ' class="basic_sort_link">'.$this->_result_params['basic_sort_off'];
		}
		elseif( $this->is_sorting_asc($type) )
		{ // We are sorting on this column , in ascneding order:
			$r .=	' class="basic_current">'.$this->_result_params['basic_sort_asc'];
		}
		else
		{ // Descending order:
			$r .=	' class="basic_current">'.$this->_result_params['basic_sort_desc'];
		}

		$r .= ' '.$atext;


		return $r.'</a>';
	}


	/**
	 * Get the next File in the Filelist by reference.
	 *
	 * @param string can be used to query only 'file's or 'dir's.
	 * @return boolean File object on success, false on end of list
	 */
	function & get_next( $type = '' )
	{
		$this->curFile = & parent::get_next( $type );

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

		return $File->get_url();
	}


	/**
	 * Get the image size of a file.
	 *
	 * @uses File::get_image_size()
	 * @return false|mixed Either false (@link Filemanager::_use_image_sizes} or the result
	 *                     from {@link File::get_image_size()}
	 */
	function getFileImageSize( $param = 'widthxheight', $File = NULL )
	{
		if( !$this->_use_image_sizes )
		{
			return false;
		}

		if( $File === NULL )
		{
			$File =& $this->curFile;
		}

		return $File->get_image_size( $param );
	}


	/**
	 * Get the link to access a file or folder.
	 *
	 * @param File file object
	 * @param string action to perform
	 */
	function getLinkFile( & $File, $action = 'default' )
	{
		if( $File->is_dir() && ($action == 'default') )
		{ // Link to open this directory:
			if( !isset( $File->cache['linkFile_1'] ) )
			{
				$File->cache['linkFile_1'] = $this->getCurUrl( array( 'path' => $File->get_rdfs_rel_path() ) );
			}
			return $File->cache['linkFile_1'];
		}
		else
		{ // Link to perform given $action on directory or file:
			if( !isset( $File->cache['linkFile_2'] ) )
			{
				$File->cache['linkFile_2'] = $this->getCurUrl().'&amp;fm_selected[]='.$File->get_md5_ID();
			}
			return $File->cache['linkFile_2'].'&amp;action='.$action;
		}
	}


	/**
	 * Get a list of selected files, by using the 'fm_selected' param.
	 *
	 * @return array of File by reference
	 */
	function & getFilelistSelected()
	{
		global $Debuglog;

		if( is_null($this->_selected_Filelist) )
		{
			$this->_selected_Filelist = new Filelist( false, $this->_root_type, $this->_root_ID );

			$fm_selected = param( 'fm_selected', 'array', array() );

			$Debuglog->add( count($fm_selected).' selected files/directories', 'files' );

			foreach( $fm_selected as $lSelectedID )
			{
				// echo 'selected: looking for md5: '.$lSelectedID;
				if( $File = & $this->get_by_md5_ID( $lSelectedID ) )
				{
					$this->_selected_Filelist->add( $File );
				}
			}

		}

		return $this->_selected_Filelist;
	}


	/**
	 * Get the directories of the supplied path as a radio button tree.
	 *
	 * @param NULL|array list of root IDs (defaults to all)
	 * @param string the root path to use
	 * @return string
	 */
	function getDirectoryTreeRadio( $Root = NULL , $path = NULL, $rootSubpath = NULL )
	{
		static $js_closeClickIDs; // clickopen IDs that should get closed

		if( $Root === NULL )
		{ // This is the top level call:
			$js_closeClickIDs = array();

			$_roots = $this->getRootList();

			$r = '<ul class="clicktree">';
			foreach( $_roots as $lRoot )
			{
				$subR = $this->getDirectoryTreeRadio( $lRoot, $lRoot['path'], '' );
				if( !empty( $subR['string'] ) )
				{
					$r .= '<li>'.$subR['string'].'</li>';
				}
			}

			$r .= '</ul>';

			if( !empty($js_closeClickIDs) )
			{
				$r .= "\n".'<script type="text/javascript">toggle_clickopen( \''
							.implode( "' );\ntoggle_clickopen( '", $js_closeClickIDs )
							."' );\n</script>";
			}

			return $r;
		}

		// We'll go through files in current dir:
		$Nodelist = new Filelist( trailing_slash($path), $Root['type'], $Root['IDn'] );
		$Nodelist->load();

		$root_and_path = format_to_output( serialize( array( 'root' => $Root['id'], 'path' => $rootSubpath ) ), 'formvalue' );
		$id_path = md5( $path );

		$r['string'] = '<input type="radio"
														name="root_and_path"
														value="'.$root_and_path.'"
														id="radio_'.$id_path.'"'
														.( $Root['id'] == $this->root && $rootSubpath == $this->_rds_list_path ? ' checked="checked"' : '' ).'
														/> ';

		$label = '<label for="radio_'.$id_path.'">'
							.'<a href="'.$this->getCurUrl( array( 'root' => $Root['id'], 'path' => $rootSubpath, 'forceFM' => 1 ) ).'"
								title="'.T_('Open this directory in the Filemanager').'">'
							.( empty($path) ? $Root['name'] : basename( $path ) )
							.'</a>'
							.'</label>';

		$r['opened'] = ( $Root['id'] == $this->root && $rootSubpath == $this->_rds_list_path ) ? true : NULL;



		if( !$Nodelist->count_dirs() )
		{
			$r['string'] .= $label;
			return $r;
		}
		else
		{ // Process subdirs
			$r['string'] .= '<img src="'.get_icon( 'collapse', 'url' ).'"'
											.' onclick="toggle_clickopen(\''.$id_path.'\');"'
											.' id="clickimg_'.$id_path.'" alt="+ / -" />
										'.$label.'
										<ul class="clicktree" id="clickdiv_'.$id_path.'">

										';

			while( $lFile =& $Nodelist->get_next( 'dir' ) )
			{
				$rSub = $this->getDirectoryTreeRadio( $Root, $lFile->get_full_path(), $lFile->get_rdfs_rel_path() );

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
		return $this->_selected_Filelist->contains( $File );
	}


	/**
	 * Creates a directory or file.
	 *
	 * @param string type; 'dir' or 'file'
	 * @param string name of the directory or file
	 * @return false|File false on failure, File on success
	 */
	function createDirOrFile( $type, $name )
	{
		global $FileCache, $Settings, $Messages;

		if( $type == 'dir' )
		{
			if( !$Settings->get( 'fm_enable_create_dir' ) )
			{ // Directory creation is gloablly disabled:
				$Messages->add( T_('Directory creation is disabled'), 'error' );
				return false;
			}
		}
		elseif( $type == 'file' )
		{
			if( !$Settings->get( 'fm_enable_create_file' ) )
			{ // File creation is gloablly disabled:
				$Messages->add( T_('File creation is disabled'), 'error' );
				return false;
			}
		}
		else
		{
			return false;
		}

		if( empty($name) )
		{ // No name was supplied:
			$Messages->add( ($type == 'dir' ?
														T_('Cannot create a directory without name.') :
														T_('Cannot create a file without name.') ), 'error' );
			return false;
		}
		elseif( !isFilename($name) )
		{
			$Messages->add( sprintf( ($type == 'dir' ?
																			T_('&laquo;%s&raquo; is not a valid directory.') :
																			T_('&laquo;%s&raquo; is not a valid filename.') ), $name), 'error' );
			return false;
		}

		// Try to get File object:
		$newFile = & $FileCache->get_by_root_and_path( $this->_root_type, $this->_root_ID, $this->_rds_list_path.$name );

		if( $newFile->exists() )
		{
			$Messages->add( sprintf( T_('The file &laquo;%s&raquo; already exists.'), $name ), 'error' );
			return false;
		}

		// not used... $chmod = $type == 'dir' ? $this->_default_chmod_dir : $this->_default_chmod_file;
		if( $newFile->create( $type ) )
		{
			if( $type == 'file' )
			{
				$Messages->add( sprintf( T_('The file &laquo;%s&raquo; has been created.'), $name ), 'success' );
			}
			else
			{
				$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; has been created.'), $name ), 'success' );
			}

			$this->add( $newFile );
		}
		else
		{
			if( $type == 'file' )
			{
				$Messages->add( sprintf( T_('Could not create file &laquo;%s&raquo; in &laquo;%s&raquo;.'), $name, $this->_rds_list_path ), 'error' );
			}
			else
			{
				$Messages->add( sprintf( T_('Could not create directory &laquo;%s&raquo; in &laquo;%s&raquo;.'), $name, $this->_rds_list_path ), 'error' );
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
		if( !$this->_ads_list_path )
		{
			return ' -- '.T_('No directory.').' -- ';
		}
		// not clickable
		$r = substr( $this->_ads_root_path, 0, strrpos( substr($this->_ads_root_path, 0, -1), '/' )+1 );

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
			$r .= '<a href="'.$this->getCurUrl( array( 'path' => $cd ) )
					.'" title="'.T_('Change to this directory').'">'.$dir.'</a>/';
		}

		return $r;
	}


	/**
	 * Get the Javascript code to open a file in a new window.
	 *
	 * @param string|NULL HREF of the new window (default is the {@link $curFile current File} in browse mode)
	 * @param string|NULL
	 * @param integer|NULL
	 * @param integer|NULL
	 * @return string
	 */
	function getJsPopupCode( $href = NULL, $target = 'fileman_default', $width = NULL, $height = NULL )
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

		$r = "pop_up_window( '$href', '$target', '"
					.( $width ? "width=$width," : '' )
					.( $height ? "height=$height," : '' )
					."scrollbars=yes,status=yes,resizable=yes' );";

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

		$UserSettings->get_cond( $this->_dirs_not_at_top,       'fm_dirsnotattop',     $this->User->ID );
		$UserSettings->get_cond( $this->_disp_permslikelsl,     'fm_permlikelsl',      $this->User->ID );
		$UserSettings->get_cond( $this->_use_image_sizes,       'fm_getimagesizes',    $this->User->ID );
		$UserSettings->get_cond( $this->_use_recursive_dirsize, 'fm_recursivedirsize', $this->User->ID ); // TODO: check for permission (Server load)
		$UserSettings->get_cond( $this->_show_hidden_files,     'fm_showhidden',       $this->User->ID );
		$UserSettings->get_cond( $this->forceFM,                'fm_forceFM',          $this->User->ID );
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
		elseif( $this->_order_asc !== NULL )
		{
			return $this->_order_asc;
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
		elseif( $this->_order !== NULL )
		{
			return $this->_order;
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
	function get_sort_order()
	{
		return $this->translate_order( $this->_order );
	}


	/**
	 * Unlinks (deletes!) a file.
	 *
	 * @param File file object
	 * @return boolean true on success, false on failure
	 */
	function unlink( & $File, $delsubdirs = false )
	{
		global $Messages;

		// TODO: permission check

		if( !is_a( $File, 'file' ) )
		{
			return false;
		}

		$unlinked = true;

		if( $File->is_dir() && $delsubdirs )
		{
			if( $unlinked = deldir_recursive( $File->get_full_path() ) )
			{
				$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; and its subdirectories have been deleted.'),
															$File->get_name() ), 'success' );
			}
			else
			{
				$Messages->add( sprintf( T_('The directory &laquo;%s&raquo; could not be deleted recursively.'), $File->get_name() ), 'error' );
			}
			$this->load(); // Reload!
		}
		elseif( $unlinked = $File->unlink() )
		{ // remove from list
			$Messages->add( sprintf( ( $File->is_dir() ?
																					T_('The directory &laquo;%s&raquo; has been deleted.') :
																					T_('The file &laquo;%s&raquo; has been deleted.') ),
																			$File->get_name() ), 'success' );
			$this->remove( $File );
		}
		else
		{
			$Messages->add( sprintf( ( $File->is_dir() ?
																				T_('Could not delete the directory &laquo;%s&raquo; (not empty?).') :
																				T_('Could not delete the file &laquo;%s&raquo;.') ),
																				$File->get_name() ), 'error' );
		}

		return $unlinked;
	}


	/**
	 * Moves a File object physically
	 * @param string Root type: 'user', 'group', 'collection' or 'absolute'
	 * @param integer ID of the user, the group or the collection the file belongs to...
	 * @param string Subpath for this file/folder, relative the associated root, including trailing slash (if directory)
	 * @return boolean true on success, false on failure
	 */
	function move_File( & $File, $root_type, $root_ID, $rel_path )
	{
		if( ! $File->move_to( $root_type, $root_ID, $rel_path ) )
		{ // failed
			return false;
		}

		// We may have moved in same dir, update caches:
		$this->update_caches();

		if( $this->contains( $File ) === false )
		{ // File not in filelist (expected if not same dir)
			$this->add( $File );
		}

		return true;
	}


	/**
	 * Copies a File object physically to another File object
	 *
	 * @param File the source file (expected to exist)
	 * @param File the target file (expected to not exist)
	 * @return boolean true on success, false on failure
	 */
	function copy_File( & $SourceFile, & $TargetFile )
	{
		if( ! $SourceFile->copy_to( $TargetFile ) )
		{
			return false;
		}

		if( $this->contains( $TargetFile ) === false )
		{ // File not in filelist (expected)
			$this->add( $TargetFile );
		}

		return true;
	}
}

/*
 * $Log$
 * Revision 1.48  2005/06/22 14:50:47  blueyed
 * getDirectoryTreeRadio(): fix JS error for empty clickopen list
 *
 * Revision 1.47  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.46  2005/05/24 15:26:52  fplanque
 * cleanup
 *
 * Revision 1.45  2005/05/17 19:26:07  fplanque
 * FM: copy / move debugging
 *
 * Revision 1.44  2005/05/13 16:49:17  fplanque
 * Finished handling of multiple roots in storing file data.
 * Also removed many full paths passed through URL requests.
 * No full path should ever be seen by the user (only the admins).
 *
 * Revision 1.43  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.42  2005/05/11 17:53:47  fplanque
 * started multiple roots handling in file meta data
 *
 * Revision 1.41  2005/05/11 15:58:30  fplanque
 * cleanup
 *
 * Revision 1.40  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.39  2005/05/09 16:09:42  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.38  2005/05/06 20:04:48  fplanque
 * added contribs
 * fixed filemanager settings
 *
 * Revision 1.37  2005/05/04 19:40:41  fplanque
 * cleaned up file settings a little bit
 *
 * Revision 1.36  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.35  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.34  2005/04/27 19:05:46  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.32  2005/04/19 16:23:02  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.30  2005/04/15 18:02:59  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.29  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.28  2005/04/14 18:34:04  fplanque
 * filemanager refactoring
 *
 * Revision 1.27  2005/04/13 17:48:23  fplanque
 * File manager refactoring
 * storing of file meta data through upload
 * displaying or metadate in previews
 *
 * Revision 1.26  2005/04/12 18:58:19  fplanque
 * use TS_() instead of T_() for JavaScript strings
 *
 * Revision 1.25  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.24  2005/02/08 01:09:36  blueyed
 * removed getToggled()
 *
 * Revision 1.22  2005/01/26 17:55:23  blueyed
 * catching up..
 *
 * Revision 1.21  2005/01/20 20:37:16  fplanque
 * removed static call to Form::button()
 *
 * Revision 1.20  2005/01/15 17:30:08  blueyed
 * regexp_fileman moved to $Settings
 *
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