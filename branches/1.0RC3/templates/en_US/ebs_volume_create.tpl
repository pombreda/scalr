{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" intable_first_column_width="250" header="Volume settings" color="Gray"}
    	<tr>
    		<td><input onclick="$('ebs_size').disabled = !this.checked; $('snapId').disabled = this.checked;" type="radio" name="ctype" checked value="1" style="vertical-align:middle;"> Create empty volume with size:</td>
    		<td><input type="text" name="size" id="ebs_size" value="" class="text" size="3"> GB</td>
    	</tr>
    	<tr>
    		<td><input onclick="$('ebs_size').disabled = this.checked; $('snapId').disabled = !this.checked;" type="radio" {if $snapId}checked{/if} name="ctype" value="2" style="vertical-align:middle;"> Create volume from snapshot:</td>
    		<td>
    			<select name="snapId" id="snapId" class="text" {if !$snapId}disabled{/if}>
    			{section name=sid loop=$snapshots}
					<option {if $snapId == $snapshots[sid]}selected{/if} value="{$snapshots[sid]}">{$snapshots[sid]}</option>
				{sectionelse}
					<option value="">No snapshots found</option>
				{/section}
				</select>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        
        {include file="inc/intable_header.tpl" header="Volume placement" color="Gray"}
        <tr>
    		<td>Placement:</td>
    		<td>
    			<select name="availZone" class="text">
    			{section name=zid loop=$avail_zones}
					<option {if $availZone == $avail_zones[zid]}selected{/if} value="{$avail_zones[zid]}">{$avail_zones[zid]}</option>
				{/section}
				</select>
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Create volume" cancel_btn=1}
{include file="inc/footer.tpl"}