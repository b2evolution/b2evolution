<?php
/**
 * This file implements the Http class, which gets and posts via both sockets and curl.
 *
 * with wget soon
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author Welby McRoberts - {@link http://www.wheely-bin.co.uk/}
 *
 * @package evocore
 */

/**
 * Class Http
 *
 * @author Welby McRoberts - {@link http://www.wheely-bin.co.uk/}
 **/
if(ereg('_class', $_SERVER['SCRIPT_NAME']))
{
    die("You have too many shoes");
}


class Http {

  function socket_post($server, $port, $url, $vars) {
   /*
    *   $server = "www.wheely-bin.co.uk"
    *   $port   = 80
    *   $url    = "/facecake.php"
    *   $vars   = array("who" => "am three", "face" => cake")
    */

        // lets make our ua something unique to b2evo ...
	$user_agent = "b2evolution";
        $urlencoded = "";
	//lets encode the url
        while (list($key,$value) = each($vars))
		$urlencoded.= urlencode($key) . "=" . urlencode($value) . "&";
	//lets trim it
        $urlencoded = substr($urlencoded,0,-1);
	//set the content length
        $content_length = strlen($urlencoded);
        //what headers do we need?
        $headers = "POST $url HTTP/1.1"
        ."\r\nAccept: */*\r\n"
        ."Accept-Language: en\r\n"
        ."Content-Type: application/x-www-form-urlencoded\r\n"
        ."User-Agent: $user_agent\r\n"
        ."Host: $server\r\n"
        ."Connection: Keep-Alive\r\n"
        ."Cache-Control: no-cache\r\n"
        ."Content-Length: $content_length\r\n\r\n";
	// lets open a socket for the post, 
	$post = fsockopen($server, $port);
	//could we create the socket ?
        if (!$post) {
                //balls we couldnt
		return false;
	};
        //excellent, it opened the socket
        //lets send the headers
	fputs($post, $headers);
	//and now the encoded things
        fputs($post, $urlencoded);
	$ret = "";
        //lets set $ret to whatever is returned by the server
	while (!feof($post))
		$ret.= fgets($post, 1024);
        //close the socket
	fclose($post);
        //return $ret
	return $ret;

  }


  function socket_get($server, $port, $url, $vars) {
  /*
   *   $server = "www.wheely-bin.co.uk"
   *   $port   = 80
   *   $url    = "/facecake.php"
   *   $vars   = array("who" => "am three", "face" => cake")
   */

        // lets make our ua something unique to b2evo ...
	$user_agent = "b2evolution";

        if (!is_null($vars))
        {
	$urlencoded = "";
	//lets encode the url
        while (list($key,$value) = each($vars))
		$urlencoded.= urlencode($key) . "=" . urlencode($value) . "&";
	//lets trim it
        $urlencoded = substr($urlencoded,0,-1);
	//set the content length
        $content_length = strlen($urlencoded);
        //lets make geturl, be $url and $urlencoded
        $geturl = $url . "?" . $urlencoded;
        }
        else {  $geturl = $url; };
        //what headers do we need?
        $headers = "GET $geturl HTTP/1.1\r\n"
        ."Accept: */*\r\n"
        ."User-Agent: $user_agent\r\n"
        ."Host: $server\r\n"
        ."Connection: Keep-Alive\r\n"
        ."Cache-Control: no-cache\r\n"
        ."Content-Length: $content_length\r\n\r\n";

	// lets open a socket for the post, 
	$get = fsockopen($server, $port);
	//could we create the socket ?
        if (!$get) {
                //balls we couldnt
		return false;
        }
        //excellent, it opened the socket
        //lets send the headers
	fputs($get, $headers);
	$ret = "";
        //lets set $ret to whatever is returned by the server
	while (!feof($get))
		$ret.= fgets($get, 1024);
        //close the socket
	fclose($get);
        //return $ret
	return $ret;

  }

  function curl_post($server, $port, $url, $vars) {
  /*
   *   $server = "www.wheely-bin.co.uk"
   *   $port   = 80
   *   $url    = "/facecake.php"
   *   $vars   = array("who" => "am three", "face" => cake")
   */

        // lets make our ua something unique to b2evo ...
	$user_agent = "b2evolution";

        //lets set the port to 80 is its not already set
        if (is_null($port)){
           $port = 80;
        };

        //lets make the url
        $url = "http://" . $server . ":" . $port . $url;
        // lets start the hair curling
        $ch = curl_init($url);
        // lets set our ua to $user_agent
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        // lets be verbose for now
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        // lets have the headers
        curl_setopt($ch, CURLOPT_HEADER, 1);
        // lets follow any redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // get the post feilds  into curl
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);

        // perform post
        $ret=curl_exec($ch);
        //hair curlings done :P ITS A PERM!
        curl_close($ch);
        // return the output
	return $ret;

  }



    function curl_get($server, $port, $url, $vars) {
  /*
   *   $server = "www.wheely-bin.co.uk"
   *   $port   = 80
   *   $url    = "/facecake.php"
   *   $vars   = array("who" => "am three", "face" => cake")
   */

        // lets make our ua something unique to b2evo ...
	$user_agent = "b2evolution";

        //lets set the port to 80 is its not already set
        if (is_null($port)){
           $port = 80;
        };

        //lets make the url
       if (!is_null($vars))
        {
	$urlencoded = "";
	//lets encode the url
        while (list($key,$value) = each($vars))
		$urlencoded.= urlencode($key) . "=" . urlencode($value) . "&";
	//lets trim it
        $urlencoded = substr($urlencoded,0,-1);
	//set the content length
        $content_length = strlen($urlencoded);
        //lets make geturl, be $url and $urlencoded
        $geturl = $url . "?" . $urlencoded;
        }
        else {  $geturl = $url; };

        // lets start the hair curling
        $ch = curl_init($url);
        // lets set our ua to $user_agent
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        // lets be verbose for now
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        // lets have the headers
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // lets follow any redirects
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // perform get
        $ret=curl_exec($ch);
        //everythings been "get"'ed 
        curl_close($ch);
        // return the output
	return $ret;
     }
};









?>