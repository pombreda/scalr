{assign var=fields value=$DataForm->ListFields()}
{foreach from=$fields key=key item=field}
	    {if ($field->FieldType == 'text')}
		<tr>
			<td style="padding-left:20px;">{$field->Title}: </td>
			<td>
				<input type="text" class="text" name="{$field_prefix}{$field->Name}{$field_suffix}" value="{$field->Value}"/> {if $field->IsRequired}*{/if}
				{if $field->Hint}
					<span class="Webta_Ihelp">{$field->Hint}</span>
				{/if}				
			</td>
		</tr>
		{elseif ($field->FieldType == 'checkbox')}
		<tr>
			<td style="padding-left:20px;">{$field->Title}: </td>
			<td>
				<input type="checkbox" {if $field->Value == 1}checked{/if} name="{$field_prefix}{$field->Name}{$field_suffix}" value="1"/> {if $field->IsRequired}*{/if}
				{if $field->Hint}
					<span class="Webta_Ihelp">{$field->Hint}</span>
				{/if}
			</td>
		</tr>
		{elseif ($field->FieldType == 'separator')}
		<tr>
			<td colspan="2"><br />{$field->Title}<br /><br /></td>
		</tr>
		{elseif $field->FieldType == 'select'}
		<tr>
			<td style="padding-left:20px;">{$field->Title}: </td>
			<td><select class="text" name="{$field_prefix}{$field->Name}{$field_suffix}">
					{assign var=values value=$field->Options}
					{foreach from=$values key=vkey item=vfield}
						<option {if $field->Value == $vkey}selected{/if} value="{$vkey}">{$vfield}</option>
					{/foreach}
				</select> {if $field->IsRequired}*{/if}
			</td>
		</tr>
		{/if}
{/foreach}