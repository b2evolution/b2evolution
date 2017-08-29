<?php
/**
 * This file implements the coll_subscription_Widget class.
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
class coll_subscription_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_subscription' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'collection-subscription-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Subscribe to Updates');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Subscribe to updates on the current collection.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Collection, $Blog, $admin_url;

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Subscribe to Updates'),
				),
				'note' => array(
					'label' => T_('Note'),
					'info' => sprintf( T_('You can configure which subscriptions are possible in Features > Other > <a %s>Subscriptions</a>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=more&amp;blog='.$Blog->ID.'"' ),
					'type' => 'info',
				),
				'display_mode' => array(
					'label' => T_('Display mode'),
					'note' => T_('Select how users can subscribe to a collection' ),
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
						array( 'checkboxes', T_('show options using checkboxes') ),
						array( 'text', T_('show options using text') )
					),
					'defaultvalue' => 'text',
				)
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * Should be overriden by core widgets
	 *
	 * @todo fp> handle custom params for each widget
	 *
	 * @param array MUST contain at least the basic display params
	 * @return bool true if the widget displayed something (other than a debug message)
	 */
	function display( $params )
	{
		global $Collection, $Blog;
		global $Plugins;
		global $rsc_url;
		global $current_User;

		$allow_subscriptions = $Blog->get_setting( 'allow_subscriptions'	);
		$allow_comment_subscriptions = $Blog->get_setting( 'allow_comment_subscriptions' );

		if( $current_User && ( $allow_subscriptions || $allow_comment_subscriptions ) )
		{
			$current_htsrv_url = get_htsrv_url();
			$subscriptions = get_user_subscription( $current_User->ID, $Blog->ID );
			if( $subscriptions )
			{
				$items_subscribed = $subscriptions->sub_items;
				$comments_subscribed = $subscriptions->sub_comments;
			}
			else
			{
				$items_subscribed = 0;
				$comments_subscribed = 0;
			}

			// prepare for display:
			$this->init_display( $params );

			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];

			switch( $this->disp_params['display_mode'] )
			{
				case 'checkboxes':
					if( $items_subscribed || $comments_subscribed )
					{
						$button_label = T_('Update');
					}
					else
					{
						$button_label = T_('Subscribe');
					}

					$Form = new Form( $current_htsrv_url.'action.php', 'collection_subscription' );
					$Form->begin_form( 'fform' );
					$Form->hidden( 'action', 'subs_update' );
					$Form->hidden( 'subscribe_blog', $Blog->ID );
					$Form->hidden( 'mname', 'collections' );
					$Form->hidden( 'sub_items', NULL );
					$Form->hidden( 'sub_comments', NULL );
					$Form->add_crumb( 'collections_subs_update' );

					$options = array();
					if( $allow_subscriptions )
					{
						$options[] = array( 'cb_sub_items', 1, T_('All posts'), $items_subscribed, false );
					}
					if( $allow_comment_subscriptions )
					{
						$options[] = array( 'cb_sub_comments', 1, T_('All comments'), $comments_subscribed, false );
					}

					$Form->checklist( $options, 'subscriptions', NULL );
					$Form->end_form( array( array( 'submit', 'submit', $button_label, 'btn btn-primary' ) ) );
					?>
					<script type="text/javascript">
					var subItems = <?php echo $items_subscribed; ?>;
					var subComments = <?php echo $comments_subscribed; ?>;
					var cbItems = jQuery('input[name=cb_sub_items]');
					var cbComments = jQuery('input[name=cb_sub_comments]');

					cbItems.change( function()
							{
								var v = cbItems.prop( 'checked' ) ? 1 : 0;
								jQuery('input[name=sub_items]').val( subItems == v ? null : v );
							});

					cbComments.change( function()
							{
								var v = cbComments.prop( 'checked' ) ? 1 : 0;
								jQuery('input[name=sub_comments]').val( subComments == v ? null : v );
							});
					</script>
					<?php
					break;

				case 'text':
				default:
					if( $allow_subscriptions )
					{
						if( $items_subscribed )
						{
							echo '<p>You are subscribed to get automatic email notifications whenever there is a new <strong>post</strong>
									in this collection. <a href="'.$current_htsrv_url.'action.php?mname=collections&action=subs_update&subscribe_blog='.$Blog->ID.
									'&sub_items=0&'.url_crumb( 'collections_subs_update' ).'">Unsubscribe</a>.</p>';
						}
						else
						{
							echo '<p><a href="'.$current_htsrv_url.'action.php?mname=collections&action=subs_update&subscribe_blog='.$Blog->ID.
									'&sub_items=1&'.url_crumb( 'collections_subs_update' ).'">Click here</a> to get automatic email notifications whenever there is a new <strong>post</strong>
									in this collection.</p>';
						}
					}
					if( $allow_comment_subscriptions )
					{
						if( $comments_subscribed )
						{
							echo '<p>You are '.( $items_subscribed ? '<strong>also</strong>' : '' ).' subscribed to get automatic email notifications whenever there is a new
									<strong>comment</strong> in this collection. <a href="'.$current_htsrv_url.'action.php?mname=collections&action=subs_update&subscribe_blog='.$Blog->ID.
									'&sub_comments=0&'.url_crumb( 'collections_subs_update' ).'">Unsubscribe</a>.</p>';
						}
						else
						{
							echo '<p><a href="'.$current_htsrv_url.'action.php?mname=collections&action=subs_update&subscribe_blog='.$Blog->ID.
									'&sub_comments=1&'.url_crumb( 'collections_subs_update' ).'">Click here</a> to '.( $items_subscribed ? '<strong>also</strong>' : '' ).
									' get automatic email notifications whenever there is a new <strong>comment</strong> in this collection.</p>';
						}
					}
					break;

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