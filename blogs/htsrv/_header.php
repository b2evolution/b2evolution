<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the header file for login/registering services
 */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo $page_title ?></title>
	<base href="<?php echo $htsrv_url; ?>/">
	<link rel="stylesheet" href="<?php echo $admin_url ?>/admin.css" type="text/css">
</head>
<body>


<div class="loginblock">

<div style="float:left"><a href="http://b2evolution.net/"><img src="../img/b2evolution_minilogo.png" width="231" height="50" border="0" /></a></div> 

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
	echo '<div class="error">'.$error.'</div>';
}
if( !empty($notes) )
{
	echo '<p>'.$notes.'</p>';
}
?>