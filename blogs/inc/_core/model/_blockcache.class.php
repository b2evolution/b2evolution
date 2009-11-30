<?php
/**
 * This file implements the BlockCache class, which caches HTML blocks/snippets genereated by the app.
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
 * Block Cache.
 *
 * @package evocore
 */
class BlockCache
{
	var $keys;
	var $serialized_keys = '';

  /**
	 * After how many bytes should we output sth live while collecting cache content:
	 */
	var $output_chunk_size = 2000;

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
	 */
	function BlockCache( $keys )
	{
		// Make sure keys are always in the same order:
		ksort( $keys );

		$this->keys = $keys;

		foreach( $keys as $key => $val )
		{
			$this->serialized_keys .= $key.':'.$val.'-';
		}

		// echo $this->serialized_keys;
	}



	/**
	 * Check if cache contents are available, otherwise start collecting output to be cached
	 *
	 * @return true if we found and have echoed content from the cache
	 */
	function check()
	{
		global $Debuglog;

		if( $this->retrieve() )
		{ // We could retrieve:
			return true;
		}

		$this->is_collecting = true;

		$Debuglog->add( 'Collecting started', 'blockcache' );

		echo 'collecting:'.$this->serialized_keys;

		ob_start( array( & $this, 'output_handler'), $this->output_chunk_size );

		return false;

	}


	/**
	 * Retrieve and output cache
	 *
	 * @return boolean true if we could retrieve
	 */
	function retrieve()
	{
		global $Debuglog;
		global $servertimenow;

		// return false;

		$content = apc_fetch( $this->serialized_keys, $success );

		if( ! $success )
		{
			return false;
		}

		echo 'retrieved:'.$this->serialized_keys;

		// SEND CONTENT!
		echo $content;

		return true;
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

 		$Debuglog->add( 'Aborting cache data collection...', 'blockcache' );

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

		$this->cacheproviderstore( $this->cached_page_content );
	}


	function cacheproviderstore( $payload )
	{
		apc_store( $this->serialized_keys, $payload, 3600 * 24 );
	}

	/**
	 * Invalidate a special key
	 *
	 * All we do is store the timestamp of teh invalidation
	 *
	 */
	function invalidate_key2()
	{
		global $servertimenow;

		cacheproviderstore( $invalidate_what, $servertimenow );
	}

	/**
	 * Store something in the cache
	 *
	 * We just concatenate all the individual keys to have a single one
	 * Then we store with the current timestamp
	 *
	 * @param array of keyname => keyvalue
	 */
	function store2( $keys, $payload )
	{
		global $servertimenow;

		cacheproviderstore(
            "itm:$key_item_ID-usr:$key_user_ID-etc-key:$key_cacheitem",
             $servertimenow.' '.$payload );
	}

	/**
	 * Retrieve something from the cache
	 *
	 * Basically we get all the invalidation dates we need, then we get the
	 * data and then we check if some invalidation occured after the data was cached.
	 * If an invalidation date is missing we consider the cache to be
	 * obsolete but we generate a new invalidation date for next time we try to retrieve.
	 *
	 * @param array of keyname => keyvalue
	 */
	function retrieve2( $keys )
	{
		global $servertimenow;

    $ts_last_invalidated_item_ID = cacheproviderretrieve( "special_key_itm_$key_item_ID" );
     if( empty( $ts_last_invalidated_item_ID ) )
     { // we lost the special key, regenerate the special key and abort retrieval for this time
        cache_invalidate( 'itm_$key_item_ID', $servertimenow );
        return NULL;
     }

     $ts_last_invalidated_user_ID = cacheproviderretrieve( "sepcial_key_usr_$key_user_ID" );
     if( empty( $ts_last_invalidated_user_ID ) )
     { // we lost the special key, regenerate the special key and abord retrieval for this time
        cache_invalidate( 'usr_$key_user_ID', $servertimenow );
        return NULL;
     }

     // Repeat for each... yeah I know it's not DRY but this is just  prototype :>

     // Don't do anythign for $key_cacheitem

     $raw_payload = cacheproviderretrieve( "itm:$key_item_ID-usr:$key_user_ID-etc-key:$key_cacheitem" );

     list( $payload_ts, $real_payload ) = split( $raw_payload  );

     if( $payload_ts > $ts_last_invalidated_item_ID
        && $payload_ts > $ts_last_invalidated_user_ID
        // && etc...
        )
     {   // We're good, the cache is fresher than all invalidation dates:
          return $real_payload;
     }

     // The cache is older than some invalidation date:
     cacheproviderdelete(  "itm:$key_item_ID-usr:$key_user_ID-etc-key:$key_cacheitem" );
      return NULL;

	}

}

/*
 * $Log$
 * Revision 1.1  2009/11/30 04:31:37  fplanque
 * BlockCache Proof Of Concept
 *
 */
?>
