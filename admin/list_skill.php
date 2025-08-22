<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../css/global.css" type="text/css">
	<title>skill_list</title>
	<!-- 
	游戏技能百科全书
	功能说明：
	1. 展示游戏中所有技能的详细数据
	2. 提供技能属性的快速参考和对比
	3. 显示技能学习所需点数

	页面特点：
	1. 简洁的深色主题设计，优化长时间查看体验
	2. 技能属性可视化展示：
		- 学习点数(pt)突出显示
		- 技能效果分类标记
	3. 响应式布局适应不同屏幕尺寸

	数据展示：
	1. 技能ID编号
	2. 学习所需点数
	3. 技能图标
	4. 技能名称
	5. 目标类型（敌人/友方/自身）
	6. 作用范围（单体/群体）
	7. SP消耗
	8. 技能威力
	9. 技能类型（物理/魔法）
	10. 特殊效果（快速/无效化/背击/支援等）
	11. 充能时间
	12. 技能描述

	技术实现：
	1. 动态加载技能数据文件（data.skill.php）
	2. 使用全局函数库（global.php）
	3. 自定义技能详情展示函数（ShowSkilldetail）
	4. 图标资源路径统一定义

	使用场景：
	1. 游戏开发阶段技能数据验证
	2. 游戏平衡性调整参考
	3. 玩家社区技能数据库
	4. 游戏攻略编写参考

	数据来源：
	通过PHP脚本从data.skill.php加载所有技能信息
	-->
	<style type="text/css">
		<!--
		* {
			padding: 0;
			margin: 0;
			line-height: 140%;
			font-family: Osaka, Verdana;
			overflow: inherit;
		}

		body {
			margin: 30px;
			background: #98a0a5
				/*#bfbfbf*/
			;
			color: #bdc8d7;
		}

		td {
			white-space: nowrap;
			background-color: #10151b;
			text-align: center;
			padding: 4px;
		}

		.a {
			background-color: #333333;
		}
		-->
	</style>
</head>

<body>
	<?php
	include('../setting.php');
	include('../data/data.skill.php');
	include("../class/global.php");
	define("IMG_ICON", "../image/icon/");


	print("<div style=\"text-align:left;padding:5px\" class=\"bgcolor\">\n");
	for ($no = 1000; $no < 9999; $no++) {
		$skill = LoadSkillData($no);
		if (!$skill) continue;
		print("$no:");
		print('<span class="bold">' . $skill["learn"] . '</span>pt');
		ShowSkilldetail($skill);
		print("<br />\n");
	}
	print("</div>\n");
	?>
</body>

</html>