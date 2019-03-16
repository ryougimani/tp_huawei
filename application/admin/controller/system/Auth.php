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
use service\DataService;
use service\NodeService;
use service\ToolsService;

/**
 * 权限管理
 * Class Auth
 * @package app\admin\controller\system
 */
class Auth extends BasicAdmin {

	protected $table = 'SystemAuth';

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
	 * 列表数据处理
	 * @access protected
	 * @param array $data
	 */
	protected function _data_filter(&$data) {
		foreach ($data as &$val) {
			$val['update_time'] = format_time($val['update_time']);
		}
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
		}
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
			$id = $this->request->post('id');
			Db::name('SystemAuthNode')->where('auth', $id)->delete();
			Db::name('SystemAuthModel')->where('auth', $id)->delete();
			$this->success(lang('del_success'), '');
		}
		$this->error(lang('del_error'));
	}
}
