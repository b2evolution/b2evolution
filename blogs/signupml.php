<?PHP
//Mailing list Sign up-script, meant to be echoed in a HTML page with params for table size
require_once (dirname(__FILE__).'/conf/_config.php');

import_request_variables("P", "P_");
import_request_variables("G", "G_");
$action=$P_action;
$so=$P_Blah;

mysql_connect($dbhost, $dbusername, $dbpassword);
mysql_select_db($dbname);


function show_signup($width=3, $message="")
{
        require (dirname(__FILE__).'/conf/_advanced.php');
        $check=0;
        echo "<FORM ACTION=\"dirname(__FILE__).'/../../../signupml.php\" METHOD=\"POST\" ENCTYPE=\"application/x-www-form-urlencoded\">";
        echo "<INPUT TYPE=\"HIDDEN\" NAME=\"action\" VALUE=\"subscribe\">";
        echo "<TABLE BORDER=0>";
        echo "<TR><TD ALIGN=\"center\"><FONT COLOR=\"red\">$message</FONT></TD></TR>";
        echo "<TR><TD>";
        echo "<TABLE BORDER=0>";
        
        $sql = 'SELECT cat_ID, cat_name ';
        $sql .= "FROM $tablecategories"; 
        $result = mysql_query($sql);
        
        while ($row = mysql_fetch_object($result)) {      
                if($check==0)
                {
                        echo "<TR>";
                }
                echo "<TD><INPUT TYPE=\"CHECKBOX\" NAME=\"c$row->cat_ID\" VALUE=\"$row->cat_ID\"><FONT SIZE=\"2\">$row->cat_name</FONT></TD>";
                $check++;
                if($check==$width)
                {
                        $check=0;
                        echo "</TR>";
                }
                
       }
       mysql_free_result($result);
       
       echo "</TABLE></TD></TR>";
       echo "<TR><TD ALIGN=\"center\"><INPUT TYPE=\"TEXT\" NAME=\"email\" WIDTH=25></TD></TR>";
       echo "<TR><TD ALIGN=\"center\"><INPUT TYPE=\"SUBMIT\" VALUE=\"Submit\"></TD><TR>";
       echo "</TABLE>";
}

switch($action)
{
case "subscribe":        
        $categories=array();
        $x=0;
        
if(ereg("([[:alnum:]\.\-]+)(\@[[:alnum:]\.\-]+\.+)", $P_email))
{
        mysql_connect($dbhost, $dbusername, $dbpassword);
        mysql_select_db($dbname);
        
        
        $sql = 'SELECT email_address ';
        $sql .= "FROM $tablemailinglist ";
        $sql .= 'WHERE email_address = "';
        $sql .= "$P_email";
        $sql .= '"';  
        $result = mysql_query($sql);       
        $row = mysql_fetch_object($result);
           
        if($row->email_address == $P_email)
        {
                header("location: $HTTP_REFERER.?message=Address Already Exists");
        }
        else 
        {
        mysql_free_result($result);
        
        $sql = 'SELECT cat_ID, cat_name ';
        $sql .= "FROM $tablecategories"; 
        $result = mysql_query($sql);
        
        while ($row = mysql_fetch_object($result)) 
        {
                $temp=$row->cat_ID;
                $p_temp=P_c.$temp;
                if(${$p_temp}!=""){$categories[$x]=${$p_temp}.",";}
                $x++;

        }
        $categories[$x]="-1";
        $list="";
        for($i=0;$i<=$x;$i++)
        {
                $list=$list.$categories[$i];
        }
        mysql_free_result($result);
        $sql = "INSERT INTO $tablemailinglist( `ID` , `email_address` , `cat_subscribe` ) ";
        $sql .= "VALUES ( NULL , \"$P_email\", \"$list\" )"; 
        $result = mysql_query($sql);
        if($result)header("location: $HTTP_REFERER.?message=Address Added");
        else header("location: $HTTP_REFERER.?message=DID NOT ADD, $tablemailinglist");
}
}
else
{
        header("location: $HTTP_REFERER.?message=Invalid Address");
        break;
}        
break;
        
}
      

?>
                    