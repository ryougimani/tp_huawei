<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\controller\member;

use controller\BasicAdmin;
use think\Db;
use service\DataService;
use service\NodeService;
use service\ToolsService;

/**
 * 用户权限管理
 * Class Auth
 * @package app\admin\controller\member
 */
class Auth extends BasicAdmin {

	protected $table = 'MemberAuth';

	/**
	 * 列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table);
		return parent::_list($db);
	}

	/**
	 * 添加
	 * @access public
	 * @return \think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function add() {
		return parent::_form($this->table, 'form');
	}

	/**
	 * 编辑
	 * @access public
	 * @return \think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function edit() {
		return parent::_form($this->table, 'form');
	}

	/**
	 * 表单数据默认处理
	 * @access public
	 * @param array $data
	 */
	public function _form_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
		} else {
			$otherData = ['lang' => $this->_lang()];
			$data['otherData'] = $otherData;
		}
	}

	/**
	 * 启用操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function enables() {
		if (DataService::update($this->table)) {
			$this->success(lang('enables_success'), '');
		}
		$this->error(lang('enables_error'));
	}

	/**
	 * 禁用操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function disables() {
		if (DataService::update($this->table)) {
			$this->success(lang('disables_success'), '');
		}
		$this->error(lang('disables_error'));
	}

	/**
	 * 删除操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function del() {
		if ($this->_update($this->table)) {
			$id = $this->request->post('id');
			Db::name('MemberAuthNode')->where('auth', $id)->delete();
			Db::name('MemberAuthModel')->where('auth', $id)->delete();
			$this->success(lang('del_success'), '');
		}
		$this->error(lang('del_error'));
	}

	/**
	 * 权限授权
	 * @access public
	 * @return string
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function apply() {
		$auth_id = $this->request->get('id', '0');
		$method = '_apply_' . strtolower($this->request->get('action', '0'));
		if (method_exists($this, $method)) {
			return $this->$method($auth_id);
		}
		$this->title = lang('auth');
		return parent::_form($this->table, 'apply');
	}

	/**
	 * 读取授权节点
	 * @access protected
	 * @param $auth_id
	 */
	protected function _apply_get_node($auth_id) {
		$nodes = NodeService::get('home');
		$checked = Db::name('MemberAuthNode')->where(['auth' => $auth_id])->column('node');
		foreach ($nodes as $key => &$node) {
			$node['checked'] = in_array($node['node'], $checked);
			if (empty($node['is_auth']) && substr_count($node['node'], '/') > 1) {
				unset($nodes[$key]);
			}
		}
		$all = $this->_apply_filter(ToolsService::listToTree($nodes, '', 'node', 'p_node', '_sub_'));
		$this->success(lang('get_auth_success'), '', $all);
	}

	/**
	 * 读取授权模块
	 * @access protected
	 * @param $auth_id
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function _apply_get_model($auth_id) {
		$checked = Db::name('MemberAuthModel')->where(['auth' => $auth_id])->field('model,class_id')->select();
		$models = Db::name('SystemModel')->where('is_class', 1)->where('status', 1)->where('is_deleted', 0)->select();
		foreach ($models as $key => &$model) {
			$data = Db::name("{$model['name']}Class")->where('is_deleted', 0)->select();
			if ($data) {
				foreach ($data as &$val) {
					//dump(in_array(['model' => $model['name'], 'class_id' => $val['id']], $checked));
					$val['checked'] = in_array(['model' => $model['name'], 'class_id' => $val['id']], $checked);
					$val['node'] = "{$model['name']}_{$val['id']}";
				}
				$model['_sub_'] = ToolsService::listToTree($data, 0, 'id', 'pid', '_sub_');
			}
		}
		$this->success(lang('get_auth_success'), '', $models);
	}

	/**
	 * 保存授权节点
	 * @access protected
	 * @param $auth_id
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	protected function _apply_save($auth_id) {
		list($data, $post) = [[], $this->request->post()];
		foreach (isset($post['nodes']) ? $post['nodes'] : [] as $node) {
			$data[] = ['auth' => $auth_id, 'node' => $node];
		}
		Db::name('MemberAuthNode')->where(['auth' => $auth_id])->delete();
		Db::name('MemberAuthNode')->insertAll($data);
		$data = [];
		foreach (isset($post['models']) ? $post['models'] : [] as $model) {
			$item = explode('_', $model);
			$data[] = ['auth' => $auth_id, 'model' => $item[0], 'class_id' => $item[1]];
		}
		Db::name('MemberAuthModel')->where(['auth' => $auth_id])->delete();
		Db::name('MemberAuthModel')->insertAll($data);

		$this->success(lang('save_auth_success'), '');
	}

	/**
	 * 节点数据拼装
	 * @access protected
	 * @param array $nodes
	 * @param int $level
	 * @return array
	 */
	protected function _apply_filter($nodes, $level = 1) {
		foreach ($nodes as $key => &$node) {
			if (!empty($node['_sub_']) && is_array($node['_sub_'])) {
				$node['_sub_'] = $this->_apply_filter($node['_sub_'], $level + 1);
			} elseif ($level < 3) {
				unset($nodes[$key]);
			}
		}
		return $nodes;
	}

}
