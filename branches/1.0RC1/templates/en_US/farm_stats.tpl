{include file="inc/header.tpl"}
	{include file="inc/table_header.tpl"}
        {section name=id loop=$roles}
        	{assign var=name value=$roles[id].vname}
	        {include file="inc/intable_header.tpl" header="$name" color="Gray"}
	    	<tr>
	    		<td colspan="2" align="center">
	    			{if $roles[id].not_avail}
	    				<div style="width:1100px;">
	    					Images not yet available.
	    				</div>
	    			{else}
	    			<div style="width:1100px;">
	    				<div style="float:left;width:540px;">
	    					<img src="/storage/graphics/{$farminfo.id}/{$roles[id].name}/mem.gif" />
	    					<br/>
	    					<img src="/storage/graphics/{$farminfo.id}/{$roles[id].name}/net.gif" />
	    				</div>
	    				<div style="float:left;width:540px;margin-left:10px;">
	    					<img src="/storage/graphics/{$farminfo.id}/{$roles[id].name}/cpu.gif" />
	    					<br />
	    					<img src="/storage/graphics/{$farminfo.id}/{$roles[id].name}/la.gif" />
	    				</div>
	    			</div>
	    			{/if}
	    		</td>
	    	</tr>
	        {include file="inc/intable_footer.tpl" color="Gray"}
        {/section}
	{include file="inc/table_footer.tpl" disable_footer_line=1}
{include file="inc/footer.tpl"}