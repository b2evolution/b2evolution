<?php
/**
 * This file implements the post type cache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

/**
 * ItemTypeCache Class
 *
 * @package evocore
 */
class ItemTypeCache extends DataObjectCache
{
	/**
	 * Post type cache for each collection
	 */
	var $col_cache = array();

	/**
	 * Default post type for each collection
	 */
	var $col_default = array();

	/**
	 * Object array by template name
	 */
	var $cache_template = array();

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct()
	{
		// Call parent constructor:
		parent::__construct( 'ItemType', true, 'T_items__type', 'ityp_', 'ityp_ID', 'ityp_name', 'ityp_ID' );
	}


	/**
	 * Returns a form option list which only contains post types that can
	 * be used by the current user (and in the current blog's context).
	 *
	 * The user cannot use any post type IDs listed in the {@see $posttypes_reserved_IDs}
	 * array; to use the "Page", "Intro-*", "Podcast" and "Sidebar link"
	 * post types, the current blog must grant the blog_page, blog_intro,
	 * blog_podcast and blog_sidebar permission, respectively (see blog
	 * user/group permissions).
	 *
	 * @deprecated
	 *
	 * @param integer The selected ID.
	 * @param boolean Provide a choice for "none" with ID ''
	 * @param string  Callback method name.
	 * @return string
	 */
	function get_option_list_usable_only( $default = 0, $allow_none = false, $method = 'get_name' )
	{
		return $this->get_option_list( $default, $allow_none, $method );
	}


	/**
	 * For use by Universal Item List widget
	 *
	 * @param array IDs to ignore.
	 * @return array
	 */
	function get_option_array( $ignore_IDs = array() )
	{
		return $this->get_option_array_worker( 'get_name', $ignore_IDs );
	}


	/**
	 * For use by Universal Item List widget and item type edit form
	 *
	 * @return array
	 */
	function get_usage_option_array()
	{
		return array(
				T_('In content flow') => array(
						'post' => /* TRANS: noun */ T_('Post')
					),
				T_('Out of content flow') => array(
						'page'    => T_('Page'),
						'special' => T_('Special'),
						'content-block' => T_('Content Block'),
					),
				T_('Intros') => array(
						'intro-front' => T_('Intro-Front'),
						'intro-main'  => T_('Intro-Main'),
						'intro-cat'   => T_('Intro-Cat'),
						'intro-tag'   => T_('Intro-Tag'),
						'intro-sub'   => T_('Intro-Sub'),
						'intro-all'   => T_('Intro-All'),
					),
			);
	}


	/**
	 * Get an Item Type from cache by template name
	 *
	 * Load the cache if necessary (all at once if allowed).
	 *
	 * @param string Template name
	 * @param boolean true if function should die on error
	 * @param boolean true if function should die on empty/null
	 * @return object|NULL|boolean Reference on cached object, NULL - if request with empty template name, FALSE - if requested object does not exist
	 */
	function & get_by_template( $template_name, $halt_on_error = true, $halt_on_empty = true )
	{
		global $DB, $Debuglog;

		if( empty( $template_name ) )
		{	// Don't allow request with empty template name:
			if( $template_name )
			{
				debug_die( 'Requested '.$this->objtype.' from '.$this->dbtablename.' without template name!' );
			}
			$r = NULL;
			return $r;
		}

		if( isset( $this->cache_template[ $template_name ] ) )
		{	// Get object from cache by template name:
			$Debuglog->add( 'Accessing <strong>'.$this->objtype.'('.$template_name.')</strong> from cache by template name', 'dataobjects' );
			return $this->cache_template[ $template_name ];
		}

		// Load just the requested object:
		$Debuglog->add( 'Loading <strong>'.$this->objtype.'('.$template_name.')</strong>', 'dataobjects' );
		$SQL = $this->get_SQL_object();
		$SQL->WHERE_and( $this->dbprefix.'template_name = '.$DB->quote( $template_name ) );

		if( $db_row = $DB->get_row( $SQL->get(), OBJECT, 0, __CLASS__.'::'.__FUNCTION__.'()' ) )
		{
			$resolved_ID = $db_row->{$this->dbIDname};
			$Debuglog->add( 'success; ID = '.$resolved_ID, 'dataobjects' );
			if( ! isset( $this->cache[$resolved_ID] ) )
			{	// Object is not already in cache:
				$Debuglog->add( 'Adding to cache...', 'dataobjects' );
				//$Obj = new $this->objtype( $row ); // COPY !!
				if( ! $this->add( $this->new_obj( $db_row ) ) )
				{	// could not add
					$Debuglog->add( 'Could not add() object to cache!', 'dataobjects' );
				}
			}
			if( ! isset( $this->cache_template[ $template_name ] ) )
			{	// Add object in cache by template name:
				$this->cache_template[ $template_name ] = $this->new_obj( $db_row );
			}
		}

		if( empty( $this->cache_template[ $template_name ] ) )
		{	// Object does not exist by requested template name:
			$Debuglog->add( 'Could not get ItemType by template name.', 'dataobjects' );
			if( $halt_on_error )
			{
				debug_die( 'Requested '.$this->objtype.' does not exist!' );
			}
			$this->cache_template[ $template_name ] = false;
		}

		return $this->cache_template[ $template_name ];
	}
}

?>