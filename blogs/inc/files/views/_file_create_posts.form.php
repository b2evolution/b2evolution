<?php
/**
 * This file implements the UI for make posts from images in file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Settings
 */

load_class( "items/model/_item.class.php" , "Item" );
load_class( 'files/model/_filelist.class.php', 'FileList' );

global $post_extracats , $fm_FileRoot , $edited_Item;
$edited_Item= new Item();

$Form = new Form( NULL, 'pre_post_publish' );

$Form->begin_form( 'fform', T_('Posts preview') );
$Form->hidden_ctrl();
$Form->hidden( 'group_action', 'make_posts' );

$images_list = get_param('fm_selected');
foreach ($images_list as $key=>$item ) {
	$Form->hidden( 'fm_selected['. $key .']' , $item );
}
$Form->hidden( 'confirmed', get_param('confirmed') );
$Form->hidden( 'md5_filelist', get_param('md5_filelist') );
$Form->hidden( 'md5_cwd', get_param('md5_cwd') );
$Form->hidden( 'locale', get_param('locale') );
$Form->hidden( 'blog', get_param('blog') );
$Form->hidden( 'mode', get_param('mode') );
$Form->hidden( 'root', get_param('root') );
$Form->hidden( 'path', get_param('path') );
$Form->hidden( 'fm_mode', get_param('fm_mode') );
$Form->hidden( 'linkctrl', get_param('linkctrl') );
$Form->hidden( 'linkdata', get_param('linkdata') );
$Form->hidden( 'iframe_name', get_param('iframe_name') );
$Form->hidden( 'fm_filter', get_param('fm_filter') );
$Form->hidden( 'fm_filter_regex', get_param('fm_filter_regex') );
$Form->hidden( 'iframe_name', get_param('iframe_name') );
$Form->hidden( 'fm_flatmode', get_param('fm_flatmode') );
$Form->hidden( 'fm_order', get_param('fm_order') );
$Form->hidden( 'fm_orderasc', get_param('fm_orderasc') );
$Form->hidden( 'crumb_file', get_param('crumb_file') );

$post_extracats=array();
$post_counter = 0;


$selected_Filelist = new Filelist( $fm_FileRoot, false );

$selected_Filelist->load_meta();
//print_r();die();
//$DB->get_results("SELECT * from T_categories WHERE ");
function fcpf_categories_select ($default_Value,$parent_Category=-1,$level=0) 
{
	global $blog , $DB;
	$result_Array = array();
	
	if ($parent_Category==-1) 
	{
		$result=$DB->get_results('SELECT * from T_categories WHERE cat_parent_ID is NULL and cat_blog_ID ='.$DB->escape($blog) . " ORDER by cat_name ");
	} else 
	{
		$result=$DB->get_results('SELECT * from T_categories WHERE cat_parent_ID = ' .$DB->escape($parent_Category). ' and cat_blog_ID ='.$DB->escape($blog) . " ORDER by cat_name ");
	}
	
	if (!empty($result)) 
	{
		foreach ($result as $item) 
		{
			$label = "";
			for ($i =0 ; $i<$level;$i++) {
				$label .= '&nbsp;&nbsp;&nbsp';
			}
			$label.=$item->cat_name;
			$result_Array[]= array( "value" => $item->cat_ID , "label" => $label );
			
			$child_Categories_opts = fcpf_categories_select($default_Value,$item->cat_ID,$level+1);
			if ($child_Categories_opts!='') 
			{
				foreach ($child_Categories_opts as $cat) {
					$result_Array[]=$cat;
				}
			}
		}
	}
	return $result_Array;
}
foreach ($images_list as $item) {
	$Form->begin_fieldset( T_('Post #'. $post_counter) );
	$Form->text_input( "post_title[". $post_counter . "]" , $item , 40, T_('Post title') );
	//$Form->select( "post_category[" . $post_counter . "]" , "" ,"fcpf_categories_select",  T_('Post category') );
//	print_r(fcpf_categories_select(""));die();
	$Form->radio_input("category[". $post_counter . "]",1,fcpf_categories_select(""),"Category",array("suffix"=>"<br>"));
	
	echo '<div class="label"><label>Post content:</label></div>';	
	//$l_File = & $selected_Filelist->get_next();
	//print_r($l_File);die();
	?>
	
	<img src="<?php echo $selected_Filelist->_FileRoot->ads_url . $item ; ?>" width="200" style="margin-left:20px" />
	
<?php
		
	$Form->end_fieldset();
	$post_counter++;
}
	$edited_Item=NULL;
	
$Form->end_form( array( array( 'submit', 'actionArray[make_posts]', T_('Make posts'), 'ActionButton')
												 ) );
	
//print_r($_POST);
