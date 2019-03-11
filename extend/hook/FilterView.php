<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace hook;

use think\Request;

/**
 * 视图输出过滤
 * Class FilterView
 * @package hook
 */
class FilterView {

	protected $request; // 当前请求对象

	/**
	 * 行为入口
	 * @access public
	 * @param $params
	 */
	public function run(&$params) {
		$this->request = Request::instance();
		list($appRoot, $uriSelf) = [$this->request->root(true), $this->request->url(true)];
		$uriRoot = strpos($appRoot, EXT) ? ltrim(dirname($appRoot), DS) : $appRoot;
		$uriStatic = "{$uriRoot}/static";
		$replace = [
			'__APP__' => $appRoot,
			'__SELF__' => $uriSelf,
			'__PUBLIC__' => $uriRoot,
			'__STATIC__' => $uriStatic
		];
		$params = str_replace(array_keys($replace), array_values($replace), $params);
	}
}
