<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace controller;

use think\Controller;
use think\Container;
use think\exception\HttpResponseException;
use think\Response;
use think\Db;
use service\DataService;
use service\LogService;
use service\ToolsService;

/**
 * 后台权限基础控制器
 * Class BasicAdmin
 * @package controller
 */
class BasicAdmin extends Controller {

	protected $table; // 默认操作数据表
	protected $lang_range; // 语言范围
	protected $other_data; // 其他数据

	/**
	 * 初始化
	 * @access protected
	 */
	protected function initialize() {
		$this->lang_range = $this->app['lang']->detect();
	}

	/**
	 * 操作成功跳转的快捷方法
	 * @access protected
	 * @param array $data 返回的数据
	 * @param int $count 总数
	 * @param string $lang 语言包
	 * @param string $otherData 其他数据
	 * @param array $header 发送的Header信息
	 * @return void
	 */
	protected function lay_data($data = [], $count = 0, $lang = '', $otherData = '', array $header = []) {
		if (count($data) > 0) {
			$result = ['code' => 0, 'msg' => lang('get_success'), 'data' => $data];
		} else {
			$result = ['code' => -1, 'msg' => lang('not_data'),];
		}
		!empty($lang) && $result['lang'] = $lang;
		!empty($otherData) && $result['other_data'] = $otherData;
		throw new HttpResponseException(json($result, 200, $header));
	}

	/**
	 * 操作成功跳转的快捷方法
	 * @access protected
	 * @param string $msg 提示信息
	 * @param string $data 返回的数据
	 * @param string $lang 语言包
	 * @param string $otherData 其他数据
	 * @param array $header 发送的Header信息
	 * @return void
	 */
	protected function lay_success($msg = '', $data = '', $lang = '', $otherData = '', array $header = []) {
		$result = [
			'code' => 1,
			'msg'  => $msg,
			'data' => $data
		];
		!empty($lang) && $result['lang'] = $lang;
		!empty($otherData) && $result['other_data'] = $otherData;
		throw new HttpResponseException(json($result, 200, $header));
	}

	/**
	 * 操作错误跳转的快捷方法
	 * @access protected
	 * @param string $msg 提示信息
	 * @param array $header 发送的Header信息
	 * @return void
	 */
	protected function lay_error($msg = '', array $header = []) {
		$result = [
			'code' => -1,
			'msg'  => $msg
		];
		$type = $this->getResponseType();
		if ('html' == strtolower($type)) {
			$type = 'jump';
		}
		$response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_error_tmpl')]);
		throw new HttpResponseException($response);
	}

	/**
	 * 返回当前语言包
	 * @access protected
	 * @param array $lang
	 * @return array
	 */
	protected function _lang($lang = []) {
		list($module, $controller) = [parse_name($this->request->module()), parse_name($this->request->controller())];
		$langModule = env('root_path') . 'lang' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $this->lang_range . DIRECTORY_SEPARATOR . 'common.php';
		file_exists($langModule) && $lang = array_merge($lang, require($langModule));
		$langController = env('root_path') . 'lang' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $this->lang_range . DIRECTORY_SEPARATOR . preg_replace('/\./', DIRECTORY_SEPARATOR, $controller) . '.php';
		file_exists($langController) && $lang = array_merge($lang, require($langController));
		return $lang;
	}

	/**
	 * 当前对象回调方法
	 * @access protected
	 * @param string $method 方法名称
	 * @param array $data 需要处理的数据
	 * @param array $otherData 其他数据
	 * @return bool
	 */
	protected function _callback($method, &$data, $otherData) {
		foreach ([$method, "_{$this->request->action()}{$method}"] as $_method) {
			if (method_exists($this, $_method) && false === $this->$_method($data, $otherData)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 分页处理方法
	 * @access protected
	 * @param \think\db $db 数据库查询对象
	 * @param bool $total 总记录数
	 * @return mixed
	 */
	protected function _page(&$db, $total = false) {
		$limit = $this->request->param('limit', cookie('admin-limit'));
		cookie('admin-limit', $limit >= 10 ? $limit : 20);
		// 使用paginate查询分页
		$query = $this->request->get();
		$page = $db->paginate($limit, $total, ['query' => $query]);
		list($result['list'], $result['total']) = [$page->all(), $page->total()];
		return $result;
	}

	/**
	 * 列表集成处理方法
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param bool $isPage 是启用分页
	 * @param string $template 模板
	 * @param bool $isDisplay 是否直接输出显示
	 * @param bool $total 总记录数
	 * @param array $result 返回内容
	 * @return array|mixed|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _list($dbQuery = null, $isPage = true, $template = '', $isDisplay = true, $total = false, $result = []) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		// 获取列表的排序条件
		if (null === $db->getOptions('order')) {
			list($fields, $order) = [$db->getTableFields(), []];
			in_array('sort', $fields) && $order = array_merge(['sort' => 'asc'], $order);
			in_array('id', $fields) && $order = array_merge(['id' => 'asc'], $order);
			$db->order($order);
		}
		// 是否分页显示
		if ($isPage) {
			$result = $this->_page($db, $total);
		} else {
			$result = ['list' => $db->select(), 'total' => false];
		}
		// 列表数据处理
		if (false !== $this->_callback('_data_filter', $result['list'], []) && $isDisplay) {
			$this->_callback('_other_data_filter', $other_data, []);
			$this->lay_data($result['list'], $result['total'], $this->_lang(), $other_data);
		}
		return $result;
	}

	/**
	 * 表单集成处理方法
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param string $template 模板
	 * @param string $pkField 主键
	 * @param array $where 查询规则
	 * @param array $extendData 扩展数据
	 * @return array|\think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	protected function _form($dbQuery = null, $template = 'form', $pkField = '', $where = [], $extendData = []) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		// 获取主键名称与值
		$pk = empty($pkField) ? ($db->getPk() ? $db->getPk() : 'id') : $pkField;
		$pkValue = $this->request->request($pk, isset($where[$pk]) ? $where[$pk] : (isset($extendData[$pk]) ? $extendData[$pk] : null));
		if ($this->request->isPost()) {
			// POST请求, 数据自动存库
			$data = array_merge($this->request->post(), $extendData);
			if (false !== $this->_callback('_form_filter', $data, [])) {
				if ($this->request->action() === 'add_batch') {
					$result = DataService::insertAll($dbQuery, $data, $pk, $where);
				} else {
					$result = DataService::save($dbQuery, $data, $pk, $where);
				}
				if (false !== $this->_callback('_form_result', $data, $result)) {
					if ($result !== false) {
						LogService::write('数据操作成功', json_encode($data));
						$this->success(lang('form_success'));
					}
					LogService::write('数据操作失败', json_encode($data));
					$this->error(lang('form_error'));
				}
			}
		} else {
			// 非POST请求, 获取数据并显示表单页面
			$data = ($pkValue !== null) ? array_merge((array)$db->where($pk, $pkValue)->where($where)->find(), $extendData) : $extendData;
			if (false !== $this->_callback('_form_filter', $data, [])) {
				$this->_callback('_other_data_filter', $other_data, $data);
				$data['__token__'] = token();
				$this->lay_success('', $data, $this->_lang(), $other_data);
			}
			return $data;
		}
	}

	/**
	 * 批量表单集成处理方法
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param string $template 模板
	 * @param string $pkField 主键
	 * @param array $where 查询规则
	 * @param array $extendData 扩展数据
	 * @return array|\think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	protected function _form_batch($dbQuery = null, $template = 'form', $pkField = '', $where = [], $extendData = []) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		// 获取主键名称与值
		$pk = empty($pkField) ? ($db->getPk() ? $db->getPk() : 'id') : $pkField;
		$pkValue = $this->request->request($pk, isset($where[$pk]) ? $where[$pk] : (isset($extendData[$pk]) ? $extendData[$pk] : null));
		if ($this->request->isPost()) {
			// POST请求, 数据自动存库
			$data = array_merge($this->request->post(), $extendData);
			//$update = $data; unset($update[$pk]);
			if (false !== $this->_callback('_form_batch_filter', $data, [])) {
				$result = DataService::save($dbQuery, $data, $pk, $where);
				//$result = $db->where($pk, 'in', $pkValue)->where($where)->update($update);
				if (false !== $this->_callback('_form_batch_result', $data, $result)) {
					if ($result !== false) {
						LogService::write('数据操作成功', json_encode($data));
						$this->success(lang('operate_success'), '');
					}
					LogService::write('数据操作失败', json_encode($data));
					$this->error(lang('operate_error'));
				}
			}
		} else {
			// 非POST请求, 获取数据并显示表单页面
			$data = ($pkValue !== null) ? ToolsService::rowToCol($db->where($pk, 'in', $pkValue)->where($where)->select(), $extendData) : $extendData;
			if (false !== $this->_callback('_form_batch_filter', $data, [])) {
				$this->_callback('_other_data_filter', $other_data, $data);
				$data['id'] = implode(',', $data['id']);
				$data['__token__'] = token();
				$this->lay_success('', $data, $this->_lang(), $other_data);
			}
			return $data;
		}
	}

	/**
	 * 修改集成处理方法
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @return bool
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	protected function _update($dbQuery = null) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		if ($this->request->isPost()) {
			// POST请求, 数据自动存库
			$result = DataService::update($db);;
			$data = $this->request->post();
			if ($result !== false) {
				LogService::write('数据操作成功', json_encode($data));
				return $result;
			}
			LogService::write('数据操作失败', json_encode($data));
			return $result;
		}
	}

	/**
	 * 修改集成处理方法
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	protected function _empty_trash($dbQuery = null) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		list($table, $fields) = [$db->getTable(), $db->getTableFields()];
		$data = [];
		if ($this->request->isPost()) {
			if (in_array('is_deleted', $fields)) {
				if ($count = $db->where('is_deleted', 1)->count('id')) {
					$data = $db->where('is_deleted', 1)->column('id');
					if (Db::table($table)->where('is_deleted', 1)->delete() !== false) {
						LogService::write('清空回收站成功', json_encode($data));
						$this->success(lang('empty_success'), '');
					}
				}
			}
		}
		LogService::write('清空回收站失败', json_encode($data));
		$this->error(lang('empty_error'));
	}

	/**
	 * 生成下拉选择框内容
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param bool $tree 是否树形
	 * @param string $firstValue 首行值
	 * @param string $key 键
	 * @param int $root
	 * @param string $pk 主键
	 * @param string $ppk 父主键
	 * @return array|\PDOStatement|string|\think\Collection
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form_select($dbQuery = null, $tree = false, $firstValue = '', $key = 'name', $root = -1, $pk = 'id', $ppk = 'pid') {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		$fields = $db->getTableFields();
		if (null === $db->getOptions('order')) {
			in_array('sort', $fields) && $db->order(['sort' => 'asc', 'id' => 'asc']);
		}
//		if (null === $db->getOptions('where') ) {
//			in_array('status', $fields) && $db->where('status', 1);
			in_array('is_deleted', $fields) && $db->where('is_deleted', 0);
//		}
		$data = $db->select();
		empty($firstValue) && $firstValue = lang('class_placeholder');
		if ($tree) {
			foreach ($data as &$val) {
				$val['ids'] = join(',', ToolsService::getListSubId($data, $val['id']));
			}
			$root == -1 && array_unshift($data, [$key => $firstValue, $pk => 0, $ppk => -1, 'ids' => '0']);
			$data = ToolsService::listToTable($data, $root, $pk, $ppk);
			//array_unshift($data, [$key => $firstValue, $pk => 0, 'ids' => '', 'spl' => '', 'path' => '-0']);
		} else {
			array_unshift($data, [$key => $firstValue, $pk => '']);
		}
		return $data;
	}

	/**
	 * 生成自身树形下拉选择框内容
	 * @access protected
	 * @param array $data 当前数据
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param string $firstValue 首行值
	 * @param string $key
	 * @param int $root
	 * @return array|\PDOStatement|string|\think\Collection
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form_this_tree_select(&$data, $dbQuery = null, $firstValue = '', $key = 'name', $root = -1) {
		empty($firstValue) && $firstValue = lang('top_class');
		$select_data = $this->_form_select($dbQuery, true, $firstValue, $key, $root);
		if (isset($data['pid']) && isset($data['id'])) {
			foreach ($select_data as $key => &$select_item) {
				if (is_array($data['pid'])) {
					foreach ($data['pid'] as $k => $v) {
						$current_path = "-{$data['pid'][$k]}-{$data['id'][$k]}";
						if ($data['pid'][$k] !== '' && (stripos("{$select_item['path']}-", "{$current_path}-") !== false || $select_item['path'] === $current_path)) {
							unset($select_data[$key]);
							break;
						}
					}
				} else {
					$current_path = "-{$data['pid']}-{$data['id']}";
					if ($data['pid'] !== '' && (stripos("{$select_item['path']}-", "{$current_path}-") !== false || $select_item['path'] === $current_path)) {
						unset($select_data[$key]);
					}
				}
			}
		}
		return $select_data;
	}
}
