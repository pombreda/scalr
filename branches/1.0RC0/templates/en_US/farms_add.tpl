{include file="inc/header.tpl" noheader=1}


    <link rel="STYLESHEET" type="text/css" href="/css/dhtmlXTree.css">
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXTree.js"></script>
    <script language="javascript" type="text/javascript" src="/js/dhtmlxtree/dhtmlXCommon.js"></script>
    
    <!-- 
    <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.5.2/build/fonts/fonts-min.css" />
	<script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/yahoo-dom-event/yahoo-dom-event.js"></script>
	<script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/dragdrop/dragdrop-min.js"></script>
	<script type="text/javascript" src="http://yui.yahooapis.com/2.5.2/build/slider/slider-min.js"></script>
    -->
    
    <link rel="stylesheet" type="text/css" href="/js/FC_TrackBar/trackbar.css" />
	<script type="text/javascript" src="/js/FC_TrackBar/trackbar.js"></script>
     
    <br />
    <table width="100%" cellpadding="10" cellspacing="0" border="0" style="height:100%">
        <tr valign="top">
            <td width="300" valign="top">
                {include file="inc/table_header.tpl" nofilter=1}
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                	<tr valign="top">
                		<td>
                		    <div style="padding:5px;">
                		      <div id="inventory_tree" style="width:300px;height:500px;"></div>
                		    </div>                            
                            <div id="AttachLoader" style="display:none;text-align:center;padding-top:250px;background-color:#f4f4f4;position:absolute;top:94px;left:23px;width:400px;height:262px;">
                		      <img src="/images/snake-loader.gif" style="vertical-align:middle;display:;"> Processing...
                		    </div>
                            <script language="Javascript" type="text/javascript">    
                            	  
                            	tree = new dhtmlXTreeObject($('inventory_tree'),"100%","100%",0);
                                tree.setImagePath("images/dhtmlxtree/csh_vista/");
                                tree.enableCheckBoxes(true);
                                tree.enableThreeStateCheckboxes(true);
                                
                                sid = '{$sid}';
                                stype = '{$stype}';
                                
                                tree.enableDragAndDrop(false);
                                tree.setXMLAutoLoading("farm_amis.xml?farmid={$id}");
            	                tree.loadXML("farm_amis.xml?farmid={$id}");
            	                            	                
            	                {literal}
            	                
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
            	                    if (state == 1)
            					    {
            					        if ($("item_"+nodeid))
            					           return true;
            					        
            					        if ($('no_servers').style.display == '')
            					           $('no_servers').style.display = "none";
            					           
            					        var arch = tree.getUserData(nodeid, "Arch");
            					           
            					        tr = document.createElement("TR");
            					        tr.id = "item_"+nodeid;
            					        td1 = document.createElement("TD");
            					        td1.nowrap = "nowrap";
            					        td1.className = "Item";
            					        td1.vAlign = "middle";
            					        td1.innerHTML = tree._globalIdStorageFind(nodeid).label+"<input type='hidden' name='ami_id[]' value='"+nodeid+"'>";
            					        tr.appendChild(td1);
            					        
            					        var attachSlider = false;
            					        
            					        var k = ['minCount', 'maxCount', 'scaleLA', 'availZone', 'iType'];
            					        for(var i = 0; i < k.length; i++)
            					        {
                					        td0 = document.createElement("TD");
                					        td0.nowrap = "nowrap";
                					        td0.className = "Item";
                					        td0.vAlign = "middle";
                					        
                					        if (k[i] == 'scaleLA')
                					        {
                					        	td0.align = "center";
                					        	td0.innerHTML = '<div id="'+nodeid+'"></div>' +
                					        	'<input type="hidden" id="minLA_'+nodeid+'" name="minLA[]" value="">'+
                					        	'<input type="hidden" id="maxLA_'+nodeid+'" name="maxLA[]" value="">'+
                					        	'';
                					        	attachSlider = true;
                					        }
                					        else if (k[i] == 'availZone')
                					        {
                					        	td0.innerHTML = '<select name="'+k[i]+'[]" class="text">'+
                					        	{/literal}
                                    			{section name=zid loop=$avail_zones}
                                    				{if $avail_zones[zid] == ""}
                                    				'<option value="">Choose randomly</option>'+
                                    				{else}
		                                    		'<option value="{$avail_zones[zid]}">{$avail_zones[zid]}</option>'+
		                                    		{/if}
		                                    	{/section}
		                                    	{literal}
		                                    	'</select>';
                					        }
                					        else if (k[i] == 'iType')
                					        {
                					        	if (arch == 'i386')
                					        	{
	                					        	td0.innerHTML = '<select name="'+k[i]+'[]" class="text">'+
	                					        	{/literal}
	                                    			{section name=tid loop=$32bit_types}
			                                    		'<option value="{$32bit_types[tid]}">{$32bit_types[tid]}</option>'+
			                                    	{/section}
			                                    	{literal}
			                                    	'</select>';
			                                    }
			                                    else
			                                    {
			                                    	td0.innerHTML = '<select name="'+k[i]+'[]" class="text">'+
	                					        	{/literal}
	                                    			{section name=tid loop=$64bit_types}
			                                    		'<option value="{$64bit_types[tid]}">{$64bit_types[tid]}</option>'+
			                                    	{/section}
			                                    	{literal}
			                                    	'</select>';
			                                    }
                					        }
                					        else
                					        {
                					        	td0.align = "center";
                					        	td0.innerHTML = '<input type="text" size="2" class="text" name="'+k[i]+'[]" value="1">';
                					        }
                					        tr.appendChild(td0);
                					        
                					        if (attachSlider == true)
                					        {
                					        	window.setTimeout(function(){
	                					        	trackbar.getObject(nodeid, true).init({
														onMove: function()
														{
															try
															{
																$('minLA_'+this.id).value = this.leftValue;
																$('maxLA_'+this.id).value = this.rightValue;
															}
															catch(e){}
														}
													}, $(nodeid));
                					        	}, 200);
                					        	
                					        	attachSlider = false;
                					        }
            					        }
            					        
            					        $('table_body').insertBefore(tr, $('last'));
            					        
            					        if (tree.getUserData(nodeid, 'alias') == "mysql")
            					        {
            					            $('mysql_settings').style.display = "";
            					        }
            					    }
            					    else
            					    {
            					        if (!$("item_"+nodeid))
                                            return true;
            					        
                                        $('table_body').removeChild($('item_'+nodeid));
            					        
            					        if ($('table_body').childNodes.length == 3)
            					           $('no_servers').style.display = "";
            					           
            					        if (tree.getUserData(nodeid, 'alias') == "mysql")
            					        {
            					            $('mysql_settings').style.display = "none";
            					        }
            					    }
            	                }
            	                
            	                            	                            	                
            	                {/literal}
                            </script>
                		</td>
                	</tr>
                </table>
                {include file="inc/table_footer.tpl" disable_footer_line=1}
            </td>
            <td style="padding:0px;margin:0px;" valign="top">
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:0px;margin:0px;">
                    <tr>
                        <td style="padding:0px;margin:0px;padding-top:10px;">
                            <form id="frm_farm" name="frm_farm" action="" style="padding:0px;margin:0px;" method="post">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="farmid" value="{$id}">
                            <input type="hidden" name="ami_id" value="{$ami_id}">
                            {include file="inc/table_header.tpl" nofilter=1}
                        		{include file="inc/intable_header.tpl" header="Farm information" color="Gray"}
                            	<tr>
                            		<td width="20%">Name:</td>
                            		<td><input type="text" class="text" name="name" value="{$farminfo.name}" /></td>
                            	</tr>
                                {include file="inc/intable_footer.tpl" color="Gray"}
                                
                                
                                {include file="inc/intable_header.tpl" intableid='mysql_settings' visible=$mysql_visible header="Settings for mysql role" color="Gray"}
                            	<tr>
                            		<td colspan="2">Rebundle and save instance snapshot of mysql role every: <input type="text" size="3" class="text" name="mysql_rebundle_every" value="{if $farminfo.mysql_rebundle_every}{$farminfo.mysql_rebundle_every}{else}48{/if}" /> hours</td>
                            	</tr>
                            	<tr>
                            		<td colspan="2"><input style="vertical-align:middle;" type="checkbox" {if $farminfo.mysql_bcp == 1}checked{/if} name="mysql_bcp" value="1"> Periodically backup databases every: <input type="text" size="3" class="text" name="mysql_bcp_every" value="{if $farminfo.mysql_bcp_every}{$farminfo.mysql_bcp_every}{else}180{/if}" /> minutes</td>
                            	</tr>
                                {include file="inc/intable_footer.tpl" color="Gray"}
                        	{include file="inc/table_footer.tpl" disable_footer_line=1}
                            
                            
                            {include file="inc/table_header.tpl" nofilter=1 table_header_text="Farm servers"}
                        	<table class="Webta_Items_farm" border="0" style="padding:0px;" rules="groups" frame="box" width="100%" cellpadding="2" id="Webta_Items_">
                            	<thead>
                            	<tr>
                            		<th width="" align="left">Role</th>
                            		<th align="left" width="1%" nowrap>Min Instances</th>
                            		<th align="left" width="1%" nowrap>Max Instances</th>
                            		<th align="center" style="text-align:center;" width="240">Scale LA *</th>
                            		<th align="left" width="1%" nowrap>Avail zone</th>
                            		<th align="left" width="1%" nowrap>Type</th>
                            	</tr>
                            	</thead>
                            	<tbody id="table_body">
                            	<tr>
                            		<td class="Item" colspan="7" align="center" height="10"> </td>
                            	</tr>
                            	{section name=id loop=$servers}
                                <tr id="item_{$servers[id].ami_id}">
                                    <td class="Item"><input type='hidden' name='ami_id[{$servers[id].id}]' value='{$servers[id].ami_id}'>{$servers[id].role}</td>
                                    <td class="Item" align="center"><input type="text" size="2" class="text" name="minCount[{$servers[id].id}]" value="{$servers[id].min_count}"></td>
                                    <td class="Item" align="center"><input type="text" size="2" class="text" name="maxCount[{$servers[id].id}]" value="{$servers[id].max_count}"></td>
                                    
                                    <td class="Item" align="center">
                                    	<div id="{$servers[id].id}">
                                    	<script type="text/javascript">
											trackbar.getObject('{$servers[id].id}').init({literal}{{/literal}
												leftValue: {$servers[id].min_LA},
												rightValue: {$servers[id].max_LA},
												{literal}
												onMove: function()
												{
													try
													{
														$('minLA_'+this.id).value = this.leftValue;
														$('maxLA_'+this.id).value = this.rightValue;
													}
													catch(e){}
												}
											}{/literal});
										</script>
										</div>
										<input type='hidden' id='minLA_{$servers[id].id}' name='minLA[{$servers[id].id}]' value='{$servers[id].min_LA}'>
										<input type='hidden' id='maxLA_{$servers[id].id}' name='maxLA[{$servers[id].id}]' value='{$servers[id].max_LA}'>
                                    </td>
                                    <td class="Item">
                                    	<select name="availZone[{$servers[id].id}]" class="text">
                                    		{section name=zid loop=$avail_zones}
                                    			{if $avail_zones[zid] == ""}
                                    			<option {if $servers[id].avail_zone == ""}selected{/if} value="">Choose randomly</option>
                                    			{else}
                                    			<option {if $servers[id].avail_zone == $avail_zones[zid]}selected{/if} value="{$avail_zones[zid]}">{$avail_zones[zid]}</option>
                                    			{/if}
                                    		{/section}
                                    	</select>
                                    </td>
                                    <td class="Item">
                                    	<select name="iType[{$servers[id].id}]" class="text">
                                    		{section name=zid loop=$servers[id].types}
                                    			<option {if $servers[id].instance_type == $servers[id].types[zid]}selected{/if} value="{$servers[id].types[zid]}">{$servers[id].types[zid]}</option>
                                    		{/section}
                                    	</select>
                                    </td>
                                </tr>
                            	{/section}
                            	<tr id="no_servers" style="display:{if $servers|@count == 0}{else}none{/if};">
                            		<td colspan="11" align="center">No servers assigned to farm</td>
                            	</tr>
                            	<tr id="last">
                            		<td colspan="7" align="center" height="10"> </td>
                            	</tr>
                            	</tbody>
                        	</table>
                        	{include 
								file="inc/table_footer.tpl" 
								colspan=9 
								button_js_name='Save' 
								button_js=1 
								button_js_action='SubmitForm();' 
								loader='Building farm... <span style="color:red;">Please do not close this page!</span>'
								extra_html = '<hr size="1" style="color:#cccccc;">
								<div>
				                * Agenda: 
					                <div>
						                <div style="padding:2px;"><div style="width:10px;height:10px;background-color:#9ef19c;display:block;float:left;margin-top:3px;">&nbsp;</div>&nbsp;&nbsp;- normal</div>
						                <div style="padding:2px;"><div style="width:10px;height:10px;background-color:#f7f9c8;display:block;float:left;margin-top:3px;">&nbsp;</div>&nbsp;&nbsp;- recommended only if you do not expect frequent load spikes/drops</div>
						                <div style="padding:2px;"><div style="width:10px;height:10px;background-color:#f7b8b8;display:block;float:left;margin-top:3px;">&nbsp;</div>&nbsp;&nbsp;- the difference is too small. Scaling may act unexpectedly (instances will be launched and terminated too frequently)</div>
						                <div style="clear:both;"></div>
						            </div>
				                </div>
								'
							}
                        	</form>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
        
        
    <script language="Javascript">
    
    $('button_js').style.display='';
	
	{literal}
	function SubmitForm()
	{
	   $('button_js').disabled = true;
	   $('btn_loader').style.display = '';
	   
	   document.getElementById("frm_farm").submit();   
	}
	{/literal}
	</script>
{include file="inc/footer.tpl"}
