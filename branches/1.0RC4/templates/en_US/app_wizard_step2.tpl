{include file="inc/header.tpl"}
	<div id="role_info_popup" align="left" style="display:none;">
    	<div id="popup_contents" style="margin-top:5px;margin-left:5px;">
    		
    	</div>
	</div>
    <link rel="STYLESHEET" type="text/css" href="/css/dhtmlXTree.css">
    <link href="css/popup.css" rel="stylesheet" type="text/css" />
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXTree.js"></script>
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXCommon.js"></script>
	<script type="text/javascript" src="js/class.NewPopup.js"></script>
	{include file="inc/table_header.tpl"}
    {include file="inc/intable_header.tpl" header="Step 2 - New application will use the following roles" color="Gray"}
	<tr valign="top">
		<td colspan="6">
			<div style="padding:5px;">
				<div id="inventory_tree" style="width:250px;height:300px;overflow-x:hidden;"></div>
			</div>
			<script language="Javascript" type="text/javascript">
			var NoCloseButton = false;
			{literal}
				Event.observe(window, 'load', function(){
					window.popup = new NewPopup('role_info_popup', {target: '', width: 270, height: 120, selecters: new Array()});
				});
				
				function ShowDescriptionPopup(event, id, obj)
				{
					var event = event || window.event;
					
					var pos = Position.positionedOffset(obj);
					
					pos[0] = Event.pointerX(event);
					pos[1] = Event.pointerY(event);
					
					$('popup_contents').innerHTML = $('role_description_tooltip_'+id).innerHTML;
					
					popup.raisePopup(pos);
				}
				
			{/literal}
			
			tree = new dhtmlXTreeObject($('inventory_tree'),"100%","100%",0);
			tree.setImagePath("images/dhtmlxtree/csh_vista/");
			tree.enableCheckBoxes(true);
			tree.enableThreeStateCheckboxes(true);
            
            tree.enableDragAndDrop(false);
            tree.setXMLAutoLoading("farm_amis.xml?farmid=");
            tree.loadXML("farm_amis.xml?farmid=");
            
            {literal}
			tree.setOnSelectStateChange(function(itemId)
			{
				return false;
			});
            	  
           	//TODO: Check amis: $amis
            	                            	                
			tree.attachEvent("onCheck", function(itemId, state)
            {            	                               	                               	                    
				tree.setCheck(itemId,state);
                					    
				//Get Top level item
				var item = tree._globalIdStorageFind(itemId);
				while (tree.getLevel(item.id) > 1)
					item = tree._globalIdStorageFind(tree.getParentId(item.id));
                        
				if (tree.getUserData(itemId,"isFolder") == 1)
				{
					var childs = tree.getAllSubItems(itemId).split(',');
                					    
					for (key in childs)
					{
						if (typeof(childs[key]) == "string")
						{
							if (tree.getUserData(childs[key],"isFolder") != 1)
								ManageTable(childs[key], state)
						}
					}
				}
				else
					ManageTable(itemId, state);
			});
            	                
			function ManageTable(nodeid, state)
			{            	                    
				var arch = tree.getUserData(nodeid, "Arch");
				var alias = tree.getUserData(nodeid, 'alias');
				var name = tree._globalIdStorageFind(nodeid).label;
				
				if (state == 1)
				{            					           
					
					var elem = document.createElement('INPUT');
					elem.type = 'hidden';
					elem.name = 'amis[]';
					elem.id = 'amis_input_'+nodeid;
					elem.value = nodeid;
					
					
					$('frm').insert("<input id='amis_input_"+nodeid+"' type='hidden' name='amis[]' value='"+nodeid+"'>");
					
					//appendChild(elem);
				}
				else
				{            					        
					$('frm').removeChild($('amis_input_'+nodeid));
				}
				
				if (alias == "mysql")
				{
					SetMysqlRolesAccess(state, nodeid);
				}
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
			{/literal}
			</script>
		</td>
	</tr>
{include file="inc/intable_footer.tpl" color="Gray"}
{section name=id loop=$roles_descr}
	{if $roles_descr[id].description}
	<span id="role_description_tooltip_{$roles_descr[id].ami_id}" style="display:none;">
		<div><span style="color:#888888;">Role name:</span> {$roles_descr[id].name}</div>
		<div><span style="color:#888888;">AMI ID:</span> {$roles_descr[id].ami_id}</div>
		<div><span style="color:#888888;">Description:</span><br> {$roles_descr[id].description}</div>
	</span>
	{/if}
{/section}
<input type="hidden" name="step" value="3">
<span id="elems_container">

</span>
{include file="inc/table_footer.tpl" button2=1 button2_name='Next'}
{include file="inc/footer.tpl"}