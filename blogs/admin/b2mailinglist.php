<?php
import_request_variables("P", "P_");
import_request_variables("G", "G_");
$action=$P_action;
/* <Mailing List> */

	$standalone=0;
require_once(dirname(__FILE__)."/_header.php");
$title = T_('Mailing List');

	//et_currentuserinfo();
	if ($user_level <= 8) {
		die("You have no right to send mail for this blog.<br>Ask for a promotion to your <a href=\"mailto:$admin_email\">blog admin</a> :)");
	}

//Self Containing the Code -- if hidden for each FORM function is returned, that switch statement executes
switch($action)
{
	
	case "delete_id":
	//delete entery from mailing list
	
	mysql_connect($dbhost, $dbusername, $dbpassword);
        mysql_select_db($dbname);
        $sql = "DELETE FROM $tablemailinglist ";
        $sql .= "WHERE `ID`=$P_the_id"; 
        $result = mysql_query($sql);
    
        
        break;
        
        case "update":
        //Update Settings Values
        mysql_connect($dbhost, $dbusername, $dbpassword);
        mysql_select_db($dbname);
        $sql = "UPDATE $tablesettings ";
        $sql .= "SET mail_list_nick=\"$P_nick\", mail_list_address=\"$P_address\", mail_list_subject=\"$P_subject\"";
        $result = mysql_query($sql);
        
        break;
      
}
//End of Switch()
require (dirname(__FILE__).'/_menutop.php');

?>
					
<?php echo $blankline ?>

<div class="panelblock">
<CENTER>
<?PHP

mysql_connect($dbhost, $dbusername, $dbpassword);
mysql_select_db($dbname);
$sql = 'SELECT mail_list_nick, mail_list_subject, mail_list_address ';
$sql .= "FROM $tablesettings"; 
$result = mysql_query($sql);
$row = mysql_fetch_object($result);


?>
<h3>Mailing List Management</h3>

Mail Prefrences<br>
Nickname: Name to display in "from" field<br>
Address: The email address you wish to use<br>
Subject: Subject for your mailinglist<br><br>

<TABLE BORDER="1" ALIGN="CENTER">
        <TR>
                <TD>Nickname</TD>
                <TD>Address</TD>
                <TD>Subject</TD>
        </TR>
        
        <TR>                
                <TD>
                        <?PHP echo "<FORM ACTION=\"b2mailinglist.php\" METHOD=\"POST\" ENCTYPE=\"application/x-www-form-urlencoded\">";
                              echo "<INPUT TYPE=\"HIDDEN\" NAME=\"action\" value=\"update\">";
                              echo "<INPUT TYPE=\"TEXT\" VALUE=\"$row->mail_list_nick\" NAME=\"nick\" SIZE=\"20\"></TD>";
                              echo "<TD><INPUT TYPE=\"TEXT\" VALUE=\"$row->mail_list_address\" NAME=\"address\" SIZE=\"20\"></TD>";
                              echo "<TD><INPUT TYPE=\"TEXT\" VALUE=\"$row->mail_list_subject\" NAME=\"subject\" SIZE=\"20\">";?>
                </TD>
        </TR>
        <TR>
                <TD COLSPAN="3"><INPUT TYPE="SUBMIT" VALUE="SUBMIT"></TD>
        </TR>
</TABLE>
<BR><BR>
Check the box near the address your wish to delete<br>
and press the "Delete" button.<br>
Use "Next" and "Previous" to display the next page of addresses. <br><br>
<TABLE BORDER="1" ALIGN="center">
<TR><TD></TD><TD></TD><TD>ID</TD><TD>Email Address</TD><TD></TD><TD></TD><TD>ID</TD><TD>Email Address</TD></TR>

<?PHP

if($P_num=="")
{
        $count=20;
}
else
{
        $count=$P_num;
}
$x=1;        
mysql_connect($dbhost, $dbusername, $dbpassword);
mysql_select_db($dbname);
$sql = 'SELECT ID, email_address, cat_subscribe ';
$sql .= "FROM $tablemailinglist"; 
$result = mysql_query($sql);
while ($row = mysql_fetch_object($result)) 
{
        if($x>$count-20 && $x<=$count)
        {
                if($x%2!=0){echo "<TR>";}
                echo "<TD><FORM ACTION=\"b2mailinglist.php\" METHOD=\"POST\" ENCTYPE=\"application/x-www-form-urlencoded\">";
                echo "<INPUT TYPE=\"HIDDEN\" NAME=\"action\"";
                echo "VALUE=\"delete_id\"><INPUT TYPE=\"HIDDEN\" NAME=\"num\" VALUE=\"";
                echo $count;
                echo "\">";
                echo "<INPUT TYPE=\"SUBMIT\" VALUE=\"Delete\"></TD>";
                echo "<TD><INPUT TYPE=\"CHECKBOX\" NAME=\"the_id\" VALUE=\"$row->ID\"></TD>";
                echo "<TD>$row->ID</TD>";
                echo "<TD>$row->email_address</FORM></TD>";
                if($x%2==0)
                {
                        echo "</TR>";
                }        
        }
        $x++;
}
echo "</TABLE>";
echo "<CENTER><TABLE BORDER=\"0\"><TR><TD>";
if($count>20)
{
        echo "<FORM ACTION=\"b2mailinglist.php\" METHOD=\"POST\" ENCTYPE=\"application/x-www-form-urlencoded\">";
        echo "<INPUT TYPE=\"HIDDEN\" NAME=\"num\" value=\"";
        echo $count-20;
        echo "\"><INPUT TYPE=\"SUBMIT\" VALUE=\"<< 20\"></FORM></TD>";
}
if($x>$count+1)
{
        echo "<TD><FORM ACTION=\"b2mailinglist.php\" METHOD=\"POST\" ENCTYPE=\"application/x-www-form-urlencoded\">";
        echo "<INPUT TYPE=\"HIDDEN\" NAME=\"num\" value=\"";
        echo $count+20;
        echo "\"><INPUT TYPE=\"SUBMIT\" VALUE=\">> 20\"></FORM>";
}
echo "</TD></TR></TABLE></CENTER>";
 
?>


</CENTER>
</div>




<?php


/* </Mailing List> */
require (dirname(__FILE__).'/_menutop_end.php');?>