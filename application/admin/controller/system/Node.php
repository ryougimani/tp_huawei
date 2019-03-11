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

		$this->assign('alert', [
			'type' => 'danger',
			'title' => lang('danger_title'),
			'content' => lang('node_danger')
		]);
		// 获取节点数据
		$nodes = ToolsService::listToTable(NodeService::get(), '', 'node', 'p_node');
		$this->assign('nodes', $nodes);
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
		dump($groups); exit;
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
					$this->success(lang('save_success'), '');
				}
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
			$this->success(lang('clear_node_success'), '');
		}
		$this->error(lang('clear_node_error'));
	}
}
