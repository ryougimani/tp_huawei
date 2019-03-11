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

/**
 * 后台权限基础控制器
 * Class BasicAdmin
 * @package controller
 */
class BasicAdmin extends Controller {

	protected $table; // 默认操作数据表
	protected $lang_range; // 语言范围
	protected $title; // 页面标题

	/**
	 * 初始化
	 * @access protected
	 */
	protected function initialize() {
		$this->lang_range = $this->app['lang']->detect();
	}

//	/**
//	 * 操作成功跳转的快捷方法
//	 * @access protected
//	 * @param  mixed     $msg 提示信息
//	 * @param  string    $url 跳转的URL地址
//	 * @param  mixed     $data 返回的数据
//	 * @param  integer   $wait 跳转等待时间
//	 * @param  array     $header 发送的Header信息
//	 * @return void
//	 */
//	protected function success($msg = '', $url = null, $data = '', $wait = 3, array $header = []) {
//		$count = 0;
//		isset($data['total']) && $count = $data['total'];
//		isset($data['list']) && $data = $data['list'];
//		if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
//			$url = $_SERVER["HTTP_REFERER"];
//		} elseif ('' !== $url) {
//			$url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : Container::get('url')->build($url);
//		}
//		$result = [
//			'code' => 0,
//			'msg'  => $msg,
//			'data' => $data,
//			'url'  => $url,
//			'wait' => $wait,
//			'count' => $count,
//		];
//		$type = $this->getResponseType();
//		if ('html' == strtolower($type)) {
//			$type = 'jump';
//		}
//		$response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_success_tmpl')]);
//		throw new HttpResponseException($response);
//	}
//
//	/**
//	 * 操作错误跳转的快捷方法
//	 * @access protected
//	 * @param  mixed     $msg 提示信息
//	 * @param  string    $url 跳转的URL地址
//	 * @param  mixed     $data 返回的数据
//	 * @param  integer   $wait 跳转等待时间
//	 * @param  array     $header 发送的Header信息
//	 * @return void
//	 */
//	protected function error($msg = '', $url = null, $data = '', $wait = 3, array $header = []) {
//		$type = $this->getResponseType();
//		if (is_null($url)) {
//			$url = $this->app['request']->isAjax() ? '' : 'javascript:history.back(-1);';
//		} elseif ('' !== $url) {
//			$url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : $this->app['url']->build($url);
//		}
//		$result = [
//			'code' => -1,
//			'msg'  => $msg,
//			'data' => $data,
//			'url'  => $url,
//			'wait' => $wait,
//		];
//		if ('html' == strtolower($type)) {
//			$type = 'jump';
//		}
//		$response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_error_tmpl')]);
//		throw new HttpResponseException($response);
//	}

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
			if ($this->request->isAjax()) {
//				return $this->success(lang('get_success'), '', $result);
				return json([
					'code' => 0,
					'msg' => ($result['total'] > 0 ? lang('get_success') : lang('not_data')),
					'data' => $result['list'],
					'count' => $result['total'],
				]);
			} else {
				return view($template, $result);
			}
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
			$data['__token__'] = token();
			if (false !== $this->_callback('_form_filter', $data, [])) {
				return $this->success(lang('get_success'), '', $data);
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
	 * 搜索表单集成处理方法
	 * @access protected
	 * @param array $data
	 */
	protected function _search_from($data = []) {
		$this->success(lang('get_success'), '', array_merge(['lang' => $this->_lang()], $data));
	}
}
