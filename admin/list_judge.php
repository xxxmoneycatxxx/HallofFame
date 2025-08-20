<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>JudgeLists</title>
	<!-- 
	游戏判定条件列表生成器
	功能说明：
	1. 自动生成游戏中的判定条件ID数组
	2. 为游戏开发提供可用的判定条件列表
	3. 输出格式化的PHP数组代码

	页面逻辑：
	1. 加载判定条件设置数据文件
	2. 遍历所有可能的判定ID（1000-9999）
	3. 对每个有效判定条件输出：
		- case语句（带注释说明）
		- 数组元素
	4. 生成格式化的PHP数组代码

	输出说明：
	1. 第一部分：case语句列表（可直接用于switch-case结构）
	2. 第二部分：PHP数组声明（每行5个元素）
	
	使用场景：
	1. 游戏开发阶段快速获取所有可用判定条件
	2. 游戏系统升级时验证判定条件完整性
	3. 调试游戏逻辑时查看可用判定选项
	-->
</head>

<body>
	<?php
	include("../data/data.judge_setup.php");
	for ($i = 1000; $i < 9999; $i++) {
		$j	= LoadJudgeData($i);
		if ($j) {
			print("case {$i}:// {$j["exp"]}<br />");
			$list[]	= $i;
		}
	}
	print("array(<br />\n");
	foreach ($list as $var) {
		$A++;
		print("$var, ");
		if ($A % 5 == 0)
			print("<br />\n");
	}
	print("<br />\n);");
	?>
</body>

</html>