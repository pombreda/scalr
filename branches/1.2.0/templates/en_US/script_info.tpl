{include file="inc/header.tpl"}
	<link rel="stylesheet" type="text/css" href="/js/highlight/styles/default.css" />
	<link rel="stylesheet" type="text/css" href="/js/highlight/styles/sunburst.css" />

	<div style="position:relative;width:auto;">
	<script language="javascript" type="text/javascript" src="/js/highlight/highlight.js"></script>
	<script language="javascript">
	{literal}
	 Event.observe(window, 'load', function(){
	 	HighLight();
	 });
	 
	 hljs.initHighlightingOnLoad.apply(null, hljs.ALL_LANGUAGES);
	 
	 function HighLight()
	 {
		hljs.highlightBlock(document.getElementById('script_source_container').firstChild.firstChild);
	 }
	 
	 function SetVersion(version)
	 {  	            	
		var url = 'script_info.php?id={/literal}{$id}{literal}&revision='+version;
		document.location = url;
	 }
	{/literal}
	</script>
	{include file="inc/table_header.tpl" nofilter=1}
		{include file="inc/intable_header.tpl" header="General information" color="Gray"}
		<tr>
    		<td>{t}Author{/t}:</td>
    		<td>
    			{if $script_info.client.id}
					{if $script_info.client.id == $smarty.session.uid}
						Me
					{else}
						{$script_info.client.fullname}
					{/if}
				{else}
					Scalr
				{/if}
    		</td>
    	</tr>
    	<tr>
    		<td>{t}Script name{/t}:</td>
    		<td>{$script_info.name}</td>
    	</tr>
    	<tr>
    		<td>{t}Description{/t}:</td>
    		<td>{$script_info.description}</td>
    	</tr>
    	{if $comments_enabled}
    	<tr>
    		<td>{t}Moderation phase{/t}:</td>
    		<td>
    			{if $script_info.approval_state == 'Approved' || !$script_info.approval_state}
					<img src="/images/true.gif" title="{t}Approved{/t}">
				{elseif $script_info.approval_state == 'Pending'}
					<img src="/images/pending.gif" title="{t}Pending{/t}">
				{elseif $script_info.approval_state == 'Declined'}
					<img src="/images/false.gif" title="{t}Declined{/t}">
				{/if}
    		</td>
    	</tr>
    	{/if}
    	<tr>
    		<td>{t}Version{/t}:</td>
    		<td>
    			<select style="vertical-align:middle;" onchange="SetVersion(this.value);" name="script_version" id="script_version" class="text">
					{section name=id loop=$versions}
					<option {if $selected_version == $versions[id]}selected{/if} value="{$versions[id]}">{$versions[id]}</option>
					{/section}
				</select>
    		</td>
    	</tr>
    	<tr>
    		<td colspan="2">
    			<br>
    			<div id="script_source_div" style="width:580px;">
					<div id="script_source_container" style="height:185px;width:570px;overflow:hidden;">
						<pre style="margin:0px;"><code>{$content}</code></pre>
					</div>
				</div>
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
    	
    	{if $comments_enabled}
	    	{include file="inc/comments.tpl"}
		{else}
			{include file="inc/table_footer.tpl" disable_footer_line="1"}
		{/if}
	</div>
{include file="inc/footer.tpl"}