{include file="inc/header.tpl"}
	<script language="javascript">
	var roles = new Array();

	{section name=id loop=$farms}
		roles[{$farms[id].id}] = new Array();
		{section name=rid loop=$farms[id].roles}
		roles[{$farms[id].id}][roles[{$farms[id].id}].length] = ['{$farms[id].roles[rid].name}','{$farms[id].roles[rid].ami_id}'];
		{/section}
	{/section}

	{literal}
		function SetRoles(farmid)
		{
			var sel = $('new_amiid');
			while (sel.firstChild)
			{
				sel.removeChild(sel.firstChild);
			}

			for (i = 0; i < roles[farmid].length; i++)
			{
				var opt = document.createElement("OPTION");
				opt.innerHTML = roles[farmid][i][0]+" ("+roles[farmid][i][1]+")";
				opt.value = roles[farmid][i][1];
			
				sel.appendChild(opt);
			}
		}
	{/literal}
	</script>

	{include file="inc/table_header.tpl"}
		{include file="inc/intable_header.tpl" header="General" color="Gray"}
    	<tr>
    		<td width="20%">{t}Application{/t}:</td>
    		<td>{$zoneinfo.zone}</td>
    	</tr>
    	<tr>
    		<td width="20%">{t}Current Farm{/t}:</td>
    		<td>{$farminfo.name}</td>
    	</tr>
    	<tr>
    		<td width="20%">{t}Current Role{/t}:</td>
    		<td>{$role_name} ({$ami_id})</td>
    	</tr>
    	<tr>
    		<td colspan="2">&nbsp;</td>
    	</tr>
    	<tr>
    		<td width="20%">{t}New Farm{/t}:</td>
    		<td>
    			<select name="new_farmid" class="text" onChange="SetRoles(this.value)">
    				<option value="{$farminfo.id}"> - Do not change farm - </option>
    				{section name=id loop=$farms}
    				<option value="{$farms[id].id}">{$farms[id].name}</option>
    				{/section}
    			</select>
    		</td>
    	</tr>
    	<tr>
    		<td width="20%">{t}New Role{/t}:</td>
    		<td>
    			<select name="new_amiid" id="new_amiid" class="text">
    				{section name=id loop=$roles}
    				<option value="{$roles[id].ami_id}">{$roles[id].name} ({$roles[id].ami_id})</option>
    				{/section}
    			</select>
    		</td>
    	</tr>
        {include file="inc/intable_footer.tpl" color="Gray"}
        <input type="hidden" name="application" value="{$application}" />
	{include file="inc/table_footer.tpl" button2=1 button2_name="Switch"}
{include file="inc/footer.tpl"}