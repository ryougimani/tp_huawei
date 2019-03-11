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
 * 系统菜单后台管理管理
 * Class Menu
 * @package app\admin\controller\system
 */
class Menu extends BasicAdmin {

	protected $table = 'SystemMenu';

	/**
	 * 菜单列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table)->order(['sort' => 'asc']);
		return parent::_list($db, false);
	}

	/**
	 * 列表数据处理
	 * @access protected
	 * @param array $data
	 */
	protected function _data_filter(&$data) {
		foreach ($data as &$val) {
			$val['ids'] = join(',', ToolsService::getListSubId($data, $val['id']));
		}
		$data = ToolsService::listToTable($data);
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
		return parent::_form($this->table, 'form');
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
		return parent::_form($this->table, 'form');
	}

	/**
	 * 表单数据前缀方法
	 * @access protected
	 * @param array $data 数据
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
		} else {
			$otherData = ['lang' => $this->_lang()];
			// 上级菜单内容处理
			$menus = $this->_form_select($this->table, true, lang('top_menu'), 'title');
			foreach ($menus as $key => &$menu) {
				// 删除3级以上菜单
				if (substr_count($menu['path'], '-') > 3) {
					unset($menus[$key]);
					continue;
				}
				if (isset($data['pid'])) {
					$current_path = "-{$data['pid']}-{$data['id']}";
					if ($data['pid'] !== '' && (stripos("{$menu['path']}-", "{$current_path}-") !== false || $menu['path'] === $current_path)) {
						unset($menus[$key]);
					}
				}
			}
			// 设置上级菜单
			if (!isset($data['pid']) && $this->request->get('pid', '0')) {
				$data['pid'] = $this->request->get('pid', '0');
			}
			$otherData['menus'] = $menus;
			$data['otherData'] = $otherData;
		}
	}

	/**
	 * 移动
	 * @access public
	 * @return array|\think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function move() {
		$this->title = lang('move_title');
		return parent::_form_batch($this->table, 'move');
	}

	/**
	 * 表单数据前缀方法
	 * @access protected
	 * @param array $data
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form_batch_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
		} else {
			// 上级菜单内容处理
			$menus = $this->_form_select($this->table, true, lang('top_menu'), 'title');
			foreach ($menus as $key => &$menu) {
				// 删除3级以上菜单
				if (substr_count($menu['path'], '-') > 3) {
					unset($menus[$key]);
					continue;
				}
				if (isset($data['pid']) && is_array($data['pid'])) {
					foreach ($data['pid'] as $k => $v) {
						$current_path = "-{$data['pid'][$k]}-{$data['id'][$k]}";
						if ($data['pid'][$k] !== '' && (stripos("{$menu['path']}-", "{$current_path}-") !== false || $menu['path'] === $current_path)) {
							unset($menus[$key]);
						}
					}
				}
			}
			$this->assign('menus', $menus);
			$data['id'] = implode(',', $data['id']);
		}
	}

	/**
	 * 排序操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function sort() {
		if ($this->_update($this->table)) {
			$this->success(lang('sort_success'), '');
		}
		$this->error(lang('sort_error'));
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
}
