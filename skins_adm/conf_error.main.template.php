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
require_once $inc_path.'/locales/_locale.funcs.php';

$locale_lang = locale_lang( false );
?>
<!DOCTYPE html>
<html lang="<?php echo ( empty( $locale_lang ) ? 'en' : $locale_lang ); ?>">
	<head>
		<base href="<?php echo get_script_baseurl(); ?>">
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
						<li role="presentation"><a href="readme.html"><?php echo T_('Read me'); ?></a></li>
						<li role="presentation"><a href="install/index.php"><?php echo T_('Installer'); ?></a></li>
						<li role="presentation" class="active"><a href="index.php"><?php echo T_('Your site'); ?></a></li>
					</ul>
				</nav>
				<h3 class="text-muted"><a href="http://b2evolution.net/"><img src="rsc/img/b2evolution8.png" alt="b2evolution CCMS"></a></h3>
			</div>

			<div class="jumbotron">
				<h2 class="h1_small"><?php echo $error_title; ?></h2>
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
				<p class="pull-right"><a href="https://github.com/b2evolution/b2evolution" class="text-nowrap"><?php echo T_('GitHub page'); ?></a></p>
				<p><a href="http://b2evolution.net/" class="text-nowrap">b2evolution.net</a>
				&bull; <a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" class="text-nowrap"><?php echo T_('Find a host'); ?></a>
				&bull; <a href="http://b2evolution.net/man/" class="text-nowrap"><?php echo T_('Online manual'); ?></a>
				&bull; <a href="http://forums.b2evolution.net" class="text-nowrap"><?php echo T_('Help forums'); ?></a>
				</p>
			</footer>

		</div><!-- /container -->
	</body>
</html>
<?php exit( 0 );?>