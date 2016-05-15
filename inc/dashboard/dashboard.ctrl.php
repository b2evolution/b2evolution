<?php
/**
 * This file implements the UI controller for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @todo add 5 plugin hooks. Will be widgetized later (same as SkinTag became Widgets)
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// load dashboard functions
load_funcs( 'dashboard/model/_dashboard.funcs.php' );

/**
 * @var User
 */
global $current_User;

global $dispatcher, $allow_evo_stats, $blog;


if( empty( $_GET['blog'] ) )
{ // Use dashboard for selected blog only from GET request
	$blog = 0;
	unset( $Blog );
}

// Site dashboard
$AdminUI->set_path( 'site', 'dashboard' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
$AdminUI->breadcrumbpath_add( T_('Site Dashboard'), $admin_url.'?ctrl=dashboard' );

// Set an url for manual page:
$AdminUI->set_page_manual_link( 'site-dashboard' );

// Load jquery UI to animate background color on change comment status and to transfer a comment to recycle bin
require_js( '#jqueryUI#' );

require_js( 'communication.js' ); // auto requires jQuery
// Load the appropriate blog navigation styles (including calendar, comment forms...):
require_css( $AdminUI->get_template( 'blog_base.css' ) ); // Default styles for the blog navigation
// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
require_js_helper( 'colorbox' );

// Include files to work with charts
require_js( '#easypiechart#' );
require_css( 'jquery/jquery.easy-pie-chart.css' );

// Init JS to quick edit an order of the blogs in the table cell by AJAX
init_field_editor_js( array(
		'field_prefix' => 'order-blog-',
		'action_url' => $admin_url.'?ctrl=dashboard&order_action=update&order_data=',
	) );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// We're on the GLOBAL tab...
$AdminUI->disp_payload_begin();
// Display blog list VIEW:
$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
load_funcs( 'collections/model/_blog_js.funcs.php' );
$AdminUI->disp_payload_end();


/*
	* DashboardGlobalMain to be added here (anyone?)
	*/


/*
 * Administrative tasks
 */

if( $current_User->check_perm( 'options', 'edit' ) )
{ // We have some serious admin privilege:
	/**
	 * @var AbstractSettings
	 */
	global $global_Cache;

	// Begin payload block:
	$AdminUI->disp_payload_begin();

	echo '<div class="row browse"><div class="col-lg-12">';

	// -- System stats -- //

	$chart_data = array();
	// Users
	$chart_data[] = array(
			'title' => T_('Users'),
			'value' => get_table_count( 'T_users' ),
			'type'  => 'number',
		);

	// Blogs
	$chart_data[] = array(
			'title' => T_('Blogs'),
			'value' => get_table_count( 'T_blogs' ),
			'type'  => 'number',
		);
	$post_all_counter = get_table_count( 'T_items__item' );

	// Posts
	$chart_data[] = array(
			'title' => T_('Posts'),
			'value' => $post_all_counter,
			'type'  => 'number',
		);

	// Slugs
	$chart_data[] = array(
			'title' => T_('Slugs'),
			'value' => get_table_count( 'T_slug' ),
			'type'  => 'number',
		);
	// Comments
	$chart_data[] = array(
			'title' => T_('Comments'),
			'value' => get_table_count( 'T_comments' ),
			'type'  => 'number',
		);

	// Files
	$chart_data[] = array(
			'title' => T_('Files'),
			'value' => get_table_count( 'T_files' ),
			'type'  => 'number',
		);

	// Conversations
	$chart_data[] = array(
			'title' => T_('Conversations'),
			'value' => get_table_count( 'T_messaging__thread' ),
			'type'  => 'number',
		);

	// Messages
	$chart_data[] = array(
			'title' => T_('Messages'),
			'value' => get_table_count( 'T_messaging__message' ),
			'type'  => 'number',
		);

	$stat_item_Widget = new Widget( 'block_item' );

	$stat_item_Widget->title = T_('System metrics');
	$stat_item_Widget->disp_template_replaced( 'block_start' );

	display_charts( $chart_data );

	$stat_item_Widget->disp_template_raw( 'block_end' );

	//---- END OF - System stats ----//


	$block_item_Widget = new Widget( 'block_item' );

	$block_item_Widget->title = T_('Updates from b2evolution.net');
	$block_item_Widget->disp_template_replaced( 'block_start' );


	// Note: hopefully, the updates will have been downloaded in the shutdown function of a previous page (including the login screen)
	// However if we have outdated info, we will load updates here.

	// Let's clear any remaining messages that should already have been displayed before...
	$Messages->clear();

	if( b2evonet_get_updates() !== NULL )
	{	// Updates are allowed, display them:

		// Display info & error messages
		$Messages->display();

		$version_status_msg = $global_Cache->getx( 'version_status_msg' );
		if( !empty($version_status_msg) )
		{	// We have managed to get updates (right now or in the past):
			echo '<p>'.$version_status_msg.'</p>';
			$extra_msg = $global_Cache->getx( 'extra_msg' );
			if( !empty($extra_msg) )
			{
				echo '<p>'.$extra_msg.'</p>';
			}
		}

		$block_item_Widget->disp_template_replaced( 'block_end' );

		/*
		* DashboardAdminMain to be added here (anyone?)
		*/
	}
	else
	{
		echo '<p>Updates from b2evolution.net are disabled!</p>';
		echo '<p>You will <b>NOT</b> be alerted if you are running an insecure configuration.</p>';
	}

	// Track just the first login into b2evolution to determine how many people installed manually vs automatic installs:
	if( $current_User->ID == 1 && $UserSettings->get('first_login') == NULL )
	{
		echo 'This is the Admin\'s first ever login.';
		echo '<img src="http://b2evolution.net/htsrv/track.php?key=first-ever-login" alt="" />';
		// OK, done. Never do this again from now on:
		$UserSettings->set('first_login', $localtimenow ); // We might actually display how long the system has been running somewhere
		$UserSettings->dbupdate();
	}


	/*
	 * DashboardAdminSide to be added here (anyone?)
	 */

	echo '</div></div>';

	// End payload block:
	$AdminUI->disp_payload_end();
}

if( ! empty( $chart_data ) )
{ // JavaScript to initialize charts
?>
<script type="text/javascript">
jQuery( 'document' ).ready( function()
{
	var chart_params = {
		barColor: function(percent)
		{
			return get_color_by_percent( {r:97, g:189, b:79}, {r:242, g:214, b:0}, {r:255, g:171, b:74}, percent );
		},
		size: 75,
		trackColor: '#eee',
		scaleColor: false,
		lineCap: 'round',
		lineWidth: 6,
		animate: 700
	}
	jQuery( '.chart .number' ).easyPieChart( chart_params );
} );

function get_color_by_percent( color_from, color_middle, color_to, percent )
{
	function get_color_hex( start_color, end_color )
	{
		num = start_color + Math.round( ( end_color - start_color ) * ( percent / 100 ) );
		num = Math.min( num, 255 ); // not more than 255
		num = Math.max( num, 0 ); // not less than 0
		var str = num.toString( 16 );
		if( str.length < 2 )
		{
			str = "0" + str;
		}
		return str;
	}

	if( percent < 50 )
	{
		color_to = color_middle;
		percent *= 2;
	}
	else
	{
		color_from = color_middle;
		percent = ( percent - 50 ) * 2;
	}

	return "#" +
		get_color_hex( color_from.r, color_to.r ) +
		get_color_hex( color_from.g, color_to.g ) +
		get_color_hex( color_from.b, color_to.b );
}
</script>
<?php
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>