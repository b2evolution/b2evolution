<?php
/**
 * This page displays an error message if the user is denied access to the admin section
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

header_http_response( '403 Forbidden' );
headers_content_mightcache( 'text/html', 0 ); // Do NOT cache error messages! (Users would not see they fixed them)

// Page title:
$page_title = T_('Back-office access denied');

// Header:
require dirname( __FILE__ ).'/login/_html_header.inc.php';

?>
<div class="wrap">
	<div class="panel panel-danger">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo $page_title; ?></h3>
		</div>
		<div class="panel-body">
			<p><?php echo T_('Sorry, you don\'t have permission to access the back-office / admin interface.'); ?></p>
			<p style="margin-bottom:20px">
				<a href="<?php echo $baseurl; ?>" class="btn btn-primary btn-lg">
					<?php echo '&laquo; '.T_('Back to home page'); ?>
				</a>
				<a href="<?php echo get_htsrv_url( true ).'login.php?action=logout&amp;redirect_to='.rawurlencode( url_rel_to_same_host( $ReqURL, get_htsrv_url( true ) ) ); ?>" class="btn btn-default btn-lg pull-right">
					<?php echo T_('Log out').'!'; ?>
				</a>
			</p>
		</div>
	</div>
</div>
<?php

// Footer:
require dirname (__FILE__ ).'/login/_html_footer.inc.php';

// Exit here to don't call any code to initalize controller and etc.
exit(0);
?>