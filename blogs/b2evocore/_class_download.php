<?
 /**
 * This classfile implements file downloads
 * (and posibly uploads at a later date)
 *
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author Welby McRoberts - {@link http://www.wheely-bin.co.uk/}
 *
 * @package evocore
 */
if(ereg('_class', $_SERVER['SCRIPT_NAME']))
{
    die("You have too many shoes");
}

 //dirname(__FILE__).'/
require_once( '_class_http.php');

/**
 * Class download
 *
 * @author Welby McRoberts - {@link http://www.wheely-bin.co.uk/}
 */
class download
{

      	function saveToFile ($url, $type, $destination)
	{
         /*
         *   $url = "http://www.wheely-bin.co.uk/facecake.php"
         *   $type   =  get
         *              post
         *   $destination   = "/tmp/beecat.tar.gz"
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

         $fp = fopen($destination, "w");
         fclose($fp);
        };

};

?>