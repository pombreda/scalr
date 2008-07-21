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
	    			{if $roles[id].not_avail}
	    				<div style="width:1100px;">
	    					Images not yet available.
	    				</div>
	    			{else}
	    			<div style="width:1100px;">
	    				<div style="float:left;width:540px;">
	    					<img src="/storage/graphics/{$farminfo.id}/{$name}/mem.gif" />
	    					<br/>
	    					<img src="/storage/graphics/{$farminfo.id}/{$name}/net.gif" />
	    				</div>
	    				<div style="float:left;width:540px;margin-left:10px;">
	    					<img src="/storage/graphics/{$farminfo.id}/{$name}/cpu.gif" />
	    					<br />
	    					<img src="/storage/graphics/{$farminfo.id}/{$name}/la.gif" />
	    				</div>
	    			</div>
	    			{/if}
	    		</td>
	    	</tr>
	        {include file="inc/intable_footer.tpl" color="Gray"}
        {/section}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}