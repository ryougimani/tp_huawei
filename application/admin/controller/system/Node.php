<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\controller\system;

use controller\BasicAdmin;
use service\DataService;
use service\NodeService;
use service\ToolsService;
use service\LogService;
use think\Db;

/**
 * 节点管理
 * Class Node
 * @package app\admin\controller\system
 */
class Node extends BasicAdmin {

	protected $table = 'SystemNode';

	/**
	 * 节点列表
	 * @access public
	 * @return \think\response\View
	 */
	public function index() {
		// 获取节点数据
		$nodes = ToolsService::listToTable(NodeService::get(), '', 'node', 'p_node');
		// 分组
		$groups = ['admin' => [], 'home' => []];
		$admin_module = app('config')->get('admin_module');
		foreach ($nodes as &$node) {
			$p_node = explode('/', $node['node'])[0];
			if (in_array($p_node, $admin_module)) {
				$groups['admin'][] = $node;
			} else {
				$groups['home'][] = $node;
			}
		}
		$this->lay_success(lang('get_success'), $groups, $this->_lang());
	}

	/**
	 * 保存节点变更
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function save() {
		if ($this->request->isPost()) {
			$data = $this->request->post();
			if (isset($data['node'])) {
				$result = DataService::save($this->table, $data, 'node');
				if ($result !== false) {
					LogService::write('更新节点成功', json_encode($data));
					$this->success(lang('save_success'));
				}
				LogService::write('更新节点失败', json_encode($data));
			}
		}
		$this->error(lang('save_error'));
	}

	/**
	 * 清理无效的节点记录
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function clear() {
		$nodes = array_keys(NodeService::get());
		if (false !== Db::name($this->table)->whereNotIn('node', $nodes)->delete()) {
			LogService::write('清理节点成功');
			$this->success(lang('clear_node_success'), '');
		}
		LogService::write('清理节点失败');
		$this->error(lang('clear_node_error'));
	}
}
