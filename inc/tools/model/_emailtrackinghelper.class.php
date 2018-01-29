<?php
/**
 * This file implements the email tracking helper class.
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


/**
 * Dependencies
 */
load_funcs('_core/_misc.funcs.php');


/**
 * Email Tracking Helper Class
 *
 * @package evocore
 */
class EmailTrackingHelper
{
	private $type;
	private $email_ID;
	private $key;

	function __construct( $type, $email_ID, $key, $mode = 'HTML' )
	{
		$this->type = $type;
		$this->email_ID = $email_ID;
		$this->key = $key;
		$this->mode = $mode;
	}

	public function callback( $matches )
	{
		$passthrough_url = get_htsrv_url().'email_passthrough.php?email_ID='.$this->email_ID.'&type='.$this->type.'&email_key=$secret_email_key_start$'.$this->key.'$secret_email_key_end$&redirect_to=';

		switch( $this->mode )
		{
			case 'HTML':
				if( preg_match( '~(\$secret_content_start\$)(.*)(\$secret_content_end\$)~', $matches[2], $submatches ) )
				{
					return $matches[1].$passthrough_url.'$secret_content_start$'.rawurlencode( $submatches[2] ).'$secret_content_end$'.$matches[3];
				}
				return $matches[1].$passthrough_url.rawurlencode( $matches[2] ).$matches[3];

			case 'plain_text':
				if( preg_match( '~(\$secret_content_start\$)(.*)(\$secret_content_end\$)~', $matches[0], $submatches ) )
				{
					return $passthrough_url.'$secret_content_start$'.rawurlencode( $submatches[2] ).'$secret_content_end$';
				}
				return $passthrough_url.rawurlencode( $matches[0] );
		}
	}
}

?>