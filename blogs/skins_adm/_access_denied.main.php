<?php
/**
 * This page displays an error message if the user is denied access to the admin section
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

header_http_response('403 Forbidden');
headers_content_mightcache( 'text/html', 0 );		// Do NOT cache error messages! (Users would not see they fixed them)
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo T_('Access denied') ?></title>
	</head>
<body>
	<div style="background-color:#fee; border: 1px solid red; text-align:center;">
		<h1><?php echo T_('Access denied') ?></h1>
		<p><?php echo T_('Sorry, you have no permission to access this section.') ?></p>
	</div>
	<p style="text-align:center;"><?php
		$secure_httsrv_url = get_secure_htsrv_url();
		echo '<a href="'.$secure_httsrv_url.'login.php?action=logout&amp;redirect_to='.rawurlencode(url_rel_to_same_host($ReqURL, $secure_httsrv_url)).'">'.T_('Log out').'</a>
					&bull;
					<a href="'.$baseurl.'">'.T_('Exit to blogs').'</a>';
	?></p>
</body>
</html>
<?php
	exit(0);
?>