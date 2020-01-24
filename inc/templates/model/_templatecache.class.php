<?php
/**
 * This file implements the TemplateCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package templates
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );
load_class( 'templates/model/_template.class.php', 'Template' );

/**
 * Blog Cache Class
 *
 * @package templates
 */
class TemplateCache extends DataObjectCache
{
	/**
	 * Cache by template code
	 *
	 * @var cache_by_code array
	 */
	var $cache_by_code;
	
	/**
	 * Constructor
	 *
	 * @param string Name of the order field or NULL to use name field
	 */
	function __construct()
	{
		parent::__construct( 'Template', false, 'T_templates', 'tpl_', 'tpl_ID', 'tpl_name' );
	}


	/**
	 * Add a template to the cache
	 *
	 * @param object Template
	 * @return boolean true if it was added false otherwise
	 */
	function add( $Template )
	{
		$code = $Template->get( 'code' );
		$this->cache_by_code[$code] = & $Template;

		return parent::add( $Template );
	}


	/**
	 * Get template from the given code
	 *
	 * @param string code
	 * @return array of WidgetContainer
	 */
	function & get_by_code( $code )
	{
		$template = NULL;

		if( isset( $this->cache_by_code[ $code ] ) )
		{
			return $this->cache_by_code[ $code ];
		}

		return $template;
	}


	/**
	 * Returns option array with cache contents
	 *
	 * Load the cache if necessary
	 *
	 * @param string Callback method name
	 * @param array IDs to ignore.
	 * @return string
	 */
	function get_code_option_array( $method = 'get_name', $ignore_IDs = array() )
	{
		if( ! $this->all_loaded && $this->load_all )
		{ // We have not loaded all items so far, but we're allowed to.
			if ( empty( $ignore_IDs ) )
			{	// just load all items
				$this->load_all();
			}
			else
			{	// only load those items not listed in $ignore_IDs
				$this->load_list( $ignore_IDs, true );
			}
		}

		$r = array();

		foreach( $this->cache as $loop_Obj )
		{
			if( in_array( $loop_Obj->code, $ignore_IDs ) )
			{	// Ignore this ID
				continue;
			}

			$r[$loop_Obj->code] = $loop_Obj->$method();
		}

		return $r;
	}
}