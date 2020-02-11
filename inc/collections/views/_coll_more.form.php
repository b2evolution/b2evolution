<?php
/**
 * This file implements the UI view for the Collection features more properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
global $Settings;

$notifications_mode = $Settings->get( 'outbound_notifications_mode' );

$Form = new Form( NULL, 'coll_more_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'more' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( T_('Tracking').get_manual_link( 'tracking-other' ) );
	$Form->checkbox( 'track_unread_content', $edited_Blog->get_setting( 'track_unread_content' ), T_('Tracking of unread content'), T_('Check this if you want this blog to display special marks in case of unread posts and comments.') );
$Form->end_fieldset();

if( $notifications_mode != 'off' )
{
	$Form->begin_fieldset( T_('Subscriptions').get_manual_link( 'subscriptions-other' ) );
		$subscription_checkboxes = array();
		$allow_subscriptions = $edited_Blog->get_setting( 'allow_subscriptions' );
		$allow_comment_subscriptions = $edited_Blog->get_setting( 'allow_comment_subscriptions' );
		$allow_item_subscriptions = $edited_Blog->get_setting( 'allow_item_subscriptions' );
		$allow_item_mod_subscriptions = $edited_Blog->get_setting( 'allow_item_mod_subscriptions' );
		$advanced_perms = $edited_Blog->get( 'advanced_perms' );
		$subscription_checkboxes[] = array( 'allow_subscriptions', 1, T_('Allow users to subscribe and receive email notifications for each new post.'), $allow_subscriptions );
		$subscription_checkboxes[] = array( 'opt_out_subscription', 1, T_('Consider collection members to be subscribed for each new post unless they specifically opt-out.'), $edited_Blog->get_setting( 'opt_out_subscription' ), $allow_subscriptions == 0 || $advanced_perms == 0 );
		$subscription_checkboxes[] = array( 'allow_comment_subscriptions', 1, T_('Allow users to subscribe and receive email notifications for each new comment.'), $allow_comment_subscriptions );
		$subscription_checkboxes[] = array( 'opt_out_comment_subscription', 1, T_('Consider collection members to be subscribed for each new comment unless they specifically opt-out.'), $edited_Blog->get_setting( 'opt_out_comment_subscription' ), $allow_comment_subscriptions == 0 || $advanced_perms == 0 );
		$subscription_checkboxes[] = array( 'allow_item_mod_subscriptions', 1, T_( 'Allow users to subscribe and receive email notifications when post is modified and user has permission to moderate it.' ), $allow_item_mod_subscriptions );
		$subscription_checkboxes[] = array( 'opt_out_item_mod_subscription', 1, T_('Consider collection members to be subscribed to all post modifications unless they specifically opt-out.'), $edited_Blog->get_setting( 'opt_out_item_mod_subscription' ), $allow_item_mod_subscriptions == 0 || $advanced_perms == 0 );
		$subscription_checkboxes[] = array( 'allow_item_subscriptions', 1, T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ), $allow_item_subscriptions );
		$subscription_checkboxes[] = array( 'opt_out_item_subscription', 1, T_('Consider collection members to be subscribed for comments on a post unless they specifically opt-out.'), $edited_Blog->get_setting( 'opt_out_item_subscription' ), $allow_item_subscriptions == 0 || $advanced_perms == 0 );
		$Form->checklist( $subscription_checkboxes, 'subscriptions', T_('Email subscriptions') );
		// TODO: checkbox 'Enable RSS/Atom feeds'
		// TODO2: which feeds (skins)?
	$Form->end_fieldset();
	?>
	<script>
		jQuery( 'input[name=allow_subscriptions], input[name=allow_comment_subscriptions], input[name=allow_item_subscriptions], input[name=allow_item_mod_subscriptions]' ).on( 'click', function()
		{
			var opt_out_object = jQuery( 'input[name=opt_out_' + jQuery( this ).attr( 'name' ).replace( /^allow_/, '' ).replace( /subscriptions$/, 'subscription' ) + ']' );
			if( <?php echo $advanced_perms ? 'true' : 'false'; ?> && jQuery( this ).is( ':checked' ) )
			{
				opt_out_object.prop( 'disabled', false );
			}
			else
			{
				opt_out_object.prop( 'disabled', true );
				opt_out_object.prop( 'checked', false );
			}
		} );
	</script>
	<?php
}
$Form->begin_fieldset( T_('Sitemaps').get_manual_link( 'sitemaps-other' ) );
	if( $edited_Blog->get_setting( 'allow_access' ) == 'users' )
	{
		echo '<p class="center orange">'.T_('This collection is for logged in users only.').' '.T_('It is recommended to keep sitemaps disabled.').'</p>';
	}
	elseif( $edited_Blog->get_setting( 'allow_access' ) == 'members' )
	{
		echo '<p class="center orange">'.T_('This collection is for members only.').' '.T_('It is recommended to keep sitemaps disabled.').'</p>';
	}
	$Form->checkbox( 'enable_sitemaps', $edited_Blog->get_setting( 'enable_sitemaps' ),
						T_( 'Enable sitemaps' ), T_( 'Check to allow usage of skins with the "sitemap" type.' ) );
$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>