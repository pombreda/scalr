<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("IP Access&nbsp;&raquo;&nbsp;Add host");
	
	if ($_POST) 
	{
		$Validator = Core::GetInstance("Validator");
		
		// Check FTP login
		if (!$post_ipaddress)
			$err[] = _("IPaddress must be filled!");
        
	    if (count($err) == 0)
	    {	
    		if (!$post_id)
    		{
    			$db->Execute("INSERT INTO ipaccess (ipaddress, comment) values (?,?)",array($post_ipaddress, $post_comment));
    			
    			$okmsg = _("IP address successfully added!");
    			CoreUtils::Redirect("ipaccess_view.php");
    			
    		}
    		else
    		{
    			$info = $db->GetRow("SELECT * FROM ipaccess WHERE id=?", array($post_id));
    			unset($info["id"]);
    			
    		    $db->Execute("UPDATE ipaccess SET ipaddress=?, comment=? WHERE id=?", array($post_ipaddress, $post_comment, $post_id));
    
    		    $uinfo = $db->GetRow("SELECT * FROM ipaccess WHERE id=?", array($post_id));
    			unset($uinfo["id"]);
    		    
    		    $okmsg = _("Ip address succesfully updated");
    			CoreUtils::Redirect("ipaccess_view.php");
    		}
	    }
	}
	
	if ($post_id || $get_id)
	{
		$id = (int)$req_id;
		$display["ip"] = $db->GetRow("SELECT * FROM ipaccess WHERE id=?", array($id));
		$display["id"] = $id;
	}
	else
		$display = array_merge($display, $_POST);
	
	require("src/append.inc.php"); 	
?>