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
use think\Db;

/**
 * 链接验证器
 * Class Link
 * @package app\admin\validate
 */
class Link extends validate {

	protected $rule = [
		'id' => 'require',
		'name' => 'require',
		'class_id' => 'require|_checkClassId',
		'link' => 'require',
		'audit' => 'require',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'name.require' => '{%name_required}',
		'class_id.require' => '{%class_required}',
		'class_id._checkClassId' => '{%class_required}',
		'link.require' => '{%link_required}',
		'audit.require' => '{%audit_required}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['name', 'class_id', 'link', '__token__'],
		'edit' => ['id', 'name', 'class_id', 'link', '__token__'],
		'move' => ['id', 'class_id', '__token__'],
		'audit' => ['id', 'audit', '__token__'],
	];

	protected function _checkClassId($value, $rule, $data) {
		return Db::name('LinkClass')->where('id', $value)->where('status', 1)->where('is_deleted', 0)->count('id') ? true : false;
	}
}