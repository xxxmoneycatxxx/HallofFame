<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../css/basis.css" type="text/css">
	<title>JOB List</title>
	<!-- 
	游戏职业详情列表页面
	功能说明：
	1. 展示游戏中所有职业的详细数据
	2. 显示每个职业可学习的技能及其详细属性
	3. 提供职业属性的直观对比

	页面结构：
	1. 顶部标题和样式定义
	2. 表格展示职业数据，每行包含：
		- 职业ID
		- 职业名称
		- 属性加成系数
		- 职业图标（男性和女性）
		- 可装备道具类型
	3. 技能展示区域，包含：
		- 技能图标
		- 技能名称
		- 目标类型（敌人/友方/自身）
		- 技能威力
		- 特殊效果（支援、优先攻击等）
		- SP消耗
		- 学习条件

	样式特点：
	1. 深色背景配合亮色文字，提高可读性
	2. 使用颜色编码区分技能类型（伤害/治疗/支援）
	3. 响应式设计，适应不同屏幕尺寸

	数据来源：
	通过PHP脚本从data.job.php和data.skill.php加载职业和技能信息
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

		.b {
			text-align: left;
		}

		.a {
			background-color: #333333;
		}
		-->
	</style>
</head>

<body>
	<?php
	include("../data/data.job.php");
	include("../data/data.skill.php");

	$det	= '<tr><td class="a">No</td>
<td class="a">Name</td>
<td class="a">IMG</td>
<td class="a">SP</td>
<td class="a">type</td>
<td class="a">lrn</td>
<td class="a">Target</td>
<td class="a">pow</td>
<td class="a">hit</td>
<td class="a">invalid</td>
<td class="a">support</td>
<td class="a">priority</td>
<td class="a">charge</td>
<td class="a">exp</td></tr>' . "\n";
	$img_f	= "../image/char/";

	print('<table border="0" cellspacing="1"><tbody>');
	//print($det);
	$detcount = 0;
	for ($no = 100; $no < 999; $no++) {
		$j = LoadJobData($no);
		if (!$j) continue;

		$detcount++;
		//if($detcount%10==0) print($det);

		print("<tr>");
		print("<td>{$no}</td>"); //no
		print("<td>{$j[name_male]}</td>"); //name
		print("<td>{$j[coe][0]} : {$j[coe][1]}</td>"); //name
		print("<td><img src=\"{$img_f}{$j[img_male]}\"><img src=\"{$img_f}{$j[img_female]}\"></td>"); //no
		print("</tr>\n");
		print("<tr>");
		print("<td colspan=\"4\">");
		foreach ($j[equip] as $i)
			print("$i, ");
		print("</td>");
		print("</tr>\n");
		// 習得技
		if ($j[learn]) {
			print("<tr><td colspan=\"4\">");
			print('<table><tbody>');
			foreach ($j[learn] as $skill) {
				print("<tr><td class=\"b\">");
				$skill	= LoadSKillData($skill);
				ShowSkillDetail($skill);
				print("</td></tr>");
			}
			print("</tbody></table>");
			print("</div></td></tr>");
		}
	}
	//print($det);
	print("</tbody></table>");
	?>
</body>

</html>
<?php
//////////////////////////////////////////////////
//	技の詳細を表示
function ShowSkillDetail($skill, $radio = false)
{

	define("IMG_ICON", "../image/icon/");

	if (!$skill) return false;

	if ($radio)
		print('<input type="radio" name="newskill" value="' . $skill["no"] . '" class="vcent">');

	print('<img src="' . IMG_ICON . $skill["img"] . '" class="vcent">');
	print("{$skill[name]}");

	if ($radio)
		print(" / <span class=\"bold\">{$skill[learn]}</span>pt");

	if ($skill[target][0] == "all") //対象
		print(" / <span class=\"charge\">{$skill[target][0]}</span>");
	else if ($skill[target][0] == "enemy")
		print(" / <span class=\"dmg\">{$skill[target][0]}</span>");
	else if ($skill[target][0] == "friend")
		print(" / <span class=\"recover\">{$skill[target][0]}</span>");
	else if ($skill[target][0] == "self")
		print(" / <span class=\"support\">{$skill[target][0]}</span>");
	else if (isset($skill[target][0]))
		print(" / {$skill[target][0]}");

	if ($skill[target][1] == "all") //単体or複数or全体
		print(" - <span class=\"charge\">{$skill[target][1]}</span>");
	else if ($skill[target][1] == "individual")
		print(" - <span class=\"recover\">{$skill[target][1]}</span>");
	else if ($skill[target][1] == "multi")
		print(" - <span class=\"spdmg\">{$skill[target][1]}</span>");
	else if (isset($skill[target][1]))
		print(" - {$skill[target][1]}");

	if (isset($skill["sp"]))
		print(" / <span class=\"support\">{$skill[sp]}sp</span>");
	if ($skill["pow"]) {
		print(" / <span class=\"" . ($skill["support"] ? "recover" : "dmg") . "\">{$skill[pow]}%</span>x");
		print(($skill["target"][2] ? $skill["target"][2] : "1"));
	}
	if ($skill["type"] == 1)
		print(" / <span class=\"spdmg\">Magic</span>");
	if ($skill["invalid"])
		print(" / <span class=\"charge\">invalid</span>");
	if ($skill["priority"] == "Back")
		print(" / <span class=\"support\">BackAttack</span>");
	if ($skill["support"])
		print(" / <span class=\"charge\">support</span>");
	if ($skill["charge"]["0"] || $skill["charge"]["1"]) {
		print(" / (" . ($skill["charge"]["0"] ? $skill["charge"]["0"] : "0") . ":");
		print(($skill["charge"]["1"] ? $skill["charge"]["1"] : "0") . ")");
	}
	if ($skill["exp"])
		print(" / {$skill[exp]}");
	print("\n");
}
?>