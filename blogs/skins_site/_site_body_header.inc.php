<?php
/**
 * This is the site header include template.
 *
 * If enabled, thiw will be included at the top of all skins to provide a common identity and site wide navigation.
 *
 * @package site_skins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $Settings;
?>

<div class="sitewide_header">

<?php
if( $Settings->get( 'notification_logo' ) != '' )
{
	$site_title = $Settings->get( 'notification_long_name' ) != '' ? ' title="'.$Settings->get( 'notification_long_name' ).'"' : '';
	$site_name_text = '<img src="'.$Settings->get( 'notification_logo' ).'" alt="'.$Settings->get( 'notification_short_name' ).'"'.$site_title.' />';
	$site_title_class = ' swhead_logo';
}
else
{
	$site_name_text = $Settings->get( 'notification_short_name' );
	$site_title_class = '';
}
?>
<a href="<?php echo $baseurl; ?>" class="swhead_sitename<?php echo $site_title_class; ?>"><?php echo $site_name_text; ?></a>

<?php
	// --------------------------------- START OF BLOG LIST --------------------------------
	// Call widget directly (without container):
	skin_widget( array(
						// CODE for the widget:
						'widget' => 'colls_list_public',
						// Optional display params
						'block_start' => ' ',
						'block_end' => '',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '',
						'item_end' => '',
						'item_selected_start' => '',
						'item_selected_end' => '',
						'link_selected_class' => 'swhead_item swhead_item_selected',
						'link_default_class' => 'swhead_item ',
				) );
	// ---------------------------------- END OF BLOG LIST ---------------------------------

	if( $Settings->get( 'info_blog_ID' ) > 0 )
	{
		// --------------------------------- START OF PAGES LIST --------------------------------
		// Call widget directly (without container):
		skin_widget( array(
						// CODE for the widget:
						'widget' => 'coll_page_list',
						// Optional display params
						'block_start' => '',
						'block_end' => '',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '',
						'item_end' => '',
						'item_selected_start' => '',
						'item_selected_end' => '',
						'link_selected_class' => 'swhead_item swhead_item_selected',
						'link_default_class' => 'swhead_item ',
						'blog_ID' => $Settings->get( 'info_blog_ID' ),
						'item_group_by' => 'none',
						'order_by' => 'order',		// Order (as explicitly specified)


				) );
		// ---------------------------------- END OF PAGES LIST ---------------------------------
	}
?>

</div>
