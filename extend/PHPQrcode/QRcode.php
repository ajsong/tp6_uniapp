<?php
namespace PHPQrcode;

/*$code_path = (new \PHPQrcode\QRcode())->png(urldecode($data), $filename, $errorCorrectionLevel, $matrixPointSize)
	->logo('logo.jpg') //生成logo二维码
	->background('background.jpg', 180, 500) //给二维码加上背景
	->text('role', 20, ['center', 740], '#ff4351') //添加文字水印
	->text('nick_name', 20, ['center', 780], '#000000')
	->getPath(); //获取二维码生成的地址*/


class QRcode
{
	protected $cache_dir = '';    //二维码缓存
	protected $outfile = '';      //输出二维码文件
	protected $image = null;      //操作对象
	
	public function __construct() {
		//$this->config = Config::get('qrcode.');
		$this->cache_dir = rtrim(runtime_path(), '/') . '/qrcode';
		require_once('phpqrcode/qrlib.php');
	}
	
	//生成普通二维码
	public function png($data, $outfile = false, $level = 'H', $size = 300, $margin = 0) {
		if (!$outfile) {
			$outfile = $this->cache_dir . '/' . time() . '.png';
		}
		$dir = substr($outfile, 0, strrpos($outfile, '/'));
		if (!is_dir($dir)) {
			$this->makedir($dir);
		}
		$this->outfile = $outfile;
		\QRcode::png($data, $outfile, $level, $size, $margin);
		
		if (class_exists('\Imagick')) {
			$this->image = new \Imagick($this->outfile);
		} else {
			$this->image = imagecreatefromstring(file_get_contents($this->outfile));
		}
		
		return $this;
	}
	
	//循环创建目录,对应根目录
	public function makedir($target_path) {
		$root_path = rtrim(root_path(), '/');
		$target_path = trim(str_replace($root_path, '', $target_path), '/');
		if (is_dir($root_path.'/'.$target_path)) return true;
		$each_path = explode('/', $target_path);
		$cur_path = $root_path;
		$origin_mask = @umask(0);
		foreach ($each_path as $path) {
			if ($path) {
				$cur_path .= '/' . $path;
				if (!is_dir($cur_path)) {
					if (@mkdir($cur_path)) {
						@chmod($cur_path, 0777);
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
	
	//保存组合
	public function save() {
		if (class_exists('\Imagick')) {
			$this->image->writeImage($this->outfile);
		} else {
			imagepng($this->image, $this->outfile);
		}
	}
	
	//保存组合并返回二维码文件的相对路径
	public function getPath() {
		$this->save();
		return $this->outfile;
	}
	
	//直接显示二维码
	public function show() {
		header('Content-type: image/png');
		if (class_exists('\Imagick')) {
			echo $this->image;
		} else {
			imagepng($this->image);
		}
		$this->destroy();
		exit;
	}
	
	//销毁内容
	public function destroy() {
		@unlink($this->outfile);
		if (!$this->image) return;
		if (class_exists('\Imagick')) {
			$this->image->destroy();
		} else {
			imagedestroy($this->image);
		}
	}
	
	//添加logo到二维码中, width 小于 1 即按百分比计算
	public function logo($logo, $width = 0.333, $transparent = false) {
		if (!isset($logo) || !$logo) return $this;
		if (!file_exists($logo)) return $this;
		
		if (class_exists('\Imagick')) {
			$radius = 3; //圆角
			$borderWidth = 3; //边框宽度
			$logo_width = imagesx($logo); //logo宽度
			$logo_height = imagesy($logo); //logo高度
			$this->image = new \Imagick($this->outfile);
			if (strpos($logo, 'http')!==false) {
				$url = $logo;
				$logo = new \Imagick();
				$logo->readImageBlob(file_get_contents($url));
			} else {
				$logo = new \Imagick($logo);
			}
			$QR_width = $this->image->getImageWidth();
			$QR_height = $this->image->getImageHeight();
			$small_width = $width < 1 ? $QR_width * $width : $width;
			$scale = $small_width / $logo_width;
			$small_height = $logo_height * $scale;
			$from_x = ($QR_width - ($small_width+$borderWidth*2)) / 2;
			$from_y = ($QR_height - ($small_height+$borderWidth*2)) / 2;
			$logo->thumbnailImage($small_width, $small_height);
			if (method_exists($logo, 'roundCorners')) $logo->roundCorners($radius, $radius); //圆角
			$border = new \Imagick(); //边框
			$border->newImage($logo->getImageWidth()+$borderWidth*2, $logo->getImageHeight()+$borderWidth*2, 'white', strtolower($logo->getImageFormat()));
			if (method_exists($logo, 'roundCorners')) $border->roundCorners($radius, $radius);
			$border->compositeImage($logo, \Imagick::COMPOSITE_OVER, $borderWidth, $borderWidth);
			$this->image->setImageCompressionQuality(100);
			$this->image->compositeImage($border, \Imagick::COMPOSITE_OVER, $from_x, $from_y);
			$logo->destroy();
			$border->destroy();
		} else {
			$QR = imagecreatefromstring(file_get_contents($this->outfile));
			$logo = imagecreatefromstring(file_get_contents($logo));
			$QR_width = imagesx($QR); //二维码宽度
			$QR_height = imagesy($QR); //二维码高度
			$logo_width = imagesx($logo); //logo宽度
			$logo_height = imagesy($logo); //logo高度
			$small_width = $width < 1 ? $QR_width * $width : $width;
			$scale = $small_width / $logo_width;
			$small_height = $logo_height * $scale;
			$from_x = ($QR_width - $small_width) / 2;
			$from_y = ($QR_height - $small_height) / 2;
			//重新组合图片并调整大小
			$this->image = imagecreatetruecolor($QR_width, $QR_height);
			$rgb = $transparent ? 255 : 1000;
			$color = imagecolorallocate($this->image, $rgb, $rgb, $rgb); //此处3个1000可以使背景设为白色，3个255可以使背景变成透明色
			imagefill($this->image, 0, 0, $color);
			imagecopyresampled($this->image, $QR, 0, 0, 0, 0, $QR_width, $QR_height, $QR_width, $QR_height);
			//调整logo大小
			$canvas = imagecreatetruecolor($small_width, $small_height);
			imagecopyresampled($canvas, $logo, 0, 0, 0, 0, $small_width, $small_height, $logo_width, $logo_height);
			//组合图片
			imagecopymerge($this->image, $canvas, $from_x, $from_y, 0, 0, $small_width, $small_height, 100);
			imagedestroy($logo);
			imagedestroy($canvas);
		}
		return $this;
	}
	
	//添加背景图, x二维码在背景图X轴位置, y二维码在背景图Y轴位置
	public function background($dst_path, $x = 0, $y = 0) {
		if (class_exists('\Imagick')) {
			if (strpos($dst_path, 'http')!==false) {
				$dst = new \Imagick();
				$dst->readImageBlob(file_get_contents($dst_path));
			} else {
				$dst = new \Imagick($dst_path);
			}
			$dst_w = $dst->getImageWidth();
			$dst_h = $dst->getImageHeight();
			$small_width = $dst_w - $x;
			$small_height = $dst_h - $y;
			$this->image->thumbnailImage($small_width, $small_height);
			$dst->compositeImage($this->image, \Imagick::COMPOSITE_OVER, $x, $y);
			$this->image->destroy();
			$this->image = $dst;
		} else {
			$dst = imagecreatefromstring(file_get_contents($dst_path));
			$dst_w = imagesx($dst);
			$dst_h = imagesy($dst);
			//获取二维码的宽高
			$QR_width = imagesx($this->image);
			$QR_height = imagesy($this->image);
			$small_width = $dst_w - $x;
			$small_height = $dst_h - $y;
			$canvas = imagecreatetruecolor($small_width, $small_height);
			imagecopyresampled($canvas, $this->image, 0, 0, 0, 0, $small_width, $small_height, $QR_width, $QR_height);
			//组合图片
			imagecopymerge($dst, $canvas, $x, $y, 0, 0, $small_width, $small_height, 100);
			imagedestroy($canvas);
			imagedestroy($this->image);
			$this->image = $dst;
		}
		return $this;
	}
	
	//添加文字水印
	public function text($text, $size, $locate = [], $color = '#000000', $font = '', $angle = 0) {
		//创建图片的实例
		$dst = imagecreatefromstring(file_get_contents($this->outfile));
		//二维码信息
		$dst_w = imagesx($dst);
		$dst_h = imagesy($dst);
		
		/* 设置颜色 */
		if (is_string($color) && strpos($color, '#') === 0) {
			$color = str_split(substr($color, 1), 2);
			$color = array_map('hexdec', $color);
			if (empty($color[3]) || $color[3] > 127) {
				$color[3] = 0;
			}
		} else if (!is_array($color)) {
			throw new \Exception('错误的颜色值');
		}
		
		//如果字体不存在即用默认字体
		if (!$font || !is_file($font)) {
			$font =  dirname(__FILE__) . '/simsun.ttc';
		}
		
		//获取文字信息
		$info = imagettfbbox($size, $angle, $font, $text);
		$minx = min($info[0], $info[2], $info[4], $info[6]);
		$maxx = max($info[0], $info[2], $info[4], $info[6]);
		$miny = min($info[1], $info[3], $info[5], $info[7]);
		$maxy = max($info[1], $info[3], $info[5], $info[7]);
		
		//计算文字初始坐标和尺寸
		$x = $minx;
		$y = abs($miny);
		$w = $maxx - $minx;
		$h = $maxy - $miny;
		
		//设置位置
		if (is_array($locate)) {
			list($posx, $posy) = $locate;
			$x += ($posx == 'center') ? (($dst_w - $w) / 2) : ($posx == 'right' ? $dst_w - $w - $posx : $posx);
			$y += ($posy == 'center') ? (($dst_h - $h) / 2) : ($posy == 'bottom' ? $dst_h - $h - $posy : $posy);
		} else {
			throw new \Exception('不支持的文字位置类型');
		}
		
		//字体颜色
		$color = imagecolorallocate($dst, $color[0], $color[1], $color[2]);
		
		//加入文字
		imagefttext($dst, $size, $angle, $x, $y, $color, $font, $text);
		
		//生成图片
		imagepng($dst, $this->outfile);
		imagedestroy($dst);
		
		if (class_exists('\Imagick')) {
			$this->image = new \Imagick($this->outfile);
		} else {
			$this->image = imagecreatefromstring(file_get_contents($this->outfile));
		}
		
		return $this;
	}
	
}