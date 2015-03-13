<?php
/**
 * This page displays an error message if the config is not done yet.
 *
 * VERY IMPORTANT: this file should assume AS LITTLE AS POSSIBLE
 * on what configuration is already done or not
 *
 * Before calling this page, you must set:
 * - $error_message
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $app_name, $app_version, $inc_path, $baseurl;

require_once $inc_path.'/_core/_misc.funcs.php';

if( isset( $_SERVER['SERVER_NAME'] ) )
{ // Set baseurl from current server name
	$temp_baseurl = 'http://'.$_SERVER['SERVER_NAME'];
	if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
	{ // Get also a port number
		$temp_baseurl .= ':'.$_SERVER['SERVER_PORT'];
	}
	if( isset( $_SERVER['SCRIPT_NAME'] ) )
	{ // Get also the subfolders, when script is called e.g. from http://localhost/blogs/b2evolution/
		$temp_baseurl .= preg_replace( '~(.*/)[^/]*$~', '$1', $_SERVER['SCRIPT_NAME'] );
	}
}
else
{ // Use baseurl from config
	$temp_baseurl = $baseurl;
}
?>
<!DOCTYPE  html>
<html lang="en">
	<head>
		<base href="<?php echo $temp_baseurl; ?>">
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo format_to_output( $error_title, 'htmlhead' ); ?></title>
		<!-- Bootstrap -->
		<link href="rsc/css/bootstrap/bootstrap.min.css" rel="stylesheet">
		<link href="rsc/build/b2evo_helper_screens.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="header">
				<nav>
					<ul class="nav nav-pills pull-right">
						<li role="presentation"><a href="readme.html">Read me</a></li>
						<li role="presentation"><a href="install/index.php">Installer</a></li>
						<li role="presentation" class="active"><a href="index.php">Your site</a></li>
					</ul>
				</nav>
				<h3 class="text-muted"><a href="http://b2evolution.net/"><img src="rsc/img/b2evolution8.png" alt="b2evolution CCMS"></a></h3>
			</div>

			<div class="jumbotron">
				<h2 class="h1"><?php echo $error_title; ?></h2>
				<p class="lead">The b2evolution files are present on your server, but it seems the database is not yet set up as expected.</p>
				<p class="lead">For more information, please visit our <a href="http://b2evolution.net/man/getting-started" class="text-nowrap">Getting Stated / Installation Guide</a>.</p>
			</div>

<?php
// Get a markdown content to replace the mask variables
ob_start();
?>
<%=content%>
<?php
$markdown_content = ob_get_clean();

// Print out the markdown content with replacing php vars
echo str_replace(
		array( '$app_name$', '$app_version$', '$error_message$' ),
		array( $app_name,     $app_version,   '<div class="alert alert-danger">'.$error_message.'</div>' ),
		$markdown_content );
?>

			<footer class="footer">
				<p class="pull-right"><a href="https://github.com/b2evolution/b2evolution" class="text-nowrap">Project page on GitHub</a></p>
				<p><a href="http://b2evolution.net/" class="text-nowrap">b2evolution.net</a>
				&bull; <a href="http://b2evolution.net/man/" class="text-nowrap">Online manual</a>
				&bull; <a href="http://forums.b2evolution.net" class="text-nowrap">Get help from the community!</a>
				</p>
			</footer>

		</div> <!-- /container -->
	</body>
</html>
<?php exit( 0 );?>