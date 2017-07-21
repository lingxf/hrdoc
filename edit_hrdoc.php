<?php
/*
	book library system
	copyright Xiaofeng(Daniel) Ling<xling@qualcomm.com>, 2012, Aug.
*/

include_once 'debug.php';
include_once 'db_connect.php';
include_once 'myphp/disp_lib.php';
include_once 'myphp/common.php';
include_once 'hrdoc_lib.php';
include_once 'hrdoc_records.php';

function print_html_head(){
	print("
			<html>
			<title>Edit Book</title>
			<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
			<meta http-equiv='Content-Language' content='zh-CN' /> 
			");
}

session_name($web_name);
session_start();
$login_id=isset($_SESSION['user'])?$_SESSION['user']:'Guest';

$doctype = get_persist_var('doctype', -1);
$status = get_persist_var('status', -1);
$room = get_persist_var('room', -1);
$submitter = get_persist_var('submitter', -1);
$uid = get_persist_var('uid', '');
	
$role = get_member_role( $login_id);
$userperm = "borrow|read";
$hrperm = $userperm . "|". "modify|edit|add_hrdoc|add$|delete|export";
$admperm = $hrperm . "|". "add_user|del_user";

$book_id = get_url_var('book_id', 0);
if(isset($_POST['op'])) $op=$_POST['op'];
if(isset($_GET['op'])) $op=$_GET['op'];

if(!isset($op))
	exit();

if($role < 1 && preg_match("/$userperm/", $op)){
}else if($role == 1 && preg_match("/$hrperm/", $op)){
}else if($role == 2 && preg_match("/$admperm/", $op)){
}else{
	print("You have no pemission\n");
	return;
}

$comment_id = get_persist_var('comment_id', '');

if($login_id == 'Guest'){
	print('Please login first');
	exit();
}
if($role == -1){
	print('Please Activate your account first');
	exit();
}

if($role < 1 && !preg_match("/add_comment_ui|edit_comment_ui|add_comment|save_comment/",$op)){
	print("You are not member!");
	return;
}

$modified_date = strftime("%Y-%m-%d %H:%M:%S", time());
$create_date = $modified_date;
$file_room =  get_url_var('file_room', '');
$note =  get_url_var('note', '');

if(isset($_POST['cancel'])){
	show_home_link('Back', 'library', '', 0.1);
	return;
}

if($op == 'read' || $op == 'write' || $op=='modify'){
	$book_id=$_POST['book_id'];
	$col=$_POST['col'];
	$text=$_POST['text'];
}else if($op == 'edit' || $op == 'add'){
	$employee_id = get_url_var('employee_id', 0);
	$status = get_url_var('status', -1);
	$doctype =  get_url_var('doctype', -1);
}

if($book_id && $op=="modify"){
	$intext = str_replace("'", "''", $text);
	if($col == 'note'){
		$cm = "$intext<br>";
		$reg = '/\[(\d+)\/(\d+)\]:([^\[]*)([\d\D\n.]*)/';
		if(preg_match($reg, $intext, $matches)){
			$intext = $matches[3];
		}
		$intext = $cm;
		$to = 'xling@qti.qualcomm.com';
		$user = get_user_attr($login_id, 'name');
		$cc = get_user_attr($login_id, 'email');
		$cc = '';
		mail_html($to, $cc, "$user is adding comments for $book_id", $text);
	}
	if($col == 'class')
		$sql = "UPDATE books set `$col`=$intext ";
	else
		$sql = "UPDATE books set `$col`='$intext'";
	$sql .= " where `book_id`=$book_id";
	$res1=update_mysql_query($sql);
	add_log($login_id, $login_id, $book_id, 10);
	$text = str_replace("''", "'", $intext);
	if($col == 'class')
		print(get_class_name($text));
	else
		print($text);
}else if($book_id != 0 && $op=="read"){
	$tt = read_book_column($book_id, $col);
	if($tt == -1)
		print("No this book");
	else
		print $tt;
	return;

}else if($op=="del_user"){
	$user_id = get_url_var('user_id', '');
	$sql = "delete from member where user = '$user_id'";
	$res=update_mysql_query($sql);
	$rows = mysql_affected_rows();
	print("Update $rows<br>");
	add_log($login_id, $login_id, -1, 14, 0, "Delete User $user_id");
	show_home_link('Back', 'user', '', 3);
	return;
}else if($op=="add_user"){
	$user_id = get_url_var('user_id', '');
	$role = get_url_var('role', 1);
	$city = get_url_var('city', 0);
	if($user_id != ''){
		$sql = "insert into member set user = '$user_id', role = $role, city=$city ";
		$res=update_mysql_query($sql);
		$rows = mysql_affected_rows();
		add_log($login_id, $login_id, -1, 13, 0, "Add User $user_id");
		print("Update $rows<br>");
	}
	show_home_link('Back', 'user', '', 3);
	return;
}else if($book_id != 0 && $op=="edit"){
    if($employee_id == 0)
        return;
    $new_book_id = get_doc_id($employee_id, $doctype, 0);
	$sql = "update books set book_id = $book_id, employee_id = '$employee_id', doctype = $doctype, status = $status, modified_date = '$modified_date', file_room= '$file_room', note='$note'";
	$sql .= "where book_id = $book_id";
	$res=update_mysql_query($sql);
	$rows = mysql_affected_rows();
	add_log($login_id, $login_id, $book_id, 10);
	print("Update $rows<br>");
	show_home_link('Back', 'library', '', 3);
	return;
}else if($op=="add"){
	$index = 0;
	while($index < 10 ){
        $book_id = get_doc_id($employee_id, $doctype, $index);
		$sql = "insert into books set book_id = $book_id, employee_id = '$employee_id', doctype = $doctype, status = $status, submitter = '$login_id', create_date = '$create_date', modified_date = '$modified_date' on duplicate key update book_id = $book_id";
		$res=update_mysql_query($sql);
		$rows = mysql_affected_rows();
    	if($rows == 0){
    	    print("Document Already exist<br>");
			$index++;
		}else{
		    print("Add $rows rows, book_id:$book_id <br>");
		    add_log($login_id, $login_id, $book_id, 11, $doctype );
			break;
    	}
	}
	show_home_link('Back', 'library', '', 3);
	return;

}else if($book_id != 0 && $op=="delete"){
	$sql = " select * from books left join user.user on books.employee_id = user.user.Empno where book_id = $book_id";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row=mysql_fetch_array($res)){
	    $name = $row['name'];
	    $doctype = $row['doctype'];
	    $employee_id = $row['employee_id'];
    }
	$sql = " delete from books where book_id = $book_id";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	add_log($login_id, $login_id, $book_id, 12, $doctype );
    print("Deleted $book_id");
	show_home_link('Back', 'library', '', 3);
}else if($op=="borrow"){
    $comment = get_url_var('comment', '');
    print("<script type=\"text/javascript\">window.location.href='hrdoc.php?action=borrow&book_id=$book_id&comment=\"$comment\"';</script>");
}else if($op=="borrow_comment_ui"||$op=="edit_comment_ui"){
    if($op=="edit_comment_ui"){
	    $sql = " select * from history left join books using(book_id) left join user.user on books.employee_id = user.user.Empno left join doctype on books.doctype=doctype.type where record_id = $record_id";
	    $res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	    while($row=mysql_fetch_array($res)){
	    	$book_id = $row['book_id'];
	    	$borrower = $row['borrower'];
	    	$comment = $row['comment'];
            $document = $row['type_name'];
	    	$employee_name = $row['name'];
	    	$employee_id = $row['employee_id'];
        }
        $op = 'edit_comment';
    }else{
        $borrower = $login_id;
        $comment = '';
        $record_id = 0;
	    $sql = " select * from books left join user.user on books.employee_id = user.user.Empno left join doctype on books.doctype=doctype.type where book_id = $book_id";
	    $res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	    while($row=mysql_fetch_array($res)){
	    	$employee_name = $row['name'];
	    	$employee_id = $row['employee_id'];
	    	$user_id = $row['user_id'];
	    	$create_date = $row['create_date'];
	    	$modified_date = $row['modified_date'];
	    	$status = $row['status'];
	    	$doctype =  $row['doctype'];
            $document = $row['type_name'];
	    }
        $op = 'borrow';
    }
	print_html_head();
	print("
		<form method='post' action='edit_hrdoc.php'>
		<table border=1 bordercolor='#0000f0', cellspacing='0' cellpadding='0' style='padding:0.2em;border-color:#0000f0;border-style:solid; width: 600px;background: none repeat scroll 0% 0% #e0e0f5;font-size:12pt;border-collapse:collapse;border-spacing:0;table-layout:auto'>
		<tbody>
		<input type='hidden' name='op' value='$op'>
		<input name='record_id' type='hidden' value='$record_id'>
		<input name='book_id' type='hidden' value='$book_id'>
		<input name='borrower' type='hidden' value='$borrower'>
		<tr class='odd noclick'><th>Borrower:</th><td>$borrower</td></tr>
		<tr class='odd noclick'><th>EmpNo:</th><td>$employee_id</td></tr>
		<tr class='odd noclick'><th>EmpName:</th><td>$employee_name</td></tr>
		<tr class='odd noclick'><th>Document:</th><td>$document</td></tr>
		");
	print("
		<tr><th>Comment:</th><td>
		<textarea wrap='soft' type='text' name='comment' rows='8' maxlength='2000' cols='60'>$comment</textarea>
		</td></tr>
		</tbody>
		</table>
		<input class='btn' type='submit' name='save' value='Save'>
		<input class='btn' type='submit' name='cancel' value='Cancel'>
		</form> ");

}else if($op=="edit_hrdoc_ui"||$op=="add_hrdoc_ui"){
	$time = time();
	$buy_date = strftime("%Y-%m-%d %H:%M:%S", $time);
	if($op == "edit_hrdoc_ui" ) { 
	    $sql = " select * from books left join user.user on books.employee_id = user.user.Empno left join doctype on books.doctype=doctype.type where book_id = $book_id";
		$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
		while($row=mysql_fetch_array($res)){
			$name = $row['name'];
			$employee_id = $row['employee_id'];
			$user_id = $row['user_id'];
			$create_date = $row['create_date'];
			$modified_date = $row['modified_date'];
			$status = $row['status'];
			$doctype =  $row['doctype'];
            $document = $row['type_name'];
            $note = $row['note'];
            $file_room = $row['file_room'];
		}
		$op = 'edit';
        $disabled = 'disabled';
	}else{
		$book_id = ''; 
        $name = '';
        $user_id = '';
		$employee_id = '';
		$status = 0;
		$doctype = 0;
		$op = 'add';
        $disabled = '';
	}
	print_html_head();
	print("
		<form method='post' action='edit_hrdoc.php'>
		<table border=1 bordercolor='#0000f0', cellspacing='0' cellpadding='0' style='padding:0.2em;border-color:#0000f0;border-style:solid; width: 600px;background: none repeat scroll 0% 0% #e0e0f5;font-size:12pt;border-collapse:collapse;border-spacing:0;table-layout:auto'>
		<tbody>
		<input type='hidden' name='op' value='$op'>
		<input name='book_id' type='hidden' value='$book_id'>
		<input name='employee_id' type='hidden' value='$employee_id'>
		<tr class='odd noclick'><th>ID:</th><td>$book_id</td></tr>
		<tr><th>EmpNo:</th><td><input name='employee_id' $disabled type='text' value='$employee_id' ></td></tr>
		<tr><th>UserID:</th><td>$user_id</td></tr>
		<tr><th>Name:</th><td>$name</td></tr>
        ");

    print("<tr><th>Status:</th><td>");
	show_filter_select('status', 'status_name', 'status_id', 'status_name', $status);
    print("</td></tr>");

	print("<tr><th>Document:</th><td>");
    if($op == 'add')
	    show_filter_select('doctype','doctype', 'type', 'type_name', $doctype);
    else
	    print($document);
    print("</td></tr>");

	print("<tr><th>FileRoom:</th><td>");
	show_filter_select('file_room', 'file_room', 'id', 'room_name', $file_room);
    print("</td></tr>");

	print("<tr><th>Note:</th><td>");
	print("<textarea wrap='soft' type='text' name='note' rows='8' maxlength='2000' cols='60'>$note</textarea>");
    print("</td></tr>");

    print("
		</tbody>
		</table>
		<input class='btn'  type='submit' name='save' value='Save'>
		<input class='btn' type='submit' name='cancel' value='Cancel'>
		</form> ");
}else if($op == 'export_database'){
	if(isset($_POST['export_document'])||isset($_GET['export_document'])){
		$create_date = get_persist_var('create_date', -1);
		$cond = get_cond_from_var($doctype, $status, $uid, $room, $submitter, $create_date);
		$sql = "select employee_id, user_id, name, type_name as doctype, status_name, create_date, modified_date, book_id, room_name, submitter, note ".
			"from books left join doctype on books.doctype = doctype.type left join status_name on books.status = status_name.status_id left join file_room on books.file_room = file_room.id left join user.user on user.user.Empno = books.employee_id".
			" where $cond ";
		error_log($sql);
		export_excel_by_sql($sql, 'hrdoc-list.xls', 'HRDoc list', array(10, 20,20,20, 20, 30,20, 10,10,10,20,50, 10, 20, 80));
		exit();
	}
	if(isset($_POST['export_history'])){
		$sql = "select * ".
			"from history".
			" where 1 ";
		export_excel_by_sql($sql, 'hrdoc-history.xls', 'HRDoc history', array(10, 20,20,20, 20, 30,20, 10,10,10,20,50, 10, 20, 80));
		exit();
	}
	print("No export");
}else{

	print("unsupported $op");
}

?>
