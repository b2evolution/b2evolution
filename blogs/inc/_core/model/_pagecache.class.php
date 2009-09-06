<?php
/**
 * This file implements the PageCache class, which caches HTML pages genereated by the app.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * @version $Id$ }}}
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Page Cache.
 *
 * @package evocore
 */
class PageCache
{
  /**
	 * How old can a cached object get before we consider it outdated
	 */
	var $max_age_seconds = 300;  // 5 minutes for now

  /**
	 * After how many bytes should we output sth live while collecting cache content:
	 */
	var $output_chunk_size = 2000;

	/**
	 * By default we consider caching not to be enabled
	 */
	var $is_enabled = false;

	/**
	 *
	 */
	var $ads_collcache_path;

	/**
	 * Filename of cache for current page
	 */
	var $cache_filepath;
	/**
	 * Progressively caching the content of the current page:
	 */
	var $cached_page_content = '';
	/**
	 * Are we currently recording cache contents for this page?
	 */
	var $is_collecting = false;


	/**
	 * Constructor
	 *
	 * @param Blog to use, can be NULL
	 */
	function PageCache( $Blog = NULL )
	{
		global $Debuglog;
		global $Settings;
		global $cache_path;

		if( is_null($Blog) )
		{	// Cache for "other" "genereic" "special" pages:
			$this->ads_collcache_path = $cache_path.'general/';

			if( ! $Settings->get('general_cache_enabled') )
			{	// We do NOT want caching for this collection
				$Debuglog->add( 'General cache not enabled.', 'cache' );
			}
			else
			{
				$this->is_enabled = true;
			}
		}
		else
		{	// Cache for a specific Blog/Collection:
			// We need to set this even if cache is not enabled (yet) bc it's used for creating:
			$this->ads_collcache_path = $cache_path.'c'.$Blog->ID.'/';

			if( ! $Blog->get_setting('cache_enabled') )
			{	// We do NOT want caching for this collection
				$Debuglog->add( 'Cache not enabled for this blog.', 'cache' );
			}
			else
			{
				$this->is_enabled = true;
			}
		}
	}


	/**
	 * Get path to file for current URL
	 *
	 * @todo fp> We may need to add some keys like the locale or the charset, I'm not sure.
	 */
	function get_af_filecache_path()
	{
		global $Debuglog;
		global $ReqHost, $ReqURI;

		// We want the cache for the current URL
		if( empty( $this->cache_filepath ) )
		{
			$ReqAbsUrl = $ReqHost.$ReqURI;
			// echo $ReqAbsUrl;
 			$Debuglog->add( 'URL being cached: '.$ReqAbsUrl, 'cache' );

 			$this->cache_filepath = $this->gen_filecache_path( $ReqAbsUrl );

 			$Debuglog->add( 'Cache file: '.$this->cache_filepath, 'cache' );
		}

 		return $this->cache_filepath;
	}


	/**
	 * Generate path for caching $url.
	 * @param string URL
	 * @return string
	 */
	function gen_filecache_path( $url )
	{
		$url_hash = md5($url);	// fp> is this teh fastest way to hash this into something not too obvious to guess?
		// echo $url_hash;

		return $this->ads_collcache_path.$url_hash.'.page';
	}


	/**
	 * Invalidate a particular page from the cache
	 *
	 * @param URL of the page to be invalidated
	 */
	function invalidate( $url )
	{
		global $Debuglog;

		// echo 'Invalidating:'.$url;
		$Debuglog->add( 'Invalidating:'.$url, 'cache' );

		// What would be the cache file for the current URL?
		$af_cache_file = $this->gen_filecache_path( $url );

		@unlink( $af_cache_file );
	}


	/**
	 * @return boolean true if cache has been successfully created
	 */
	function cache_create( $clear = true )
	{
		// Create by using the filemanager's default chmod. TODO> we may not want to make these publicly readable
		if( ! mkdir_r( $this->ads_collcache_path, NULL ) )
		{
			return false;
		}

		if( $clear )
		{	// Clear contents of folder, if any:
			cleardir_r( $this->ads_collcache_path );
		}

		return true;
	}


	/**
	 * Delete all cache files
	 */
	function cache_delete()
	{
		rmdir_r( $this->ads_collcache_path );
	}


	/**
	 * Check if cache contents are available, otherwise start collecting output to be cached
	 *
	 * @return true if we found and have echoed content from the cache
	 */
	function check()
	{
		global $Debuglog;
		global $disp;

		global $Messages;

		if( ! $this->is_enabled )
		{	// We do NOT want caching for this page
			$Debuglog->add( 'Cache not enabled. No lookup nor caching performed.', 'cache' );
			return false;
		}

		if( $disp == '404' )
		{	// We do NOT want caching for 404 pages (illimited possibilities!)
			$Debuglog->add( 'Never cache 404s!', 'cache' );
			return false;
		}

		if( is_logged_in() )
		{	// We do NOT want caching when a user is logged in (private data)
			$Debuglog->add( 'Never cache pages for/from logged in members!', 'cache' );
			return false;
		}

		if( $Messages->count('all') )
		{	// There are some messages do be displayed. That means the user has done some action.
			// We do want to display those messages.
			// There may also be more... like a "comment pending review" etc...
			// DO NOT CACHE and do not present a cached page.
			$Debuglog->add( 'Not caching because we have messages!', 'cache' );
			return false;
		}

		if( ! $this->cache_create( false ) )
		{	// Make sure that blog cache directory exists
			$Debuglog->add( 'Could not create cache directory: '.$this->ads_collcache_path, 'cache' );
			return false;
		}
		
		
		// TODO: fp> If the user has submitted a comment, we might actually want to invalidate the cache...


		if( $this->retrieve() )
		{ // We could retrieve:
			return true;
		}


		$this->is_collecting = true;

		$Debuglog->add( 'Collecting started', 'cache' );

		ob_start( array( & $this, 'output_handler'), $this->output_chunk_size );

		return false;
	}


	/**
	 * Retrieve and output cache for current URL.
	 *
	 * @return boolean true if we could retrieve
	 */
	function retrieve()
	{
		global $Debuglog;
		global $ReqHost, $ReqURI;
		global $servertimenow;

		// What would be the cache file for the current URL?
		$af_cache_file = $this->get_af_filecache_path();


		/*
		// fstat() is interesting because it gives the last access time... use that for purging...
		* Tblue> Note: Many server admins mount partitions with the "noatime"
		*              option, which disables atime updates and thus speeds
		*              up disk access - that means the atime is not reliable,
		*              better use the mtime (modification time).
		if( $fh = @fopen( $af_cache_file, 'r', false ) )
		{
			$fstat = fstat( $fh );
			pre_dump( $fstat );
			fclose( $fh );
		}
		*/

		$lines = @file( $af_cache_file, false );
		// fp> note we are using empty() so that we detect both the case where there is no file and the case wher ethe file
		// might have ended up empty because PHP crashed while writing to it or sth like that...
		if( ! empty($lines) )
		{	// We have data in the cache!
			$Debuglog->add( 'Retrieving from cache!', 'cache' );

			// Check that the format of the file if OK.
			$sep = trim($lines[2]);
			unset($lines[2]);
			if( $sep != '' )
			{
				$Debuglog->add( 'Cached file format not recognized, aborting retrieve.', 'cache' );
				return false;
			}

			// Retrieved cached URL:
			$retrieved_url = trim($lines[0]);
			unset($lines[0]);
			if( $retrieved_url != $ReqHost.$ReqURI )
			{
				$Debuglog->add( 'Cached file URL ['.$retrieved_url.'] does not match current URL, aborting retrieve.', 'cache' );
				return false;
			}

			// timestamp of cache generation:
			$retrieved_ts = trim($lines[1]);
			unset($lines[1]);
			$cache_age = $servertimenow-$retrieved_ts;
			$Debuglog->add( 'Cache age: '.floor($cache_age/60).' min '.($cache_age % 60).' sec', 'cache' );
			if( $cache_age > $this->max_age_seconds )
			{	// Cache has expired
				return false;
			}

			// Go through headers
			$i = 2;
			while( $headerline = trim($lines[++$i]) )
			{
				header( $headerline );
				unset($lines[$i]);
			}
			unset($lines[$i]);

			// SEND CONTENT!
			echo implode('',$lines);

			return true;
		}
	}


  /**
	 * This is called every x bytes to provide real time output
	 */
	function output_handler( $buffer )
	{
		$this->cached_page_content .= $buffer;
		return $buffer;
	}


	/**
	 * We are going to output personal data and we want to abort collecting the data for the cache.
	 */
	function abort_collect()
	{
		global $Debuglog;

		if( ! $this->is_collecting )
		{	// We are not collecting anyway
			return;
		}

 		$Debuglog->add( 'Aborting cache data collection...', 'cache' );

		ob_end_flush();

		// We are no longer collecting...
		$this->is_collecting = false;
	}


	/**
	 * End collecting output to be cached
	 */
	function end_collect()
	{
		global $Debuglog;

		if( ! $this->is_collecting )
		{	// We are not collecting
			return;
		}

		ob_end_flush();

		// echo ' *** cache end *** ';
		// echo $this->cached_page_content;

		// What would be the cache file for the current URL?
		$af_cache_file = $this->get_af_filecache_path();

		// fp> 'x' mode should either give an exclusive write lock or fail
		// fp> TODO: this here should be ok, but it would be even better with locking the file when we start collecting cache
		if( ! $fh = @fopen( $af_cache_file.'.tmp', 'x', false ) )
		{
			$Debuglog->add( 'Could not open cache file!', 'cache' );
		}
		else
		{
			// Put the URL of the page we are caching into the cache. You can never be to paranoid!
			// People can change their domain names, folder structures, etc... AND you cannot trust the hash to give a
			// different file name in 100.00% of the cases! Serving a page for a different URL would be REEEEEALLLY BAAAAAAD!
			global $ReqHost, $ReqURI;
			$file_head = $ReqHost.$ReqURI."\n";

			// Put the time of the page generation into the file (btw this is the time of when we started this script)
			global $servertimenow;
			$file_head .= $servertimenow."\n";

 			$file_head .= "\n";

			// We need to write the content type!
			global $content_type_header;
			if( !empty($content_type_header) )
			{
				$file_head .= $content_type_header."\n";
			}

			$file_head .= "\n";

			fwrite( $fh, $file_head.$this->cached_page_content );
			fclose( $fh );

			// Now atomically replace old cache with new cache (at least on Linux)
			if( ! @rename( $af_cache_file.'.tmp', $af_cache_file ) )
			{	// Rename failed, we are probably on windows PHP <= 5.2.5... http://bugs.php.net/bug.php?id=44805
				// we have to split this:
				$Debuglog->add( 'Renaming of cache file failed. (Windows?)', 'cache' );
				// Kill cache:
				unlink( $af_cache_file );
				// Now, some other process might start to try caching (and will likely give up since the .tmp file already exists)
				if( ! @rename( $af_cache_file.'.tmp', $af_cache_file ) )
				{ // Hide errors bc another PHP process could have beaten us to writing a new file there
					// Anyways, we still could not rename, let's drop the .tmp file:
					unlink( $af_cache_file.'.tmp' );
				}
				else
				{
					$Debuglog->add( 'Cache updated... after unlink+rename!', 'cache' );
				}
			}
			else
			{
				$Debuglog->add( 'Cache updated!', 'cache' );
			}
		}
	}

}

/*
 * $Log$
 * Revision 1.9  2009/09/06 05:40:44  sam2kb
 * Make sure that blog cache directory exists
 *
 * Revision 1.8  2009/08/26 19:03:59  tblue246
 * doc
 *
 * Revision 1.7  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.6  2009/01/25 19:09:32  blueyed
 * phpdoc fixes
 *
 * Revision 1.5  2008/10/05 07:18:06  fplanque
 * thow in a tiny doc about windows bug
 *
 * Revision 1.4  2008/10/05 07:11:38  fplanque
 * I think it's atomic now
 *
 * Revision 1.3  2008/10/05 04:43:19  fplanque
 * minor, and would 4096 serve any purpose?
 *
 * Revision 1.2  2008/10/03 16:27:56  blueyed
 * Fix indent, cleanup, doc
 *
 * Revision 1.1  2008/09/28 08:06:06  fplanque
 * Refactoring / extended page level caching
 * */
?>
