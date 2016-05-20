<?php
/**
 * This file implements the Member Count Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_member_count_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_member_count' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'member-count-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Member count');
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a count of the collection members.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;

		if( empty( $Blog ) || $Blog->get_setting( 'allow_access' ) != 'members' )
		{ // Use this widget only when blog is allowed only for members
			return;
		}

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'before'    => ' ',
				'after'     => ' ',
			), $this->disp_params );

		echo $this->disp_params['before'];

		echo '<a href="'.url_add_param( $Blog->get( 'usersurl' ), 'filter=new&amp;membersonly=1' ).'">'.sprintf( T_('%d members'), $this->get_members_count() ).'</a>';

		echo $this->disp_params['after'];

		return true;
	}


	/**
	 * Get a count of blog members
	 *
	 * @return integer Members count
	 */
	function get_members_count()
	{
		global $Blog, $DB;

		// Get blog owner
		$blogowner_SQL = new SQL();
		$blogowner_SQL->SELECT( 'user_ID, user_status' );
		$blogowner_SQL->FROM( 'T_users' );
		$blogowner_SQL->FROM_add( 'INNER JOIN T_blogs ON blog_owner_user_ID = user_ID' );
		$blogowner_SQL->WHERE( 'blog_ID = '.$DB->quote( $Blog->ID ) );

		// Calculate what users are members of the blog
		$userperms_SQL = new SQL();
		$userperms_SQL->SELECT( 'user_ID, user_status' );
		$userperms_SQL->FROM( 'T_users' );
		$userperms_SQL->FROM_add( 'INNER JOIN T_coll_user_perms ON ( bloguser_user_ID = user_ID AND bloguser_ismember = 1 )' );
		$userperms_SQL->WHERE( 'bloguser_blog_ID = '.$DB->quote( $Blog->ID ) );

		// Calculate what user groups are members of the blog
		$usergroups_SQL = new SQL();
		$usergroups_SQL->SELECT( 'user_ID, user_status' );
		$usergroups_SQL->FROM( 'T_users' );
		$usergroups_SQL->FROM_add( 'INNER JOIN T_groups ON grp_ID = user_grp_ID' );
		$usergroups_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( bloggroup_group_ID = grp_ID AND bloggroup_ismember = 1 )' );
		$usergroups_SQL->WHERE( 'bloggroup_blog_ID = '.$DB->quote( $Blog->ID ) );

		$members_count_sql = 'SELECT COUNT( user_ID ) AS coll_member_count FROM ( '
			.$blogowner_SQL->get()
			.' UNION '
			.$userperms_SQL->get()
			.' UNION '
			.$usergroups_SQL->get().' ) AS members '
			.'WHERE members.user_status != "closed"';

		return intval( $DB->get_var( $members_count_sql ) );
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => 'any',     // Have the settings of ANY blog changed ? (ex: new skin here, new name on another)
			);
	}
}