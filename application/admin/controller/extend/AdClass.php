<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\controller\extend;

use controller\BasicAdmin;
use think\Db;
use service\ToolsService;

/**
 * 广告分类管理
 * Class AdClass
 * @package app\admin\controller\extend
 */
class AdClass extends BasicAdmin {

	protected $table = 'AdClass';

	/**
	 * 列表
	 * @access public
	 * @return \think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table);
		$this->_list_where($db);
		return parent::_list($db, false, 'index');
	}

	/**
	 * 回收站列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function recycle() {
		$db = Db::name($this->table);
		$this->_list_where($db);
		return parent::_list($db, true, 'index');
	}

	/**
	 * 列表搜索条件
	 * @access protected
	 * @param $db
	 */
	protected function _list_where(&$db) {
		// 应用搜索条件
		$get = $this->request->get();
		// 标签条件
		switch ($this->request->action()) {
			case 'index':
				$db->where('is_deleted', 'eq', 0);
				break;
			case 'recycle': // 回收站
				$db->where('is_deleted', 'eq', 1);
				break;
		}
	}

	/**
	 * 列表数据处理
	 * @access protected
	 * @param array $data
	 */
	protected function _data_filter(&$data) {
		foreach ($data as &$val) {
			$val['update_time'] = format_time($val['update_time']);
			$val['ids'] = join(',', ToolsService::getListSubId($data, $val['id']));
		}
		$data = ToolsService::listToTable($data);
	}

	/**
	 * 其他数据处理
	 * @access public
	 * @param array $other_data
	 * @param array $data
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _other_data_filter(&$other_data, $data) {
		$other_data['classes'] = $this->_form_this_tree_select($data, Db::name($this->table)->where('status', 1));
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
	 * 表单数据前缀方法
	 * @access protected
	 * @param array $data
	 */
	protected function _form_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
			$data['pinyin'] = get_pinyin($data['name']);
		} else {
			// 设置上级菜单
			if (!isset($data['pid']) && $this->request->get('pid', '0')) {
				$data['pid'] = $this->request->get('pid', '0');
			}
		}
	}

	/**
	 * 移动
	 * @access public
	 * @return array|\think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function move() {
		return parent::_form_batch($this->table, 'move');
	}

	/**
	 * 表单数据前缀方法
	 * @access protected
	 * @param array $data
	 */
	protected function _form_batch_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
		} else {
			empty($data) && $this->error(lang('no_operate_data'));
		}
	}

	/**
	 * 排序操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function sort() {
		if ($this->_update($this->table)) {
			$this->success(lang('sort_success'), '');
		}
		$this->error(lang('sort_error'));
	}

	/**
	 * 启用操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function enables() {
		if ($this->_update($this->table)) {
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
		if ($this->_update($this->table)) {
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
			$this->success(lang('del_success'), '');
		}
		$this->error(lang('del_error'));
	}

	/**
	 * 还原操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function restore() {
		if ($this->_update($this->table)) {
			$this->success(lang('restore_success'), '');
		}
		$this->error(lang('restore_error'));
	}

	/**
	 * 完全删除操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function thorough_del() {
		if ($this->_update($this->table)) {
			$this->success(lang('del_success'), '');
		}
		$this->error(lang('del_error'));
	}

	/**
	 * 清空回收站操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function empty_trash() {
		$this->_empty_trash($this->table);
	}
}
