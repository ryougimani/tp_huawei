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
use think\Db;
use service\ToolsService;
use service\DataService;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * 前台权限基础控制器
 * Class BasicAdmin
 * @package controller
 */
class BasicHome extends Controller {

	protected $table;
	protected $lang_range = 'zh-cn';

	protected function initialize() {
		$this->lang_range = $this->app['lang']->detect();
	}

	/**
	 * 获取所有频道
	 * @access protected
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _channels() {
		$channels = cache("channels_{$this->lang_range}");
		if (empty($channels)) {
			$channels = ['pc_head_channel' => [], 'pc_top_channel' => [], 'pc_foot_channel' => [], 'mobile_channel' => []];
			foreach (Db::name('Channel')->where('status', 1)->where('is_deleted', 0)->where('', 'EXP', 'FIND_IN_SET(' . NETWORK . ', network_segment)')->order(['sort' => 'asc', 'id' => 'asc'])->select() as $val) {
				$position = explode(',', $val['position']);
				in_array('1', $position) && $channels['pc_head_channel'][] = $val;
				in_array('2', $position) && $channels['pc_top_channel'][] = $val;
				in_array('3', $position) && $channels['pc_foot_channel'][] = $val;
				in_array('4', $position) && $channels['mobile_channel'][] = $val;
				in_array('0', $position) && $channels['not_show_channel'][] = $val;
			}
			foreach ($channels as &$val) {
				$val = ToolsService::listToTree($val);
			}
			cache("channels_{$this->lang_range}", $channels);
		}
		return $channels;
	}


	protected function _control_url() {
		$channel = $this->request->param('channel', []);
		if (!empty($channel)) {
			if (isset($channel['type']) && $channel['type'] !== 3) {
				return empty($channel['byname']) ? $channel['pinyin'] : $channel['byname'];
			}
		}
		return '/';
	}

	protected function _getIp(){
		return $this->request->ip();
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
		foreach ([$method, "_" . $this->request->action() . "{$method}"] as $_method) {
			if (method_exists($this, $_method) && false === $this->$_method($data, $otherData))
				return false;
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
		$limit = $this->request->param('limit', cookie('home-limit'));
		cookie('home-limit', $limit >= 10 ? $limit : 20);
		// 使用paginate查询分页
		$query = $this->request->get();
		$page = $db->paginate($limit, $total, ['query' => $query]);
		// 判断是否有数据
		if (($totalNum = $page->total()) > 0) {
			list($result['list'], $result['total'], $result['page']) = [$page->all(), $totalNum, $page->render()];
		} else {
			list($result['list'], $result['total']) = [$page->all(), $totalNum];
		}
		return $result;
	}

	/**
	 * 列表集成处理方法
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param string $template 模板
	 * @param bool $isPage 是启用分页
	 * @param bool $isDisplay 是否直接输出显示
	 * @param bool $total 总记录数
	 * @param array $result 返回内容
	 * @return array|mixed|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _list($dbQuery = null, $template='', $isPage = true, $isDisplay = true, $total = false, $result = []) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		$fields = $db->getTableFields();
		// 获取列表的排序条件
		if (null === $db->getOptions('order')) {
			$order = [];
			in_array('is_top', $fields) && $order = array_merge(['is_top' => 'desc'], $order);
			in_array('sort', $fields) && $order = array_merge(['sort' => 'asc'], $order);
			in_array('id', $fields) && $order = array_merge(['id' => 'desc'], $order);
			$db->order($order);
		}
		// 获取列表的查询条件
		in_array('is_deleted', $fields) && $db->where('is_deleted', 0);
		// 是否分页显示
		if ($isPage) {
			$result = $this->_page($db, $total);
		} else {
			$result = ['list' => $db->select(), 'total' => false];
		}
		// 列表数据处理
		if (false !== $this->_callback('_data_filter', $result['list'], []) && $isDisplay) {
			!empty($this->title) && $this->assign('title', $this->title);
			$this->assign('channels', $this->_channels());
			$this->assign('control_url', $this->_control_url());
			$this->assign('channelInfo',$this->request->param('channel'));
			return view($template, $result);
		}
		return $result;
	}

	/**
	 * 详情集成处理方法
	 * @access protected
	 * @param null $dbQuery
	 * @param string $pkField
	 * @param int $pkValue
	 * @param array $where
	 * @param array $extendData
	 * @return array|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _details($dbQuery = null, $pkField = '', $pkValue = null, $where = [], $extendData = []) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		empty($pkField) && $pkField = ($db->getPk() ? $db->getPk() : 'id');
		empty($pkValue) && $pkValue = $this->request->request($pkField, isset($where[$pkField]) ? $where[$pkField] : (isset($extendData[$pkField]) ? $extendData[$pkField] : null));
		$data = ($pkValue !== null) ? array_merge((array)$db->where($pkField, $pkValue)->where($where)->find(), $extendData) : $extendData;
		if (false !== $this->_callback('_details_filter', $data, [])) {
			$this->assign('channels', $this->_channels());
			$this->assign('control_url', $this->_control_url());
			$this->assign('channelInfo',$this->request->param('channel'));
			empty($this->title) || $this->assign('title', $this->title);
			return view('', ['data' => $data]);
		}
		return $data;
	}

	/**
	 * 表单默认操作
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param string $template 模板
	 * @param string $pkField 主键
	 * @param array $where 查询规则
	 * @param array $extendData 扩展数据
	 * @return mixed
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form($dbQuery = null, $template = 'form', $pkField = '', $where = [], $extendData = []) {
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		empty($pkField) && $pkField = ($db->getPk() ? $db->getPk() : 'id');
		empty($pkValue) && $pkValue = $this->request->request($pkField, isset($where[$pkField]) ? $where[$pkField] : (isset($extendData[$pkField]) ? $extendData[$pkField] : null));
		// 非POST请求, 获取数据并显示表单页面
		if (!$this->request->isPost()) {
			$data = ($pkValue !== null) ? array_merge((array)$db->where($pkField, $pkValue)->where($where)->find(), $extendData) : $extendData;
			if (false !== $this->_callback('_form_filter', $data,[])) {
				$this->assign('channels', $this->_channels());
				empty($this->title) || $this->assign('title', $this->title);

				return $this->fetch($template, ['data' => $data]);
			}
			return $data;
		}
		// POST请求, 数据自动存库
		$data = array_merge($this->request->post(), $extendData);
		if (false !== $this->_callback('_form_filter', $data,[])) {
			$result = DataService::save($db, $data, $pkField, $where);
			if (false !== $this->_callback('_form_result', $data, $result)) {
				if ($result !== false) {
					$this->success('恭喜, 数据保存成功!', '');
				}
				$this->error('数据保存失败, 请稍候再试!');
			}
		}
	}

	/**
	 * 生成下拉框内容
	 * @access protected
	 * @param \think\db\Query|string $dbQuery 数据库查询对象
	 * @param bool $tree 是否树形
	 * @param string $firstValue 首行值
	 * @param string $key 键
	 * @param string $pk 主键
	 * @param string $ppk 父主键
	 * @return array|\PDOStatement|string|\think\Collection
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	protected function _form_select($dbQuery = null, $tree = false, $firstValue = '', $key = 'name', $pk = 'id', $ppk = 'pid') {
		empty($firstValue) && $firstValue = lang('class_placeholder');
		$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
		// 获取表字段
		$fields = $db->getTableFields();
		// 排序
		if (null === $db->getOptions('order')) {
			in_array('sort', $fields) && $db->order(['sort' => 'asc']);
		}
		// 条件
		if (null === $db->getOptions('where') ) {
			in_array('status', $fields) && $db->where('status', '1');
			in_array('is_deleted', $fields) && $db->where('is_deleted', '0');
		}
		$data = $db->select();
		if ($tree) {
			foreach ($data as &$val) {
				$val['ids'] = join(',', ToolsService::getListSubId($data, $val['id']));
			}
			$data[] = [$key => $firstValue, $pk => 0, $ppk => -1, 'ids' => ''];
			$data = ToolsService::listToTable($data, -1, $pk, $ppk);
		} else {
			array_unshift($data, [$key => $firstValue, $pk => '']);
		}
		return $data;
	}

	public function download() {
		$file = base64_decode($this->request->param('file'));
		$name = base64_decode($this->request->param('name'));
		if (empty($file)) {
			$this->error("下载地址不存在");
		}
		if (!file_exists(app('env')->get('root_path') . $file)) {
			$this->error("该文件不存在，可能是被删除");
		}
		header("Content-type: application/octet-stream");
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header("Content-Length: " . filesize($file));
		readfile($file);
	}

	/**
	 * @param null $dbQuery
	 * @param array $fields
	 * @return array|\think\response\View
	 * @throws \PhpOffice\PhpSpreadsheet\Exception
	 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
	 * @throws \think\Exception
	 * @throws \think\exception\PDOException
	 */
	protected function _import($dbQuery = null, $fields = []) {
		if ($this->request->isPost()) {
			$post = $this->request->post();
			//dump($post); exit;
			// 判断文件是否存在
			empty($post['import']) && $this->error(lang('import_required'));
			$file_name = env('root_path') . $post['import'];
			!file_exists($file_name) && $this->error(lang('import_file_not_exist'));
			// 文件读取
			$reader = IOFactory::createReader('Xls');
			$excel = $reader->load($file_name); // 载入excel文件
			$sheet = $excel->getSheet(0); // 读取第一個工作表
			$highestRow = $sheet->getHighestRow(); // 取得总行数
			$highestRow < 2 && $this->error(lang('import_file_not_content'));
			//$highestColumn = $sheet->getHighestColumn(); // 取得总列数
			// 获取数据
			$data = [];
			for ($row = 2; $row <= $highestRow; $row++) {
				foreach ($fields as $column => $field) {
					if (empty(trim($sheet->getCell($column . $row)->getValue())) == false) {
						$data[$row][$field] = trim($sheet->getCell($column . $row)->getValue());
					}
				}
			}
			$db = is_null($dbQuery) ? Db::name($this->table) : (is_string($dbQuery) ? Db::name($dbQuery) : $dbQuery);
			if (false !== $this->_callback('_import_filter', $data, [])) {
				$db->startTrans();
				foreach ($data as $val) {
					$result = DataService::save($db, $val);
					if ($result === false) {
						$db->rollback();
						if (false !== $this->_callback('_import_result', $data, $result)) {
							$this->error('导入失败！');
						}
					}
				}
				$db->commit();
				if (false !== $this->_callback('_import_result', $post, true)) {
					$this->success('导入成功！', '');
				}
			}
		} else {
			$data = [];
			if (false !== $this->_callback('_import_filter', $data, [])) {
				$this->assign('channels', $this->_channels());
				empty($this->title) || $this->assign('title', $this->title);
				return view('', ['data' => $data]);
			}
			return $data;
		}
	}

}