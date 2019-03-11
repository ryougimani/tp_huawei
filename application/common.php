<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

use think\Db;
use service\DataService;
use service\NodeService;

/**
 * RBAC节点权限验证
 * @param string $node
 * @param bool $check
 * @return bool
 */
function auth($node, $check = true) {
//	if ($check && !is_exist_action($node)) return false;
	return NodeService::checkAuthNode($node);
}

/**
 * 是否存在此节点方法
 * @param string $node
 * @return bool
 */
function is_exist_action($node) {
	list($module, $controller, $action) = explode('/', str_replace(['?', '=', '&'], '/', $node . '///'));
	$controller = explode('.', $controller);
	$controller[count($controller) - 1] = parse_name(end($controller), 1);
	$className = env('app_namespace') . '\\' . $module . '\\controller\\' . implode('\\', $controller);
	if (!class_exists($className)) return false;
	return in_array($action, get_class_methods($className));
}

/**
 * 获取或设置系统参数
 * @param string $name 参数名称
 * @param bool $value 默认是false为获取值，否则为更新
 * @return bool|mixed|string
 * @throws \think\Exception
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 * @throws \think\exception\PDOException
 */
function system_config($name, $value = false) {
	static $config = [];
	if ($value !== false) {
		$config = [];
		$data = ['name'=>$name, 'value'=>$value];
		return DataService::save('SystemConfig', $data, 'name');
	}
	if (empty($config)) {
		foreach (Db::name('SystemConfig')->select() as $item) {
			$config[$item['name']] = $item['value'];
		}
	}
	return isset($config[$name]) ? $config[$name] : '';
}

/**
 * 用户密码加密
 * @param string $password 输入密码
 * @param string $code 加密编号
 * @return string
 */
function password_encode($password, $code){
	return md5(md5($password).$code);
}

/**
 * 汉字转换拼音
 * @param string $chinese 中文字符
 * @param bool $is_initial 是否首字母
 * @param string $delimiter 分割符
 * @return mixed|Pinyin|string
 */
function get_pinyin($chinese, $is_initial = false, $delimiter = '-') {
	$pinyin = new Pinyin();
	if ($is_initial) {
		$result = $pinyin->abbr($chinese, $delimiter);
	} else {
		$result = $pinyin->permalink($chinese, $delimiter);
	}
	return $result;
}

/**
 * 去首尾空格
 * @param $str
 * @return false|string
 */
function mb_trim($str) {
	$str = mb_ereg_replace('^(([ \r\n\t])*(　)*)*', '', $str);
	$str = mb_ereg_replace('(([ \r\n\t])*(　)*)*$', '', $str);
	return $str;
}

/**
 * 替换图片路径标识
 * @param $img
 * @return mixed
 */
function img_replace($img){
	return str_replace('\\','/',$img);
}

/**
 * 安全URL编码
 * @param array|string $data
 * @return string
 */
function encode($data) {
	return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(serialize($data)));
}

/**
 * 时间格式
 * @param datetime $time 时间
 * @return bool|false|string
 */
function format_time($time) {
	(!is_numeric($time)) && $time = strtotime($time);
	if($time == 0) return false;
	$second = time() - $time;
	if ($second >= 2592000) {
		$datetime = date('Y-m-d H:i:s', $time);
	} elseif ($second >= 604800 && $second < 2592000) { // 周
		$week = floor($second / 604800);
	} elseif ($second >= 86400) { // 天
		$day = floor($second / 86400);
	} elseif ($second >= 3600) { // 时
		$hour = floor($second / 3600);
	} elseif ($second >= 60) { // 分
		$minute = floor($second / 60);
	}

	if (isset($datetime)) {
		return $datetime;
	} elseif (isset($week)) {
		return $week . lang('weed_ago');
	} elseif (isset($day)) {
		return $day . lang('day_ago');
	} elseif (isset($hour)) {
		return $hour . lang('hour_ago');
	} elseif (isset($minute)) {
		return $minute . lang('minute_ago');
	} elseif (isset($second)) {
		return $second . lang('second_ago');
	}
	return false;
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}


