<?php
/**
 * This file implements the Download class.
 *
 * (and posibly uploads at a later date)
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author wmcroberts: Welby McRoberts - {@link http://www.wheely-bin.co.uk/}
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_http.class.php';

/**
 * Class download
 *
 * @author Welby McRoberts - {@link http://www.wheely-bin.co.uk/}
 */
class Download
{

 	function saveToFile ($url, $type, $destination)
	{
    /*
     *   $url = "http://www.wheely-bin.co.uk/facecake.php"
     *   $type   =  get
     *              post
     *   $destination   = "/tmp/beecat.tar.gz"
     */
     $data = getToMemory($url, $type)
     $fp = fopen($destination, "w");

     fclose($fp);
    }

    function getToMemory   ($url, $type) {
  /*
   *   $url = "http://www.wheely-bin.co.uk/facecake.php"
   *   $type   =  get
   *              post
   */
   if (!is_null($url){
    /*
     parse_url("http://username:password@hostname:81/path?arg=value#anchor");
     Array
     (
          [scheme] => http
          [host] => hostname
          [port] => 81
          [user] => username
          [pass] => password
          [path] => /path
          [query] => arg=value
          [fragment] => anchor
     )
     */
      $url_array = parse_url($url);
      $hostname = $url_array["host"];
      $path = $url_array["url"];
      $vars = $url_array["query"];
      $port = $url_array["port"];

    }
    else { die();};
   if (!is_null($destination){
    }
    else { die();};
    switch ($type)
       {
          case "socket_get":
                  $result = http::socket_get($hostname, $port, $url, $vars);
                  break;
          case "socket_post":
                  $result = http::socket_post($server, $port, $url, $vars);
                  break;
           case "curl_get":
                  $result = http::curl_get($hostname, $port, $url, $vars);
                  break;
          case "curl_post":
                  $result = http::curl_post($server, $port, $url, $vars);
                  break;
          default:
                  http::socket_get($server, $port, $url, $vars);
                  break;
       };
    }
};

/*
 * $Log$
 * Revision 1.4  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.3  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2004/12/21 21:22:46  fplanque
 * factoring/cleanup
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.8  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>