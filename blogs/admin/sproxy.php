<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file is called back by th eonline spell checker
 * (The original SDK features a PERL script instead.)
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