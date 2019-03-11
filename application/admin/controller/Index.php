<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use controller\BasicAdmin;
use service\NodeService;
use service\ToolsService;
use service\DataService;
use think\Db;
use think\App;

/**
 * 后台入口
 * Class Index
 * @package app\admin\controller
 */
class Index extends BasicAdmin {

	/**
	 * 后台入口
	 * @access public
	 * @return \think\response\View
	 */
	public function index() {
		return view();
	}

	/**
	 * 获取菜单
	 * @access public
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function config() {
		$param = $this->request->param();
		// 获取缓存名称
		$cache_name = 'config';
		isset($param['group']) && $cache_name .= "_{$param['group']}";
		isset($param['name']) && $cache_name .= "_{$param['name']}";
		// 获取缓存
		$config = cache($cache_name);
		if (empty($nodes)) {
			$db = Db::name('SystemConfig');
			foreach (['name', 'group'] as $key) {
				if (isset($param[$key]) && $param[$key] !== '') {
					$db->where($key, 'eq', $param[$key]);
				}
			}
			$config = $db->column('value', 'name');
			cache($cache_name, $config);
		}
		$this->success(lang('get_success'), '', $config);
	}

	/**
	 * 获取菜单
	 * @access public
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function menu() {
		// 获取系统菜单数据
		$list = Db::name('SystemMenu')->where('status', '1')->order('sort asc,id asc')->select();
		// 转换为树形菜单
		$menus = ToolsService::listToTree($list, 0, 'id', 'pid', 'list');
		// 过滤权限
		$menus = $this->filter_menu($menus, 'list');
		$this->success(lang('get_success'), '', $menus);
	}

	/**
	 * 过滤菜单
	 * @access private
	 * @param $menus
	 * @param string $child
	 * @return mixed
	 */
	private function filter_menu($menus, $child = 'sub') {
		foreach ($menus as $key => &$menu) {
			if (!empty($menu[$child])) {
				$menu[$child] = $this->filter_menu($menu[$child], $child);
			}
			if (!empty($menu['list'])) {
//				$menu['jump'] = '#';
			} elseif (stripos($menu['url'], 'http') === 0) {
				$menu['jump'] = $menu['url'];
			} elseif ($menu['url'] !== '#' && NodeService::checkAuthNode(join('/', array_slice(explode('/', $menu['node']), 0, 3)))) {
				$menu['jump'] = $menu['url'];
			} else {
				unset($menus[$key]);
			}
		}
		return $menus;
	}

	/**
	 * 获取session信息
	 * @access public
	 * @param $name
	 * @param string $group
	 */
	public function session($name, $group = 'admin') {
		$data = [
			$name => session("{$group}.{$name}"),
		];
		$this->success(lang('get_success'), '', $data);
	}

	/**
	 * 获取提示信息数量
	 * @access public
	 */
	public function message_num() {
		$data = [
			'new_message' => 0
		];
		$this->success(lang('get_success'), '', $data);
	}
}
