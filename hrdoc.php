<?php
include 'debug.php';
$web_name = 'hrdoc';
$home_page = 'hrdoc.php';
session_set_cookie_params(30*24*3600);
session_name($web_name);
session_start();
setcookie('username',session_name(),time()+3600);	 //创建cookie
if(isset($_COOKIE["username"])){	//使用isset()函数检测cookie变量是否已经被设置
	$username = $_COOKIE["username"];	 //您好！nostop		读取cookie 
}else{
	$username = '';
}

include_once 'debug.php';
include_once 'db_connect.php';
include_once 'myphp/login_lib.php';
include_once 'myphp/disp_lib.php';
include 'hrdoc_lib.php';
include 'hrdoc_records.php';
global $login_id, $max_book, $setting;	
$login_id = "Guest";
check_login($web_name);


?>

<html>
<title>HR Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="zh-CN" /> 
<script type="text/javascript" src="inpage_edit.js"></script>
<!--
-<link rel="stylesheet" type="text/css" href="report.css" media="screen12"/>
	A php that could manage book library 
	by Ling Xiaofeng <lingxf@gmail.com>
-->
<style type="text/css">
@media screen {
	.print_ignore {
display: none;
	}

	body, table, th, td {
		font-size:		   12pt;
	}

	table, th, td {
		border-width:	   1px;
		border-color:	   #0000f0;
		border-style:	   solid;
	}
	th, td {
padding:		   0.2em;
	}
}
</style>
<body onload="load_intro()">
<script type="text/javascript">
function load_intro(){
	var intr = document.getElementById("div_homeintro");
	if(intr){
		intr.innerHTML="Please wait...";
		url = "brqclub.htm";
		loadXMLDoc(url,function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				intr.innerHTML=xmlhttp.responseText;
			}else{
				if(xmlhttp.status=='0')
				intr.innerHTML="Please wait...";
				else
				intr.innerHTML=xmlhttp.status+xmlhttp.responseText;
				}
			});
	}

}
function change_filter_field(name, value){
	url = "show_document.php?";
	url = url + name+"="+value;
	change_div(url, "div_booklist");
}

function change_div(url, div){
	document.getElementById(div).innerHTML="Please wait...";
	loadXMLDoc(url,function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById(div).innerHTML=xmlhttp.responseText;
			}else{
			if(xmlhttp.status=='0')
				document.getElementById(div).innerHTML="Please wait...";
			else
				document.getElementById(div).innerHTML=xmlhttp.status+xmlhttp.responseText;
			}
	});
};

function change_order(order, view){
	url = "show_book.php?";
	url = url + "order="+order;
	if(view != 0)
		url = url + "&view="+view;

	document.getElementById("div_booklist").innerHTML="Please wait...";
	loadXMLDoc(url,function() {
			if (xmlhttp.readyState==4 && xmlhttp.status==200) {
				document.getElementById("div_booklist").innerHTML=xmlhttp.responseText;
			}else{
				if(xmlhttp.status=='0')
				document.getElementById("div_booklist").innerHTML="Please wait...";
				else
				document.getElementById("div_booklist").innerHTML=xmlhttp.status+xmlhttp.responseText;
			}
	});
};

function employee_search(){
	url = "show_document.php?";
	employee = document.getElementById("id_employee").value;
	document.getElementById("sel_status").value = -1;
	document.getElementById("sel_doctype").value = -1;
	url = url + "uid="+employee +"&status=-1&doctype=-1";
	change_div(url, 'div_booklist');
};

function reset_search(){
	url = "show_document.php?";
	url = url + "uid=-1&status=-1&doctype=-1";
	document.getElementById("sel_status").value = -1;
	document.getElementById("sel_doctype").value = -1;
	document.getElementById("id_employee").value = '';
	change_div(url, 'div_booklist');
};

function add_records(){
	url = "edit_hrdoc.php?";
	url = url + "op=add_hrdoc_ui";
	window.location.href=url;
};



</script>

<?php
include_once 'debug.php';
include_once 'db_connect.php';
/*
   copyright Xiaofeng(Daniel) Ling<lingxf@gmail.com>, 2017, Nov.
 */


global $login_id, $max_book, $setting;	

//foreach(session_get_cookie_params() as $a=>$b){ print("$a=>$b<br>");}

$sid=session_id();

$max_books = 100;
$role = get_member_role($login_id);
#$role_city = get_user_attr($login_id, 'city');
$disp_city = 0;
if($role == 2)
	$role_text = "admin";
else if($role == 1)
	$role_text = "hr";
else
	$role_text = "user";


if($login_id == 'Guest')
	$login_text = "<a id='id_login_name' href=?action=login>Login</a>";
else
	$login_text = "$login_id($role_text) &nbsp;&nbsp;<a href=\"?action=logout&url=hrdoc.php\">Logout</a>";

$action="home";
if(isset($_GET['action']))$action=$_GET['action'];

$book_id=0;
if(isset($_GET['book_id'])) $book_id=$_GET['book_id'];
if(isset($_GET['record_id'])) $record_id=$_GET['record_id'];
if(isset($_GET['borrower'])) $borrower =$_GET['borrower'];


print "<a href=\"hrdoc.php\">Home</a>";

if($role == 0){
}else if($role >= 1){
	print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp";
	print "&nbsp;&nbsp;<a href=\"hrdoc.php?action=library\">Stock</a>";
}
if($role == 2){
	print "&nbsp;&nbsp;<a href=\"$home_page?action=manage\">Manage</a>";
	print "&nbsp;&nbsp;<a href=\"$home_page?action=history\">History</a>";
	print "&nbsp;&nbsp;<a href=\"$home_page?action=list_out\">Lent</a>";
	print "&nbsp;&nbsp;<a href=\"$home_page?action=list_timeout\">Timeout</a>";
	print "&nbsp;&nbsp;<a href=\"$home_page?action=log\">Log</a>";
	print "&nbsp;&nbsp;<a href=\"$home_page?action=user\">User</a>";
	print "&nbsp;&nbsp;<a href=\"$home_page?action=export\">Export</a>";
}

print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$login_text ";

print("<br>");


$items_perpage = get_persist_var('items_perpage', 50);
$order = get_persist_var('order', 2);
$start = get_persist_var('start', 0);
$doctype = get_persist_var('doctype', -1);
$status = get_persist_var('status', -1);
$uid = get_persist_var('uid', -1);


if(isset($_POST['prev'])) $action="prev";
if(isset($_POST['next']))$action="next";
if(isset($_POST['begin'])) $action="begin";
if(isset($_POST['end']))$action="end";
if(isset($_POST['list_all']))$action="list_all";


//dprint("Action:$action Login:$login_id book_id:$book_id start:$start items:$items_perpage setting:$setting<br>");


if(isset($_GET['class'])) $class=$_GET['class'];
else if(isset($_SESSION['class'])) $class=$_SESSION['class'];
else $class = 100;
$_SESSION['class'] = $class;

if(isset($_GET['view'])) $view=$_GET['view'];
else if(isset($_SESSION['view'])) $view=$_SESSION['view'];
else $view = $setting & 1 ? 'normal':'brief';
//dprint("view:$view, setting:$setting<br>");
$_SESSION['view'] = $view;
$_SESSION['setting'] = $setting;
$favor = false;

switch($action){
	case "home":
		show_home();
		break;
	case "next":
		$start += $items_perpage;
		$_SESSION['start'] = $start;
		show_library();
		break;
	case "library":
		show_library();
		break;
	case "begin":
		$start = 0;
		$_SESSION['start'] = $start;
		show_library();
		break;
	case "end":
		$end = get_total_documents();
		$start = $end - $items_perpage ;
		if($start < 0)
			$start = 0;
		$_SESSION['start'] = $start;
		show_library();
		break;
	case "prev":
		$start -= $items_perpage;
		if($start < 0)
			$start = 0;
		$_SESSION['start'] = $start;
		show_library();
		break;
	case "show_borrower":
		show_book($book_id);
		break;
	case "list_timeout":
		print(">8 week<br>");
		list_record('', 'timeout', 56);
		print(">4 week<br>");
		list_record('', 'timeout', 28);
		print(">3.5 week<br>");
		list_record('', 'timeout', 24);
		print(">3 week<br>");
		list_record('', 'timeout', 21);
		break;
	case "list_out":
		list_record($login_id, 'out');
		break;
	case "join":
		if($login_id == 'NoLogin'){
			print("please register first!");
			break;
		}
		$borrower = $login_id;
		$cc = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$to = get_admin_mail();
		add_member($borrower, $user, $cc, 0x0);
		add_record(0, $login_id, 0x107);
		mail_html($to, $cc, "$user is applying to join reading club", "");
		manage_record($login_id);
		break;

	/*member*/
	case "borrow":
		$comment = get_url_var('comment', '');
		if(preg_match("/\"(.+)\"/", $comment, $matches)){
			$comment = $matches[1];
		}
		if(isset($record_id)){
			borrow_wait_book($record_id, $login_id);
		}else{
			if(!borrow_book($book_id, $login_id, $comment))
				show_home_link('Back', 'home', '', 5);
			else
				show_my_hot();
		}
		break;
	case "renew":
		$book_id = get_bookid_by_record($record_id);
		renew_book($book_id, $record_id, $login_id);
		show_my_hot($login_id);
		break;
	case "cancel":
		set_record_status($record_id, 0x100);
		show_my_hot($login_id);
		break;
	case "returning":
		set_record_status($record_id, 3);
		show_my_hot($login_id);
		break;
	case "wait":
		if(wait_book($book_id, $login_id)){
			$bookname = get_bookname($book_id);
			$borrower = get_borrower($book_id);
			$to = get_user_attr($borrower, 'email');
			$user = get_user_attr($login_id, 'name');
			$cc = '';
			mail_html($to, $cc, "$user is waiting for your book <$bookname>", "");
		}
		home_link();
		break;
	case "list_statistic":
		list_statistic();
		break;

		/*admin*/
	case "transfer":
		$book_id = get_bookid_by_record($record_id);
		$old_borrower = get_borrower($book_id);
		$bookname = get_bookname($book_id);
		$record_id_my = get_record($book_id);
		$new_borrower = get_borrower_by_record($record_id);
		dprint("trasnfer:$book_id,$old_borrower, $new_borrower, $bookname, $record_id, $record_id_my<br>");
		if($old_borrower != $login_id){
			print("$bookname is not owned by you currently<br>");
			break;
		}
		$old_status = get_book_status($book_id);
		$old_user = get_user_attr($old_borrower, 'name');
		$new_user = get_user_attr($new_borrower, 'name');
		$new_max = get_user_attr($new_borrower, 'max');
		$sql = " select * from history where borrower='$new_borrower' and (status = 1 or status = 2)";
		$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
		$rows = mysql_num_rows($res);
		if($rows >= $new_max){
			print ("$new_user 已达最高借阅数，请让他/她先归还!");
			break;
		}

		$to = get_user_attr($new_borrower, 'email');
		$to .= ';' . get_user_attr($old_borrower, 'email');
		$cc = get_admin_mail();
		add_log($login_id, $old_borrower, $book_id, 0);
		add_log($login_id, $new_borrower, $book_id, 2);
		mail_html($to, $cc, "<$bookname> is transfered from <$old_borrower:$old_user> to <$new_borrower:$new_user>", "");
		set_record_status($record_id_my, 0);
		set_record_status($record_id, 2);
		show_home($login_id);
		break;

	/*admin*/
	case "migrate":
		migrate_record($login_id);
		break;
	case "transfer_comment":
		transfer_comment();
		break;
	case "update_borrow_times":
		update_borrow_times();
		break;
	case "import_favor":
		import_favor_from_history();
		break;

	case "list_member":
		list_member();
		break;
	case "manage":
		manage_record($login_id);
		break;
	case "add_newbook":
		add_newbook($login_id);
		break;
	case "edit_book":
		edit_book($book_id);
		break;
	case "push":
		$book_id = get_bookid_by_record($record_id);
		$borrower = get_borrower($book_id);
		$bookname = get_bookname($book_id);
		$to = get_user_attr($borrower, 'email');
		$cc = get_admin_mail();
		mail_html($to, $cc, "Timeout, Please return the book <$bookname>", "");
		home_link("Back", 'manage');
		break;

	case "approve_renew":
		$book_id = get_bookid_by_record($record_id);
		$borrower = get_borrower($book_id);
		$bookname = get_bookname($book_id);
		$to = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$cc = get_admin_mail();
		set_record_status($record_id, 0);
		add_log($login_id, $borrower, $book_id, 0);
		mail_html($to, $cc, "<$bookname> is returned by <$borrower:$user>", "");

		$record_id = add_record($book_id, $borrower, 1, true);
		set_record_status($record_id, 2);
		add_log($login_id, $borrower, $book_id, 2);
		mail_html($to, $cc, "<$bookname> is lent to <$borrower:$user>", "");
		manage_record($login_id);
		break;
	case "lend":
		$book_id = get_bookid_by_record($record_id);
		$borrower = get_borrower_by_record($record_id);
		$bookname = get_bookname($book_id);
		$old_status = get_book_status($book_id);
		if($old_status != 0 && $old_status != 1){
			print("<$book_id>$bookname is not returned yet");
			break;
		}
		$to = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$cc = get_admin_mail();
		set_record_status($record_id, 2);
		$message = "book:$book_id $bookname, record:$record_id, $user";
		mail_html($to, $cc, "<$bookname> is lent to <$borrower:$user>", "$message");
		add_log($login_id, $borrower, $book_id, 2);
		manage_record($login_id);
		break;
	case "stock":
		$book_id = get_bookid_by_record($record_id);
		$borrower = get_borrower($book_id);
		$bookname = get_bookname($book_id);
		$to = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$cc = get_admin_mail();
		mail_html($to, $cc, "<$bookname> is returned by <$user>", "");
		add_log($login_id, $borrower, $book_id, 0);
		set_record_status($record_id, 0);
		manage_record($login_id);
		break;
	case "remove_member":
		dprint("remove $borrower");
		set_member_attr($borrower, 'role', 0);
		list_member();
		break;
	case "approve_member":
		$to = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$cc = get_admin_mail();
		mail_html($to, $cc, "$user is approved to join reading club", "");
		add_log($login_id, $borrower, 0, 0x108);
		if(isset($record_id))
			set_record_status($record_id, 0x108);
		set_member_attr($borrower, 'role', 0x1);
		manage_record($login_id);
		break;
	case "reject_return":
		$book_id = get_bookid_by_record($record_id);
		$borrower = get_borrower($book_id);
		$bookname = get_bookname($book_id);
		$to = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$cc = get_admin_mail();
		mail_html($to, $cc, "Your return for <$bookname> is rejected", "");
		set_record_status($record_id, 0x2);
		set_book_status($book_id, 2);
		manage_record($login_id);
		break;
	case "reject":
		$book_id = get_bookid_by_record($record_id);
		$borrower = get_borrower($book_id);
		$bookname = get_bookname($book_id);
		$to = get_user_attr($borrower, 'email');
		$user = get_user_attr($borrower, 'name');
		$cc = get_admin_mail();
		mail_html($to, $cc, "You apply to <$bookname> is rejected", "");
		set_record_status($record_id, 0x101);
		set_book_status($book_id, 0);
		manage_record($login_id);
		break;
	case "reject_wait":
		set_record_status($record_id, 0x101);
		manage_record($login_id);
		break;
	case "history":
		list_record('all', 'history');
		break;
	case "log":
		list_log();
		break;
	case "user":
		show_user();
	   break;
	case "export":
		show_export();
	   break;



}

function show_my_hot()
{
	show_home();
}

function show_home()
{
	global $login_id, $view, $start, $items_perpage;
	global $class_list, $class, $comment_type, $role, $order;
	if($role >= 1){
	}
	if($role > 0){
	}else if($login_id == 'Login'){
		print("<div id='div_homentro'>");
		$sql = "select * from notice where item = 'HR'";
		$res = read_mysql_query($sql);
		$n = 0;
		if($row = mysql_fetch_array($res)){
			$notice = $row['notice'];
			$lines = explode("\n", $notice);
			foreach ($lines as $line_num => $line) {
#print(htmlspecialchars($line) . "<br/>\n");
				if(is_numeric($line[0]) && $n != 0)
					print("<br>");
				print($line."<br/>");
				$n++;
			}
		}
		print('</div>');
	}

	print("My Document");
	show_hrdoc($login_id);
	print("My Borrow");
	list_record($login_id, 'self', "history.borrower='$login_id'");

}


function show_library()
{
	global $login_id, $view, $start, $items_perpage;
	global $doctype, $status, $uid;
	$view_op = $view == 'brief'?'normal':'brief';
	$view_ch = $view_op == 'brief'?'brief':'normal';
	print("
			<form enctype='multipart/form-data' action='import_records.php' method='POST'>
			<input type='hidden' name='MAX_FILE_SIZE' value='128000000' />
			Upload List: <input name='userfile' type='file' />
			<input name='import_document' type='submit' value='Upload' />
			</form>
			");

	print("<div>Document Type:");
	show_filter_select('doctype','doctype', 'type', 'type_name', $doctype);
	print("Status:");
	show_filter_select('status', 'status_name', 'status_id', 'status_name', $status, "status_id < 10");
	print("Employee:");
	print("<input id='id_employee' name='employee' type='text' value=''>");
	print("<input class='btn' type='button' name='search' value='Search' onclick='employee_search()'>");
	print("<input class='btn' type='button' name='reset' value='Reset' onclick='reset_search()'>");
	print("<input class='btn' type='button' name='reset' value='Add' onclick='add_records()'>");

	print("<div id='div_booklist'>");

	$cond = get_cond_from_var($doctype, $status, $uid);
	list_document($view, 17880, $start, $items_perpage,  $cond);
	print("</div>");
}

function manage_record()
{
	global $login_id;
	print("&nbsp;&nbsp;申请：");
	list_record($login_id, 'approve', " (history.status = 1 or history.status = 5) ");
	print("&nbsp;&nbsp;归还：");
	list_record($login_id, 'approve', " history.status = 3 ");
	print("&nbsp;&nbsp;提交:");
}



function add_newbook()
{

	print("<iframe height=1920 width=800 src='edit_book.php?op=edit_book_ui'></iframe>");
}

function edit_book($book_id)
{
	print("<iframe height=1920 width=800 src='edit_book.php?op=edit_book_ui&book_id=$book_id'></iframe>");
}

function update_borrow_times()
{
	$sql = " update books as b inner join ( select book_id, count(status) as cnt from history where status = 0 or status = 2 or status = 3 or status = 4 group by book_id) as x using(book_id) set b.times = x.cnt";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	$rows = mysql_affected_rows();
	print("<br>update $rows lines");
}

?>

</body>
</html>
