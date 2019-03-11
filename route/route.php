<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

use think\facade\Route;

return [
	'/admin/config' => '@admin/index/config', // 系统参数
	'/admin/menu' => '@admin/index/menu', // 后台菜单
	'/admin/lang' => '@admin/index/lang', // 语言包
	'/admin/session' => '@admin/index/session', // session信息
	'/admin/message_num' => '@admin/index/message_num', // 提示信息数量
];
