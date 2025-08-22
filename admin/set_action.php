<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>SetAction</title>
	<!-- 
	怪物行动模式设置工具
	功能说明：
	1. 为游戏中的怪物配置复杂的战斗AI行为
	2. 创建和编辑怪物的行动模式序列
	3. 支持从现有怪物模板加载配置
	4. 生成可直接使用的游戏数据代码

	主要功能模块：
	1. 模式序列设置：
		- 可配置最多8个行动模式（可扩展）
		- 每行包含：判定条件、数值参数、执行技能
	2. 模板管理：
		- 通过怪物ID加载预设行动模式
		- 即时预览加载的怪物名称和形象
	3. 序列编辑：
		- 添加新行动序列行
		- 删除指定序列行
		- 调整序列数量
	4. 代码生成：
		- 一键生成PHP格式的行动模式代码
		- 直接复制到游戏数据文件中使用

	技术实现：
	1. 动态加载游戏数据：
		- 判定条件数据（data.judge_setup.php）
		- 技能数据（data.skill.php）
		- 怪物数据（data.monster.php）
	2. 响应式表单设计：
		- 自动保存表单状态
		- 支持添加/删除序列行
	3. PHP数据处理：
		- 序列化行动模式数据
		- 生成格式化的PHP数组代码

	使用流程：
	1. 设置行动模式的行数（默认8行）
	2. 为每行选择判定条件、输入数值、选择技能
	3. 可选：加载现有怪物模板作为起点
	4. 使用添加/删除按钮调整序列
	5. 点击"创建"生成最终代码
	-->
	<style type="text/css">
		<!--
		* {
			line-height: 140%;
			font-family: Osaka, Verdana;
		}

		.bg {
			background-color: #cccccc;
		}

		body {
			background-color: #666666;
		}

		option {
			background-color: #dddddd;
		}

		input {
			background-color: #dddddd;
		}
		-->
	</style>
</head>

<body>
	<?php
	function UserAmount()
	{
		return 1;
	}

	// 行数
	define("ROWS", $_POST["patternNum"] ? $_POST["patternNum"] : 8);
    const IMG = "../image/char/";

	// Load
	if ($_POST["Load"] && $_POST["loadMob"]) {
		include("../data/data.monster.php");
		$monster	= CreateMonster($_POST["loadMob"]);
		if ($monster) {
			for ($i = 0; $i < ROWS; $i++) {
				$_POST["judge" . $i]		= $monster["judge"][$i] ? $monster["judge"][$i] : NULL;
				$_POST["quantity" . $i]	= $monster["quantity"][$i] ? $monster["quantity"][$i] : NULL;
				$_POST["skill" . $i]		= $monster["action"][$i] ? $monster["action"][$i] : NULL;
			}
		}
		print('<span style="font-weight:bold">' . $_POST["loadMob"] . " " . $monster["name"] . '</span><img src="' . IMG . $monster["img"] . '" />');
	}

	// Add
	if ($_POST["add"] && isset($_POST["number"])) {
		$number	= $_POST["number"];
		$var	= array("judge", "quantity", "skill");
		foreach ($var as $head) {
			for ($i = ROWS; -1 < $i; $i--) {
				if ($number == $i)
					$_POST[$head . $i]	= NULL;
				else if ($number < $i)
					$_POST[$head . $i]	= $_POST[$head . ($i - 1)];
				else
					break;
			}
		}
	}

	// Delete
	if ($_POST["delete"] && isset($_POST["number"])) {
		$number	= $_POST["number"];
		$var	= array("judge", "quantity", "skill");
		foreach ($var as $head) {
			for ($i = 0; $i < ROWS; $i++) {
				if ($number <= $i)
					$_POST[$head . $i]	= $_POST[$head . ($i + 1)];
			}
		}
	}

	// TEXTAREA
	if ($_POST["make"]) {
		$judgeString	= '"judge"	=> array(';
		$quantityString	= '"quantity"	=> array(';
		$skillString	= '"action"	=> array(';
		for ($i = 0; $i < ROWS; $i++) {
			if ($_POST["judge" . $i] && $_POST["skill" . $i]) {
				$judgeString	.= $_POST["judge" . $i] . ", ";
				$quantityString	.= $_POST["quantity" . $i] . ", ";
				$skillString	.= $_POST["skill" . $i] . ", ";
			}
		}
		$judgeString	.= "),\n";
		$quantityString	.= "),\n";
		$skillString	.= "),\n";


		print('<textarea style="width:800px;height:100px">');
		print($judgeString . $quantityString . $skillString);
		print("</textarea>\n");
	}
	// 判定の種類
	include("../data/data.judge_setup.php");
	for ($i = 1000; $i < 10000; $i++) {
		$judge	= LoadJudgeData($i);
		if (!$judge)
			continue;
		$judgeList["$i"]["exp"]	= $judge["exp"];
		if ($judge["css"])
			$judgeList["$i"]["css"]	= true;
	}

	// 全スキル
	include("../data/data.skill.php");
	for ($i = 1000; $i < 10000; $i++) {
		$skill	= LoadSkillData($i);
		if (!$skill)
			continue;
		$skillList["$i"]	= $i . " - " . $skill["name"] . "(sp:{$skill["sp"]})";
	}

	print('<form method="post" action="?">' . "\n");
	print("<table>\n");
	for ($i = 0; $i < ROWS; $i++) {
		print("<tr><td>\n");
		print('<span style="font-weight:bold">' . sprintf("%2s", $i + 1) . "</span>");
		print("</td><td>\n");
		// 判定リスト
		print('<select name="judge' . $i . '">' . "\n");
		print('<option></option>' . "\n");
		foreach ($judgeList as $key => $exp) {
			$css	= $exp["css"] ? ' class="bg"' : NULL;
			if ($_POST["judge" . $i] == $key)
				print('<option value="' . $key . '"' . $css . 'selected>' . $exp["exp"] . '</option>' . "\n");
			else
				print('<option value="' . $key . '"' . $css . '>' . $exp["exp"] . '</option>' . "\n");
		}
		print("</select>\n");
		print("</td><td>\n");
		// 数値
		print('<input type="text" name="quantity' . $i . '" value="' . ($_POST["quantity" . $i] ? $_POST["quantity" . $i] : "0") . '" size="10" />' . "\n");
		print("</td><td>\n");
		// 技
		print('<select name="skill' . $i . '">' . "\n");
		print('<option></option>' . "\n");
		foreach ($skillList as $key => $exp) {
			if ($_POST["skill" . $i] == $key)
				print('<option value="' . $key . '" selected>' . $exp . '</option>' . "\n");
			else
				print('<option value="' . $key . '">' . $exp . '</option>' . "\n");
		}
		print("</select>\n");
		print("</td><td>\n");
		print('<input type="radio" name="number" value="' . $i . '">' . "\n");
		print("</td></tr>\n");
	}
	print("</table>\n");
	print('判定次数: <input type="text" name="patternNum" size="10" value="' . ($_POST["patternNum"] ? $_POST["patternNum"] : "8") . '" /><br />' . "\n");
	print('<input type="submit" value="创建" name="make">' . "\n");
	print('<input type="hidden" value="make" name="make">' . "\n");
	print('<input type="submit" value="添加" name="add">' . "\n");
	print('<input type="submit" value="删除" name="delete"><br />' . "\n");
	print('输入怪物id: <input type="text" name="loadMob" size="10" /> <input type="submit" value="读取" name="Load" />');
	print("</form>\n");
	?>
</body>

</html>