{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="Confirm synchronization abort" color="Gray"}
    	<tr>
    		<td colspan="2">
    		  {t escape=no instance_id=$instance_id}
    		  You are about to cancel synchronization proccess on <b>%1</b>.<br>
			  This will <b>NOT</b> terminate the rebundle proccess on the instance itself.<br>
			  If the synchronizzation will succeed, the new AMI will not be visible in Scalr.
			  {/t}
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="task" value="abort" />
        <input type="hidden" name="confirmed" value="1" />
        <input type="hidden" name="id" value="{$id}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Cancel synchronization" button3=1 button3_name="Do not cancel synchronization"}
{include file="inc/footer.tpl"}