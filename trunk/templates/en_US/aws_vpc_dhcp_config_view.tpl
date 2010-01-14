{include file="inc/header.tpl"}
    
    <link rel="stylesheet" type="text/css" href="/js/highlight/styles/default.css" />
	<link rel="stylesheet" type="text/css" href="/js/highlight/styles/sunburst.css" />


	<style type="text/css">
		{literal}
		pre code
		{
			width:800px;
			height:290px;
		}
		pre .xml .tag .title 
		{
		
			color:#84CDe4;				
		}				
		{/literal}
	</style>

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
		{include file="inc/intable_header.tpl" header="DHCP  configuration" color="Gray"}
		
    	<tr>
    		<td colspan="2">
    		  <table cellpadding="5" cellspacing="15" width="100%" border="0" > 
    			 <tr>
    		      	  <td style="font-weight:bold; width:20%;">Options set ID</td>
    		      	  <td>
    		      	    {$id}
    		      	  </td>    		          
    		      </tr>    		      
    		      <tr>
    		      	  <td style="font-weight:bold; width:20%;">Parameters</td>
    		      	  <td>
    		      	    <textarea style="width:90%; height:100px;">{$options}</textarea>
    		      	  </td>    		          
    		      </tr>       		        		         		     
    		  </table>
       		</td>
    	</tr>
    
    	
    	
		{include file="inc/intable_footer.tpl" color="Gray"} 	
		{include file="inc/table_footer.tpl" }	
{include file="inc/footer.tpl"}