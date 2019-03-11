<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace service;

use think\Db;

/**
 * 操作日志服务
 * Class LogService
 * @package service
 */
class LogService {

	/**
	 * 获取数据操作对象
	 * @access protected
	 * @return \think\db\Query
	 */
	protected static function db() {
		return Db::name('SystemLog');
	}

	/**
	 * 写入操作日志
	 * @access public
	 * @param string $action
	 * @param string $content
	 * @return bool
	 */
	public static function write($action = '行为', $content = "内容描述") {
		$request = app('request');
		$node = strtolower(join('/', [$request->module(), $request->controller(), $request->action()])); // 节点
		$data = [
			'ip' => $request->ip(),
			'node' => $node,
			'username' => session('admin.username'),
			'action' => $action,
			'content' => $content,
			'create_time' => time()
		];
		return self::db()->insert($data) !== false;
	}
}
