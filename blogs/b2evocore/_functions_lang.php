<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

function lang_options( $default = '' )
{
	global $languages, $default_language;
	
	if( !isset( $default ) ) $default = $default_language;
	
	foreach( $languages as $this_lang => $this_lang_params )
	{
		echo '<option value="'.$this_lang.'"';
		if( $this_lang == $default ) echo ' selected="selected"';
		echo '>'.$this_lang_params[0].'</option>';
	}
}
?>