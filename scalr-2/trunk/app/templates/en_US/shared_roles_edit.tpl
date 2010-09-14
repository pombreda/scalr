{include file="inc/header.tpl"}
    <script language="Javascript" src="/js/class.DataFormField.js"></script>
    <script language="Javascript">
    
    {literal}
    
    function AddRule()
    {        
        if (Ext.get('add_portfrom').dom.value == "" || parseInt(Ext.get('add_portfrom').dom.value) <= -2 || parseInt(Ext.get('add_portfrom').dom.value) > 65536)
        {
            alert("'From port' must be a number between 1 and 65536");
            return false;
        }
        
        if (Ext.get('add_portto').dom.value == "" || parseInt(Ext.get('add_portto').dom.value) <= -2 || parseInt(Ext.get('add_portto').dom.value) > 65536)
        {
            alert("'To port' must be a number between 1 and 65536");
            return false;
        }
        
        if (Ext.get('add_ipranges').dom.value == "")
        {
            alert("'IP ranges' required");
            return false;
        }
        
        container = document.createElement("TR");
        container.id = "rule_"+parseInt(Math.random()*100000);
        
        //first row
        td1 = document.createElement("TD");
        td1.innerHTML = Ext.get('add_protocol').dom.value;
        container.appendChild(td1);
        
        //second row
        td2 = document.createElement("TD");
        td2.innerHTML = Ext.get('add_portfrom').dom.value;
        container.appendChild(td2);
        
        //third row
        td3 = document.createElement("TD");
        td3.innerHTML = Ext.get('add_portto').dom.value;
        container.appendChild(td3);
        
        //forth row
        td4 = document.createElement("TD");
        td4.innerHTML = Ext.get('add_ipranges').dom.value;
        container.appendChild(td4);
        
        rule = Ext.get('add_protocol').dom.value+':'+Ext.get('add_portfrom').dom.value+":"+Ext.get('add_portto').dom.value+":"+Ext.get('add_ipranges').dom.value;
        
        // fifth row
        td5 = document.createElement("TD");
        td5.innerHTML = "<input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteRule(\""+container.id+"\")'><input type='hidden' name='rules[]' value='"+rule+"'>";
        container.appendChild(td5);
        
        Ext.get('rules_container').dom.appendChild(container);
        
        Ext.get('add_portfrom').dom.value = "";
        Ext.get('add_portto').dom.value = "";
        Ext.get('add_ipranges').dom.value = "0.0.0.0/0";
        Ext.get('add_protocol').dom.value = 'tcp';
    }
    
    function DeleteRule(id)
    {
        Ext.get(id).dom.parentNode.removeChild(Ext.get(id).dom);
    }
    {/literal}
    </script>
    {literal}
<script language="Javascript" type="text/javascript">

function CheckType(type)
{
    if (type == 'SELECT')
    {
        Ext.get('selectinfo').dom.style.display = '';
    }
    else
    {
        Ext.get('selectinfo').dom.style.display = 'none';
    }
}

{/literal}
var Items = new Array();
var Num = {$num|default:0};
{literal}

function AddItem(item_key, item_name, item_isdef)
{
    if (!item_key)
    	var item_key = Ext.get('ikey').dom.value;
    	
    if (!item_name)
    	var item_name = Ext.get('iname').dom.value;
    	
    if (!item_isdef)
    	var item_isdef = Ext.get('idef_add').dom.checked
    	
    
    if (item_key == '' || item_name == '')
        return "";
    
    var uniqid = parseInt((Math.random()*100000))+"."+parseInt((Math.random()*100000));
    
    var index = Items.length;
    
    Items[index] = [item_key, item_name, uniqid];
    Num++;
    
    cont = document.createElement("DIV");
    cont.style.width = '490px';
    cont.className = "select_item";
       
    
    dv_key = document.createElement("DIV");
    dv_key.className = 'item_key';
    dv_key.innerHTML = item_key;
    cont.appendChild(dv_key);
    
    dv_name = document.createElement("DIV");
    dv_name.className = 'item_value';
    dv_name.innerHTML = item_name;
    cont.appendChild(dv_name);
    
    dv_def = document.createElement("DIV");
    dv_def.className = 'item_def';
    dv_def.align = 'center';
    
    var ischecked = item_isdef;
    
    if (ischecked)
    {
    	checked = 'checked';
    	Ext.get('select_properties').select('.select_one').each(function(item){
			tmp = item.select('[name="idef"]')
			tmp[0].checked = false;
		});
    }
    else
    	checked = '';
    
    if (Ext.get('allow_multiplechoise').dom.checked)
    {
    	visible_one = 'none';
    	visible_many = '';
    }
    else
    {
    	visible_one = '';
    	visible_many = 'none';
    }
    
    dv_def.innerHTML = ""+
    		"<span class=\"select_one\" id=\"select_one_"+uniqid+"\" style=\"display:"+visible_one+";\">"+
         	"	<input type=\"radio\" name=\"idef\" "+checked+" id=\"idef_rdo_"+index+"\" value=\"1\">"+
         	"</span>"+
         	"<span class=\"select_many\" id=\"select_many_"+uniqid+"\" style=\"display:"+visible_many+";\">"+
         	"	<input type=\"checkbox\" name=\"idef\" "+checked+" id=\"idef_chk_"+index+"\" value=\"1\">"+
         	"</span>"+
    "";
    
    cont.appendChild(dv_def);
    Ext.get('idef_add').dom.checked = false;
    
    img = document.createElement("IMG");
    img.style.verticalAlign = 'middle';
    img.src = "images/delete.png";
    img.id = index;
    
    dv_img = document.createElement("DIV");
    dv_img.className = 'item_delete';
    dv_img.align = 'center';
    dv_img.appendChild(img);
    cont.appendChild(dv_img);
    
    img.onclick = function()
    {
         Num--;
         Items[this.id] = false;
         this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);
         
         if (Num == 0)
         {
            Ext.get('no_items').dom.style.display = '';    
         }
    }
    
    Ext.get('Items').dom.appendChild(cont);
    Ext.get('no_items').dom.style.display = 'none';
    
    Ext.get('iname').dom.value = "";
    Ext.get('ikey').dom.value = "";
    
    Ext.get('idef_add').dom.checked = false;
}

function PrepareSubmit()
{
    var df_inp = document.createElement("INPUT");
    df_inp.type = 'hidden';
    df_inp.name = 'role_options_dataform';
    
    var fields = window.df.fields;
    var submit_fields = new Array();
    
    for (k in fields)
    {
    	if (typeof(fields[k]) == 'object')
    		submit_fields[submit_fields.length] = fields[k]; 
    }
    
    df_inp.value = Object.toJSON(submit_fields);
    document.forms[1].appendChild(df_inp);
    
    Ext.get('button_js').dom.disabled = true;
    
    document.forms[1].submit();
}

Ext.onReady(function()
{   
	 window.df_action = 'create';	 
	 window.df = new DataForm(Ext.get('role_data_form').dom);
	 
	 {/literal}
	 {if $role_options_dataform}
	 	var data_form = {$role_options_dataform|replace:"\'":"\\\'"};
	 	window.df.Load(data_form);
	 {/if}
	 {literal}

	 SetFieldForm();
	 AllowMultipleChoice(false);
}); 

function SetFieldForm()
{
	var items = Ext.get('fieldtype').dom.options;
	for(var i = 0; i < items.length;i++)
	{
		if (Ext.get('fieldtype').dom.options[i].selected == true)
		{
			Ext.get(Ext.get('fieldtype').dom.options[i].value+'_properties').dom.style.display = '';
			if (Ext.get('fieldtype').dom.options[i].value == 'select')
			{
				Ext.get('list_options').dom.style.display = '';
			}
			else
			{
				Ext.get('list_options').dom.style.display = 'none';
			}
		}
		else
		{
			Ext.get(Ext.get('fieldtype').dom.options[i].value+'_properties').dom.style.display = 'none';
		}
	}
}

function ResetFieldForm()
{
	Ext.get('fieldname').dom.value = "";
	Ext.get('fieldtype').dom.value = "text";
	Ext.get('fieldrequired').dom.checked = false;
	Ext.get('fieldname').dom.disabled = false;
	
	tmp = Ext.get('tab_contents_options').select('.fielddefval');
	tmp.each(function(item){
		item.value = "";
	});
	Ext.get('allow_multiplechoise').dom.checked = false;
	
	Items = new Array();
	
	var items = Ext.get('Items').select('.select_item');
	for (var i = 0; i < items.length; i++)
	{
		Ext.get('Items').dom.removeChild(items[i]);
	}
	
	Ext.get('no_items').dom.style.display = '';
	
	AllowMultipleChoice(false);
	
	SetFieldForm();
	
	Ext.get('field_buttons_add').dom.style.display = '';
	Ext.get('field_buttons_edit').dom.style.display = 'none';
}

function AllowMultipleChoice(value)
{
	if (value == true)
	{
		Ext.get('list_options').dom.select('.select_one').each(function(item){
			item.style.display = 'none';
			
			if (item.select("[name='idef']")[0].checked == true)
				Ext.get(item.select("[name='idef']")[0].id.replace('rdo', 'chk')).dom.checked = true;
			else
				Ext.get(item.select("[name='idef']")[0].id.replace('rdo', 'chk')).dom.checked = false;
		});
		
		Ext.get('list_options').select('.select_many').each(function(item){
			item.style.display = '';
		});
	}
	else
	{
		Ext.get('list_options').select('.select_one').each(function(item){
			item.style.display = '';
			
			var chk = false;
		
			if (item.select("[name='idef']")[0].checked == true && !chk)
			{
				Ext.get(item.select("[name='idef']")[0].id.replace('chk', 'rdo')).dom.checked = true;
				chk = true;
			}
			else
				Ext.get(item.select("[name='idef']")[0].id.replace('rdo', 'chk')).dom.checked = false;
			
		});
				
		Ext.get('list_options').select('.select_many').each(function(item){
			item.style.display = 'none';
		});
	}
}

function SetField()
{
	name = Ext.get('fieldname').dom.value;
	type = Ext.get('fieldtype').dom.value;
	required = Ext.get('fieldrequired').dom.checked;
	
	if (name == '')
	{
		alert("Field name required");
		return;
	}
	
	if (type == 'text' || type == 'textarea')
	{
		tmp = Ext.get(type+'_properties').dom.select('.fielddefval');
		def_val = tmp[0].value; 
	}
	else
		def_val = false;
		
	allow_multiplechoise = Ext.get('allow_multiplechoise').dom.checked;
		
	if (type == 'select')
	{
		options = Items;
		options.each(function(item){
		
			if (allow_multiplechoise)
				var container = Ext.get('select_many_'+item[2]).dom;
			else
				var container = Ext.get('select_one_'+item[2]).dom;
			
			var isdef = container.select('[name="idef"]')[0].checked;
			
			item[3] = isdef; 			
		});
		
		if (options.length == 0)
		{
			alert("For select field you should add at least one option");
			return;
		}			
	}
	else
		options = null;
	
	var field = new DataFormField(name, type, required, def_val, allow_multiplechoise, options);
	
	var result = false;
	if (window.df_action == 'edit')
	{
		result = window.df.UpdateField(field);
		
	}
	else if (window.df_action == 'create')
	{
		result = window.df.AddField(field);
	}
	
	if (res)
	{
		ResetFieldForm();
	}
}

</script>
<style>

.item_key
{
    padding:2px;float:left;width:150px;
}

.item_value
{
    padding:2px;float:left;width:150px;
}

.item_def
{
    padding:2px;float:left;width:75px;
}


.item_delete
{
    padding:2px;float:left;width:46px;
    cursor:pointer;
}

</style>
{/literal}
    <br>
	{include file="inc/table_header.tpl" nofilter=1 tabs=1}
		{include intable_classname="tab_contents" intableid="tab_contents_general" visible="" file="inc/intable_header.tpl" header="Role information" color="Gray"}
    	{if $ami_id}
    	<tr>
    		<td width="20%">AMI:</td>
    		<td>{$ami_id}<input type="hidden" name="ami_id" value="{$ami_id}"></td>
    	</tr>
    	<tr>
    		<td width="20%">Architecture:</td>
    		<td>{$arch}<input type="hidden" name="arch" value="{$arch}"></td>
    	</tr>
    	{else}
    	<tr>
    		<td width="20%">AMI:</td>
    		<td><input type="text" class="text" name="ami_id" value=""></td>
    	</tr>
    	<tr>
    		<td width="20%">Location:</td>
    		<td>
    			<select name="region" id="region" style="vertical-align:middle;">
					{foreach from=$regions name=id key=key item=item}
						<option {if $region == $key}selected{/if} value="{$key}">{$item}</option>
					{/foreach}
				</select>
    		</td>
    	</tr>
    	{/if}
		<tr>
    		<td width="20%">Role name:</td>
    		<td><input type="text" class="text" name="name" value="{$name}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Type:</td>
    		<td>
    			<select name="alias" class="text">
    				{section name=id loop=$aliases}
    					<option {if $alias == $aliases[id]}selected{/if} value="{$aliases[id]}">{$aliases[id]}</option>
    				{/section}
    			</select>
    		</td>
    	</tr>
    	<tr>
    		<td width="20%">Default mimimum LA for this role:</td>
    		<td><input type="text" class="text" name="default_minLA" value="{$default_minLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Default maximum LA for this role:</td>
    		<td><input type="text" class="text" name="default_maxLA" value="{$default_maxLA}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Stable:</td>
    		<td><input type="checkbox" {if $isstable || !$arch}checked="checked"{/if} name="isstable" value="1" /></td>
    	</tr>
    	<tr valign="top">
    		<td width="20%">Description:</td>
    		<td>
    			<textarea rows="5" cols="50" name="description" class="text">{$description}</textarea>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}

		<div id="tab_contents_options" class="tab_contents" style="padding:7px;display:none;">
		<p class="placeholder">
			These options will appear in the Parameters tab on farm/role edit Page.
			<a target="_blank" href="http://code.google.com/p/scalr/wiki/RoleOptions">How do I retrieve these values on my instances?</a>
		</p>
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td width="7" class="TableHeaderCenter_Gray"></td>
			<td class="Inner_Gray">
				<table width="100%" cellspacing="0" cellpadding="0" id="Webta_InnerTable_roleopts">
		        <tr>
		    		<td colspan="2">
		    			<table width="100%" cellspacing="0" cellpadding="0">
		    				<tr valign="top">
		    					<td width="50%" style="padding:10px;">
		    						<table width="100%" border="0" cellspacing="0" cellpadding="0">
		    							<tr>
											<td width="7"><div class="TableHeaderLeft_Gray"></div></td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">Field Name</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">Field type</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray">Required</div>
											</td>
											<td>
												<div id="webta_table_header" style="line-height:20px;" class="SettingsHeader_Gray"></div>
											</td>
											<td width="7"><div class="TableHeaderRight_Gray"></div></td>
										</tr>
										<tbody id="no_fields">
		    								<tr>
		    									<td colspan="10" align="center">No options defined</td>
		    								</tr>
		    							</tbody>
		    							<tbody id="role_data_form">
		    							
		    							</tbody>
		    						</table>
		    					</td>
		    					<td width="4" align="center" style="border-right:4px solid #cccccc;">
		    					&nbsp;
		    					</td>
		    					<td width="50%" style="padding-left:10px;padding-top:3px;padding-right:0px;">
		    						<div id="role_data_form_builder">
		    							{include file="inc/intable_header.tpl" no_first_row=1 header="Field" color="Gray"}
		    							<tr>
		    								<td colspan="2">
							    				<div style="padding-left:0px;">
							    					<div style="margin-top:0px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">Type:</div>
							    						<div style="float:left;line-height:25px;height:25px;">
							    							<select class="text" name="fieldtype" id="fieldtype" onChange='SetFieldForm()' style="vertical-align:middle;">
										    					<option value="text">Text</option>
										    					<option value="textarea">Textarea</option>
										    					<option value="select">List</option>
										    					<option value="checkbox">Boolean</option>
										    				</select>
							    						</div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div style="margin-top:5px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">Name:</div>
							    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="text" id="fieldname" class="text" name="fieldname" value=""></div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div style="margin-top:5px;">
							    						<div style="float:left;line-height:25px;height:25px;width:150px;">Required?</div>
							    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="checkbox" id="fieldrequired" name="fieldrequired" value="1"></div>
							    						<div style="clear:both;"></div>
							    					</div>
							    					<div id="text_properties" style="display:none;">
							    						<div style="margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">Default value:</div>
								    						<div style="float:left;line-height:25px;height:25px;"><input style="vertical-align:middle;" type="text" class="text" name="fielddefval" value=""></div>
								    						<div style="clear:both;"></div>
								    					</div>	
							    					</div>
							    					<div id="checkbox_properties" style="display:none;">
							    						<!-- TODO: -->	
							    					</div>
							    					<div id="textarea_properties" style="display:none;">
							    						<div style="margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">Default value:</div>
								    						<div style="float:left;margin-bottom:10px;"><textarea style="vertical-align:middle;" cols="50" rows="10" type="text" class="text" name="fielddefval" value=""></textarea></div>
								    						<div style="clear:both;"></div>
								    					</div>	
							    					</div>
							    					<div id="select_properties" style="display:none;">
							    						<div style="25px;margin-top:5px;">
								    						<div style="float:left;line-height:25px;height:25px;width:150px;">Allow multiple choice:</div>
								    						<div style="float:left;line-height:10px;">
								    							<input style="vertical-align:middle;margin-top:4px;" type="checkbox" onClick="AllowMultipleChoice(this.checked)" name="allow_multiplechoise" id="allow_multiplechoise" value="1">
								    						</div>
								    						<div style="clear:both;"></div>
								    					</div>
								    				</div>
							    				</td>
							    			</tr>
							    			{include file="inc/intable_footer.tpl" color="Gray"}
					    					{include intableid='list_options' no_first_row=1 visible='none' file="inc/intable_header.tpl" header="Options" color="Gray"}
					    					<tr>
					    						<td colspan="2">
						    					<div style="">
						    						<div style="float:left;">
						    							<div style="padding:2px;">
													         <div id="item1" style="width:440px;padding-left:0px;">
												                 <div style="padding:2px;float:left;width:150px;"><b>Value</b></div>
												                 <div style="padding:2px;float:left;width:150px;"><b>Name</b></div>
												                 <div style="padding:2px;float:left;width:75px;" align="center"><b>Default</b></div>
												                 <div style="padding:2px;float:left;width:auto;" align="center"><b>Delete</b></div>
													         </div>
													         <div id="Items" style="margin-left:0px;width:360px;">
													             <div id="no_items" align="center" style="display:;">No items defined</div>
													         </div>
													     </div>
													     <div style="clear:both;"></div>
													     <div style="padding:2px;width:440px;">
													         <div style="padding:2px;float:left;width:150px;"><input style="width:100px;" type="text" class="text" id="ikey" value=""></div>
													         <div style="padding:2px;float:left;width:150px;"><input style="width:100px;" type="text" class="text" id="iname" value=""></div>
													         <div style="padding:2px;float:left;width:75px;" align="center">
													         	<input type="checkbox" name="idef_add" id="idef_add" value="1">
													         </div>
													         <div style="padding:2px;float:left;width:auto;" align="center"><input onclick="AddItem();" type="button" class="btn" id="iname" value="Add"></div>
													     </div>
						    						</div>
						    					</div>	
						    					</td>
							    			</tr>
							    			{include file="inc/intable_footer.tpl" color="Gray"}
					    					<div id="field_buttons_add" style="clear:both;height:50px;padding-left:16px;">
					    						<br />
					    						<input type="Button" name="setfield" onClick="window.df_action = 'create'; SetField();" value="Add" class="btn">
					    						<br />
					    					</div>
					    					<div id="field_buttons_edit" style="clear:both;height:50px;padding-left:16px;display:none;">
					    						<br />
					    						<input type="Button" name="setfield" onClick="window.df_action = 'edit'; SetField();" value="Edit" class="btn">
					    						&nbsp;
					    						<input type="Button" name="setfield" onClick="ResetFieldForm();" value="Cancel" class="btn">
					    						<br />
					    					</div>
					    				</div>
					    			</div>
		    					</td>
		    				</tr>
		    			</table>
		    		</td>
		    	</tr>
				</table>
				</td>
				<td width="7" class="TableHeaderCenter_Gray"></td>
			</tr>
			</table>
		</div>
        
        {include intable_classname="tab_contents" intableid="tab_contents_security" visible="none" file="inc/intable_header.tpl" header="Security settings" color="Gray"}
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="700">
    		      <thead>
    		          <th>Protocol</th>
    		          <th>From Port</th>
    		          <th>To Port</th>
    		          <th>IP Ranges</th>
    		          <th></th>
    		      </thead>
    		      <tbody id="rules_container">
    		      {section name=id loop=$rules}
    		      <tr id="{$rules[id].id}">
    		          <td>{$rules[id].protocol}</td>
    		          <td>{$rules[id].portfrom}</td>
    		          <td>{$rules[id].portto}</td>
    		          <td>{$rules[id].ipranges}</td>
    		          <td><input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteRule("{$rules[id].id}")'><input type='hidden' name='rules[]' value='{$rules[id].rule}'></td>
    		      </tr>
    		      {/section}
    		      </tbody>
    		      <tr>
    		          <td><select class="text" name="add_protocol" id="add_protocol">
    		                  <option value="tcp">TCP</option>
    		                  <option value="udp">UDP</option>
    		                  <option value="icmp">ICMP</option>
    		              </select>
    		          </td>
    		          <td>
    		              <input type="text" class="text" size="5" id="add_portfrom" name="add_portfrom" value="">
    		          </td>
    		          <td>
    		              <input type="text" class="text" size="5" id="add_portto" name="add_portto" value="">
    		          </td>
    		          <td>
    		              <input type="text" class="text" size="20" id="add_ipranges" name="add_ipranges" value="0.0.0.0/0">
    		          </td>
    		          <td>
    		              <input type="button" class="btn" onclick="AddRule()" value="Add">
    		          </td>
    		      </tr>
    		  </table>
       		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button_js=1 button_js_action="return PrepareSubmit();" button_js_name="Save" show_js_button=1 cancel_btn=1}
{include file="inc/footer.tpl"}