<?php
/**
 * Placeholder for old login page
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @deprecated In b2evolution, you do not need to point to the b2login.php page to log in.
 */

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/../conf/_config.php' );
require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_main.php' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('Login...') ?></title>
</head>
<body>
<p><?php echo T_('In b2evolution, you do not need to point to the b2login.php page to log in.') ?></p>
<p><?php printf( T_('Simply point directly to the backoffice page you need, for example: %s or %s. b2evo will prompt you to log in if needed.'), '<a href="b2edit.php">b2edit.php</a>', '<a href="b2browse.php">b2browse.php</a>' ); ?></p>
</body>
</html>