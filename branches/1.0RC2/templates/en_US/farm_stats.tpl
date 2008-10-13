{include file="inc/header.tpl"}
	<br />
	{include file="inc/table_header.tpl" nofilter=1 tabs=1}
		{section name=id loop=$roles}
			{assign var=name value=$roles[id].name}
			{assign var=tid value=$roles[id].id}
			{if $selected_tab == $tid}
				{assign var=visible value=""}
			{else}
				{assign var=visible value="none"}
			{/if}
			{if $name != '_FARM'}
	        	{include intable_classname="tab_contents" intableid="tab_contents_$tid" visible="$visible" file="inc/intable_header.tpl" header="Statistics for role: $name" color="Gray"}
	        {else}
	        	{include intable_classname="tab_contents" intableid="tab_contents_$tid" visible="$visible" file="inc/intable_header.tpl" header="Farm statistics" color="Gray"}
	        {/if}
			<tr>
	    		<td colspan="2" align="center">
	    			<div style="width:1100px;">
	    				{foreach key=watchername item=image from=$roles[id].images}
	    					<div style="float:left;margin-right:10px;height:360px;">
	    						<a href="farm_extended_stats.php?farmid={$farminfo.id}&role={$name}&watcher={$watchername}"><img src="{$image.url}"></a>
	    					</div>
	    				{/foreach}
	    			</div>
	    		</td>
	    	</tr>
        	{include file="inc/intable_footer.tpl" color="Gray"}
        {/section}
	{include file="inc/table_footer.tpl" disable_footer_line=1}				
{include file="inc/footer.tpl"}