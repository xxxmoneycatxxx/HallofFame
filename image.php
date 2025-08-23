<?php

/**
 * 战斗场景图像生成器
 * 
 * 功能说明：
 * 1. 动态生成游戏战斗场景图像
 * 2. 支持自定义背景和角色位置排列
 * 3. 提供灰度滤镜等后期处理效果
 * 4. 支持调试信息输出模式
 * 
 * 此类负责根据URL参数动态生成战斗场景图像：
 * - 加载指定背景图像
 * - 排列队伍1和队伍2的前后排角色
 * - 应用滤镜效果
 * - 输出最终图像
 * 
 * 使用示例：
 * /image.php?bg=grass&f11=warrior&b12=mage&f21=monster
 */

include_once("setting.php");

$img = new image();
$img->SetBackGround("gif");  // 设置背景图像

$img->SetCharFile("gif");     // 加载角色图像
$img->ShowInfo();             // 调试信息输出(可选)
$img->CopyChar();             // 将角色复制到背景上
$img->Filter();               // 应用滤镜效果

$img->OutPutImage("gif");     // 输出最终图像

/**
 * 战斗场景图像处理类
 * 
 * 负责战斗场景的构建和渲染，支持：
 * - 多队伍角色排列（前后排位置）
 * - 角色镜像处理（敌方角色左右翻转）
 * - 灰度滤镜效果
 * - 调试信息输出
 */
class image
{
	var $image;                // GD图像资源
	var $background;           // 背景图像路径
	var $team1_front = array(); // 队伍1前排角色
	var $team1_back = array();  // 队伍1后排角色
	var $team2_front = array(); // 队伍2前排角色
	var $team2_back = array();  // 队伍2后排角色
	var $char_img_type;        // 角色图像类型(gif/jpg等)
	var $img_x, $img_y;        // 图像尺寸

	/**
	 * 加载角色图像文件
	 * 
	 * 根据URL参数识别并加载角色图像：
	 * f11-15: 队伍1前排角色
	 * b11-15: 队伍1后排角色
	 * f21-25: 队伍2前排角色
	 * b21-25: 队伍2后排角色
	 * 
	 * @param string $type 图像类型(gif/jpg/png等)
	 */
	function SetCharFile($type)
	{
		$this->char_img_type = $type;

		// 加载队伍1角色
		for ($j = 1; $j < 6; $j++) {
			// 前排角色
			if ($img = $_GET["f1" . $j]) {
				if (strpos($img, "/") !== false) continue; // 安全过滤
				$file = IMG_CHAR . $img . "." . $type;
				if (file_exists($file)) $this->team1_front[] = $file;
			}
			// 后排角色
			if ($img = $_GET["b1" . $j]) {
				if (strpos($img, "/") !== false) continue;
				$file = IMG_CHAR . $img . "." . $type;
				if (file_exists($file)) $this->team1_back[] = $file;
			}
		}

		// 加载队伍2角色（自动翻转）
		for ($j = 1; $j < 6; $j++) {
			// 前排角色
			if ($img = $_GET["f2" . $j]) {
				if (strpos($img, "/") !== false) continue;
				$file = IMG_CHAR_REV . $img . "." . $type;
				if (file_exists($file)) $this->team2_front[] = $file;
			}
			// 后排角色
			if ($img = $_GET["b2" . $j]) {
				if (strpos($img, "/") !== false) continue;
				$file = IMG_CHAR_REV . $img . "." . $type;
				if (file_exists($file)) $this->team2_back[] = $file;
			}
		}
	}

	/**
	 * 将角色复制到背景图像上
	 * 
	 * 按照游戏布局规则排列角色位置：
	 * 1. 队伍1后排 -> 左1/6位置
	 * 2. 队伍1前排 -> 左2/6位置
	 * 3. 队伍2前排 -> 右2/6位置
	 * 4. 队伍2后排 -> 右1/6位置
	 */
	function CopyChar()
	{
		$cell_width = ($this->img_x) / 6; // 将宽度分为6等分
		$y = $this->img_y / 2;           // 垂直居中基准线

		// 按位置排列各队伍角色
		$this->CopyRow($this->team1_back, 0, $cell_width * 1, $cell_width, $y, $this->img_y);
		$this->CopyRow($this->team1_front, 0, $cell_width * 2, $cell_width, $y, $this->img_y);
		$this->CopyRow($this->team2_front, 1, $cell_width * 4, $cell_width, $y, $this->img_y);
		$this->CopyRow($this->team2_back, 1, $cell_width * 5, $cell_width, $y, $this->img_y);
	}

	/**
	 * 排列一行角色
	 * 
	 * @param array $teams 角色图像路径数组
	 * @param int $direction 排列方向(0=从左向右, 1=从右向左)
	 * @param float $axis_x 基准X坐标
	 * @param float $cell_width 单元格宽度
	 * @param float $axis_y 基准Y坐标
	 * @param float $cell_height 单元格高度
	 */
	function CopyRow($teams, $direction, $axis_x, $cell_width, $axis_y, $cell_height)
	{
		$number = count($teams);
		if ($number == 0) return false;

		// 根据方向调整基准点
		$axis_x += ($direction ? -$cell_width / 2 : +$cell_width / 2);
		$axis_y += ($direction ? -$cell_height / 2 : -$cell_height / 2);

		// 计算角色间距
		$gap_x = $cell_width / ($number + 1) * ($direction ? 1 : -1);
		$gap_y = $cell_height / ($number + 1) * ($direction ? 1 : 1);
		$gap = 0;

		// 逐个复制角色
		foreach ($teams as $file) {
			$gap++;
			$x = $axis_x + ($gap_x * $gap);
			$y = $axis_y + ($gap_y * $gap);
			$this->CopyImage($file, $x, $y);
		}
	}

	/**
	 * 将单个角色图像复制到背景上
	 * 
	 * @param string $file 角色图像路径
	 * @param float $x 目标X坐标
	 * @param float $y 目标Y坐标
	 */
	function CopyImage($file, $x, $y)
	{
		// 根据文件类型创建图像资源
		$imgcreatefrom = "imagecreatefrom{$this->char_img_type}";
		$copy = $imgcreatefrom($file);

		// 获取角色尺寸并调整位置居中
		list($width, $height) = getimagesize($file);
		$x -= $width / 2;
		$y -= $height / 2;

		// 复制到主图像
		imagecopy($this->image, $copy, round($x), round($y), 0, 0, $width, $height);
	}

	/**
	 * 设置战斗背景
	 * 
	 * 根据URL参数加载背景图像，默认为草地背景
	 * 
	 * @param string $type 图像类型(gif/jpg/png等)
	 */
	function SetBackGround($type)
	{
		// 获取指定背景或使用默认
		if ($_GET["bg"]) {
			$file = IMG_OTHER . "bg_" . $_GET["bg"] . "." . $type;
		}

		// 验证背景文件存在
		if (file_exists($file)) {
			$this->background = $file;
		} else {
			$this->background = IMG_OTHER . "bg_grass." . $type;
		}

		// 创建图像资源并获取尺寸
		$func = "imagecreatefrom" . $type;
		$this->image = $func($this->background);
		list($this->img_x, $this->img_y) = getimagesize($this->background);
	}

	/**
	 * 应用图像滤镜效果
	 * 
	 * 当前支持灰度滤镜，通过gray参数控制灰度程度(0-100)
	 */
	function Filter()
	{
		if ($_GET["gray"]) {
			$val = $_GET["gray"];
			// 限制灰度值范围
			if ($val < 0) $val = 0;
			else if (100 < $val) $val = 100;

			// 应用灰度滤镜
			imagecopymergegray(
				$this->image,
				$this->image,
				0,
				0,
				0,
				0,
				$this->img_x,
				$this->img_y,
				$val
			);
		}
	}

	/**
	 * 输出最终图像
	 * 
	 * @param string $type 输出图像类型(gif/jpg/png等)
	 */
	function OutPutImage($type)
	{
		$func = "image" . $type;
		$func($this->image);       // 输出图像
		header("Content-Type: image/{$type}"); // 设置HTTP头
		imagedestroy($this->image); // 释放资源
	}

	/**
	 * 显示调试信息
	 * 
	 * 当URL包含info参数时，输出配置信息而非图像
	 */
	function ShowInfo()
	{
		if (!$_GET["info"]) return true;

		// 创建调试信息图像
		$image = imagecreate(360, 240);
		$bg = imagecolorallocate($image, 24, 24, 128);
		$textcolor = imagecolorallocate($image, 255, 24, 255);

		// 设置文本参数
		$size = 2;
		$height = 14;
		$mar_l = 6;
		$mar_t = 6;

		// 输出调试信息
		imagestring($image, $size, $mar_l, $mar_t, "info-", $textcolor);
		imagestring($image, $size, $mar_l, $mar_t + $height, "BG : " . $this->background, $textcolor);

		// 列出所有加载的角色
		$row = 2;
		$teams = array(
			"team1_front" => "TEAM1_F",
			"team1_back" => "TEAM1_B",
			"team2_front" => "TEAM2_F",
			"team2_back" => "TEAM2_B"
		);

		foreach ($teams as $team_var => $team_pos) {
			foreach ($this->{$team_var} as $val) {
				imagestring($image, $size, $mar_l, $mar_t + $height * $row, "$team_pos : " . $val, $textcolor);
				$row++;
			}
		}

		// 输出调试图像
		header("Content-type: image/gif");
		imagepng($image);
		exit();
	}
}
