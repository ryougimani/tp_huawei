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
 * 广告验证器
 * Class Ad
 * @package app\admin\validate
 */
class Ad extends validate {

	protected $rule = [
		'id' => 'require',
		'title' => 'require',
		'class_id' => 'require|_checkClassId',
		'link' => 'require',
		'start_time' => 'date',
		'end_time' => 'date',
		'audit' => 'require',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'title.require' => '{%title_required}',
		'class_id.require' => '{%class_required}',
		'class_id._checkClassId' => '{%class_required}',
		'link.require' => '{%link_required}',
		'start_time.date' => '{%start_time_date}',
		'end_time.date' => '{%end_time_date}',
		'audit.require' => '{%audit_required}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['title', 'class_id', 'link', 'start_time', 'end_time', '__token__'],
		'edit' => ['id', 'title', 'class_id', 'link', 'start_time', 'end_time', '__token__'],
		'move' => ['id', 'class_id', '__token__'],
		'audit' => ['id', 'audit', '__token__'],
	];

	protected function _checkClassId($value, $rule, $data) {
		return Db::name('AdClass')->where('id', $value)->where('status', 1)->where('is_deleted', 0)->count('id') ? true : false;
	}
}