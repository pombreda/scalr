{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}		
	
		{include file="inc/intable_header.tpl" header="DNS zone settings" color="Gray"}
		<tr>
			<td colspan="2">	
				<input style="vertical-align:middle;" name="allow_manage_system_records" type="checkbox" {if $zone.allow_manage_system_records}checked="checked"{/if} id="allow_manage_system_records" value="1" /> 
				Allow me to edit system records. <span style="color:red;">Scalr will stop monitoring your zone.</span>						
			</td>
		</tr>		
		<input type="hidden" name="zoneid" id="zoneid" value="{$zone.id}" />		
		{include file="inc/intable_footer.tpl" color="Gray"}
		
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}