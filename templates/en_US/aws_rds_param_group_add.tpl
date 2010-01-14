{include file="inc/header.tpl"}
    <script language="Javascript">
    {literal}
        
    function SaveParams()
    {
    	//AddRule(true);
    	
    	var footer_button_table = $('footer_button_table');
		var elems = footer_button_table.select('[class="btn"]');
		elems.each(function(item){
			item.disabled = true;
		});
		
		$('btn_hidden_field').name = this.name;
		$('btn_hidden_field').value = this.value;
		
		document.forms[1].submit();
    }
    
    {/literal}
    </script>
{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Parameters Group " color="Gray"}
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="100%" border="0" >    		      
    		      <tr>
    		      	  <td style="font-weight:bold; width:30%;">Parameter Group Name</td>
    		      	  <td><input type="text" class="text" style="width:20%" id="newGroupName" name="newGroupName" value="">  </td>    		          
    		      </tr>    		      
    		      <tr>
    					<td style="font-weight:bold;">Engine</td>    	
    					<td> 
    						<select class="text" name="engine" id="engine"><option>MySQL5.1</option></select>
    					</td>
    		      </tr>
    		      <tr>
    		      	  <td style="font-weight:bold;vertical-align:text-top;">Description</th>
    		      	  <td><textarea class="text" cols="60" rows="10" name="description" id="description"></textarea></th>  
    		      </tr> 
    		  </table>
       		</td>
    	</tr>
		{include file="inc/intable_footer.tpl" color="Gray"} 
	{include file="inc/table_footer.tpl" button_js=1 button_js_name="Create New Group" show_js_button=1 button_js_action="SaveParams();"}	
{include file="inc/footer.tpl"}