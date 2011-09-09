<?php
/**
 * This file implements the Internal search item class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Internal search item Class
 *
 * @package evocore
 */
class InternalSearches extends DataObject
{
	var $keywords = '';
	var $name = '';
	/**
	 * @var int
	 */
	var $coll_ID = '';
	var $hit_ID = '';

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function InternalSearches( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_logs__internal_searches', 'isrch_', 'isrch_ID' );

 		if( $db_row )
		{
			$this->ID            = $db_row->isrch_ID;
			$this->coll_ID       = $db_row->isrch_coll_ID;
			$this->hit_ID        = $db_row->isrch_hit_ID;
			$this->keywords      = $db_row->isrch_keywords;
			
		}
		else
		{	// Create a new internal search item:
		}
	}


	/**
	 * Generate help title text for action
	 *
	 * @param string action code: edit, delete, etc.
	 * @return string translated help string
	 */
	function get_action_title( $action )
	{
		switch( $action )
		{
			case 'edit': return T_('Edit this internal search...');
			case 'copy': return T_('Duplicate this internal search...');
			case 'delete': return T_('Delete this internal search!');
			default:
				return '';
		}
	}


	/**
	 * Check permission on a persona
	 *
	 * @todo fp> break up central User::check_perm() so that add-on modules do not need to add code into User class.
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action= 'view', $assert = true )
	{
		/**
		* @var User
		*/
		global $current_User;

		return $current_User->check_perm( 'stats', $action, $assert );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Coll ID
		$this->set_string_from_param( 'coll_ID', true );

		// Hit ID
		$this->set_string_from_param( 'hit_ID', true );

		// Keywords :
		$this->set_string_from_param( 'keywords' );
		
		return ! param_errors_detected();
	}


	function get_keywords()
	{
		return $this->keywords;
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'coll_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );
				
			case 'hit_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'keywords':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Auto pruning of old stats.
	 *
	 * It uses a general setting to store the day of the last prune, avoiding multiple prunes per day.
	 *
	 * NOTE: do not call this directly, but only in conjuction with auto_prune_stats_mode.
	 *
	 * @todo fp>al: move this to HitList::dbprune() thta function should prune everything that is related all together (it already does Hits & Sessions)
	 *
	 * @static
	 * @return string Empty, if ok.
	 */
	function dbprune()
	{
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog, $Settings, $localtimenow;
		global $Plugins;

		// Prune when $localtime is a NEW day (which will be the 1st request after midnight):
		$last_prune = $Settings->get( 'auto_prune_stats_done' );
		if( $last_prune >= date('Y-m-d', $localtimenow) && $last_prune <= date('Y-m-d', $localtimenow+86400) )
		{ // Already pruned today (and not more than one day in the future -- which typically never happens)
			return T_('Pruning has already been done today');
		}

		$time_prune_before = ($localtimenow - ($Settings->get('auto_prune_stats') * 86400)); // 1 day = 86400 seconds

		$ids = $DB->get_results( "
			SELECT hit_ID FROM T_hitlog
			WHERE hit_datetime < '".date('Y-m-d', $time_prune_before)."'", 'Getting hit ids for prune' );
		$rows_affected=0;
		foreach ($ids as $item) {
			$rows_affected+=$DB->query( "DELETE FROM T_logs__internal_searches WHERE isrch_hit_ID=". $item->hit_ID );
		}
		$Debuglog->add( 'InternalSearches::dbprune(): autopruned '.$rows_affected.' rows from T_logs__internal_searches.', 'request' );

		// Optimizing tables
		$DB->query('OPTIMIZE TABLE T_logs__internal_searches');

		return ''; /* ok */
	}


}


/*
 * $Log$
 * Revision 1.4  2011/09/09 21:53:55  fplanque
 * doc
 *
 * Revision 1.3  2011/09/08 17:59:59  lxndral
 * Prune for internal searches
 *
 * Revision 1.2  2011/09/08 11:04:04  lxndral
 * fix for internal searches
 *
 * Revision 1.1  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.0  2011/09/05 20:07:19  alexader
 *
 */
?>