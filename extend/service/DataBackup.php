<?php
// +----------------------------------------------------------------------
// | Think_firdot
// +----------------------------------------------------------------------
// | 版权所有 2008~2017 上海泛多网络技术有限公司 [ http://www.firdot.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.firdot.com
// +----------------------------------------------------------------------

namespace service;

use think\Db;

/**
 * 数据备份服务
 * Class DataService
 * @package service
 */
class DataBackup {

	private $fp; // 文件指针
	private $file; // 备份文件信息 part - 卷号，name - 文件名
	private $size = 0; // 当前打开文件大小
	private $config; // 备份配置

	/**
	 * 数据库备份构造方法
	 * @access public
	 * @param array $file 备份或还原的文件信息
	 * @param array $config 备份配置信息
	 * @param string $type
	 */
	public function __construct($file, $config, $type = 'export') {
		$this->file = $file;
		$this->config = $config;
	}

	/**
	 * 打开一个卷，用于写入数据
	 * @access private
	 * @param int $size 写入数据的大小
	 */
	private function open($size) {
		if ($this->fp) {
			$this->size += $size;
			if ($this->size > $this->config['part']) {
				$this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
				$this->fp = null;
				$this->file['part']++;
				session('backup.file', $this->file);
				$this->create();
			}
		} else {
			$backupPath = $this->config['path'];
			$filename = "{$backupPath}{$this->file['name']}-{$this->file['part']}.sql";
			if ($this->config['compress']) {
				$filename = "{$filename}.gz";
				$this->fp = @gzopen($filename, "a{$this->config['level']}");
			} else {
				$this->fp = @fopen($filename, 'a');
			}
			$this->size = filesize($filename) + $size;
		}
	}

	/**
	 * 写入SQL语句
	 * @access private
	 * @param string $sql 要写入的SQL语句
	 * @return bool|int
	 */
	private function write($sql) {
		$size = strlen($sql);
		// 由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
		// 一般情况压缩率都会高于50%；
		$size = $this->config['compress'] ? $size / 2 : $size;
		$this->open($size);
		return $this->config['compress'] ? @gzwrite($this->fp, $sql) : @fwrite($this->fp, $sql);
	}

	/**
	 * 写入初始数据
	 * @access public
	 * @return mixed
	 */
	public function create() {
		$sql = "-- -----------------------------\n";
		$sql .= "-- Think MySQL Data Transfer \n";
		$sql .= "-- \n";
		$sql .= "-- Host     : " . config('DB_HOST') . "\n";
		$sql .= "-- Port     : " . config('DB_PORT') . "\n";
		$sql .= "-- Database : " . config('DB_NAME') . "\n";
		$sql .= "-- \n";
		$sql .= "-- Part : #{$this->file['part']}\n";
		$sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
		$sql .= "-- -----------------------------\n\n";
		$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
		return $this->write($sql);
	}

	/**
	 * 备份表结构
	 * @access public
	 * @param string $table 表名
	 * @param int $start 起始行数
	 * @return array|bool|int
	 */
	public function backup($table, $start) {
		//备份表结构
		if (0 == $start) {
			$result = Db::query("SHOW CREATE TABLE `{$table}`");
			$sql = "\n";
			$sql .= "-- -----------------------------\n";
			$sql .= "-- Table structure for `{$table}`\n";
			$sql .= "-- -----------------------------\n";
			$sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
			$sql .= trim($result[0]['Create Table']) . ";\n\n";
			if (false === $this->write($sql)) {
				return false;
			}
		}

		//数据总数
		$result = Db::query("SELECT COUNT(*) AS count FROM `{$table}`");
		$count = $result['0']['count'];

		//备份表数据
		if ($count) {
			//写入数据注释
			if (0 == $start) {
				$sql = "-- -----------------------------\n";
				$sql .= "-- Records of `{$table}`\n";
				$sql .= "-- -----------------------------\n";
				$this->write($sql);
			}

			//备份数据记录
			$result = Db::query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
			foreach ($result as $row) {
				$row = array_map('addslashes', $row);
				$sql = "INSERT INTO `{$table}` VALUES ('" . str_replace(array("\r", "\n"), array('\r', '\n'), implode("', '", $row)) . "');\n";
				if (false === $this->write($sql)) {
					return false;
				}
			}

			//还有更多数据
			if ($count > $start + 1000) {
				return array($start + 1000, $count);
			}
		}

		//备份下一表
		return 0;
	}

	/**
	 *
	 * @access public
	 * @param $start
	 * @return array|bool|int
	 */
	public function import($start) {
		//还原数据
		if ($this->config['compress']) {
			$gz = gzopen($this->file[1], 'r');
			$size = 0;
		} else {
			$size = filesize($this->file[1]);
			$gz = fopen($this->file[1], 'r');
		}

		$sql = '';
		if ($start) {
			$this->config['compress'] ? gzseek($gz, $start) : fseek($gz, $start);
		}

		for ($i = 0; $i < 1000; $i++) {
			$sql .= $this->config['compress'] ? gzgets($gz) : fgets($gz);
			if (preg_match('/.*;$/', trim($sql))) {
				if (false !== Db::execute($sql)) {
					$start += strlen($sql);
				} else {
					return false;
				}
				$sql = '';
			} elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
				return 0;
			}
		}

		return array($start, $size);
	}

	/**
	 * 析构方法，用于关闭文件资源
	 * @access public
	 */
	public function __destruct() {
		$this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
	}

	/**
	 * 解析
	 * @access public
	 * @param mixed $filename
	 * @return mixed
	 */
	public static function parseSql($filename) {
		$lines = file($filename);
		$lines[0] = str_replace(chr(239) . chr(187) . chr(191), '', $lines[0]); // 去除BOM头
		$flag = true;
		$sql_arr = [];
		$sql = '';
		foreach ($lines as $line) {
			$line = trim($line);
			$char = substr($line, 0, 1);
			if ($char != '#' && strlen($line) > 0) {
				$prefix = substr($line, 0, 2);
				switch ($prefix) {
					case '/*':
						$flag = (substr($line, -3) == '*/;' || substr($line, -2) == '*/') ? true : false;
						break 1;
					case '--':
						break 1;
					default :
						if ($flag) {
							$sql .= $line;
							if (substr($line, -1) == ";") {
								$sql_arr[] = $sql;
								$sql = "";
							}
						}
						if (!$flag) $flag = (substr($line, -3) == '*/;' || substr($line, -2) == '*/') ? true : false;
				}
			}
		}
		return $sql_arr;
	}

	/**
	 * 安装
	 * @access public
	 * @param mixed $sql_arr
	 * @return mixed
	 */
	public static function install($sql_arr) {
		$flag = true;
		if (is_array($sql_arr)) {
			foreach ($sql_arr as $sql) {
				if (Db::execute($sql) === false) {
					$flag = false;
				}
			}
		}
		return $flag;
	}
}
