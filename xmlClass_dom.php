<?php

/**
 * Class xml_dom
 *
nodeType：
1 XML_ELEMENT_NODE（元素类型）
2 XML_ATTRIBUTE_NODE
3 XML_TEXT_NODE
4 XML_CDATA_SECTION_NODE
5 XML_ENTITY_REFERENCE_NODE
6 XML_ENTITY_NODE
7 XML_PROCESSING_INSTRUCTION_NODE
8 XML_COMMENT_NODE（注释类型）
9 XML_DOCUMENT_NODE
10 XML_DOCUMENT_TYPE_NODE
11 XML_DOCUMENT_FRAGMENT_NODE
12 XML_NOTATION_NODE
 *
PHP DOMDocument操作：
属性:
Attributes     存储节点的属性列表(只读)
childNodes     存储节点的子节点列表(只读)
dataType     返回此节点的数据类型
Definition     以DTD或XML模式给出的节点的定义(只读)
Doctype     指定文档类型节点(只读)
documentElement     返回文档的根元素(可读写)
firstChild     返回当前节点的第一个子节点(只读)
Implementation     返回XMLDOMImplementation对象
lastChild     返回当前节点最后一个子节点(只读)
nextSibling     返回当前节点的下一个兄弟节点(只读)
nodeName     返回节点的名字(只读)
nodeType     返回节点的类型(只读)
nodeTypedValue     存储节点值(可读写)
nodeValue     返回节点的文本(可读写)
ownerDocument     返回包含此节点的根文档(只读)
parentNode     返回父节点(只读)
Parsed     返回此节点及其子节点是否已经被解析(只读)
Prefix     返回名称空间前缀(只读)
preserveWhiteSpace     指定是否保留空白(可读写)
previousSibling     返回此节点的前一个兄弟节点(只读)
Text     返回此节点及其后代的文本内容(可读写)
url     返回最近载入的XML文档的URL(只读)
Xml     返回节点及其后代的XML表示(只读)
方法:
appendChild     为当前节点添加一个新的子节点,放在最后的子节点后
cloneNode     返回当前节点的拷贝
createAttribute     创建新的属性
createCDATASection     创建包括给定数据的CDATA段
createComment     创建一个注释节点
createDocumentFragment     创建DocumentFragment对象
createElement     创建一个元素节点
createEntityReference     创建EntityReference对象
createNode     创建给定类型,名字和命名空间的节点
createPorcessingInstruction     创建操作指令节点
createTextNode     创建包括给定数据的文本节点
getElementsByTagName     返回指定名字的元素集合
hasChildNodes     返回当前节点是否有子节点
insertBefore     在指定节点前插入子节点
Load     导入指定位置的XML文档
loadXML     导入指定字符串的XML文档
removeChild     从子结点列表中删除指定的子节点
replaceChild     从子节点列表中替换指定的子节点
Save     把XML文件存到指定节点
selectNodes     对节点进行指定的匹配,并返回匹配节点列表
selectSingleNode     对节点进行指定的匹配,并返回第一个匹配节点
transformNode     使用指定的样式表对节点及其后代进行转换
 *
 */
class xml_dom
{
	protected $dblink;	// xml连接
	protected $dbfile;	// xml文件路径

	/**
	 * xml文件 构造类
	 * @param $db_file xml文件
	 */
	public function __construct($db_file)
	{
		$this->dbfile = $db_file;
		if(!file_exists($db_file))
		{
//			die('未找到数据库文件');
			$this->dblink = new DOMDocument('1.0', 'utf-8');
			$root = $this->dblink->createElement('root');
			$this->dblink->appendChild($root);
			$this->dblink->formatOutput = true;	// xml文件保留缩进样式
			$this->dblink->save($this->dbfile);
		}
		else
		{
			$this->dblink =  new DOMDocument();
			$this->dblink->formatOutput = true;
			$this->dblink->load($this->dbfile);
		}
	}

	/**
	 * 遍历所有元素
	 * ===============================================
	 * 标准xml文件，一个元素可能有n个属性，可用自定义键[nodevalue]获取元素值
	 * <?xml version="1.0" encoding="utf-8"?>
	 * <table name="posts">
	 *     <column name="id">1</column>
	 *     <column name="title">标题一</column>
	 *     <column name="content">详细内容一</column>
	 * </table>
	 * ===============================================
	 * 简单xml文件，没有属性，键值一一对应
	 * <?xml version="1.0" encoding="utf-8"?>
	 * <root>
	 *     <posts>
	 *         <id>1</id>
	 *         <title>标题一</title>
	 *         <content>详细内容一</content>
	 *     </posts>
	 * </root>
	 * @param $node
	 * @return array
	 */
	function getData($node=0){

		if(!$node)
		{
			$node = $this->dblink->documentElement;
		}

		$array = array();

		foreach($node->attributes as $attribute)
		{
			$key = $attribute->nodeName;
			$val = $attribute->nodeValue;
			$array[$key] = $val;
		}

		if(count($array))	// 有属性，则用[nodevalue]键代表值
		{
			$array['nodevalue'] = $node->nodeValue;
		}

		// 递归遍历所有子元素
		$node_child = $node->firstChild;
		while($node_child)
		{
			if(XML_ELEMENT_NODE == $node_child->nodeType)
			{
				$tagname = $node_child->tagName;
				$result = $this->getData($node_child);
				if(isset($array[$tagname]))	// 发现有重复tagName的子元素存在，所以改用数组存储重复tagName的所有子元素
				{
					if(!is_array($array[$tagname][0]))
					{
						$tmp = $array[$tagname];
						$array[$tagname] = array();
						$array[$tagname][] = $tmp;
					}
					$array[$tagname][] = $result;
				}
				else
				{
					$array[$tagname] = $result;
				}
			}
			$node_child = $node_child->nextSibling;
		}

		if(!count($array))	// 没有子元素&没有属性=最末子元素，就返回该元素的nodeValue值
		{
			return $node->nodeValue;
		}

		return $array;
	}

	/**
	 * 把array数据写到xml文件（覆盖）
	 * @param $data
	 */
	public function setData($data,&$node=0)
	{
		$is_root = false;

		if(!$node)
		{
			$is_root = true;

			$node = $this->dblink->documentElement;
			// 清除原数据
			$remove = array();
			$node_child = $node->firstChild;
			while($node_child)
			{
				$remove[] = $node_child;
				$node_child = $node_child->nextSibling;
			}
			foreach($remove as $r)
			{
				$node->removeChild($r);
			}
		}

		if(is_array($data))
		{
			foreach($data as $k=>$v)
			{
				if(is_array($v))
				{
					foreach($v as $r)
					{
						$item = $this->dblink->createElement($k);
						$result = $this->setData($r,$item);
						$node->appendChild($result);
					}
				}
				else
				{
					$item = $this->dblink->createElement($k);
					$value = $this->dblink->createTextNode($v);
					$item->appendChild($value);
					$node->appendChild($item);
				}
			}
		}
		else
		{
			$item = $this->dblink->createTextNode($data);
			$node->appendChild($item);
		}

		if($is_root)
		{
			$this->dblink->save($this->dbfile);	// 覆盖写入
		}
		else
		{
			return $node;
		}
	}

}