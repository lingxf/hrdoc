var xmlhttp;
function loadXMLDoc(url,cfunc)
{
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=cfunc;
	xmlhttp.open("GET",url,true);
	xmlhttp.send();
}
function loadXMLDocByPost(url,arg, cfunc)
{
	if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	} else {// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=cfunc;
	xmlhttp.open("POST",url, true);
	xmlhttp.setRequestHeader("CONTENT-TYPE","application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Content-Length",arg.length);
	xmlhttp.setRequestHeader("Connection", "close");
	xmlhttp.send(arg);
}

var ctdc = null;
var ctxt = "";
var cnewtxt = "";
var undotxt = "";
var colnames = ['NULL', 'desc', 'comments', 'class'];

function save_edit_col(tdc, id, col)
{
	//var txt = tdc.firstChild.innerHTML;
	var txt = tdc.firstChild.value;

	var colnames = ['NULL', 'desc', 'comments', 'class'];
	if(col < 8){
		var arg = "book_id="+id+"&col="+colnames[col]+"&op=modify&text="+txt;
		var scripts = "edit_book.php";
	}

   regx = /^\s*([^:]+):([^:]+)\s*$/;
   match = regx.exec(id);
   if(match){
		casenumber = match[1];
   		caseurl = match[2];
	}
	
	loadXMLDocByPost(scripts, arg ,function() {
	  if (xmlhttp.readyState==4 && xmlhttp.status==200) {
   			//document.getElementById("div_report").innerHTML=xmlhttp.responseText;
			tdc.innerHTML = xmlhttp.responseText;
			if(txt == xmlhttp.responseText)
				ctxt = txt;
	  }else{
   			tdc.innerHTML =xmlhttp.readyState+":status:"+xmlhttp.status+xmlhttp.responseText;
	  }
  	});

}
function discard_edit_col(tdc)
{
	//var txt = tdc.firstChild.innerHTML;
	var txt = tdc.firstChild.value;
	undotxt = txt;
	tdc.innerHTML = ctxt;
}
function get_edit_column(tdc, id, col, callback)
{

	var colnames = ['NULL', 'desc', 'comments', 'class'];	
	var arg = "book_id="+id+"&col="+colnames[col]+"&op=read&text=a";
	var scripts = "edit_book.php";
	var ttxt;

	loadXMLDocByPost(scripts,arg ,function() {
	  if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			ttxt = xmlhttp.responseText;
	 		callback(tdc, id, col, ttxt);
	  }else{
   			ttxt =xmlhttp.readyState+"status:"+xmlhttp.status+xmlhttp.responseText;
	  }
  	});

}


function cont_edit_col(tdc, id, col, txt)
{
	if(txt == 'NULL')
		txt = "";
	var tdbox = tdc.firstChild;
	if(tdbox){
		var t = tdbox.nodeName;// != "textarea";
		if(t == "TEXTAREA")
			return;
	}

	if(ctdc != null){
		ctdc.innerHTML = ctxt;
	}

	var w = tdc.clientWidth/9;
	var h = tdc.clientHeight/16-1;
	if(w < 12)
		w = 12;

	ctxt = tdc.innerHTML;
	var len = 0;
	if(col == 2){
		today = new Date();
		m = today.getMonth()+1;
		d = today.getDate();
		len = txt.length;
		dt = '[' + m+"/"+d+']'+":\n";
		txt = dt + txt;
		len = dt.length-1;
	}
	if(col == 3)
		end = len + 1;
	else
		end = len;
	var box = '<button type="button" onclick="save_edit_col(this.parentNode,\''+id+'\','+col+')" class="gwt-Button">Save</button><button type="button" onclick="discard_edit_col(this.parentNode)" class="gwt-Button">Discard</button>';
	tdc.innerHTML = "<textarea cols="+w+" rows="+h+"spellcheck='true'>"+txt+"</textarea><br>"+box;
	ta = tdc.firstChild;

	if(ta.setSelectionRange){
		//ta.setSelectionRange(1000,1000);
		ta.setSelectionRange(len,end);
		ta.focus();
	}else if(ta.createTextRange){
		tempText=ta.createTextRange();
		var len=-tempText.text.length;
		tempText.moveBegin("character",-len);
		tempText.select();
	}

//	tdc.firstChild.scrollIntoView();
	ctdc = tdc;
};

function show_edit_col(tdc, id, col)
{
	if(col == 2)
		cont_edit_col(tdc, id, col, '');
	else if(col==8)
		cont_edit_col(tdc, id, col, tdc.innerHTML);
	else
		get_edit_column(tdc, id, col, cont_edit_col);
}

function show_fulltext_col(tdc, id, col, txt)
{
	var tdbox = tdc.firstChild;
	if(tdbox){
		var t = tdbox.nodeName;// != "textarea";
		if(t == "TEXTAREA")
			return;
	}
	brieftext = tdc.innerHTML;
	txt = txt.replace(/\n/g,'<br>');
	tdc.innerHTML = txt;
//	tdc.firstChild.focus();
};

function show_less_col(tdc, id, col, txt)
{
	var tdbox = tdc.firstChild;
	if(tdbox){
		var t = tdbox.nodeName;// != "textarea";
		if(t == "TEXTAREA")
			return;
	}

	tdc.innerHTML = brieftext;
};

function show_more_col(tdc, id, col)
{
	if(col==0)
		cont_edit_col(tdc, id, col, tdc.innerHTML);
	else
		get_column_col(tdc, id, col, show_fulltext);
};


