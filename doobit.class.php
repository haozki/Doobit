<?php
/**
 *	Doobit Class By Haosiji
 */
require ('doobit.config.inc.php');

/* 配置项参考 */
$doobit_config = array(
	'hostname' => 'localhost',	//主机地址（可以带端口）
	'username' => 'root',		//数据库用户名
	'password' => '',			//数据库密码
	'database' => 'test',		//连接的数据库名
	'dbprefix' => '',			//表前缀
	'charset' => 'utf8'			//字符集
);

class doobit{
	public $hostname;
	public $username;
	public $password;
	public $database;
	public $dbprefix;
	public $charset = 'utf8';
	public $conn_id;
	public $result_id;
	public $sql_string;
	public $AR_table = '';
	public $AR_field = array();
	public $AR_data = array();
	public $AR_where = array();

	// 构造函数
	public function __construct($config = array())
	{
		$this->initialize($config);
		$this->conn_id = $this->db_connect() or die("Connect Failed!");
		$this->db_select();
		$this->db_set_charset();
	}

	// 初始化配置项
	private function initialize($config)
	{
		foreach ($config as $key => $val){
			/* 如果使用isset()检测变量是否存在要求变量已经初始化 */
			if (property_exists($this, $key)){
				$this->$key = $val;
			}
		}
	}

	// 数据库连接
	public function db_connect()
	{
		return @mysql_connect($this->hostname, $this->username, $this->password);
	}

	// 选择数据库
	public function db_select()
	{
		return @mysql_select_db($this->database, $this->conn_id);
	}

	// 设定字符集
	public function db_set_charset()
	{
		// mysql_set_charset() 需要PHP>=5.2.3和MySQL>=5.0.7，使用SET NAMES作为后备
		$use_set_names = (version_compare(PHP_VERSION, '5.2.3', '>=') && version_compare(mysql_get_server_info(), '5.0.7', '>=')) ? FALSE : TRUE;

		if ($use_set_names === TRUE){
			return @mysql_query("SET NAMES '".$this->charset."'", $this->conn_id);
		}else{
			return @mysql_set_charset($this->charset, $this->conn_id);
		}
	}

	// 关闭数据库连接
	public function db_close()
	{
		return @mysql_close($this->conn_id);
	}

	// 转义SQL语句中使用的字符串中的特殊字符
	public function escape($str)
	{
		if (is_array($str)){
			foreach ($str as $key => $val){
				$str[$key] = $this->escape($val);
	   		}
	   		return $str;
	   	}
		if (function_exists('mysql_real_escape_string') AND is_resource($this->conn_id)){
			$str = mysql_real_escape_string($str, $this->conn_id);
		}
		elseif (function_exists('mysql_escape_string')){
			$str = mysql_escape_string($str);
		}else{
			$str = addslashes($str);
		}
		return $str;
	}

	// 错误提示
	private function halt($msg='')
	{
		$msg .= '<p>Error Number: '.mysql_errno($this->conn_id).'</p>';
		$msg .= '<p>'.mysql_error($this->conn_id).'</p>';
		die($msg);
	}

	// 取得结果集中行的数目
	public function num_rows()
	{
		return @mysql_num_rows($this->result_id);
	}

	// 取得上一步 INSERT 操作产生的 ID
	public function insert_id()
	{
		return @mysql_insert_id($this->conn_id);
	}

	// 生成的查询串
	public function last_query()
	{
		return $this->sql_string;
	}

	// 重置Active Record变量
	private function reset_ar()
	{
		$ar_reset_items = array(
			'AR_table' => '',
			'AR_field' => array(),
			'AR_data' => array(),
			'AR_where' => array()
		);
		foreach ($ar_reset_items as $item => $default_value){
			if (property_exists($this, $item)){
				$this->$item = $default_value;
			}
		}
	}
	// 查询
	public function query($sql)
	{
		$this->sql_string = $sql;
		$this->result_id = mysql_query($sql,$this->conn_id);
		if(!$this->result_id) $this->halt('Query Error: ' . $sql);
		$this->reset_ar();
		return $this;
	}

	// 获取一条记录（对象）
	public function row()
	{
		if ($this->result_id === false || $this->num_rows() == 0)
			return array();
		return @mysql_fetch_object($this->result_id);
	}

	// 获取一条记录（数组）
	public function row_array()
	{
		if ($this->result_id === false || $this->num_rows() == 0)
			return array();
		return @mysql_fetch_assoc($this->result_id);
	}

	// 获取全部记录（对象）
	public function rows()
	{
		if ($this->result_id === false || $this->num_rows() == 0)
			return array();

		while($row = @mysql_fetch_object($this->result_id)){
			$rows[]=$row;
		}

		return $rows;
	}

	// 获取全部记录（数组）
	public function rows_array()
	{
		if ($this->result_id === false || $this->num_rows() == 0)
			return array();

		while($row = @mysql_fetch_assoc($this->result_id)){
			$rows[]=$row;
		}

		return $rows;
	}

	// Active Record : 待操作的表名
	public function table($table)
	{
		$this->AR_table = $this->dbprefix.$table;
		return $this;
	}

	// Active Record : 待获取的字段
	public function field($field)
	{
		if (!is_array($field)) return false;
		$this->AR_field = $field;
		return $this;
	}

	// Active Record : 待操作的数据对
	public function data($data)
	{
		if (!is_array($data)) return false;
		$this->AR_data = $data;
		return $this;
	}

	// Active Record : 待操作的条件
	public function where($where)
	{
		if (!is_array($where)) return false;
		$this->AR_where = $where;
		return $this;
	}

	// 查找记录
	public function select()
	{
		foreach ($this->AR_field as $value){
			$field[] = "`".$this->escape($value)."`";
		}
		$select = @implode(',', $field);
		if ($this->AR_field == array())
			$select = "*";
		if ($this->AR_where == null){
			$where = "";
		}else{
			foreach($this->AR_where as $key => $value ){
				$condition[] = "`".$key."`='".$this->escape($value)."'";
			}
			$where = "WHERE ".@implode(' AND ', $condition);
		}
		$sql = "SELECT {$select} FROM {$this->AR_table} {$where}";
		return $this->query($sql);
	}

	// 插入操作
	public function insert()
	{
		foreach ($this->AR_data as $key => $value){
			$cols[] = "`".$key."`";
			$vals[] = "'".$this->escape($value)."'";
		}
		$col = @implode(',', $cols);
		$val = @implode(',', $vals);
		$sql = "INSERT INTO {$this->AR_table} ({$col}) VALUES ({$val})";
		$this->query($sql);
		if ($this->result_id === true)
			return true;
		else return false;
	}

	// 更新操作
	public function update()
	{
		foreach ($this->AR_data as $key => $value){
			$data[] = "`".$key."`='".$this->escape($value)."'";
		}
		$set = @implode(',', $data);
		foreach($this->AR_where as $key => $value ){
			$condition[] = "`".$key."`='".$this->escape($value)."'";
		}
		$where = @implode(' AND ', $condition);
		$sql = "UPDATE {$this->AR_table} SET {$set} WHERE {$where}";
		$this->query($sql);
		if ($this->result_id === true)
			return true;
		else return false;
	}

	// 删除操作
	public function delete(){
		foreach($this->AR_where as $key => $value ){
			$condition[] = "`".$key."`='".$this->escape($value)."'";
		}
		$where = @implode(' AND ', $condition);
		$sql = "DELETE FROM {$this->AR_table} WHERE {$where}";
		$this->query($sql);
		if ($this->result_id === true)
			return true;
		else return false;
	}

	// 析构函数
	public function __destruct()
	{
		$this->db_close();
	}
}