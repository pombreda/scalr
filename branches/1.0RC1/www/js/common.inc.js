
	var allchecked = new Array();
	function checkall(formname)
	{
	    if (!formname)
	       formname = "frm";
	    
	    if (allchecked[formname] == undefined)
	       allchecked[formname] = false;
	       
		var frm = $(formname);
		for (var i=0;i<frm.elements.length;i++)
		{
			var e = frm.elements[i]
			if ((e.name == "delete[]" || e.name == "actid[]") && (e.type=='checkbox') && !e.disabled) {
				e.checked = !allchecked[formname];
			}
		}
		allchecked[formname] = !allchecked[formname];
		try {
			Tweaker.CheckAll();
		} catch (err) {}
	}


var webtacp = new LibWebta({ load_calendar: load_calendar, load_treemenu: load_treemenu });
	webtacp.loadDefautls();

	