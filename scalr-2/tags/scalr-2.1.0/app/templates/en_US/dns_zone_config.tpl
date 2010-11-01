{include file="inc/header.tpl"}
    <script language="Javascript">
    
    {literal}
    
    function AddHost()
    {        
        if (Ext.get('add_host').dom.value == "")
        {
            alert("'IP address' required");
            return false;
        }
        
        container = document.createElement("TR");
        container.id = "host_"+parseInt(Math.random()*100000);
        
        //first row
        td1 = document.createElement("TD");
        td1.innerHTML = Ext.get('add_host').dom.value;
        container.appendChild(td1);
             
        var host = Ext.get('add_host').dom.value
                
        // fifth row
        td5 = document.createElement("TD");
        td5.innerHTML = "<input type='button' class='btn' name='dleterule' value='Delete' onclick='DeleteHost(\""+container.id+"\")'>";
        container.appendChild(td5);

		//<input type='hidden' name='hosts[]' value='"+host+"'>
		var inp = document.createElement("INPUT");
		inp.type = 'hidden';
		inp.name = 'hosts[]';
		inp.value = host;
		inp.id = container.id+'_inp';
		
		document.forms[1].appendChild(inp);
        
        Ext.get('hosts_container').dom.appendChild(container);
        
        Ext.get('add_host').dom.value = "";
    }
    
    function DeleteHost(id)
    {
        Ext.get(id).dom.parentNode.removeChild(Ext.get(id).dom);
        
        try
        {
        	Ext.get(id+"_inp").dom.parentNode.removeChild(Ext.get(id+"_inp").dom);
        }
        catch(e){}
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