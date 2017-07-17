<?php
$link=mysql_connect("localhost","hrdoc","hrdoc2web");
mysql_query("set character set 'utf8'");//..
mysql_query("set names 'utf8'");//.. 
if(isset($db) && $db==1)
	$db=mysql_select_db("docdb",$link);
else
	$db=mysql_select_db("docdb",$link);

?>
