<?php
$web_name = 'hrdoc';
session_set_cookie_params(30*24*3600);
session_name($web_name);
session_start();

include 'myphp/login_lib.php';
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
	import_document($uploadfile);
}

function PMA_getColumnNumberFromName($name) {
    if (strlen($name) != 0) {
        $name = strtoupper($name);
        $num_chars = count($name);
        $number = 0;
        for ($i = 0; $i < $num_chars; ++$i) {
            $number += (ord($name[$i]) - 64);
        }
        return $number;
    } else {
        return 0;
    }
}
function get_tb_fields($db, $tbname)
{
	$fields = mysql_list_fields($db, $tbname);
	$columns = mysql_num_fields($fields);
	$field_names  = array();
	for ($i = 0; $i < $columns; $i++) {
		$field_names[] = mysql_field_name($fields, $i);
	}
	return $field_names;
}

function import_document($import_file)
{

	set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/php/libraries/PHPExcel/');
	require_once 'php/libraries/PHPExcel/PHPExcel.php';
	
	require_once 'php/libraries/PHPExcel/PHPExcel/Reader/Excel5.php';
	
	$fields_names_user = get_tb_fields("docdb", "docdb");

	$user_new = 0;
	$user_update = 0;
	$objReader = new PHPExcel_Reader_Excel5();
	$objReader->setReadDataOnly(true);
	$objReader->setLoadAllSheets();
	$objPHPExcel = $objReader->load($import_file);
	$current_sheet = $objPHPExcel->getSheet(0);
	$num_rows = $current_sheet->getHighestRow();
	$num_cols = PMA_getColumnNumberFromName($current_sheet->getHighestColumn());
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
			if($colname == 'Empno'){
				$colname = 'employee_id';
				$EmpNo = $cell;
				continue;
			}else if($colname == 'doctype'){
				$colname = 'doctype';
				$doctype= $cell;
				continue;
			}else if($colname == 'Office'){
				$colname = 'file_room';
				$cell = substr($cell, 0, 5);
			}

	
			if($cell == '' || $cell == 'NULL')
				continue;
			if(in_array($colname, $fields_names_user))
				$sql_set_user .= " `$colname` = '$cell' ," ;
		}
	
		if($emptyline || $EmpNo == ''){
			print "skip empty line<br>\n";
			$emptyline = false;
			continue;
		}

		$tm =  strftime("%Y-%m-%d %H:%M:%S", time());
		$sql_insert1 = "Insert into docdb set " . $sql_set_user . " doctype=1, status=0,create_date='$tm', `employee_id` = '$EmpNo' ";
		if($doctype != ''){
			$sql_query = "select * from where `employee_id` = '$EmpNo' and doctype = $doctype ";
			$res = read_mysql_query($sql_query);
			if(!$res)
				print("$employee_id  $doctype already exist");
		}
		$sql_update1 = "Update docdb set " . $sql_set_user. " employee_id = `employee_id` where employee_id = '$EmpNo'";

		print("Update docdb: ");
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
						print "duplicate reporter" . $reporter . "<br/>";
				}else{
					print $sql1;
					print "adding new user:$reporter<br>";
					$user_new++;
				}
			}else{
				if(intval($match) > 1 ){
					print "$sql_update1 ";
					print "Find $match matched user, $rs, update user:$reporter<br>";
				}else
					print "Find $match matched user, $rs, update user:$reporter<br>";
				$user_update++;
			}
		}
		
		unset($tempRow);
	}
	
	unset($objPHPExcel);
	unset($objReader);
	
	$incount = $user_update + $user_new;
	print("Total $incount users, Update:$user_update, New:$user_new\n"); 
	add_log("import", "message", "Insert $incount users, Update:$user_update, New:$user_new"); 
}
?> 

