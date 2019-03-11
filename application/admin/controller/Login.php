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
use think\Db;
use service\LogService;
use service\NodeService;
use think\captcha\Captcha;

/**
 * 后台登陆
 * Class Login
 * @package app\admin\controller
 */
class Login extends BasicAdmin {

	/**
	 * 后台登录
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function index() {
		if ($this->request->isPost()) {
			// 获取POST数据并验证
			$param = $this->request->param();
			!captcha_check($param['captcha']) && $this->error(lang('captcha_check_error'));
			(empty($param['username']) || strlen($param['username']) < 4) && $this->error(lang('username_length'));
			(empty($param['password']) || strlen($param['password']) < 4) && $this->error(lang('password_length'));
			// 获取用户信息并验证
			$user = Db::name('SystemUser')->where('username', $param['username'])->find();
			empty($user) && $this->error(lang('not_username_error'));
			($user['password'] !== password_encode($param['password'], $user['random_code'])) && $this->error(lang('neq_password_error'));
			// 保存SESSION
			session('admin', $user);
			// 修改用户最后登录时间和登录次数
			Db::name('SystemUser')->where('id', $user['id'])->update(['login_num' => ['INC', '1'], 'login_time' => time(), 'login_ip' => $this->request->ip()]);
			// 获取用户权限节点
			!empty($user['authorize']) && NodeService::applyAuthNode(true);
			LogService::write('用户登录系统成功', '');
			$this->success(lang('login_success'), '@admin', ['access_token' => session_id()]);
		}
	}

	/**
	 * 退出登录
	 * @access public
	 */
	public function logout() {
		LogService::write('用户退出系统成功', '');
		session('user', null);
		session_destroy();
		$this->success(lang('logout_success'), 'admin');
	}
}
