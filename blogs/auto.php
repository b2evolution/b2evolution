
<?PHP

//New Auto.php mailing list sender, updated to work with SQL
//Revisions to date checking, send mailing list, allowing for specific emails
//to users based on preferences.

require_once (dirname(__FILE__).'/conf/_config.php');
$my_date = date("Y-m-d H:i:s",$my_date); //Get todays date
$its_date="";

//Connect to the Database
mysql_connect($dbhost, $dbusername, $dbpassword);
mysql_select_db($dbname);

//Define Arrays to hold values
$post_cat=array();
$post_count=array();
$cat_name=array();

//Loop Varibales
$i=0;




//Query for for some basic params for mailinglist.
 
$sql = "SELECT mail_list_last_run, mail_list_nick, mail_list_subject, mail_list_address ";
$sql .= "FROM $tablesettings";
$result =  mysql_query($sql);

$row = mysql_fetch_object($result);
$last_run = $row->mail_list_last_run;
$subject = $row->mail_list_subject;
$nick = $row->mail_list_nick;
$address = $row->mail_list_address;        

//If the script hasn't been run today, let's go!
if( strtotime($last_run) < strtotime( date("Ymd") ) )
{
        //Update time stamp on mailing list
        $stamp=date("Ymd");
        $sql = "UPDATE $tablesettings ";
        $sql .= "SET mail_list_last_run=\"$stamp\"";
        $result =  mysql_query($sql);
        
        
        $last_run = strtotime($last_run);
 
        $last_run=date("Y-m-d H:i:s",$last_run);

        
        
        //Query for Post Data, Sort into an array of categories not updated yet
        $sql = "SELECT post_category, post_date ";
        $sql .= "FROM $tableposts  WHERE post_date > '$last_run' ";
        $result = mysql_query($sql);
        while( $row = mysql_fetch_object($result) )
        {
                
                $found=1;
                $j=0;
                foreach($post_cat as $cat_num)
                {
                        if($row->post_category==$cat_num)
                        { 
                                $found=0;
                                $post_count[$j]+=1;
                        }
                        $j++;
                        
                }
                if($found==1) 
                {
                        $post_cat[$i]=$row->post_category;
                        $post_count[$i]=1;
                        $i++;
                }
         
        }
        
        
        $i=0;
        $sql = "SELECT cat_ID, cat_name ";
        $sql .= "FROM $tablecategories"; 
        $result = mysql_query($sql);
        while($row = mysql_fetch_object($result) )
        {
                $i=0;
                foreach($post_cat as $cat)
                {
                        if($cat==$row->cat_ID)
                        {
                                $cat_name[$i]=$row->cat_name;
                        }
                        $i++;
                }
        }
        
        
        $sql = "SELECT email_address, ID, cat_subscribe ";
        $sql .= "FROM $tablemailinglist";
        $result = mysql_query($sql);
        
        $k=mysql_affected_rows();
        while( $row = mysql_fetch_object($result) )
        {
        $sendmail=0;
        $email_address=$row->email_address;
        
        $user_subscribe=explode(",", $row->cat_subscribe);
                
                $body="------------------------------------------------------------------------------------
        This email is not spam. You signed up for this list at $baseurl.
        You can unsubscribe by following the link at the bottom of  this email message.
        ----------------------------------------------------------------------------------
        
        ";
        $j=0;
        while($user_subscribe[$j]!="-1")
        {
                $i=0;
                foreach($post_cat as $cat)
                {
                        if($cat==$user_subscribe[$j])
                        {
                                $post_count[$i]!="1" ? $suffix="s" : $suffix="";
                                $body .= $cat_name[$i]." :: (".$post_count[$i].") New Post".$suffix."\n";
                                if($cat_name!="") $sendmail=1;
                        }
                        $i++;
                }
                $j++;
        }
        
        $body .= "
        -------------------------------------------------------------------------------------
        If you would like to unsubscribe from the $siteurl
        post update email list, just click the link below
        $baseurl/unsubscribe.php?id=$row->ID
        if the link above does not show in your email
        program just cut and paste it to your browser.
        ------------------------------------------------------------------------------------- ";
        
        //Send the email.
     
        if($sendmail==1) mail($email_address, $subject, $body, "From: $nick <$address>");
        
      
}
        echo "The script has been run. You will not be able to run it again until tomorrow.";
}
else
{
        echo "The script has already been run today.";
}

?>