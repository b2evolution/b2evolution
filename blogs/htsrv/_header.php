<?php
/**
 * This is the header file for login/registering services
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo $page_title ?></title>
	<base href="<?php echo $htsrv_url; ?>">
	<link href="<?php echo $admin_url ?>variation.css" rel="stylesheet" type="text/css" title="Variation" />
	<link href="<?php echo $admin_url ?>desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
	<link href="<?php echo $admin_url ?>legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
	<?php if( is_file( dirname(__FILE__).'/'.$htsrv_dirout.$admin_subdir.'custom.css' ) ) { ?>
	<link href="<?php echo $admin_url ?>custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
	<?php } ?>
	<script type="text/javascript" src="styleswitcher.js"></script>
</head>
<body>


<div class="loginblock">

<div style="float:left"><a href="http://b2evolution.net/"><img src="../img/b2evolution_minilogo.png" width="231" height="50" /></a></div>

<?php if( isset($page_icon) ) { ?>
<img src="<?php echo $htsrv_url, '/img/', $page_icon ?>" width="24" height="24" style="float:right;" />
<?php } ?>
<div style="float:right">
<h1 class="logintitle"><?php echo $page_title ?></h1>
</div>

<div class="clear"></div>

<?php
if( !empty($error) )
{
	echo '<div class="error"><p class="error">'.$error.'</p></div>';
}
if( !empty($notes) )
{
	echo '<p>'.$notes.'</p>';
}
?>