<?PHP
require_once (dirname(__FILE__).'/conf/_config.php');
import_request_variables(G, "G_");

if($G_id!="")
{

        mysql_connect($dbhost, $dbusername, $dbpassword);
        mysql_select_db($dbname);
        $sql = "DELETE FROM $tablemailinglist ";
        $sql .= 'WHERE ID = "';
        $sql .= "$G_id";
        $sql .= '"'; 
        $result = mysql_query($sql);
        
        if(mysql_affected_rows()==0)
        {
                echo "<CENTER><h2>You have already been unsubscribed.</h2></CENTER>";
        }
        else
        {
                echo "<CENTER><h2>You have been successfully removed.</h2></CENTER>";
        }        
              
}
else
{
        echo "<CENTER><h2>General Access to this page is not allowed.</h2></CENTER>";
}
?>