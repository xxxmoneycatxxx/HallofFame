<?php

/**
 * 战斗判定决策函数
 * 
 * 功能说明：
 * 1. 根据给定的判定条件编号($number)和角色状态($My)进行战斗决策
 * 2. 支持多种判定类型：HP/SP状态、队伍人数、异常状态、位置队列等
 * 3. 结合队伍整体状态进行综合判断
 * 
 * 参数说明：
 * @param int $number 判定条件编号
 * @param object $My 当前角色对象
 * @param object $classBattle 战斗系统对象
 * 
 * 返回说明：
 * @return bool 判定结果（true表示条件满足，false表示不满足）
 * 
 * 判定条件分类：
 * 1. 基础判定：
 *    - 1000: 必定执行
 *    - 1001: 跳过执行
 * 
 * 2. HP相关判定：
 *    - 1100-1111: 自身HP百分比/数值阈值
 *    - 1121-1126: 队伍HP百分比状态
 * 
 * 3. SP相关判定：
 *    - 1200-1211: 自身SP百分比/数值阈值
 *    - 1221-1226: 队伍SP百分比状态
 * 
 * 4. 属性相关判定：
 *    - 1300-1381: 角色基础属性(STR/INT/DEX等)阈值
 * 
 * 5. 人数相关判定：
 *    - 1400-1410: 己方生存/死亡人数
 *    - 1450-1456: 敌方生存/死亡人数
 * 
 * 6. 状态相关判定：
 *    - 1500-1511: 己方蓄力/咏唱状态
 *    - 1550-1561: 敌方蓄力/咏唱状态
 *    - 1600-1616: 中毒状态检测
 * 
 * 7. 位置队列判定：
 *    - 1700-1717: 己方前后排位置状态
 *    - 1750-1757: 敌方前后排位置状态
 * 
 * 8. 召唤物判定：
 *    - 1800-1805: 己方召唤物数量
 *    - 1820-1825: 敌方召唤物数量
 * 
 * 9. 魔法阵判定：
 *    - 1840-1845: 己方魔法阵数量
 *    - 1850-1855: 敌方魔法阵数量
 * 
 * 10. 回合相关判定：
 *     - 1900-1902: 自身行动回合数
 *     - 1920: 回合限制
 *     - 1940: 概率判定
 * 
 * 11. 特殊判定：
 *     - 9000: 敌方等级超过阈值
 * 
 * 技术细节：
 * 1. 首先确定角色所属队伍（TEAM_0或TEAM_1）
 * 2. 根据判定编号($number)执行对应的条件检查
 * 3. 使用$Quantity作为判定阈值参数
 * 4. 遍历队伍成员进行群体状态检测
 * 
 * 注意事项：
 * 1. 函数依赖角色对象的状态属性（HP/SP/位置等）
 * 2. 部分判定需要遍历整个队伍成员
 * 3. 死亡角色(STATE === DEAD)会被跳过检测
 */
function DecideJudge($number, $My, $classBattle)
{
	if ($My->team == TEAM_0) {
		$MyTeam	= $classBattle->team0;
		$EnemyTeam	= $classBattle->team1;
		$MyTeamMC	= $classBattle->team0_mc;
		$EnemyTeamMC	= $classBattle->team1_mc;
	} else {
		$MyTeam	= $classBattle->team1;
		$EnemyTeam	= $classBattle->team0;
		$MyTeamMC	= $classBattle->team1_mc;
		$EnemyTeamMC	= $classBattle->team0_mc;
	}
	$Judge		= $My->judge["$number"];
	$Quantity	= $My->quantity["$number"];
	switch ($Judge) {
		case 1000: // 必定
			return true;
		case 1001: // pass
			return false;
			//------------------------ HP相关
		case 1100: // 自己的HP ←←(%)以上
			$hpp	= $My->HpPercent();
			if ($Quantity <= $hpp) return true;
			break;
		case 1101: // 自己的HP ←←(%)以下
			$hpp	= $My->HpPercent();
			if ($hpp <= $Quantity) return true;
			break;
		case 1105: // 自己的HP ←←以上
			$hp		= $My->HP;
			if ($Quantity <= $hp) return true;
			break;
		case 1106: // 自己的HP ←←以下
			$hp		= $My->HP;
			if ($hp <= $Quantity) return true;
			break;
		case 1110: // 最大HP ←←以上
			$mhp		= $My->MAXHP;
			if ($Quantity <= $mhp) return true;
			break;
		case 1111: // 最大HP ←←以下
			$mhp		= $My->MAXHP;
			if ($mhp <= $Quantity) return true;
			break;
		case 1121: // 我方 HP←←(%)以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->HpPercent() <= $Quantity)
					return true;
			}
			break;
		case 1125: // 我方平均HP ←←(%)以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				$sum	+= $char->HpPercent();
				$cnt++; // 生存人数
			}
			$ave	= $sum / $cnt;
			if ($Quantity <= $ave) return true;
			break;
		case 1126: // 我方平均HP ←←(%)以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				$sum	+= $char->HpPercent();
				$cnt++; // 生存人数
			}
			$ave	= $sum / $cnt;
			if ($ave <= $Quantity) return true;
			break;
		//------------------------ SP
		case 1200: // 自己的SP←←(%)以上
			$spp	= $My->SpPercent();
			if ($Quantity <= $spp) return true;
			break;
		case 1201: // 自己的SP←←(%)以下
			$spp	= $My->SpPercent();
			if ($spp <= $Quantity) return true;
			break;
		case 1205: // 自己的SP←←以上
			$sp		= $My->SP;
			if ($Quantity <= $sp) return true;
			break;
		case 1206: // 自己的SP←←(%)以下
			$sp		= $My->SP;
			if ($sp <= $Quantity) return true;
			break;
		case 1210: // 自己的SP←←以上
			$msp		= $My->MAXSP;
			if ($Quantity <= $msp) return true;
			break;
		case 1211: // 最大SP←←以下
			$msp		= $My->MAXSP;
			if ($msp <= $Quantity) return true;
			break;
		case 1221: // 我方 SP←←(%)以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->MAXSP === 0) continue;
				if ($char->SpPercent() <= $Quantity)
					return true;
			}
			break;
		case 1225: // 我方平均SP ←←(%)以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->MAXSP === 0) continue;
				$sum	+= $char->SpPercent();
				$cnt++; // 生存人数
			}
			// 被零除的话
			if (!$cnt)
				break;
			$ave	= $sum / $cnt;
			if ($Quantity <= $ave) return true;
			break;
		case 1226: // 我方平均SP ←←(%)以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->MAXSP === 0) continue;
				$sum	+= $char->SpPercent();
				$cnt++; // 生存人数
			}
			// 被零除的话
			if (!$cnt)
				break;
			$ave	= $sum / $cnt;
			if ($ave <= $Quantity) return true;
			break;
		//------------------------ STR
		case 1300: // 自己的STR ** 以上
			break;
		case 1301: // 自己的STR ** 以下
			break;
		//------------------------ INT
		case 1310: // 自己的INT ** 以上
			break;
		case 1311: // 自己的INT ** 以下
			break;
		//------------------------ DEX
		case 1320: // 自己的DEX ** 以上
			break;
		case 1321: // 自己的DEX ** 以下
			break;
		//------------------------ SPD
		case 1330: // 自己的SPD ** 以上
			break;
		case 1331: // 自己的SPD ** 以下
			break;
		//------------------------ LUK
		case 1340: // 自己的LUK ** 以上
			break;
		case 1341: // 自己的LUK ** 以下
			break;
		//------------------------ ATK
		case 1350: // 自己的ATK ** 以上
			break;
		case 1351: // 自己的ATK ** 以下
			break;
		//------------------------ MATK
		case 1360: // 自己的MATK ** 以上
			break;
		case 1361: // 自己的MATK ** 以下
			break;
		//------------------------ DEF
		case 1370: // 自己的DEF ** 以上
			break;
		case 1371: // 自己的DEF ** 以下
			break;
		//------------------------ MDEF
		case 1380: // 自己的MDEF ** 以上
			break;
		case 1381: // 自己的MDEF ** 以下
			break;
		//------------------------ 人数(己方)
		case 1400: // 己方的生存人数 *个以上
			foreach ($MyTeam as $char) {
				if ($char->STATE !== DEAD)
					$alive++;
			}
			if ($Quantity <= $alive) return true;
			break;
		case 1401: // 己方的生存人数 *个以下
			foreach ($MyTeam as $char) {
				if ($char->STATE !== DEAD)
					$alive++;
			}
			if ($alive <= $Quantity) return true;
			break;
		case 1405: // 己方的死者 *个以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD)
					$dead++;
			}
			if ($Quantity <= $dead) return true;
			break;
		case 1406: // 己方的死者 *个以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD)
					$dead++;
			}
			if ($dead <= $Quantity) return true;
			break;
		case 1410: // 我方前排的生存人数 *个以上
			$front_alive	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE !== DEAD && $char->position == FRONT)
					$front_alive++;
			}
			if ($Quantity <= $front_alive) return true;
			break;
		//------------------------ 人数(敌)
		case 1450: // 敌方的生存人数 *个以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE !== DEAD)
					$alive++;
			}
			if ($Quantity <= $alive) return true;
			break;
		case 1451: // 敌方的生存人数 *个以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE !== DEAD)
					$alive++;
			}
			if ($alive <= $Quantity) return true;
			break;
		case 1455: // 敌方的死者 *个以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD)
					$dead++;
			}
			if ($Quantity <= $dead) return true;
			break;
		case 1456: // 敌方的死者 *个以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD)
					$dead++;
			}
			if ($dead <= $Quantity) return true;
			break;
		//------------------------ 蓄力+咏唱
		case 1500: // 蓄力状态的 *个以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CHARGE)
					$charge++;
			}
			if ($Quantity <= $charge) return true;
			break;
		case 1501: // 蓄力状态的 *个以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CHARGE)
					$charge++;
			}
			if ($charge <= $Quantity) return true;
			break;
		case 1505: // 咏唱状态的 *个以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST)
					$cast++;
			}
			if ($Quantity <= $cast) return true;
			break;
		case 1506: // 咏唱状态的 *个以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CHARGE)
					$cast++;
			}
			if ($cast <= $Quantity) return true;
			break;
		case 1510: // 蓄力咏唱状态的 *个以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST || $char->expect_type === CHARGE)
					$expect++;
			}
			if ($Quantity <= $expect) return true;
			break;
		case 1511: // 蓄力咏唱状态的 *个以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST || $char->expect_type === CHARGE)
					$expect++;
			}
			if ($expect <= $Quantity) return true;
			break;
		//------------------------ 蓄力+咏唱(敌)
		case 1550: // 蓄力状态（敌方）*个以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CHARGE)
					$charge++;
			}
			if ($Quantity <= $charge) return true;
			break;
		case 1551: // 蓄力状态（敌方）*个以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CHARGE)
					$charge++;
			}
			if ($charge <= $Quantity) return true;
			break;
		case 1555: // 咏唱状态（敌方）*个以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST)
					$cast++;
			}
			if ($Quantity <= $cast) return true;
			break;
		case 1556: // 咏唱状态（敌方）*个以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST)
					$cast++;
			}
			if ($cast <= $Quantity) return true;
			break;
		case 1560: // 蓄力咏唱状态（敌方）*个以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST || $char->expect_type === CHARGE)
					$expect++;
			}
			if ($Quantity <= $expect) return true;
			break;
		case 1561: // 蓄力咏唱状态（敌方）*个以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->expect_type === CAST || $char->expect_type === CHARGE)
					$expect++;
			}
			if ($expect <= $Quantity) return true;
			break;
		//------------------------ 毒
		case 1600: // 自己处于毒状态
			if ($My->STATE === POISON) return true;
			break;
		case 1610: // 我方毒状态 **个以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->STATE === POISON)
					$poison++;
			}
			if ($Quantity <= $poison) return true;
			break;
		case 1611: // 我方毒状态 **个以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->STATE === POISON)
					$poison++;
			}
			if ($poison <= $Quantity) return true;
			break;
		case 1612: // 我方毒状态 **% 以上
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				$alive++;
				if ($char->STATE === POISON)
					$poison++;
			}
			if (!$alive) return false;
			$Rate	= ($poison / $alive) * 100;
			if ($Quantity <= $Rate) return true;
			break;
		case 1613: // 我方毒状态 **% 以下
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				$alive++;
				if ($char->STATE === POISON)
					$poison++;
			}
			if (!$alive) return false;
			$Rate	= ($poison / $alive) * 100;
			if ($Rate <= $Quantity) return true;
			break;
		//------------------------ 毒(敌)
		case 1615: // 敌方毒状态 **个以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->STATE === POISON)
					$poison++;
			}
			if ($Quantity <= $poison) return true;
			break;
		case 1616: // 敌方毒状态 **个以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->STATE === POISON)
					$poison++;
			}
			if ($poison <= $Quantity) return true;
			break;
		case 1612: // 敌方毒状态 **% 以上
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				$alive++;
				if ($char->STATE === POISON)
					$poison++;
			}
			if (!$alive) return false;
			$Rate	= ($poison / $alive) * 100;
			if ($Quantity <= $Rate) return true;
			break;
		case 1613: // 敌方毒状态 **% 以下
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				$alive++;
				if ($char->STATE === POISON)
					$poison++;
			}
			if (!$alive) return false;
			$Rate	= ($poison / $alive) * 100;
			if ($Rate <= $Quantity) return true;
			break;
		//------------------------ 队列
		case 1700: // 自己在前排
			if ($My->POSITION == FRONT) return true;
			break;
		case 1701: // 自己在后排
			if ($My->POSITION == BACK) return true;
			break;
		case 1710: // 己方前排 **个以上
			$front	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == FRONT)
					$front++;
			}
			if ($Quantity <= $front) return true;
			break;
		case 1711: // 己方前排 **个以下
			$front	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == FRONT)
					$front++;
			}
			if ($front <= $Quantity) return true;
			break;
		case 1712: // 己方前排 **个
			$front	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == FRONT)
					$front++;
			}
			if ($front == $Quantity) return true;
			break;
		case 1715: // 己方后排 **个以上
			$back	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == BACK)
					$back++;
			}
			if ($Quantity <= $back) return true;
			break;
		case 1716: // 己方后排 **个以下
			$back	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == BACK)
					$back++;
			}
			if ($back <= $Quantity) return true;
			break;
		case 1717: // 己方后排 **个
			$back	= 0;
			foreach ($MyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == BACK)
					$back++;
			}
			if ($back == $Quantity) return true;
			break;
		//------------------------ 队列(敌)
		case 1750: // 敌方前排 **个以上
			$front	= 0;
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == FRONT)
					$front++;
			}
			if ($Quantity <= $front) return true;
			break;
		case 1751: // 敌方前排 **个以下
			$front	= 0;
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == FRONT)
					$front++;
			}
			if ($front <= $Quantity) return true;
			break;
		case 1752: // 敌方前排 **个
			$front	= 0;
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == FRONT)
					$front++;
			}
			if ($Quantity == $front) return true;
			break;
		case 1755: // 敌方后排 **个以上
			$back	= 0;
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == BACK)
					$back++;
			}
			if ($Quantity <= $back) return true;
			break;
		case 1756: // 敌方后排 **个以下
			$back	= 0;
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == BACK)
					$back++;
			}
			if ($back <= $Quantity) return true;
			break;
		case 1757: // 敌方后排 **个
			$back	= 0;
			foreach ($EnemyTeam as $char) {
				if ($char->STATE === DEAD) continue;
				if ($char->POSITION == BACK)
					$back++;
			}
			if ($Quantity == $back) return true;
			break;
		//------------------------ 召唤
		case 1800: // 己方的召唤物**匹以上
			$summon	= 0;
			foreach ($MyTeam as $char) {
				//if($char->STATE === DEAD) continue;
				if ($char->summon)
					$summon++;
			}
			if ($Quantity <= $summon) return true;
			break;
		case 1801: // 己方的召唤物**匹以下
			$summon	= 0;
			foreach ($MyTeam as $char) {
				//if($char->STATE === DEAD) continue;
				if ($char->summon)
					$summon++;
			}
			if ($summon <= $Quantity) return true;
			break;

		case 1805: // 己方的召唤物**匹
			$summon	= 0;
			foreach ($MyTeam as $char) {
				//if($char->STATE === DEAD) continue;
				if ($char->summon)
					$summon++;
			}
			if ($summon == $Quantity) return true;
			break;
		//------------------------ 召唤(敌)
		case 1820: // 敌方的召唤物**匹以上
			$summon	= 0;
			foreach ($EnemyTeam as $char) {
				//if($char->STATE === DEAD) continue;
				if ($char->summon)
					$summon++;
			}
			if ($Quantity <= $summon) return true;
			break;
		case 1821: // 敌方的召唤物**匹以下
			$summon	= 0;
			foreach ($EnemyTeam as $char) {
				//if($char->STATE === DEAD) continue;
				if ($char->summon)
					$summon++;
			}
			if ($summon <= $Quantity) return true;
			break;
		case 1825: // 敌方的召唤物**匹
			$summon	= 0;
			foreach ($EnemyTeam as $char) {
				//if($char->STATE === DEAD) continue;
				if ($char->summon)
					$summon++;
			}
			if ($summon == $Quantity) return true;
			break;
		//------------------------ 魔法阵
		case 1840: // 己方的魔法阵数**个以上
			if ($Quantity <= $MyTeamMC)
				return true;
			break;
		case 1841: // 己方的魔法阵数**个以下
			if ($MyTeamMC <= $Quantity)
				return true;
			break;
		case 1845: // 己方的魔法阵数**个
			if ($Quantity == $MyTeamMC)
				return true;
			break;
		//------------------------ 魔法阵(敌)
		case 1850: // 敌方的魔法阵数**个以上
			if ($Quantity <= $EnemyTeamMC)
				return true;
			break;
		case 1851: // 敌方的魔法阵数**个以下
			if ($EnemyTeamMC <= $Quantity)
				return true;
			break;
		case 1855: // 敌方的魔法阵数**个
			if ($Quantity == $EnemyTeamMC)
				return true;
			break;
		//------------------------ 指定行动回数
		case 1900: // 自己的行动回数**回以上
			if (($Quantity - 1) <= $My->ActCount) return true;
			break;
		case 1901: // 自己的行动回数**回以下
			if ($My->ActCount <= ($Quantity - 1)) return true;
			break;
		case 1902: // 自己的行动回数**回合
			if ($My->ActCount == ($Quantity - 1)) return true;
			break;
		//------------------------ 回合限制
		case 1920: // 第←←回 必定
			if ($My->JdgCount[$number] < $Quantity) return true;
			break;
		//------------------------ 概率
		case 1940: // **%的概率
			$prob	= mt_rand(1, 100);
			if ($prob <= $Quantity) return true;
			break;
		//------------------------ 特殊判定
		case 9000: // 敌方Lv超过←←以上
			foreach ($EnemyTeam as $char) {
				if ($Quantity <= $char->level)
					return true;
			}
			break;
	}
}
//////////////////////////////////////////////////
//	SP代替用（？）
function &FuncTeamHpSpRate(&$TeamHpRate, $NO)
{
	foreach ($TeamHpRate as $key => $Rate) {
		if ($Rate <= $NO)
			$target[]	= &$MyTeam[$key];
	}
	return $target ? $target : false;
}
