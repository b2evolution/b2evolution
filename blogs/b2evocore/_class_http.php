<?
/**
 * This classfile implements http gets and posts
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
 */
class http {
  
  function post($server, $port, $url, $vars) {
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
	$headers = "POST $url HTTP/1.1
Accept: */*
Accept-Language: en
Content-Type: application/x-www-form-urlencoded
User-Agent: $user_agent
Host: $server
Connection: Keep-Alive
Cache-Control: no-cache
Content-Length: $content_length

";
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


  function get($server, $port, $url, $vars) {
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
        //lets make geturl, be $url and $urlencoded
        $geturl = $url . "?" . $urlencoded;
        //what headers do we need?
	$headers = "GET $geturl HTTP/1.1
Accept: */*
User-Agent: $user_agent
Host: $server
Connection: Keep-Alive
Cache-Control: no-cache
Content-Length: $content_length

";

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

};



?>