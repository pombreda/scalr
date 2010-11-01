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
				object = Ext.get(obj).dom;
				if (object.tagName == 'INPUT'){ object.value = sPassword; } else{ object.innerHTML = sPassword; }
				if (obj2) {
					object = Ext.get(obj2).dom;
					if (object.tagName == 'INPUT'){ object.value = sPassword; }else{ object.innerHTML = sPassword; }
				}
			}catch(err){}; return true;
    }
	</script>
	{/literal}
	{include file="inc/table_header.tpl"}
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
	{include file="inc/table_footer.tpl" edit_page=1}
{include file="inc/footer.tpl"}
