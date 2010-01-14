{include file="inc/header.tpl"}
	<script language="Javascript">
	{literal}
		function getRandomNum() {
   	        var rndNum = Math.random()
	    	rndNum = parseInt(rndNum * 1000);
	        rndNum = (rndNum % 94) + 33;
	        return rndNum;
	    };	    
	    function checkPunc(num) {
	        if ((num >=33) && (num <=47)) { return true; }
	        if ((num >=58) && (num <=64)) { return true; }
	        if ((num >=91) && (num <=96)) { return true; }
	        if ((num >=123) && (num <=126)) { return true; }
	        return false;
	    };
		function GeneratePassword(obj, obj2)
		{
	    	if (parseInt(navigator.appVersion) <= 3) {
	        	alert("Sorry this only works in 4.0+ browsers");
	        	return true;
	    	}

    		var length=16;
    		var sPassword = "";

    		var noPunction = true;

	    	for (i=0; i < length; i++) 
			{
	        	numI = getRandomNum();
	        	if (noPunction) { while (checkPunc(numI)) { numI = getRandomNum(); } }
	        	sPassword = sPassword + String.fromCharCode(numI);
	    	}
			try
			{
				object = $(obj);
				if (object.tagName == 'INPUT'){ object.value = sPassword; } else{ object.innerHTML = sPassword; }
				if (obj2) {
					object = $(obj2);
					if (object.tagName == 'INPUT'){ object.value = sPassword; }else{ object.innerHTML = sPassword; }
				}
			}catch(err){}; return true;
    }
	</script>
	{/literal}
	{include file="inc/table_header.tpl"}		
		{include file="inc/intable_header.tpl" header="System settings" color="Gray"}
		<tr>
			<td colspan="2">{t}Automatically abort instance synchronization if it does not complete in{/t} <input name="sync_timeout" type="text" class="text" id="sync_timeout" value="{$sync_timeout}" size="2"> {t}minutes{/t}.</td>
		</tr>
		<tr>
			<td width="18%">{t}Instances limit{/t}:</td>
			<td width="82%"><input name="client_max_instances" type="text" class="text" id="client_max_instances" value="{$client_max_instances}" size="10"> <span class="Webta_Ihelp">{t}You need to ask Amazon (aws@amazon.com) to increase instances limit for you before increasing this value.{/t}</span></td>
		</tr>
		<tr>
			<td width="18%">{t}Elastic IPs limit{/t}:</td>
			<td width="82%">
				<input name="client_max_eips" type="text" class="text" id="client_max_eips" value="{$client_max_eips}" size="2">
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="Date &amp; Time settings" color="Gray"}
		<tr>
			<td>{t}Timezone{/t}:</td>
			<td>
				<select name="system.timezone" class="text">
				{section name=id loop=$timezones}
					<option {if $timezone == $timezones[id]}selected{/if} value="{$timezones[id]}">{$timezones[id]}</option>
				{/section}
				</select>
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="RSS feed settings" color="Gray"}
		<tr>
			<td colspan="2">
				Each farm has an events and notifications page. You can get these events outside of Scalr on an RSS reader with the below credentials.
			</td>
		</tr>
		<tr>
			<td>{t}Login{/t}:</td>
			<td><input name="rss_login" type="text" class="text" id="rss_login" value="{$rss_login}"></td>
		</tr>
		<tr>
			<td>{t}Password{/t}:</td>
			<td><input name="rss_password" type="text" class="text" id="rss_password" value="{$rss_password}">
			&nbsp;&nbsp;<input style="vertical-align:middle;" type="button" value="Generate" class="btn" onClick="GeneratePassword('rss_password');" />
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		{include file="inc/intable_header.tpl" header="API settings" color="Gray"}
		<tr>
			<td>{t}Enabled{/t}:</td>
			<td><input name="api.enabled" {if $api_enabled}checked="checked"{/if} type="checkbox" id="api.enabled" value="1"></td>
		</tr>
		<tr valign="top">
			<td>
				Allow access to the API only from the following IPs (coma separated):<br />
				<div style="font-size:10px;"><i>Example: 67.45.3.7, 67.46.*.*, 91.*.*.*</i></div>
			</td>
			<td>
				<textarea name="api.allowed_ips" class="text" cols="50" rows="10" id="api.allowed_ips">{$api_allowed_ips}</textarea>
			</td>
		</tr>
		<tr>
			<td>{t}API Key ID{/t}:</td>
			<td><input name="scalr_api_keyid" type="text" readonly="readonly" class="text" id="scalr_api_keyid" value="{$scalr_api_keyid}"></td>
		</tr>
		<tr>
			<td>{t}API Access Key{/t}:</td>
			<td><textarea name="scalr_api_key" class="text" readonly="readonly" cols="50" rows="4" id="api_key">{$scalr_api_key}</textarea></td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}
