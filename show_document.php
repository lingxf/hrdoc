<?php
/*
	book library system
	copyright Xiaofeng(Daniel) Ling<xling@qualcomm.com>, 2012, Aug.
*/

include 'myphp/common.php';
include 'myphp/disp_lib.php';
include 'hrdoc_lib.php';
include 'hrdoc_records.php';
include 'debug.php';
include 'db_connect.php';

session_name('hrdoc');
session_start();

$doctype = get_persist_var('doctype', -1);
$order = get_persist_var('order', 0);
$status = get_persist_var('status', -1);
$room = get_persist_var('room', -1);
$submitter = get_persist_var('submitter', -1);
$create_date = get_persist_var('create_date', -1);
$uid = get_persist_var('uid', '');
	
$login_id = get_session_var('user', 'Guest');
$role = get_member_role($login_id);

$start=$_SESSION['start'];
$start=0;
$items_perpage=$_SESSION['items_perpage'];

$cond = get_cond_from_var($doctype, $status, $uid, $room, $submitter, $create_date);
list_document($start, $items_perpage, $cond ,$order);

?>
