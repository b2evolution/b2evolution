<?php
/**
 * This file is called back by the online spell checker
 *
 * (The original SDK features a PERL script instead.)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
	//	print_r($_POST);
	foreach( $_POST as $key => $val )
	{
		echo 'handlin ', $key;
		$$key = preg_replace( '#\\\(.)#', '$1', $val );
	}
	echo $f;
	echo '<textarea name="TextBox1">';
	echo $g;
	echo '</textarea>';
	echo '<textarea name="TextBox2">';
	echo $h;
	echo '</textarea>';
	echo $i;
?>