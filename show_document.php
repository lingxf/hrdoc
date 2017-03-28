<?php
/*
	book library system
	copyright Xiaofeng(Daniel) Ling<xling@qualcomm.com>, 2012, Aug.
*/

include 'myphp/common.php';
include 'myphp/disp_lib.php';
include 'hrdoc_lib.php';
include 'debug.php';
include 'db_connect.php';

session_name('hrdoc');
session_start();

if(isset($_GET['view'])) $view=$_GET['view'];
else $view=$_SESSION['view'];

$doctype = get_persist_var('doctype', -1);
$order = get_persist_var('order', 0);
$status = get_persist_var('status', -1);
$uid = get_persist_var('uid', -1);
	
$login_id = $_SESSION['user'];
#$role = is_member($login_id);

$start=$_SESSION['start'];
$start=0;
$items_perpage=$_SESSION['items_perpage'];

print("====$start $items_perpage===");

$cond = get_cond_from_var($doctype, $status, $uid);
list_document($view, 0, $start, $items_perpage, $cond ,$order);

?>
