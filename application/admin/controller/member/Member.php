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
use service\ToolsService;

/**
 * 前台用户管理控制器
 * Class Member
 * @package app\admin\controller\member
 */
class Member extends BasicAdmin {

	protected $table = 'Member';
	protected $field = 'id,username,phone,email,login_num,login_time,authorize,status,is_deleted';

	/**
	 * 列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table)->field($this->field)->order(['id' => 'desc']);
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
		$db = Db::name($this->table)->field($this->field)->order(['id' => 'desc']);
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
		$authorize_names = Db::name('MemberAuth')->column('name','id');
		foreach ($data as &$val) {
			// 用户手机
			empty($val['phone']) && $val['phone'] = lang('not_phone');
			// 用户邮箱
			empty($val['email']) && $val['email'] = lang('not_email');
			// 用户角色
			if (empty($val['authorize'])) {
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
	 * 其他数据处理
	 * @access public
	 * @param array $other_data
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _other_data_filter(&$other_data) {
		$other_data['authorizes'] = Db::name('MemberAuth')->where('status', 1)->select();
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
		return parent::_form($this->table, 'auth');
	}

	/**
	 * 表单数据默认处理
	 * @access public
	 * @param array $data
	 */
	public function _form_filter(&$data) {
		if ($this->request->isPost()) {
			in_array($this->request->action(), ['add', 'edit']) && (empty($data['phone']) && empty($data['email'])) && $this->error(lang('phone_ro_email_require'));
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
			// 生成随机码
			$this->request->action() === 'add' && $data['random_code'] = ToolsService::getRandString(8);
			// 密码加密
			in_array($this->request->action(), ['add', 'password']) && $data['password'] = password_encode($data['password'], isset($data['random_code']) ? $data['random_code'] : Db::name($this->table)->where('id', $data['id'])->value('random_code'));
			// 权限处理
			in_array($this->request->action(), ['add', 'auth']) && $data['authorize'] = (isset($data['authorize']) && is_array($data['authorize'])) ? join(',', $data['authorize']) : null;
		} else {
			if (in_array($this->request->action(), ['add', 'auth'])) {
				// 角色
				isset($data['authorize']) && $data['authorize'] = array_filter(explode(',', $data['authorize']));
			}
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
}
