<?php

function show_browser_button($hasprev=true, $hasmore=true)
{
	print('<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	print('<input type="submit"'); print(' name="begin" value="Begin" />   ');
	print('<input type="submit"'); if(!$hasprev) print(" disabled "); print(' name="prev" value="Prev" />   ');
	print('<input type="submit"'); if(!$hasmore) print(" disabled "); print(' name="next" value="Next" />   ');
	print('<input type="submit"');  print(' name="end" value="End" />   ');
}


function list_document($view, $empno, $start, $items_perpage, $cond=1, $order='')
{
	$dbfield = "book_id, employee_id, user_id, name, type_name, status_name, file_room, submitter";
	$sql = "select $dbfield from books a left join user.user b on a.employee_id = b.EmpNo left join doctype c on a.doctype = c.type left join status_name d on a.status = d.status_id where $cond ";	

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
	$ns = $start+$items_perpage;
	if($ns < $rows){
		$hasmore = true;
	}
	if($start > 0)
		$hasprev = true;

	dprint("$start, $rows, $items_perpage, $ns");
	$sql .= "limit $start, $items_perpage";
	$field = array('Doc ID', 'EmpNo', 'UserID', 'Name', 'Document', 'Status', 'File Room', 'Submitter');
	$width = array(20, 30, 50, 80, 80, 50, 50, 80);
	print('<form enctype="multipart/form-data" action="hrdoc.php" method="POST">');
	show_browser_button($hasprev, $hasmore);
	show_table_by_sql('mydoc', 'hrdoc', 800, $sql, $field, $width, null, 2); 
	show_browser_button($hasprev, $hasmore);
	print('</form');
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
	dprint("cond:$cond");
	return $cond;
}

function show_op($field, $value, $row='')
{
	if($field == 'op')
		return ("<a href=hrdoc.php?action=borrow&book_id=$value>Borrow</a>");
	return $value;
}

function show_hrdoc($login_id)
{
	$sql = "select EmpNo, user_id, name, type_name, status_name, file_room, submitter, book_id as op from books a left join user.user b on a.employee_id = b.EmpNo left join doctype c on a.doctype = c.type left join status_name d on a.status = d.status_id where b.user_id = '$login_id'";	
	$field = array('EmpNo', 'ID', 'Name', 'Document', 'Status', 'File Room', 'Submitter', 'Op');
	$width = array(50, 50, 100, 100, 50, 50, 100);
	show_table_by_sql('mydoc', 'hrdoc', 800, $sql, $field, $width, show_op, 2); 

}

?>
