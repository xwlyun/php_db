<?php

class sqlite_pdo
{
	protected $dblink;	// SQLite数据库连接

	/**
	 * 数据库连接 构造类
	 * @param $databaseFile 数据库文件
	 */
	public function __construct($databaseFile)
	{
//		if(file_exists($databaseFile))
//		{
//			$this->dblink = new PDO('sqlite:'.$databaseFile);
//		}
//		else
//		{
//			die('未找到数据库文件');
//		}
		$this->dblink = new PDO('sqlite:'.$databaseFile);
	}

	/**
	 * 执行INSERT,DELETE,UPDATA操作
	 * @param $sql SQL语句
	 * @return bool
	 */
	public function query($sql)
	{
		return $this->dblink->exec($sql) or die('SQL error:'.$this->dblink->errorInfo()[2]);
	}

	/**
	 * 获取单个字段数据
	 * @param $sql
	 * @return mixed
	 */
	function getOne($sql)
	{
		 return $this->getRow($sql)[0];
	}

	/**
	 * 取出一条数据
	 * @param $sql
	 * @return array|mixed
	 */
	public function getRow($sql){
		$result = $this->dblink->prepare($sql);
		if(false === $result)
		{
			return array();
		}
		$result->execute();
		$data = $result->fetch();
		if(false === $data)
		{
			return array();
		}
		return $data;
	}

	/**
	 * 取出多条数据
	 * @param $sql
	 * @return array
	 */
	public function getRows($sql){
		$result = $this->dblink->prepare($sql);
		if(false === $result)
		{
			return array();
		}
		$result->execute();
		$data = $result->fetchAll();
		if(false === $data)
		{
			return array();
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
		return $return ? $this->dblink->lastInsertId() : $query;
	}

	/**
	 * 更新数据
	 * @param $table
	 * @param $condition
	 * @param $data
	 * @param bool $debug
	 * @return bool|int|resource|string
	 */
	function update($table, $condition, $data, $debug=false)
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
		// sqlite 不支持 limit
		$sql = 'UPDATE `'.$table.'`
				SET '.join(',',$set).'
				WHERE '.$condition.' ';
		if($debug)
		{
			return $sql;
		}
		return $this->query($sql);
	}

}