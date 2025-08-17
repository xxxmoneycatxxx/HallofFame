<?php

/**
 * 怪物信息展示页面
 * 
 * 功能说明：
 * 1. 展示游戏中各种怪物的详细数据
 * 2. 提供怪物属性表格和文字描述
 * 3. 支持按特定顺序展示怪物信息
 * 
 * 页面结构：
 * 1. 包含怪物数据文件：加载怪物基础数据
 * 2. 定义怪物列表数组：包含怪物ID和对应的场景/描述信息
 * 3. 创建属性表格头：显示怪物各项属性名称
 * 4. 循环展示怪物：为每个怪物创建详细数据行和描述行
 * 
 * 数据展示内容：
 * 1. 怪物形象：使用char类展示怪物图像
 * 2. 基础属性：
 *    - EXP: 击败后获得的经验值
 *    - MONEY: 击败后获得的金钱
 *    - HP: 生命值
 *    - SP: 法力值
 *    - STR: 力量
 *    - INT: 智力
 *    - DEX: 敏捷
 *    - SPD: 速度
 *    - LUK: 幸运
 * 3. 怪物描述：提供战斗策略和特性说明
 * 
 * 技术细节：
 * 1. 使用DATA_MONSTER常量指向怪物数据文件路径
 * 2. CreateMonster函数根据怪物ID创建怪物数据
 * 3. char类用于处理和展示怪物形象
 * 4. $List数组定义要展示的怪物ID及其描述信息
 * 
 * 页面样式：
 * 1. 使用15px的外边距创建舒适的浏览空间
 * 2. 表格宽度固定为740px
 * 3. 使用交替行样式(td6/td7/td8)增强可读性
 * 4. 属性表格采用居中对齐
 * 
 * 注意事项：
 * 1. 需要确保DATA_MONSTER常量正确定义
 * 2. CreateMonster和char类需提前定义
 * 3. $List数组可根据游戏内容扩展
 * 4. 页面顶部注释提到可能存在显示问题
 */

/*
	どっかおかしくて茶咙山绩されてないので涩妥ならば木して
*/
include_once(DATA_MONSTER);
?>
<div style="margin:0 15px">
	<h4>モンスタ〖</h4>
	<table class="align-center" style="width:740px" cellspacing="0">
		<?php
		$List	= array(
			1000	=> array("grass", "SPがあるときは、強い攻撃をたまにしてくる程度。"),
			1001	=> array("grass", "SPがあるときは、強い攻撃をたまにしてくる程度。"),
			1002	=> array("grass", "後列に押し出す攻撃をする。"),
			1003	=> array("grass", "そこそこな強さ。"),
			1005	=> array("grass", "レベルが低いと強く感じる。"),
			1009	=> array("grass", "HPが高い。"),
			1012	=> array("cave", "仲間を呼んで吸血攻撃をしてくる。"),
			1014	=> array("cave", "魔法で攻撃しないと倒しにくい。"),
			1017	=> array("cave", "洞窟のボス。倒すと奥に行けるようになる。"),
		);
		$Detail	= "<tr>
<td class=\"td6\">Image</td>
<td class=\"td6\">EXP</td>
<td class=\"td6\">MONEY</td>
<td class=\"td6\">HP</td>
<td class=\"td6\">SP</td>
<td class=\"td6\">STR</td>
<td class=\"td6\">INT</td>
<td class=\"td6\">DEX</td>
<td class=\"td6\">SPD</td>
<td class=\"td6\">LUK</td>
</tr>";
		foreach ($List as $No => $exp) {
			$monster	= CreateMonster($No);
			$char	= new char($monster);
			print($Detail);
			print("</td><td class=\"td7\">\n");
			//print('<img src="'.IMG_CHAR.$monster["img"].'" />'."\n");
			$char->ShowCharWithLand($exp[0]);
			print("</td><td class=\"td7\">\n");
			print("{$monster[exphold]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[moneyhold]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[maxhp]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[maxsp]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[str]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[int]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[dex]}\n");
			print("</td><td class=\"td7\">\n");
			print("{$monster[spd]}\n");
			print("</td><td class=\"td8\">\n");
			print("{$monster[luk]}\n");
			print("</td></tr>\n");
			print("<tr><td class=\"td7\" colspan=\"11\">\n");
			print("$exp[1]");
			print("</td></tr>\n");
		}
		?>
	</table>
</div>