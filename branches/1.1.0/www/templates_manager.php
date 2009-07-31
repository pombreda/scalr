<? 
	require_once('src/prepend.inc.php');
	
	if ($_SESSION["uid"] != 0)
	   UI::Redirect("index.php");
	
	if ($get_action == "delete")
	{
	    $req_dirName = str_replace(".", "", $req_dirName);
	    $req_templateName = str_replace("..", "", $req_templateName);
	    
	    @unlink("{$Smarty->template_dir}/{$req_dirName}/{$req_templateName}");
	    $okmsg = "Template successfully deleted";
	    $req_dir = $req_dirName;
	}
	
	if ($_POST)
	{
		$i = 0;
		foreach ($post_body as $key=>$val)
		{
			$handle = @fopen ("{$Smarty->template_dir}/{$req_dir}/{$key}", "w");
			if ($handle)
			{
                @fwrite($handle, stripslashes($val));
			    @fclose ($handle);
			}
			else 
                $err[] = "Cannot modify this template. It must be writable by webserver.";
		}
		
		if (!$err)
		{
            $okmsg = "Template file saved";
            redirect("templ_view.php");	
		}
	}
	
	$req_dir = str_replace(".", "", $req_dir);
	$req_cd = str_replace(".", "", $req_cd);
	$req_explode = str_replace("..", "", $req_explode);
	
	if ($req_dir == "up")
	{
	    $chunks = explode("/", $req_cd);
	    array_pop($chunks);
	    $req_dir = @implode("/", $chunks);
	}
	
	if ($req_dir != '')
		$display["folders"][] = array("name" => "up", "curdir" => $req_dir);
	
	if ($req_explode)
	{
		$display["dir"] = $req_dir;
		$display["file"] = $req_explode;
	}
		
	if ($handle = @opendir("{$Smarty->template_dir}/{$req_dir}")) 
	{
		while (false !== ($file = @readdir($handle))) 
		{
			if ($file != "." && $file != ".." && $file != ".svn") 
			{
				if (is_file("{$Smarty->template_dir}/{$req_dir}/{$file}"))
				{
					$image = (stristr($file, ".eml")) ? "mail_tpl" : "layout_tpl";
					$type = (stristr($file, ".eml")) ? "E-mail" : "Layout";
					
					$display["files"][] = array("name" => $file, "image"=>$image, "type"=>$type);
					if ($req_explode == $file)
						$display["content"] = @file_get_contents("{$Smarty->template_dir}/{$req_dir}/{$file}");
				}
				else
					$display["folders"][] = array("name" => trim("{$req_dir}/{$file}", "/"));
			}
		}
		
		@closedir($handle);
	}
	else
		$err[] = "Cannot read templates directory! Check templates folder permissions.";
	
	
	$display["dir"] = $req_dir;
	
	$display["title"] = "Settings > Templates";
	
	require_once ("src/append.inc.php");
?>
          