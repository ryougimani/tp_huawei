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
use service\ToolsService;

/**
 * 系统用户管理控制器
 * Class User
 * @package app\admin\controller\system
 */
class User extends BasicAdmin {

	protected $table = 'SystemUser';
	protected $field = 'id,username,phone,email,login_num,login_time,authorize,department_id,status,is_deleted';

	/**
	 * 列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table)->field($this->field);
		$this->_list_where($db);
		return parent::_list($db,true, 'index');
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
		$db = Db::name($this->table)->field($this->field);
		$this->_list_where($db);
		return parent::_list($db, true, 'index');
	}

	/**
	 * 列表搜索条件
	 * @access protected
	 * @param $db
	 */
	protected function _list_where(&$db) {
		$get = $this->request->get();
		foreach (['id', 'username', 'status', 'query_key'] as $key) {
			if (isset($get[$key]) && $get[$key] !== '') {
				if (in_array($key, ['username'])) {
					$db->where($key, 'like', "%{$get[$key]}%");
				} elseif ($key === 'query_key') {

				} else {
					$db->where($key, 'eq', $get[$key]);
				}
			}
		}
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
		// 角色
		$authorize_names = Db::name('SystemAuth')->column('name','id');
		foreach ($data as &$val) {
			// 用户手机
			empty($val['phone']) && $val['phone'] = lang('not_phone');
			// 用户邮箱
			empty($val['email']) && $val['email'] = lang('not_email');
			// 用户角色
			if ($val['id'] === 10000) {
				$val['authorize'] = lang('administrator');
			} elseif (empty($val['authorize'])) {
				$val['authorize'] = lang('not_authorize');
			} else {
				$authorize = [];
				foreach (array_filter(explode(',', $val['authorize'])) as $authorize_id) {
					isset($authorize_names[$authorize_id]) && $authorize[] = $authorize_names[$authorize_id];
				}
				$val['authorize'] = implode(' ', $authorize);
			}
			// 登录次数
			empty($val['login_num']) && $val['login_num'] = lang('not_login');
			// 最后登录时间
			(empty($val['login_time']) && $val['login_time'] = lang('not_login')) || $val['login_time'] = format_time($val['login_time']);
		}
	}

	/**
	 * 搜索表单
	 * @access public
	 */
	public function search_from() {

		$this->success(lang('get_success'), '', ['lang' => $this->_lang()]);
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
		return parent::_form(Db::name($this->table)->field($this->field), 'form');
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
		$this->_is_super_admin();
		return parent::_form(Db::name($this->table)->field($this->field), 'form');
	}

	/**
	 * 用户密码修改
	 * @access public
	 * @return \think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function password() {
		$this->_is_super_admin();
		return parent::_form($this->table, 'password');
	}

	/**
	 * 授权管理
	 * @access public
	 * @return \think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function auth() {
		$this->_is_super_admin();
		return parent::_form($this->table, 'auth');
	}

	/**
	 * 表单数据默认处理
	 * @access public
	 * @param array $data
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function _form_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
			// 生成随机码
			$this->request->action() === 'add' && $data['random_code'] = ToolsService::getRandString(8);
			// 密码加密
			in_array($this->request->action(), ['add', 'password']) && $data['password'] = password_encode($data['password'], isset($data['random_code']) ? $data['random_code'] : Db::name($this->table)->where('id', $data['id'])->value('random_code'));
			// 权限处理
			isset($data['authorize']) && is_array($data['authorize']) && $data['authorize'] = join(',', $data['authorize']);
		} else {
			$otherData = ['lang' => $this->_lang()];
			if (in_array($this->request->action(), ['add', 'auth'])) {
				// 角色
				isset($data['authorize']) && $data['authorize'] = array_filter(explode(',', $data['authorize']));
				$otherData['authorizes'] = Db::name('SystemAuth')->where('status', 1)->select();
			}
			if (in_array($this->request->action(), ['password'])) {
				$otherData['not_auth'] = false;
			}
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
		$this->_is_super_admin();
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
		$this->_is_super_admin();
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
	 * 是否存在超级账号
	 * @access protected
	 */
	protected function _is_super_admin() {
		if (in_array('10000', explode(',', $this->request->param('id')))) {
			$this->error(lang('not_edit_super_admin'));
		}
	}
}
