<?php
/**
 * The Filemanager class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
class FileManager
{
	var $root;
	var $cwd;        // current working directory

	var $order = 'size';
	var $orderasc = '#'; // '#' is default and means ascending for 'name', descending for the rest


	var $showhidden = true;
	var $permlikelsl = true; // show permissions like "ls -l" or octal?


	var $default_chmod_file = 0700;
	var $default_chmod_dir = 0700;

	var $dirsattop = true;
	var $fulldirsize = false;


	/** PRIVATE **/
	var $current_idx = -1;  // represents current entry for looping


	/**
	 * Constructor
	 *
	 * @param User the current User
	 * @param string the root dir
	 */
	function FileManager( $current_User, $url, $dir = '#', $order = 'name', $asc = '#' )
	{
		global $basepath, $baseurl, $media_subdir, $core_dirout, $admin_subdir;

		$this->Messages = new Log( 'error' );

		$this->order = $order;
		$this->orderasc = $asc;
		
		$this->imgpath = $basepath.'/'.$admin_subdir.'/img/fileicons/';
		$media_dir = $basepath.'/'.$media_subdir;
		#$media_dir = 'd:\\home';
		
		if( $current_User->login == 'demouser' )
		{
			$media_dir = $basepath.'/media_test';
			$media_subdir = 'media_test';
		}
		
		$media_dir = str_replace( '\\', '/', $media_dir );

		// base URL, used for created links
		$this->url = $url;

		$this->entries = array();  // the directory entries

		// TODO: get user's/group's root
		$this->root = $media_dir;
		$this->debug( $this->root, 'root' );
		$this->root_url = $baseurl.'/'.$media_subdir;
		$this->debug( $this->root_url, 'root_url' );
		
		if( $dir == '#' || empty($dir) )
		{
			$this->cwd = $media_dir;
		}
		else
		{
			$this->cwd = $this->root.$dir;
		}
		$this->debug( $this->cwd, 'cwd' );

		// get real cwd
		$realcwd = str_replace( '\\', '/', realpath($this->cwd) );
		$this->debug( $realcwd, 'real cwd' );
		
		if( empty($realcwd) )
		{ // does not exist
			$this->cwd = $this->root;
		}
		elseif( !preg_match( '#^'.addslashes($this->root).'/#', $realcwd.'/' ) )
		{ // cwd is not below root!
			$this->Messages->add( T_( 'You are not allowed to go outside your root directory!' ) );
			$this->cwd = $this->root;
		}
		else
		{
			$this->cwd = $realcwd;
		}
		
		// get the subpath relative to root
		$this->subpath = preg_replace( '#^'.$this->root.'#', '', $this->cwd );
		$this->subpath .= '/';
		$this->debug( $this->subpath, 'subpath' );


		$this->dir = @dir( $this->cwd );
		if( empty($realcwd) || !$this->dir )
		{
			$this->Messages->add( sprintf( T_('Cannot open directory [%s]!'), $this->cwd ) );
			$this->entries = false;
		}
		else
		{ // read the directory
			$i = 0;
			while( $entry = $this->dir->read() )
			{
				if( $entry == '.' || $entry == '..'
						|| ( !$this->showhidden && substr($entry, 0, 1) == '.' )  // hidden files (prefixed with .)
					)
				{ // don't use those
					continue;
				}

				$i++;

				$this->entries[ $i ]['name'] = $entry;
				if( is_dir( $this->cwd.'/'.$entry ) )
				{
					$this->entries[ $i ]['type'] = 'dir';
					$this->entries[ $i ]['size'] = '&lt;DIR&gt;';
					if( $this->fulldirsize )
						$this->entries[ $i ]['size'] = $this->get_dirsize( $this->cwd.'/'.$entry );
					else $this->entries[ $i ]['size'] = '[dir]';
				}
				else
				{
					$this->entries[ $i ]['type'] = 'file';
					$this->entries[ $i ]['size'] = filesize( $this->cwd.'/'.$entry );
				}

				$this->entries[ $i ]['lastm'] = filemtime( $this->cwd.'/'.$entry );
				$this->entries[ $i ]['perms'] = fileperms( $this->cwd.'/'.$entry );

			}
			$this->dir->close();

		}

		// load file icons..
		require( $core_dirout.'/'.$admin_subdir.'/img/fileicons/fileicons.php' );
		
		/**
		 * These are the filetypes. The extension is a regular expression that must match the end of the file.
		 */
		$this->filetypes = array(
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
			);

		$this->sort();

		$this->restart();

		$this->debug( $this->entries, 'entries' );
	}


	/**
	 * @return integer 1 for ascending sorting, 0 for descending
	 */
	function is_sortingasc( $type = '' )
	{
		if( empty($type) )
			$type = $this->order;
		
		if( $this->orderasc == '#' )
		{ // default
			return ( $type == 'name' ) ? 1 : 0;
		}
		else
		{
			return ( $this->orderasc ) ? 1 : 0;
		}
	}


	function sortlink( $type )
	{
		$r = url_add_param( $this->url, 'cd='.$this->subpath.'&amp;order='.$type );
		
		if( $this->order == $type )
		{ // change asc
			$r .= '&amp;asc='.(1 - $this->is_sortingasc());
		}
		
		return $r;
	}


	function link_sort( $type, $atext )
	{
		$r = '<a href="'.url_add_param( $this->url, 'cd='.$this->subpath.'&amp;order='.$type );
		
		if( $this->order == $type )
		{ // change asc
			$r .= '&amp;asc='.(1 - $this->is_sortingasc());
		}
		
		$r .= '">'.$atext.'</a>';
		
		if( $this->order == $type )
		{
			if( $this->is_sortingasc() ) $r .= ' ['.T_('asc').']';
			else $r .= ' ['.T_('desc').']';
		}
		
		return $r;
	}


	/**
	 * sorts the entries.
	 *
	 * @param string the entries key
	 * @param boolean ascending (true) or descending
	 */
	function sort( $order = '#', $asc = '#' )
	{
		if( !$this->entries )
		{
			return false;
		}
		
		if( $order == '#' )
			$order = $this->order;
		
		if( $asc == '#' )
		{
			if( $this->orderasc != '#' )
			{
				$asc = $this->orderasc;
			}
			else
			{
				$asc = ($order == 'name') ? $asc = 1 : $asc = 0;
			}
		}

		if( $order == 'size' )
			$sortfunction = '$r = ($a[\'type\'].$b[\'type\'] == \'dirdir\') ?
														strcasecmp( $a[\'name\'], $b[\'name\'] )
														: ( $a[\'size\'] - $b[\'size\'] );';
		elseif( $order == 'type' )
		{ // dirty hack: copy the whole Filemanager into global array to access filetypes // TODO: optimize
			global $typetemp;
			$typetemp = $this;
			$sortfunction = 'global $typetemp; $r = strcasecmp( $typetemp->cget_file($a[\'name\'], \'type\'), $typetemp->cget_file($b[\'name\'], \'type\') );';
		}
		else
			$sortfunction = '$r = strcasecmp( $a["'.$order.'"], $b["'.$order.'"] );';

		if( !$asc )
		{ // switch order
			$sortfunction .= '$r = -$r;';
		}

		if( $this->dirsattop )
			$sortfunction .= 'if( $a[\'type\'] == \'dir\' && $b[\'type\'] != \'dir\' )
													$r = -1;
												elseif( $b[\'type\'] == \'dir\' && $a[\'type\'] != \'dir\' )
													$r = 1;';
		$sortfunction .= 'return $r;';

		usort( $this->entries, create_function( '$a, $b', $sortfunction ) );
	}


	/**
	 * go to next entry
	 *
	 * @param string can be used to query only 'file's or 'dir's.
	 * @return boolean true on success, false on end of list
	 */
	function next( $type = '' )
	{
		$this->current_idx++;
		if( !$this->entries || $this->current_idx >= count( $this->entries ) )
		{
			return false;
		}

		if( $type != '' )
		{
			if( $type == 'dir' && $this->entries[ $this->current_idx ]['type'] != 'dir' )
			{ // we want a dir
				return $this->next( 'dir' );
			}
			elseif( $this->entries[ $this->current_idx ]['type'] != 'file' )
			{
				return $this->next( 'file' );
			}
		}
		else
		{
			$this->current_entry = $this->entries[ $this->current_idx ];
			return true;
		}
	}


	/**
	 *
	 * @author zilinex at linuxmail dot com {@link www.php.net/manual/en/function.fileperms.php}
	 * @param string
	 */
	function TranslatePerm( $in_Perms )
	{
		$sP = '';

		if(($in_Perms & 0xC000) == 0xC000)		 // Socket
			$sP = 's';
		elseif(($in_Perms & 0xA000) == 0xA000) // Symbolic Link
			$sP = 'l';
		elseif(($in_Perms & 0x8000) == 0x8000) // Regular
			$sP = '&minus;';
		elseif(($in_Perms & 0x6000) == 0x6000) // Block special
			$sP = 'b';
		elseif(($in_Perms & 0x4000) == 0x4000) // Directory
			$sP = 'd';
		elseif(($in_Perms & 0x2000) == 0x2000) // Character special
			$sP = 'c';
		elseif(($in_Perms & 0x1000) == 0x1000) // FIFO pipe
			$sP = 'p';
		else												 // UNKNOWN
			$sP = 'u';

		// owner
		$sP .= (($in_Perms & 0x0100) ? 'r' : '&minus;') .
						(($in_Perms & 0x0080) ? 'w' : '&minus;') .
						(($in_Perms & 0x0040) ? (($in_Perms & 0x0800) ? 's' : 'x' ) :
																		(($in_Perms & 0x0800) ? 'S' : '&minus;'));

		// group
		$sP .= (($in_Perms & 0x0020) ? 'r' : '&minus;') .
						(($in_Perms & 0x0010) ? 'w' : '&minus;') .
						(($in_Perms & 0x0008) ? (($in_Perms & 0x0400) ? 's' : 'x' ) :
																		(($in_Perms & 0x0400) ? 'S' : '&minus;'));

		// world
		$sP .= (($in_Perms & 0x0004) ? 'r' : '&minus;') .
						(($in_Perms & 0x0002) ? 'w' : '&minus;') .
						(($in_Perms & 0x0001) ? (($in_Perms & 0x0200) ? 't' : 'x' ) :
																		(($in_Perms & 0x0200) ? 'T' : '&minus;'));
		return $sP;
	}


	/**
	 * Get an attribute of the current entry,
	 *
	 * @param string property
	 * @param string optional parameter
	 * @param string gets through sprintf where %s gets replaced with the result
	 */
	function cget( $what, $param = '', $displayiftrue = '' )
	{
		global $basepath, $admin_subdir, $admin_url;
		
		$path = isset($this->current_entry) ? $this->cwd.'/'.$this->current_entry['name'] : false;
		
		/* // detect dying loops
		global $owhat;
		if( $what == $owhat )
		{
			pre_dump( $what, 'loop' );
			return;
		}
		$owhat = $what;*/

		
		switch( $what )
		{
			case 'path':
				$r = $path;
				break;
				
			case 'url':
				$r = $this->root_url.$this->subpath.$this->current_entry['name'];
				break;
			
			case 'ext':  // the file extension, replaced in $displayiftrue
				if( empty($param) && preg_match('/\.([^.])+$/', $this->current_entry['name'], $match) )
					$r = $match[1];
				else
					$r = false;
				break;
			
			case 'perms':
				if( $param != 'octal'
						&& ($this->permlikelsl || $param == 'lsl') )
					$r = $this->translatePerm( $this->current_entry['perms'] );
				else
					$r = substr( sprintf('%o', $this->current_entry['perms']), -3 );
				break;

			case 'nicesize':
				if( ($r = $this->cget('size')) !== false )
					$r = $this->bytesreadable( $r );
				else
					$r = '';
				break;
				
			case 'imgsize':
				$r = $this->imgsize( $path, $param );
				break;

			case 'link':
				if( $param == 'parent' ) // TODO: check if allowed
					$r = url_add_param( $this->url, 'cd='.urlencode($this->subpath.'..') );
				elseif( $param == 'home' )
				{
					// TODO: provide a dropdown list if various home dirs available to the user
					$r = $this->url;
				}
				elseif( $this->current_entry['type'] == 'dir' && $param != 'forcefile' )
				{
					$r = url_add_param( $this->url, 'cd='.urlencode($this->subpath.$this->current_entry['name']) );
				}
				else
				{
					$r = url_add_param( $this->url, 'cd='.urlencode($this->subpath).'&amp;file='.urlencode($this->current_entry['name']) );
				}
				break;

			case 'link_edit':
				if( $this->current_entry['type'] == 'dir' ) $r = false;
				else $r = $this->cget('link').'&amp;action=edit';
				break;

			case 'link_copymove':
				if( $this->current_entry['type'] == 'dir' ) $r = false;
				else $r = $this->cget('link').'&amp;action=copymove';
				break;

			case 'link_rename':
				$r = $this->cget('link', 'forcefile').'&amp;action=rename';
				break;

			case 'link_delete':
				$r = $this->cget('link', 'forcefile').'&amp;action=delete';
				break;

			case 'link_editperm':
				$r = $this->cget('link', 'forcefile').'&amp;action=editperm';
				break;

			case 'lastmod':
				$r = date_i18n( locale_datefmt().' '.locale_timefmt(), $this->current_entry['lastm'] );
				break;

			case 'type':
				if( $param == 'parent' )
					$r = T_('go to parent directory');
				elseif( $param == 'home' )
					$r = T_('home directory');
				elseif( !isset($this->current_entry) )
					$r = false;
				elseif( $this->current_entry['type'] == 'dir' )
					$r = T_('directory');
				else
				{
					$found = false;
					foreach( $this->filetypes as $type => $desc )
					{
						if( preg_match('/'.$type.'$/i', $this->current_entry['name']) )
						{
							$r = $desc;
							$found = true;
							break;
						}
					}
					if( !$found ) $r = T_('unknown');
				}
				break;

			case 'iconfile':
				if( $param == 'parent' )
					$r = $this->fileicons_special['parent'];
				elseif( $param == 'home' )
					$r = $this->fileicons_special['home'];
				elseif( $param == 'newwin' )
					$r = $this->fileicons_special['newwin'];
				elseif( !isset($this->current_entry) )
					$r = false;
				elseif( $this->current_entry['type'] == 'dir' )
					$r = $this->fileicons_special['folder'];
				else foreach( $this->fileicons as $ext => $imgfile )
				{
					$r = $this->fileicons_special['unknown'];
					if( preg_match( '/'.$ext.'$/i', $this->current_entry['name'], $match ) )
					{
						$r = $imgfile;
						break;
					}
				}
				break;
				
			case 'iconurl':
				$r = $this->cget( 'iconfile', $param, $admin_url.'/img/fileicons/%s' );
				break;
				
			case 'iconsize':
				$r = $this->imgsize( $this->imgpath.$this->cget( 'iconfile', $param ), 'string' );
				break;
				
			case 'iconimg':
				$imgfile = $this->cget( 'iconfile', $param );

				if( !is_file($this->imgpath.$imgfile) )
				{
					$r = false;
				}
				else
				{
					$r = '<img src="'.$this->cget( 'iconurl', $param ).'" '.$this->cget( 'iconsize', $param ).' '.$this->cget('ext', $param, ' alt="%s"').' title="'.$this->cget('type', $param).'" />';
				}
				break;

			default:
				$r = ( isset( $this->current_entry[ $what ] ) ) ? $this->current_entry[ $what ] : false;
				break;
		}
		if( $r && !empty($displayiftrue) )
			return sprintf( $displayiftrue, $r );
		else
			return $r;
	}

	
	/**
	 * wrapper for cget() to display right away
	 * @param string property of loop file
	 * @param mixed optional parameter
	 */
	function cdisp( $what, $param = '', $displayiftrue = '' )
	{
		if( ( $r = $this->cget( $what, $param, $displayiftrue ) ) !== false )
		{
			echo $r;
			return true;
		}
		return $r;
	}
	
	
	/**
	 * wrapper for cget_file() to display right away
	 * @param string the file
	 * @param string property of loop file
	 * @param mixed optional parameter
	 */
	function cdisp_file( $file, $what, $param = '', $displayiftrue = '' )
	{
		if( ( $r = $this->cget_file( $file, $what, $param, $displayiftrue ) ) !== false )
		{
			echo $r;
		}
		return $r;
	}
	
	
	/**
	 * is the current file a directory?
	 *
	 * @param string force a specific file
	 * @return boolean true if yes, false if not
	 */
	function cisdir( $file = '' )
	{
		if( $file != '' )
		{
			if( $this->loadc( $file ) )
			{
				$isdir = ($this->current_entry['type'] == 'dir');
				$this->restorec();
				return $isdir;
			}
			else return false;
		}
		return ($this->current_entry['type'] == 'dir');
	}
	
	
	/**
	 * loads a specific file as current file as saves current one (can be nested).
	 *
	 * (for restoring see {@link Fileman::restorec()})
	 *
	 * @param string the filename (in cwd)
	 * @return boolean true on success, false on failure.
	 */
	function loadc( $file )
	{
		$this->save_idx[] = $this->current_idx;
		
		if( ($this->current_idx = $this->findkey( $file )) === false )
		{ // file could not be found
			$this->current_idx = array_pop( $this->save_idx );
			return false;
		}
		else
		{
			$this->current_entry = $this->entries[ $this->current_idx ];
			return true;
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
			$this->current_idx = array_pop( $this->save_idx );
			if( $this->current_idx != -1 )
			{
				$this->current_entry = $this->entries[ $this->current_idx ];
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
	 * do actions to a file/dir
	 *
	 * @param string filename (in cwd)
	 * @param string the action (chmod)
	 * @param string parameter for action
	 */
	function cdo_file( $filename, $what, $param = '' )
	{
		if( $this->loadc( $filename ) )
		{
			$path = $this->cget( 'path' );
			switch( $what )
			{
				case 'chmod':
					if( !file_exists($path) )
					{
						$this->Messages->add( sprintf(T_('File [%s] does not exists.'), $filename) );
					}
					else
					{
						$oldperm = $this->cget( 'perms' );
						if( chmod( $path, decoct($param) ) )
						{
							clearstatcache();
							// update current entry
							$this->entries[ $this->current_idx ]['perms'] = fileperms( $path );
							$this->current_entry['perms'] = fileperms( $path );
							$r = true;
						}
						if( $oldperm != $this->cget( 'perms' ) )
						{
							$this->Messages->add( sprintf( T_('Changed permissions for [%s] to %s.'), $filename, $this->cget( 'perms' ) ), 'note' );
						}
						else
						{
							$this->Messages->add( sprintf( T_('Permissions for [%s] not changed.'), $filename ) );
						}
					}
					break;
					
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
	 *
	 */
	function checkstatus( $path )
	{
	}

	/**
	 * restart
	 */
	function restart()
	{
		$this->current_idx = -1;
	}


	/**
	 * get an array list of a specific type
	 *
	 * @param string type ('dirs' or 'files')
	 * @param return array
	 */
	function arraylist( $type )
	{
		$r = array();
		foreach( $this->entries as $entry )
		{
			if( $type == 'files' && $entry['type'] != 'dir' )
			{
				$r[] = $entry['name'];
			}
			elseif( $type == 'dirs' && $entry['type'] == 'dir' )
			{
				$r[] = $entry['name'];
			}
		}
		return $r;
	}


	/**
	 * Remove a file or directory.
	 *
	 * @param string filename, defaults to current loop entry
	 * @param boolean delete subdirs of a dir?
	 * @return boolean true on success, false on failure
	 */
	function delete( $file = '#', $delsubdirs = false )
	{
		// TODO: permission check

		if( $file == '#' )
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
	 * create a directory
	 */
	function createdir( $dirname, $chmod = '#' )
	{
		if( $chmod == '#' )
		{
			$chmod = $this->default_chmod_dir;
		}
		if( empty($dirname) )
		{
			$this->Messages->add( T_('Cannot create empty directory.') );
			return false;
		}
		elseif( !mkdir( $this->cwd.'/'.$dirname, $chmod ) )
		{
			$this->Messages->add( sprintf( T_('Could not create directory [%s] in [%s].'), $dirname, $this->cwd ) );
			return false;
		}

		$this->Messages->add( sprintf( T_('Directory [%s] created.'), $dirname ), 'note' );
		return true;
	}


	/**
	 * create a file
	 */
	function createfile( $filename, $chmod = '#' )
	{
		$path = $this->cwd.'/'.$filename;

		if( $chmod == '#' )
		{
			$chmod = $this->default_chmod_file;
		}

		if( empty($filename) )
		{
			$this->Messages->add( T_('Cannot create empty file.') );
			return false;
		}
		elseif( file_exists($path) )
		{
			// TODO: allow overwriting
			$this->Messages->add( sprintf(T_('File [%s] already exists.'), $filename) );
			return false;
		}
		elseif( !touch( $path ) )
		{
			$this->chmod( $filename, $chmod );
			$this->Messages->add( sprintf( T_('Could not create file [%s] in [%s].'), $filename, $this->cwd ) );
			return false;
		}
		else
		{
			$this->Messages->add( sprintf( T_('File [%s] created.'), $filename ), 'note' );
			return true;
		}
	}


	/**
	 * Reloads the page where Filemanager was called for, useful when a file or dir has been created.
	 */
	function reloadpage()
	{
		header( 'Location: '.url_add_param( $this->url, 'cd='.$this->subpath ) );
		exit;
	}


	/**
	 * finds an entry ('name' field) in the entries array
	 *
	 * @param string needle
	 * @return integer the key of the entries array
	 */
	function findkey( $find )
	{
		foreach( $this->entries as $key => $arr )
		{
			if( $arr['name'] == $find )
			{
				return $key;
			}
		}
		return false;
	}


	/**
	 * create crossplatform-safe filename
	 */
	function safefilename( $path )
	{
		// TODO: create safe path
		return $path;
	}

	/**
	 * converts bytes to readable bytes/kb/mb/gb
	 *
	 * @param integer bytes
	 * @return string bytes made readable
	 */
	function bytesreadable( $bytes )
	{
		$type = array ('b', 'kb', 'mb', 'gb');

		for ($i = 0; $bytes > 1024; $i++)
			$bytes /= 1024;

		return str_replace(',', '.', round($bytes, 2)) . $type[$i];
	}


	function debug( $what, $desc, $forceoutput = 0 )
	{
		global $Debuglog;
		
		ob_start();
		pre_dump( $what, '[Fileman] '.$desc );
		$Debuglog->add( ob_get_contents() );
		if( $forceoutput )
			ob_end_flush();
		else
			ob_end_clean();
	}


	/**
	 * deletes a dir recursive, wiping out all subdirectories!!
	 *
	 * @param string the dir
	 */
	function deldir_recursive( $dir )
	{
		$current_dir = opendir( $dir );
		while( $entryname = readdir($current_dir) )
		{
			if( is_dir( "$dir/$entryname" ) && ( $entryname != '.' && $entryname != '..') )
			{
				deldir( "$dir/$entryname" );
			}
			elseif( $entryname != '.' && $entryname != '..' )
			{
				unlink( "$dir/$entryname" );
			}
		}
		closedir( $current_dir );
		return rmdir( $dir );
	}

	
	/**
	 * get size of directory, including anything in there.
	 *
	 * @param string the dir's full path
	 */
	function get_dirsize( $path )
	{
		$dir = opendir( $path );
		$total = 0;
		while( $cur = readdir($dir) ) if( !in_array( $cur, array('.', '..')) )
		{
			if( is_dir($path.'/'.$cur) )
			{
				$total += $this->GetDirSize($path.'/'.$cur);
			}
			else
			{
				$total += filesize($path.'/'.$cur);
			}
		}
	return $total;
	}
		

	/**
	 * get the size of an image file
	 *
	 * @param string absolute file path
	 * @param string 'width', 'height', 'widthheight' (array), 'type', 'string' (as for img tags), else: 'widthxheight'
	 */
	function imgsize( $path, $param )
	{
		if( !preg_match( '/\.(jpe?g|gif|png|swf)$/', $path) )
		{
			return false;
		}
		else
		{
			if( !($size = @getimagesize( $path )) )
				return false;
			elseif( $param == 'width' )
				return $size[0];
			elseif( $param == 'height' )
				return $size[1];
			elseif( $param == 'widthheight' )
				return array( $size[0], $size[1] );
			elseif( $param == 'type' )
			{
				switch( $size[1] )
				{
					case 1: return 'gif';
					case 2: return 'jpg';
					case 3: return 'png';
					case 4: return 'swf';
					default: return 'unknown';
				}
			}
			elseif( $param == 'string' )
				return $size[3];
			else
				return $size[0].'x'.$size[1];
		}
	}

}

?>
