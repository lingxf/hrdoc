<?php
include_once 'debug.php';
$web_name = 'hrdoc';
session_set_cookie_params(30*24*3600);
session_name($web_name);
session_start();

include "db_connect.php";
include_once 'myphp/common.php';
include_once 'myphp/login_lib.php';
include_once 'hrdoc_records.php';

$login_id = "Login";
check_login($web_name);

if(!isset($login_id) || $login_id == 'Login'){
	die("please login first");
}

$uploaddir = '/local/mnt/uploads/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
if(move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
    echo "File is valid, and was successfully uploaded.\n";
} else {
    echo "Upload Failed copy {$_FILES['userfile']['tmp_name']}\n";
	return;
}


if(isset($_POST['import_document'])){
	$type = 'user';
	import_document('books', $uploadfile);
	$trans = array(
	'EmpNo'=>'employee_id',
	'Office'=>'file_room',
	);
    $more = '';
	//$lines = import_excel_file($uploadfile, 'docdb', 'books','book_id', $trans, $more, '',''); 
}

if(isset($_POST['import_user'])){
    import_user($uploadfile);
}

print "<a href=\"hrdoc.php\">Home</a>";

function import_document($tb, $import_file)
{
    global $login_id;

	if(substr_count($import_file, '.xlsx') || substr_count($import_file, '.xlsm') ){
		$xlsx = true;
	}
	else
	{
		$xlsx = false;
	}

	set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/PHPExcel/');
	require_once 'PHPExcel/PHPExcel.php';
	if($xlsx){
		require_once 'PHPExcel/PHPExcel/Reader/Excel2007.php';
		$objReader = new PHPExcel_Reader_Excel2007();
	}else{
		require_once 'PHPExcel/PHPExcel/Reader/Excel5.php';
		$objReader = new PHPExcel_Reader_Excel5();
	}

	$fields_names_user = get_tb_fields("docdb", "books");
	$doctype_array = get_tb_list("docdb", "doctype", "type", "type_name");
	$status_array = get_tb_list("docdb", "status_name", "status_id", "status_name");
	$room_array = get_tb_list("docdb", "file_room", "id", "room_name");
	$room_alias = array('Beijing' => 'BJ', 'Shanghai' => 'SH', 'Shenzhen' => 'SZ', 'Xian' => 'XA');

	$user_new = 0;
	$user_update = 0;
	$objReader->setReadDataOnly(true);
	$objReader->setLoadAllSheets();
	$objPHPExcel = $objReader->load($import_file);
	$current_sheet = $objPHPExcel->getSheet(0);
	$num_rows = $current_sheet->getHighestRow();
	$num_cols = excel_get_column($current_sheet->getHighestColumn());
	print "excel line x col : $num_rows x $num_cols<br>\n"; 
	$begin_rol=1;
	
	for ($r = $begin_rol; $r <= $num_rows; ++$r) {
	    $tempRow = array();
	    for ($c = 0; $c < $num_cols; ++$c) {
	        $cellobj = $current_sheet->getCellByColumnAndRow($c, $r);
	        $cell = $current_sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue();
	        if (! strcmp($cell, '')) {
				if($r == 1){
					$num_cols = $c;
					break;
				}
	            $cell = 'NULL';
	        }
	        $tempRow[] = $cell;
	    }
		if($r==$begin_rol){
			$colnames = $tempRow; 
			continue;
		}
	
		$i = 0;
		$sql_set_user = '';
		$emptyline = false;
		$doctype = '';
		$EmpNo = '';
		//print_r($colnames);
		//print_r($tempRow);
		foreach($colnames as $colname){
			$cell = $tempRow[$i];
			$i += 1;
	
			$cell = str_replace("'", "''", $cell);
			$cell = str_replace("\\", "\\\\", $cell);
	
			//print("$colname<br>\n");
			if($colname == 'Empno' || $colname == 'employee_id'){
				$colname = 'employee_id';
				$EmpNo = $cell;
				continue;
			}else if($colname == 'doctype'){
				$colname = 'doctype';
				if(is_numeric($cell))
					$doctype= $cell;
				else
					$doctype = get_id_by_name($doctype_array, $cell);
				continue;
			}else if($colname == 'status'){
				if(!is_numeric($cell))
					$cell = get_id_by_name($status_array, $cell);
			}else if($colname == 'file_room'){
				if(!is_numeric($cell)){
					if(array_key_exists($cell, $room_alias))
						$room = $room_alias[$cell];
					$room = get_id_by_name($room_array, $room);
					if($room != -1)
						$cell = $room;
					print "room:$room<br>";
				}
			}else if($colname == 'submitter'){
				$submitter = $cell;
				continue;
			}else if($colname == 'Office' || $colname == 'file_room'){
				$colname = 'file_room';
				//$cell = substr($cell, 0, 5);
			}else if($colname == 'Note' ){
				$colname = 'note';
			}

	
			if($cell == '' || $cell == 'NULL')
				continue;
			if(in_array($colname, $fields_names_user))
				$sql_set_user .= " `$colname` = '$cell' ," ;
		}
	
		if($emptyline || $EmpNo == '' || $EmpNo == 'NULL'){
			//print "skip empty line<br>\n";
			$emptyline = false;
			continue;
		}

		$tm =  strftime("%Y-%m-%d %H:%M:%S", time());
        /*
		if($doctype != ''){
			$sql_query = "select * from where `employee_id` = '$EmpNo' and doctype = $doctype ";
			$res = read_mysql_query($sql_query);
			if(!$res)
				print("$employee_id  $doctype already exist");
		}
        */
        $id = $EmpNo * 100 + $doctype;
		$sql_update1 = "Update $tb set " . $sql_set_user. " employee_id = `employee_id` where book_id = '$id'";
		$sql_insert1 = "Insert into $tb set " . $sql_set_user . " doctype = $doctype, create_date='$tm', `employee_id` = '$EmpNo', book_id = '$id', submitter='$login_id' ";

		for($i = 0; $i < 1; $i++){
			$res1=mysql_query($sql_update1) or die("Invalid query:" . $sql_update1 . mysql_error());
			$rs = mysql_info();
			$match = 0;
			if(preg_match("/matched:\s*(\d+)/", $rs, $matches)){
				$match = $matches[1];
			}
			if($match == 0){
				$res1=mysql_query($sql_insert1);
				if(!$res1){
					if(mysql_errno() !=  1062)
						die("Invalid query:" . $sql_insert1 . mysql_error());
					else
						print "duplicate reporter" . $EmpNo. "<br/>";
				}else{
					//print "adding new user:$employee_id<br>";
					$user_new++;
				}
			}else{
				if(intval($match) > 1 ){
					dprint("Find $match matched user, $rs, update user:$EmpNo<br>");
				}else
					dprint("Find $match matched user, $rs, update user:$EmpNo<br>");
				$user_update++;
			}
		}
		
		unset($tempRow);
	}
	
	unset($objPHPExcel);
	unset($objReader);
	
	$incount = $user_update + $user_new;
	$import_message = "Total $incount documents, Update:$user_update, New:$user_new\n"; 
	print($import_message);
	add_log($login_id, "import", -1, 15, 0, "$import_message");
	$to = get_user_attr($login_id, 'email');
	$cc = get_admin_mail();
	mail_html($to, $cc, "$login_id import $import_message", "");
}

function import_user($import_file)
{
    global $login_id;
	if(substr_count($import_file, '.xlsx') || substr_count($import_file, '.xlsm') )
		$xlsx = true;
	else
		$xlsx = false;
	set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/php/libraries/PHPExcel/');
	
	require_once 'PHPExcel/PHPExcel.php';
	if($xlsx){
		require_once 'PHPExcel/PHPExcel/Reader/Excel2007.php';
		$objReader = new PHPExcel_Reader_Excel2007();
	}else{
		require_once 'PHPExcel/PHPExcel/Reader/Excel5.php';
		$objReader = new PHPExcel_Reader_Excel5();
	}



	$fields_names_user = get_tb_fields("user", "user");

	$user_new = 0;
	$user_update = 0;
	$objReader = new PHPExcel_Reader_Excel5();
	$objReader->setReadDataOnly(true);
	$objReader->setLoadAllSheets();
	$objPHPExcel = $objReader->load($import_file);
	$current_sheet = $objPHPExcel->getSheet(0);
	$num_rows = $current_sheet->getHighestRow();
	$num_cols = excel_get_column($current_sheet->getHighestColumn());
	print "excel line x col : $num_rows x $num_cols<br>\n"; 
	$begin_rol=1;
	
	for ($r = $begin_rol; $r <= $num_rows; ++$r) {
	    $tempRow = array();
	    for ($c = 0; $c < $num_cols; ++$c) {
	        $cellobj = $current_sheet->getCellByColumnAndRow($c, $r);
	        $cell = $current_sheet->getCellByColumnAndRow($c, $r)->getCalculatedValue();
	        if (! strcmp($cell, '')) {
				if($r == 1){
					$num_cols = $c;
					break;
				}
	            $cell = 'NULL';
	        }
	        $tempRow[] = $cell;
	    }
		if($r==$begin_rol){
			$colnames = $tempRow; 
			continue;
		}
	
		$i = 0;
		$sql_set_user = '';
		$emptyline = false;
		$reporter = '';
		$password = '';
		foreach($colnames as $colname){
			$cell = $tempRow[$i];
			$i += 1;
			if($colname == 'Uid' || $colname == 'reporter'){
				$reporter = $cell;
				continue;
			}
	
			if($colname == 'Empno'){
				$password = $cell;
			}
	
			$cell = str_replace("'", "''", $cell);
			$cell = str_replace("\\", "\\\\", $cell);
	
			if($colname == 'Name')
				$colname = 'name';
			else if($colname == 'Manager')
				$colname = 'supervisor';
			else if($colname == 'Email')
				$colname = 'email';
			else if($colname == 'team'){
				if($ta = get_region_match($cell)){
					$cell = $ta;
				}
			}
			else if($colname == 'tech'){
				if($ta = get_team_match($cell)){
					$cell = $ta[1];
				}
			}
	
			if($cell == '' || $cell == 'NULL')
				continue;
			if(in_array($colname, $fields_names_user))
				$sql_set_user .= " `$colname` = '$cell' ," ;
		}
	
		if($emptyline || $reporter == ''){
			print "skip empty line<br>\n";
			$emptyline = false;
			continue;
		}

	    if($password != '')
		    $sql_insert = $sql_set_user . " `password` = '$password' ,";
		$sql_insert1 = "Insert into user.user set " . $sql_insert . " `user_id` = '$reporter' ";
		$sql_update1 = "Update user.user set " . $sql_set_user . " user_id = `user_id` where user_id = '$reporter'";
		for($i = 0; $i < 1; $i++){
			$res1=mysql_query($sql_update1) or die("Invalid query:" . $sql_update1 . mysql_error());
			$rs = mysql_info();
			$match = 0;
			if(preg_match("/matched:\s*(\d+)/", $rs, $matches)){
				$match = $matches[1];
			}
			if($match == 0){
				$res1=mysql_query($sql_insert1);
				if(!$res1){
					if(mysql_errno() !=  1062)
						die("Invalid query:" . $sql_insert1 . mysql_error());
					else
						dprint("duplicate reporter" . $reporter . "<br/>");
				}else{
					dprint("adding new user:$reporter<br>");
					$user_new++;
				}
			}else{
				if(intval($match) > 1 ){
					dprint("Find $match matched user, $rs, update user:$reporter<br>");
				}else
					dprint("Find $match matched user, $rs, update user:$reporter<br>");
				$user_update++;
			}
			if($i == 1)
				break;
			dprint("Update user.user: ");
		}
		
		unset($tempRow);
	}
	
	unset($objPHPExcel);
	unset($objReader);
	
	$incount = $user_update + $user_new;
	print("Total $incount users, Update:$user_update, New:$user_new\n"); 
    
	$fields = mysql_list_fields('docdb', 'log');
	add_log($login_id, $login_id, -1, 15, 0, "Insert $incount users, Update:$user_update, New:$user_new"); 
}

?> 

