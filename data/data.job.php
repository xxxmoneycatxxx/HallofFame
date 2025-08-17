<?php

/**
 * 职业数据加载函数
 * 
 * 功能说明：
 * 1. 根据职业ID加载完整的职业数据
 * 2. 提供职业的性别区分名称和形象
 * 3. 定义职业的可装备物品类型
 * 4. 设定职业的HP/SP成长系数
 * 5. 指定可转职的职业路径
 * 
 * 参数说明：
 * @param string $no 职业ID
 * 
 * 返回说明：
 * @return array 职业数据数组，包含以下字段：
 *   - name_male: 男性职业名称
 *   - name_female: 女性职业名称
 *   - img_male: 男性职业形象图片
 *   - img_female: 女性职业形象图片
 *   - equip: 可装备物品类型数组
 *   - coe: HP/SP成长系数数组 [HP系数, SP系数]
 *   - change: 可转职的职业ID数组
 * 
 * 职业分类说明：
 * 1. 战士系(100):
 *   - 100: 战士(基础)
 *   - 101: 皇家卫士(进阶)
 *   - 102: 狂战士(进阶)
 *   - 103: 魔女狩(进阶)
 * 
 * 2. 法师系(200):
 *   - 200: 巫师(基础)
 *   - 201: 术士(进阶)
 *   - 202: 召唤师(进阶)
 *   - 203: 死灵法师(进阶)
 * 
 * 3. 牧师系(300):
 *   - 300: 牧师/女祭司(基础)
 *   - 301: 主教(进阶)
 *   - 302: 德鲁伊(进阶)
 * 
 * 4. 猎人系(400):
 *   - 400: 猎人(基础)
 *   - 401: 神射手(进阶)
 *   - 402: 驯兽师(进阶)
 *   - 403: 刺客(进阶)
 * 
 * 职业特性说明：
 * 1. 形象区分：每个职业都有男女不同的名称和形象
 * 2. 装备限制：定义职业可装备的物品类型（如剑/弓/魔杖等）
 * 3. 成长系数：决定角色HP/SP的基础成长值
 * 4. 转职路径：指定可进阶的职业方向
 * 
 * 注意事项：
 * 1. 职业数据通过switch语句硬编码定义
 * 2. 基础职业通常有多个进阶方向
 * 3. 进阶职业通常没有进一步的转职路径
 * 
 * else？
 * 职业性别名
 * 职业的图像
 * 可以装备物
 * "coe"	=> array(HP系数 ,SP系数),
 * "change"	=> array(可以转的职),
 */
function LoadJobData($no)
{
	switch ($no) {
		case "100":
			$job	= array(
				"name_male"		=> "战士",
				"name_female"	=> "战士",
				"img_male"		=> "mon_079.gif",
				"img_female"	=> "mon_080r.gif",
				"equip"			=> array("剑", "双手剑", "盾", "甲", "衣服", "长袍", "道具"),
				"coe"			=> array(3, 0.5),
				"change"		=> array(101, 102, 103),
			);
			break;
		case "101":
			$job	= array(
				"name_male"		=> "皇家卫士",
				"name_female"	=> "皇家卫士",
				"img_male"		=> "mon_199r.gif",
				"img_female"	=> "mon_234r.gif",
				"equip"			=> array("剑", "双手剑", "盾", "甲", "衣服", "长袍", "道具"),
				"coe"			=> array(4, 0.7),
			);
			break;
		case "102":
			$job	= array(
				"name_male"		=> "狂战士",
				"name_female"	=> "狂战士",
				"img_male"		=> "mon_100r.gif",
				"img_female"	=> "mon_012.gif",
				"equip"			=> array("剑", "双手剑", "盾", "衣服", "长袍", "道具"),
				"coe"			=> array(5.0, 0.2),
			);
			break;
		case "103":
			$job	= array(
				"name_male"		=> "魔女狩",
				"name_female"	=> "魔女狩",
				"img_male"		=> "mon_150.gif",
				"img_female"	=> "mon_234.gif",
				"equip"			=> array("剑", "匕首", "盾", "甲", "衣服", "长袍", "道具"),
				"coe"			=> array(3.7, 1),
			);
			break;
		case "200":
			$job	= array(
				"name_male"		=> "巫师",
				"name_female"	=> "巫师",
				"img_male"		=> "mon_106.gif",
				"img_female"	=> "mon_018.gif",
				"equip"			=> array("魔杖", "杖", "书", "衣服", "长袍", "道具"),
				"coe"			=> array(1.5, 1),
				"change"		=> array(201, 202, 203),
			);
			break;
		case "201":
			$job	= array(
				"name_male"		=> "术士",
				"name_female"	=> "术士",
				"img_male"		=> "mon_196z.gif",
				"img_female"	=> "mon_246r.gif",
				"equip"			=> array("魔杖", "杖", "书", "衣服", "长袍", "道具"),
				"coe"			=> array(2.1, 2),
			);
			break;
		case "202":
			$job	= array(
				"name_male"		=> "召唤师",
				"name_female"	=> "召唤师",
				"img_male"		=> "mon_196y.gif",
				"img_female"	=> "mon_246z.gif",
				"equip"			=> array("魔杖", "杖", "书", "长袍", "道具"),
				"coe"			=> array(1.5, 2.5),
			);
			break;
		case "203":
			$job	= array(
				"name_male"		=> "死灵法师",
				"name_female"	=> "死灵法师",
				"img_male"		=> "mon_196x.gif",
				"img_female"	=> "mon_246y.gif",
				"equip"			=> array("魔杖", "杖", "书", "衣服", "长袍", "道具"),
				"coe"			=> array(2.1, 1.5),
			);
			break;
		case "300":
			$job	= array(
				"name_male"		=> "牧师",
				"name_female"	=> "女祭司",
				"img_male"		=> "mon_213.gif",
				"img_female"	=> "mon_214.gif",
				"equip"			=> array("魔杖", "书", "衣服", "长袍", "道具"),
				"coe"			=> array(2, 0.8),
				"change"		=> array(301, 302),
			);
			break;
		case "301":
			$job	= array(
				"name_male"		=> "主教",
				"name_female"	=> "主教",
				"img_male"		=> "mon_213r.gif",
				"img_female"	=> "mon_214r.gif",
				"equip"			=> array("魔杖", "书", "衣服", "长袍", "道具"),
				"coe"			=> array(2.7, 1.4),
			);
			break;
		case "302":
			$job	= array(
				"name_male"		=> " 德鲁伊",
				"name_female"	=> " 德鲁伊",
				"img_male"		=> "mon_213rz.gif",
				"img_female"	=> "mon_214rz.gif",
				"equip"			=> array("魔杖", "书", "衣服", "长袍", "道具"),
				"coe"			=> array(2.5, 1.2),
			);
			break;
		case "400":
			$job	= array(
				"name_male"		=> " 猎人",
				"name_female"	=> " 猎人",
				"img_male"		=> "mon_219rr.gif",
				"img_female"	=> "mon_219r.gif",
				"equip"			=> array("弓", "衣服", "长袍", "道具"),
				"coe"			=> array(2.2, 0.7),
				"change"		=> array(401, 402, 403),
			);
			break;
		case "401":
			$job	= array(
				"name_male"		=> "神射手",
				"name_female"	=> "神射手",
				"img_male"		=> "mon_076z.gif",
				"img_female"	=> "mon_042z.gif",
				"equip"			=> array("弓", "衣服", "长袍", "道具"),
				"coe"			=> array(3.0, 0.8),
			);
			break;
		case "402":
			$job	= array(
				"name_male"		=> "驯兽师",
				"name_female"	=> "驯兽师",
				"img_male"		=> "mon_216z.gif",
				"img_female"	=> "mon_217z.gif",
				"equip"			=> array("弓", "鞭", "衣服", "长袍", "道具"),
				"coe"			=> array(3.2, 1.0),
			);
			break;
		case "403":
			$job	= array(
				"name_male"		=> "刺客",
				"name_female"	=> "刺客",
				"img_male"		=> "mon_216y.gif",
				"img_female"	=> "mon_217rz.gif",
				"equip"			=> array("匕首", "弓", "甲", "衣服", "道具"),
				"coe"			=> array(3.6, 0.7),
			);
			break;
	}
	return $job;
}
