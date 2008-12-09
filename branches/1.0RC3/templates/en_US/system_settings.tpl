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
			<td colspan="2">Automatically abort instance synchronization if it does not complete in <input name="sync_timeout" type="text" class="text" id="sync_timeout" value="{$sync_timeout}" size="2"> minutes.</td>
		</tr>
		<tr>
			<td width="18%">Instances limit:</td>
			<td width="82%"><input name="client_max_instances" type="text" class="text" id="client_max_instances" value="{$client_max_instances}" size="10"></td>
		</tr>
		<tr>
			<td width="18%">Elastic IPs limit:</td>
			<td width="82%">
				<input name="client_max_eips" type="text" class="text" id="client_max_eips" value="{$client_max_eips}" size="2">
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
		
		{include file="inc/intable_header.tpl" header="RSS feed settings" color="Gray"}
		<tr>
			<td>Login:</td>
			<td><input name="rss_login" type="text" class="text" id="rss_login" value="{$rss_login}"></td>
		</tr>
		<tr>
			<td>Password:</td>
			<td><input name="rss_password" type="text" class="text" id="rss_password" value="{$rss_password}">
			&nbsp;&nbsp;<input style="vertical-align:middle;" type="button" value="Generate" class="btn" onClick="GeneratePassword('rss_password');" />
			</td>
		</tr>
		{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}
