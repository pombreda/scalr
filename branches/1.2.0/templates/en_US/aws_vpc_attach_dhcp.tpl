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
		{include file="inc/intable_header.tpl" header="DHCP options " color="Gray"}
		
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="100%" border="0" > 
    		      <tr>
					<td style="font-weight:bold; width:20%">DHCP options ID</td>    	
					<td> 
						<select style="width:180px;" id="aws_dhcp" name="aws_dhcp" class="text">
             				{section name=id loop=$dhcpOptionsId}	             					
             					<option  value="{$dhcpOptionsId[id]}"> {$dhcpOptionsId[id]}</option>	             					
             				{/section}             				
	             				<option value="default">Default</option>	             			
             		</select>
					</td>
    		      </tr>    		     
    		  </table>
       		</td>
    	</tr>
    	
    	
		{include file="inc/intable_footer.tpl" color="Gray"} 
	{include file="inc/table_footer.tpl" button_js=1 button_js_name="Assosiate with VPC" show_js_button=1 button_js_action="SaveParams();"}	
{include file="inc/footer.tpl"}