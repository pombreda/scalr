{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
		<div style="margin:10px;">
		{section name=id loop=$roles}
			<a href="#{$roles[id].name}">{if $roles[id].name != '_FARM'}{$roles[id].name}{else}Farm{/if}</a>{if !$smarty.section.id.last}&nbsp;{/if}
		{/section}
		</div>
        {section name=id loop=$roles}
        	{assign var=name value=$roles[id].name}
        	{if $name != '_FARM'}
	        	{include file="inc/intable_header.tpl" header="Statistics for role: $name" ancor_name="$name" color="Gray"}
	        {else}
	        	{include file="inc/intable_header.tpl" header="Farm statistics" ancor_name="$name" color="Gray"}
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