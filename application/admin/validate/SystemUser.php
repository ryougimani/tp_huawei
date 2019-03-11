<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\Validate;
use think\Db;

/**
 * 系统用户验证器
 * Class SystemUser
 * @package app\admin\validate
 */
class SystemUser extends Validate {

	protected $rule = [
		'id' => 'require',
		'username' => 'require|unique:SystemUser,username',
		'phone' => 'mobile|unique:SystemUser,phone',
		'email' => 'email|unique:SystemUser,email',
		'old_password' => 'require|_checkOldPwd',
		'password' => 'require|regex:/^[\S]{6,16}$/|different:old_password',
		're_password' => 'require|confirm:password',
		'authorize' => '_checkAuthId',
		'department_id' => 'require|_checkDepartmentId',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'username.require' => '{%username_required}',
		'username.unique' => '{%username_unique}',
		'phone.mobile' => '{%phone_regex}',
		'phone.unique' => '{%phone_unique}',
		'email.email' => '{%email_regex}',
		'email.unique' => '{%email_unique}',
		'old_password.require' => '{%old_password_required}',
		'old_password._checkOldPwd' => '{%old_password_checkOldPwd}',
		'password.require' => '{%password_required}',
		'password.regex' => '{%password_regex}',
		'password.different' => '{%password_different}',
		're_password.require' => '{%re_password_required}',
		're_password.confirm' => '{%password_confirm}',
		'department_id.require' => '{%department_required}',
		'department_id._checkDepartmentId' => '{%department_required}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['username', 'phone', 'email', 'password', 're_password', 'department_id', '__token__'],
		'edit' => ['id', 'phone', 'email', '__token__'],
		'password' => ['id', 'password', 're_password', '__token__'],
		'department' => ['id', 'department_id', '__token__'],
		'auth' => ['id', '__token__'],
		'password_self' =>  ['id', 'old_password', 'password', 're_password', '__token__'],
	];

	/**
	 * 验证旧密码
	 * @access protected
	 * @param $value
	 * @param $rule
	 * @param $data
	 * @return bool
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _checkOldPwd($value, $rule, $data) {
		$user = Db::name('SystemUser')->where('id', session('admin.id'))->field('password,random_code')->find();
		return !empty($user) && $user['password'] === password_encode($value, $user['random_code']) ? true : false;
	}

	/**
	 * 验证部门
	 * @access protected
	 * @param $value
	 * @param $rule
	 * @param $data
	 * @return bool
	 */
	protected function _checkDepartmentId($value, $rule, $data) {
		if ($value === 0) return true;
		return Db::name('Department')->where('id', $value)->where('status', 1)->where('is_deleted', 0)->count('id') ? true : false;
	}
}