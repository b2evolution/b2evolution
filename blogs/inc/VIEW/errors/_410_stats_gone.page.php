<?php
/**
 * This page displays an error message when we have detected access to the stats.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */

// Note: if you have a really really good reason to bypass this, uncomment the following line:
// return;

header('HTTP/1.0 410 Gone');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>410 Gone</title>
	</head>
	<body>
		<h1>410 Gone</h1>
		<p><?php echo $app_name ?> does no longer publish referer statistics publicly in order not to attract spam robots.</p>
		<?php
			debug_info();
		?>
	</body>
</html>
<?php
 	exit;		// Note: this is NOT a crash. There should be no dying here!
?>