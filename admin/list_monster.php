<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Monster List</title>
	<link rel="stylesheet" href="../css/global.css" type="text/css">
	<!--
	游戏怪物百科全书
	功能说明：
	1. 展示游戏中所有怪物的详细数据
	2. 显示怪物的属性、技能和掉落物品
	3. 提供怪物数据的完整参考

	页面结构：
	1. 响应式表格展示怪物核心属性：
		- 基础属性：ID、名称、等级、图标
		- 战斗属性：经验值、金钱、HP/SP、攻击/防御
		- 状态属性：力量、智力、敏捷等
		- 位置和保护设置
	2. 技能展示区：
		- 显示怪物的所有技能及详细参数
		- 技能效果可视化展示
	3. 掉落物品展示区：
		- 显示怪物掉落的物品及掉落概率
		- 物品详情展示

	样式特点：
	1. 深色主题设计，减少长时间查看的视觉疲劳
	2. 使用颜色编码区分属性类型：
		- 攻击属性：红色系
		- 防御属性：蓝色系
		- 特殊效果：紫色系
	3. 响应式布局适应不同屏幕尺寸
	4. 层次化结构清晰展示复杂数据

	数据来源：
	1. data.monster.php - 怪物基础数据
	2. data.judge_setup.php - 技能判定数据
	3. data.skill.php - 技能效果数据
	4. data.item.php - 物品数据
	5. data.enchant.php - 装备附魔数据

	技术实现：
	1. PHP动态生成怪物数据表格
	2. 自定义函数渲染技能和物品详情
	3. 嵌套表格结构展示复杂关系
    -->
	<style type="text/css">
		<!--
		* {
			padding: 0;
			margin: 0;
			line-height: 140%;
			font-family: Osaka, Verdana, "ＭＳ Ｐゴシック";
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
			text-align: left;
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
	include_once("../data/data.monster.php");
	include_once("../data/data.judge_setup.php");
	include_once("../data/data.skill.php");
	include_once("../data/data.item.php");
	include_once("../data/data.enchant.php");
	include_once("../class/global.php");
	define("IMG_ICON", "../image/icon/");

	$det	= '<tr><td class="a">ID</td>
		<td class="a">名称</td>
		<td class="a">Lv</td>
		<td class="a">图</td>
		<td class="a">经验值</td>
		<td class="a">钱</td>
		<td class="a">hp</td>
		<td class="a">sp</td>
		<td class="a">atk</td>
		<td class="a">def</td>
		<td class="a">str / int / dex / spd / luk</td>
		<td class="a">位置</td>
		<td class="a">保护</td>' . "\n";
	$img_f	= "../image/char/";

	print('<table border="0" cellspacing="1"><tbody>');
	$detcount = 0;
	for ($no = 1000; $no < 5999; $no++) {
		$m = CreateMonster($no);
		if (!$m) continue;

		//if($detcount%3==0) 
		//$detcount++;
		print($det);
		print("<tr>");
		print("<td>{$no}</td>"); //no
		print("<td>{$m["name"]}</td>"); //name
		print("<td>{$m["level"]}</td>"); //name
		print("<td><img src=\"$img_f{$m["img"]}\">$img_f{$m["img"]}</td>"); //img
		print("<td>{$m["exphold"]}</td>"); //exp
		print("<td>{$m["moneyhold"]}</td>"); //money
		print("<td>{$m["hp"]}/{$m["maxhp"]}</td>"); //hp
		print("<td>{$m["sp"]}/{$m["maxsp"]}</td>"); //sp
		print("<td>{$m["atk"][0]}<br />{$m["atk"][1]}</td>"); //atk
		print("<td>{$m["def"][0]}+{$m["def"][1]}<br />{$m["def"][2]}+{$m["def"][3]}</td>"); //def
		print("<td>{$m["str"]} / {$m["int"]} / {$m["dex"]} / {$m["spd"]} / {$m["luk"]}</td>"); //status
		if ($m["posed"])
			print("<td>-</td>"); //position
		else
			print("<td>{$m["position"]}</td>"); //position
		print("<td>{$m["guard"]}</td>"); //guard
		// 行動手順
		print("</tr>\n");
		print("<tr><td colspan=\"13\" style=\"text-align:left\">");
		print("<table><tbody>");
		foreach ($m["judge"] as $key => $val) {
			print("<tr><td>");
			$judge	= LoadJudgeData($val);
			print($judge["exp"]);
			print("</td><td>");
			print($m["quantity"]["$key"]);
			print("</td><td>");
			$skill	= LoadSkillData($m["action"]["$key"]);
			//print($skill[name]);
			ShowSkillDetail($skill);
			print("</td></tr>");
		}
		// 落とすアイテム
		if ($m["itemtable"]) {
			print('<tr><td colspan="3">');
			print("<table><tbody>");
			$dif	= 0;
			foreach ($m["itemtable"] as $itemno => $prob) {
				print("<tr><td>");
				print(($prob / 100) . "%");
				print("</td><td>");
				$item	= LoadItemdata($itemno);
				ShowItemDetail($item);
				print("</td></tr>");
			}
			print("</tbody></table>");
			print("</td></tr>");
		}
		print("</tbody></table>");
		print("</td></tr>\n");
	}
	print($det);
	print("</tbody></table>");
	?>
</body>

</html>
<?php
//////////////////////////////////////////////////
//	
function ShowItemDetail2($item, $amount = false)
{
	$file	= "../image/icon/";
	if (!$item) return false;

	print("\n");

	print("<img src=\"" . $file . $item["img"] . "\" class=\"vcent\">{$item["name"]}");

	if ($item["type"])
		print("<span class=\"light\"> ({$item["type"]})</span>");
	if ($amount) { //数量
		print(" x{$amount}");
	}
	if ($item["atk"]["0"]) //物理攻撃
		print(' / <span class="dmg">Atk:' . $item["atk"][0] . '</span>');
	if ($item["atk"]["1"]) //魔法攻撃
		print(' / <span class="spdmg">Matk:' . $item["atk"][1] . '</span>');
	if ($item["def"]) {
		print(" / <span class=\"recover\">Def:{$item["def"][0]}+{$item["def"][1]}</span>");
		print(" / <span class=\"support\">Mdef:{$item["def"][2]}+{$item["def"][3]}</span>");
	}
	if ($item["handle"])
		print(' / <span class="charge">h:' . $item["handle"] . '</span>');
	//print("\n");//なんでバグるん？(IE6)
}
//	技の詳細を表示
function ShowSkillDetail2($skill, $radio = false)
{
	$file	= "../../image/icon/";
	if (!$skill) return false;

	if ($radio)
		print('<input type="radio" name="newskill" value="' . $skill["no"] . '" class="vcent">');

	print('<img src="' . $file . $skill["img"] . '" class="vcent">');
	print("{$skill["name"]}");

	if ($radio)
		print(" / <span class=\"bold\">{$skill["learn"]}</span>pt");

	if ($skill["target"][0] == "all") //対象
		print(" / <span class=\"charge\">{$skill["target"][0]}</span>");
	else if ($skill["target"][0] == "enemy")
		print(" / <span class=\"dmg\">{$skill["target"][0]}</span>");
	else if ($skill["target"][0] == "friend")
		print(" / <span class=\"recover\">{$skill["target"][0]}</span>");
	else if ($skill["target"][0] == "self")
		print(" / <span class=\"support\">{$skill["target"][0]}</span>");
	else if (isset($skill["target"][0]))
		print(" / {$skill["target"][0]}");

	if ($skill["target"][1] == "all") //単体or複数or全体
		print(" - <span class=\"charge\">{$skill["target"][1]}</span>");
	else if ($skill["target"][1] == "individual")
		print(" - <span class=\"recover\">{$skill["target"][1]}</span>");
	else if ($skill["target"][1] == "multi")
		print(" - <span class=\"spdmg\">{$skill["target"][1]}</span>");
	else if (isset($skill["target"][1]))
		print(" - {$skill["target"][1]}");

	if (isset($skill["sp"]))
		print(" / <span class=\"support\">{$skill["sp"]}sp</span>");
	if ($skill["pow"]) {
		print(" / <span class=\"" . ($skill["support"] ? "recover" : "dmg") . "\">{$skill["pow"]}%</span>x");
		print(($skill["target"][2] ? $skill["target"][2] : "1"));
	}
	if ($skill["type"] == 1)
		print(" / <span class=\"spdmg\">Magic</span>");
	if ($skill["quick"])
		print(" / <span class=\"charge\">Quick</span>");
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
		print(" / {$skill["exp"]}");
	print("\n");
}
?>