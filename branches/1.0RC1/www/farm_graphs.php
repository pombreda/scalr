<?
    require("src/prepend.inc.php"); 
    
    if ($_SESSION['uid'] == 0)
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($req_id));
    else 
        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND clientid=?", array($req_id, $_SESSION['uid']));

    if (!$farminfo)
        UI::Redirect("farms_view.php");
        
    header("Content-type: image/png");
    header('Pragma: private');
	header('Cache-control: private, must-revalidate');
    
    $req_img = preg_replace("/[^A-Za-z0-9-_]+/", "", $req_img);
    $req_type = preg_replace("/[^A-Za-z0-9-_]+/", "", $req_type);
    
    @readfile(APPPATH."/data/{$farminfo['id']}/graphics/{$req_img}/{$req_type}.gif");
?>