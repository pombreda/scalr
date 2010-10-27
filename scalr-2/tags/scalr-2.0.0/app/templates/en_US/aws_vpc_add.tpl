{include file="inc/header.tpl"}
    <script language="Javascript">
    {literal}
        
    function SaveParams()
    {    	 	
		var elems = Ext.get('footer_button_table').select('.btn');
		elems.each(function(item){
			item.disabled = true;
		});
		
		Ext.get('btn_hidden_field').dom.name = this.name;
		Ext.get('btn_hidden_field').dom.value = this.value;
		
		document.forms[1].submit();
    }
    
    {/literal}
    </script>
{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Virtual Private Cloud " color="Gray"}
    	<tr>
    		<td style="padding: 3px;" colspan="2">
    		  <table style=" border-collapse: separate; border-spacing:3px; width:100%;" border="0" >    		      
    				<tr>
    		      		<td style="font-weight:bold; padding: 15px; width:10%;">CIDR</td>
    		      		<td style="font-weight:bold; padding: 10px;width:15%;"><input type="text" class="text" style="width:100%" id="cidr" name="cidr" value=""></td>   
    		      		<td style="padding: 10px; width:40%;">
    		      			<i style="font-style:italic;vertical-align:text-top;font-size:9pt;">(e.g.) 10.0.0.0/24</i>
    					</td>
    				</tr>
    		  </table>
       		</td>
    	</tr>
		{include file="inc/intable_footer.tpl" color="Gray"} 
	{include file="inc/table_footer.tpl" button_js=1 button_js_name="Create VPC" show_js_button=1 button_js_action="SaveParams();"}	
{include file="inc/footer.tpl"}