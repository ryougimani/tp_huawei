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
use think\Db;
use think\exception\HttpResponseException;

/**
 * 访问权限管理
 * Class AccessAuth
 * @package hook
 */
class AccessAuth {

	/**
	 * 行为入口
	 * @access public
	 * @param Request $request
	 * @param $params
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function run(Request $request, $params) {
		// 获取模块、控制器、方法名称
		list($module, $controller, $action) = [parse_name($request->module()), parse_name($request->controller()), $request->action()];
		if ($module !== '') {
			// 有模块、控制器、方法名称
			$node = parse_name("{$module}/{$controller}") . strtolower("/{$action}");
			$system_node = Db::name('SystemNode')->cache(true, 30)->where('node', $node)->find();
			$access = [
				'is_auth' => intval(!empty($system_node['is_auth'])),
				'is_login' => empty($system_node['is_auth']) ? intval(!empty($system_node['is_login'])) : 1
			];
			// 判断是否后台模块
			if (in_array($module, Container::get('config')->get('admin_module'))) { // 后台模块
				!in_array($node, ['admin/index/index', 'admin/login/index']) && $access['is_login'] = 1;
				// layuiAdmin的access_token
				$access_token = $request->param('access_token');
				!empty($access_token) && session_id($access_token);
				// 登录状态检查
				if (!empty($access['is_login']) && !session('admin')) {
					$msg = ['code' => 1001, 'msg' => lang('not_logged'), 'url' => url('@admin')];
					throw new HttpResponseException($request->isAjax() ? json($msg) : redirect($msg['url']));
				}
			} else { // 前台模块
				// 登录状态检查
				if (!empty($access['is_login']) && !session('member')) {
					$msg = ['code' => 0, 'msg' => lang('not_logged'), 'url' => url('@login')];
					throw new HttpResponseException($request->isAjax() ? json($msg) : redirect($msg['url']));
				}
			}
			// 访问权限检查
			if (!empty($access['is_auth']) && !auth($node, false)) {
				throw new HttpResponseException(json(['code' => 0, 'msg' => lang('not_auth')]));
			}
		}
	}
}
