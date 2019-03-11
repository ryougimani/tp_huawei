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
 * 前台用户验证器
 * Class Member
 * @package app\admin\validate
 */
class Member extends validate {

	protected $rule = [
		'id' => 'require',
		'username' => 'require|unique:Member,username',
		'phone' => 'mobile|unique:Member,phone',
		'email' => 'email|unique:Member,email',
		'password' => 'require|regex:/^[\S]{6,16}$/',
		're_password' => 'require|confirm:password',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'username.require' => '{%username_required}',
		'username.unique' => '{%username_unique}',
		'phone.mobile' => '{%phone_regex}',
		'phone.unique' => '{%phone_unique}',
		'email.email' => '{%email_regex}',
		'email.unique' => '{%phone_unique}',
		'password.require' => '{%password_required}',
		'password.regex' => '{%password_regex}',
		're_password.require' => '{%re_password_required}',
		're_password.confirm' => '{%password_confirm}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['username', 'phone', 'email', 'password', 're_password', '__token__'],
		'edit' => ['id', 'phone', 'email', '__token__'],
		'password' => ['id', 'password', 're_password', '__token__'],
		'auth' => ['id', '__token__'],
	];
}