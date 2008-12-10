<? 
	require("src/prepend.inc.php"); 
	
	$display["title"] = _("Settings&nbsp;&raquo;&nbsp;Default DNS records");
	
	if ($_POST) 
	{
		$err = array();
		foreach ((array)$post_zone["records"] as $k=>$v)
		{
			if ($v["rkey"] != '' || $v["rvalue"] != '')
			{
				//
				// Validate Record
				//
				
				// Only for validate zone
				$key = str_replace("%hostname%", "test.com.", $v["rkey"]);
				$value = str_replace("%hostname%", "test.com.", $v["rvalue"]);
				
				$GLOBALS['warnings'] = array();
				$reflection = new ReflectionClass("{$v['rtype']}DNSRecord");
				if ($v['rtype'] != 'MX')
					$c = $reflection->newInstance($key, $value, $v["ttl"]);
				else
					$c = $reflection->newInstance($key, $value, $v["ttl"], $v["rpriority"]);
				
				if ($c->__toString() == "")
				{
					$err = array_merge($GLOBALS['warnings'], $err);
				}
				else
					$db->Execute("UPDATE default_records SET `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=? WHERE id=? AND clientid='{$_SESSION['uid']}'", array($v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"], $k));
			}
			else
				$db->Execute("DELETE FROM default_records WHERE id=? AND clientid='{$_SESSION['uid']}'", array($k));
		}
		
		foreach ((array)$post_add as $k=>$v)
		{
			if ($v["rkey"] != '' || $v["rvalue"] != '')
			{
				//
				// Validate Record
				//
				
				// Only for validate zone
				$key = str_replace("%hostname%", "test.com.", $v["rkey"]);
				$value = str_replace("%hostname%", "test.com.", $v["rvalue"]);
				
				$GLOBALS['warnings'] = array();
				$reflection = new ReflectionClass("{$v['rtype']}DNSRecord");
				if ($v['rtype'] != 'MX')
					$c = $reflection->newInstance($key, $value, $v["ttl"]);
				else
					$c = $reflection->newInstance($key, $value, $v["ttl"], $v["rpriority"]);
				
				if ($c->__toString() == "")
					$err = array_merge($GLOBALS['warnings'], $err);
				else
					$db->Execute("INSERT INTO default_records SET clientid=?, `rtype`=?, `ttl`=?, `rpriority`=?, `rvalue`=?, `rkey`=?", array($_SESSION['uid'], $v["rtype"], $v["ttl"], $v["rpriority"], $v["rvalue"], $v["rkey"]));
			}
		}
		
		if (count($err) == 0)
		{
			$okmsg = _("Default records successfully changed");
			CoreUtils::Redirect("default_records.php");
		}
	}
	
	if ($_SESSION["uid"] == 0)	
		$display["zone"]["records"] = $db->GetAll("SELECT * FROM default_records WHERE clientid='0'");
	else
		$display["zone"]["records"] = $db->GetAll("SELECT * FROM default_records WHERE clientid=?", array($_SESSION["uid"]));
		
	$display["add"] = array(1, 2, 3, 4, 5);
		
	$display["help"] = _("You can use a %hostname% tag, which will be replaced with full zone hostname.");
	
	require("src/append.inc.php"); 
?>