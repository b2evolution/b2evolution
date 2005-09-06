<?php
/**
 * This is the header file for login/registering services
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo $app_shortname.$admin_path_seprator.$page_title ?></title>
	<base href="<?php echo $htsrv_url; ?>" />
	<link href="rsc/css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>


<div class="loginblock">

<div style="float:left"><h1 class="logintitle"><a href="http://b2evolution.net/"><img src="../img/b2evolution_minilogo.png" width="231" height="50" alt="b2evolution" /></a></h1></div>

<?php if( isset($page_icon) ) { ?>
<img src="<?php echo $htsrv_url.'rsc/icons/'.$page_icon ?>" width="24" height="24" style="float:right;" alt="" />
<?php } ?>
<div style="float:right">
<h2 class="logintitle"><?php echo $page_title ?></h2>
</div>

<div class="clear"></div>

<?php
$Messages->display( '', '', true, 'all', array( 'login_error' => array( 'class' => 'log_error' ) ) );
?>