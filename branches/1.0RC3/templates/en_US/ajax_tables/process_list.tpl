{if $error == ''}
	{section name=id loop=$processes}
	<tr id='tr_{$smarty.section.id.iteration}'>
	  <td class="Item">{$processes[id].hrSWRunPath} {$processes[id].hrSWRunParameters}</td>
	  <td class="Item" nowrap width="150">{$processes[id].hrSWRunPerfMem}</td>
	  <td class="Item" nowrap width="180">{$processes[id].hrSWRunType}</td>
	  <td class="Item" nowrap width="100">{$processes[id].hrSWRunStatus}</td>
	</tr>
	{/section}
{else}
	<tr>
	  <td colspan="5">{$error}</td>
	</tr>
{/if}
