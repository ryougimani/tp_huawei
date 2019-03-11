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
use think\Container;

/**
 * 加载语言包管理
 * Class AccessAuth
 * @package hook
 */
class LoadLang {

	/**
	 * 行为入口
	 * @access public
	 * @param Request $request
	 * @param $params
	 */
	public function run(Request $request, $params) {
		$lang = app('lang');
		$langRange = $lang->detect();
		// 获取模块、控制器
		list($module, $controller) = [parse_name($request->module()), parse_name($request->controller())];
		if (!in_array($module, Container::get('config')->get('admin_module'))) {
			// 非后台模块加载全局语言
			$langCommon = env('root_path') . 'lang' . DIRECTORY_SEPARATOR . $langRange . '.php';
			file_exists($langCommon) && $lang->load($langCommon);
		}
		$langModule = env('root_path') . 'lang' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $langRange . DIRECTORY_SEPARATOR . 'common.php';
		file_exists($langModule) && $lang->load($langModule);
		$langController = env('root_path') . 'lang' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $langRange . DIRECTORY_SEPARATOR . preg_replace('/\./', DIRECTORY_SEPARATOR, $controller) . '.php';
		file_exists($langController) && $lang->load($langController);
	}
}
