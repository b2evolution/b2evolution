<?php
/**
 * This file implements the item_seen_by Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
class item_seen_by_Widget extends ComponentWidget
{
	var $icon = 'eye';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_seen_by' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'seen-by-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Seen by');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Seen by') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display which Users have seen the current Item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'include' => array(
					'type' => 'checklist',
					'label' => T_('Include'),
					'options' => array(
						array( 'author', T_('Author'), 1 ),
						array( 'commenters', T_('Commenters'), 0 ),
						array( 'assignees', T_('Potential assignees'), 1 ),
						array( 'members', T_('Collection members'), 0 ),
					),
				),
				'limit' => array(
					'label' => T_('Limit to'),
					'type' => 'integer',
					'suffix' => ' '.T_('usernames max'),
					'defaultvalue' => 30,
					'valid_range' => array(
						'min' => 1,
					),
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog, $Item, $current_User, $DB;

		$this->init_display( $params );

		if( empty( $Blog ) || ! $Blog->get_setting( 'track_unread_content' ) )
		{	// Don't display this widget if current collection doesn't track the unread content:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is a collection restriction.' );
			return false;
		}

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item object.' );
			return false;
		}

		if( ! is_logged_in() || ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
		{	// Don't display this widget if user is NOT logged in OR user has no permission to edit this Item:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no user permission.' );
			return false;
		}

		$UserCache = & get_UserCache();
		$users_limit = intval( $this->disp_params['limit'] );
		$user_IDs = array();

		if( ! empty( $this->disp_params['include']['author'] ) )
		{	// Include author:
			$user_IDs[] = $Item->get( 'creator_user_ID' );
		}

		if( ! empty( $this->disp_params['include']['commenters'] ) )
		{	// Include commenters:
			$commenters_SQL = new SQL( '' );
			$commenters_SQL->SELECT( 'DISTINCT comment_author_user_ID' );
			$commenters_SQL->FROM( 'T_comments' );
			$commenters_SQL->WHERE( 'comment_item_ID = '.$DB->quote( $Item->ID ) );
			$commenters_SQL->WHERE_and( 'comment_author_user_ID IS NOT NULL' );
			$commenters_SQL->WHERE_and( statuses_where_clause( get_inskin_statuses( $Blog->ID, 'comment' ), 'comment_', $Blog->ID, 'blog_comment!' ) );
			$user_IDs = array_merge( $user_IDs, $DB->get_col( $commenters_SQL ) );
		}

		if( ! empty( $this->disp_params['include']['assignees'] ) )
		{	// Include potential assignees:
			$UserCache->clear();
			$UserCache->load_blogmembers( $Blog->ID, $users_limit, false/*assignees*/ );
			$user_IDs = array_merge( $user_IDs, array_keys( $UserCache->cache ) );
		}

		if( ! empty( $this->disp_params['include']['members'] ) )
		{	// Include collection members:
			$UserCache->clear();
			$UserCache->load_blogmembers( $Blog->ID, $users_limit, true/*members*/ );
			$user_IDs = array_merge( $user_IDs, array_keys( $UserCache->cache ) );
		}

		if( empty( $user_IDs ) )
		{	// Don't display this widget if no users:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there are no users.' );
			return false;
		}

		$user_IDs = array_unique( $user_IDs );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Get post read statuses for all collection members of the current Item:
		$SQL = new SQL( 'Get post read statuses for all collection members of the Item#'.$Item->ID );
		$SQL->SELECT( 'itud_user_ID, IF( itud_read_item_ts >= '.$DB->quote( $Item->last_touched_ts ).', "read", "updated" ) AS read_post_status' );
		$SQL->FROM( 'T_items__user_data' );
		$SQL->FROM_add( 'INNER JOIN T_users ON itud_user_ID = user_ID' );
		$SQL->WHERE( 'itud_user_ID IN ( '.$DB->quote( $user_IDs ).' )' );
		$SQL->WHERE_and( 'itud_item_ID = '.$DB->quote( $Item->ID ) );
		$SQL->ORDER_BY( 'read_post_status, user_login' );
		$SQL->LIMIT( $users_limit );
		$read_statuses = $DB->get_assoc( $SQL );

		foreach( $user_IDs as $user_ID )
		{
			if( ! isset( $read_statuses[ $user_ID ] ) )
			{	// Append users that don't see the item at the end of list:
				if( count( $read_statuses ) == $users_limit )
				{	// Limit user statuses by max value for this widget:
					break;
				}
				$read_statuses[ $user_ID ] = NULL;
			}
		}

		$seen_post_users = array();
		foreach( $read_statuses as $read_user_ID => $read_status )
		{
			if( ! ( $seen_post_User = & $UserCache->get_by_ID( $read_user_ID, false, false ) ) )
			{	// Skip unexisting user:
				continue;
			}

			if( $read_status == 'read' )
			{	// The item was read by user completely:
				$status_icon = get_icon( 'bullet_green', 'imgtag', array( 'title' => '' ) );
			}
			elseif( $read_status == 'updated' )
			{	// The item was read by user but it has new modifications for reading again:
				$status_icon = get_icon( 'bullet_orange', 'imgtag', array( 'title' => '' ) );
			}
			else
			{	// The item is not read by user:
				$status_icon = get_icon( 'bullet_brown', 'imgtag', array( 'title' => '' ) );
			}

			// Display each user as login with colored status icon:
			$login_users[] = '<span class="nowrap">'.$status_icon.' '.$seen_post_User->get_identity_link( array( 'link_text' => 'auto' ) ).'</span>';
		}

		// Print out all member logins with post read statuses:
		echo '<span class="evo_seen_by">';
		echo sprintf( T_('Seen by: %s'), implode( ', ', $login_users ) );
		echo '</span>';
		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>