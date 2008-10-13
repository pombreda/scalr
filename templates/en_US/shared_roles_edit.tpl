{include file="inc/header.tpl"}
    <script language="Javascript">
    
    {literal}
    
    function AddRule()
    {        
        if ($('add_portfrom').value == "" || parseInt($('add_portfrom').value) <= -2 || parseInt($('add_portfrom').value) > 65536)
        {
            alert("'From port' must be a number between 1 and 65536");
            return false;
        }
        
        if ($('add_portto').value == "" || parseInt($('add_portto').value) <= -2 || parseInt($('add_portto').value) > 65536)
        {
            alert("'To port' must be a number between 1 and 65536");
            return false;
        }
        
        if ($('add_ipranges').value == "")
        {
            alert("'IP ranges' required");
            return false;
        }
        
        container = document.createElement("TR");
        container.id = "rule_"+parseInt(Math.random()*100000);
        
        //first row
        td1 = document.createElement("TD");
        td1.innerHTML = $('add_protocol').value;
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
        td4.innerHTML = $('add_ipranges').value;
        container.appendChild(td4);
        
        rule = $('add_protocol').value+':'+$('add_portfrom').value+":"+$('add_portto').value+":"+$('add_ipranges').value;
        
        // fifth row
        td5 = document.createElement("TD");
        td5.innerHTML = "<input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteRule(\""+container.id+"\")'><input type='hidden' name='rules[]' value='"+rule+"'>";
        container.appendChild(td5);
        
        $('rules_container').appendChild(container);
        
        $('add_portfrom').value = "";
        $('add_portto').value = "";
        $('add_ipranges').value = "0.0.0.0/0";
        $('add_protocol').value = 'tcp';
    }
    
    function DeleteRule(id)
    {
        $(id).parentNode.removeChild($(id));
    }
    {/literal}
    </script>
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Role information" color="Gray"}
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
    	{/if}
		<tr>
    		<td width="20%">Role name:</td>
    		<td><input type="text" class="text" name="name" value="{$name}" /></td>
    	</tr>
    	<tr>
    		<td width="20%">Prototype role:</td>
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
        
        {include file="inc/intable_header.tpl" header="Security settings" color="Gray"}
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
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}