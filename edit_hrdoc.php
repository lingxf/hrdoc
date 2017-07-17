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

$role = get_member_role( $login_id);
$book_id = get_url_var('book_id', 0);
if(isset($_POST['op'])) $op=$_POST['op'];
if(isset($_GET['op'])) $op=$_GET['op'];

if(!isset($op))
	exit();

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

if($op == 'read' || $op == 'write' || $op=='modify'){
	$book_id=$_POST['book_id'];
	$col=$_POST['col'];
	$text=$_POST['text'];
}else if($op == 'edit' || $op == 'add'){
	$employee_id = get_url_var('employee_id', 0);
	$status = get_url_var('status', -1);
	$doctype =  get_url_var('doctype', -1);
	$note =  get_url_var('note', '');
	$file_room =  get_url_var('file_room', '');
    if($op == 'add')
        $book_id = $employee_id * 100 + $doctype;
}

if($book_id && $op=="modify"){
	$intext = str_replace("'", "''", $text);
	if($col == 'comments'){
		$cm = "[$login_id]$intext<br>";
		$reg = '/\[(\d+)\/(\d+)\]:([^\[]*)([\d\D\n.]*)/';
		if(preg_match($reg, $intext, $matches)){
			$intext = $matches[3];
		}
		add_comment($book_id, $login_id, $intext);
		$tt =  read_book_column($book_id, $col);
		$bookname = read_book_column($book_id, 'name');
		if($tt != -1)
			$cm .= $tt;
		$intext = $cm;
		$to = 'QClub.BJ.Reading@qti.qualcomm.com';
		#$to = 'xling@qti.qualcomm.com';
		$user = get_user_attr($login_id, 'name');
		$cc = get_user_attr($login_id, 'email');
		$cc = '';
		#mail_html($to, $cc, "$user is adding comments for book <$bookname>", $text);
	}
	if($col == 'class')
		$sql = "UPDATE books set `$col`=$intext ";
	else
		$sql = "UPDATE books set `$col`='$intext'";
	$sql .= " where `book_id`=$book_id";
	$res1=update_mysql_query($sql);
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

}else if($book_id != 0 && $op=="edit"){
    if($employee_id == 0)
        return;
    $new_book_id = $employee_id * 100 + $doctype;
	$sql = "update books set book_id = $book_id, employee_id = '$employee_id', doctype = $doctype, status = $status, modified_date = '$modified_date', file_room= '$file_room', note='$note'";
	$sql .= "where book_id = $book_id";
	$res=update_mysql_query($sql);
	$rows = mysql_affected_rows();
	add_log($login_id, $login_id, $book_id, 8);
	print("Update $rows<br>");
	show_home_link('Back', 'library', '', 3);
	return;
}else if($op=="add"){
	$sql = "insert into books set book_id = $book_id, employee_id = '$employee_id', doctype = $doctype, status = $status, create_date = '$create_date', modified_date = '$modified_date' on duplicate key update book_id = $book_id";
	$res=update_mysql_query($sql);
	$rows = mysql_affected_rows();
    if($rows == 0)
        print("Document Already exist<br>");
    else{
	    print("Add $rows rows, book_id:$book_id <br>");
	    add_log($login_id, $login_id, $book_id, 7, $doctype );
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
	add_log($login_id, $login_id, $book_id, 6, $doctype );
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
	    $sql = " select * from books left join user.user on books.employee_id = user.user.Empno left join doctype on doctype=type where book_id = $book_id";
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
	    $sql = " select * from books left join user.user on books.employee_id = user.user.Empno left join doctype on doctype=type where book_id = $book_id";
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
	print("<input name='file_room' type='text' value='$file_room' >");
    print("</td></tr>");

	print("<tr><th>Note:</th><td>");
	print("<textarea wrap='soft' type='text' name='note' rows='8' maxlength='2000' cols='60'>$note</textarea>");
    print("</td></tr>");

    print("
		</tbody>
		</table>
		<input class='btn'  type='submit' name='save' value='Save'>
		</form> ");
}else{
	print("unsupported $op");
}

?>
