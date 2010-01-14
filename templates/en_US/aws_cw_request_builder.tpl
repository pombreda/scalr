{include file="inc/header.tpl"}	
	<script language="javasript" type="text/javascript">

		var Measures = {$measures};
		var Dimensions = {$dimensions};
		var DimensionValues = {$dimension_values}
		
		{literal}
		function SetNamespace(namespace)
		{
			var el = $('Measure'); 
			while(el.firstChild) { 
				el.removeChild(el.firstChild); 
			}
														
			for (var i = 0; i < Measures[namespace].length; i ++)
			{
				var opt = document.createElement("OPTION");
				opt.value = Measures[namespace][i];
				opt.innerHTML = Measures[namespace][i];
				$('Measure').appendChild(opt); 
			}

			SetMeasure(namespace, $('Measure').value);
		}

		function SetMeasure(namespace, measure)
		{
			var el = $('DimensionType'); 
			while(el.firstChild) { 
				el.removeChild(el.firstChild); 
			}

			for (var i = 0; i < Dimensions[namespace+":"+measure].length; i ++)
			{
				var opt = document.createElement("OPTION");
				opt.value = Dimensions[namespace+":"+measure][i];
				opt.innerHTML = Dimensions[namespace+":"+measure][i];
				$('DimensionType').appendChild(opt); 
			}

			SetDimensionType(namespace, measure, $('DimensionType').value);
		}

		function SetDimensionType(namespace, measure, dimension_type)
		{
			var el = $('DimensionValue'); 
			while(el.firstChild) { 
				el.removeChild(el.firstChild); 
			}

			for (var i = 0; i < DimensionValues[namespace+":"+measure+":"+dimension_type].length; i ++)
			{
				var opt = document.createElement("OPTION");
				opt.value = DimensionValues[namespace+":"+measure+":"+dimension_type][i];
				opt.innerHTML = DimensionValues[namespace+":"+measure+":"+dimension_type][i];
				$('DimensionValue').appendChild(opt); 
			}
		}

		Ext.onReady(function(){
			SetNamespace($('Namespace').value);
		});
		{/literal}
	</script>
	{include file="inc/table_header.tpl"}
    	{include file="inc/intable_header.tpl" intable_first_column_width="15%" header="Request information" color="Gray"}
    	<tr>
    		<td>Namespace:</td>
    		<td>
    			<select name="Namespace" id="Namespace" class="text" onChange="SetNamespace(this.value);">
    				{foreach from=$namespaces key=key item=item}
    				<option value="{$item}">{$item}</option>
    				{/foreach}
    			</select>
    		</td>
    	</tr>
    	<tr>
    		<td>Metric:</td>
    		<td>
    			<select name="Measure" id="Measure" class="text" onChange="SetMeasure($('Namespace').value, this.value);">
    				
    			</select>
    		</td>
    	</tr>
    	<tr>
    		<td>Dimension:</td>
    		<td>
    			<select name="DimensionType" id="DimensionType" class="text" onChange="SetDimensionType($('Namespace').value, $('Measure').value, this.value);">
    				
    			</select>
    			
    			<select name="DimensionValue" id="DimensionValue" class="text">
    				
    			</select>
    		</td>
    	</tr>
    	{include file="inc/intable_footer.tpl" color="Gray"}
	{include file="inc/table_footer.tpl" button2=1 button2_name="Continue" cancel_btn=1}
{include file="inc/footer.tpl"}