<?php
/**
 * This file implements the item_comment_notification Widget class.
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
 * @author erhsatingin: Erwin Rommel Satingin
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
class item_comment_notification_Widget extends ComponentWidget
{
	var $icon = 'file-text';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_comment_notification' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-comment-notification-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Comment Notification');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Comment Notification') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item comment notification.');
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
				)
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because item content may includes other items by inline tags like [inline:item-slug]:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;
		//global $cookie_name, $cookie_email, $cookie_url;
		//global $comment_cookies, $comment_allow_msgform, $comment_anon_notify;
		//global $Plugins;
		global $current_User;
		global $Collection, $Blog;

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			return false;
		}

		// Default renderers:
		$comment_renderers = array( 'default' );

		$this->init_display( $params );

		$params = array_merge( array(
			'widget_item_comment_notification_display' => true,
			'widget_item_comment_notification_params'  => array(),
		), $params );

		$widget_params = array_merge( array(
				'notification_before' => '<nav class="evo_post_comment_notification">',
				'notification_text'   => T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ),
				'notification_text2'  => T_( 'You will be notified by email when someone comments here.' ),
				'notification_text3'  => T_( 'Notify me by email when someone comments here.' ),
				'notification_after'  => '</nav>',
			), $params['widget_item_comment_notification_params'] );

		if( $params['widget_item_comment_notification_display'] && is_logged_in() && $Item->can_comment( NULL ) )
		{
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];


			echo $widget_params['notification_before'];

			global $DB;
			global $UserSettings;

			$notification_icon = get_icon( 'notification' );

			$not_subscribed = true;
			$creator_User = $Item->get_creator_User();

			if( $Blog->get_setting( 'allow_comment_subscriptions' ) )
			{
				$sql = 'SELECT count( sub_user_ID )
								FROM (
									SELECT DISTINCT sub_user_ID
									FROM T_subscriptions
									WHERE sub_user_ID = '.$current_User->ID.' AND sub_coll_ID = '.$Blog->ID.' AND sub_comments <> 0

									UNION

									SELECT user_ID
									FROM T_coll_settings AS opt
									INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
									INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
									LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
									LEFT JOIN T_users ON ( user_grp_ID = bloggroup_group_ID )
									LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = user_ID )
									WHERE opt.cset_coll_ID = '.$Blog->ID.'
										AND opt.cset_name = "opt_out_comment_subscription"
										AND opt.cset_value = 1
										AND user_ID = '.$current_User->ID.'
										AND ( sub_comments IS NULL OR sub_comments <> 0 )

									UNION

									SELECT sug_user_ID
									FROM T_coll_settings AS opt
									INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
									INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
									LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
									LEFT JOIN T_users__secondary_user_groups ON ( sug_grp_ID = bloggroup_group_ID )
									LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = sug_user_ID )
									WHERE opt.cset_coll_ID = '.$Blog->ID.'
										AND opt.cset_name = "opt_out_comment_subscription"
										AND opt.cset_value = 1
										AND sug_user_ID = '.$current_User->ID.'
										AND ( sub_comments IS NULL OR sub_comments <> 0 )

									UNION

									SELECT bloguser_user_ID
									FROM T_coll_settings AS opt
									INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
									INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
									LEFT JOIN T_coll_user_perms ON ( bloguser_blog_ID = opt.cset_coll_ID AND bloguser_ismember = 1 )
									LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = bloguser_user_ID )
									WHERE opt.cset_coll_ID = '.$Blog->ID.'
										AND opt.cset_name = "opt_out_comment_subscription"
										AND opt.cset_value = 1
										AND bloguser_user_ID = '.$current_User->ID.'
										AND ( sub_comments IS NULL OR sub_comments <> 0 )
								) AS users';

				if( $DB->get_var( $sql ) > 0 )
				{
					echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'You are receiving notifications when anyone comments on any post.' );
					echo ' <a href="'.$Blog->get('subsurl').'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
					$not_subscribed = false;
				}
			}

			if( $not_subscribed && ( $creator_User->ID == $current_User->ID ) && ( $UserSettings->get( 'notify_published_comments', $current_User->ID ) != 0 ) )
			{
				echo '<p class="text-center">'.$notification_icon.' <span>'.$widget_params['notification_text'];
				echo ' <a href="'.$Blog->get('subsurl').'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
				$not_subscribed = false;
			}
			if( $not_subscribed && $Blog->get_setting( 'allow_item_subscriptions' ) )
			{
				if( get_user_isubscription( $current_User->ID, $Item->ID ) )
				{
					echo '<p class="text-center">'.$notification_icon.' <span>'.$widget_params['notification_text2'];
					echo ' <a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=0&amp;'.url_crumb( 'collections_isubs_update' ).'">'.T_( 'Click here to unsubscribe.' ).'</a></span></p>';
				}
				else
				{
					echo '<p class="text-center"><a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=1&amp;'.url_crumb( 'collections_isubs_update' ).'" class="btn btn-default">'.$notification_icon.' '.$widget_params['notification_text3'].'</a></p>';
				}

				echo $widget_params['notification_after'];
			}

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}
		else
		{
			return false;
		}
	}
}

?>