{include file="inc/header.tpl"}
    <script language="Javascript">
    
    {literal}
    
    function AddRule(no_alerts)
    {        
        var type = $('add_protocol').value;
        
        if (type != 'user')
        {
	        if ($('add_portfrom').value == "" || parseInt($('add_portfrom').value) <= -2 || parseInt($('add_portfrom').value) > 65536)
	        {
	            if (!no_alerts)
	            	alert("'From port' must be a number between 1 and 65536");
	            	
	            return false;
	        }
	        
	        if ($('add_portto').value == "" || parseInt($('add_portto').value) <= -2 || parseInt($('add_portto').value) > 65536)
	        {
	            if (!no_alerts)
	            	alert("'To port' must be a number between 1 and 65536");
	            	
	            return false;
	        }
	        
	        if ($('add_ipranges').value == "")
	        {
	            if (!no_alerts)
	            	alert("'IP ranges' required");
	            	
	            return false;
	        }
	    }
	    else
	    {
	    	if ($('add_portfrom').value.length != 12)
	    	{
	    		alert("User ID must be 12 digits length");
	    		return false;
	    	}
	    }
        
        container = document.createElement("TR");
        container.id = "rule_"+parseInt(Math.random()*100000);
        
        //first row
        td1 = document.createElement("TD");
        
        if (type != 'user')
        {
        	td1.innerHTML = $('add_protocol').value;
        }
        else
        {
        	td1.innerHTML = 'User &amp; Group';
        }
        
        container.appendChild(td1);
        
        //second row
        td2 = document.createElement("TD");
        td2.innerHTML = $('add_portfrom').value;
        container.appendChild(td2);
        
        //third row
        td3 = document.createElement("TD");
        td3.innerHTML = $('add_portto').value;
        container.appendChild(td3);
        
        //forth row
       	td4 = document.createElement("TD");
        	
        if (type != 'user')
        {
        	td4.innerHTML = $('add_ipranges').value;
        }
        container.appendChild(td4);
        
        rule = $('add_protocol').value+':'+$('add_portfrom').value+":"+$('add_portto').value+":"+$('add_ipranges').value;
        
        // fifth row
        td5 = document.createElement("TD");
        td5.innerHTML = "<input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteRule(\""+container.id+"\")'>";
        container.appendChild(td5);
        
        if (type != 'user')
        	$('rules_container').appendChild(container);
        else
        {
        	$('ug_rules').style.display = '';
        	$('ug_rules').appendChild(container);
        }
        
        var rule_input = document.createElement('INPUT');
        rule_input.type = 'hidden';
        rule_input.name = 'rules[]';
        rule_input.id = container.id+"_input";
        rule_input.value = rule;
        
        document.forms[1].appendChild(rule_input);
        
        $('add_portfrom').value = "";
        $('add_portto').value = "";
        $('add_ipranges').value = "0.0.0.0/0";
        $('add_protocol').value = 'tcp';
    }
    
    function DeleteRule(id)
    {
        try
        {	
        	$(id+"_input").parentNode.removeChild($(id+"_input"));
        }
        catch(e){}	
        
        try
        {
        	$(id).parentNode.removeChild($(id));
        }
        catch(e){}
    }
    
    function SaveRules()
    {
    	AddRule(true);
    	
    	var footer_button_table = $('footer_button_table');
		var elems = footer_button_table.select('[class="btn"]');
		elems.each(function(item){
			item.disabled = true;
		});
		
		$('btn_hidden_field').name = this.name;
		$('btn_hidden_field').value = this.value;
		
		document.forms[1].submit();
    }
    
    function SetType(type)
    {
    	if (type == 'user')
    		$('add_ipranges').style.display = 'none';
    	else
    		$('add_ipranges').style.display = '';
    }
    {/literal}
    </script>
	{include file="inc/table_header.tpl"}       
        {include file="inc/intable_header.tpl" header="Security group rules" color="Gray"}
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="700" border="0">
    		      <thead>
    		      <tr>
    		          <th width="20%" style="font-weight:bold;">Protocol</th>
    		          <th width="20%" style="font-weight:bold;">From Port</th>
    		          <th width="20%" style="font-weight:bold;">To Port</th>
    		          <th width="30%" style="font-weight:bold;">IP Ranges</th>
    		          <th width="10%"></th>
    		      </tr>
    		      </thead>
    		      <tbody id="rules_container">
    		      {foreach from=$rules item=rule}
    		      <tr id="{$rule->id}">
    		          <td>{$rule->ipProtocol}</td>
    		          <td>{$rule->fromPort}</td>
    		          <td>{$rule->toPort}</td>
    		          <td>{$rule->ip}</td>
    		          <td><input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteRule("{$rule->id}")'><input type='hidden' name='rules[]' value='{$rule->rule}'></td>
    		      </tr>
    		      {/foreach}
    		      </tbody>
    		      <tbody id="ug_rules" style="display:{if $ug_rules|@count == 0}none{/if};">
    		      	<tr>
    		      	  <th width="20%" style="font-weight:bold;">Type</th>
    		          <th width="20%" style="font-weight:bold;">UserID</th>
    		          <th width="20%" style="font-weight:bold;">Group</th>
    		          <th width="30%" style="font-weight:bold;"></th>
    		          <th width="10%"></th>
    		        </tr>
    		        {foreach from=$ug_rules item=rule}
	    		      <tr id="{$rule->id}">
	    		          <td>User &amp; Group</td>
	    		          <td>{$rule->userId}</td>
	    		          <td>{$rule->groupname}</td>
	    		          <td></td>
	    		          <td><input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteRule("{$rule->id}")'><input type='hidden' id="{$rule->id}_input" name='rules[]' value='{$rule->rule}'></td>
	    		      </tr>
	    		      {/foreach}
    		      </tbody>
    		      <tr>
    		      	<td colspan="10">&nbsp;</td>
    		      </tr>
    		      <tr>
    		          <td><select class="text" name="add_protocol" id="add_protocol" onchange="SetType(this.value);">
    		                  <option value="tcp">TCP</option>
    		                  <option value="udp">UDP</option>
    		                  <option value="icmp">ICMP</option>
    		                  <option value="user">User and Group</option>
    		              </select>
    		          </td>
    		          <td>
    		              <input type="text" class="text" size="10" id="add_portfrom" name="add_portfrom" value="">
    		          </td>
    		          <td>
    		              <input type="text" class="text" size="10" id="add_portto" name="add_portto" value="">
    		          </td>
    		          <td>
    		              <input type="text" class="text" size="14" id="add_ipranges" name="add_ipranges" value="0.0.0.0/0">
    		          </td>
    		          <td>
    		              <input type="button" class="btn" onclick="AddRule()" value="Add">
    		          </td>
    		      </tr>
    		  </table>
       		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="name" value="{$group_name}">
	{include file="inc/table_footer.tpl" button_js=1 button_js_name="Save" show_js_button=1 button_js_action="SaveRules();"}
{include file="inc/footer.tpl"}