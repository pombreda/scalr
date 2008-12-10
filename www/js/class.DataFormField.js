	var DataFormField = Class.create();
	DataFormField.prototype = {
		name:"",
		type:"text",
		required:false,
		defval:"",
		allow_multiple_choise:false,
		options: new Array(),
		
		initialize: function(name, type, required, defval, allow_multiple_choise, options) 
		{
			this.name = name;
			this.type = type;
			this.required = required;
			this.defval = defval;
			this.allow_multiple_choise = allow_multiple_choise;
			this.options = options;
	  	}
	};
	
	var DataForm = Class.create();
	
	DataForm.prototype = {
		fields: new Array(),
		container: null,
		count:0,
		
		initialize: function(container) 
		{
			this.container = container;			
	  	},
	  	
	  	Load: function(fields)
	  	{
	  		fields.each(function(item){
	  			window.df.AddField(item);
			});
	  	},
	  	
	  	AddField: function (DataFromField)
	  	{
	  		if (!this.fields[DataFromField.name])
	  		{
	  			this.fields[DataFromField.name] = DataFromField;
	  			this.count++;
	  			
	  			this.AddFieldToContainer(DataFromField);
	  			
	  			$('no_fields').style.display = 'none';
	  			
	  			return true;
	  		}
	  		else
	  			alert("Field with same name already exists");
	  			
	  		return false;
	  	},
	  	
	  	AddFieldToContainer: function(DataFromField)
	  	{
	  		var color = (this.count % 2 == 0) ? 'background-color:#f0f0f0;' : '';
	  		
	  		var isreq = DataFromField.required ? 'true' : 'false';
	  		var content = '<tr id="field_container_'+DataFromField.name+'" style="'+color+'"><td></td>'+
 							'<td style="padding:2px;">'+DataFromField.name+'</td>'+
 							'<td style="padding:2px;">'+DataFromField.type+'</td>'+
 							'<td style="padding:2px;" width="1%" nowrap="nowrap" align="center"><img src="images/'+isreq+'.gif"></td>'+
 							'<td style="padding:2px;" width="1%" nowrap="nowrap" align="right"><img onClick="window.df.EditField(\''+DataFromField.name+'\');" style="cursor:pointer;" title="Edit" src="/images/edit.png">&nbsp;<img onClick="window.df.DeleteField(\''+DataFromField.name+'\');" title="Delete" style="cursor:pointer;" src="/images/del.gif"></td>'+
 						   '<td></td></tr>';
 						   
 			this.container.insert(content);
	  	},
	  	
	  	GetFieldByName: function(name)
	  	{
	  		return this.fields[name];
	  	},
	  	
	  	DeleteField: function(name)
	  	{
	  		this.fields[name] = null;
	  		this.count--;
	  		
	  		if (this.count == 0)
	  			$('no_fields').style.display = '';
	  		
	  		var f = $('field_container_'+name);
	  		f.parentNode.removeChild(f);
	  		
	  		ResetFieldForm();
	  	},
	  	
	  	EditField: function(name)
	  	{
	  		ResetFieldForm();
	  		
	  		$('fieldname').disabled = true;
	  		
	  		$('field_buttons_add').style.display = 'none';
	  		$('field_buttons_edit').style.display = '';
	  		
	  		field = this.GetFieldByName(name);
	  		
	  		$('fieldname').value = field.name;
			$('fieldtype').value = field.type;
			var items = $('fieldtype').options;
			for(var i = 0; i < items.length;i++)
			{
				if (items[i].value == field.type)
					items[i].selected = true;
			}
			
			$('fieldrequired').checked = field.required;
			
			tmp = $('tab_contents_options').select('[name="fielddefval"]');
			tmp.each(function(item){
				item.value = field.defval;
			});
			
			$('allow_multiplechoise').checked = field.allow_multiple_choise;
					
			for (var k in field.options)
			{
				window.AddItem(field.options[k][0], field.options[k][1], field.options[k][3]);
			}		
			
			AllowMultipleChoice($('allow_multiplechoise').checked);			
			SetFieldForm();
	  	},
	  	
	  	UpdateField: function (DataFromField)
	  	{
	  		if (this.fields[DataFromField.name])
	  		{
	  			this.fields[DataFromField.name] = DataFromField;
	  			
	  			var field_cont = $('field_container_'+DataFromField.name);
	  			var cols = field_cont.select('td');
	  			cols[1].innerHTML = DataFromField.name;
	  			cols[2].innerHTML = DataFromField.type;
	  			
	  			var isreq = DataFromField.required ? 'true' : 'false';
	  			cols[3].innerHTML = '<img src="images/'+isreq+'.gif">';
	  			
	  			return true;
	  		}
	  		else
	  			alert("Field with does not exists");
	  			
	  		return false;
	  	}
	};
	
	
	function CheckType(type)
	{
	    if (type == 'SELECT')
	    {
	        $('selectinfo').style.display = '';
	    }
	    else
	    {
	        $('selectinfo').style.display = 'none';
	    }
	}
	
	var Items = new Array();
	
	function AddItem(item_key, item_name, item_isdef)
	{
	    if (!item_key)
	    	var item_key = $('ikey').value;
	    	
	    if (!item_name)
	    	var item_name = $('iname').value;
	    	
	    if (!item_isdef)
	    	var item_isdef = $('idef_add').checked
	    	
	    
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
	    	$('select_properties').select('[class="select_one"]').each(function(item){
				tmp = item.select('[name="idef"]')
				tmp[0].checked = false;
			});
	    }
	    else
	    	checked = '';
	    
	    if ($('allow_multiplechoise').checked)
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
	    $('idef_add').checked = false;
	    
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
	            $('no_items').style.display = '';    
	         }
	    }
	    
	    $('Items').appendChild(cont);
	    $('no_items').style.display = 'none';
	    
	    $('iname').value = "";
	    $('ikey').value = "";
	    
	    $('idef_add').checked = false;
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
	    
	    $('button_js').disabled = true;
	    
	    document.forms[1].submit();
	}
	
	function SetFieldForm()
	{
		var items = $('fieldtype').options;
		for(var i = 0; i < items.length;i++)
		{
			if ($('fieldtype').options[i].selected == true)
			{
				$($('fieldtype').options[i].value+'_properties').style.display = '';
				if ($('fieldtype').options[i].value == 'select')
				{
					$('list_options').style.display = '';
				}
				else
				{
					$('list_options').style.display = 'none';
				}
			}
			else
			{
				$($('fieldtype').options[i].value+'_properties').style.display = 'none';
			}
		}
	}
	
	function ResetFieldForm()
	{
		$('fieldname').value = "";
		$('fieldtype').value = "text";
		$('fieldrequired').checked = false;
		$('fieldname').disabled = false;
		
		tmp = $('tab_contents_options').select('[name="fielddefval"]');
		tmp.each(function(item){
			item.value = "";
		});
		$('allow_multiplechoise').checked = false;
		
		Items = new Array();
		
		var items = $('Items').select('[class="select_item"]');
		for (var i = 0; i < items.length; i++)
		{
			$('Items').removeChild(items[i]);
		}
		
		$('no_items').style.display = '';
		
		AllowMultipleChoice(false);
		
		SetFieldForm();
		
		$('field_buttons_add').style.display = '';
		$('field_buttons_edit').style.display = 'none';
	}
	
	function AllowMultipleChoice(value)
	{
		if (value == true)
		{
			$('list_options').select('[class="select_one"]').each(function(item){
				item.style.display = 'none';
				
				if (item.select("[name='idef']")[0].checked == true)
					$(item.select("[name='idef']")[0].id.replace('rdo', 'chk')).checked = true;
				else
					$(item.select("[name='idef']")[0].id.replace('rdo', 'chk')).checked = false;
			});
			
			$('list_options').select('[class="select_many"]').each(function(item){
				item.style.display = '';
			});
		}
		else
		{
			$('list_options').select('[class="select_one"]').each(function(item){
				item.style.display = '';
				
				var chk = false;
			
				if (item.select("[name='idef']")[0].checked == true && !chk)
				{
					$(item.select("[name='idef']")[0].id.replace('chk', 'rdo')).checked = true;
					chk = true;
				}
				else
					$(item.select("[name='idef']")[0].id.replace('rdo', 'chk')).checked = false;
				
			});
					
			$('list_options').select('[class="select_many"]').each(function(item){
				item.style.display = 'none';
			});
		}
	}
	
	function SetField()
	{
		name = $('fieldname').value;
		type = $('fieldtype').value;
		required = $('fieldrequired').checked;
		
		if (name == '')
		{
			alert("Field name required");
			return;
		}
		
		if (name == 'all')
		{
			alert("Field name cannot be 'all'. Please select another name.");
			return;
		}
		
		if (type == 'text' || type == 'textarea')
		{
			tmp = $(type+'_properties').select('[name="fielddefval"]');
			def_val = tmp[0].value; 
		}
		else
			def_val = false;
			
		allow_multiplechoise = $('allow_multiplechoise').checked;
			
		if (type == 'select')
		{
			options = Items;
			options.each(function(item){
			
				if (allow_multiplechoise)
					var container = $('select_many_'+item[2]);
				else
					var container = $('select_one_'+item[2]);
				
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
		
		if (result)
		{
			ResetFieldForm();
		}
	}