<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace service;

/**
 * 系统工具服务
 * Class ToolsService
 * @package service
 */
class ToolsService {

	/**
	 * 列表转树形
	 * @access public
	 * @param array $list 列表数据
	 * @param int $root 初始父级
	 * @param string $pk 主键
	 * @param string $ppk 父级主键
	 * @param string $child 子集键名
	 * @return array
	 */
	public static function listToTree($list, $root = 0, $pk = 'id', $ppk = 'pid', $child = 'sub') {
		$tree = $refer = [];
		if (is_array($list)) {
			// 创建基于主键的数组引用
			foreach ($list as $key => $val) {
				$refer[$val[$pk]] = &$list[$key];
			}
			foreach ($list as $key => $val) {
//				$pid = $val[$ppk];
//				if ($root == $pid) {
//					$tree[] = &$list[$key];
//				} else {
//					if (isset($refer[$pid])) {
//						$parent = &$refer[$pid];
//						$parent[$child][] = &$list[$key];
//					}
//				}
				if (isset($val[$ppk]) && isset($refer[$val[$ppk]])) {
					$refer[$val[$ppk]][$child][] = &$list[$key];
				} elseif ($val[$ppk] === $root) {
					$tree[] = &$list[$key];
				}
			}
		}
		unset($refer);
		return $tree;
	}

	/**
	 * 列表转表格
	 * @access public
	 * @param array $list 列表数据
	 * @param int $root 初始父级
	 * @param string $pk 主键
	 * @param string $ppk 父级主键
	 * @param string $path
	 * @param string $ppath
	 * @param bool $is_first
	 * @return array
	 */
	public static function listToTable($list, $root = 0, $pk = 'id', $ppk = 'pid', $path = 'path', $ppath = '', $is_first = true) {
		$table = [];
		// 列表树形处理
		if ($is_first)
			$tree = self::listToTree($list, $root, $pk, $ppk);
		else
			$tree = $list;
		foreach ($tree as $val) {
			$val['level'] = substr_count($ppath, '-');
			$val[$path] = $ppath . '-' . $val[$pk];
			//$val['spl'] = str_repeat("&nbsp;&nbsp;&nbsp;├&nbsp;&nbsp;", substr_count($ppath, '-'));
			if ($val['level'] > 0) {
				$val['spl'] = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", substr_count($ppath, '-') - 1) . "&nbsp;&nbsp;&nbsp;├&nbsp;&nbsp;";
			} else {
				$val['spl'] = "";
			}
			if (isset($val['sub'])) {
				$sub = $val['sub'];
				unset($val['sub']);
				$table[] = $val;
				if ($sub) {
					$sub_array = self::ListToTable($sub, $root, $pk, $ppk, $path, $val[$path], false);
					$table = array_merge($table, (Array)$sub_array);
				}
			} else {
				$table[] = $val;
			}
		}
		return $table;
	}

	/**
	 * 列表转分组
	 * @access public
	 * @param array $list 列表数据
	 * @param string $groupField 分组字段
	 * @return array
	 */
	public static function listToGroup($list, $groupField) {
		$group = [];
		foreach ($list as $val) {
			$group[$val[$groupField]][] = $val;
		}
		return $group;
	}

	/**
	 * 纵转横
	 * @access public
	 * @param $row 列表数据
	 * @param array $extendData
	 * @return array
	 */
	public static function rowToCol($row, $extendData = []) {
		$col = [];
		foreach ($row as $key => $val) {
			foreach ($val as $k => $v) {
				$col[$k][$key] = $v;
			}
			foreach ($extendData as $k => $v) {
				$col[$k][$key] = $v;
			}
		}
		return $col;
	}

	/**
	 * 获取列表子id
	 * @access public
	 * @param array $list 数据列表
	 * @param int $id 起始
	 * @param string $pk 主键
	 * @param string $ppk 父级主键
	 * @return array
	 */
	public static function getListSubId($list, $id = 0, $pk = 'id', $ppk = 'pid') {
		$ids = [intval($id)];
		foreach ($list as $v) {
			if (intval($v[$ppk]) > 0 && intval($v[$ppk]) == intval($id)) {
				$ids = array_merge($ids, self::getListSubId($list, intval($v[$pk]), $pk, $ppk));
			}
		}
		return $ids;
	}

	/**
	 * 获取列表父id
	 * @access public
	 * @param array $list 数据列表
	 * @param int $id 起始
	 * @param string $pk 主键
	 * @param string $ppk 父级主键
	 * @return array
	 */
	public static function getListParentId($list, $id = 0, $pk = 'id', $ppk = 'pid') {
		$ids = [intval($id)];
		foreach ($list as $v) {
			//if (intval($v[$ppk]) > 0 && intval($v[$ppk]) == intval($id)) {
			if (intval($v[$pk]) == intval($id) && intval($v[$ppk]) != intval($v[$pk])) {
				$ids = array_merge($ids, self::getListParentId($list, intval($v[$ppk]), $pk, $ppk));
			}
		}
		return $ids;
	}

	/**
	 * 产生随机字串
	 * @access public
	 * @param int $length 长度
	 * @param int $type 字串类型 0字母加数字 1数字 2字母 3大写 4小写
	 * @param string $addChars 额外字符
	 * @return null|string
	 */
	public static function getRandString($length = 12, $type = 0, $addChars = '') {
		$str = null;
		switch ($type) {
			case 1:
				$chars = '0123456789';
				break;
			case 2:
				$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
				break;
			case 3:
				$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
				break;
			case 4:
				$chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
				break;
			default:
				$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
				break;
		}
		// 打乱字符串
		$chars = str_shuffle(str_repeat($chars, $length));
		// 获取字符串长度
		$chars_len = strlen($chars) - 1;
		// 循环长度获得随机字符串
		while (strlen($str) < $length) {
			$str .= mb_substr($chars, mt_rand(0, $chars_len), 1, 'UTF-8');
		};
		return $str;
	}

	/**
	 * 生成一定数量的随机数，并且不重复
	 * @access public
	 * @param int $num 数量
	 * @param int $length 长度
	 * @param int $type 字串类型 0字母加数字 1数字 2字母 3大写 4小写
	 * @return array|bool
	 */
	public static function getCountRand($num, $length = 12, $type = 0) {
		//不足以生成一定数量的不重复数字
		if ($type == 1 && $length < strlen($num))
			return false;
		$rand = [];
		while (count($rand) < $num) {
			$rand[] = self::getRandString($length, $type);
			$rand = array_unique($rand);
		}
		return $rand;
	}

	/**
	 * 字符串解析成数组
	 * @access public
	 * @param string $str
	 * @param array $separate
	 * @return null
	 */
	public static function stringToArray($str = '', $separate = ["\n", ':']) {
		if ($str) {
			$arr = explode($separate[0], $str);
			if (0 < count($arr)) {
				foreach ($arr as $val) {
					$item = explode($separate[1], $val);
					if (0 < count($item)) {
						$str_arr[$item[0]] = $item[1];
					}
				}
			}
		}
		return isset($str_arr) ? $str_arr : null;
	}

	/**
	 * CrossOriginResourceSharing Options 授权处理
	 * @access public
	 */
	public static function CORSOptionsHandler() {
		if (request()->isOptions()) {
			header('Access-Control-Allow-Origin:*'); // 指定允许其他域名访问
			header('Access-Control-Allow-Headers:Accept,Referer,Host,Keep-Alive,User-Agent,X-Requested-With,Cache-Control,Content-Type,Cookie,token'); // 响应头设置
			header('Access-Control-Allow-Credentials:true');
			header('Access-Control-Allow-Methods:GET,POST,OPTIONS'); // 响应类型
			header('Access-Control-Max-Age:1728000');
			header('Content-Type:text/plain charset=UTF-8');
			header('Content-Length: 0', true);
			header('status: 204');
			header('HTTP/1.0 204 No Content');
			exit;
		}
	}

	/**
	 * CrossOriginResourceSharing Request Header信息
	 * @access public
	 * @return array
	 */
	public static function CORSRequestHeader() {
		return [
			'Access-Control-Allow-Origin'      => '*',
			'Access-Control-Allow-Credentials' => true,
			'Access-Control-Allow-Methods'     => 'GET,POST,OPTIONS',
			'Access-Defined-X-Support'         => 'service@cuci.cc',
			'Access-Defined-X-Servers'         => 'Guangzhou Cuci Technology Co. Ltd',
		];
	}
}
