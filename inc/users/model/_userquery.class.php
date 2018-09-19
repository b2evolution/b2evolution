<?php
/**
 * This file implements the UserQuery class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/db/_filtersql.class.php', 'FilterSQL' );

/**
 * UserQuery: help constructing queries on Users
 * @package evocore
 */
class UserQuery extends FilterSQL
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
	function __construct( $dbtablename = 'T_users', $dbprefix = 'user_', $dbIDname = 'user_ID', $params = array() )
	{
		global $collections_Module;

		$this->dbtablename = $dbtablename;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;

		// Params to build query
		$params = array_merge( array(
				'join_group'       => true,
				'join_sec_groups'  => false,
				'join_session'     => false,
				'join_country'     => true,
				'join_region'      => false,
				'join_subregion'   => false,
				'join_city'        => true,
				'join_colls'       => true,
				'join_lists'       => false,
				'join_user_tags'   => false,
				'grouped'          => false,
			), $params );

		$this->SELECT( 'user_ID, user_login, user_nickname, user_lastname, user_firstname, user_gender, user_source, user_created_datetime, user_profileupdate_date, user_lastseen_ts, user_level, user_status, user_avatar_file_ID, user_email, user_url, user_age_min, user_age_max, user_pass, user_salt, user_pass_driver, user_locale, user_unsubscribe_key, user_reg_ctry_ID, user_ctry_ID, user_rgn_ID, user_subrg_ID, user_city_ID, user_grp_ID' );
		$this->SELECT_add( ', IF( user_avatar_file_ID IS NOT NULL, 1, 0 ) as has_picture' );
		$this->FROM( $this->dbtablename );

		if( $params['join_group'] )
		{ // Join Group
			$this->SELECT_add( ', grp_ID, grp_name, grp_level' );
			//$this->SELECT_add( ', ( SELECT COUNT( sug_count.sug_grp_ID ) FROM T_users__secondary_user_groups AS sug_count WHERE sug_count.sug_user_ID = user_ID ) AS secondary_groups_count' );
			$this->FROM_add( 'LEFT JOIN T_groups ON user_grp_ID = grp_ID' );
		}

		if( $params['join_sec_groups'] )
		{	// Join Secondary groups:
			$this->SELECT_add( ', COUNT( DISTINCT sug_count.sug_grp_ID ) AS secondary_groups_count' );
			$this->FROM_add( 'LEFT JOIN T_users__secondary_user_groups AS sug_count ON sug_count.sug_user_ID = user_ID' );
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

		if( $params['join_region'] )
		{	// Join Region:
			$this->SELECT_add( ', rgn_name' );
			$this->FROM_add( 'LEFT JOIN T_regional__region ON user_rgn_ID = rgn_ID ' );
		}

		if( $params['join_subregion'] )
		{	// Join Sub-region:
			$this->SELECT_add( ', subrg_name' );
			$this->FROM_add( 'LEFT JOIN T_regional__subregion ON user_subrg_ID = subrg_ID ' );
		}

		if( $params['join_city'] )
		{ // Join City
			$this->SELECT_add( ', city_name, city_postcode' );
			$this->FROM_add( 'LEFT JOIN T_regional__city ON user_city_ID = city_ID ' );
		}

		if( $params['join_colls'] )
		{	// Join a count of collections:
			if( isset( $collections_Module ) )
			{	// We are handling blogs:
				$this->SELECT_add( ', COUNT( DISTINCT blog_ID ) AS nb_blogs' );
				$this->FROM_add( 'LEFT JOIN T_blogs on user_ID = blog_owner_user_ID ' );
			}
			else
			{
				$this->SELECT_add( ', 0 AS nb_blogs' );
			}
		}

		if( $params['join_user_tags'] )
		{
			$this->SELECT_add( ', user_tags' );
			$this->FROM_add( 'LEFT JOIN (
						SELECT uutg_user_ID, GROUP_CONCAT( uutg_emtag_ID ) AS user_tags, COUNT(*) AS user_tag_count
						FROM T_users__usertag
						GROUP BY uutg_user_ID
					) AS user_tags ON user_tags.uutg_user_ID = user_ID' );
		}

		if( $params['join_lists'] )
		{ // subscribed_list contains comma-separated list of newsletter IDs, "negative" IDs are unsubscribed to newsletter lists
			$this->SELECT_add( ', subscribed_list' );
			$this->FROM_add( 'LEFT JOIN (
						SELECT enls_user_ID, GROUP_CONCAT( IF( enls_subscribed = 1, enls_enlt_ID, CONCAT( "-", enls_enlt_ID ) ) ) AS subscribed_list, COUNT(*) AS subscribed_list_count
						FROM T_email__newsletter_subscription
						GROUP BY enls_user_ID
					) AS subscribed_lists on subscribed_lists.enls_user_ID = user_ID' );
		}

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
	 * Restrict by user IDs
	 *
	 * @param array User IDs
	 */
	function where_user_IDs( $user_IDs )
	{
		global $DB;

		if( empty( $user_IDs ) )
		{	// Don't restrict:
			return;
		}

		$this->WHERE_and( 'user_ID IN ( '.$DB->quote( $user_IDs ).' ) ');
	}


	/**
	 * Restrict by members
	 *
	 * @param boolean TRUE to select only member of the current Blog
	 */
	function where_members( $members )
	{
		global $DB, $Collection, $Blog;

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
		$usergroups_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( bloggroup_ismember = 1
			AND ( bloggroup_group_ID = grp_ID
			      OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = user_ID ) ) )' );
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
	function where_keywords( $keywords, $search_kw_combine = 'AND' )
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
			$this->WHERE_and( implode( ' '.$search_kw_combine.' ', $search ) );
		}
	}


	/**
	 * Restrict with gender
	 *
	 * @param string Gender ( M, F, O, MF, MO, FO, MFO )
	 */
	function where_gender( $gender )
	{
		if( empty( $gender ) )
		{
			return;
		}

		switch( $gender )
		{
			case 'MF':
			case 'MO':
			case 'FO':
			case 'MFO':
				$this->add_filter_rule( 'gender', str_split( $gender ), NULL, 'OR' );
				break;
			case 'M':
			case 'F':
			case 'O':
				$this->add_filter_rule( 'gender', $gender );
				break;
		}
	}


	/**
	 * Restrict to user status, currently activated also means auto and manually activated users
	 *
	 * @param string user status ( 'activated', 'deactivated', 'new', 'emailchanged', 'failedactivation', 'closed' )
	 * @param boolean set true to include users only with the given status, or set false to exclude users with the given status
	 * @param boolean set true to make exact comparing with selected status
	 */
	function where_status( $status, $include = true, $exactly = false )
	{
		if( empty( $status ) )
		{
			return;
		}

		if( $status == 'activated' && !$exactly )
		{	// Activated, Manually activated, Autoactivated users:
			$this->add_filter_rule( 'status', array( 'activated', 'autoactivated', 'manualactivated' ), '=', 'OR' );
		}
		else
		{ // Other status check
			// init compare, which depends if we want to include or exclude users with the given status
			$this->add_filter_rule( 'status', $status, $include ? '=' : '<>' );
		}
	}


	/**
	 * Restrict to user registration date
	 *
	 * @param date Registration from date
	 * @param date Registration to date
	 */
	function where_registered_date( $min_date = NULL, $max_date = NULL )
	{
		if( ! empty( $min_date ) && ! empty( $max_date ) )
		{
			$this->add_filter_rule( 'regdate', array( $min_date, $max_date ), 'between', NULL, 'date' );
		}
		elseif( ! empty( $min_date ) )
		{
			$this->add_filter_rule( 'regdate', $min_date, '>=', NULL, 'date' );
		}
		elseif( ! empty( $max_date ) )
		{
			$this->add_filter_rule( 'regdate', $max_date, '<=', NULL, 'date' );
		}
	}


	/**
	 * Restrict to reported users
	 *
	 * @param boolean is reported
	 */
	function where_reported( $reported )
	{
		if( ! empty( $reported ) )
		{
			$this->add_filter_rule( 'report_count', 1, '>=' );
		}
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
		{	// Restrict to users with custom notification sender email address:
			$this->add_filter_rule( 'custom_sender_email', 'yes' );
		}

		if( $custom_sender_name )
		{	// Restrict to users with custom notification sender name:
			$this->add_filter_rule( 'custom_sender_name', 'yes' );
		}
	}


	/**
	 * Restrict to users with tag
	 *
	 * @param string User should have all of these tags
	 * @param string User should not have any of these tags
	 */
	function where_tag( $user_tag = NULL, $not_user_tag = NULL)
	{
		if( trim( $user_tag ) !== '' )
		{
			$this->add_filter_rule( 'tags', $user_tag, 'user_tagged' );
		}
		if( trim( $not_user_tag ) !== '' )
		{
			$this->add_filter_rule( 'tags', $not_user_tag, 'user_not_tagged' );
		}
	}


	/**
	 * Restrict with primary user group
	 *
	 * @param integer Primary user group ID
	 */
	function where_group( $group_ID )
	{
		$group_ID = (int)$group_ID;

		if( $group_ID < 1 )
		{ // Group Id may be '0' - to show all groups, '-1' - to show all groups as ungrouped list
			return;
		}

		$this->add_filter_rule( 'group', $group_ID );
	}


	/**
	 * Restrict with secondary user group
	 *
	 * @param integer Secondary user group ID
	 */
	function where_secondary_group( $secondary_group_ID )
	{
		$secondary_group_ID = intval( $secondary_group_ID );

		if( $secondary_group_ID < 1 )
		{	// Group ID may be '0' - to show all groups
			return;
		}

		$this->add_filter_rule( 'group2', $secondary_group_ID );
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

		$this->add_filter_rule( 'org', $org_ID );
	}


	/**
	 * Select by newsletter ID
	 *
	 * @param integer Newsletter ID
	 * @param boolean|NULL TRUE - only users with active subscription, FALSE - only unsubscribed users, NULL - both
	 */
	function where_newsletter( $newsletter_ID, $is_subscribed = true )
	{
		$newsletter_ID = intval( $newsletter_ID );

		if( empty( $newsletter_ID ) )
		{
			return;
		}

		$this->add_filter_rule( 'newsletter', array( $newsletter_ID, $is_subscribed ) );
	}


	/**
	 * Select by not subscribed newsletter ID
	 *
	 * @param integer Newsletter ID
	 */
	function where_not_newsletter( $not_newsletter_ID )
	{
		$not_newsletter_ID = intval( $not_newsletter_ID );

		if( empty( $not_newsletter_ID ) )
		{
			return;
		}

		$this->add_filter_rule( 'newsletter', $not_newsletter_ID, '!=' );
	}


	/**
	 * Select by Email Campaign ID
	 *
	 * @param integer Email Campaign ID
	 * @param string Recipient type of email campaign: 'filter', 'receive', 'wait'
	 */
	function where_email_campaign( $ecmp_ID, $recipient_type = '', $recipient_action = '' )
	{
		global $DB;

		$ecmp_ID = intval( $ecmp_ID );

		if( empty( $ecmp_ID ) )
		{
			return;
		}

		$this->SELECT_add( ', csnd_status, csnd_emlog_ID' );
		$this->FROM_add( 'INNER JOIN T_email__campaign_send ON csnd_user_ID = user_ID AND csnd_camp_ID = '.$DB->quote( $ecmp_ID ) );

		// Get email log date and time:
		$this->SELECT_add( ', csnd_last_sent_ts, enls_user_ID, csnd_last_open_ts, csnd_last_click_ts, csnd_like, csnd_cta1, csnd_cta2, csnd_cta3' );

		// Get subscription status:
		$this->SELECT_add( ', enls_subscribed' );
		$this->FROM_add( 'LEFT JOIN T_email__campaign ON ecmp_ID = csnd_camp_ID' );
		$this->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_enlt_ID = ecmp_enlt_ID AND enls_user_ID = user_ID AND enls_subscribed = 1' );

		switch( $recipient_type )
		{
			case 'ready_to_send':
				// Get recipients which have already received this newsletter:
				$this->WHERE_and( 'csnd_status IN ( "ready_to_send", "ready_to_resend" )' );
				break;

			case 'sent':
			case 'send_error':
			case 'skipped':
				// Get recipients which have already received this newsletter:
				$this->WHERE_and( 'csnd_status = "'.$recipient_type.'"' );
				break;
		}

		switch( $recipient_action )
		{
			case 'img_loaded':
				$this->WHERE_and( 'csnd_last_open_ts IS NOT NULL' );
				break;

			case 'link_clicked':
				$this->WHERE_and( 'csnd_last_click_ts IS NOT NULL' );
				break;

			case 'cta1':
				$this->WHERE_and( 'csnd_cta1 = 1' );
				break;

			case 'cta2':
				$this->WHERE_and( 'csnd_cta2 = 1' );
				break;

			case 'cta3':
				$this->WHERE_and( 'csnd_cta3 = 1' );
				break;

			case 'liked':
				$this->WHERE_and( 'csnd_like = 1' );
				break;

			case 'disliked':
				$this->WHERE_and( 'csnd_like = -1' );
				break;

			case 'clicked_unsubscribe':
				$this->WHERE_and( 'csnd_clicked_unsubscribe = 1' );
				break;
		}
	}


	/**
	 * Select by viewed user
	 *
	 * @param integer User ID
	 */
	function where_viewed_user( $user_ID )
	{
		global $DB;
		$this->SELECT_add( ', upv_visited_user_ID, upv_visitor_user_ID, upv_last_visit_ts' );
		$this->FROM_add( 'RIGHT JOIN T_users__profile_visits ON upv_visitor_user_ID = user_ID AND upv_visited_user_ID = '.$DB->quote( $user_ID ) );
	}


	/**
	 * Select by registration IP range
	 *
	 * @param string Min IP address
	 * @param string Max IP address
	 */
	function where_reg_ip( $reg_ip_min, $reg_ip_max )
	{
		global $DB;

		$reg_ip_min = ip2int( $reg_ip_min );
		$reg_ip_max = ip2int( $reg_ip_max );

		if( empty( $reg_ip_min ) && empty( $reg_ip_max ) )
		{	// No IP filters:
			return;
		}

		// Join User settings table:
		$this->FROM_add( 'INNER JOIN T_users__usersettings
			 ON uset_user_ID = user_ID
			AND uset_name = "created_fromIPv4"' );

		if( ! empty( $reg_ip_min ) )
		{	// Restrict with MIN registration IP address:
			$this->WHERE_and( 'uset_value >= '.$DB->quote( $reg_ip_min ) );
		}

		if( ! empty( $reg_ip_max ) )
		{	// Restrict with MAX registration IP address:
			$this->WHERE_and( 'uset_value <= '.$DB->quote( $reg_ip_max ) );
		}
	}


	/**
	 * Restrict with user group level
	 *
	 * @param integer Minimum user level
	 * @param integer Maximum user level
	 */
	function where_level( $user_level_min, $user_level_max )
	{
		if( $user_level_min < 0 || is_null($user_level_min) )
		{ // Min group level is 0
			$user_level_min = 0;
		}

		if( $user_level_max > 10 || is_null($user_level_max) )
		{ // Max group level is 10
			$user_level_max = 10;
		}

		if( $user_level_min > 0 || $user_level_max < 10 )
		{	// Filter only with actual values:
			$this->add_filter_rule( 'level', array( $user_level_min, $user_level_max ), 'between' );
		}
	}


	/**
	 * Restrict to users with duplicate emails
	 */
	function where_duplicate_email()
	{
		$this->SELECT_add( ', email_user_count' );
		$this->FROM_add( 'LEFT JOIN ( SELECT user_email AS dup_email, COUNT(*) AS email_user_count FROM T_users GROUP BY user_email ) AS dup_emails ON dup_emails.dup_email = T_users.user_email' );
		$this->WHERE_and( 'email_user_count > 1' );
	}


	/**
	 * Restrict with user gender
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_gender( $value, $operator )
	{
		if( in_array( $value, array( 'M', 'F', 'O' ) ) )
		{
			return $this->get_where_condition( 'user_gender', $value, $operator );
		}
	}


	/**
	 * Restrict with user level
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_level( $value, $operator )
	{
		return $this->get_where_condition( 'user_level', $value, $operator );
	}


	/**
	 * Restrict with user organization
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_org( $value, $operator )
	{
		if( $operator == 'equal' || $operator == 'not_equal' )
		{	// If operator is allowed for this filter:
			global $DB;
			$this->SELECT_add( ', uorg_org_ID, uorg_accepted, uorg_role, uorg_priority' );
			$this->FROM_add( 'LEFT JOIN T_users__user_org ON uorg_user_ID = user_ID' );
			return '( SELECT uorg_org_ID FROM T_users__user_org WHERE uorg_user_ID = user_ID AND uorg_org_ID = '.$DB->quote( $value ).' ) '.( $operator == 'equal' ? 'IS NOT NULL' : 'IS NULL' );
		}
	}


	/**
	 * Restrict if user uses custom sender email address
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_custom_sender_email( $value, $operator )
	{
		if( $value == 'yes' || $value == 'no' )
		{	// If value is allowed for this filter:
			global $Settings, $DB;
			$this->FROM_add( 'LEFT JOIN T_users__usersettings as custom_sender_email ON custom_sender_email.uset_user_ID = user_ID AND custom_sender_email.uset_name = "notification_sender_email"' );
			$operator1 = ( $value == 'yes' ? 'IS NOT NULL AND' : 'IS NULL OR' );
			$operator2 = ( $value == 'yes' ? '<>' : '=' );
			return 'custom_sender_email.uset_value '.$operator1.' custom_sender_email.uset_value '.$operator2.' '.$DB->quote( $Settings->get( 'notification_sender_email' ) );
		}
	}


	/**
	 * Restrict if user uses custom sender name
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_custom_sender_name( $value, $operator )
	{
		if( $value == 'yes' || $value == 'no' )
		{	// If value is allowed for this filter:
			global $Settings, $DB;
			$this->FROM_add( 'LEFT JOIN T_users__usersettings as custom_sender_name ON custom_sender_name.uset_user_ID = user_ID AND custom_sender_name.uset_name = "notification_sender_name"' );
			$operator1 = ( $value == 'yes' ? 'IS NOT NULL AND' : 'IS NULL OR' );
			$operator2 = ( $value == 'yes' ? '<>' : '=' );
			return 'custom_sender_name.uset_value '.$operator1.' custom_sender_name.uset_value '.$operator2.' '.$DB->quote( $Settings->get( 'notification_sender_name' ) );
		}
	}


	/**
	 * Restrict with user fields (Specific criteria)
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_criteria( $value, $operator )
	{
		if( ! preg_match( '#^(\d+):(contains|not_contains):(.+)$#', $value, $m ) )
		{	// Skip wrong value:
			return;
		}

		$user_field_def_ID = intval( $m[1] );
		$user_field_operator = trim( strip_tags( $m[2] ) );
		$user_field_value = trim( strip_tags( $m[3] ) );
		if( $user_field_def_ID <= 0 || $user_field_value == '' || $user_field_operator == '' )
		{	// Skip wrong value:
			return;
		}

		global $DB;

		switch( $user_field_operator )
		{
			case 'contains':
				$word_operator = 'LIKE';
				$field_condition_start = 'uf_ufdf_ID = '.$DB->quote( $user_field_def_ID );
				$field_condition_end = '';
				break;

			case 'not_contains':
				$word_operator = 'NOT LIKE';
				$field_condition_start = '( uf_ufdf_ID = '.$DB->quote( $user_field_def_ID );
				// This condition selects users which have no the requested field in DB, i.e. thier requested field has no the requested value:
				$field_condition_end = ' ) OR ( SELECT COUNT( uf_ID ) FROM yb_users__fields WHERE uf_user_ID = user_ID AND uf_ufdf_ID = '.$DB->quote( $user_field_def_ID ).' ) = 0';
				break;

			default:
				debug_die( 'Unknown operator "'.$user_field_operator.'" for user searching by specific criteria' );
		}

		$word_sql_conditions = array();
		$words = explode( ' ', $user_field_value );
		foreach( $words as $word )
		{	// Find each word separately:
			$word_sql_conditions[] = 'uf_varchar '.$word_operator.' '.$DB->quote( '%'.$word.'%' );
		}

		// Join table for columns uf_ufdf_ID and uf_varchar:
		$this->FROM_add( 'LEFT JOIN T_users__fields ON uf_user_ID = user_ID' );

		// Build SQL condition for specific criteria:
		$criteria_sql_condition = '( '.$field_condition_start.' AND ';
		if( count( $word_sql_conditions ) > 1 )
		{
			$criteria_sql_condition .= '( '.implode( ' OR ', $word_sql_conditions ).' )';
		}
		else
		{
			$criteria_sql_condition .= $word_sql_conditions[0];
		}
		$criteria_sql_condition .= $field_condition_end.' )';

		return $criteria_sql_condition;
	}


	/**
	 * Restrict with user last seen date
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_lastseen( $value, $operator )
	{
		if( ! empty( $value ) )
		{
			return $this->get_where_condition( 'DATE( user_lastseen_ts )', $value, $operator );
		}
	}


	/**
	 * Restrict with user last seen date
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_source( $value, $operator )
	{
		return $this->get_where_condition( 'user_source', $value, $operator );
	}


	/**
	 * Restrict with user report count
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_report_count( $value, $operator )
	{
		$value = intval( $value );

		if( $value > 0 && $operator == 'greater_or_equal' )
		{
			$this->SELECT_add( ', user_rep' );
			$this->FROM_add( 'LEFT JOIN ( SELECT urep_target_user_ID, COUNT( DISTINCT urep_reporter_ID ) AS user_rep FROM T_users__reports GROUP BY urep_target_user_ID ) AS urep ON urep.urep_target_user_ID = user_ID' );

			return $this->get_where_condition( 'user_rep', $value, $operator );
		}
	}


	/**
	 * Restrict with user primary group
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_group( $value, $operator )
	{
		$value = intval( $value );

		if( $value > 0 && ( $operator == 'equal' || $operator == 'not_equal' ) )
		{
			return $this->get_where_condition( 'user_grp_ID', $value, $operator );
		}
	}


	/**
	 * Restrict with user secondary group
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_group2( $value, $operator )
	{
		$value = intval( $value );

		if( $value > 0 && ( $operator == 'equal' || $operator == 'not_equal' ) )
		{	// If value and operator are allowed for this filter:
			global $DB;
			$this->FROM_add( 'LEFT JOIN T_users__secondary_user_groups AS sug_filter ON sug_filter.sug_user_ID = user_ID' );
			return '( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = user_ID AND sug_grp_ID = '.$DB->quote( $value ).' ) '.( $operator == 'equal' ? 'IS NOT NULL' : 'IS NULL' );
		}
	}


	/**
	 * Restrict with user account status
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_status( $value, $operator )
	{
		return $this->get_where_condition( 'user_status', $value, $operator );
	}


	/**
	 * Restrict with user registration date
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_regdate( $value, $operator )
	{
		if( ! empty( $value ) )
		{
			return $this->get_where_condition( 'DATE( user_created_datetime )', $value, $operator );
		}
	}


	/**
	 * Restrict with user newsletter
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_newsletter( $value, $operator )
	{
		if( is_array( $value ) && count( $value ) == 2 )
		{	// Special case for additional param to also get user which will be unsubscribed:
			$is_subscribed = $value[1];
			$value = intval( $value[0] );
		}
		else
		{	// Get only subscribed users:
			$is_subscribed = 1;
			$value = intval( $value );
		}

		if( $value > 0 && ( $operator == 'equal' || $operator == 'not_equal' ) )
		{	// If value and operator are allowed for this filter:
			$restrict_is_subscribed = '';
			if( $is_subscribed !== NULL )
			{	// Get only subscribed or unsubscribed users:
				$restrict_is_subscribed = ' AND enls_subscribed = '.( $is_subscribed ? '1' : '0' );
			}

			global $DB;
			$this->SELECT_add( ', enls_last_sent_manual_ts, enls_last_open_ts, enls_last_click_ts, enls_send_count, enls_subscribed, enls_subscribed_ts, enls_unsubscribed_ts, enls_enlt_ID' );
			$this->FROM_add( 'INNER JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID'.( $is_subscribed === NULL ? ' AND enls_enlt_ID = '.$DB->quote( $value ) : '' ) );

			return '( SELECT enls_enlt_ID FROM T_email__newsletter_subscription WHERE enls_user_ID = user_ID AND enls_enlt_ID = '.$DB->quote( $value ).$restrict_is_subscribed.' ) '.( $operator == 'equal' ? 'IS NOT NULL' : 'IS NULL' );
		}
	}


	/**
	 * Restrict with user tags
	 *
	 * @param string Value
	 * @param string Operator
	 */
	function filter_field_tags( $value, $operator )
	{
		$tags = array_unique( array_map( 'trim', explode( ',', $value ) ) );

		foreach( $tags as $t => $tag )
		{
			if( $tag === '' )
			{	// Remove empty tags:
				unset( $tags[ $t ] );
			}
		}

		if( count( $tags ) > 0 && ( $operator == 'user_tagged' || $operator == 'user_not_tagged' ) )
		{	// If value and operator are allowed for this filter:
			global $DB;
			return '( SELECT COUNT( uutg_emtag_ID )
				 FROM T_users__tag
				 INNER JOIN T_users__usertag ON uutg_emtag_ID = utag_ID
				WHERE uutg_user_ID = user_ID
				  AND utag_name IN ( '.$DB->quote( $tags ).' )
				) '.( $operator == 'user_tagged' ? '= '.count( $tags ) : '= 0' );
		}
	}
}

?>