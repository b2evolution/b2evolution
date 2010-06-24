<?php
/**
 * This file implements the PageCache class, which caches HTML pages genereated by the app.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
	var $max_age_seconds = 900;  // 15 minutes for now

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
	 * Are we currently recording cache contents
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
				$Debuglog->add( 'General cache not enabled.', 'pagecache' );
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
				$Debuglog->add( 'Cache not enabled for this blog.', 'pagecache' );
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
		global $ReqURL;

		// We want the cache for the current URL
		if( empty( $this->cache_filepath ) )
		{
 			$Debuglog->add( 'URL being cached: '.$ReqURL, 'pagecache' );

 			$this->cache_filepath = $this->gen_filecache_path( $ReqURL );

 			$Debuglog->add( 'Cache file: '.$this->cache_filepath, 'pagecache' );
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
		$Debuglog->add( 'Invalidating:'.$url, 'pagecache' );

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
			$Debuglog->add( 'Cache not enabled. No lookup nor caching performed.', 'pagecache' );
			return false;
		}

		if( $disp == '404' )
		{	// We do NOT want caching for 404 pages (illimited possibilities!)
			$Debuglog->add( 'Never cache 404s!', 'pagecache' );
			return false;
		}

		if( is_logged_in() )
		{	// We do NOT want caching when a user is logged in (private data)
			$Debuglog->add( 'Never cache pages for/from logged in members!', 'pagecache' );
			return false;
		}

		if( $Messages->count('all') )
		{	// There are some messages do be displayed. That means the user has done some action.
			// We do want to display those messages.
			// There may also be more... like a "comment pending review" etc...
			// DO NOT CACHE and do not present a cached page.
			$Debuglog->add( 'Not caching because we have messages!', 'pagecache' );
			return false;
		}

	/* fp> It feels like a waste of time to do this for every page hit
	   sam2kb> I spent 20 minutes trying to figure out why caching doesn't work after upgrade,
		 checked directory perms etc... Do you think that everybody will save cache
		 dir content when they backup/upgrade b2evo?
		 If blog cache directoty is missing users will have to disable and reenable
		 caching for each blog (what if they have 100 blogs),
		 but they won't because they will never know that caching is disabled ;)
		 fp> Yeah, I'm actually still under the illusion that some people will just follow the upgrade instructions.
		 Anyways, we can try to recreate the cache directory:
		 	- everytime we upgrade
			- everytime we try to write to it
		 We canNOT try to recreate:
		 	- everytime we try to read from it

		if( ! $this->cache_create( false ) )
		{	// Make sure that blog cache directory exists
			$Debuglog->add( 'Could not create cache directory: '.$this->ads_collcache_path, 'pagecache' );
			return false;
		}
	*/

		// TODO: fp> If the user has submitted a comment, we might actually want to invalidate the cache...


		if( $this->retrieve() )
		{ // We could retrieve:
			return true;
		}


		$this->is_collecting = true;

		$Debuglog->add( 'Collecting started', 'pagecache' );

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
		global $ReqURL;
		global $servertimenow;
		global $Timer;

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

		$Timer->resume( 'Read cache file' );
		$lines = @file( $af_cache_file, false );
		$Timer->pause( 'Read cache file' );

		// fp> note we are using empty() so that we detect both the case where there is no file and the case where the file
		// might have ended up empty because PHP crashed while writing to it or sth like that...
		if( ! empty($lines) )
		{	// We have data in the cache!
			$Debuglog->add( 'Retrieving from cache!', 'pagecache' );

			$Timer->resume( 'Cache file processing' );

			// Check that the format of the file if OK.
			$sep = trim($lines[2]);
			unset($lines[2]);
			if( $sep != '' )
			{
				$Debuglog->add( 'Cached file format not recognized, aborting retrieve.', 'pagecache' );
				return false;
			}

			// Retrieved cached URL:
			$retrieved_url = trim($lines[0]);
			unset($lines[0]);
			if( $retrieved_url != $ReqURL )
			{
				$Debuglog->add( 'Cached file URL ['.$retrieved_url.'] does not match current URL, aborting retrieve.', 'pagecache' );
				return false;
			}

			// timestamp of cache generation:
			$retrieved_ts = trim($lines[1]);
			unset($lines[1]);
			$cache_age = $servertimenow-$retrieved_ts;
			$Debuglog->add( 'Cache age: '.floor($cache_age/60).' min '.($cache_age % 60).' sec', 'pagecache' );
			if( $cache_age > $this->max_age_seconds )
			{	// Cache has expired
				return false;
			}


			// Check if the request has an If-Modified-Since date
	  	if( array_key_exists( 'HTTP_IF_MODIFIED_SINCE', $_SERVER) )
    	{
        $if_modified_since = strtotime( preg_replace('/;.*$/','',$_SERVER['HTTP_IF_MODIFIED_SINCE']) );
        if( $retrieved_ts <= $if_modified_since )
        {	// Cached version is equal to (or older than) $if_modified since; contents not modified, send 304!

        // fp> IMPORTANT: thsi will not prevent you from logging out but it can prevent you from seeing you're logged out.
        // It may tell you that the version with the evobar was not modified so your browser will show the evobar again
        // TODO: investigate ETag & Vary Headers.
        // Vary on cookie, does that work?
        // ETag: maybe sending user ID would be enough  and then denying 304 whenever an Etag is sent back

					header( 'HTTP/1.0 304 Not Modified' );
					exit(0);
        }
			}

			// ============== Ready to send cached version of the page =================

			// Send no cache header including last modified date:
			header_nocache( $retrieved_ts );

			// Go through headers that were saved in the cache:
			$i = 2;
			while( $headerline = trim($lines[++$i]) )
			{
				header( $headerline );
				unset($lines[$i]);
			}
			unset($lines[$i]);

			// SEND CONTENT!
			$body = implode('',$lines);

			$Timer->pause( 'Cache file processing' );

			$Timer->resume( 'Sending cached content' );

			// Echo a first chunk (see explanation below)
			$buffer_size = 12000;  // Empiric value, you can make it smaller if you show me screenshots of better timings with a smaller value
			echo substr( $body, 0, $buffer_size );

			ob_start();
			// fp> So why do we want an ob_start here?
			// fp> Because otherwise echo will "hang" until all the data gets passed through apache (on default Apache install with default SendBufferSize)
			// fp> With ob_start() the script will terminate much faster and the total exec time of the script will look much smaller.
			// fp> This doesn't actually improve the speed of the transmission, it just lets the PHP script exit earlier
			// fp> DRAWBACK: shutdown will be executed *before* the "ob" data is actually sent :'(
			// fp> This is why we send a first chunk of data before ob_start(). shutdown can occur while that data is sent. then the remainder is sent.
			// Inspiration: http://wonko.com/post/seeing_poor_performance_using_phps_echo_statement_heres_why
			//              http://fplanque.com/dev/linux/how-to-log-request-processing-times-in-apache
			//              http://fplanque.com/dev/linux/why-echo-is-slow-in-php-how-to-make-it-really-fast
			// fp> TODO: do something similar during page cache collection.

			echo substr( $body, $buffer_size );
			// ob_end_flush(); // fp> WARNING: Putting an end flush here would just kill the benefit of the ob_start() above.

			$Timer->pause( 'Sending cached content' );

			return true;
		}

		return false;
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

 		$Debuglog->add( 'Aborting cache data collection...', 'pagecache' );

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
			$Debuglog->add( 'Could not open cache file!', 'pagecache' );
		}
		else
		{
			// Put the URL of the page we are caching into the cache. You can never be to paranoid!
			// People can change their domain names, folder structures, etc... AND you cannot trust the hash to give a
			// different file name in 100.00% of the cases! Serving a page for a different URL would be REEEEEALLLY BAAAAAAD!
			global $ReqURL;
			$file_head = $ReqURL."\n";

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
				$Debuglog->add( 'Renaming of cache file failed. (Windows?)', 'pagecache' );
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
					$Debuglog->add( 'Cache updated... after unlink+rename!', 'pagecache' );
				}
			}
			else
			{
				$Debuglog->add( 'Cache updated!', 'pagecache' );
			}
		}
	}


	/**
	 * Delete any file that is older than 24 hours from the whole /cache folder (recursively) 
	 * except index.html files and hiddenfiles (starting with .)
	 * 
	 * @static
	 *
	 * @return string empty string on success, error message otherwise.
	 */
	function prune_page_cache()
	{
		global $cache_path, $localtimenow;

		// fp> TODO: on large sites this is not a good scaling mechanism. We should rewrite this and delete files immediately after
		// finding them instead of creating an ENORMOUS list of files in memory.
		$cache_content = get_filenames( $cache_path, true, false );

		if( $cache_content == false )
		{
			return T_('Cache directory could not be accessed.');
		}

		$first_error = '';
		foreach( $cache_content as $file_path )
		{
			// get file name from path
			$file_name = basename($file_path);

			// Note: index.html pages are in the cache to hide the contents from browsers in case the webserver whould should a listing
			if( $file_name != 'index.html' && substr( $file_name, 0, 1 ) != '.' )
			{ // this file is not an index.html and it is not hidden
				$datediff = $localtimenow - filemtime( $file_path );
				if( $datediff > 86400 /* 60*60*24 = 24 hour*/)
				{ // older than 24 hours
					// TODO: I think errors should not get suppressed - would show up in error log / cron mail, but that's good IMHO
					// fp> TODO: do not stop deletion when one file cannot be deleted
					if( ( ! @unlink( $file_path ) ) && ($first_error == '') )
					{ // deleting the file failed: return error
						//$error = error_get_last(); // XXX: requires PHP 5.2
						//$error['message']; - concat this to the first
						$first_error = $file_name;
					}
				}
			}
		}

		if( $first_error != '' )
		{
			return sprintf( T_('Some files could not be deleted (including: %s).'), $first_error );
		}
		return '';
	}
}


/*
 * $Log$
 * Revision 1.21  2010/06/24 06:03:59  efy-asimo
 * Prune page cache - don't stop after the first error - fix
 *
 * Revision 1.20  2010/05/21 10:46:31  efy-asimo
 * move prune_page_cache() function from _file.funcs.php to _pagecache.class.php
 *
 * Revision 1.19  2010/02/08 17:51:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.18  2009/12/22 08:53:34  fplanque
 * global $ReqURL
 *
 * Revision 1.17  2009/12/06 03:24:11  fplanque
 * minor/doc/fixes
 *
 * Revision 1.16  2009/12/05 01:21:59  fplanque
 * PageChace 304 handling
 *
 * Revision 1.15  2009/12/03 06:05:40  fplanque
 * Make cache performance visible (instead of hidden behind Apache's SendBuffer)
 *
 * Revision 1.14  2009/11/30 04:31:37  fplanque
 * BlockCache Proof Of Concept
 *
 * Revision 1.13  2009/11/30 00:22:04  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.12  2009/09/08 21:32:41  fplanque
 * doc
 *
 * Revision 1.11  2009/09/08 02:59:33  sam2kb
 * doc
 *
 * Revision 1.10  2009/09/07 23:35:49  fplanque
 * cleanup
 *
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
