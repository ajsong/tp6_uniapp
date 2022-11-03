<?php
/*
Developed by @mario v1.1.20221019
*/

//多语言自动选择
function lang($name, $vars = []) {
	$cookie_var = config('lang.cookie_var');
	$range = cookie('?'.$cookie_var) ? cookie($cookie_var) : '';
	\think\facade\Lang::load(app_path() . 'lang/' . $range . '.php');
	return \think\facade\Lang::get($name, $vars, $range);
}

//修复session漏洞
function session($name = '', $value = '') {
	if (is_null($name)) {
		// 清除
		\think\facade\Session::clear();
	} elseif ('' === $name) {
		return \think\facade\Session::all();
	} elseif (is_null($value)) {
		// 删除
		\think\facade\Session::delete($name);
	} elseif ('' === $value) {
		// 判断或获取
		return 0 === strpos($name, '?') ? \think\facade\Session::has(substr($name, 1)) : \think\facade\Session::get($name);
	} else {
		// 设置
		if (preg_match('/\.php/', $name)) error404();
		if (is_string($value) && preg_match('/<\?(php)?/', $value)) error404();
		\think\facade\Session::set($name, $value);
	}
	return true;
}

//模板拼接字符串用(可变长参数)
function cat(...$strings): string {
	return implode('', $strings);
}

//生成序列号
function generate_sn(): string {
	return date('ymdHis') . rand(10000, 99999);
}

//生成token
function generate_token(): string {
	return md5(md5(rand(100000, 999999)) . time());
	//return md5(uniqid(strval(rand()), true));
}

//生成密码盐值salt
function generate_salt(): int {
	return rand(100000, 999999);
}

//生成指定位数的随机整数
function generate_code($length=4): int {
	return rand(pow(10, ($length-1)), pow(10, $length)-1);
}

//生成md5密码与盐值
function generate_password($password): array {
	if (!$password) error('密码不能为空');
	$salt = generate_salt();
	$password = crypt_password($password, $salt);
	return [$password, $salt];
}

//根据盐值生成加密密码
function crypt_password($password, $salt): string {
	if (!strlen($password) || !strlen($salt)) return '';
	return md5(md5($password).$salt);
}

//上传文件
//filename:(高度平均分数量|filename)(每份高度||filename)(小数!filename百分比压缩), third:上传方法([0|false|空字符串]本地,[1|true|字符串]指定第三方存储,2大文件分割上传到本地)
//detail:返回所有信息(数组), filetype:NULL不限制类型
//上传文件,自动判断是否数组形式
function upload_file($filename='filename', $dir='', $third='', $detail=false, $filetype=['jpg', 'jpeg', 'png', 'gif', 'bmp']) {
	$_filename = $filename;
	if (is_string($filename)) {
		if (preg_match('/^(\d+)(\|{1,2})(\w+)$/', $filename)) {
			preg_match('/^(\d+)(\|{1,2})(\w+)$/', $filename, $matcher);
			$_filename = $matcher[3];
		} else if (preg_match('/^([\d.]+)!(\w+)$/', $filename)) {
			preg_match('/^([\d.]+)!(\w+)$/', $filename, $matcher);
			$_filename = $matcher[2];
		}
	}
	if ($_FILES && isset($_FILES[$_filename]) && is_array($_FILES[$_filename]['name'])) {
		$file = $_FILES[$_filename];
		$name = $file['name'];
		$type = $file['type'];
		$tmp_name = $file['tmp_name'];
		$error = $file['error'];
		$size = $file['size'];
		$files = [];
		for ($i = 0; $i < count($name); $i++) {
			if ($name[$i] != '') {
				$fileObj = [];
				$fileObj[$_filename] = [];
				$fileObj[$_filename]['name'] = $name[$i];
				$fileObj[$_filename]['type'] = $type[$i];
				$fileObj[$_filename]['tmp_name'] = $tmp_name[$i];
				$fileObj[$_filename]['error'] = $error[$i];
				$fileObj[$_filename]['size'] = $size[$i];
				$files[] = upload_adapter($fileObj, $_filename, $dir, $third, $detail, $filetype);
			} else {
				$files[] = $detail ? [] : '';
			}
		}
		return $files;
	} else {
		return upload_adapter($_FILES, $_filename, $dir, $third, $detail, $filetype);
	}
}
//上传适配器
function upload_adapter($fileObj, $filename='filename', $dir='', $third='', $detail=false, $filetype=['jpg', 'jpeg', 'png', 'gif', 'bmp']) {
	$splitNumber = 0; //图片平均分割, 3|filename
	$splitHeight = 0; //图片高度分割, 300||filename
	$compressPercent = 0; //图片百分比压缩, 0.8!filename
	if (is_string($filename)) {
		if (preg_match('/^(\d+)(\|{1,2})(\w+)$/', $filename)) {
			preg_match('/^(\d+)(\|{1,2})(\w+)$/', $filename, $matcher);
			if ($matcher[2] == '|') $splitNumber = min(intval($matcher[1]), 50);
			else $splitHeight = intval($matcher[1]);
			$filename = $matcher[3];
		} else if (preg_match('/^([\d.]+)!(\w+)$/', $filename)) {
			preg_match('/^([\d.]+)!(\w+)$/', $filename, $matcher);
			$compressPercent = floatval($matcher[1]);
			$filename = $matcher[2];
		}
	}
	$is_multiple = false;
	$is_byte = false;
	$filelist = [];
	if (!preg_match('/^\w+$/', $filename)) {
		$is_byte = true;
		if (preg_match('/^http/', $filename)) $filename = download_file($filename);
	}
	for ($one = 0; $one < 1; $one++) { //为了error退出if用
		if ( $is_byte || ($fileObj && isset($fileObj[$filename]) && strlen($fileObj[$filename]['name'])) ) {
			$files = [];
			$fileinfo = [];
			$filedetail = [];
			$fileerror = '';
			$temp = $is_byte ? $filename : $fileObj[$filename];
			$getExt = function($file) use (&$fileerror, $filetype) {
				$upload_file = '';
				if (is_array($file) && isset($file['tmp_name'])) {
					$upload_file = $file['name'];
					$fs = fopen($file['tmp_name'], 'rb');
					$byte = fread($fs, 2);
					fclose($fs);
				} else if (is_string($file)) {
					if (preg_match('/^http/', $file)) {
						$fs = fopen($file, 'rb');
						$byte = fread($fs, 2);
						fclose($fs);
					} else {
						$byte = substr($file, 0, 2);
					}
				} else {
					$byte = 0;
				}
				$info = @unpack('C2chars', $byte);
				$code = intval($info['chars1'].$info['chars2']);
				$ext = '';
				switch ($code) {
					case 6063: //php,xml
					case 7790: //exe,dll
					case 64101:$fileerror = 'FORBIDDEN TO UPLOAD OF ' . implode(',', $filetype) . '!!';break; //bat
					case 7173:$ext = 'gif';break;
					case 255216:$ext = 'jpg';break;
					case 13780:$ext = 'png';break;
					case 6677:$ext = 'bmp';break;
					case 8297:$ext = 'rar';break;
					case 4742:$ext = 'js';break;
					case 5666:$ext = 'psd';break;
					case 10056:$ext = 'torrent';break;
					case 239187:$ext = 'txt';break; //txt,aspx,asp,sql
					case 6033:$ext = 'html';break; //htm,html
					case 208207: //doc,xls,ppt
					case 8075: //docx,xlsx,pptx,zip,mmap
					default:
						if ($code == 208207 && function_exists('finfo_open')) {
							$finfo = @finfo_open(FILEINFO_MIME_TYPE);
							$type = @finfo_file($finfo, $upload_file);
							if ($type) {
								if (strpos($type, 'msword') !== false) $ext = 'doc';
								else if (strpos($type, 'vnd.ms-office') !== false) $ext = 'xls';
								else if (strpos($type, 'powerpoint') !== false) $ext = 'ppt';
							}
						}
						if (!strlen($ext)) {
							if (strlen($upload_file)) {
								$type = pathinfo($upload_file, PATHINFO_EXTENSION);
								if (strlen($type)) $ext = $type;
							} else if (strpos($file, 'http') !== false && strpos($file, '.') !== false) {
								$arr = explode('.', $file);
								$ext = end($arr);
							}
							if (!strlen($ext)) {
								if (is_array($filetype) && count($filetype) == 1) {
									$ext = end($filetype);
								} else if (isset($file['type'])) {
									$arr = explode('/', $file['type']);
									$ext = end($arr);
								}
							}
						}
						break;
				}
				if (!strlen($ext)) $ext = 'tmp';
				return $ext;
			};
			if (input('?upload_third')) $third = input('post.upload_third');
			if (is_bool($third)) {
				if ($third) $third = 1;
				else $third = 0;
			}
			if (is_numeric($third)) {
				$third = intval($third);
				if ($third == 1) $third = 'qiniu';
				else if ($third == 0) $third = '';
			}
			if (is_string($third) && strlen($third)) $third = strtolower($third);
			if ($splitNumber != 0 || $splitHeight != 0 || $compressPercent != 0 || $is_byte) $third = '';
			if ($is_byte) {
				$ext = $getExt($filename);
				if (!strlen($fileerror)) {
					$fileinfo[] = ['name'=>'bytefile', 'tmp_name'=>$filename, 'type'=>$ext, 'error'=>0, 'size'=>strlen($filename), 'ext'=>$ext];
					$info = ['name'=>'bytefile', 'file'=>'', 'type'=>$ext, 'size'=>strlen($filename)];
					$filedetail[] = $info;
					$files = [$info];
				}
			} else if (is_array($temp['name'])) {
				$ext = 'tmp';
				$is_multiple = true;
				for ($i=0; $i<count($temp['name']); $i++) {
					if (!$temp['name'][$i]) continue;
					$files[] = new \think\file\UploadedFile($temp['tmp_name'][$i], $temp['name'][$i], $temp['type'][$i], $temp['error'][$i]);
					$ext = $getExt(['name'=>$temp['name'][$i], 'tmp_name'=>$temp['tmp_name'][$i], 'type'=>$temp['type'][$i]]);
					if (strlen($fileerror)) break;
					$fileinfo[] = ['name'=>$temp['name'][$i], 'tmp_name'=>$temp['tmp_name'][$i], 'type'=>$temp['type'][$i], 'error'=>$temp['error'][$i], 'size'=>$temp['size'][$i], 'ext'=>$ext];
					$info = ['name'=>$temp['name'][$i], 'file'=>'', 'type'=>$ext, 'size'=>$temp['size'][$i]];
					if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
						list($width, $height) = getimagesize($temp['tmp_name'][$i]); //list(width, height, [1:GIF,2:JPG,3:PNG,6:BMP])
						$info['width'] = $width;
						$info['height'] = $height;
					}
					$filedetail[] = $info;
				}
				if (strlen($fileerror)) {
					$filelist = ['error' => $fileerror];
				}
			} else {
				$files = [new \think\file\UploadedFile($temp['tmp_name'], $temp['name'], $temp['type'], $temp['error'])];
				$ext = $getExt($temp);
				if (!strlen($fileerror)) {
					$fileinfo = [['name'=>$temp['name'], 'tmp_name'=>$temp['tmp_name'], 'type'=>$temp['type'], 'error'=>$temp['error'], 'size'=>$temp['size'], 'ext'=>$ext]];
					$info = ['name'=>$temp['name'], 'file'=>'', 'type'=>$ext, 'size'=>$temp['size']];
					if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
						list($width, $height) = getimagesize($temp['tmp_name']);
						$info['width'] = $width;
						$info['height'] = $height;
					}
					$filedetail = [$info];
				}
			}
			if (strlen($fileerror)) {
				if ($is_multiple) {
					$filelist = ['error' => $fileerror];
				} else {
					$filelist[] = ['error' => $fileerror];
				}
				break;
			}
			if ($third != 2 && !$is_byte) {
				foreach ($files as $file) {
					try {
						validate(["$filename" => 'fileExt:'.implode(',', $filetype)])->check(["$filename" => $file]);
					} catch (\think\exception\ValidateException $e) {
						$fileerror = $e->getMessage();
						break;
					}
				}
			}
			if (strlen($fileerror)) {
				if ($is_multiple) {
					$filelist = ['error' => $fileerror];
				} else {
					$filelist[] = ['error' => $fileerror];
				}
				break;
			}
			if (strlen($dir)) {
				$savepath = $dir . '/' . date('Y') . '/' . date('m') . '/' . date('d');
			} else {
				$savepath = date('Y') . '/' . date('m') . '/' . date('d');
			}
			for ($i = 0; $i < count($files); $i++) {
				switch ($fileinfo[$i]['ext']) {
					case 'php':case 'exe':case 'dll':case 'bat':case 'asp':case 'sql':$fileerror = 'FORBIDDEN TO UPLOAD OF ' . implode(',', $filetype) . '.';break;
				}
				if (!in_array($fileinfo[$i]['ext'], $filetype)) {
					$fileerror = 'PLEASE SELECT THE FILE OF ' . implode(',', $filetype);
				}
				$tmp = $fileinfo[$i]['tmp_name'];
				$resource = fopen($tmp, 'rb');
				$fileSize = filesize($tmp);
				fseek($resource, 0);
				if ($fileSize > 512) { //取头和尾
					$hexCode = bin2hex(fread($resource, 512));
					fseek($resource, $fileSize - 512);
					$hexCode .= bin2hex(fread($resource, 512));
				} else { //取全部
					$hexCode = bin2hex(fread($resource, $fileSize));
				}
				fclose($resource);
				/* 通过匹配十六进制代码检测是否存在木马脚本 */
				/* 匹配16进制中的 <% ( ) %> */
				/* 匹配16进制中的 <? ( ) ?> */
				/* 匹配16进制中的 <script | /script> 大小写亦可 */
				if (preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hexCode)) {
					$fileerror = 'FORBIDDEN TO UPLOAD OF ' . implode(',', $filetype) . '!';
				}
				if (strlen($fileerror)) {
					if ($is_multiple) {
						$filelist = ['error' => $fileerror];
					} else {
						$filelist[] = ['error' => $fileerror];
					}
					break;
				}
				if (is_string($third) && strlen($third)) {
					//上传到第三方文件存储
					$configs = config('filesystem.disks');
					if (!isset($configs[$third])) {
						$fileerror = 'Third-party service provider parameter does not exist.';
					}
					if (strlen($fileerror)) {
						if ($is_multiple) {
							$filelist = ['error' => $fileerror];
						} else {
							$filelist[] = ['error' => $fileerror];
						}
						break;
					}
					$configs = $configs[$third];
					switch (strtolower($third)) {
						case 'qiniu':
							$api = new \Qiniu\Qiniu($configs['bucket'], $configs['accessKey'], $configs['secretKey'], $configs['domain']);
							$savename = $api->upload($is_byte ? $fileinfo[$i]['tmp_name'] : $fileinfo[$i], generate_sn(), $fileinfo[$i]['ext'], $savepath);
							break;
						case 'ypyun':
							$api = new \Ypyun\Ypyun($configs['bucketname'], $configs['operator_name'], $configs['operator_pwd'], $configs['domain']);
							$savename = $api->upload($is_byte ? $fileinfo[$i]['tmp_name'] : $fileinfo[$i], generate_sn(), $fileinfo[$i]['ext'], $savepath);
							break;
						default:
							$savename = '';
					}
				} else if ($third == 2) {
					//文件分割上传到本地服务器
					$name = input('post.split_name');
					$total = input('post.split_total/d', 0);
					$savename = \think\facade\Filesystem::disk('public')->putFileAs($savepath, $files[$i], $name.'.'.$fileinfo[$i]['ext']);
					$savename = UPLOAD_PATH . '/' . $savename;
					$ext = explode('.', $savename);
					$ext = $ext[count($ext)-1];
					if (!strlen($ext)) {
						$ext = $fileinfo[$i]['ext'];
						rename(PUBLIC_PATH.$savename, PUBLIC_PATH.$savename.$ext);
					}
					for ($s = 0; $s < $total; $s++) {
						$names = explode('_', $name);
						$splitname = $names[0] . '_' . $s . '.' . $ext;
						$pathname = PUBLIC_PATH . UPLOAD_PATH . '/' . $savepath . '/' . $splitname;
						if (!file_exists($pathname)) exit;
					}
					$pname = generate_sn();
					$fileinfo[$i]['name'] = $pname . '.' . $ext;
					$newname = UPLOAD_PATH . '/' . $savepath . '/' . $pname . '.' . $ext;
					for ($s = 0; $s < $total; $s++) {
						$names = explode('_', $name);
						$splitname = $names[0] . '_' . $s . '.' . $ext;
						$pathname = PUBLIC_PATH . UPLOAD_PATH . '/' . $savepath . '/' . $splitname;
						file_put_contents(PUBLIC_PATH.$newname, file_get_contents($pathname), FILE_APPEND);
						if (file_exists($pathname)) unlink($pathname);
					}
					$savename = $newname;
				} else {
					//上传到本地服务器
					if ($is_byte) {
						makedir(PUBLIC_PATH . UPLOAD_PATH . '/' . $savepath);
						$savename = $savepath . '/' . generate_sn() . '.' . $ext;
						file_put_contents(PUBLIC_PATH . UPLOAD_PATH . '/' . $savename, $filename);
					} else {
						$savename = \think\facade\Filesystem::disk('public')->putFile($savepath, $files[$i], function() {
							return generate_sn();
						});
					}
					$savename = UPLOAD_PATH . '/' . $savename;
					if ($compressPercent > 0) {
						//图片宽高百分比压缩
						$fs = fopen(PUBLIC_PATH . $savename, 'rb');
						$byte = fread($fs, 2);
						fclose($fs);
						$info = @unpack('C2chars', $byte);
						$code = intval($info['chars1'].$info['chars2']);
						$e = '';
						switch ($code) {
							case 255216:$e = 'jpeg';break; //jpg
							case 7173:$e = 'gif';break; //gif
							case 6677:$e = 'bmp';break; //bmp
							case 13780:$e = 'png';break; //png
							default: $fileerror = 'FILE IS NOT A IMAGE';
						}
						if (strlen($fileerror)) {
							if ($is_multiple) {
								$filelist = ['error' => $fileerror];
							} else {
								$filelist[] = ['error' => $fileerror];
							}
							break;
						}
						if (in_array($e, ['jpeg', 'jpg'])) {
							$newname = UPLOAD_PATH . '/' . $savepath . '/' . generate_sn() . '.jpg';
							list($width, $height) = getimagesize(PUBLIC_PATH . $savename);
							$source = imagecreatefromjpeg(PUBLIC_PATH . $savename);
							$thumb = imagecreatetruecolor($width, $height);
							imagecopyresized($thumb, $source, 0, 0, 0, 0, $width, $height, $width, $height);
							imagejpeg($thumb, PUBLIC_PATH . $newname, $compressPercent * 100);
							unlink(PUBLIC_PATH . $savename);
							$savename = $newname;
						}
					} else if ($splitNumber > 0 || $splitHeight > 0) {
						//图片平均分割或按高度分割
						list($width, $height) = getimagesize(PUBLIC_PATH . $savename); //list(width, height, [1:GIF,2:JPG,3:PNG,6:BMP])
						if ( !($splitHeight > 0 && $height <= $splitHeight) ) {
							$fs = fopen(PUBLIC_PATH . $savename, 'rb');
							$byte = fread($fs, 2);
							fclose($fs);
							$info = @unpack('C2chars', $byte);
							$code = intval($info['chars1'].$info['chars2']);
							$e = '';
							switch ($code) {
								case 255216:$e = 'jpeg';break; //jpg
								case 7173:$e = 'gif';break; //gif
								case 6677:$e = 'bmp';
									if (version_compare(PHP_VERSION, '7.2', '<')) $fileerror = 'BMP MUST BE PHP >= 7.2';
									break; //bmp
								case 13780:$e = 'png';break; //png
								default: $fileerror = 'FILE IS NOT A IMAGE';
							}
							if (strlen($fileerror)) {
								if ($is_multiple) {
									$filelist = ['error' => $fileerror];
								} else {
									$filelist[] = ['error' => $fileerror];
								}
								break;
							}
							if ($e == 'bmp') $e = 'jpeg';
							$imagecreatefrom = "imagecreatefrom$e";
							$imageoutput = "image$e";
							$source = $imagecreatefrom(PUBLIC_PATH . $savename);
							$newWidth = $width;
							if ($splitHeight > 0) {
								$newHeight = $splitHeight;
								$splitNumber = ceil($height / $splitHeight);
								$lastHeight = $height % $splitHeight;
							} else {
								$newHeight = floor($height / $splitNumber);
								$lastHeight = $newHeight + ($height % $splitNumber);
							}
							$paths = [];
							$name = generate_sn();
							for ($i = 0; $i < $splitNumber; $i++) {
								$p = $newHeight * $i;
								if (($i+1) == $splitNumber) $newHeight = $lastHeight;
								$thumb = imagecreatetruecolor($newWidth, $newHeight);
								imagecopyresized($thumb, $source, 0, 0, 0, $p, $newWidth, $height, $width, $height);
								if (in_array($e, ['jpeg', 'jpg'])) {
									$path = UPLOAD_PATH . '/' . $savepath . '/' . $name . $i . '.jpg';
									$imageoutput($thumb, PUBLIC_PATH . $path, 80);
								} else {
									$path = UPLOAD_PATH . '/' . $savepath . '/' . $name . $i . '.' . $e;
									$imageoutput($thumb, PUBLIC_PATH . $path);
								}
								$paths[] = $path;
							}
							unlink(PUBLIC_PATH . $savename);
							$savename = $paths;
						}
					}
				}
				$filedetail[$i]['file'] = $savename;
				$filelist[] = $detail ? (is_array($savename) ? $savename : $filedetail[$i]) : $savename;
			}
		} else if (input("?post.$filename")) {
			$filelist = [input("post.$filename", '')];
		} else if (input("?post.origin_$filename")) {
			$filelist = [input("post.origin_$filename", '')];
		}
	}
	return $is_multiple ? $filelist : (count($filelist) ? $filelist[0] : '');
}

//下载远程文件到本地
function download_file($url, $dir='', $origin_name=false, $suffix='') {
	$paths = download_files([$url], $dir, $origin_name, $suffix);
	return count($paths) ? $paths[0] : '';
}
function download_files($urls, $dir='', $origin_name=false, $suffix=''): array {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	if (strlen($dir)) $dir = str_replace(PUBLIC_PATH . UPLOAD_PATH . '/', '', $dir);
	$paths = [];
	foreach ($urls as $url) {
		if (!strlen($url)) continue;
		preg_match('/(\.\w+)$/', $url, $matcher);
		if (is_array($matcher) && count($matcher)) $suffix = $matcher[1];
		if (!$origin_name || !(is_array($matcher) && count($matcher))) {
			$filename = generate_sn();
		} else {
			$pathinfo = explode('/', $url);
			$pathinfo = explode('.', $pathinfo[count($pathinfo)-1]);
			$filename = $pathinfo[0];
		}
		if (strlen($suffix) && !preg_match('/^\./', $suffix)) $suffix = '.'.$suffix;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		if (substr($url, 0, 8) === 'https://') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		if (strlen($dir)) {
			makedir(PUBLIC_PATH . UPLOAD_PATH . '/' . $dir);
			$filepath = UPLOAD_PATH . '/' . $dir . '/' . $filename . $suffix;
			$res = fopen(PUBLIC_PATH . $filepath, 'a');
			fwrite($res, $data);
			fclose($res);
		} else {
			$filepath = $data;
		}
		$paths[] = $filepath;
	}
	return $paths;
}

//导出成excel, $return为true即在服务器生成文件, $fields = ['id'=>'ID', 'name'=>'姓名', 'mobile'=>'电话'];
//composer require phpoffice/phpexcel
//在需要使用导出的文件头引入
//use PHPExcel_IOFactory;
//use PHPExcel;
function export_excel($rs, $fields, $return=false) {
	$objPHPExcel = new PHPExcel();
	//表格头
	$column = 'A';
	$row_number = 1;
	foreach ($fields as $name) {
		$cell = "$column$row_number";
		$objPHPExcel->setActiveSheetIndex()->setCellValue("$cell", "$name ");
		$objSheet = $objPHPExcel->getActiveSheet();
		$objSheet->getColumnDimension($column)->setAutoSize(true);
		$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$objSheet->getStyle("$cell")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		$objSheet->getStyle("$cell")->getAlignment()->setWrapText();
		$column++;
	}
	if ($rs instanceof \think\model\Collection) $rs = $rs->toArray();
	$row_number = 2; //1:based index
	foreach ($rs as $g) {
		$column = 'A';
		foreach ($fields as $field => $name) {
			$cell = "$column$row_number";
			$objSheet = $objPHPExcel->getActiveSheet();
			$objSheet->getColumnDimension($column)->setAutoSize(true);
			if (array_key_exists($field, $g)) {
				$objSheet->setCellValue("$cell", "{$g[$field]} ");
			} else {
				$objSheet->setCellValue("$cell", " ");
			}
			$objSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
			$objSheet->getStyle("$cell")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
			$objSheet->getStyle("$cell")->getAlignment()->setWrapText();
			$column++;
		}
		$row_number++;
	}
	if ($return) {
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$filepath = '/export/';
		$filename = $filepath . generate_sn() . '.xlsx';
		makedir($filepath);
		$objWriter->save(ROOT_PATH . UPLOAD_PATH . $filename);
		return $filename;
	} else {
		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . generate_sn() . '.xlsx"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
}

//导入excel, 对应根目录, sheet<0即获取所有工作表,否则获取指定索引工作表, column_name[false:索引|true:Excel的ABC|字符串数组]
//在需要使用导入的文件头引入
//use PHPExcel_Reader_Excel2007;
//use PHPExcel_Reader_Excel5;
//use PHPExcel_Cell_DataType;
//use PHPExcel_Shared_Date;
//use PHPExcel_RichText;
function import_excel($file, $sheet=0, $start_row=1, $start_column=0, $column_name=false, $non_space_row=false, $delete_file=true) {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$file = ROOT_PATH . '/' . str_replace(ROOT_PATH, '', $file);
	if (empty($file) || !file_exists($file)) die('EXCEL FILE NOT EXISTS');
	setlocale(LC_ALL, 'zh_CN');
	$objRead = new PHPExcel_Reader_Excel2007();
	if (!$objRead->canRead($file)) {
		$objRead = new PHPExcel_Reader_Excel5();
		if (!$objRead->canRead($file)) die('NOT A EXCEL FILE');
	}
	$cellName = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
		'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
	$obj = $objRead->load($file);
	$getSheet = function($sheet) use ($obj, $cellName, $start_row, $start_column, $column_name, $non_space_row) {
		$data = [];
		$currSheet = is_numeric($sheet) ? $obj->getSheet($sheet) : $sheet; //获取指定的sheet表
		$columnH = $currSheet->getHighestColumn(); //取得最大的列号
		$columnCnt = array_search($columnH, $cellName);
		$rowCnt = $currSheet->getHighestRow(); //获取总行数
		for ($row = $start_row; $row <= $rowCnt; $row++) {
			$cellValues = [];
			for ($column = $start_column; $column <= $columnCnt; $column++) {
				$cellId = $cellName[$column].$row;
				$cell = $currSheet->getCell($cellId);
				if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
					$cellValue = $cell->getValue();
					$cellstyleformat = $cell->getStyle()->getNumberFormat();
					$formatcode = $cellstyleformat->getFormatCode();
					if (preg_match('/^(\[\$[A-Z]*-[\dA-F]*])*[hmsdy]/i', $formatcode)) {
						$cellValue = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($cellValue));
					} else {
						//$cellValue = PHPExcel_Style_NumberFormat::toFormattedString($cellValue, $formatcode);
						$cellValue = $cell->getFormattedValue();
					}
				} else {
					//$cellValue = $cell->getCalculatedValue(); //获取公式计算的值
					$cellValue = $cell->getFormattedValue();
				}
				if ($cellValue instanceof PHPExcel_RichText) $cellValue = $cellValue->__toString(); //富文本转换字符串
				$cellValues[$cellName[$column]] = trim(strval($cellValue));
			}
			$notEmptyCell = !$non_space_row;
			foreach ($cellValues as $g) {
				if (!empty($g) && strlen($g)) {
					$notEmptyCell = true;
					break;
				}
			}
			if ($notEmptyCell) {
				$index = 0;
				$rows = [];
				foreach ($cellValues as $k => $g) {
					//$g = iconv('gb2312', 'utf-8//IGNORE', $g);
					if (!$column_name) {
						$rows[] = $g;
					} else if (is_array($column_name)) {
						$rows[$column_name[$index]] = $g;
					} else {
						$rows[$k] = $g;
					}
					$index++;
				}
				$data[] = $rows;
			}
		}
		return $data;
	};
	if ($sheet >= 0) {
		$res = $getSheet($sheet);
	} else {
		$res = [];
		$sheetNames = $obj->getSheetNames();
		foreach ($sheetNames as $name) {
			$res[$name] = $getSheet($obj->getSheetByName($name));
		}
		
	}
	if ($delete_file) unlink($file);
	return $res;
}

//循环创建目录,对应根目录
function makedir($destination, $create_html=false): bool {
	$target_path = trim(str_replace(ROOT_PATH, '', $destination), '/');
	if (is_dir(ROOT_PATH.'/'.$target_path)) return true;
	$each_path = explode('/', $target_path);
	$cur_path = ROOT_PATH; //当前循环处理的路径
	$origin_mask = @umask(0);
	foreach ($each_path as $path) {
		if ($path) {
			$cur_path .= '/' . $path;
			if (!is_dir($cur_path)) {
				if (@mkdir($cur_path)) {
					@chmod($cur_path, 0777);
					if ($create_html) @fclose(@fopen($cur_path . '/index.html', 'w'));
				} else {
					@umask($origin_mask);
					return false;
				}
			}
		}
	}
	@umask($origin_mask);
	return true;
}

//删除文件夹和文件夹下的所有文件
function deletedir($path, $delete_myself=true): bool {
	$path = ROOT_PATH.'/'.trim(str_replace(ROOT_PATH, '', $path), '/');
	if (!is_dir($path)) return true;
	$handle = dir($path);
	while ($entry = $handle->read()) {
		if ($entry != '.' && $entry != '..') {
			if (is_dir($path . '/' . $entry)) {
				deletedir($path . '/' . $entry);
			} else {
				unlink($path . '/' . $entry);
			}
		}
	}
	if ($delete_myself) rmdir($path);
	return true;
}

//判断字符串包含中英文, 返回0全英文或数字或下划线, 1全中文, 2中英混合
function is_en_cn($str): int {
	$allen = preg_match('/^\w+$/', $str); //判断是否是英文或数字或下划线
	$allcn = preg_match('/^[\x7f-\xff]+$/', $str); //判断是否是中文
	if ($allen) {
		return 0;
	} else if ($allcn) {
		return 1;
	} else {
		return 2;
	}
}

//判断移动端浏览器打开
function is_mobile_web(): bool {
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$keywords = [
			'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'blackberry',
			'meizu', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile',
			'smartphone', 'windows ce', 'windows phone', 'ipod', 'iphone', 'ipad', 'android'
		];
		if (preg_match('/('.implode('|', $keywords).')/i', strtolower($_SERVER['HTTP_USER_AGENT']))) return true;
	}
	return false;
}

//验证手机号
function is_mobile($mobile): bool {
	return (bool)preg_match('/^13\d{9}$|^14[5,7]\d{8}$|^15[^4]\d{8}$|^17[03678]\d{8}$|^18\d{9}$/', $mobile);
}

//验证座机
function is_tel($tel): bool {
	return (bool)preg_match('/^((\d{3,4}-)?\d{8}(-\d+)?|(\(\d{3,4}\))?\d{8}(-\d+)?)$/', $tel);
}

//验证电话号码(包括手机号与座机)
function is_phone($phone): bool {
	$result = is_mobile($phone);
	if (!$result) $result = is_tel($phone);
	return $result;
}

//验证邮箱
function is_email($email): bool {
	//return filter_var($email, FILTER_VALIDATE_EMAIL);
	return (bool)preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $email);
}

//验证日期
function is_date($date): bool {
	return (bool)preg_match('/^(?:(?!0000)\d{4}[\/-](?:(?:0?[1-9]|1[0-2])[\/-](?:0?[1-9]|1\d|2[0-8])|(?:0?[13-9]|1[0-2])[\/-](?:29|30)|(?:0?[13578]|1[02])[\/-]31)|(?:\d{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)[\/-]0?2[\/-]29)$/', $date);
}

//严格验证身份证
function is_idcard($idcard): bool {
	$idcard_verify_number = function($idcard_base) {
		//计算身份证校验码, 根据国家标准GB 11643-1999
		if (strlen($idcard_base) != 17) return false;
		$factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2]; //加权因子
		$verify_number_list = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2']; //校验码对应值
		$checksum = 0;
		for ($i=0; $i<strlen($idcard_base); $i++) {
			$checksum += substr($idcard_base, $i, 1) * $factor[$i];
		}
		$mod = $checksum % 11;
		return $verify_number_list[$mod];
	};
	$idcard_15to18 = function($idcard) use ($idcard_verify_number) {
		//将15位身份证升级到18位
		if (strlen($idcard) != 15) return false;
		//如果身份证顺序码是996 997 998 999, 这些是为百岁以上老人的特殊编码
		if (in_array(substr($idcard, 12, 3), ['996', '997', '998', '999'])) {
			$idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
		} else {
			$idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
		}
		return $idcard . $idcard_verify_number($idcard);
	};
	$idcard_checksum18 = function($idcard) use ($idcard_verify_number) {
		//18位身份证校验码有效性检查
		if (strlen($idcard) != 18) return false;
		$idcard_base = substr($idcard, 0, 17);
		if ($idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
			return false;
		} else {
			return true;
		}
	};
	if (strlen($idcard) == 18) {
		return $idcard_checksum18($idcard);
	} else if (strlen($idcard) == 15) {
		$idcard = $idcard_15to18($idcard);
		return $idcard_checksum18($idcard);
	} else {
		return false;
	}
}

//是否图片文件
function is_image($path): bool {
	if (!strlen($path)) return false;
	if (!preg_match('/^https?:\/\//', $path) && !file_exists(PUBLIC_PATH.$path)) return false;
	if (!preg_match('/^https?:\/\//', $path)) $path = PUBLIC_PATH . $path;
	$fs = fopen($path, 'rb');
	$byte = fread($fs, 2);
	fclose($fs);
	$info = @unpack('C2chars', $byte);
	$code = intval($info['chars1'].$info['chars2']);
	switch ($code) {
		case 255216: //jpg
		case 7173: //gif
		case 6677: //bmp
		case 13780:$is_image = true;break; //png
		default:$is_image = false;
	}
	return $is_image;
	/*$fs = fopen('https://www.google.com', 'r', false, stream_context_create(['http' => ['proxy' => 'tcp://127.0.0.1:7890']]));
	$res = stream_get_contents($fs);
	fclose($fs);*/
}

//字符串是否包含avatar字样, 且判断是否本地图片(本地需要返回空,否则按照又拍云规则增加后缀)
function is_avatar($key): string {
	global $img_domain;
	if (strlen($key)) {
		if (strpos($key, 'avatar') !== false) {
			if (!strlen($img_domain)) $img_domain = https() . request()->server('HTTP_HOST');
			if ( !(strpos($key, $img_domain) !== false || !preg_match('/^https?:\/\//', $key)) ) {
				return '!logo';
			}
		}
	}
	return '';
}

//格式化URL,suffix增加网址后缀, 如七牛?imageMogr2/thumbnail/200x200, 又拍云(需自定义)!logo
function add_domain($url, $origin_third_host='', $replace_third_host='', $suffix='') {
	$img_domain = config('app.img_domain');
	$http_host = request()->server('HTTP_HOST');
	if (is_string($url) && strlen($url) && !preg_match('/^https?:\/\//', $url)) {
		if (substr($url, 0, 2) === '//') {
			$url = https() . substr($url, 2);
		} else {
			if (stripos($url, '%domain%') !== false && strpos($url, https() . $http_host) === false) {
				$url = str_replace('%domain%', https() . $http_host, $url);
			} else {
				$url = str_replace('%domain%', '', $url);
				if (substr($url, 0, 1) === '/') {
					$url = ($img_domain ?: https() . $http_host) . $url;
				} else {
					if (preg_match('/^((http|https|ftp):\/\/)?[\w\-_]+(\.[\w\-_]+)+([\w\-.,@?^=%&:\/~+#]*[\w\-@?^=%&\/~+#])?$/', https() . $http_host . '/' . $url)) {
						$url = ($img_domain ?: https() . $http_host) . '/' . $url;
					} else {
						$url = str_replace('"/uploads/', '"' . https() . $http_host . '/uploads/', $url);
					}
				}
			}
		}
	}
	if (is_string($url) && strlen($url) && strpos($url, '/images/') === false && strlen($suffix) && stripos($url, $suffix) === false) $url .= $suffix;
	if (is_string($url)) $url = str_replace('%domain%', '', $url);
	if (strlen($origin_third_host) && strlen($replace_third_host) && preg_match('/^https?:\/\//', $url)) {
		$replace = 'str_replace';
		if (substr($origin_third_host, 0, 1) === '/' && substr($origin_third_host, strlen($origin_third_host) - 1) === '/') $replace = 'preg_replace';
		$url = $replace($origin_third_host, $replace_third_host, $url);
	}
	return $url;
}

//递归一个数组/对象的属性加上域名
function add_domain_deep($obj, $fields=[], $origin_third_host='', $replace_third_host='') {
	if (is_object($obj) || is_array($obj)) {
		$tmpObj = $obj;
		$isModel = false;
		if ( ($obj instanceof \think\model\Collection) || ($obj instanceof \think\Model) || ($obj instanceof \think\paginator\driver\Bootstrap) ) {
			$isModel = true;
			$tmpObj = $obj->toArray();
			if ($obj instanceof \think\paginator\driver\Bootstrap) $tmpObj = $tmpObj['data'];
		}
		foreach ($tmpObj as $key => $val) {
			if (is_object($val) || is_array($val)) {
				if (is_array($obj) || $isModel) {
					$obj[$key] = add_domain_deep($val, $fields, $origin_third_host, $replace_third_host);
				} else if (is_object($obj)) {
					$obj->{$key} = add_domain_deep($val, $fields, $origin_third_host, $replace_third_host);
				}
			} else {
				if ((is_array($fields) && in_array($key, $fields)) || (is_bool($fields) && $fields) || (is_string($fields) && $key === $fields)) {
					if (is_array($obj) || $isModel) {
						$obj[$key] = add_domain($val, $origin_third_host, $replace_third_host, is_avatar($key));
					} else if (is_object($obj)) {
						$obj->{$key} = add_domain($val, $origin_third_host, $replace_third_host, is_avatar($key));
					}
				}
			}
		}
	} else if (!is_null($obj)) {
		$obj = add_domain($obj, $origin_third_host, $replace_third_host);
	}
	return $obj;
}

//获取省市
function city($ip = '') {
	$res = file_get_contents('http://apis.map.qq.com/ws/location/v1/ip?key=NC6BZ-TOF2J-KWKF4-F6I3B-EM2CZ-QJFJ3' . ($ip ?: ''));
	return json_decode($res, true);
	/*{"status":0,"message":"Success","request_id":"c181d1d3-ccb9-4fbb-aeb5-69f4e86b4126","result":{"ip":"120.235.227.101","location":{"lat":23.26093,"lng":113.8109},"ad_info":{"nation":"中国","province":"广东省","city":"广州市","district":"增城区","adcode":440118}}}*/
}
function citys($ip) {
	$data = [
		'accessKey' => 'alibaba-inc',
		'ip' => $ip
	];
	$opts['http'] = [
		'timeout' => 60,
		'method' => 'POST',
		'header' => 'Content-Type:application/x-www-form-urlencoded',
		'content' => http_build_query($data)
	];
	$res = file_get_contents('https://ip.taobao.com/outGetIpInfo', false, stream_context_create($opts));
	$res = json_decode($res, true);
	return $res['data'];
	/*{"data":{"area":"","country":"中国","isp_id":"100025","queryIp":"211.136.223.20","city":"广州","ip":"211.136.223.20","isp":"移动","county":"","region_id":"440000","area_id":"","county_id":null,"region":"广东","country_id":"CN","city_id":"440100"},"msg":"query success","code":0}*/
}

//返回http协议
function https(): string {
	$is_https = false;
	if (!isset($_SERVER['HTTPS'])) return 'http://';
	if ($_SERVER['HTTPS'] === 1) { //Apache
		$is_https = true;
	} else if (strtoupper($_SERVER['HTTPS']) == 'ON') { //IIS
		$is_https = true;
	} else if ($_SERVER['SERVER_PORT'] == 443) { //Other
		$is_https = true;
	}
	return $is_https ? 'https://' : 'http://';
}

//生成随机字母、数字
function random_str($length=4, $factor=[]): string {
	//生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
	if (count($factor)) {
		$arr = $factor;
	} else {
		$arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
	}
	shuffle($arr);
	//return str_shuffle(implode('', $arr)); //随机打乱字符串
	$str = '';
	$arr_len = count($arr);
	for ($i = 0; $i < $length; $i++) {
		$rand = mt_rand(0, $arr_len-1);
		$str .= $arr[$rand];
	}
	return $str;
}

//随机范围内整数
function random_num($min=0, $max=PHP_INT_MAX): int {
	return mt_rand($min, $max);
}

//随机范围内小数
function random_float($min=0, $max=1, $length=2) {
	//$num =  $min + mt_rand() / mt_getrandmax() * ($max - $min);
	$num =  bcadd(strval($min), bcmul(strval(mt_rand() / mt_getrandmax()), bcsub(strval($max), strval($min), 8), 8), 8);
	return substr($num, 0, $length+2);
}

//随机指定数量的范围内不重复数字, 可包含小数, restrict:强制包含小数
function random_norepeat($count, $min=0, $max=PHP_INT_MAX, $is_sort=false, $float_min=0, $float_max=0, $float_length=2, $restrict=false): array {
	$result = [];
	while (count($result) < $count) {
		$num = mt_rand($min, $max);
		if ($float_min != $float_max) {
			if ($restrict) {
				$num = bcadd($num, random_float($float_min, $float_max, $float_length), $float_length);
			} else {
				if (in_array($num, $result)) $num = bcadd($num, random_float($float_min, $float_max, $float_length), $float_length);
			}
		}
		$result[] = strval($num);
		//数组去重
		//$result = array_unique($result); //效率较低
		$result = array_flip($result);
		$result = array_flip($result);
	}
	if ($is_sort) sort($result);
	return $result;
}

//将时间转换成刚刚、分钟、小时
function get_time_word($date) {
	if (!is_numeric($date)) $date = strtotime($date);
	$between = time() - $date;
	if ($between < 60) return '刚刚';
	if ($between < 3600) return floor($between/60) . '分钟前';
	if ($between < 86400) return floor($between/3600) . '小时前';
	if ($between <= 864000) return floor($between/86400) . '天前';
	return date('Y-m-d', $date);
}

//将字符串中间设为星号
function set_star_mark($str, $offset=3, $length=-4) {
	return substr_replace(strval($str), '****', $offset, $length);
}

//保留指定位数小数
function number_round($number, $decimals = 0): string {
	return number_format($number, $decimals, '.', '');
}

//截断过长的字符
function cut_str($string, $length=80, $suffix=''): string {
	$repeat_pattern = function($pattern, $length) {
		return str_repeat("$pattern{0,65535}", $length / 65535)."$pattern{0,".($length % 65535)."}";
	};
	if (!preg_match("(^(".$repeat_pattern("[\t\r\n -\x{10FFFF}]", $length).")($)?)u", $string, $match)) {
		preg_match("(^(".$repeat_pattern("[\t\r\n -~]", $length).")($)?)", $string, $match);
	}
	return $match[1] . $suffix . (isset($match[2]) ? '' : '<i>...</i>');
}

//下划线转驼峰, small:小驼峰
function camelize($value, $small=false, $delimiter='_'): string {
	return trim(str_replace(' ', '', ucwords(str_replace(['-', $delimiter], ' ', ($small ? '#' : '').$value))), '#');
}

//驼峰转下划线
function uncamelize($value, $delimiter='_'): string {
	//return strtolower(preg_replace('/([a-z])([A-Z])/', '$1'.$delimiter.'$2', $value));
	return mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, preg_replace('/\s+/u', '', ucwords($value))), 'UTF-8');
}

//科学计数法还原数值字符串
function enumToStr($num) {
	if (stripos($num, 'e') === false) return $num;
	$num = trim(preg_replace('/[=\'"]/', '', $num, 1), '');
	$res = '';
	while ($num > 0) {
		$v = $num - floor($num / 10) * 10;
		$num = floor($num / 10);
		$res = $v . $res;
	}
	return $res;
}

//输出xml格式
function xml_encode($data) {
	$xml = new \SimpleXMLElement('<?xml version="1.0"?><rest></rest>');
	foreach ($data as $key => $value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$xml->addChild($k, $v);
			}
		} else {
			$xml->addChild($key, $value);
		}
	}
	return $xml->asXML();
}

//解析xml数据
function xml_decode($xml, $assoc=false) {
	$res = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	return json_decode(json_encode($res, JSON_UNESCAPED_UNICODE), $assoc);
}

//写log文件
function write_log($content='', $file='', $trace=false, $echo=false) {
	if (filter_var($file, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
		$trace = $file;
		$file = '';
	}
	if (!strlen($file)) $file = RUNTIME_PATH . '/log.txt';
	if (is_array($content) || is_object($content)) $content = json_encode($content, JSON_UNESCAPED_UNICODE);
	$is_warning = preg_match('/\.php$/i', $file);
	if ($is_warning) {
		$content = $file . PHP_EOL . $content;
		$file = RUNTIME_PATH . '/warning.txt';
		$trace = true;
	}
	$traceStr = '';
	if ($trace) {
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		$traceStr = PHP_EOL . PHP_EOL . $trace;
		//$backtrace = debug_backtrace();
		//array_shift($backtrace); //删除当前函数
	}
	file_put_contents($file, date('Y-m-d H:i:s') . PHP_EOL . $content . $traceStr . PHP_EOL . (is_bool($echo)?'==============================':'') . PHP_EOL . PHP_EOL, FILE_APPEND);
	if ($is_warning) error503();
	if (is_bool($echo) && $echo) echo $content . PHP_EOL;
}
//写error文件
function write_error($content='', $file='', $trace=false, $echo=false) {
	if (filter_var($file, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
		$trace = $file;
		$file = '';
	}
	write_log($content, strlen($file) ? $file : RUNTIME_PATH . '/error.txt', $trace, $echo);
}

//输出script
function script($msg='', $url='') {
	$html = '<meta charset="UTF-8"><script>';
	if (strlen($msg)) $html .= "alert('$msg');";
	if (strlen($url)) {
		if (substr($url, 0, 11) == 'javascript:') {
			$html .= substr($url, 11);
		} else if (substr($url, 0, 3) == 'js:') {
			$html .= substr($url, 3);
		} else if (stripos($url, 'commit') !== false) {
			$html .= 'history.go(-2);';
		} else {
			$html .= "location.href = '$url';";
		}
	}
	$html .= '</script>';
	exit($html);
}
function historyBack($msg='') {
	script($msg, 'javascript:history.back()');
}

//404错误
function error404() {
	header('HTTP/1.1 404 Not Found.', TRUE, 404);
	exit(1);
}

//503错误
function error503() {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	exit(1);
}

//友好提示
function error_tip($tips = '', $toIndex = false) {
	if (is_numeric($toIndex)) $toIndex = false;
	$icon = $iconStyle = $textStyle = '';
	$iconWidth = $iconHeight = '1.5rem';
	$bgColor = ''; //#ebfaff
	$countDown = 5;
	if (is_array($tips)) {
		if (isset($tips['icon'])) $icon = $tips['icon'];
		if (isset($tips['iconWidth'])) $iconWidth = $tips['iconWidth'];
		if (isset($tips['iconHeight'])) $iconHeight = $tips['iconHeight'];
		if (isset($tips['bgColor'])) $bgColor = $tips['bgColor'];
		if (isset($tips['iconStyle'])) $iconStyle = $tips['iconStyle'];
		if (isset($tips['textStyle'])) $textStyle = $tips['textStyle'];
		if (isset($tips['countDown'])) $countDown = intval($tips['countDown']);
		$tips = $tips['tips'];
	}
	if (defined('IS_API') && IS_API) error($tips);
	$referer = request()->server('HTTP_REFERER');
	$html = '<!doctype html>
<html lang="zh-CN" style="font-size:100px;">
<head>
<meta name="viewport" content="width=320,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=0" />
<meta name="format-detection" content="telephone=no" />
<meta name="format-detection" content="email=no" />
<meta name="format-detection" content="address=no" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta charset="UTF-8">
<title>'.$tips.'</title>
</head>
<body style="background:'.$bgColor.';">
<style>
html, body{height:100%; margin:0; padding:0; position:relative; text-align:center; font-family:Arial,serif; -webkit-font-smoothing:antialiased;}
.tip-view{position:absolute; left:0; top:50%; width:100%; height:auto; margin-top:-0.5rem; transform:translateY(-50%);}
.tip-view i{display:block; margin:0 auto; width:1.5rem; height:1.5rem; background:no-repeat center center;'.(!strlen($icon)?" background-image:url(\"data:image/png;base64,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\");":'').' background-size:cover;}
.tip-view span{display:block; width:100%; height:0.34rem; line-height:0.34rem; font-size:0.18rem; font-family:"微软雅黑",arial,sans-serif;}
.tip-view div{width:100%; height:0.2rem; line-height:0.2rem; font-size:0.14rem; color:#ccc;}
.tip-view strong{font-weight:normal;}
</style>
<div class="tip-view">
	<i'.(strlen($icon)?" style=\"background-image:url($icon);width:$iconWidth;height:$iconHeight;$iconStyle\"":'').'></i>
	<span'.(strlen($textStyle)?" style=\"$textStyle\"":'').'>'.$tips.'</span>
	'.(($countDown && (strlen($referer) || $toIndex))?'<div>That will be return after at <strong>'.$countDown.'</strong>s</div>':'').'
</div>
</body>
</html>';
	if ($countDown && (strlen($referer) || $toIndex)) $html .= PHP_EOL.'<script>
let count = '.$countDown.', timer = setInterval(function(){
	if(count <= 0){
		clearInterval(timer);timer = null;
		'.( strlen($referer) ? 'history.back();' : (is_string($toIndex) ? 'location.href = "'.$toIndex.'";' : 'location.href = "/'.MODULE_NAME.'";') ).'
		return;
	}
	count--;
	let strong = document.getElementsByTagName("strong");
	if(strong.length)strong[0].innerHTML = count;
}, 1000);
</script>';
	echo $html;
	exit;
}

//CURL方式请求
function requestUrl($method, $url, $params=[], $returnJson=false, $postJson=false, $headers=[], $getHeader=false) {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$method = strtoupper($method);
	if (substr($url, 0, 1) == '/') $url = https() . request()->server('HTTP_HOST') . $url;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $method == 'GET' ? 7 : 60*60); //请求超时
	curl_setopt($ch, CURLOPT_TIMEOUT, 60*60); //执行超时
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //使用IPV4
	if (defined('CURL_GFW')) {
		$gfw = explode('@', CURL_GFW);
		if (count($gfw) > 1) {
			$account = $gfw[0];
			$hp = explode(':', $gfw[1]);
		} else {
			$account = null;
			$hp = explode(':', $gfw[0]);
		}
		curl_setopt($ch, CURLOPT_PROXY, $hp[0]);
		curl_setopt($ch, CURLOPT_PROXYPORT, count($hp) > 1 ? $hp[1] : '8080');
		if ($account) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $account);
	}
	if ($getHeader) curl_setopt($ch, CURLOPT_HEADER, 1);
	if (isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	switch ($method) {
		case 'POST':curl_setopt($ch, CURLOPT_POST, 1);break;
		case 'PUT':
		case 'PATCH':
		case 'DELETE':curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);break;
		default:curl_setopt($ch, CURLOPT_HTTPGET, 1);break;
	}
	if (is_array($headers) && count($headers)) {
		$headers[] = "X-HTTP-Method-Override: $method"; //HTTP头信息
	} else {
		$headers = ["X-HTTP-Method-Override: $method"];
	}
	if (is_array($headers) && count($headers)) {
		//使用JSON提交
		if ($postJson) {
			$headers[] = 'Content-type: application/json;charset=UTF-8';
			if (!empty($params) && is_array($params)) $params = json_encode($params, JSON_UNESCAPED_UNICODE);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	if (substr($url, 0, 8) === 'https://') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
		//curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_SSLv3); //SSL版本
	}
	if (!empty($params)) {
		if (is_array($params)) {
			if (class_exists('\CURLFile')) {
				foreach ($params as $key => $param) {
					if (is_string($param) && preg_match('/^@/', $param)) $params[$key] = new \CURLFile(realpath(trim($param, '@')));
				}
			} else {
				if (defined('CURLOPT_SAFE_UPLOAD')) curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0); //指定PHP5.5及以上兼容@语法,否则需要使用CURLFile
			}
		}
		//如果data为数组即使用multipart/form-data提交, 为字符串即使用application/x-www-form-urlencoded
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}
	$res = curl_exec($ch);
	if ($getHeader) exit($res);
	$header_info = curl_getinfo($ch); //获取请求头信息
	if (intval($header_info['http_code']) == 301 || intval($header_info['http_code']) == 302) {
		$url = $header_info['redirect_url'];
		$fn = __FUNCTION__;
		$res = $fn($method, $url, $params, false, $postJson, $headers, $getHeader);
	}
	$result = $res;
	if ($res === false) {
		echo 'Curl error: ' . curl_error($ch);
		exit;
	}
	curl_close($ch);
	if ($returnJson) {
		if (stripos($res, '<?xml ') !== false) $res = xml_decode($res, true);
		else $res = json_decode($res, true);
		if (is_null($res)) $res = null;
	}
	if (is_null($res)) write_log(print_r($result, true));
	return $res;
}
function requestUrls($method, $url, $params=[], $returnJson=false, $postJson=false, $headers=[], $getHeader=false) {
	set_time_limit(0);
	ini_set('memory_limit', '10240M');
	$method = strtoupper($method);
	$urls = is_array($url) ? array_merge([], $url) : [$url];
	$ch = [];
	$res = [];
	$cm = curl_multi_init(); //创建批处理cURL句柄
	foreach ($urls as $k => $_url) {
		$_params = is_array($params) ? array_merge([], $params) : [];
		$_headers = is_array($headers) ? array_merge([], $headers) : [];
		$ch[$k] = curl_init();
		curl_setopt($ch[$k], CURLOPT_URL, $_url);
		curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch[$k], CURLOPT_CONNECTTIMEOUT, $method == 'GET' ? 7 : 60*60); //请求超时
		curl_setopt($ch[$k], CURLOPT_TIMEOUT, 60*60); //执行超时
		curl_setopt($ch[$k], CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //使用IPV4
		if (defined('CURL_GFW')) {
			$gfw = explode('@', CURL_GFW);
			if (count($gfw) > 1) {
				$account = $gfw[0];
				$hp = explode(':', $gfw[1]);
			} else {
				$account = null;
				$hp = explode(':', $gfw[0]);
			}
			curl_setopt($ch[$k], CURLOPT_PROXY, $hp[0]);
			curl_setopt($ch[$k], CURLOPT_PROXYPORT, count($hp) > 1 ? $hp[1] : '8080');
			if ($account) curl_setopt($ch[$k], CURLOPT_PROXYUSERPWD, $account);
		}
		if ($getHeader) curl_setopt($ch[$k], CURLOPT_HEADER, 1);
		if (isset($_SERVER['HTTP_USER_AGENT'])) curl_setopt($ch[$k], CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		switch ($method) { //请求方式
			case 'POST':curl_setopt($ch[$k], CURLOPT_POST, 1);break;
			case 'PUT':
			case 'PATCH':
			case 'DELETE':curl_setopt($ch[$k], CURLOPT_CUSTOMREQUEST, $method);break;
			default:curl_setopt($ch[$k], CURLOPT_HTTPGET, 1);break;
		}
		if (is_array($headers) && count($headers)) {
			$_headers[] = "X-HTTP-Method-Override: $method"; //HTTP头信息
		} else {
			$_headers = ["X-HTTP-Method-Override: $method"];
		}
		if (is_array($headers) && count($headers)) {
			//使用JSON提交
			if ($postJson) {
				$_headers[] = 'Content-type: application/json;charset=UTF-8';
				if (!empty($params)) $_params = json_encode($_params, JSON_UNESCAPED_UNICODE);
			}
			curl_setopt($ch[$k], CURLOPT_HTTPHEADER, $_headers);
		}
		if (substr($_url, 0, 8) === 'https://') {
			curl_setopt($ch[$k], CURLOPT_SSL_VERIFYPEER, 0); //对认证证书来源的检查
			curl_setopt($ch[$k], CURLOPT_SSL_VERIFYHOST, 0); //从证书中检查SSL加密算法是否存在
			//curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_SSLv3); //SSL版本
		}
		if (!empty($params)) {
			if (is_array($_params)) {
				if (class_exists('\CURLFile')) {
					foreach ($_params as $key => $param) {
						if (is_string($param) && preg_match('/^@/', $param)) $_params[$key] = new \CURLFile(realpath(trim($param, '@')));
					}
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) curl_setopt($ch[$k], CURLOPT_SAFE_UPLOAD, 0); //指定PHP5.5及以上兼容@语法,否则需要使用CURLFile
				}
			}
			//如果data为数组即使用multipart/form-data提交, 为字符串即使用application/x-www-form-urlencoded
			curl_setopt($ch[$k], CURLOPT_POSTFIELDS, $_params);
		}
		//附加 Authorization: Basic <Base64(id:key)>
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		//curl_setopt($ch, CURLOPT_USERPWD, "api:{$key}");
		curl_multi_add_handle($cm, $ch[$k]); //加入多处理句柄
	}
	$active = NULL; //连接数
	do { //防卡死写法,执行批处理句柄
		//这里$active会被改写成当前未处理数,全部处理成功$active会变成0
		$mrc = curl_multi_exec($cm, $active);
		//这个循环的目的是尽可能的读写，直到无法继续读写为止(返回CURLM_OK)
		//返回(CURLM_CALL_MULTI_PERFORM)就表示还能继续向网络读写
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	//curl_multi_select的作用在等待过程中，如果有就返回目前可以读写的句柄数量,以便
	//继续读写操作,0则没有可以读写的句柄(完成了)
	while ($active && $mrc == CURLM_OK) { //直到出错或者全部读写完毕
		while (curl_multi_exec($cm, $active) === CURLM_CALL_MULTI_PERFORM);
		if (curl_multi_select($cm) != -1) {
			do {
				$mrc = curl_multi_exec($cm, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}
	}
	foreach ($urls as $k => $_url) {
		//$info = curl_multi_info_read($cm); //获取当前解析的cURL的相关传输信息
		$res[$k] = curl_multi_getcontent($ch[$k]); //获取输出的文本流
		if ($getHeader) exit($res[$k]);
		$header_info = curl_getinfo($ch[$k]); //获取请求头信息
		if (intval($header_info['http_code']) == 301 || intval($header_info['http_code']) == 302) {
			$_url = $header_info['redirect_url'];
			$fn = __FUNCTION__;
			$res[$k] = $fn($method, $_url, $params, false, $postJson, $headers, $getHeader);
		}
		$result = $res[$k];
		if ($res[$k] === false) {
			echo 'Curl error: ' . curl_error($ch[$k]);
			exit;
		}
		curl_multi_remove_handle($cm, $ch[$k]); //移除curl批处理句柄资源中的某个句柄资源
		curl_close($ch[$k]);
		if ($returnJson) {
			if (!is_array($res[$k])) {
				if (stripos($res[$k], '<?xml ') !== false) $res[$k] = xml_decode($res[$k], true);
				else $res[$k] = json_decode($res[$k], true);
			}
			if (is_null($res[$k])) $res[$k] = null;
		}
		if (is_null($res[$k])) write_log(print_r($result, true));
	}
	curl_multi_close($cm);
	if (is_array($url)) {
		return $res;
	} else {
		$values = array_values($res);
		return $values[0];
	}
}

//异步调用PHP, 例如处理完客户端需要的数据就返回, 再调用该函数异步在服务器执行耗时的操作
function requestAsync($method, $url, $param=[], $header=[]) {
	//当执行过程中,客户端连接断开或连接超时,都会有可能造成执行不完整,因此目标网址程序需要加上
	//ignore_user_abort(true); //忽略客户端断开
	//set_time_limit(0); //设置执行不超时
	$method = strtoupper($method);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	if ($method == 'POST') {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, empty($param) ? '' : (is_array($param) ? http_build_query($param) : $param));
	}
	if (is_array($header)) {
		$headers = ['Content-type: application/x-www-form-urlencoded'];
		foreach ($header as $h) $headers[] = $h;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	if (substr($url, 0, 8) === 'https://') {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}
	curl_exec($ch);
	curl_close($ch);
}

//实例化一个控制器
function c($controller, $is_static = false) {
	if (!$controller) error('CONTROLLER IS EMPTY');
	$clazz = '\\app\\'.MODULE_NAME.'\\controller\\' . camelize('_'.$controller);
	if ($is_static) return $clazz;
	return new $clazz();
}

//实例化一个模型
function m($model, $is_static = false) {
	if (!$model) error('MODEL IS EMPTY');
	$clazz = '\\app\\model\\' . camelize('_'.$model);
	if ($is_static) return $clazz;
	return new $clazz();
}

//根据表实例化一个数组
function t($table, $fields='*', $is_detail=false): array {
	if (is_string($fields) && !strlen($fields)) $fields = '*';
	$is_sqlite3 = false;
	if (strpos($table, ':') !== false) {
		/*env增加
		[SQLITE]
		DATABASE = sqlite.db
		DRIVER = sqlite
		connections增加
		'sqlite' => [
			// 数据库类型
			'type' => 'sqlite',
			// 数据库名
			'database' => env('sqlite.database', ''),
			// 监听SQL
			'trigger_sql' => true
		]
		t('sqlite:table')*/
		$is_sqlite3 = true;
		$arr = explode(':', $table);
		$desc = \think\facade\Db::connect($arr[0])->query("PRAGMA table_info($arr[1])");
	} else {
		if (is_string($table)) {
			$prefix = env('database.prefix');
			$t = ($prefix ?: '') . $table;
		} else if ($table instanceof \think\Model) {
			$t = $table->getTable();
		} else {
			exit('Table type invalid');
		}
		$desc = \think\facade\Db::query("SHOW FULL COLUMNS FROM $t");
		//SHOW COLUMNS FROM table //数据表结构(不包括注释)
		//SHOW TABLE STATUS //数据表与注释
		//SHOW TABLE STATUS LIKE 'table' //指定数据表
	}
	if (is_string($fields) && strlen($fields) && $fields != '*') {
		$fieldsArr = [];
		$fields = explode(',', $fields);
		foreach ($fields as $f) $fieldsArr[] = trim($f);
		$fields = $fieldsArr;
	}
	$app = [];
	foreach ($desc as $g) {
		if (is_array($fields) || $fields == '*') {
			if (is_array($fields)) {
				if ($is_sqlite3) {
					if (!in_array($g['name'], $fields)) continue;
				} else {
					if (!in_array($g['Field'], $fields)) continue;
				}
			}
			$item = [];
			if (preg_match('/^(char|varchar|text|tinytext|mediumtext|longtext)/i', $is_sqlite3 ? $g['type'] : $g['Type'])) {
				if ($is_detail) {
					if ($is_sqlite3) {
						$item['default'] = $g['dflt_value'] ? trim($g['dflt_value'], "'") : '';
					} else {
						$item['default'] = $g['Default'] ?: '';
					}
					$item['type'] = 'string';
				} else {
					if ($is_sqlite3) {
						$item = $g['dflt_value'] ? trim($g['dflt_value'], "'"): '';
					} else {
						$item = $g['Default'] ?: '';
					}
				}
			} else if (preg_match('/^(datetime|date|time|timestamp)/i', $is_sqlite3 ? $g['type'] : $g['Type'])) {
				if ($is_detail) {
					if ($is_sqlite3) {
						$item['default'] = $g['dflt_value'] ? trim($g['dflt_value'], "'") : null;
						$item['type'] = $g['type'];
					} else {
						$item['default'] = $g['Default'] ?: null;
						$item['type'] = $g['Type'];
					}
				} else {
					if ($is_sqlite3) {
						$item = $g['dflt_value'] ? trim($g['dflt_value'], "'"): null;
					} else {
						$item = $g['Default'] ?: null;
					}
				}
			} else if (isset($g['Field']) && $g['Field'] == 'id') {
				if ($is_detail) {
					$item['default'] = 0;
					$item['type'] = 'int';
				} else {
					$item = 0;
				}
			} else if (isset($g['name']) && $g['name'] == 'id') {
				if ($is_detail) {
					$item['default'] = 0;
					$item['type'] = 'int';
				} else {
					$item = 0;
				}
			} else {
				if (preg_match('/^(float|decimal|double|numeric)/i', $is_sqlite3 ? $g['type'] : $g['Type'])) {
					if ($is_detail) {
						if ($is_sqlite3) {
							$item['default'] = $g['dflt_value'] ? floatval(trim($g['dflt_value'], "'")) : 0;
						} else {
							$item['default'] = $g['Default'] ? floatval($g['Default']) : 0;
						}
						$item['type'] = 'float';
					} else {
						if ($is_sqlite3) {
							$item = $g['dflt_value'] ? floatval(trim($g['dflt_value'], "'")) : 0;
						} else {
							$item = $g['Default'] ? floatval($g['Default']) : 0;
						}
					}
				} else {
					if ($is_detail) {
						if ($is_sqlite3) {
							$item['default'] = $g['dflt_value'] ? intval(trim($g['dflt_value'], "'")) : 0;
						} else {
							$item['default'] = $g['Default'] ? intval($g['Default']) : 0;
						}
						$item['type'] = 'int';
					} else {
						if ($is_sqlite3) {
							$item = $g['dflt_value'] ? intval(trim($g['dflt_value'], "'")) : 0;
						} else {
							$item = $g['Default'] ? intval($g['Default']) : 0;
						}
					}
				}
			}
			if ($is_detail) {
				if ($is_sqlite3) {
					$item['null'] = $g['notnull'] == 0 ? 1 : 0;
				} else {
					$item['null'] = $g['Null'] == 'YES' ? 1 : 0;
					$item['comment'] = $g['Comment'] ?: '';
				}
			}
			if ($is_sqlite3) {
				$app[$g['name']] = $item;
			} else {
				$app[$g['Field']] = $item;
			}
		}
	}
	return $app;
}

//替换表前缀(注意！不要随便使用，请在后台调用)
function changeTablePrefix($old_prefix, $prefix) {
	$content = file_get_contents(ROOT_PATH . '/.env');
	if (!preg_match("/PREFIX = $old_prefix/", $content)) error('旧表前缀不正确');
	$list = \think\facade\Db::query('SHOW TABLE STATUS');
	foreach ($list as $row) {
		\think\facade\Db::execute('ALTER TABLE `'.$row['Name'].'` RENAME TO `'.str_replace($old_prefix, $prefix, $row['Name']).'`');
	}
	$content = str_replace("PREFIX = $old_prefix", "PREFIX = $prefix", $content);
	file_put_contents(ROOT_PATH . '/.env', $content);
	return $prefix;
}

//后台文件夹随机修改(注意！不要随便使用，请在后台调用)
function changeGmPath() {
	while (true) {
		$dirname = strtolower(random_str(8));
		if (!preg_match('/^\d/', $dirname)) break;
	}
	$dirpath = dirname(app_path()) . '/' . $dirname;
	$files = glob(app_path() . 'controller/*.php');
	foreach ($files as $file) {
		$content = file_get_contents($file);
		$content = str_replace('namespace app\\'.MODULE_NAME.'\\controller;', 'namespace app\\'.$dirname.'\\controller;', $content);
		file_put_contents($file, $content);
	}
	$path = app_path() . 'view';
	$files = glob($path . '/*.html');
	$handle = dir($path);
	while ($entry = $handle->read()) {
		if ($entry != '.' && $entry != '..' && is_dir($path . '/' . $entry)) $files = array_merge($files, glob($path . '/' . $entry . '/*.html'));
	}
	foreach ($files as $file) {
		$content = file_get_contents($file);
		$content = str_replace('\\app\\'.MODULE_NAME.'\\controller\\Core::', '\\app\\'.$dirname.'\\controller\\Core::', $content);
		$content = str_replace('/'.MODULE_NAME.'/', '/'.$dirname.'/', $content);
		file_put_contents($file, $content);
	}
	$menu = \think\facade\Db::name('menu')->where('path', 'like', '/'.MODULE_NAME.'/%')->field('id, path')->select();
	foreach ($menu as $row) {
		\think\facade\Db::name('menu')->where('id', $row['id'])->update(['path'=>preg_replace('/^\/'.MODULE_NAME.'\//', '/'.$dirname.'/', $row['path'])]);
	}
	$manage = \think\facade\Db::name('manage')->where('status', 1)->field('id')->select();
	foreach ($manage as $row) {
		\think\facade\Cache::delete('manage:menu:' . $row['id']);
	}
	deletedir(ROOT_PATH . '/runtime/' . MODULE_NAME);
	rename(app_path(), $dirpath);
	return $dirname;
}

//指定币种与USDT兑换率(1USDT兑n个币种), huobi火币, bian币安
function getCoinRate($symbol = 'btc', $from = 'huobi') {
	$symbol = strtolower($symbol);
	if ($symbol == 'usdt') return 1;
	switch ($from) {
		case 'huobi':
			$data = requestUrl('get', "https://api.huobi.pro/market/detail/merged?symbol={$symbol}usdt", null, true);
			if (!isset($data['tick']) || !isset($data['tick']['close'])) return 0;
			return floatval($data['tick']['close']);
			break;
		case 'bian':
			$data = requestUrl('get', "https://fapi.binance.com/fapi/v1/ticker/price?symbol={$symbol}usdt", null, true);
			if (!$data) return 0;
			return floatval($data['price']);
			break;
	}
	return 0;
}

//成功返回函数封装
const JSON_DATA_CAMELIZE = false; //字段转小驼峰
function success($data = null, $msg = 'success') {
	$RETURN_TYPE = defined('RETURN_TYPE') ? strtolower(RETURN_TYPE) : '';
	$JSON_CODE_SUCCESS = defined('JSON_CODE_SUCCESS') ? JSON_CODE_SUCCESS : 0;
	$JSON_CODE_KEY = defined('JSON_CODE_KEY') ? JSON_CODE_KEY : 'code';
	$JSON_MSG_KEY = defined('JSON_MSG_KEY') ? JSON_MSG_KEY : 'msg';
	$JSON_DATA_KEY = defined('JSON_DATA_KEY') ? JSON_DATA_KEY : 'data';
	$IS_AJAX = defined('IS_AJAX') ? IS_AJAX : (request()->isAjax() || request()->get('output') === 'json');
	$IS_AJAX = $IS_AJAX || $RETURN_TYPE === 'json' || $RETURN_TYPE === 'xml';
	
	if ((is_string($data) && !is_string($msg)) ||
		(is_string($data) && preg_match('/\.(html|view)$/', $data)) ||
		(is_string($data) && preg_match('/<[^>]+>/', $data)) ||
		(is_string($data) && preg_match('/^(json|xml|view|display):/', $data)) ||
		(is_string($msg) && preg_match('/^(tourl|location|stay):/', $msg))) {
		list($data, $msg) = [$msg, $data]; //保持tourl:|stay:在data, .html|.view|<tag>|json:|xml:|view:|display:在msg
	}
	
	if (preg_match('/\.html$/', $msg)) { //.html结尾按完整路径处理
		$template_file = $msg;
		$msg = 'success';
	} else if (preg_match('/\.view$/', $msg)) { //.view结尾自动在前面追加当前Controller
		$template_file = substr($msg, 0, strlen($msg) - 5);
		if (strpos($template_file, '/') === false) $template_file = CONTROLLER_NAME . '/' . $template_file;
		$msg = 'success';
	} else if (preg_match('/<[^>]+>/', $msg)) { //html标签即按模板内容输出
		$template_file = $msg;
		$msg = 'success';
	} else {
		$template_file = CONTROLLER_NAME . '/' . ACTION_NAME;
		if (preg_match('/^json:/', $msg)) {
			preg_match('/^json:(.*)$/', $msg, $matcher);
			$RETURN_TYPE = 'json';
			$IS_AJAX = true;
			$msg = strlen($matcher[1]) ? $matcher[1] : 'success';
		} else if (preg_match('/^xml:/', $msg)) {
			preg_match('/^xml:(.*)$/', $msg, $matcher);
			$RETURN_TYPE = 'xml';
			$IS_AJAX = true;
			$msg = strlen($matcher[1]) ? $matcher[1] : 'success';
		} else if (preg_match('/^view:/', $msg)) {
			preg_match('/^view:(.*)$/', $msg, $matcher);
			$RETURN_TYPE = 'view';
			$IS_AJAX = false;
			$msg = strlen($matcher[1]) ? $matcher[1] : 'success';
		} else if (preg_match('/^display:/', $msg)) {
			preg_match('/^display:(.*)$/', $msg, $matcher);
			$RETURN_TYPE = 'display';
			$IS_AJAX = false;
			$msg = strlen($matcher[1]) ? 'success' : '';
			if (strlen($matcher[1])) $template_file = $matcher[1];
		}
	}
	
	$washResponse = function($data) use (&$washResponse, $IS_AJAX) {
		if ($data instanceof \think\response\Json) {
			$tmp = $data->getData();
			if (!isset($tmp['data'])) return $data;
			$data = $tmp['data'];
		}
		else if ($data instanceof \think\response\View) $data = $data->getVars();
		if (($data instanceof \think\model\Collection) || ($data instanceof \think\Model) || ($data instanceof \think\paginator\driver\Bootstrap)) {
			if ($IS_AJAX) {
				$obj = $data->toArray();
				if ($data instanceof \think\paginator\driver\Bootstrap) $obj = $obj['data'];
				$data = $obj;
			}
		}
		if (!is_array($data)) return $data;
		foreach ($data as $key => $value) {
			if (JSON_DATA_CAMELIZE && $IS_AJAX && !is_numeric($key)) {
				unset($data[$key]);
				$key = camelize($key, true);
			}
			if (is_array($value)) $data[$key] = $washResponse($value);
			else if ($value instanceof \think\response\Json) {
				$tmp = $value->getData();
				if (isset($tmp['data'])) $data[$key] = $washResponse($tmp['data']);
			}
			else if ($value instanceof \think\response\View) $data[$key] = $washResponse($value->getVars());
			else if (($value instanceof \think\model\Collection) || ($value instanceof \think\Model) || ($value instanceof \think\paginator\driver\Bootstrap)) {
				if ($IS_AJAX) {
					$obj = $value->toArray();
					if ($value instanceof \think\paginator\driver\Bootstrap) $obj = $obj['data'];
					$data[$key] = $washResponse($obj);
				} else {
					$data[$key] = $value;
				}
			} else {
				$data[$key] = $value;
			}
		}
		return $data;
	};
	
	if (func_num_args() > 3) { //第4个参数开始若为数组即合并到data
		if (!is_object($data)) {
			if (!is_array($data)) $data = [];
			for ($i = 3; $i < func_num_args(); $i++) {
				$param = $washResponse(func_get_arg($i));
				if (is_array($param)) $data = array_merge($data, $param);
			}
		}
	}
	
	$toUrl = '';
	$json = [
		$JSON_CODE_KEY => $JSON_CODE_SUCCESS,
		$JSON_MSG_KEY => (is_string($msg) && !strlen($msg)) ? 'success' : $msg,
	];
	if (is_string($data) && !strlen($data)) $data = null;
	if (is_array($data) && !count($data)) $data = null;
	if (!is_null($data)) {
		if (is_string($data)) {
			if (preg_match('/^tourl:/', $data)) {
				$action = str_replace('\\', '/', str_ireplace('tourl:', '', $data));
				if (substr($action, 0, 1) === ':') {
					$action = strtolower(url(substr($action, 1)));
				} else if (substr($action, 0, 1) !== '/') {
					$arr = explode('/', $action);
					if (count($arr) == 1) {
						$action = MODULE_NAME . '/' . CONTROLLER_NAME . '/' . $action;
					} else if (count($arr) == 2) {
						$action = MODULE_NAME . '/' . $action;
					}
				}
				$data = '/' . trim(strtolower($action), '/');
				if ($IS_AJAX) {
					$json['toUrl'] = $data;
				} else {
					$toUrl = $data;
				}
			} else if (preg_match('/^stay:/', $data)) {
				if ($IS_AJAX) {
					$json['stay'] = 1;
				}
			} else {
				$json[$JSON_DATA_KEY] = $data;
			}
		} else {
			$json[$JSON_DATA_KEY] = $washResponse($data);
		}
	}
	
	if (func_num_args() > 2) { //第3个参数合并到json
		$extend = $washResponse(func_get_arg(2));
		if (is_array($extend)) $json = array_merge($json, $extend);
	}
	
	if ($RETURN_TYPE === 'xml' || request()->get('output') === 'xml') {
		return xml($json);
	}
	
	if ($IS_AJAX || request()->get('output') === 'view') {
		return json($json);
	}
	
	if (strlen($toUrl)) {
		if (!is_string($json[$JSON_MSG_KEY]) || $json[$JSON_MSG_KEY] === 'success') return redirect($toUrl);
		script($json[$JSON_MSG_KEY], $toUrl);
	}
	
	unset($json[$JSON_CODE_KEY], $json[$JSON_MSG_KEY]);
	if (preg_match('/<[^>]+>/', $template_file)) return display($template_file, $json);
	return view($template_file, $json);
}

//错误返回函数封装
function error($msg = 'error', $code = 1) {
	$RETURN_TYPE = defined('RETURN_TYPE') ? strtolower(RETURN_TYPE) : '';
	$JSON_CODE_KEY = defined('JSON_CODE_KEY') ? JSON_CODE_KEY : 'code';
	$JSON_MSG_KEY = defined('JSON_MSG_KEY') ? JSON_MSG_KEY : 'msg';
	$IS_AJAX = defined('IS_AJAX') ? IS_AJAX : (request()->isAjax() || request()->get('output') === 'json');
	$IS_AJAX = $IS_AJAX || $RETURN_TYPE === 'json' || $RETURN_TYPE === 'xml';
	if (is_string($code) && !is_string($msg)) {
		list($msg, $code) = [$code, $msg];
	}
	if ($IS_AJAX) {
		if (!is_numeric($code)) $code = 1;
		echo json_encode([$JSON_CODE_KEY => $code, $JSON_MSG_KEY => $msg], JSON_UNESCAPED_UNICODE);
		exit;
	}
	error_tip($msg, $code);
	return '';
}
