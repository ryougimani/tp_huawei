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
 * 系统菜单验证器
 * Class SystemMenu
 * @package app\admin\validate
 */
class SystemMenu extends validate {

	protected $rule = [
		'id' => 'require',
		'title' => 'require',
		'pid' => 'require',
		'name' => 'require',
		'url' => 'require',
		'node' => 'require',
		'__token__' => 'require|token'
	];

	protected $message = [
		'id.require' => '{%id_required}',
		'title.require' => '{%title_required}',
		'pid.require' => '{%parent_required}',
		'name.require' => '{%name_required}',
		'url.require' => '{%url_required}',
		'node.require' => '{%node_required}',
		'__token__.require' => '{%token_required}',
	];

	protected $scene = [
		'add' => ['title', 'pid', 'name', 'url', 'node', '__token__'],
		'edit' => ['id', 'title', 'pid', 'name', 'url', 'node', '__token__'],
		'move' => ['id', 'pid', '__token__'],
	];
}