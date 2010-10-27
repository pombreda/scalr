{include file="inc/header.tpl"}
	{literal}
	<script language="Javascript">
		Ext.onReady(function(){
		
			if (Ext.get('volumeId').dom)
				Ext.get('volid').dom.innerHTML = Ext.get('volumeId').dom.value; 
			
		});
	</script>
	{/literal}
	{include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" intable_first_column_width="10%" header="Attach to instance" color="Gray"}
        <tr>
    		<td>Volume:</td>
    		<td>
    			{if !$volumeId}
	    			<select name="volumeId" id="volumeId" class="text" onchange="Ext.get('volid').dom.innerHTML = this.value;">
	    			{section name=vid loop=$volumes}
						<option value="{$volumes[vid]->volumeId}">{$volumes[vid]->volumeId}</option>
					{/section}
					</select>
				{else}
					{$volumeId}
					<input type="hidden" name="volumeId" value="{$volumeId}" />
				{/if}
    		</td>
    	</tr>
        <tr>
    		<td>Server:</td>
    		<td>
    			{if !$iid}
	    			<select name="serverId" id="serverId" class="text">
	    			{section name=iid loop=$servers}
						<option {if $iid == $instances[iid].server_id}selected{/if} value="{$servers[iid].server_id}">{$servers[iid].server_id} ({$servers[iid].role_name}) on '{$servers[iid].farm_name}'</option>
					{/section}
					</select>
				{else}
					<input type="hidden" name="serverId" value="{$serverId}" />
				{/if}
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td colspan="2">
    			<input type="checkbox" onclick="{literal}if (this.checked){ Ext.get('mount_settings').dom.style.display = ''; }else{ Ext.get('mount_settings').dom.style.display = 'none'; }{/literal}" style="vertical-align:middle;" name="attach_on_boot" value="1"> Always attach volume <span id="volid" style="font-weight:bold;">{$volumeId}</span>&nbsp;to this instance upon startup
    		</td>
    	</tr>
    	<tr id='mount_settings' style='display:none;'>
    		<td colspan="2">
    			<input type="checkbox" style="vertical-align:middle;" name="mount" style='vertical-align:middle;' value="1"> Automaticaly mount volume to <input style='vertical-align:middle;' type='text' name='mountpoint' value='/mnt/storage' class='text' />
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	<input type="hidden" name="task" value="attach">
	{include file="inc/table_footer.tpl" button2=1 button2_name="Continue" cancel_btn=1}
{include file="inc/footer.tpl"}