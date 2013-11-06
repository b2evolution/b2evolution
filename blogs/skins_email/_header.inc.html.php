<?php
/**
 * This is the HTML template of HEADER email message
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
?>
<style>
img {
	border: none;
}
img.avatar_before_login {
	margin-right: 2px;
	vertical-align: bottom;
}

/* User Genders */
.user, user.anonymous{
	font-weight: bold;
}

.user.closed {
	color: #666;
}

.user.man{
	color: #00F;
}
.user.woman{
	color: #e100af;
}
.user.nogender, user.anonymous.nogender{
	color: #000;
}
</style>
