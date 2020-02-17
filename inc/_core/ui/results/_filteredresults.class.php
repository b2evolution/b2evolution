<?php
/**
 * This file implements the Filtered Results class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/ui/results/_results.class.php', 'Results' );


/**
 * @package evocore
 */
class FilteredResults extends Results
{
	/**
	 * Default filter set (used if no specific params are passed)
	 */
	var $default_filters = array();

	/**
	 * Current filter set (depending on user input)
	 */
	var $filters = array();

	/**
	* Constructor
	* 
	* @param string Filterset name
	*/
	function __construct( $filterset_name )
	{
		$this->filterset_name = $filterset_name;
	}

	/**
	 * Check if the Result set is filtered or not
	 */
	function is_filtered()
	{
		if( empty( $this->filters ) )
		{
			return false;
		}

		return ( $this->filters != $this->default_filters );
	}


	/**
	 * Get a specific active filter
	 */
	function get_active_filter( $key )
	{
		if( isset($this->filters[$key]) )
		{
			return $this->filters[$key];
		}

		return NULL;
	}


	/**
	 * Get every "active" filter, i-e: that is not the same as the defaults
	 */
	function get_active_filters()
	{
		$r = array();

		foreach( $this->default_filters as $key => $value )
		{
			if( !isset( $this->filters[$key] ) )
			{	// Some value has not been copied over from defaults to active or specifically set:
				if( !is_null($value)) // Note: NULL value are not copied over. that's normal.
				{	// A NON NULL value is missing
					$r[] = $key;
				}
			}
			elseif( $value != $this->filters[$key] )
			{
				$r[] = $key;
			}
		}
		return $r;
	}


	/**
	 * Show every active filter that is not the same as the defaults
	 */
	function dump_active_filters()
	{
		foreach( $this->default_filters as $key => $value )
		{
			if( !isset( $this->filters[$key] ) )
			{	// Some value has not been copied over from defaults to active or specifically set:
				if( !is_null($value)) // Note: NULL value ar enot copied over. that's normal.
				{	// A NON NULL value is missing
					pre_dump( 'no active value for default '.$key );
				}
			}
			elseif( $value != $this->filters[$key] )
			{
				pre_dump( 'default '.$key, $value );
				pre_dump( 'active '.$key, $this->filters[$key] );
			}
		}
	}


	/**
	 * Set default filter values we always want to use if not individually specified otherwise:
	 *
	 * @param array default filters to be merged with the class defaults
	 * @param array default filters for each preset, to be merged with general default filters if the preset is used
	 */
	function set_default_filters( $default_filters, $preset_filters = NULL )
	{
		$this->default_filters = array_merge( $this->default_filters, $default_filters );
		if( $preset_filters !== NULL )
		{	// Overrride preset filters only when this is requested:
			$this->preset_filters = $preset_filters;
		}
	}


	/**
	 * Activate preset default filters if necessary
	 */
	function activate_preset_filters()
	{
		if( empty( $this->filters['filter_preset'] ) )
		{ // No filter preset, there are no additional defaults to use:
			return;
		}

		if( isset( $this->preset_filters[$this->filters['filter_preset']] ) )
		{	// Override general defaults with the specific defaults for the preset:
			$this->default_filters = array_merge( $this->default_filters, $this->preset_filters[$this->filters['filter_preset']] );
		}

		// Save the name of the preset in order for is_filtered() to work properly:
		$this->default_filters['filter_preset'] = $this->filters['filter_preset'];
	}


	/**
	 * Save current filterset to session.
	 */
	function save_filterset()
	{
		/**
		 * @var Session
		 */
		global $Session, $Debuglog, $localtimenow;

		$Debuglog->add( 'Saving filterset <strong>'.$this->filterset_name.'</strong>', 'filters' );

		$Session->set( $this->filterset_name, $this->filters );
		$Session->set( $this->filterset_name.'_refresh_time', floor( $localtimenow / 3600 ) * 3600 );
	}


	/**
	 * Load previously saved filterset from session.
	 *
	 * @return boolean true if we could restore something
	 */
	function restore_filterset()
	{
		/**
		 * @var Session
		 */
			global $Session;
		/**
		 * @var Request
		 */

		global $Debuglog;

		$filters = $Session->get( $this->filterset_name );

		if( empty($filters) )
		{ // set_filters() expects array
			$filters = array();
		}

		$Debuglog->add( 'Restoring filterset <strong>'.$this->filterset_name.'</strong>', 'filters' );

		// Restore filters:
		$this->set_filters( $filters );

		return true;
	}


	/**
	 * Set/Activate filterset.
	 *
	 * @param array
	 */
	function set_filters( $filters )
	{
		if( !empty( $filters ) )
		{ // Activate the filterset (fallback to default filter when a value is not set):
			$this->filters = array_merge( $this->default_filters, $filters );
		}

		// Activate preset filters if necessary:
		$this->activate_preset_filters();
	}


	/**
	 * Check if filter query has at least one selected filter with given value and condition
	 *
	 * @param string Filter name
	 * @param string Value
	 * @param string Condition operator: =, !=, >, >=, <, <=
	 */
	function check_filter_query( $filter, $value, $condition = '=' )
	{
		if( empty( $this->filters['filter_query'] ) )
		{	// Filter query is not defined for this results list:
			return false;
		}

		if( ! preg_match_all( '/{"id":"'.$filter.'"[^}]+"value":"([^"]*)"[^}]*}/i', $this->filters['filter_query'], $filter_values ) )
		{	// Current filter query has no value of the requested filter:
			return false;
		}

		foreach( $filter_values[1] as $filter_value )
		{
			switch( $condition )
			{
				case '=':
					$result = ( $filter_value == $value );
					break;
				case '!=':
					$result = ( $filter_value != $value );
					break;
				case '>':
					$result = ( $filter_value > $value );
					break;
				case '>=':
					$result = ( $filter_value >= $value );
					break;
				case '<':
					$result = ( $filter_value < $value );
					break;
				case '<=':
					$result = ( $filter_value <= $value );
					break;
			}
			if( $result )
			{	// Stop of the first searched value:
				return $result;
			}
		}

		return false;
	}



	/**
	 * Widget callback for template vars.
	 *
	 * This allows to replace template vars, see {@link Widget::replace_callback()}.
	 *
	 * @return string
	 */
	function replace_callback( $matches )
	{
		// echo '['.$matches[1].']';
		switch( $matches[1] )
		{
			case 'reset_filters_button':
				if( ! $this->is_filtered() )
				{
					return '';
				}
				// Resetting the filters is the same as applying preset 'all' (should be defined for all Results tables)
				if( !isset($this->filter_area['presets']['all'][1]) )
				{
					return '';
				}
				return '<a href="'.$this->filter_area['presets']['all'][1].'" class="btn btn-sm btn-warning">'.get_icon('reset_filters').T_('Remove filters').'</a>';

			default :
				return parent::replace_callback( $matches );
		}
	}

}
?>