<?php
/**
 * This file implements the UserQuery class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _userquery.class.php 13 2011-10-24 23:42:53Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/db/_sql.class.php', 'SQL' );

/**
 * UserQuery: help constructing queries on Users
 * @package evocore
 */
class UserQuery extends SQL
{
	/**
	 * Fields of users table to search by keywords
	 * 
	 */
	var $keywords_fields = 'user_login, user_firstname, user_lastname, user_nickname, user_email';

	/**
	 * Constructor.
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 * @param array Query params
	 */
	function UserQuery( $dbtablename = 'T_users', $dbprefix = 'user_', $dbIDname = 'user_ID', $params = array() )
	{
		global $collections_Module;

		$this->dbtablename = $dbtablename;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;

		// Params to build query
		$params = array_merge( array(
				'join_group'   => true,
				'join_session' => false,
				'join_country' => true,
				'join_city'    => true,
				'grouped'      => false,
			), $params );

		$this->SELECT( 'user_ID, user_login, user_nickname, user_lastname, user_firstname, user_gender, user_source, user_created_datetime, user_profileupdate_date, user_lastseen_ts, user_level, user_status, user_avatar_file_ID, user_email, user_url, user_age_min, user_age_max, user_pass, user_locale, user_unsubscribe_key, user_reg_ctry_ID, user_ctry_ID, user_rgn_ID, user_subrg_ID, user_city_ID, user_grp_ID' );
		$this->SELECT_add( ', IF( user_avatar_file_ID IS NOT NULL, 1, 0 ) as has_picture' );
		$this->FROM( $this->dbtablename );

		if( $params['join_group'] )
		{ // Join Group
			$this->SELECT_add( ', grp_ID, grp_name' );
			$this->FROM_add( 'LEFT JOIN T_groups ON user_grp_ID = grp_ID ' );
		}

		if( $params['join_session'] )
		{ // Join Session
			$this->SELECT_add( ', MAX(T_sessions.sess_lastseen_ts) as sess_lastseen' );
			$this->FROM_add( 'LEFT JOIN T_sessions ON user_ID = sess_user_ID' );
		}

		if( $params['join_country'] )
		{ // Join Country
			$this->SELECT_add( ', c.ctry_name, c.ctry_code, rc.ctry_name AS reg_ctry_name, rc.ctry_code AS reg_ctry_code' );
			$this->FROM_add( 'LEFT JOIN T_regional__country AS c ON user_ctry_ID = c.ctry_ID ' );
			$this->FROM_add( 'LEFT JOIN T_regional__country AS rc ON user_reg_ctry_ID = rc.ctry_ID ' );
		}

		if( $params['join_city'] )
		{ // Join City
			$this->SELECT_add( ', city_name, city_postcode' );
			$this->FROM_add( 'LEFT JOIN T_regional__city ON user_city_ID = city_ID ' );
		}

		if( isset( $collections_Module ) )
		{	// We are handling blogs:
			$this->SELECT_add( ', COUNT( DISTINCT blog_ID ) AS nb_blogs' );
			$this->FROM_add( 'LEFT JOIN T_blogs on user_ID = blog_owner_user_ID ' );
		}
		else
		{
			$this->SELECT_add( ', 0 AS nb_blogs' );
		}

		$this->WHERE( 'user_ID IS NOT NULL' );
		if( $params['grouped'] )
		{ // Group by user group
			$this->GROUP_BY( 'user_ID, grp_ID' );
			$this->ORDER_BY( 'grp_name, *, user_profileupdate_date DESC, user_lastseen_ts DESC, user_ID ASC' );
		}
		else
		{
			$this->GROUP_BY( 'user_ID' );
			$this->ORDER_BY( '*, user_profileupdate_date DESC, user_lastseen_ts DESC, user_ID ASC' );
		}
	}


	/**
	 * Restrict with keywords
	 *
	 * @param string Keyword search string
	 */
	function where_keywords( $keywords )
	{
		global $DB;

		if( empty( $keywords ) )
		{
			return;
		}

		$search = array();

		$kw_array = explode( ' ', $keywords );
		foreach( $kw_array as $kw )
		{
			// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
			$search[] = 'CONCAT_WS( " ", '.$this->keywords_fields.' ) LIKE "%'.$DB->escape($kw).'%"';
		}

		if( count( $search ) > 0 )
		{
			$this->WHERE_and( implode( ' AND ', $search ) );
		}
	}


	/**
	 * Restrict with gender
	 *
	 * @param string Gender ( M, F, MF )
	 */
	function where_gender( $gender )
	{
		global $DB;

		if( empty( $gender ) )
		{
			return;
		}

		if( $gender == 'MF' )
		{	// Get men AND women
			$this->WHERE_and( 'user_gender IN ( "M", "F" )' );
		}
		else
		{	// Get men OR women
			$this->WHERE_and( 'user_gender = '.$DB->quote( $gender ) );
		}
	}


	/**
	 * Restrict to user status, currenlty activated also means autoactivated users
	 *
	 * @param string user status ( 'activated', 'deactivated', 'new', 'emailchanged', 'failedactivation', 'closed' )
	 * @param boolean set true to include users only with the given status, or set false to exclude users with the given status
	 * @param boolean set true to make exact comparing with selected status
	 */
	function where_status( $status, $include = true, $exactly = false )
	{
		global $DB;

		if( empty( $status ) )
		{
			return;
		}

		if( $status == 'activated' && !$exactly )
		{ // Activated and Autoactivated users
			if( $include )
			{
				$this->WHERE_and( 'user_status = '.$DB->quote( 'activated' ).' OR user_status = '.$DB->quote( 'autoactivated' ) );
			}
			else
			{
				$this->WHERE_and( 'user_status <> '.$DB->quote( 'activated' ).' AND user_status <> '.$DB->quote( 'autoactivated' ) );
			}
		}
		else
		{ // Other status check
			// init compare, which depends if we want to include or exclude users with the given status
			$compare = $include ? ' = ' : ' <> ';
			$this->WHERE_and( 'user_status'.$compare.$DB->quote( $status ) );
		}

		return;
	}


	/**
	 * Restrict to reported users
	 *
	 * @param boolean is reported
	 */
	function where_reported( $reported )
	{
		if( empty( $reported ) || !$reported )
		{
			return;
		}

		$this->SELECT_add( ', COUNT( DISTINCT urep_reporter_ID ) AS user_rep' );
		$this->FROM_add( ' LEFT JOIN T_users__reports ON urep_target_user_ID = user_ID' );
		$this->WHERE_and( 'urep_reporter_ID IS NOT NULL' );
	}


	/**
	 * Restrict to users with custom notifcation sender settings
	 *
	 * @param boolean with custom sender email
	 * @param boolean with custom sender name
	 */
	function where_custom_sender( $custom_sender_email, $custom_sender_name )
	{
		global $DB, $Settings;

		if( $custom_sender_email )
		{ // restrict to users with custom notification sender email address
			$this->FROM_add( ' LEFT JOIN T_users__usersettings as custom_sender_email ON custom_sender_email.uset_user_ID = user_ID AND custom_sender_email.uset_name = "notification_sender_email"' );
			$this->WHERE_and( 'custom_sender_email.uset_value IS NOT NULL AND custom_sender_email.uset_value <> '.$DB->quote( $Settings->get( 'notification_sender_email' ) ) );
		}

		if( $custom_sender_name )
		{ // restrict to users with custom notification sender name
			$this->FROM_add( ' LEFT JOIN T_users__usersettings as custom_sender_name ON custom_sender_name.uset_user_ID = user_ID AND custom_sender_name.uset_name = "notification_sender_name"' );
			$this->WHERE_and( 'custom_sender_name.uset_value IS NOT NULL AND custom_sender_name.uset_value <> '.$DB->quote( $Settings->get( 'notification_sender_name' ) ) );
		}
	}


	/**
	 * Restrict with user group
	 *
	 * @param integer User group ID
	 */
	function where_group( $group_ID )
	{
		global $DB;

		$group_ID = (int)$group_ID;

		if( $group_ID < 1 )
		{ // Group Id may be '0' - to show all groups, '-1' - to show all groups as ungrouped list
			return;
		}

		$this->WHERE_and( 'user_grp_ID = '.$DB->quote( $group_ID ) );
	}


	/**
	 * Restrict with location (Country | Region | Subregion | City)
	 *
	 * @param string Field name of location (ctry | rgn | subrg | city)
	 * @param integer Location ID
	 */
	function where_location( $location, $ID )
	{
		global $DB;

		if( empty( $ID ) )
		{
			return;
		}

		$this->WHERE_and( 'user_'.$location.'_ID = '.$DB->quote( $ID ) );
	}


	/**
	 * Restrict with age group
	 *
	 * @param integer Age min
	 * @param integer Age max
	 */
	function where_age_group( $age_min, $age_max )
	{
		global $DB;

		$sql_age = array();

		if( $age_min > 0 )
		{	// search_min_value BETWEEN user_age_min AND user_age_max
			$sql_age[] = '( '.$DB->quote( $age_min ).' >= user_age_min AND '.$DB->quote( $age_min ).' <= user_age_max )';
		}

		if( $age_max > 0 )
		{	// search_max_value BETWEEN user_age_min AND user_age_max
			$sql_age[] = '( '.$DB->quote( $age_max ).' >= user_age_min AND '.$DB->quote( $age_max ).' <= user_age_max )';
		}

		if( count( $sql_age ) > 0 )
		{
			$this->WHERE_and( implode( ' OR ', $sql_age ) );
		}
	}


	/**
	 * Restrict with user fields
	 *
	 * @param array User fields
	 */
	function where_userfields( $userfields )
	{
		global $DB;

		if( empty( $userfields ) )
		{
			return;
		}

		$criteria_where_clauses = array();
		foreach( $userfields as $field )
		{
			$type = (int)$field['type'];
			$value = trim( strip_tags( $field['value'] ) );
			if( $type > 0 && $value != '' )
			{	// Filter by Specific criteria
				$words = explode( ' ', $value );
				if( count( $words ) > 0 )
				{
					foreach( $words as $word )
					{
						$criteria_where_clauses[] = 'uf_ufdf_ID = "'.$DB->escape($type).'" AND uf_varchar LIKE "%'.$DB->escape($word).'%"';
					}
				}
			}
		}

		if( count( $criteria_where_clauses ) > 0 )
		{	// Some creteria is defined
			$this->FROM_add( ' LEFT JOIN T_users__fields ON uf_user_ID = user_ID' );
			$this->WHERE_and( ' ( ( '.implode( ' ) OR ( ', $criteria_where_clauses ).' ) ) ' );
		}
	}

}

?>