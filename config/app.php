<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

return [
	/* 应用设置 */
	'app_name' => '', // 应用名称 *
	'app_host' => '', // 应用地址 *
	'app_debug' => true, // 应用调试模式
	'app_trace' => false, // 应用Trace
	'app_multi_module' => true, // 是否支持多模块
	'auto_bind_module' => false, // 入口自动绑定模块
	'root_namespace' => [], // 注册的根命名空间
	'default_return_type' => 'html', // 默认输出类型
	'default_ajax_return' => 'json', // 默认AJAX 数据返回格式,可选json xml ...
	'default_jsonp_handler' => 'jsonpReturn', // 默认JSONP格式返回的处理方法
	'var_jsonp_handler' => 'callback', // 默认JSONP处理方法
	'default_timezone' => 'PRC', // 默认时区
	'lang_switch_on' => true, // 是否开启多语言
	'default_filter' => 'htmlspecialchars', // 默认全局过滤方法 用逗号分隔多个
	'default_lang' => 'zh-cn', // 默认语言
	'class_suffix' => false, // 应用类库后缀
	'controller_suffix' => false, // 控制器类后缀

	/* 模块设置 */
	'default_module' => 'home', // 默认模块名
	'deny_module_list' => ['common', 'lang'], // 禁止访问模块
	'default_controller' => 'Index', // 默认控制器名
	'default_action' => 'index', // 默认操作名
	'default_validate' => '', // 默认验证器
	'empty_module' => '', // 默认的空模块名
	'empty_controller' => 'Error', // 默认的空控制器名
	'use_action_prefix' => false, // 操作方法前缀 *
	'action_suffix' => '', // 操作方法后缀
	'controller_auto_search' => false, // 自动搜索控制器
	'admin_module' => ['admin'], // 后台模块
	'api_module' => ['api'], // 接口模块

	/* URL设置 */
	'var_pathinfo' => 's', // PATHINFO变量名 用于兼容模式
	'pathinfo_fetch' => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'], // 兼容PATH_INFO获取
	'pathinfo_depr' => '/', // pathinfo分隔符
	'https_agent_name' => '', // HTTPS代理标识 *
	'http_agent_ip' => 'X-REAL-IP', // IP代理获取标识 *
	'url_html_suffix' => 'html', // URL伪静态后缀
	'url_common_param' => false, // URL普通方式参数 用于自动生成
	'url_param_type' => 0, // URL参数方式 0 按名称成对解析 1 按顺序解析
	'url_lazy_route' => false, // 是否开启路由延迟解析 *
	'url_route_must' => false, // 是否强制使用路由 *
	'route_rule_merge' => false, // 合并路由规则
	'route_complete_match' => false, // 路由是否完全匹配
	'route_annotation' => false, // 使用注解路由
	'url_domain_root' => '', // 域名根，如thinkphp.cn
	'url_convert' => true, 	// 是否自动转换URL中的控制器和操作名
	'url_controller_layer' => 'controller', // 默认的访问控制器层
	'var_method' => '_method', // 表单请求类型伪装变量
	'var_ajax' => '_ajax', // 表单ajax伪装变量
	'var_pjax' => '_pjax', // 表单pjax伪装变量
	'request_cache' => false, // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
	'request_cache_expire' => null, // 请求缓存有效期
	'request_cache_except' => [], // 全局请求缓存排除规则
	'route_check_cache' => false, // 是否开启路由缓存
	'route_check_cache_key' => '', // 路由缓存的Key自定义设置（闭包），默认为当前URL和请求类型的md5
	'dispatch_success_tmpl' => Env::get('think_path') . 'tpl/dispatch_jump.tpl', // 默认跳转页面对应的模板文件
	'dispatch_error_tmpl' => Env::get('think_path') . 'tpl/dispatch_jump.tpl',
	'exception_tmpl' => Env::get('think_path') . 'tpl/think_exception.tpl', // 异常页面的模板文件
	'error_message' => '页面错误！请稍后再试～', // 错误显示信息,非调试模式有效
	'show_error_msg' => false, // 显示错误信息
	'exception_handle' => '', // 异常处理handle类 留空使用 \think\exception\Handle
];
