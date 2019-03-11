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

/**
 * 前台入口
 * Class Index
 * @package app\home\controller
 */
class Index extends BasicHome {

	/**
	 * 主页
	 * @access public
	 * @return \think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function index() {

		$this->assign('channels', $this->_channels());
		$this->assign('channelInfo',$this->request->param('channel'));
		return view();
	}

	/**
	 * 单页
	 * @access public
	 * @param $channel
	 * @return array|\think\response\View
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	public function single_page($channel) {
		$db = Db::name('Channel');
		return parent::_details($db, 'id', $channel['id']);
	}
}
