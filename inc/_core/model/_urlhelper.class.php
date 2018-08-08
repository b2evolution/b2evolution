<?php
/**
 * This file implements the URL helper class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

class UrlHelper
{
	private $host;

	function __construct( $host )
	{
		$this->host = $host;
	}

	public function callback( $matches )
	{
		return $matches[1].$matches[2].$matches[3].url_absolute( $matches[4], "'.$this->host.'" ).$matches[5];
	}
}