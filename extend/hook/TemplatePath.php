<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace hook;

use think\Container;
use think\Request;

/**
 * 模板路径处理
 * Class Template
 * @package hook
 */
class TemplatePath {

	/**
	 * 行为入口
	 * @access public
	 * @param Request $request
	 * @param $params
	 */
	public function run(Request $request, $params) {
		$config = Container::get('config');
		// 获取模块名称
		$module = $request->module();
		// 模板根路径
		if ($view_base = $config->get('template.view_base')) {
			// 判断是否后台模块
			if (in_array($module, $config->get('admin_module'))) {
				$template_path = $view_base;
			} elseif (in_array($module, $config->get('api_module'))) {
				$template_path = $view_base . 'api' . DIRECTORY_SEPARATOR;
			} else {
				// 是否手机浏览
				$type = ($request->isMobile() ? 'mobile' : 'pc') . DIRECTORY_SEPARATOR;
				// 模板名称
				$theme = 'default' . DIRECTORY_SEPARATOR;
				$template_path = $view_base . $type . $theme;
			}
			// 赋值模板路径
			$config->set('template.view_base', $template_path);
			Container::get('view')->config('view_base', $template_path);
		}
	}
}
