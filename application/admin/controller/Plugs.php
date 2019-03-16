<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace app\admin\controller;

use controller\BasicAdmin;
use service\DataService;
use service\FileService;

/**
 * 插件助手控制器
 * Class Plugs
 * @package app\admin\controller
 */
class Plugs extends BasicAdmin {

	/**
	 * 文件上传页面
	 * @access public
	 * @return \think\response\View
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function upload() {
		if ($this->request->isPost()) {
			$file = $this->request->file('file');
			$save_name = $file->move('upload')->getSaveName();
			// 检测图像文件
			if ($file->checkImg()) {

			}
			$info = $file->getInfo();
			$data = [
				'file_name' => $info['name'], // 上传文件名
				'save_path' => '/upload/' . img_replace($save_name), // 保存路径
			];
			$result = DataService::save('Upload', $data);
			if ($result !== false) {
				$this->lay_success('', ['src' => $data['save_path']]);
			}
		}
		return json(['code' => 'ERROR']);
	}

	/**
	 * 文件上传处理
	 * @access public
	 * @return \think\response\Json
	 * @throws \think\Exception
	 */
	public function upload_file() {



//		if ($this->request->isPost()) {
//			$post = $this->request->post();
//			// 路径处理
//			$dir = substr($post['key'], 0, strripos($post['key'],DIRECTORY_SEPARATOR));
//			if (($info = $this->request->file('file')->move('upload' . $dir, $post['md5'], true))) {
//				$filename = $post['key'];
//				$site_url = FileService::getFileUrl($filename, 'local');
//				if ($site_url) {
//					return json(['data' => ['site_url' => $site_url], 'code' => 'SUCCESS']);
//				}
//			}
//		}
//		return json(['code' => 'ERROR']);
	}

	/**
	 * 上传状态（文件是否已经上传过）
	 * @access public
	 */
	public function upload_state() {
		$post = $this->request->post();
		// 获取文件路径与名称
		$dir = $post['model'] . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR;
		$filename = $dir . $post['md5'] . '.' . pathinfo($post['filename'], PATHINFO_EXTENSION);
		// 检查文件是否已上传
		if (($site_url = FileService::getFileUrl($filename))) {
			$this->result(['site_url' => $site_url], 'IS_FOUND');
		}
		// 需要上传文件，生成上传配置参数
		$config = ['up_type' => $post['up_type'], 'file_url' => $filename, 'file_dir' => $dir];
		switch (strtolower($post['up_type'])) {
			case 'local':
				$config['server'] = FileService::getUploadLocalUrl();
				break;
			case 'qiniu':
				$config['server'] = FileService::getUploadQiniuUrl(true);
				$config['token'] = $this->_getQiniuToken($filename);
				break;
			case 'oss':
				$time = time() + 3600;
				$policyText = [
					'expiration' => date('Y-m-d', $time) . 'T' . date('H:i:s', $time) . '.000Z',
					'conditions' => [
						['content-length-range', 0, 1048576000]
					]
				];
				$config['policy'] = base64_encode(json_encode($policyText));
				$config['server'] = FileService::getUploadOssUrl();
				$config['site_url'] = FileService::getBaseUrlOss() . $filename;
				$config['signature'] = base64_encode(hash_hmac('sha1', $config['policy'], system_config('storage_oss_secret'), true));
				$config['OSSAccessKeyId'] = system_config('storage_oss_keyid');
		}
		$this->result($config, 'NOT_FOUND');
	}

	/**
	 * 生成七牛文件上传Token
	 * @access protected
	 * @param string $key
	 * @return string
	 */
	protected function _getQiniuToken($key) {
		$accessKey = system_config('storage_qiniu_access_key');
		$secretKey = system_config('storage_qiniu_secret_key');
		$bucket = system_config('storage_qiniu_bucket');
		$host = system_config('storage_qiniu_domain');
		$protocol = system_config('storage_qiniu_is_https') ? 'https' : 'http';
		$params = [
			'scope' => "{$bucket}:{$key}",
			'deadline' => 3600 + time(),
			'returnBody' => "{\"data\":{\"site_url\":\"{$protocol}://{$host}/$(key)\",\"file_url\":\"$(key)\"}, \"code\": \"SUCCESS\"}",
		];
		$data = str_replace(['+', '/'], ['-', '_'], base64_encode(json_encode($params)));
		return $accessKey . ':' . str_replace(['+', '/'], ['-', '_'], base64_encode(hash_hmac('sha1', $data, $secretKey, true))) . ':' . $data;
	}

	/**
	 * 字体图标
	 * @access public
	 * @return \think\response\View
	 */
	public function icon() {
		$this->assign('field', $this->request->get('field', 'icon'));
		return view();
	}
}
