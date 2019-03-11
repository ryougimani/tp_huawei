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

/**
 * 用户角色验证器
 * Class MemberAuth
 * @package app\admin\validate
 */
class MemberAuth extends Validate {

	protected $rule = [
		'id' => 'require',
		'name' => 'require',
		'desc' => 'require',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'name.require' => '{%name_required}',
		'desc.require' => '{%desc_required}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['name', 'desc', '__token__'],
		'edit' => ['id', 'name', 'desc', '__token__'],
	];
}