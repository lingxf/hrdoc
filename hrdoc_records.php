<?php

function out_record()
{
	global $login_id;
	list_record($login_id, 'out');
}
/* 0x100 cancel 
   0x101 reject
   0x104 wait 260
   0x105 share 
   0x106 share_done
   0x107 apply_join
   0x108 approve_member
   0x109 add score
   0x110 share_cancel
   0x111 add want
 */

$table_head = "<table>";

$record_title_op      = array('序号','借阅人', '文档','编号','申请日期', '借出日期', '归还日期','入库日期', '状态', '操作'); 
$record_title_lend    = array('序号','借阅人', '文档','编号','申请日期', '借出日期', '状态', '操作');
$record_title_history = array('序号','借阅人', '文档','编号','申请日期', '借出日期', '归还日期','入库日期' ); 
$record_title_timeout = array('序号','借阅人', '文档','编号','申请日期', '借出日期', '到期日期','状态', '操作');

$record_format = array(
'self' => array($record_title_op, 
		'record_id, borrower, name as user_name, type_name as name, history.status, books.status as bstatus, data, adate, bdate,rdate,sdate, history.book_id',
		'history left join `books` using (`book_id`) left join user.user on user.user.user_id = history.borrower left join doctype on doctype.type = books.doctype',
		' (history.status = 2 or history.status = 3 or history.status = 1) ',
        ''),
'approve'=>array($record_title_op,
		'record_id, borrower, history.status, type_name as name, misc, name as user_name, data, adate, bdate,rdate,sdate, history.book_id',
		'history left join `books` using (`book_id`) left join user.user on user.user.user_id = history.borrower left join doctype on doctype.type = books.doctype',
		' 1 ',
        ''),
'out'=>array($record_title_lend, 
		'record_id, borrower, history.status, type_name as name, misc, name as user_name, data, adate, bdate,rdate,sdate, history.book_id',
		'history left join `books` using (`book_id`) left join user.user on user.user.user_id = history.borrower left join doctype on doctype.type = books.doctype',
		'history.status  = 2 ',
        ''),
'history'=>array($record_title_history, 
		'record_id, borrower, history.status, type_name as name, misc, name as user_name, data, adate, bdate,rdate,sdate, history.book_id',
		'history left join `books` using (`book_id`) left join user.user on user.user.user_id = history.borrower left join doctype on doctype.type = books.doctype',
		' history.status = 0 ',
		' sdate desc'),
'timeout'=>array($record_title_timeout, 
		'record_id, borrower, history.status, type_name as name, misc, name as user_name, data, adate, bdate,rdate,sdate, history.book_id',
		'history left join `books` using (`book_id`) left join user.user on user.user.user_id = history.borrower left join doctype on doctype.type = books.doctype',
		' 1 ',
		'bdate asc'),
);

function get_field_title($format, $condition)
{
	global $record_format, $link;

	if(array_key_exists($format, $record_format)){
		$title = $record_format[$format][0];
		$field = $record_format[$format][1];
		$db = $record_format[$format][2];
		$cond = $record_format[$format][3];
		$order_cond = $record_format[$format][4];

		mysql_select_db("docdb",$link);

		print_tdlist($title);
		$sql = " select $field from $db where $cond ";
		if($condition != ''){
			if($format == 'timeout')
				$sql .= " and (to_days(now())  - to_days(bdate)) >= $condition ";
			else
				$sql .= " and $condition";
		}
		if($order_cond == '')
			$sql .= "order by adate desc ";
		else
			$sql .= "order by $order_cond";

		$sql .= " limit 100 ";
		return $sql;
	}

	print("not support $format");
	return " 1 ";
}

function list_record($login_id, $format='self', $condition='')
{
	global $role, $table_head, $role_city, $disp_city;
	global $home_page;
	$role_city = isset($role_city)?$role_city:0;
	$disp_city = isset($disp_city)?$disp_city:0;
	$cond = " 1 ";
	if($disp_city != 255 && $disp_city != '')
		$cond .= " and books.city = $role_city ";
    $res = mysql_query("select database()") or die(mysql_error()."error get db");
	$book_db = mysql_result($res, 0); 
	$mail_url = get_cur_php();
	if($login_id == -2){
		$book_db = 'book';
		$mail_url = "http://cedump-sh.ap.qualcomm.com/hrdoc/$home_page";
	}
	if($mail_url == ''){
		$mail_url = "http://cedump-sh.ap.qualcomm.com/hrdoc/$home_page";
	}
	print_table_head('list');
	$sql = get_field_title($format, $condition);
	$i = 0;
	$res = read_mysql_query($sql);
	while($row=mysql_fetch_array($res)){
		print("<tr>");
		$record_id = $row['record_id']; 
		$borrower_id = $row['borrower']; 
		$borrower = $row['user_name']; 
		$book_id = $row['book_id']; 
		$name = isset($row['name'])?$row['name']:''; 
		$name = "<a href='$mail_url?action=show_borrower&book_id=$book_id'>$name</a>";
		$adate= $row['adate']; 
		$bdate= $row['bdate']; 
		$rdate= $row['rdate']; 
		$sdate= $row['sdate']; 
		$score = $row['data'];
		$time = time();
		$nowdate = strftime("%Y-%m-%d", $time);
		if($format == 'self'){
			$adate = substr($adate, 0, 10);
			$bdate = substr($bdate, 0, 10);
			$rdate = substr($rdate, 0, 10);
			$sdate = substr($sdate, 0, 10);
		}
		$status = $row['status'];
		$status_text = "";
		$blink = "";
		if($format == 'approve' || $format == 'out' || $format == 'timeout'){
			if($status == 1){
				$status_text = "申请中";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=lend\">批准</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=reject\">拒绝</a>";
			}else if($status == 2){
				$status_text = "借出";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=push\">催还</a>";
				if(substr($bdate, 0, 10) == $nowdate)
					$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=stock\">入库</a>";
				else
					$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=stock\">入库</a>";
				$rdate = $bdate + 28;
				$ldate = strtotime($bdate) + 28*24*3600; 
				$rdate = strftime("%Y-%m-%d", $ldate);
			}else if($status == 3){
				$status_text = "归还中";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=stock\">入库</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=reject_return\">拒绝</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=push\">催还</a>";
			}else if($status == 4 || $status == 0x104 ){
				$status_text = "等候";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=lend\">批准</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=reject_wait\">拒绝</a>";
			}else if($status == 5){
				$status_text = "续借";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=approve_renew\">批准</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=reject_wait\">拒绝</a>";
			}else if($status == 0){
				$status_text = "已还";
			}else{
				$status_text = "取消";
			}
			if($role < 2)
				$blink = '';
		}else if($format == 'self'){
			$bstatus = $row['bstatus'];
			if($status == 0){
				$status_text = "已还";
				$url = "$home_page?action=list_favor";
				$blink = "<a href='javascript:add_score(this,$book_id)'>评分</a>";
				$blink .= "&nbsp;<a href='javascript:show_share_choice(this,$book_id)'>分享</a>";
			}else if($status == 1){
				$status_text = "借阅中";
			}else if($status == 2){
				$status_text = "借出";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=returning\">归还</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=renew\">续借</a>";
				$rdate = $bdate + 28;
				$ldate = strtotime($bdate) + 28*24*3600; 
				$rdate = strftime("%Y-%m-%d", $ldate);
			}else if($status == 3){
				$status_text = "归还中";
				$blink = "";
			}else if($status == 4 || $status == 0x104){
				$status_text = get_book_status_name($bstatus);
				$blink = "<a href=\"$home_page?record_id=$record_id&action=cancel\">取消</a>";
				if($bstatus == 0)
					$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=borrow\">借阅</a>";
			}else if($status == 5){
				$status_text = "续借中";
				#$blink = "<a href=\"$home_page?record_id=$record_id&action=cancel\">取消</a>";
			}else if($status == 0x100){
				$status_text = "取消";
			}else if($status == 0x101){
				$status_text = "拒绝";
			}else{
				$status_text = "其它";
			}
		}else if($format == 'waityou'){
			if($status == 0x104){
				$status_text = "等候";
				$blink = "<a href=\"$home_page?record_id=$record_id&action=transfer\">转移</a>";
			}
		}else if($format == 'member'){
			if($status == 0x107){
				$status_text = "申请";
				$blink = "<a href=\"$home_page?record_id=$record_id&borrower=$borrower_id&action=approve_member\">批准</a>";
			}
		}

		$i++;

		if($format == 'out')
			print_tdlist(array($i,$borrower, $name,$book_id,  $adate, $bdate, $status_text, $blink)); 
		else if($format == 'history')
			print_tdlist(array($i,$borrower, $name,$book_id,  $adate, $bdate, $rdate,$sdate)); 
		else if($format == 'share'){
			if($book_id == 0)
				$name = $row['name'].":".$row['misc'];
			if($status == 0x105 && $role == 2 && $login_id != -2){
				$blink = "<a href=\"$home_page?record_id=$record_id&action=share_done\">完成</a>";
				$blink .= "&nbsp;<a href=\"$home_page?record_id=$record_id&action=share_cancel\">取消</a>";
				$blink .= "&nbsp;<a href=\"edit_$home_page?record_id=$record_id&op=edit_share_ui\">编辑</a>";
			}
			print_tdlist(array($i,$borrower, $name,$book_id,  $adate, $sdate, $blink)); 
		}else if($format == 'member')
			print_tdlist(array($i,$borrower_id, $borrower,$adate, $sdate, $blink)); 
		else if($format == 'score')
			print_tdlist(array($i,$borrower, $name,$book_id,  $adate, $bdate, $rdate,$sdate, get_book_status_name($row['bstatus']), $score)); 
		else if($format == 'timeout')
			print_tdlist(array($i,$borrower, $name,$book_id,  $adate, $bdate, $rdate,$status_text, $blink)); 
		else
			print_tdlist(array($i,$borrower, $name,$book_id,  $adate, $bdate, $rdate,$sdate, $status_text, $blink)); 
		print("</tr>\n");
	}
	print("</table>");
}

function borrow_book($book_id, $borrower, $comment)
{
	global $max_books;
	if(!check_record($book_id, $borrower))
		return false;

	$sql = " select * from history where borrower='$borrower' and (status = 1 or status = 2)";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	$rows = mysql_num_rows($res);
	if($rows >= $max_books){
		print ("You already reached the maximum books:$rows >= $max_books !");
		return false;
	}
	add_record($book_id, $borrower, 1, false, 0, $comment);
	set_book_status($book_id, 1);
	return true;
}

function renew_book($book_id, $record_id, $login_id)
{
	global $max_books;
	$sql = " select * from history left join books using (book_id) where book_id = $book_id and history.status = 0x104 ";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row = mysql_fetch_array($res)){
		print ("Someone wait, Can not renew!");
		return false;
	}
	set_record_status($record_id, 5); 
	return true;
}

function get_bookname($book_id)
{
	$sql = " select * from books left join doctype on books.doctype = doctype.type where book_id=$book_id";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row=mysql_fetch_array($res)){
		$bookname = $row['type_name'];
		return $bookname;
	}
	return '';
}

function get_record_by_bookid($book_id)
{

	$sql = " select record_id, borrower, t1.status, adate, bdate,rdate,sdate, t1.book_id from history t1, books t2, member t3 where t1.book_id=$book_id and t1.book_id = t2.book_id and t3.user = t1.borrower and t1.status != 0 and t1.status < 6 and t1.status != 4 order by `adate` asc";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row=mysql_fetch_array($res)){
		$borrower = $row['borrower'];
		$record_id = $row['record_id'];
		return array($record_id, $borrower, 0);
	}
	return array('', '', 0);
}


function check_record($book_id, $login_id)
{
	global $role;
	if($role == 0){
		print("You are not a member!");
		return false;
	}
	$sql = " select * from history where borrower='$login_id' and book_id=$book_id and (status < 0x100 and status !=0 or status = 0x104) ";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row = mysql_fetch_array($res)){
		if($row['status'] == 0x104)
			print ("You already wait this book, please borrow from the old record!");
		else
			print ("You already borrowed this book!");
		return false;
	}
	return true;
}

function add_record($book_id, $user_id, $status=1, $record_id=false, $data=0, $comment='')
{
	$time = time();
	$time_start = strftime("%Y-%m-%d %H:%M:%S", $time);
	$sql = " insert into history set `borrower`='$user_id', book_id=$book_id, adate= '$time_start', status=$status, data=$data, comment='$comment'";
	$res = update_mysql_query($sql);
	if($record_id){
		$sql = " select * from history where `borrower`='$user_id' and book_id=$book_id and adate= '$time_start' and status=$status";
		$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
		while($row = mysql_fetch_array($res)){
			return $row['record_id'];
		}
		return $record_id;
	}
	return 0;
}

function add_record_one($book_id, $user_id, $adate, $bdate, $rdate, $sdate, $status=1, $data=0, $misc='')
{
	$time = time();
	$time_start = strftime("%Y-%m-%d %H:%M:%S", $time);
	$sql = " insert into history set `borrower`='$user_id', book_id=$book_id, adate='$adate', bdate= '$bdate', rdate='$rdate', sdate='$sdate', status=$status, data=$data, misc='$misc'";
	$res = update_mysql_query($sql);
	return $res;
}

function add_record_full($book_id, $user_id, $bdate, $sdate, $status=1, $data=0, $misc='')
{
	$time = time();
	$time_start = strftime("%Y-%m-%d %H:%M:%S", $time);
	$sql = " insert into history set `borrower`='$user_id', book_id=$book_id, adate='$bdate', bdate= '$bdate', rdate='$sdate', sdate='$sdate', status=$status, data=$data, misc='$misc'";
	$res = update_mysql_query($sql);
	return $res;
}

function get_bookid_by_borrower($borrower)
{
	$book_ids = array();
	$sql = " select * from history where `borrower` = '$borrower' and status = 2 ";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row = mysql_fetch_array($res)){
		$book_id = $row['book_id'];
		$book_ids[] = $book_id;
	}
	return $book_ids;
}

function get_bookid_by_record($record_id)
{
	$sql = " select * from history where `record_id` = $record_id";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row = mysql_fetch_array($res)){
		$book_id = $row['book_id'];
		return $book_id;
	}
	return 0;
}

function get_borrower_by_record($record_id)
{
	$sql = " select * from history where `record_id` = $record_id";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row = mysql_fetch_array($res)){
		$borrower = $row['borrower'];
		return $borrower;
	}
	return 0;
}


function set_record_status($record_id, $status)
{
	dprint("set_record_status:$record_id:$status<br>");
	$time = time();
	$time_start = strftime("%Y-%m-%d %H:%M:%S", $time);
	if($status == 0)
		$sql = " update history set sdate= '$time_start', status=$status where `record_id` = $record_id";
	else if($status == 1)
		$sql = " update history set bdate= '$time_start', status=$status where `record_id` = $record_id";
	else if($status == 2)
		$sql = " update history set bdate= '$time_start', status=$status where `record_id` = $record_id";
	else if($status == 3)
		$sql = " update history set rdate= '$time_start', status=$status where `record_id` = $record_id";
	else if($status == 4)
		$sql = " update history set adate= '$time_start', status=$status where `record_id` = $record_id";
	else if($status == 5)
		$sql = " update history set rdate= '$time_start', status=$status where `record_id` = $record_id";
	else if($status == 0x106 || $status == 0x105)
		$sql = " update history set sdate= '$time_start', status=$status where `record_id` = $record_id";
	else
		$sql = " update history set adate= '$time_start', status=$status where `record_id` = $record_id";

	dprint("$sql<br>");
	$res = update_mysql_query($sql);
	if($status < 0x100){
		$book_id = get_bookid_by_record($record_id);
		set_book_status($book_id, $status);
	}
}

function get_book_status($book_id)
{
	$sql = "select status from books where book_id=$book_id";
	$res = mysql_query($sql) or die("Invalid query:".$sql.mysql_error());
	if($row = mysql_fetch_array($res)){
		return $row['status'];
    }
	return -1;
}
function set_book_status($book_id, $status)
{
	$sql = "update books set `status` = $status where book_id=$book_id";
	if($status == 2)
		$sql = "update books set `status` = $status, `times` = `times` + 1 where book_id=$book_id";
	$res = update_mysql_query($sql);
	$rows = mysql_affected_rows();
	if($rows != 0){
		return true;
    }
	return false;
}

function get_borrower($book_id)
{
	$r = array();
	$r = get_record_by_bookid($book_id);
	return $r[1];
}

function get_member_role($user)
{
	return get_member_attr($user, 'role');
}

function set_member_attr($user, $prop, $value) {
	$sql = "update member set `$prop` = '$value' where `user` = '$user' ";
	$res=mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($rows=mysql_affected_rows() > 0)
		return true;
	return false;
}

function get_member_attr($user, $prop) {
	$sql1 = "select * from member where user ='$user'";
	if($prop == 'name')
		$prop = 'user_name';
	$res1=mysql_query($sql1) or die("Invalid query:" . $sql1 . mysql_error());
	if($row1=mysql_fetch_array($res1)){
		if(isset($row1[$prop]))
			return $row1["$prop"];
	}
	return -1;
}

function get_user_name($user){
	return get_user_attr($user, 'name');
}

function get_user_email($user){
	return get_user_attr($user, 'email');
}

function get_user_attr($user, $prop) {
	$sql1 = "select * from user.user where user_id ='$user'";
	$res1=mysql_query($sql1) or die("Invalid query:" . $sql1 . mysql_error());
	if($row1=mysql_fetch_array($res1)){
		if(isset($row1["$prop"]))
			return $row1[$prop];
	}
	$sql1 = "select * from member where user ='$user'";
	if($prop == 'name')
		$prop = 'user_name';
	$res1=mysql_query($sql1) or die("Invalid query:" . $sql1 . mysql_error());
	if($row1=mysql_fetch_array($res1)){
		if(isset($row1[$prop]))
			return $row1["$prop"];
	}
	return -1;
}

function set_user_attr($user, $prop, $value) {
	$sql1 = "update user.user set `$prop` = '$value' where user_id ='$user'";
	$res1=mysql_query($sql1) or die("Invalid query:" . $sql1 . mysql_error());
	if($row1=mysql_affected_rows($res1) > 0)
		return true;
	return false;
}

function get_admin_mail()
{
	$sql = "select user, email from member where role = 2";
	$res = read_mysql_query($sql);
	$cc = "";
	while($row = mysql_fetch_array($res)){
		$user = $row['user'];
		if($user == 'xling')
			continue;
		$cc .= $row['email'];
		$cc .= ";";
	}
	return 'xling@qti.qualcomm.com';
}

function add_log($login_id, $borrower, $book_id, $status, $doctype = 0, $name='')
{
	$sql = " insert into log set `operator`='$login_id', book_id=$book_id, member_id = '$borrower', status=$status, name = '$name', doctype = $doctype ";
	dprint("add_log:$sql <br>");
	$res = update_mysql_query($sql);
	$rows = mysql_affected_rows();
	dprint("rows:$rows<br>");
	return true;
}

function list_log($format='normal')
{
	global $login_id, $role;

	$table_name = "log";
	$tr_width = 800;
	$background = '#efefef';
	print("<table id='$table_name' width=600 class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 style='width:$tr_width.0pt;background:$background;margin-left:20.5pt;border-collapse:collapse'>");
	if($format == 'normal')
		print_tdlist(array('Date', 'Operator','Doc Id', 'Document','EmpNo', 'Name','Action'));
	$sql = " select f1.book_id, f1.operator, member_id, user_id, f1.timestamp, f1.name as user_name, f3.name as user_name, type_name, f1.status from log f1, books f2 left join doctype on f2.doctype = doctype.type , user.user f3 where f1.book_id = f2.book_id and f1.member_id = f3.user_id order by timestamp desc";
	$sql = " select f1.book_id, f1.operator, member_id, f1.timestamp, f1.name as user_name, type_name, f1.status, status_name from log f1 left join doctype f2 on f1.doctype = f2.type left join status_name f3 on f1.status = f3.status_id order by timestamp desc";

    print $sql;
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	while($row=mysql_fetch_array($res)){
		$book_id = $row['book_id']; 
		$operator = $row['operator'];
		$member_id= $row['member_id'];
		$timestamp= $row['timestamp'];
		$bookname = $row['type_name'];
		$username = $row['user_name'];
		$status=$row['status'];	
        $status_text = $row['status_name'];

/*
		if($status == 0){
			$status_text = "还入";
		}else if($status == 10){
			$status_text = "新加";
		}else if($status == 11){
			$status_text = "新购";
		}else if($status == 264){
			$status_text = "入会";
		}else{
			$status_text = "借出";
		}
*/
		$bcolor = 'white';
		if($status != 0)
			$bcolor = '#efcfef';
		print("<tr style='background:$bcolor;'>");
		if($format == 'normal'){
			print_tdlist(array($timestamp, $operator, $book_id, $bookname, $member_id, $username, $status_text)); 
		}
		print("</tr>\n");
	}
	print("</table>");
}


?>
