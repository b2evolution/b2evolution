<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 */
require_once(dirname(__FILE__).'/../conf/b2evo_config.php');
require_once(dirname(__FILE__)."/$b2inc/_main.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Login...') ?></title>
	<link rel="stylesheet" href="b2.css" type="text/css">
</head>
<body bgcolor="#ffffff" text="#000000" link="#cccccc" vlink="#cccccc" alink="#ff0000">
<p>In b2evolution, you do not need to point to the b2login.php page to log in.</p>
<p>Simply point directly to the backoffice page you need, for example: <a href="b2edit.php">b2edit.php</a> or <a href="b2browse.php">b2browse.php</a>. b2evo will prompt you to log in if needed.</p>
</body>
</html>