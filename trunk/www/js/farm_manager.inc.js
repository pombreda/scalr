	function ManageTable(nodeid, state)
	{            	                    
		if (state == 1)
		{            					                   					        
			RoleTabObject.AddRoleToFarm(nodeid);
        					        
			tree._selectItem(tree._globalIdStorageFind(nodeid), true);
        	
        	RoleTabObject.SetCurrentRoleObject(nodeid, true);
        					        
			if (tree.getUserData(nodeid, 'alias') == "mysql")
			{
				SetMysqlRolesAccess(1, nodeid);
			}
		}
		else
		{            					        
			RoleTabObject.RemoveRoleFromFarm(nodeid);
        					        
			if (tree.getSelectedItemId() == nodeid)
			{
				tree._unselectItems();
				SetActiveTab('general');
				$('tab_roles').style.display = 'none';
			}
        					           
			if (tree.getUserData(nodeid, 'alias') == "mysql")
			{
				SetMysqlRolesAccess(0, nodeid);
			}
		}
	}
    
    function GetRoleObjectFromTree(ami_id)
    {
    	var arch = tree.getUserData(ami_id, "Arch");
		var alias = tree.getUserData(ami_id, 'alias');
		var description = tree.getUserData(ami_id, 'description');
		var type = tree.getUserData(ami_id, 'type');
		var author = tree.getUserData(ami_id, 'author');
       					        
		var name = tree._globalIdStorageFind(ami_id).label;
       					        
		return new FarmRole(name, ami_id, alias, arch, description, type, author);
    }
        	                
	function SetMysqlRolesAccess(disabled, nodeid)
	{
		var childs = tree.getAllChildless().split(',');
            					    
		for (key in childs)
		{
			if (tree.getUserData(childs[key], "alias") == "mysql" && childs[key] != nodeid)
			{
				tree.disableCheckbox(childs[key], disabled);
			}
		}
	}
      
    function HideIntableTabs(skip_tab)
    {
    	var elems = $('itabs_container').select('[class="InTableTab"]');
		elems.each(function(item){    
			if (item.id != 'itab_'+skip_tab)
				item.style.display = 'none';
		});
    }
    
    function ShowIntableTabs()
    {
    	var elems = $('itabs_container').select('[class="InTableTab"]');
		elems.each(function(item){ 
			item.style.display = '';
		});
    }
        
    function OnTabChanged_i(id)
    {
    	if (id == 'scripts')
    		$('intable_top_empty_tr').style.display = 'none';
    	else
    		$('intable_top_empty_tr').style.display = '';
    }
        	                
	function OnTabChanged(current_active_tab)
	{
        	                	
	}