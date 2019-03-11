<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\home\controller;

use controller\BasicHome;
use think\Db;
use service\ToolsService;

/**
 * 文章管理
 * Class Article
 * @package app\home\controller
 */
class Article extends BasicHome {

	protected $table = 'Article';

	/**
	 * 文章列表
	 * @access public
	 * @return array|mixed|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {
		$class_id = $this->request->param('class_id', 0);
		$db = Db::name($this->table)
			->order(['is_top' => 'desc', 'sort' => 'asc', 'publish_time' => 'desc', 'id' => 'desc'])
			->where('', 'EXP', 'FIND_IN_SET(' . NETWORK . ', network_segment)')
			->where('status', 1)
			->where('audit', 1)
			->where('is_draft', 0);
		if (!empty($class_id)) {
			$classes = Db::name($this->table . 'Class')->where('status', 1)->where('is_deleted', 0)->select();
			$db->where('class_id', 'in', ToolsService::getListSubId($classes, $class_id));
		}
		$show_type = Db::name($this->table.'Class')->where('id',$class_id)->value('show_type');
		$template = $show_type == '1' ? 'photo' : '';
		return parent::_list($db,$template);
	}

	/**
	 * 列表数据处理
	 * @access protected
	 * @param array $data
	 */
	protected function _index_data_filter(&$data) {

	}

	/**
	 * 文章详情
	 * @access public
	 * @param int $id
	 * @return array|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function details($id = 0) {
		$db = Db::name($this->table)->where('', 'EXP', 'FIND_IN_SET(' . NETWORK . ', network_segment)');
		Db::name($this->table)->where('id', $id)->setInc('view_num', 1);
		return parent::_details($db, 'id', $id);
	}

	/**
	 * 详情数据处理
	 * @access protected
	 * @param $data
	 */
	protected function _details_filter(&$data){
		$this->assign('pinyin',Db::name('Channel')->where(['is_deleted'=>0,'model_name'=>'Article','model_class_id'=>$data['class_id']])->value('pinyin'));
		$this->assign('next',Db::name($this->table)->where('class_id',$data['class_id'])->where('id','>',$data['id'])->where('status', 1)->where('audit', 1)->where('is_draft', 0)->order(['id' => 'asc'])->find());
		$this->assign('prev',Db::name($this->table)->where('class_id',$data['class_id'])->where('id','<',$data['id'])->where('status', 1)->where('audit', 1)->where('is_draft', 0)->order(['id' => 'desc'])->find());
//		$img_url = app('config')->get('template.tpl_replace_string.__IMG_URL__');
//		$data['content'] = preg_replace("/\/upload\//",$img_url . "/upload/", $data['content']);
	}

	/**
	 * 搜索页面
	 * @return array|mixed|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function search(){
		$get = $this->request->get();
		$db = Db::name($this->table)
			->order(['is_top' => 'desc', 'publish_time' => 'desc', 'sort' => 'asc', 'id' => 'desc'])
			->where('', 'EXP', 'FIND_IN_SET(' . NETWORK . ', network_segment)')
			->where('status', 1)
			->where('audit', 1)
			->where('is_draft', 0);
		isset($get['key']) && !empty($get['key']) && $db->whereLike('title|content','%'.$get['key'].'%');
		$this->assign('key',$get['key']);
		return parent::_list($db);
	}

	protected function _search_data_filter(&$data){
		$class_ids = array_column($data,'class_id');
		$channelInfo = Db::name('Channel')->where('model_name',$this->table)->whereIn('model_class_id',$class_ids)->column('pinyin,name','model_class_id');
		foreach($data as $key => &$val){
			$data[$key]['pinyin'] = isset($channelInfo[$val['class_id']]['pinyin']) ? $channelInfo[$val['class_id']]['pinyin'] : null;
			$data[$key]['class_name'] = isset($channelInfo[$val['class_id']]['name']) ? $channelInfo[$val['class_id']]['name'] : null;
		}
	}

}
