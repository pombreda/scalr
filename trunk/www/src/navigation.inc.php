<?php
 
	//include("src/prepend.inc.php");
	require_once (dirname(__FILE__) . "/../../src/class.XmlMenu.php");
	$Menu = new XmlMenu();
    
    if ($_SESSION["uid"] == 0)
    	$Menu->LoadFromFile(dirname(__FILE__)."/../../etc/admin_nav.xml");
    else
    	$Menu->LoadFromFile(dirname(__FILE__)."/../../etc/client_nav.xml");  
    	
    if ($_SESSION["uid"] != 0)
    {   	    			
		// creates path for  user menu cash files in session		
		$menuDirectory = dirname(__FILE__)."/../../cache/menu";

		foreach ($GLOBALS["db"]->GetAll("SELECT name, id  FROM farms WHERE clientid=?", array($_SESSION['uid'])) as $row)
		{
		    $farm_info[] = array(
		    	'name' =>$row['name'], 
		    	'id' => $row['id']
		    );
		    // if farms list changes - file name also changes
		    $farmCrc32String .= $row['name'].$row['id'];
		}   			
		
		$farmCrc32String = crc32($farmCrc32String);		
		$xmlUserFileName = "menu_{$_SESSION['uid']}_{$farmCrc32String}.xml";
					
		$filesArray = array();
	
		// system("rm -rf $menuDirectory");  	// delete menu directory if you need to update menu		
		
		if(!file_exists($menuDirectory))
			mkdir($menuDirectory, 0777);			
	    else  // get the list of directory files
	    {			    	
	    	$filesArray = glob("{$menuDirectory}/menu_*.xml");
	  	    	    
	   		for($i = 0; $i < count($filesArray); $i++)    		
    			$filesArray[$i] = basename($filesArray[$i]);    		    		
	    }	    	   
	    
	    $currentUserFileInfo = null;
	    
	    foreach($filesArray as $fileName)
	    {
	    	// $currentUserFileInfo[1] - user ID
	    	// $currentUserFileInfo[2] - $farmCrc32String code like  "123456789.xml"	    		   	
	    	$currentUserFileInfo = explode("_",$fileName);	

	    	// updates "menu" cache files for user
	    	if(($currentUserFileInfo[1] == $_SESSION['uid']) &&  // current user...
	    	 	($currentUserFileInfo[2] != "{$farmCrc32String}.xml")) // has another(old) file    
	    	 	unlink("{$menuDirectory}/{$fileName}");  
	        	
		    if($fileName == $xmlUserFileName)	    	
	    		$userFileExists = true;
	    }
	    
	    if($userFileExists)
	    	$Menu->LoadFromFile("{$menuDirectory}/{$xmlUserFileName}");
	    else	
	    {    	
	    	$Menu->LoadFromFile(dirname(__FILE__)."/../../etc/client_nav.xml");		    		    	
	    	
	    	// get XML document to add new children as farms names
	    	$clientMenu = $Menu->GetXml();   
		    	
			// creates a list of farms for server farms in main menu
			$nodeServerFarms = $clientMenu->xpath("//node[@id='server_farms']");			
			
			if(count($farm_info) > 0)
				$nodeServerFarms[0]->addChild('separator');
			
			foreach($farm_info as $farm_row)
			{			
				$farmList = $nodeServerFarms[0]->addChild('node');			
				$farmList->addAttribute('title', $farm_row['name']);	
						
				$itemFarm = $farmList->addChild('item','Manage');
					$itemFarm->addAttribute('href', "http://{$_SERVER['HTTP_HOST']}/farms_view.php?farmid={$farm_row['id']}");
				$itemFarm = $farmList->addChild('separator');			
				$itemFarm = $farmList->addChild('item','Roles');
					$itemFarm->addAttribute('href', "http://{$_SERVER['HTTP_HOST']}/roles_view.php?farmid={$farm_row['id']}");								
				$itemFarm = $farmList->addChild('item','Instances');
					$itemFarm->addAttribute('href', "http://{$_SERVER['HTTP_HOST']}/instances_view.php?farmid={$farm_row['id']}");					
				$itemFarm = $farmList->addChild('item','Applications');
					$itemFarm->addAttribute('href', "http://{$_SERVER['HTTP_HOST']}/sites_view.php?farmid={$farm_row['id']}");
											
			}
	    }			

		$Menu->WtiteXmlToFile("{$menuDirectory}/{$xmlUserFileName}");			
    }

    $display["menuitems"] = json_encode($Menu->GetExtJSMenuItems());
    
?>