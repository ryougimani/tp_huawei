<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\controller\extend;

use controller\BasicAdmin;
use think\Db;

/**
 * 广告管理
 * Class Ad
 * @package app\admin\controller\extend
 */
class Ad extends BasicAdmin {

	protected $table = 'Ad';

	/**
	 * 列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$db = Db::name($this->table)->order(['sort' => 'asc', 'id' => 'desc']);
		$this->_list_where($db);
		return parent::_list($db, true, 'index');
	}

	/**
	 * 审核列表
	 * @access public
	 * @return array|string
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function wait_audit() {
		$db = Db::name($this->table)->order(['sort' => 'asc', 'id' => 'desc']);
		$this->_list_where($db);
		return parent::_list($db, true, 'index');
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
		$db = Db::name($this->table)->order(['id' => 'desc']);
		$this->_list_where($db);
		return parent::_list($db, true, 'index');
	}

	/**
	 * 列表搜索条件
	 * @access protected
	 * @param $db
	 */
	protected function _list_where(&$db) {
		// 应用搜索条件
		$get = $this->request->get();
		foreach (['id', 'title', 'class_id', 'date_range', 'query_key', 'audit'] as $key) {
			if (isset($get[$key]) && $get[$key] !== '') {
				if (in_array($key, ['title'])) {
					$db->where($key, 'like', "%{$get[$key]}%");
				} elseif (in_array($key, ['class_id'])) {
					!empty($get[$key]) && $db->where($key, 'in', $get[$key]);
				} elseif (in_array($key, ['date_range'])) {
					$get[$key] = explode(' - ', $get[$key]);
					foreach ($get[$key] as &$v)
						$v = strtotime($v);
//					$db->where($key, 'between', $get[$key]);
					$db->where('start_time', ['>=', $get[$key][0]], ['<=', $get[$key][1]], 'or');
					$db->where('end_time', ['<=', $get[$key][1]], ['>=', $get[$key][0]], 'or');
				} elseif ($key === 'query_key') {
					$db->where('title', 'like', "%{$get[$key]}%");
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
			case 'wait_audit':
				$db->where('is_deleted', 'eq', 0)->where('audit', 'eq', 0);
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
		$class_names = Db::name("{$this->table}Class")->where('status', 1)->column('name', 'id');
		foreach ($data as &$val) {
			if ($val['audit'] == 0) {
				$val['title'] = lang('wait_audit_title') . $val['title'];
			} elseif ($val['audit'] == -1) {
				$val['title'] = lang('not_through_title') . $val['title'];
			}
			$val['class'] = (isset($class_names[$val['class_id']]) ? $class_names[$val['class_id']] : lang('not_class'));
			$val['date_range'] = (!empty($val['start_time']) && !empty($val['end_time'])) ? date('Y-m-d', $val['start_time']) . ' - ' . date('Y-m-d', $val['end_time']) : lang('not_date_range');
//			$val['start_time'] = (empty($val['start_time']) ? lang('not_start_time') : date('Y-m-d', $val['start_time']));
//			$val['end_time'] = (empty($val['end_time']) ? lang('not_end_time') : date('Y-m-d', $val['end_time']));
			$val['update_time'] = format_time($val['update_time']);
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
		if (in_array($this->request->action(), ['add', 'edit', 'move'])) {
			$class_first_value = 'class_placeholder';
		} else {
			$class_first_value = 'all_class';
		}
		$other_data['classes'] = $this->_form_select(Db::name("{$this->table}Class")->where('status', 1), true, lang($class_first_value));
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
	 * @param array $data
	 */
	protected function _form_filter(&$data) {
		if ($this->request->isPost()) {
			// 时间范围
			!empty($data['date_range']) && list($data['start_time'], $data['end_time']) = explode(' - ', $data['date_range']);
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
			// 开始时间
			$data['start_time'] = empty($data['start_time']) ? null : strtotime($data['start_time']);
			// 结束时间
			$data['end_time'] = empty($data['end_time']) ? null : strtotime($data['end_time']);
			// 审核
			$data['audit'] = 0;
			!empty($data['class_id']) && empty(Db::name("{$this->table}Class")->where('id', $data['class_id'])->value('is_audit')) && $data['audit'] = 1;
		} else {
			// 时间范围
			(!empty($data['start_time']) && !empty($data['end_time'])) && $data['date_range'] = date('Y-m-d', $data['start_time']) . ' - ' . date('Y-m-d', $data['end_time']);
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
		return parent::_form_batch($this->table, 'move');
	}

	/**
	 * 审核
	 * @access public
	 * @return array|\think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function audit() {
		return parent::_form_batch($this->table, 'audit', 'id', ['audit' => 0]);
	}

	/**
	 * 表单数据前缀方法
	 * @access protected
	 * @param array $data
	 */
	protected function _form_batch_filter(&$data) {
		if ($this->request->isPost()) {
			// 规则验证
			$result = $this->validate($data, "{$this->table}.{$this->request->action()}");
			(true !== $result) && $this->error($result);
		} else {
			empty($data) && $this->error(lang('no_operate_data'));
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
			$this->success(lang('sort_success'));
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
			$this->success(lang('enables_success'));
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
			$this->success(lang('disables_success'));
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
			$this->success(lang('del_success'));
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
			$this->success(lang('restore_success'));
		}
		$this->error(lang('restore_error'));
	}

	/**
	 * 完全删除操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function thorough_del() {
		if ($this->_update($this->table)) {
			$this->success(lang('del_success'));
		}
		$this->error(lang('del_error'));
	}

	/**
	 * 清空回收站操作
	 * @access public
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	public function empty_trash() {
		$this->_empty_trash($this->table);
	}
}
