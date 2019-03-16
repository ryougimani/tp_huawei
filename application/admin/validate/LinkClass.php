<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\validate;

use think\validate;

/**
 * 链接分类验证器
 * Class LinkClass
 * @package app\admin\validate
 */
class LinkClass extends validate {

	protected $rule = [
		'id' => 'require',
		'name' => 'require',
		'pid' => 'require',
		'is_audit' => 'require',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'name.require' => '{%name_required}',
		'pid.require' => '{%parent_required}',
		'is_audit.require' => '{%is_audit_required}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['name', 'pid', 'is_audit', '__token__'],
		'edit' => ['id', 'name', 'pid', 'is_audit', '__token__'],
		'move' => ['id', 'pid', '__token__'],
	];
}