<?php

function show_browser_button($hasprev=true, $hasmore=true)
{
	print('<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	print('<input type="submit"'); print(' name="begin" value="Begin" />   ');
	print('<input type="submit"'); if(!$hasprev) print(" disabled "); print(' name="prev" value="Prev" />   ');
	print('<input type="submit"'); if(!$hasmore) print(" disabled "); print(' name="next" value="Next" />   ');
	print('<input type="submit"');  print(' name="end" value="End" />   ');
}

function show_op($field, $value, $row='')
{
	if($field == 'op')
		return ("<a href=edit_hrdoc.php?op=borrow_comment_ui&book_id=$value>Borrow</a>");
	return $value;
}

function show_hrdoc($login_id)
{
	$sql = "select EmpNo, user_id, name, type_name, status_name, file_room, book_id as op from books a left join user.user b on a.employee_id = b.EmpNo left join doctype c on a.doctype = c.type left join status_name d on a.status = d.status_id where b.user_id = '$login_id'";	
	$field = array('EmpNo', 'ID', 'Name', 'Document', 'Status', 'File Room', 'Op');
	$width = array(50, 50, 100, 100, 50, 50, 100);
	show_table_by_sql('mydoc', 'hrdoc', 800, $sql, $field, $width, 'show_op', 2); 

}


function show_doc_list($field, $value, $row='')
{
	if($field == 'op'){
		$op = "<a href=edit_hrdoc.php?op=borrow_comment_ui&book_id=$value>Borrow</a>" .
        "&nbsp;" .
	    "<a onclick='javascript:return confirm(\"Do you really want to delete?\");' href=edit_hrdoc.php?op=delete&book_id=$value>Delete</a>";
        return $op;
    }else if($field == 'employee_id'){
		$url = "<a href=http://people.qualcomm.com/servlet/PhotoPh?fld=def&mch=eq&query=$value&org=0&lst=0&srt=cn&frm=0>$value</a>";
		return $url;
	}else if($field == 'book_id'){
		return ("<a href=edit_hrdoc.php?op=edit_hrdoc_ui&book_id=$value>$value</a>");
    }
	return $value;
}



function show_filter_select($name, $tb_name, $id, $field_name, $default_value=-1, $cond=1)
{
	print("<select id='sel_$name' name='$name' onchange='change_filter_field(\"$name\", this.value)'>");
	$class_list = get_tb_list('docdb', $tb_name, $id, $field_name, $cond);
	if($default_value == -1)
		$select = "selected";
	print("<option value='-1' $select>All</option> ");
	foreach($class_list as $key => $class_text) {
		print("<option value='$key' ");
		if($default_value == $key) print("selected");
		if($key != -1)
			print(" >$key-$class_text</option>");
		else
			print(" >$class_text</option>");
	}
	print("</select>");
}


function list_document($view, $empno, $start, $items_perpage, $cond=1, $order='')
{
	$dbfield = "book_id, employee_id, name, type_name, status_name, file_room, submitter, note, book_id as op";
	$sql = "select $dbfield from books a left join user.user b on a.employee_id = b.EmpNo left join doctype c on a.doctype = c.type left join status_name d on a.status = d.status_id where $cond ";	
    $sql .= " and employee_id != 0 ";

	$res1 = read_mysql_query($sql);
	$rows = mysql_num_rows($res1);
	if($start >= $rows){
		$start = $rows - $items_perpage;
		if($start < 0)
			$start = 0;
		$_SESSION['start'] = $start;
	}

	$hasmore = false;
	$hasprev = false;
	$end = $start+$items_perpage-1;
	if($end < $rows -1 ){
		$hasmore = true;
	}
	if($start > 0)
		$hasprev = true;

	$sql .= "limit $start, $items_perpage";
	$field = array('Doc ID', 'EmpNo', 'Name', 'Document', 'Status', 'File Room', 'Submitter','Note','Op');
	$width = array(20, 30, 50, 80, 80, 50, 50, 80);
	print('<form enctype="multipart/form-data" action="hrdoc.php" method="POST">');
	show_browser_button($hasprev, $hasmore);
	$startd = $start + 1;
	$endd = $end + 1;
	print("($startd-$endd/$rows)");
	show_table_by_sql('mydoc', 'hrdoc', 800, $sql, $field, $width, 'show_doc_list', 2); 
	show_browser_button($hasprev, $hasmore);
	print('</form');
}

function get_total_documents()
{
	$sql = " select * from books";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	$rows = mysql_num_rows($res);
	return $rows;
}

function get_cond_from_var($doctype, $status, $uid)
{
	$cond = " 1 " ;
	if($doctype != -1)
		$cond .= " and doctype = $doctype";
	if($status != -1)
		$cond .= " and status = $status";
	if($uid != -1 && $uid != ''){
		if(is_numeric($uid))
			$cond .= " and employee_id = '$uid' ";
		else
			$cond .= " and user_id = '$uid' ";
	}
	dprint("cond:<$cond>");
	return $cond;
}

function show_home_link($str="Home", $action='', $more='', $seconds=5){

	if($action!='')
        $url = "<a href=\"hrdoc.php?action=$action\">$str</a>" . $more;
    else
    	$url = "<a href=\"hrdoc.php\">$str</a>" . $more;
    print($url);
    if($seconds != 0)
    	print("<script type=\"text/javascript\">setTimeout(\"window.location.href='hrdoc.php?action=$action'\",1000*$seconds);</script>");
}

function show_user()
{
		print("
			<form enctype='multipart/form-data' action='import_records.php' method='POST'>
			<input type='hidden' name='MAX_FILE_SIZE' value='128000000' />
			Upload List: <input name='userfile' type='file' />
			<input name='import_user' type='submit' value='Import User' />
			</form>
			");
		print("
			<form enctype='multipart/form-data' action='edit_hrdoc.php' method='POST'>
			<input type='hidden' name='op' value='add_user' />
			User_Id:<input name='user_id' type='text' value='' />
			<INPUT type=radio name=\"role\" value=\"2\">Admin</>
			<INPUT type=radio name=\"role\" checked value=\"1\">HR</>
			<input name='import_user' type='submit' value='Add User' />
			");
		$sql = "select user as `User ID`, b.name as Name, ".
			"case role when 2 then 'Admin' when 1 then 'HR' else 'User' end as Role,".
			" b.email as email, city,".
			" concat('<a href=edit_hrdoc.php?op=del_user&user_id=', user, '>Delete</a>') as op".
			" from member a left join user.user b on a.user = b.user_id".
			" where user != 'xling' ";
		show_table_by_sql('member', 'docdb', 800, $sql);
}

function show_export()
{
		print("
			<form enctype='multipart/form-data' action='edit_hrdoc.php' method='POST'>
			<input type='hidden' name='op' value='export_database' />
			<input name='export_document' type='submit' value='Export Document List' />
			<input name='export_history' type='submit' value='Export History' />
			");
}

?>
