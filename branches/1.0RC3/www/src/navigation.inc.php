<?php
	if (!$get_searchpage)
    	$XMLNav = new XMLNavigation();
    else
    	$XMLNav = new XMLNavigation($get_searchpage);
    	
        if ($_SESSION["uid"] == 0)
			$XMLNav->LoadXMLFile(dirname(__FILE__)."/../../etc/admin_nav.xml");
        else 
            $XMLNav->LoadXMLFile(dirname(__FILE__)."/../../etc/client_nav.xml");
    
    //
    // Add languages to menu
    $DOMLang = new DOMDocument();
    $DOMLang->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?><menu></menu>");
    $LangRoot = $DOMLang->documentElement;
    
    // Settings Node
    $node_Settings = $DOMLang->createElement("node");
    $node_Settings->setAttribute("title", "Settings");
    $LangRoot->appendChild($node_Settings);
    
    // Language Node
    $node = $DOMLang->createElement("node");
    $node->setAttribute("title", "Language");
    $node_Settings->appendChild($node);
    
    foreach ((array)$display["languages"] as $k=>$lng)
    {
        if ($lng)
        {
            $item = $DOMLang->createElement("item");
            $item->setAttribute("href", "index.php?lang={$lng["name"]}");
            $item->nodeValue = $lng["language"];
            $node->appendChild($item);
        }
    }	
    $XMLNav->AddNode($LangRoot, $XMLNav->XML->documentElement);
    
    $XMLNav->Generate();
    	
    $display["dmenu"] = $XMLNav->DMenu;
    
    // Index page menu
    if (NOW == "index")
    	if (!$get_searchpage)
    		$display["index_menu"] = $XMLNav->IMenu;
    	else
    	{
    		$display["index_menu"] = $XMLNav->SMenu;
    		$display["title"] = "Search results for '{$get_searchpage}'";
    	}
?>