<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | author: ryougimani <ryougimani@qq.com>
// +----------------------------------------------------------------------

namespace service;

use Exception;
use OSS\OssClient;
use OSS\Core\OssException;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\facade\Log;

/**
 * 文件存储服务
 * Class FileService
 * @package service
 */
class FileService {

	/**
	 * 根据文件后缀获取文件MINE
	 * @access public
	 * @param array|string $ext 文件后缀
	 * @param array $mine 文件后缀MINE信息
	 * @return string
	 */
	public static function getFileMine($ext, $mine = []) {
		$mines = app('config')->get('mines');
		foreach (is_string($ext) ? explode(',', $ext) : $ext as $_ext) {
			if (isset($mines[strtolower($_ext)])) {
				$_extInfo = $mines[strtolower($_ext)];
				$mine[] = is_array($_extInfo) ? join(',', $_extInfo) : $_extInfo;
			}
		}
		return join(',', $mine);
//		$mines = self::getMines();
//		foreach (is_string($ext) ? explode(',', $ext) : $ext as $e) {
//			$mine[] = isset($mines[strtolower($e)]) ? $mines[strtolower($e)] : 'application/octet-stream';
//		}
//		return join(',', array_unique($mine));
	}

	/**
	 * 获取所有文件扩展的mine
	 * @return mixed
	 */
	public static function getMines()
	{
		$mines = cache('all_ext_mine');
		if (empty($mines)) {
			$content = file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');
			preg_match_all('#^([^\s]{2,}?)\s+(.+?)$#ism', $content, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				foreach (explode(" ", $match[2]) as $ext) {
					$mines[$ext] = $match[1];
				}
			}
			cache('all_ext_mine', $mines);
		}
		return $mines;
	}

	/**
	 * 获取文件当前URL地址
	 * @access public
	 * @param string $filename 文件名称
	 * @param string|null $storage 存储类型
	 * @return bool|string
	 */
	public static function getFileUrl($filename, $storage = null) {
		if (self::hasFile($filename, $storage) === false) {
			return false;
		}
		switch (empty($storage) ? system_config('storage_type') : $storage) {
			case 'local':
				return self::getBaseUrlLocal() . $filename;
			case 'qiniu':
				return self::getBaseUrlQiniu() . $filename;
			case 'oss':
				return self::getBaseUrlOss() . $filename;
		}
		throw new \think\Exception('未设置存储方式，无法获取到文件对应URL地址');
		return false;
	}

	/**
	 * 检查文件是否已经存在
	 * @access public
	 * @param string $filename 文件名称
	 * @param string|null $storage 存储类型
	 * @return bool
	 */
	public static function hasFile($filename, $storage = null) {
		switch (empty($storage) ? system_config('storage_type') : $storage) {
			case 'local':
				return file_exists(env('root_path') . 'upload' . DIRECTORY_SEPARATOR . $filename);
			case 'qiniu':
				$auth = new Auth(system_config('storage_qiniu_access_key'), system_config('storage_qiniu_secret_key'));
				$bucketMgr = new BucketManager($auth);
				list($ret, $err) = $bucketMgr->stat(system_config('storage_qiniu_bucket'), $filename);
				return $err === null;
			case 'oss':
				$ossClient = new OssClient(system_config('storage_oss_keyid'), system_config('storage_oss_secret'), self::getBaseUrlOss(), true);
				return $ossClient->doesObjectExist(system_config('storage_oss_bucket'), $filename);
		}
		return false;
	}

	/**
	 * 获取服务器URL前缀
	 * @access public
	 * @return string
	 */
	public static function getBaseUrlLocal() {
//		$root = request()->root(true);
//		$rootUrl = preg_match('/\.php$/', $root) ? dirname($root) : $root;
		$rootUrl = request()->rootUrl();
		return $rootUrl . DIRECTORY_SEPARATOR . 'upload';
	}

	/**
	 * 获取七牛云URL前缀
	 * @access public
	 * @return string
	 */
	public static function getBaseUrlQiniu() {
		switch (strtolower(system_config('storage_qiniu_is_https'))) {
			case 'https':
				return 'https://' . system_config('storage_qiniu_domain') . '/';
			case 'http':
				return 'http://' . system_config('storage_qiniu_domain') . '/';
			case 'auto':
				return '//' . system_config('storage_qiniu_domain') . '/';
			default:
				throw new \think\Exception('未设置七牛云文件地址协议');
		}

		return (system_config('storage_qiniu_is_https') ? 'https' : 'http') . '://' . system_config('storage_qiniu_domain') . '/';
	}

	/**
	 * 获取AliOss URL前缀
	 * @access public
	 * @return string
	 */
	public static function getBaseUrlOss() {
		return (system_config('storage_oss_is_https') ? 'https' : 'http') . '://' . system_config('storage_oss_domain') . '/';
	}

	/**
	 * 根据配置获取到本地文件上传目标地址
	 * @access public
	 * @return string
	 */
	public static function getUploadLocalUrl() {
		if (in_array(app('request')->module(), app('config')->get('admin_module'))) {
			return url('@admin/plugs/upload_file');
		} else {
			return url('@home/plugs/upload_file');
		}
	}

	/**
	 * 根据配置获取到七牛云文件上传目标地址
	 * @access public
	 * @param bool $isClient
	 * @return string
	 */
	public static function getUploadQiniuUrl($isClient = true) {
		$region = system_config('storage_qiniu_region');
		$isHttps = !!system_config('storage_qiniu_is_https');
		switch ($region) {
			case '华东':
				if ($isHttps)
					return $isClient ? 'https://upload.qbox.me' : 'https://up.qbox.me';
				return $isClient ? 'http://upload.qiniu.com' : 'http://up.qiniu.com';
			case '华北':
				if ($isHttps)
					return $isClient ? 'https://upload-z1.qbox.me' : 'https://up-z1.qbox.me';
				return $isClient ? 'http://upload-z1.qiniu.com' : 'http://up-z1.qiniu.com';
			case '北美':
				if ($isHttps)
					return $isClient ? 'https://upload-na0.qbox.me' : 'https://up-na0.qbox.me';
				return $isClient ? 'http://upload-na0.qiniu.com' : 'http://up-na0.qiniu.com';
			case '华南':
			default:
				if ($isHttps)
					return $isClient ? 'https://upload-z2.qbox.me' : 'https://up-z2.qbox.me';
				return $isClient ? 'http://upload-z2.qiniu.com' : 'http://up-z2.qiniu.com';
		}
	}

	/**
	 * 获取AliOSS上传地址
	 * @access public
	 * @return string
	 */
	public static function getUploadOssUrl() {
		return (request()->isSsl() ? 'https' : 'http') . '://' . system_config('storage_oss_domain');
	}

	/**
	 * 获取文件相对名称
	 * @access public
	 * @param string $source 文件名称
	 * @param string $ext 文件后缀
	 * @param string $pre 文件前缀
	 * @return string
	 */
	public static function getFileName($source, $ext = '', $pre = '') {
		return $pre . DS . $source . '.' . $ext;
	}

	/**
	 * 根据Key读取文件内容
	 * @access public
	 * @param string $filename 文件名称
	 * @param string|null $storage 存储类型
	 * @return string|null
	 */
	public static function readFile($filename, $storage = null) {
		switch (empty($storage) ? system_config('storage_type') : $storage) {
			case 'local':
				$filepath = ROOT_PATH . 'static/upload/' . $filename;
				if (file_exists($filepath)) {
					return file_get_contents($filepath);
				}
			case 'qiniu':
				$auth = new Auth(system_config('storage_qiniu_access_key'), system_config('storage_qiniu_secret_key'));
				return file_get_contents($auth->privateDownloadUrl(self::getBaseUrlQiniu() . $filename));
			case 'oss':
				$ossClient = new OssClient(system_config('storage_oss_keyid'), system_config('storage_oss_secret'), self::getBaseUrlOss(), true);
				return $ossClient->getObject(system_config('storage_oss_bucket'), $filename);
		}
		Log::error("通过{$storage}读取文件{$filename}的不存在！");
		return null;
	}

	/**
	 * 根据当前配置存储文件
	 * @access public
	 * @param string $filename 文件名称
	 * @param string $content 文件内容
	 * @param string|null $storage 存储类型
	 * @return array|false
	 */
	public static function save($filename, $content, $storage = null) {
		$type = empty($storage) ? system_config('storage_type') : $storage;
		if (!method_exists(__CLASS__, $type)) {
			Log::error("保存存储失败，调用{$type}存储引擎不存在！");
			return false;
		}
		return self::$type($filename, $content);
	}

	/**
	 * 文件储存在本地
	 * @access public
	 * @param string $filename 文件名称
	 * @param string $content 文件内容
	 * @return array|null
	 */
	public static function local($filename, $content) {
		try {
			$filepath = ROOT_PATH . 'static' . DS . 'upload' . DS . $filename;
			!file_exists(dirname($filepath)) && mkdir(dirname($filepath), '0755', true);
			if (file_put_contents($filepath, $content)) {
				$url = pathinfo(request()->baseFile(true), PATHINFO_DIRNAME) . 'static' . DS . 'upload' . DS . $filename;
				return ['file' => $filepath, 'hash' => md5_file($filepath), 'key' => 'static' . DS . 'upload' . DS . $filename, 'url' => $url];
			}
		} catch (Exception $err) {
			Log::error('本地文件存储失败, ' . var_export($err, true));
		}
		return null;
	}

	/**
	 * 七牛云存储
	 * @access public
	 * @param string $filename 文件名称
	 * @param string $content 文件内容
	 * @return array|null
	 */
	public static function qiniu($filename, $content) {
		$auth = new Auth(system_config('storage_qiniu_access_key'), system_config('storage_qiniu_secret_key'));
		$token = $auth->uploadToken(system_config('storage_qiniu_bucket'));
		$uploadMgr = new UploadManager();
		list($result, $err) = $uploadMgr->put($token, $filename, $content);
		if ($err !== null) {
			Log::error('七牛云文件上传失败, ' . var_export($err, true));
			return null;
		}
		$result['file'] = $filename;
		$result['url'] = self::getBaseUrlQiniu() . $filename;
		return $result;
	}

	/**
	 * 阿里云OSS
	 * @access public
	 * @param string $filename 文件名称
	 * @param string $content 文件内容
	 * @return array|null
	 */
	public static function oss($filename, $content) {
		try {
			$ossClient = new OssClient(system_config('storage_oss_keyid'), system_config('storage_oss_secret'), self::getBaseUrlOss(), true);
			$result = $ossClient->putObject(system_config('storage_oss_bucket'), $filename, $content);
			return ['file' => $filename, 'hash' => $result['content-md5'], 'key' => $filename, 'url' => $result['oss-request-url']];
		} catch (OssException $err) {
			Log::error('阿里云OSS文件上传失败, ' . var_export($err, true));
			return null;
		}
	}

	/**
	 * 下载文件到本地
	 * @access public
	 * @param string $url 文件URL地址
	 * @param bool $isForce 是否强制重新下载文件
	 * @return array|null;
	 */
	public static function download($url, $isForce = false) {
		try {
			$filename = self::getFileName($url, strtolower(pathinfo($url, 4)), 'download/');
			if (false === $isForce && ($siteUrl = self::getFileUrl($filename, 'local'))) {
				$realfile = ROOT_PATH . 'static' . DS . 'upload' . DS . $filename;
				return ['file' => $realfile, 'hash' => md5_file($realfile), 'key' => "static/upload/{$filename}", 'url' => $siteUrl];
			}
			return self::local($filename, file_get_contents($url));
		} catch (\Exception $e) {
			Log::error("FileService 文件下载失败 [ {$url} ] . {$e->getMessage()}");
			return false;
		}
	}

}
