<?php 
require ('doobit.class.php');

function title($text,$h=1){
	echo '<h'.$h.'>'.$text.'</h'.$h.'>';
}
function line(){
	echo "<p>------------------</p>";
}
function hr(){
	echo "<hr>";
}
$dbconfig = array(
		'hostname' => 'localhost',
		'username' => 'root',
		'password' => '',
		'database' => 'test',
		'dbprefix' => '',
		'charset' => 'utf8'
);
$db = new doobit($dbconfig);

// 输出成员属性值
title('输出成员属性值');
var_dump($db->sql_string);
line();

// query()方法测试
title('query()方法测试');
var_dump($db->query("show databases"));
var_dump($db->query("SELECT `id`,`text` FROM `test` LIMIT 3")->rows());
line();

// last_query()方法测试
title('last_query()方法测试');
var_dump($db->last_query());
line();

// insert_id()方法测试
title('insert_id()方法测试');
var_dump($db->insert_id());
line();

// num_rows()方法测试
title('num_rows()方法测试');
var_dump($db->num_rows());
line();

// row()方法测试
title('rows()方法测试');
var_dump($db->query("show variables like 'character_set%'")->row());
var_dump($db->query("show databases")->row());
line();

// rows()方法测试
title('rows()方法测试');
var_dump($db->query("show variables like 'character_set%'")->rows());
var_dump($db->query("show databases")->rows());
line();

// select()方法测试
title('select()方法测试');
var_dump($db->field(array('id','text'))->table('test')->where(array('id'=>1))->select()->rows());
var_dump($db->last_query());
var_dump($db->table('test')->where(array('id'=>1))->select()->rows());
var_dump($db->last_query());
line();

// insert()方法测试
title('insert()方法测试');
var_dump($db->table('test')->data(array('text'=>'Doobit Insert'))->insert());
var_dump($db->last_query());
var_dump($last_insert_id = $db->insert_id());
var_dump($db->field(array('id','text'))->table('test')->where(array('id'=>$last_insert_id))->select()->rows());
line();

// update()方法测试
title('update()方法测试');
var_dump($db->table('test')->data(array('text'=>'Doobit Update'))->where(array('id'=>$last_insert_id))->update());
var_dump($db->last_query());
var_dump($db->field(array('id','text'))->table('test')->where(array('id'=>$last_insert_id))->select()->rows());
line();

// delete()方法测试
title('update()方法测试');
var_dump($db->table('test')->where(array('id'=>$last_insert_id))->delete());
var_dump($db->last_query());
var_dump($db->field(array('id','text'))->table('test')->where(array('id'=>$last_insert_id))->select()->rows());
line();