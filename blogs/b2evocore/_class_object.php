<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

class Object
{

	/* 
	 * Object::Object(-)
	 *
	 * Constructor
	 */
	function Object( )
	{
	}	
	
	/* 
	 * Object::get(-)
	 *
	 * Get a param
	 */
	function get( $parname )
	{
		return $this->$parname;
	}

	/* 
	 * Object::disp(-)
	 *
	 * Display a param
	 */
	function disp( $parname, $format = 'htmlbody' )
	{
		// Note: we call get again because of derived objects specific handlers !
		echo format_to_output( $this->get($parname), $format );
	}
}
?>
