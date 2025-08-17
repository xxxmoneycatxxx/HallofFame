<?php

/**
 * 角色基础职业生成函数
 * 
 * 功能说明：
 * 1. 根据职业编号生成预设的角色基础属性
 * 2. 提供战士、法师、牧师和猎人四种职业的初始配置
 * 3. 自动生成角色创建时间戳
 * 
 * 参数说明：
 * @param string|int $no 职业编号
 *   - "1": 战士
 *   - "2": 法师
 *   - "3": 牧师
 *   - "4": 猎人
 * 
 * 返回说明：
 * @return array 包含角色基础属性的关联数组，结构如下：
 *   [
 *     'level' => int,       // 初始等级
 *     'exp' => int,         // 初始经验值
 *     'maxhp' => int,       // 最大生命值
 *     'hp' => int,          // 当前生命值
 *     'maxsp' => int,       // 最大法力值
 *     'sp' => int,          // 当前法力值
 *     'str' => int,         // 力量属性
 *     'int' => int,         // 智力属性
 *     'dex' => int,         // 敏捷属性
 *     'spd' => int,         // 速度属性
 *     'luk' => int,         // 幸运属性
 *     'job' => int,         // 职业编号
 *     'weapon' => int,      // 初始武器ID
 *     'shield' => int,      // 初始盾牌ID（战士特有）
 *     'armor' => int,       // 初始护甲ID
 *     'skill' => array,     // 初始技能ID数组
 *     'Pattern' => string,   // 战斗行为模式
 *     'position' => string,  // 默认站位（front/back）
 *     'guard' => string,     // 防御策略
 *     'birth' => float       // 角色创建时间戳（微秒精度）
 *   ]
 * 
 * 职业特性说明：
 * 1. 战士：高生命值，前排坦克，物理输出
 * 2. 法师：高法力值，后排法术输出
 * 3. 牧师：平衡型，后排治疗支援
 * 4. 猎人：高敏捷，后排物理输出
 * 
 * 特殊字段说明：
 * - Pattern：战斗AI模式，格式为"动作序列"，使用"<>"分隔回合行为，"|"分隔不同回合
 * - position：角色站位，决定在战斗中的前后排位置
 * - guard：防御策略（always=总是防御，never=从不防御）
 * 
 * 注意事项：
 * 1. 所有职业都会自动添加'birth'字段作为唯一标识
 * 2. 未列出的职业编号将返回空数组
 */
function BaseCharStatus($no)
{
	switch ($no) {
		case "1": //战士
			$stat	= array(
				"level"	=> "1",
				"exp"	=> "0",
				"maxhp"	=> "300",
				"hp"	=> "300",
				"maxsp"	=> "50",
				"sp"	=> "50",
				"str"	=> "10",
				"int"	=> "2",
				"dex"	=> "4",
				"spd"	=> "4",
				"luk"	=> "1",
				"job"	=> "100",
				"weapon" => "1000",
				"shield" => "3000",
				"armor"	=> "5000",
				"skill"	=> array(1000, 1001),
				"Pattern" => "1205<>1000|8<>0|1001<>1000",
				"position"	=> "front",
				"guard"	=> "always",
			);
			break;
		case "2": //法师
			$stat	= array(
				"level"	=> "1",
				"exp"	=> "0",
				"maxhp"	=> "150",
				"hp"	=> "150",
				"maxsp"	=> "100",
				"sp"	=> "100",
				"str"	=> "2",
				"int"	=> "10",
				"dex"	=> "5",
				"spd"	=> "3",
				"luk"	=> "1",
				"job"	=> "200",
				"weapon" => "1700",
				"armor"	=> "5200",
				"skill"	=> array(1000, 1002, 3010),
				"Pattern" => "1206<>1000<>1000|20<>0<>0|3010<>1002<>1000",
				"position"	=> "back",
				"guard"	=> "never",
			);
			break;
		case "3": //牧师
			$stat	= array(
				"level"	=> "1",
				"exp"	=> "0",
				"maxhp"	=> "200",
				"hp"	=> "200",
				"maxsp"	=> "80",
				"sp"	=> "80",
				"str"	=> "3",
				"int"	=> "8",
				"dex"	=> "5",
				"spd"	=> "4",
				"luk"	=> "1",
				"job"	=> "300",
				"weapon" => "1700",
				"armor"	=> "5200",
				"skill"	=> array(1000, 3000, 3101),
				"Pattern" => "1121<>1000|70<>0|3000<>3101",
				"position"	=> "back",
				"guard"	=> "never",
			);
			break;
		case "4": //猎人
			$stat	= array(
				"level"	=> "1",
				"exp"	=> "0",
				"str"	=> "2",
				"int"	=> "2",
				"dex"	=> "10",
				"spd"	=> "6",
				"luk"	=> "1",
				"job"	=> "400",
				"weapon" => "2000",
				"armor"	=> "5100",
				"skill"	=> array(2300, 2310),
				"Pattern" => "1205<>1000|28<>0|2310<>2300",
				"position"	=> "back",
				"guard"	=> "never",
			);
			break;
	}

	$stat	+= array("birth" => time() . substr(microtime(), 2, 6));
	return $stat;
}
