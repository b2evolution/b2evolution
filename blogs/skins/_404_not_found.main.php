<?php
/**
 * This page displays an error message when we cannot resolve the extra path.
 *
 * This happens when you request an invalid tracking code on track.php for example
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


header('HTTP/1.0 404 Not Found');
header('Content-Type: text/html; charset=iso-8859-1'); // no translation
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>404 Not Found</title>
	</head>
	<body>
		<h1>404 Not Found</h1>
		<p>The page you requested doesn't seem to exist on <a href="<?php echo $baseurl ?>">this system</a>.</p>
	</body>
</html>
<?php
 	exit(0);
?>