<?php 
function show_browser_button($hasprev=true, $hasmore=true)
{
	print('<span>&nbsp;&nbsp;&nbsp;');
	print('<input type="submit"'); print(' name="begin" value="Begin" />   ');
	print('<input type="submit"'); if(!$hasprev) print(" disabled "); print(' name="prev" value="Prev" />   ');
	print('<input type="submit"'); if(!$hasmore) print(" disabled "); print(' name="next" value="Next" />   ');
	print('<input type="submit"');  print(' name="end" value="End" />   ');
}

function show_op($field, $value, $row)
{
	if($field == 'op')
		return ("<a href=edit_hrdoc.php?op=borrow_comment_ui&book_id=$value>Borrow</a>");
	return $value;
}

function show_hrdoc($login_id)
{
	list_document(0, 0, " user_id = '$login_id'");
	return;
}

function show_filter_select_by_sql($name, $sql, $default_value=-1)
{
	$res = read_mysql_query($sql);
	while($rows = mysql_fetch_array($res)){
		$class_list[$rows[0]] = $rows[1]; 	
	}
	show_filter_select_by_array($name, $class_list, $default_value);
}

function show_filter_select_by_array($name, $class_list, $default_value=-1, $disable=false)
{
	$dis = "";
	if($disable)
		$dis = "disabled";
	
	print("<select id='sel_$name' $dis name='$name' onchange='change_filter_field(\"$name\", this.value)'>");
	$select = "";
	if($default_value == -1)
		$select = "selected";
	print("<option value='-1' $select>All</option> ");
	foreach($class_list as $key => $class_text) {
		print("<option value='$key' ");
		if($default_value == $key) print("selected");
		if($key != -1 && is_numeric($key))
			print(" >$key-$class_text</option>");
		else
			print(" >$class_text</option>");
	}
	print("</select>");
}

function show_filter_select($name, $tb_name, $id, $field_name, $default_value=-1, $cond=1, $disable=false)
{
	$class_list = get_tb_list('docdb', $tb_name, $id, $field_name, $cond);
	show_filter_select_by_array($name, $class_list, $default_value, $disable);
}

function show_doc_list($index, $field, $value, $row, &$td_attr, &$width)
{
	global $role;

	$fields = array('No.', 'status', 'EmpNo', 'Name', 'Document', 'Ind', 'Status', 'File Room', 'Submitter','Note','Created','Modified', 'Op');
	$widths = array(20, -1, 30, 50, 80, 20, 80, 20, 30, 80);


	if($field == '((title))'){
		if($index == -1)
			return $row;
		if($value == 'Note')
			$width = 150;
		else if(isset($widths[$index]))
			$width = $widths[$index];
		return $fields[$index];
	}	

	/*tr line*/
	$colors = array('#b1e0cf','#7595a7', '#99cb8e','#98995c','#d9ac6d','#c8b1c3');
	if($index == -1){
		if($field == '((sum))')
			return $row;
		$col = $colors[$row['status']];
		$td_attr = "style='height:15.0pt;background:$col;'";
		return $row;
	}

	/*sum td*/
	if($index >= 1000){
		if($field == 'status')
			$width = -1;
		return $value;
	}

	if($field == 'status'){
		$width = -1;
	}else if($field == 'op'){
		$status = $row['status'];
		if($status != 0)
			return "";
		$op = "<a href=edit_hrdoc.php?op=borrow_comment_ui&book_id=$value>B</a>";
		if($role >= 1)
			$op .= "&nbsp;" .  "<a href=edit_hrdoc.php?op=edit_hrdoc_ui&book_id=$value>E</a>";
		if($role >= 2)
        	$op .= "&nbsp;" .  "<a onclick='javascript:return confirm(\"Do you really want to delete?\");' href=edit_hrdoc.php?op=delete&book_id=$value>D</a>";
        return $op;
    }else if($field == 'employee_id'){
		$url = "<a href=http://people.qualcomm.com/servlet/PhotoPh?fld=def&mch=eq&query=$value&org=0&lst=0&srt=cn&frm=0>$value</a>";
		return $url;
    }else if($field == 'type_name'){
		$value = substr($value, 0, 15);
    }else if($field == 'ind'){
		$value = substr($value, -2, 2);
    }else if($field == 'note'){
		if(strlen($value)> 30){
			$book_id = $row['op'];
			$value = substr($value, 0, 30) . "<a href=edit_hrdoc.php?op=edit_hrdoc_ui&book_id=$book_id>...</a>";
		}
		if($role >= 1){
			$book_id = $row['op'];
			$td_attr .= " ondblclick='show_edit_col(this,$book_id,1)' ";
		}
	}else if($field == 'rownum'){
		//$value = "<input type='checkbox' value='$value' class='multi_checkbox checkall' name='rows_to_delete_$value' id='id_rows_to_delete_value'>$value";
	}else if($field == 'book_id'){
		$value = ("<a href=edit_hrdoc.php?op=edit_hrdoc_ui&book_id=$value>$value</a>");
    }

	return $value;
}

function list_document($start, $items_perpage, $cond=" 1 ", $order='')
{
	$dbfield = " @rownum := @rownum+1 as rownum, status, employee_id, name, type_name, book_id as ind, status_name, room_name, submitter, note, create_date,modified_date, book_id as op";

	if($items_perpage != 0){
		$sql = "select * from books where $cond ";	
		if(preg_match("/name|user_id/", $cond))
			$sql = "select * from books a left join user.user b on a.employee_id = b.EmpNo where $cond ";	
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
		}else{
			$end = $rows - 1;
		}
		if($start > 0)
			$hasprev = true;

		print('<form enctype="multipart/form-data" action="hrdoc.php" method="POST">');
		show_browser_button($hasprev, $hasmore);
		$startd = $start + 1;
		$endd = $end + 1;
		print("($startd-$endd/$rows) ");

		global $submitter, $create_date;
		print("Import Time:");
		$cond2 = " 1 ";
		if($submitter != -1)
			$cond2 .= " and submitter = '$submitter' ";
		$sql = "select distinct create_date, create_date from books where $cond2";
		show_filter_select_by_sql('create_date', $sql, $create_date);

		if($cond != " 1 "){
			$mt = $cond;
			if(preg_match("/ and (.+)/", $cond, $match)){
				$mt = $match[1];
			}
			print("  Filter: $mt");
		}
	}
	$sql = "select $dbfield from books a left join user.user b on a.employee_id = b.EmpNo left join doctype c on a.doctype = c.type left join status_name d on a.status = d.status_id left join file_room e on a.file_room = e.id, (select @rownum:=$start) as it where $cond ";	
    $sql .= " and employee_id != 0 ";
	if($items_perpage != 0)
		$sql .= "limit $start, $items_perpage";
	show_table_by_sql2('mydoc', $sql, 800, 'show_doc_list', 2); 

	if($items_perpage != 0){
		show_browser_button($hasprev, $hasmore);
		print('</form');
	}
}

function get_total_documents()
{
	$sql = " select * from books";
	$res = mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	$rows = mysql_num_rows($res);
	return $rows;
}

function get_cond_from_var($doctype, $status, $uid, $room, $submmiter, $create_date)
{
	$cond = " 1 " ;
	if($doctype != -1)
		$cond .= " and doctype = $doctype";
	if($status != -1)
		$cond .= " and status = $status";
	if($room != -1)
		$cond .= " and file_room = $room";
	if($submmiter != -1)
		$cond .= " and submitter = '$submmiter'";
	if($create_date != -1)
		$cond .= " and create_date = '$create_date'";
	if($uid != -1 && $uid != ''){
		if(is_numeric($uid))
			$cond .= " and employee_id = '$uid' ";
		else
			$cond .= " and (user_id = '$uid' or name like '%$uid%')";
	}
	return $cond;
}

function read_book_column($book_id, $col)
{
	$sql = "select * from books where `book_id`=$book_id";
	$res1=mysql_query($sql) or die("Invalid query:" . $sql . mysql_error());
	if($row1=mysql_fetch_array($res1)){
		$tt = $row1["$col"];
		return $tt;
	}
	return -1;
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

function get_doc_id($employee_id, $doctype, $index)
{
	$book_id = $employee_id * 10000 + $doctype * 100 + $index;
	return $book_id;
}

function show_user()
{
		print("
			<form enctype='multipart/form-data' action='import_records.php' method='POST'>
			<input type='hidden' name='MAX_FILE_SIZE' value='128000000' />
			Upload List: <input name='userfile' type='file' />
			<input name='import_user' type='submit' value='Import User' />
			</form>");
		print("
			<form enctype='multipart/form-data' action='edit_hrdoc.php' method='POST'>
			<input type='hidden' name='op' value='add_user' />
			User_Id:<input name='user_id' type='text' value='' />
			City:");
			show_filter_select('city', 'file_room', 'id', 'room_name', 0);
		print("
			<INPUT type=radio name=\"role\" value=\"2\">Admin</>
			<INPUT type=radio name=\"role\" checked value=\"1\">HR</>
			<input name='import_user' type='submit' value='Add User' />
			");
		$sql = "select user as `User ID`, b.name as Name, ".
			"case role when 2 then 'Admin' when 1 then 'HR' else 'Employee' end as Role,".
			" b.email as email, room_name as city,".
			" concat('<a href=edit_hrdoc.php?op=del_user&user_id=', user, '>Delete</a>') as op".
			" from member a left join user.user b on a.user = b.user_id".
			" left join file_room c on c.id = a.city".
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
