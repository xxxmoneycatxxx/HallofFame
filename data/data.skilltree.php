<?php

/**
 * 角色技能树加载函数
 * 
 * 功能说明：
 * 1. 根据角色职业和已学技能动态生成可学习的技能列表
 * 2. 实现技能树的分支解锁机制（前置技能要求）
 * 3. 支持多种职业体系的技能树结构
 * 
 * 参数说明：
 * @param object $char 角色对象，需包含以下属性：
 *    - job: 职业ID（整数）
 *    - skill: 已学习技能ID数组
 *    - level: 角色等级（整数）
 * 
 * 返回说明：
 * @return array 可学习的技能ID数组
 * 
 * 核心逻辑：
 * 1. 将已学技能转换为快速查找的哈希表
 * 2. 根据职业ID进入对应的技能分支
 * 3. 通过前置技能检查解锁后续技能
 * 4. 添加通用技能和等级限定技能
 * 
 * 职业体系：
 * - 剑士系(100-103): RoyalGuard(101), Sacrier(102), WitchHunt(103)
 * - 法师系(200-203): Warlock(201), Summoner(202), Necromancer(203)
 * - 支援系(300-302): Bishop(301), Druid(302)
 * - 弓手系(400-403): Sniper(401), BeastTamer(402), Murderer(403)
 * 
 * 技能解锁机制：
 * 1. 基础技能无条件解锁
 * 2. 进阶技能需要特定前置技能
 * 3. 组合技能需要多个前置技能
 * 4. 等级限制技能（如4000需Lv>19）
 * 
 * 特殊机制：
 * - 使用array_flip()实现快速技能存在检查
 * - 分支职业有专属技能树扩展
 * - 最终结果按技能ID排序
 */
function LoadSkillTree(object $char): array
{
	// 使用array_fill_keys创建快速查找表，替代原有的array_flip技巧
	$learnedSkills = array_fill_keys($char->skill, true);
	$learnable = [];

	// 剑士系技能树 (100-103)
	if ($char->job >= 100 && $char->job <= 103) {
		if (!empty($learnedSkills["1001"])) {
			$learnable = array_merge($learnable, ["1003", "1013", "3110", "3120"]);
		}
		if (!empty($learnedSkills["1003"])) {
			$learnable[] = "1017";
			$learnable[] = "1011";
		}
		if (!empty($learnedSkills["1013"])) {
			$learnable = array_merge($learnable, ["1014", "1016"]);
		}
		if (!empty($learnedSkills["3120"])) {
			$learnable[] = "3121";
		}

		// RoyalGuard (101)
		if ($char->job === 101) {
			if (!empty($learnedSkills["1003"])) {
				$learnable[] = "1012";
			}
			if (!empty($learnedSkills["1017"])) {
				$learnable = array_merge($learnable, ["1018", "1022"]);
			}
			if (!empty($learnedSkills["1013"])) {
				$learnable = array_merge($learnable, ["1015", "1023"]);
			}
			if (!empty($learnedSkills["1016"])) {
				$learnable[] = "1019";
			}
			if (!empty($learnedSkills["3110"])) {
				$learnable = array_merge($learnable, ["3111", "3112"]);
			}
			if (!empty($learnedSkills["3121"])) {
				$learnable = array_merge($learnable, ["3122", "3123"]);
			}
		}

		// Sacrier (102)
		if ($char->job === 102) {
			$learnable = array_merge($learnable, ["1100", "1113"]);
			if (!empty($learnedSkills["1100"])) {
				$learnable[] = "1101";
			}
			if (!empty($learnedSkills["1101"])) {
				$learnable[] = "1102";
			}
			if (!empty($learnedSkills["1113"])) {
				$learnable = array_merge($learnable, ["1114", "1117"]);
			}
			if (!empty($learnedSkills["1114"])) {
				$learnable = array_merge($learnable, ["1115", "1118"]);
			}
			if (!empty($learnedSkills["1115"])) {
				$learnable[] = "1116";
			}
			if (!empty($learnedSkills["1114"]) && !empty($learnedSkills["1117"]) && !empty($learnedSkills["1102"])) {
				$learnable[] = "1119";
			}
		}

		// WitchHunt (103)
		if ($char->job === 103) {
			if (!empty($learnedSkills["1003"])) {
				$learnable[] = "1020";
			}
			if (!empty($learnedSkills["1020"])) {
				$learnable = array_merge($learnable, ["1021", "1025"]);
			}
			if (!empty($learnedSkills["1021"])) {
				$learnable[] = "1024";
			}
			$learnable = array_merge($learnable, ["2090", "3231"]);
			if (!empty($learnedSkills["2090"])) {
				$learnable = array_merge($learnable, ["2091", "2110", "2111"]);
			}
			if (!empty($learnedSkills["2091"])) {
				$learnable[] = "3421";
			}
			if (!empty($learnedSkills["3231"])) {
				$learnable = array_merge($learnable, ["3215", "3230", "3235"]);
			}
		}
	}

	// 法师系技能树 (200-203)
	if ($char->job >= 200 && $char->job <= 203) {
		$learnable[] = "3011";
		if (!empty($learnedSkills["1002"])) {
			$learnable[] = "2000";
		}
		if (!empty($learnedSkills["2000"])) {
			$learnable[] = "2002";
		}
		if (!empty($learnedSkills["1002"])) {
			$learnable[] = "2010";
		}
		if (!empty($learnedSkills["2010"])) {
			$learnable[] = "2011";
		}
		if (!empty($learnedSkills["2011"])) {
			$learnable[] = "2014";
		}
		if (!empty($learnedSkills["1002"])) {
			$learnable[] = "2020";
		}
		if (!empty($learnedSkills["2020"])) {
			$learnable[] = "2021";
		}
		if (!empty($learnedSkills["2021"])) {
			$learnable = array_merge($learnable, ["2022", "2023"]);
		}

		// Warlock (201)
		if ($char->job === 201) {
			if (!empty($learnedSkills["2000"])) {
				$learnable[] = "2001";
			}
			if (!empty($learnedSkills["2001"])) {
				$learnable[] = "2004";
			}
			if (!empty($learnedSkills["2002"])) {
				$learnable[] = "2003";
			}
			if (!empty($learnedSkills["2011"])) {
				$learnable[] = "2012";
			}
			if (!empty($learnedSkills["2011"]) && !empty($learnedSkills["2014"])) {
				$learnable[] = "2015";
			}
			if (!empty($learnedSkills["2021"])) {
				$learnable[] = "2024";
			}
			if (!empty($learnedSkills["3011"])) {
				$learnable[] = "3012";
			}
			if (!empty($learnedSkills["3012"])) {
				$learnable[] = "3013";
			}
			if (!empty($learnedSkills["2000"]) && !empty($learnedSkills["2021"])) {
				$learnable[] = "2041";
			}
			if (!empty($learnedSkills["2041"])) {
				$learnable[] = "2042";
			}
			if (!empty($learnedSkills["2011"]) && !empty($learnedSkills["2021"])) {
				$learnable[] = "2040";
			}
		}

		// Summoner (202)
		if ($char->job === 202) {
			$learnable = array_merge($learnable, ["3020", "2500", "2501", "2502", "2503", "2504"]);
			if (!empty($learnedSkills["3011"])) {
				$learnable[] = "3012";
			}
			$learnable[] = "3410";
			if (!empty($learnedSkills["3410"])) {
				$learnable = array_merge($learnable, ["3411", "3420"]);
			}
		}

		// Necromancer (203)
		if ($char->job === 203) {
			$learnable[] = "2030";
			if (!empty($learnedSkills["2030"])) {
				$learnable = array_merge($learnable, ["2031", "2050", "3205", "3215"]);
			}
			if (!empty($learnedSkills["2050"])) {
				$learnable[] = "2051";
			}
			$learnable[] = "2460";
			if (!empty($learnedSkills["2460"])) {
				$learnable = array_merge($learnable, ["2461", "2462"]);
			}
			if (!empty($learnedSkills["2461"]) && !empty($learnedSkills["2462"])) {
				$learnable[] = "2055";
			}
			if (!empty($learnedSkills["2461"])) {
				$learnable[] = "2463";
			}
			if (!empty($learnedSkills["2463"])) {
				$learnable[] = "2057";
			}
			if (!empty($learnedSkills["2462"])) {
				$learnable[] = "2464";
			}
			if (!empty($learnedSkills["2464"])) {
				$learnable[] = "2056";
			}
			if (!empty($learnedSkills["2463"]) && !empty($learnedSkills["2464"])) {
				$learnable[] = "2465";
			}
		}
	}

	// 支援系技能树 (300-302)
	if ($char->job >= 300 && $char->job <= 302) {
		if (!empty($learnedSkills["3000"])) {
			$learnable = array_merge($learnable, ["2100", "3001", "3003"]);
		}
		if (!empty($learnedSkills["3001"]) || !empty($learnedSkills["3003"])) {
			$learnable = array_merge($learnable, ["3002", "3004", "3030"]);
		}
		if (!empty($learnedSkills["2100"])) {
			$learnable[] = "2480";
		}
		if (!empty($learnedSkills["3101"])) {
			$learnable[] = "3102";
		}

		// Bishop (301)
		if ($char->job === 301) {
			if (!empty($learnedSkills["2100"])) {
				$learnable = array_merge($learnable, ["2101", "3200", "3210", "3220", "3230"]);
			}
			if (!empty($learnedSkills["2101"])) {
				$learnable[] = "2102";
			}
			if (!empty($learnedSkills["3220"])) {
				$learnable[] = "3400";
			}
			if (!empty($learnedSkills["3230"])) {
				$learnable[] = "3401";
			}
			if (!empty($learnedSkills["2480"])) {
				$learnable[] = "2481";
			}
			if (!empty($learnedSkills["3102"]) && !empty($learnedSkills["3220"]) && !empty($learnedSkills["3230"])) {
				$learnable[] = "3103";
			}
			$learnable[] = "3415";
		}

		// Druid (302)
		if ($char->job === 302) {
			if (!empty($learnedSkills["3004"])) {
				$learnable = array_merge($learnable, ["3005", "3060"]);
			}
			if (!empty($learnedSkills["3060"])) {
				$learnable = array_merge($learnable, ["3050", "3055"]);
			}
			$learnable = array_merge($learnable, ["3250", "3255"]);
			if (!empty($learnedSkills["3250"]) || !empty($learnedSkills["3255"])) {
				$learnable[] = "3265";
			}
			$learnable[] = "3415";
		}
	}

	// 弓手系技能树 (400-403)
	if ($char->job >= 400 && $char->job <= 403) {
		$learnable[] = "2310";
		if (empty($learnedSkills["2300"])) {
			$learnable[] = "2300";
		}
		if (!empty($learnedSkills["2300"])) {
			$learnable = array_merge($learnable, ["2301", "2302", "2303"]);
		}

		// Sniper (401)
		if ($char->job === 401) {
			if (!empty($learnedSkills["2303"])) {
				$learnable[] = "2304";
			}
			if (!empty($learnedSkills["2301"])) {
				$learnable = array_merge($learnable, ["2305", "2306"]);
			}
			if (!empty($learnedSkills["2306"])) {
				$learnable = array_merge($learnable, ["2308", "2309"]);
			}
			if (!empty($learnedSkills["2302"]) && !empty($learnedSkills["2305"]) && !empty($learnedSkills["2306"])) {
				$learnable[] = "2307";
			}
		}

		// BeastTamer (402)
		if ($char->job === 402) {
			$learnable[] = "1240";
			if (!empty($learnedSkills["1240"])) {
				$learnable = array_merge($learnable, ["1241", "1243"]);
			}
			if (!empty($learnedSkills["1241"])) {
				$learnable = array_merge($learnable, ["1242", "1244"]);
			}
			$learnable = array_merge($learnable, ["2401", "2404", "2408"]);
			if (!empty($learnedSkills["2401"])) {
				$learnable[] = "2402";
			}
			if (!empty($learnedSkills["2402"])) {
				$learnable[] = "2403";
			}
			if (!empty($learnedSkills["2404"])) {
				$learnable[] = "2405";
			}
			if (!empty($learnedSkills["2405"])) {
				$learnable[] = "2406";
			}
			if (!empty($learnedSkills["2408"])) {
				$learnable[] = "2409";
			}
			if (!empty($learnedSkills["2409"])) {
				$learnable[] = "2410";
			}
			if (!empty($learnedSkills["2408"]) && !empty($learnedSkills["2405"])) {
				$learnable[] = "2407";
			}
			$learnable = array_merge($learnable, ["3300", "3301", "3302", "3303"]);
			if (!empty($learnedSkills["3300"])) {
				$learnable[] = "3304";
			}
			if (!empty($learnedSkills["3301"])) {
				$learnable[] = "3305";
			}
			if (!empty($learnedSkills["3302"])) {
				$learnable[] = "3306";
			}
			if (!empty($learnedSkills["3303"])) {
				$learnable[] = "3307";
			}
			if (
				!empty($learnedSkills["3300"]) && !empty($learnedSkills["3301"]) &&
				!empty($learnedSkills["3302"]) && !empty($learnedSkills["3303"])
			) {
				$learnable = array_merge($learnable, ["3308", "3310"]);
			}
		}

		// Murderer (403)
		if ($char->job === 403) {
			$learnable[] = "1200";
			if (!empty($learnedSkills["1200"])) {
				$learnable = array_merge($learnable, ["1207", "1208", "1220"]);
			}
			if (!empty($learnedSkills["1208"])) {
				$learnable[] = "1209";
			}
			$learnable[] = "1203";
			if (!empty($learnedSkills["1203"])) {
				$learnable[] = "1204";
			}
			$learnable[] = "1205";
			if (!empty($learnedSkills["1205"])) {
				$learnable[] = "1206";
			}
		}
	}

	// 通用技能
	if (empty($learnedSkills["3010"]) && $char->job === 200) {
		$learnable[] = "3010";
	}
	if ($char->level > 19) {
		$learnable[] = "4000"; // 臨戦態勢
	}
	if ($char->level > 4) {
		$learnable[] = "9000"; // 複数判定
	}

	// 移除重复项和已学技能
	$learnable = array_unique($learnable);
	$learnable = array_diff($learnable, array_keys($learnedSkills));

	// 排序并返回
	sort($learnable, SORT_STRING);
	return $learnable;
}
