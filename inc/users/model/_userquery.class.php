<?php
/**
 * This file implements the UserQuery class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
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

		$this->SELECT( 'user_ID, user_login, user_nickname, user_lastname, user_firstname, user_gender, user_source, user_created_datetime, user_profileupdate_date, user_lastseen_ts, user_level, user_status, user_avatar_file_ID, user_email, user_url, user_age_min, user_age_max, user_pass, user_salt, user_locale, user_unsubscribe_key, user_reg_ctry_ID, user_ctry_ID, user_rgn_ID, user_subrg_ID, user_city_ID, user_grp_ID' );
		$this->SELECT_add( ', IF( user_avatar_file_ID IS NOT NULL, 1, 0 ) as has_picture' );
		$this->FROM( $this->dbtablename );

		if( $params['join_group'] )
		{ // Join Group
			$this->SELECT_add( ', grp_ID, grp_name, grp_level' );
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
		{ // We are handling blogs:
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
	 * Restrict by members
	 *
	 * @param boolean TRUE to select only member of the current Blog
	 */
	function where_members( $members )
	{
		global $DB, $Blog;

		if( empty( $members ) || is_admin_page() || empty( $Blog ) || $Blog->get_setting( 'allow_access' ) != 'members' )
		{ // Don't restrict
			return;
		}

		// Get blog owner
		$blogowner_SQL = new SQL();
		$blogowner_SQL->SELECT( 'user_ID' );
		$blogowner_SQL->FROM( 'T_users' );
		$blogowner_SQL->FROM_add( 'INNER JOIN T_blogs ON blog_owner_user_ID = user_ID' );
		$blogowner_SQL->WHERE( 'blog_ID = '.$DB->quote( $Blog->ID ) );

		// Calculate what users are members of the blog
		$userperms_SQL = new SQL();
		$userperms_SQL->SELECT( 'user_ID' );
		$userperms_SQL->FROM( 'T_users' );
		$userperms_SQL->FROM_add( 'INNER JOIN T_coll_user_perms ON ( bloguser_user_ID = user_ID AND bloguser_ismember = 1 )' );
		$userperms_SQL->WHERE( 'bloguser_blog_ID = '.$DB->quote( $Blog->ID ) );

		// Calculate what user groups are members of the blog
		$usergroups_SQL = new SQL();
		$usergroups_SQL->SELECT( 'user_ID' );
		$usergroups_SQL->FROM( 'T_users' );
		$usergroups_SQL->FROM_add( 'INNER JOIN T_groups ON grp_ID = user_grp_ID' );
		$usergroups_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( bloggroup_group_ID = grp_ID AND bloggroup_ismember = 1 )' );
		$usergroups_SQL->WHERE( 'bloggroup_blog_ID = '.$DB->quote( $Blog->ID ) );

		$members_count_sql = 'SELECT DISTINCT user_ID FROM ( '
			.$blogowner_SQL->get()
			.' UNION '
			.$userperms_SQL->get()
			.' UNION '
			.$usergroups_SQL->get().' ) members';

		$this->WHERE_and( 'user_ID IN ( '.$members_count_sql.' ) ');
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


	/**
	 * Restrict with user group level
	 *
	 * @param integer Minimum group level
	 * @param integer Maximum group level
	 */
	function where_group_level( $group_level_min, $group_level_max )
	{
		global $DB;

		if( $group_level_min < 0 )
		{ // Min group level is 0
			$group_level_min = 0;
		}

		if( $group_level_max > 10 )
		{ // Max group level is 10
			$group_level_max = 10;
		}

		$this->WHERE_and( 'grp_level >= '.$DB->quote( $group_level_min ) );
		$this->WHERE_and( 'grp_level <= '.$DB->quote( $group_level_max ) );
	}


	/**
	 * Select by organization ID
	 *
	 * @param integer Organization ID
	 */
	function where_organization( $org_ID )
	{
		global $DB;

		$org_ID = intval( $org_ID );

		if( empty( $org_ID ) )
		{
			return;
		}

		// Join Organization table
		$this->SELECT_add( ', uorg_org_ID, uorg_accepted' );
		$this->FROM_add( 'INNER JOIN T_users__user_org ON uorg_user_ID = user_ID AND uorg_org_ID = '.$DB->quote( $org_ID ) );
	}

	/**
	 * Restrict with user group level
	 *
	 * @param integer Minimum user level
	 * @param integer Maximum user level
	 */
	function where_level( $user_level_min, $user_level_max )
	{
		global $DB;

		if( $user_level_min < 0 || is_null($user_level_min) )
		{ // Min group level is 0
			$user_level_min = 0;
		}

		if( $user_level_max > 10 || is_null($user_level_max) )
		{ // Max group level is 10
			$user_level_max = 10;
		}

		$this->WHERE_and( 'user_level >= '.$DB->quote( $user_level_min ) );
		$this->WHERE_and( 'user_level <= '.$DB->quote( $user_level_max ) );
	}

}

?>