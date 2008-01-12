<?php
/**
 * XML-RPC : Movable Type API (partial)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author tor
 *
 * @package xmlsrv
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );



$mt_setPostCategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcArray));
$mt_setPostCategories_doc = "Sets the categories for a post.";
/**
 * mt.setPostCategories
 */
function mt_setPostCategories($m)
{
	global $xmlrpcerruser,$Settings;
	global $DB, $Messages;

	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();

	if( ! user_pass_ok($username,$password) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}

// ok - pick up new category from call
	$xcontent = $m->getParam(3); // This is now a array of structs
	$iSize = $xcontent->arraysize(); // The number of objects ie categories
	logIO("O","finished getting - iSize ...".$iSize); // number of categories entry has set

	logIO("O","finished getting contentstruct ...");
	$categories = array();
	if ($iSize > 0)
	{
		for ($i=0;$i<$iSize;$i++)
		{
			logIO("O","finished getting - i ...>".$i); // works!
			$struct = $xcontent->arraymem($i); // get a struct object from array
			$tempcat = $struct->structmem('categoryId');
			$tempcat = $tempcat->scalarval();
			$tempPrimary = $struct->structmem('isPrimary');// Start finding the primary category
			$tempPrimary = $tempPrimary->scalarval();
			if ($tempPrimary != 0)
			{
				logIO("O","got primary category and there should only be one...".$tempcat);
				$category = $tempcat;
			}
			logIO("O","finished getting - tempcat ...".$tempcat); // works!
			$categories[$i] = $tempcat;
			logIO("O","finished getting - categories ...".$categories[$i]);
		}
	}

	// UPDATE POST CATEGORIES IN DB:
	logIO("O","bpost_update - category ...".$category); // works!
	$ItemCache = & get_Cache( 'ItemCache' );
	if( ! ($edited_Item = & $ItemCache->get_by_ID( $post_ID ) ) )
	{
		return new xmlrpcresp(0, $xmlrpcerruser+7, "No such post (#$post_ID)."); // user error 7
	}
	$edited_Item->set( 'main_cat_ID', $category );
	$edited_Item->set( 'extra_cat_IDs', $categories );

	$edited_Item->dbupdate();

	return new xmlrpcresp(new xmlrpcval(1));
}


$mt_getPostCategories_sig = array(array($xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString));
$mt_getPostCategories_doc = "Returns a list of all categories to which the post is assigned.";
/**
 * mt.getPostCategories
 */
function mt_getPostCategories($m) {
	global $xmlrpcerruser;
	global $DB;

	$post_ID = $m->getParam(0);
	$post_ID = $post_ID->scalarval();
	logIO("O","mt_getPostCategories postID  ...".$post_ID);
	$username = $m->getParam(1);
	$username = $username->scalarval();
	$password = $m->getParam(2);
	$password = $password->scalarval();
	if( user_pass_ok($username,$password) )
	{
		// First get the primary category in postdata
		$postdata = get_postdata($post_ID);
		$dato = $postdata["Date"];
			logIO("O","mt_getPostCategories get_postdata argument postID  ...".$post_ID);
			logIO("O","mt_getPostCategories postdata argument date  ...".$dato);
		$Category = $postdata["Category"];// Primary category - nb also present in separate table so will not be used
			logIO("O","mt_getPostCategories postdata argument Category  ...".$Category);
		$categories = postcats_get_byID( $post_ID ); // Secondary categories
			logIO("O","mt_getPostCategories postcats_get_byID  ...".$categories);
		$iSize = count($categories); // The number of objects ie categories
		logIO("O","mt_getgategorylist  no of categories...".$iSize);// works
		$struct = array();
		for ($i=0;$i<$iSize;$i++)
		{
			logIO("O","mt_getPostCategories categories  ...".$categories[$i]);
// In database cat_ID and cat_name from tablecategories
			$sql = "SELECT * FROM T_categories WHERE  cat_ID = $categories[$i] ";
			logIO("O","mt_getgategorylist  sql...".$sql);
			$rows = $DB->get_results( $sql );
			foreach( $rows as $row )
			{
				$Categoryname =  $row->cat_name;
				logIO("O","mt_getPostCategories Categoryname  ...".$Categoryname);
			}
			if( $postdata['Date'] != '' )
			{
				logIO("O","mt_getPostCategories date ok  ...");
				if ($i > 0) {
				logIO("O","mt_getPostCategories found secondary  ...".$categories[$i]);
					$isPrimary = "0";
				}
			else
			{
				logIO("O","mt_getPostCategories found primary  ...".$categories[$i]);
				$isPrimary = "1";
			}
			$struct[$i] = new xmlrpcval(array("categoryId" => new xmlrpcval($categories[$i]),    // Look up name from ID separately
											"categoryName" => new xmlrpcval($Categoryname),
											"isPrimary" => new xmlrpcval($isPrimary)
											),"struct");
		}
	}
	return new xmlrpcresp(new xmlrpcval($struct, "array") );
//		else
//		{
//		return new xmlrpcresp(0, $xmlrpcerruser+7, // user error 7
//					 "No such post #$post_ID");
//		}
	}
	else
	{
		return new xmlrpcresp(0, $xmlrpcerruser+1, // user error 1
					 'Wrong username/password combination '.$username.' / '.starify($password));
	}
}


$mt_getCategoryList_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mt_getCategoryList_doc = 'Get category list';
/**
 * mt.getCategoryList
 *
 * @see http://www.sixapart.com/developers/xmlrpc/movable_type_api/mtgetcategorylist.html
 */
function mt_getCategoryList($m) {
	logIO("O","mt_getCategoryList  start");
	return _b2_or_mt_get_categories('mt', $m);
}




$xmlrpc_procs["mt.getCategoryList"] = array(
				"function" => "mt_getCategoryList",
				"signature" => $mt_getCategoryList_sig,
				"docstring" => $mt_getCategoryList_doc );

$xmlrpc_procs["mt.setPostCategories"] = array(
				"function" => "mt_setPostCategories",
				"signature" => $mt_setPostCategories_sig,
				"docstring" => $mt_setPostCategories_doc );

$xmlrpc_procs["mt.getPostCategories"] = array(
				"function" => "mt_getPostCategories",
				"signature" => $mt_getPostCategories_sig,
				"docstring" => $mt_getPostCategories_doc );


?>
