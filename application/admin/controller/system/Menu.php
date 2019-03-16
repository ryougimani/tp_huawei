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
use think\Db;
use service\ToolsService;

/**
 * 系统菜单后台管理管理
 * Class Menu
 * @package app\admin\controller\system
 */
class Menu extends BasicAdmin {

	protected $table = 'SystemMenu';

	/**
	 * 列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table)->order(['sort' => 'asc', 'id' => 'asc']);
		return parent::_list($db, false);
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
		$other_data['menus'] = $this->_form_this_tree_select($data, Db::name($this->table), lang('top_menu'), 'title');
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
	 * @param array $data 数据
	 */
	protected function _form_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
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
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form_batch_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
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
}
