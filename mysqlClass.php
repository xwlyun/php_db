<?php
define("DB_HOST",'127.0.0.1');	// 数据库配置
define("DB_USERNAME",'root');
define("DB_PASSWORD",'root');
define("DB_NAME",'test');
define("DB_SLAVE_HOST",'127.0.0.1');	// 从库配置
define("DB_SLAVE_USERNAME",'root');
define("DB_SLAVE_PASSWORD",'root');
define("DB_SLAVE_NAME",'test');

class Mysqls{
	// 数据库连接
	protected $dblink;
	// 当前使用
	protected $config = array('host'=>DB_HOST,'username'=>DB_USERNAME,'password'=>DB_PASSWORD,'dbname'=>DB_NAME);
	// 主数据库
	protected $mconfig = array('host'=>DB_HOST,'username'=>DB_USERNAME,'password'=>DB_PASSWORD,'dbname'=>DB_NAME);
	// slave数据库
	protected $sconfig = array('host'=>DB_SLAVE_HOST,'username'=>DB_SLAVE_USERNAME,'password'=>DB_SLAVE_PASSWORD,'dbname'=>DB_SLAVE_NAME);

	/**
	 * 构造函数
	 * @param bool $is_slave 是否连从库
	 * @param array $p
	 */
	function __construct($is_slave=false,$p=array())
	{
		$this->dblink = false;
		if(isset($p['host']))	// 程序中配置数据库连接
		{
			$this->config = $p;
		}

		if($is_slave && $this->sconfig['host'])	// 选择slave数据库并且配置了slave数据库
		{
			$this->config = $this->sconfig;
		}else{
			$this->config = $this->mconfig;
		}
	}

	/**
	 * 执行sql
	 * @param $sql
	 * @param bool $affect_num 是否返回影响行数
	 * @return int|resource
	 */
	function query($sql,$affect_num=false)
	{
		if(!$this->dblink)	// 只有执行sql的时候才有数据库链接
		{
			$this->dblink = mysql_connect($this->config['host'],$this->config['username'],$this->config['password']) or die('连接失败:' . mysql_error());
			mysql_select_db($this->config['dbname'],$this->dblink) or die('连接失败:'.mysql_error());
			mysql_query("set names utf8",$this->dblink);
		}
		$res = mysql_query($sql,$this->dblink);
		if($affect_num)
		{
			return $res?mysql_affected_rows($this->dblink):0;
		}
		return $res;
	}

	/**
	 * 获取单个字段数据
	 * @param $sql
	 * @return mixed
	 */
	function getOne($sql)
	{
		$query = $this->query($sql);
		$data = mysql_fetch_array($query,MYSQL_NUM);
		return $data[0];
	}

	/**
	 * 取出一条数据
	 * @param $sql
	 * @return array
	 */
	function getRow($sql)
	{
		$query = $this->query($sql);
		$data = mysql_fetch_array($query,MYSQL_ASSOC);
		return $data?$data:array();
	}

	/**
	 * 取出多条数据
	 * @param $sql
	 * @return array
	 */
	function getRows($sql)
	{
		$query = $this->query($sql);
		$data = array();
		while($row = mysql_fetch_array($query,MYSQL_ASSOC))
		{
			$data[] = $row;
		}
		return $data;
	}

	/**
	 * 插入数据,debug为真返回sql
	 * @param $table
	 * @param $data
	 * @param bool $return
	 * @param bool $debug
	 * @return bool|int|resource|string
	 */
	function insert($table, $data, $return = false, $debug=false)
	{
		if(!$table)
		{
			return false;
		}
		$fields = array();
		$values = array();
		foreach ($data as $field => $value)
		{
			$fields[] = '`'.$field.'`';
			$values[] = "'".addslashes($value)."'";
		}
		if(empty($fields) || empty($values))
		{
			return false;
		}
		$sql = 'INSERT INTO `'.$table.'`
				('.join(',',$fields).')
				VALUES ('.join(',',$values).')';
		if($debug)
		{
			return $sql;
		}
		$query = $this->query($sql);
		return $return ? mysql_insert_id() : $query;
	}

	/**
	 * 更新数据
	 * @param $table
	 * @param $condition
	 * @param $data
	 * @param int $limit
	 * @param bool $debug
	 * @return bool|int|resource|string
	 */
	function update($table, $condition, $data, $limit = 1, $debug=false)
	{
		if(!$table)
		{
			return false;
		}
		$set = array();
		foreach ($data as $field => $value)
		{
			$set[] = '`'.$field.'`='."'".addslashes($value)."'";
		}
		if(empty($set))
		{
			return false;
		}
		$sql = 'UPDATE `'.$table.'`
				SET '.join(',',$set).'
				WHERE '.$condition.' '.
			($limit ? 'LIMIT '.$limit : '');
		if($debug)
		{
			return $sql;
		}
		return $this->query($sql);
	}
}