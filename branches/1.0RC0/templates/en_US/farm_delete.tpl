{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Delete farm" color="Gray"}
    	<tr>
    		<td colspan="2">
    		  {if $app_count > 0}There are {$app_count} applications assigned to this farm. These applications will be deleted too and DNS zones will be erased.{/if}
    		  <br>
    		  Are you sure?
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="action" value="delete" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Yes, delete farm now" cancel_btn=1}
{include file="inc/footer.tpl"}