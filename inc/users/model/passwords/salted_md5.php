<?php
/**
 * This file implements the salted md5 Password Driver class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'users/model/passwords/_passworddriver.class.php', 'PasswordDriver' );

/**
 * saltedMd5PasswordDriver Class
 *
 * @package evocore
 */
class saltedMd5PasswordDriver extends PasswordDriver
{
	protected $code = 'bb$H';


	/**
	 * Hash password
	 *
	 * @param string Password
	 * @param string Settings
	 * @param string Old hash, used to extract a salt from old hash, can be useful on checking with entered password
	 * @return string Hashed password
	 */
	public function hash( $password, $setting = '', $old_hash = '' )
	{
		if( empty( $setting ) && $old_hash != '' )
		{	// Use setting from old hash when a request to compare a hash with entered password:
			$setting = $old_hash;
		}

		if( $setting )
		{
			if( ( $settings = $this->get_hash_settings( $this->get_prefix().$setting ) ) === false )
			{
				// Return md5 of password if settings do not
				// comply with our standards. This will only
				// happen if pre-determined settings are
				// directly passed to the driver. The manager
				// will not do this. Same as the old hashing
				// implementation in phpBB 3.0
				return md5( $password );
			}
		}
		else
		{
			$settings = $this->get_hash_settings( $this->get_random_salt() );
		}

		$hash = md5( $settings['salt'].$password, true );
		do
		{
			$hash = md5( $hash.$password, true );
		}
		while( --$settings['count'] );

		$output = $settings['full'];
		$output .= $this->hash_encode64( $hash, 16 );

		return $this->clear_hash( $output );
	}


	/**
	 * Check password against the supplied hash
	 *
	 * @param string Password
	 * @param string Salt
	 * @param string Hash
	 * @param boolean Is the password parameter already hashed?
	 * @return boolean TRUE if password is correct, else FALSE
	 */
	public function check( $password, $salt, $hash, $password_is_hashed = false )
	{
		if( strlen( $hash ) !== 31 )
		{
			return md5( $password ) === $hash;
		}

		return $this->string_compare( $hash, $this->hash( $password, $hash ) );
	}

	/**
	* Get a random salt value
	*
	* @return string Salt for password hashing
	*/
	protected function get_random_salt()
	{
		$count = 6;

		$random = generate_random_key( $count );

		$salt = $this->get_prefix();
		$salt .= $this->itoa64[ min( $count + 5, 30 ) ];
		$salt .= $this->hash_encode64( $random, $count );

		return $salt;
	}

	/**
	* Get hash settings
	*
	* @param string The hash that contains the settings
	*
	* @return boolean|array Array containing the count_log2, salt, and full
	*   hash settings string or false if supplied hash is empty
	*   or contains incorrect settings
	*/
	public function get_hash_settings( $hash )
	{
		if( empty( $hash ) )
		{
			return false;
		}

		$count_log2 = strpos( $this->itoa64, $hash[3] );
		$salt = substr( $hash, 4, 8 );

		if( $count_log2 < 7 || $count_log2 > 30 || strlen( $salt ) != 8 )
		{
			return false;
		}

		return array(
			'count'	=> 1 << $count_log2,
			'salt'	=> $salt,
			'full'	=> substr( $hash, 0, 12 ),
		);
	}
}
?>