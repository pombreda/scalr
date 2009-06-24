{if $DataForm}
	{assign var=fields value=$DataForm->ListFields()}
	{foreach from=$fields key=key item=field}
		    {if ($field->FieldType == 'text')}
			<tr valign="top">
				<td style="vertical-align:middle;">{$field->Title}: {if $field->IsRequired}*{/if}</td>
				<td style="padding-left:10px;">
					<input type="text" size="5" class="scaling_options text" id="{$field->Name}" name="{$field->Name}" value="{$field->Value}"/>
					{if $field->Hint}
						<span class="Webta_Ihelp">{$field->Hint}</span>
					{/if}				
				</td>
			</tr>
			{elseif ($field->FieldType == 'textarea')}
			<tr valign="top">
				<td style="vertical-align:middle;">{$field->Title}: {if $field->IsRequired}*{/if}</td>
				<td style="padding-left:10px;">
					<textarea cols="40" rows="8" class="scaling_options text" name="{$field->Name}">{$field->Value}</textarea>
					{if $field->Hint}
						<span class="Webta_Ihelp">{$field->Hint}</span>
					{/if}
				</td>
			</tr>
			{elseif ($field->FieldType == 'min_max_slider')}
			<tr valign="top">
				<td style="vertical-align:middle;">{$field->Title}: {if $field->IsRequired}*{/if}</td>
				<td style="padding-left:10px;margin:0px;">
					<div id="{$field->Name}" style="padding-left:30px;margin-top:-15px;">

					</div>
					<input type="hidden" style="padding:0px;margin:0px;" name="{$field->Name}.max" id="{$field->Name}.max" class="scaling_options"/>
					<input type="hidden" style="padding:0px;margin:0px;" name="{$field->Name}.min" id="{$field->Name}.min" class="scaling_options"/>
				</td>
			</tr>
			{elseif ($field->FieldType == 'checkbox')}
			<tr valign="top">
				<td style="padding-left:20px;">{$field->Title}: {if $field->IsRequired}*{/if}</td>
				<td>
					<input type="checkbox" class="scaling_options" {if $field->Value == 1}checked{/if} name="{$field->Name}" value="1"/>
					{if $field->Hint}
						<span class="Webta_Ihelp">{$field->Hint}</span>
					{/if}
				</td>
			</tr>
			{elseif ($field->FieldType == 'separator')}
			<tr valign="top">
				<td colspan="2"><br />{$field->Title}<br /><br /></td>
			</tr>
			{elseif $field->FieldType == 'select'}
			<tr valign="top">
				<td style="vertical-align:middle;">{$field->Title}: {if $field->IsRequired}*{/if}</td>
				<td style="padding-left:10px;margin:0px;">
					{assign var=values value=$field->Options}
					{if $field->AllowMultipleChoice}
						{foreach from=$values key=vkey item=vfield}
						<div style="float:left;padding-right:5px;">
							<input {if $vkey|@in_array:$field->Value}checked{/if} style="vertical-align:middle;" type="checkbox" name="{$field->Name}[{$vkey}]" value="1"> {$vfield}
						</div> 
						{/foreach}
					{else}
					<select id="{$field->Name}" class="scaling_options text" name="{$field->Name}">
						{foreach from=$values key=vkey item=vfield}
							<option {if $field->Value == $vkey}selected{/if} value="{$vkey}">{$vfield}</option>
						{/foreach}
					</select>
					{/if}
				</td>
			</tr>
			{/if}
	{/foreach}
{/if}