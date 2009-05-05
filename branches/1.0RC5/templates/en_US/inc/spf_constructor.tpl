<div id="spf_popup" align="left">
    <div align="center"><h3>{t}SPF Constructor{/t}</h3></div>
    {t}Add mechanism:{/t}<br>
    <table align="left" width="" cellpadding="2" cellspacing="2">
        <tr>
            <td>
                <select class="text" name="prefix" id="prefix">
                    <option value=""></option>
                    <option value="-">-</option>
                    <option value="+">+</option>
                    <option value="?">?</option>
                    <option value="~">~</option>
                </select>
            </td>
            <td>
                <select class="text" name="mechanism" id="mechanism">
                    <option value="a">a</option>
                    <option value="mx">mx</option>
                    <option value="ptr">ptr</option>
                    <option value="ip4">ip4</option>
                    <option value="ip6">ip6</option>
                    <option value="exists">exists</option>
                    <option value="include">include</option>
                    <option value="all">all</option>
                </select>
            </td>
            <td><input type="text" class="text" name="value" id="mech_value" value="" /></td>
            <td><input type="button" name="but" class="btn" onclick="AddMechanism();" value="add"></td>
        </tr>
    </table>
	<br>
	<br><br>
	{t}Add modifier:{/t}<br>
    <table align="left" width="">
        <tr>
            <td>
                <select class="text" name="mod" id="mod">
                    <option value="redirect">redirect</option>
                    <option value="exp">exp</option>
                </select>
            </td>
            <td>= <input type="text" class="text" name="mod_value" value="" /></td>
            <td><input type="button" name="but" onclick="AddMod();" class="btn" value="add"></td>
        </tr>
    </table>
	<br><br><br>
	{t}SPF Record:{/t}<br><input type="text" class="text" name="spfpreview" style="width:350px;margin:4px;" id="spfpreview" value="v=spf1" />
	<br>
	<br>
	<div align="center">
	    <input type="button" name="button" onClick="SaveSPF();" value="Save SPF" class="btn" />
	</div>
</div>
{literal}
<script language="javascript">
    NoCloseButton = false;
    popup = new NewPopup('spf_popup', {target: '', width: 380, height: 260, selecters: new Array()});
    
    function AddSPFRecord(id, obj)
	{
	    var pos = Position.positionedOffset(obj);
		
		pos[0] = pos[0]+parseInt(obj.offsetWidth)/2-35;
		pos[1] = pos[1]+parseInt(obj.offsetHeight)/2;
		
		window.recordID = id;
		popup.raisePopup(pos);
	}
	
	function SaveSPF()
	{
	    objj = $('zone[records]['+window.recordID+'][rvalue]')
	    if (!objj)
	    	objj = $('add['+window.recordID+'][rvalue]')
	    
	    objj.value = $('spfpreview').value;
	    
	    $('spfpreview').value = "v=spf1";
	    
	    popup.hide(popup.options.popup);
		popup.selecters('visible');
		popup.ieTweak('visible');
	}
	
	function AddMechanism()
	{
	    var str = " "+$('prefix').value+$('mechanism').value+$('mech_value').value;
	    $('spfpreview').value += str;
	    
	    $('prefix').value = "";
	    $('mechanism').value = "a";
	    $('mech_value').value = "";
	}
	
	function AddMod()
	{
	    var str = " "+$('mod').value+"="+$('mod_value').value;
	    $('spfpreview').value += str;
	    
	    $('mod').value = "redirect";
	    $('mod_value').value = "";
	}
</script>
{/literal}