<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../css/global.css" type="text/css">
	<title>skill_list</title>
	<!-- 
	游戏技能百科全书

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
	include_once('../setting.php');
	include_once('../data/data.skill.php');
	include_once("../class/global.php");
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