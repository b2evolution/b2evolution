<?php
/**
 * This page displays an error message when we have detected referer spam.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package evocore
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
header('HTTP/1.0 403 Forbidden');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
 		<title>403 Forbidden - Please stop referer spam.</title>
	</head>
	<body>
		<h1>403 Forbidden</h1>
		<h2>Please stop referer spam.</h2>
		<p>We have identified that you have been refered here by a known or supposed spammer.</p>
		<p>If you feel this is an error, please <a href="<?php global $ReqURI; echo $ReqURI; ?>">bypass this message</a>
		and leave us a comment about the error. We are sorry for the inconvenience.</p>
		<p>If you are actually doing referer spam, please note that this website/<?php global $app_name; echo $app_name; ?> no longer records and publishes referers. Not even legitimate ones!
		While we understand it was fun for you guys while it lasted, please understand our servers cannot take the load of
		all this cumulated spam any longer... Thank you.</p>
		<p>Also, please note that comment/trackback submitted URLs will be tagged with rel="nofollow" in order to be ignored by search engines.</p>
  </body>
</html>
<?php
	debug_info();
 	exit;
?>