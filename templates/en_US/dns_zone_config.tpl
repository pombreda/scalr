{include file="inc/header.tpl"}
    <script language="Javascript">
    
    {literal}
    
    function AddHost()
    {        
        if ($('add_host').value == "")
        {
            alert("'IP address' required");
            return false;
        }
        
        container = document.createElement("TR");
        container.id = "host_"+parseInt(Math.random()*100000);
        
        //first row
        td1 = document.createElement("TD");
        td1.innerHTML = $('add_host').value;
        container.appendChild(td1);
             
        var host = $('add_host').value
                
        // fifth row
        td5 = document.createElement("TD");
        td5.innerHTML = "<input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteHost(\""+container.id+"\")'><input type='hidden' name='hosts[]' value='"+host+"'>";
        container.appendChild(td5);
        
        $('hosts_container').appendChild(container);
        
        $('add_host').value = "";
    }
    
    function DeleteHost(id)
    {
        $(id).parentNode.removeChild($(id));
    }
    {/literal}
    </script>
	{include file="inc/table_header.tpl"}
        {include file="inc/intable_header.tpl" header="IP address(es) that are allowed to transfer (copy) the zone information" color="Gray"}
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="0" cellspacing="5" width="200">
    		      <thead>
    		          <th>IP address</th>
    		          <th></th>
    		      </thead>
    		      <tbody id="hosts_container">
    		      {section name=id loop=$hosts}
    		      {if $hosts[id] != ''}
	    		      <tr id="{$hosts[id]}">
	    		          <td>{$hosts[id]}</td>
	    		          <td><input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteHost("{$hosts[id]}")'><input type='hidden' name='hosts[]' value='{$hosts[id]}'></td>
	    		      </tr>
    		      {/if}
    		      {/section}
    		      </tbody>
    		      <tr>
    		          <td>
    		              <input type="text" class="text" size="15" id="add_host" name="add_host" value="">
    		          </td>
    		          <td>
    		              <input type="button" class="btn" onclick="AddHost()" value="Add">
    		          </td>
    		      </tr>
    		  </table>
       		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}