﻿<?php

/**
 * 判定条件数据加载函数
 * 
 * 功能说明：
 * 1. 根据判定条件编号($no)加载对应的判定条件描述数据
 * 2. 提供人类可读的判定条件说明文本
 * 3. 标识需要特殊样式处理的判定条件类别
 * 
 * 参数说明：
 * @param int $no 判定条件编号
 * 
 * 返回说明：
 * @return array|bool 返回包含以下字段的数组，若编号不存在则返回false
 *   - 'exp': 判定条件的说明文本（包含占位符{$Quantity}表示数值）
 *   - 'css': 布尔值，表示该条件是否需要特殊样式处理
 * 
 * 判定条件分类：
 * 1. 基础判定：
 *    - 1000: 必定执行
 *    - 1001: 跳过执行
 * 
 * 2. HP相关判定：
 *    - 1100-1126: 自身/队伍HP百分比和数值阈值
 *    - 1099: HP类别标记(需要特殊样式)
 * 
 * 3. SP相关判定：
 *    - 1200-1226: 自身/队伍SP百分比和数值阈值
 *    - 1199: SP类别标记(需要特殊样式)
 * 
 * 4. 队伍状态判定：
 *    - 1399-1456: 己方/敌方生存人数和死亡人数
 *    - 1499-1561: 蓄力/咏唱状态检测
 *    - 1599-1618: 中毒状态检测
 * 
 * 5. 位置队列判定：
 *    - 1699-1757: 前后排位置状态检测
 * 
 * 6. 召唤物判定：
 *    - 1799-1825: 召唤物数量检测
 * 
 * 7. 魔法阵判定：
 *    - 1839-1855: 魔法阵数量检测
 * 
 * 8. 回合相关判定：
 *    - 1899-1920: 行动回合数限制
 * 
 * 9. 概率判定：
 *    - 1939-1940: 概率执行
 * 
 * 10. 特殊判定：
 *     - 9000: 敌方等级超过阈值
 * 
 * 技术细节：
 * 1. 使用$Quantity作为数值占位符，在实际显示时会被替换为具体数值
 * 2. 带有'css'=>true的条目表示该条目是类别标题而非具体条件
 * 3. 函数通过switch语句实现编号到说明文本的映射
 * 
 * 注意事项：
 * 1. 部分编号范围被注释掉(属性相关判定)，可根据需要启用
 * 2. 函数返回的说明文本包含占位符{$Quantity}，使用时需要替换为实际数值
 * 3. 类别标记条目('css'=>true)用于前端样式分类显示
 */
function LoadJudgeData($no)
{
	/*
	判定基础
	HP
	SP
	人数(自己的生存者数)
	状态（毒）？？？
	自己的行动回数
	回合限定
	对手的状态
	单纯的概率
*/
	$Quantity	= '←←';
	switch ($no) {
		case 1000: // 必定
			$judge["exp"]	= "必定";
			break;
		case 1001: // pass
			$judge["exp"]	= "跳过此判断";
			break;
		//------------------------ HP
		case 1099:
			$judge["exp"]	= "HP";
			$judge["css"]	= true;
			break;
		case 1100: // 自己的HP {$Quantity}(%)以上
			$judge["exp"]	= "自己的HP {$Quantity}(%)以上";
			break;
		case 1101: // 自己的HP {$Quantity}(%)以下
			$judge["exp"]	= "自己的HP {$Quantity}(%)以下";
			break;
		case 1105: // 自己的HP {$Quantity}以上
			$judge["exp"]	= "自己的HP {$Quantity}以上";
			break;
		case 1106: // 自己的HP {$Quantity}以下
			$judge["exp"]	= "自己的HP {$Quantity}以下";
			break;
		case 1110: // 最大HP {$Quantity}以上
			$judge["exp"]	= "最大HP {$Quantity}以上";
			break;
		case 1111: // 最大HP {$Quantity}以下
			$judge["exp"]	= "最大HP {$Quantity}以下";
			break;
		case 1121: // 我方 HP{$Quantity}(%)以下HP
			$judge["exp"]	= "我方 HP{$Quantity}(%)以下";
			break;
		case 1125: // 我方平均HP {$Quantity}(%)以上の箕
			$judge["exp"]	= "我方平均HP {$Quantity}(%)以上";
			break;
		case 1126: // 我方平均HP {$Quantity}(%)以下の箕
			$judge["exp"]	= "我方平均HP {$Quantity}(%)以下";
			break;
		//------------------------ SP
		case 1199:
			$judge["exp"]	= "SP";
			$judge["css"]	= true;
			break;
		case 1200: // 自己的SP{$Quantity}(%)以上
			$judge["exp"]	= "自己的SP{$Quantity}(%)以上";
			break;
		case 1201: // 自己的SP{$Quantity}(%)以下
			$judge["exp"]	= "自己的SP{$Quantity}(%)以下";
			break;
		case 1205: // 自己的SP{$Quantity}以上
			$judge["exp"]	= "自己的SP{$Quantity}以上";
			break;
		case 1206: // 自己的SP{$Quantity}以下
			$judge["exp"]	= "自己的SP{$Quantity}以下";
			break;
		case 1210: // 最大SP{$Quantity}以上
			$judge["exp"]	= "最大SP{$Quantity}以上";
			break;
		case 1211: // 最大SP{$Quantity}以下
			$judge["exp"]	= "最大SP{$Quantity}以下";
			break;
		case 1221: // 我方 SP{$Quantity}(%)以下HP
			$judge["exp"]	= "我方 SP{$Quantity}(%)以下";
			break;
		case 1225: // 我方平均SP {$Quantity}(%)以上の箕
			$judge["exp"]	= "我方平均SP {$Quantity}(%)以上";
			break;
		case 1226: // 我方平均SP {$Quantity}(%)以下の箕
			$judge["exp"]	= "我方平均SP {$Quantity}(%)以下";
			break;
		/*
//------------------------ STR
		case 1299:
			$judge["exp"]	= "STR";
			break;
		case 1300:// 自己的STR{$Quantity} 以上
			$judge["exp"]	= "自己的STR{$Quantity} 以上";
			break;
		case 1301:// 自己的STR{$Quantity} 以下
			$judge["exp"]	= "自己的STR{$Quantity} 以下";
			break;
//------------------------ INT
		case 1309:
			$judge["exp"]	= "INT";
			break;
		case 1310:// 自己的INT{$Quantity} 以上
			$judge["exp"]	= "自己的INT{$Quantity} 以上";
			break;
		case 1311:// 自己的INT{$Quantity} 以下
			$judge["exp"]	= "自己的INT{$Quantity} 以下";
			break;
//------------------------ DEX
		case 1319:
			$judge["exp"]	= "DEX";
			break;
		case 1320:// 自己的DEX{$Quantity} 以上
			$judge["exp"]	= "自己的DEX{$Quantity} 以上";
			break;
		case 1321:// 自己的DEX{$Quantity} 以下
			$judge["exp"]	= "自己的DEX{$Quantity} 以下";
			break;
//------------------------ SPD
		case 1329:
			$judge["exp"]	= "SPD";
			break;
		case 1330:// 自己的SPD{$Quantity} 以上
			$judge["exp"]	= "自己的SPD{$Quantity} 以上";
			break;
		case 1331:// 自己的SPD{$Quantity} 以下
			$judge["exp"]	= "自己的SPD{$Quantity} 以下";
			break;
//------------------------ LUK
		case 1339:
			$judge["exp"]	= "LUK";
			break;
		case 1340:// 自己的LUK{$Quantity} 以上
			$judge["exp"]	= "自己的LUK{$Quantity} 以上";
			break;
		case 1341:// 自己的LUK{$Quantity} 以下
			$judge["exp"]	= "自己的LUK{$Quantity} 以下";
			break;
//------------------------ ATK
		case 1349:
			$judge["exp"]	= "ATK";
			break;
		case 1350:// 自己的ATK{$Quantity} 以上
			$judge["exp"]	= "自己的ATK{$Quantity} 以上";
			break;
		case 1351:// 自己的ATK{$Quantity} 以下
			$judge["exp"]	= "自己的ATK{$Quantity} 以下";
			break;
//------------------------ MATK
		case 1359:
			$judge["exp"]	= "MATK";
			break;
		case 1360:// 自己的MATK{$Quantity} 以上
			$judge["exp"]	= "自己的MATK{$Quantity} 以上";
			break;
		case 1361:// 自己的MATK{$Quantity} 以下
			$judge["exp"]	= "自己的MATK{$Quantity} 以下";
			break;
//------------------------ DEF
		case 1369:
			$judge["exp"]	= "DEF";
			break;
		case 1370:// 自己的DEF{$Quantity} 以上
			$judge["exp"]	= "自己的DEF{$Quantity} 以上";
			break;
		case 1371:// 自己的DEF{$Quantity} 以下
			$judge["exp"]	= "自己的DEF{$Quantity} 以下";
			break;
//------------------------ MDEF
		case 1379:
			$judge["exp"]	= "MDEF";
			break;
		case 1380:// 自己的MDEF{$Quantity} 以上
			$judge["exp"]	= "自己的MDEF{$Quantity} 以上";
			break;
		case 1381:// 自己的MDEF{$Quantity} 以下
			$judge["exp"]	= "自己的MDEF{$Quantity} 以下";
			break;
*/
		//------------------------ 生死(己方)
		case 1399:
			$judge["exp"]	= "生死";
			$judge["css"]	= true;
			break;
		case 1400: // 我方的生存者 {$Quantity}人以上
			$judge["exp"]	= "我方的生存者 {$Quantity}人以上";
			break;
		case 1401: // 我方的生存者 {$Quantity}人以下
			$judge["exp"]	= "我方的生存者 {$Quantity}人以下";
			break;
		case 1405: // 我方的死者 {$Quantity}人以上
			$judge["exp"]	= "我方的死者 {$Quantity}人以上";
			break;
		case 1406: // 我方的死者 {$Quantity}人以下
			$judge["exp"]	= "我方的死者 {$Quantity}人以下";
			break;
		case 1410: // 己方前排的生存 {$Quantity}人以上
			$judge["exp"]	= "我方前排的生存者 {$Quantity}人以上";
			break;
		//------------------------ 生死（敌）
		case 1449:
			$judge["exp"]	= "生死(敌)";
			$judge["css"]	= true;
			break;
		case 1450: // 敌方的生存者 {$Quantity}人以上
			$judge["exp"]	= "敌方的生存者 {$Quantity}人以上";
			break;
		case 1451: // 敌方的生存者 {$Quantity}人以下
			$judge["exp"]	= "敌方的生存者 {$Quantity}人以下";
			break;
		case 1455: // 敌方的死者 {$Quantity}人以上
			$judge["exp"]	= "敌方的死者 {$Quantity}人以上";
			break;
		case 1456: // 敌方的死者 {$Quantity}人以下
			$judge["exp"]	= "敌方的死者 {$Quantity}人以下";
			break;
		//------------------------ 咏唱
		case 1499:
			$judge["exp"]	= "蓄力+咏唱";
			$judge["css"]	= true;
			break;
		case 1500: // 处于咏唱状态的 {$Quantity}人以上
			$judge["exp"]	= "蓄力状态的 {$Quantity}人以上";
			break;
		case 1501: // 处于咏唱状态的 {$Quantity}人以下
			$judge["exp"]	= "蓄力状态的 {$Quantity}人以下";
			break;
		case 1505: // 蓄力咏唱状态的{$Quantity}人以上
			$judge["exp"]	= "咏唱状态的{$Quantity}人以上";
			break;
		case 1506: // 蓄力咏唱状态的{$Quantity}人以下
			$judge["exp"]	= "咏唱状态的{$Quantity}人以下";
			break;
		case 1510: // 蓄力咏唱状态的{$Quantity}人以上
			$judge["exp"]	= "蓄力咏唱状态的{$Quantity}人以上";
			break;
		case 1511: // 蓄力咏唱状态的{$Quantity}人以下
			$judge["exp"]	= "蓄力咏唱状态的{$Quantity}人以下";
			break;
		//------------------------ 咏唱(敌)
		case 1549:
			$judge["exp"]	= "咏唱(敌)";
			$judge["css"]	= true;
			break;
		case 1550: // 敌方蓄力状态的 {$Quantity}人以上
			$judge["exp"]	= "敌方蓄力状态的 {$Quantity}人以上";
			break;
		case 1551: // 敌方蓄力状态的 {$Quantity}人以下
			$judge["exp"]	= "敌方蓄力状态的 {$Quantity}人以下";
			break;
		case 1555: // 敌方咏唱状态的 {$Quantity}人以上
			$judge["exp"]	= "敌方咏唱状态的 {$Quantity}人以上";
			break;
		case 1556: // 敌方咏唱状态的 {$Quantity}人以下
			$judge["exp"]	= "敌方咏唱状态的 {$Quantity}人以下";
			break;
		case 1560: // 蓄力敌方咏唱状态的 {$Quantity}人以上
			$judge["exp"]	= "敌方咏唱蓄力状态的 {$Quantity}人以上";
			break;
		case 1561: // 蓄力敌方咏唱状态的 {$Quantity}人以下
			$judge["exp"]	= "敌方蓄力咏唱状态的 {$Quantity}人以下";
			break;
		//------------------------ 毒
		case 1599:
			$judge["exp"]	= "毒";
			$judge["css"]	= true;
			break;
		case 1600: // 极尸毒觉轮
			$judge["exp"]	= "自己处于毒状态";
			break;
		case 1610: // 我方毒状态 {$Quantity}人以上
			$judge["exp"]	= "我方毒状态 {$Quantity}人以上";
			break;
		case 1611: // 我方毒状态 {$Quantity}人以下
			$judge["exp"]	= "我方毒状态 {$Quantity}人以下";
			break;
		case 1612: // 我方毒状态 {$Quantity}% 以下
			$judge["exp"]	= "我方毒状态 {$Quantity}% 以上";
			break;
		case 1613: // 我方毒状态 {$Quantity}% 以下
			$judge["exp"]	= "我方毒状态 {$Quantity}% 以下";
			break;
		//------------------------ 毒(敌)
		case 1614:
			$judge["exp"]	= "毒(敌)";
			$judge["css"]	= true;
			break;
		case 1615: // 敌方毒状态 {$Quantity}人以上
			$judge["exp"]	= "敌方毒状态 {$Quantity}人以上";
			break;
		case 1616: // 敌方毒状态 {$Quantity}人以下
			$judge["exp"]	= "敌方毒状态 {$Quantity}人以下";
			break;
		case 1617: // 敌方毒状态 {$Quantity}% 以下
			$judge["exp"]	= "敌方毒状态 {$Quantity}% 以上";
			break;
		case 1618: // 敌方毒状态 {$Quantity}% 以下
			$judge["exp"]	= "敌方毒状态 {$Quantity}% 以下";
			break;
		//------------------------ 队列
		case 1699:
			$judge["exp"]	= "队列";
			$judge["css"]	= true;
			break;
		case 1700: // 自己在前排
			$judge["exp"]	= "自己在前排";
			break;
		case 1701: // 自己在后排
			$judge["exp"]	= "自己在后排";
			break;
		case 1710: // 我方前排{$Quantity}人以上
			$judge["exp"]	= "我方前排{$Quantity}人以上";
			break;
		case 1711: // 我方前排{$Quantity}人以下
			$judge["exp"]	= "我方前排{$Quantity}人以下";
			break;
		case 1712: // 我方前排{$Quantity}人以下
			$judge["exp"]	= "我方前排{$Quantity}人";
			break;
		case 1715: // 我方后排{$Quantity}人以上
			$judge["exp"]	= "我方后排{$Quantity}人以上";
			break;
		case 1716: // 我方后排{$Quantity}人以下
			$judge["exp"]	= "我方后排{$Quantity}人以下";
			break;
		case 1717: // 我方后排{$Quantity}人以下
			$judge["exp"]	= "我方后排{$Quantity}人";
			break;
		//------------------------ 队列(敌)
		case 1749:
			$judge["exp"]	= "队列(敌)";
			$judge["css"]	= true;
			break;
		case 1750: // 敌方前排{$Quantity}人以上
			$judge["exp"]	= "敌方前排{$Quantity}人以上";
			break;
		case 1751: // 敌方前排{$Quantity}人以下
			$judge["exp"]	= "敌方前排{$Quantity}人以下";
			break;
		case 1752: // 敌方前排{$Quantity}人
			$judge["exp"]	= "敌方前排{$Quantity}人";
			break;
		case 1755: // 敌方后排{$Quantity}人以上
			$judge["exp"]	= "敌方后排{$Quantity}人以上";
			break;
		case 1756: // 敌方后排{$Quantity}人以下
			$judge["exp"]	= "敌方后排{$Quantity}人以下";
			break;
		case 1757: // 敌方后排{$Quantity}人
			$judge["exp"]	= "敌方后排{$Quantity}人";
			break;
		//------------------------ 召唤
		case 1799:
			$judge["exp"]	= "召唤";
			$judge["css"]	= true;
			break;
		case 1800: // 我方的召唤物 {$Quantity}匹以上
			$judge["exp"]	= "我方的召唤物 {$Quantity}匹以上";
			break;
		case 1801: // 我方的召唤物 {$Quantity}匹以下
			$judge["exp"]	= "我方的召唤物 {$Quantity}匹以下";
			break;
		case 1805: // 我方的召唤物 {$Quantity}匹
			$judge["exp"]	= "我方的召唤物 {$Quantity}匹";
			break;
		//------------------------ 召唤(敌)
		case 1819:
			$judge["exp"]	= "召唤(敌)";
			$judge["css"]	= true;
			break;
		case 1820: // 敌方的召唤物 {$Quantity}匹以上
			$judge["exp"]	= "敌方的召唤物 {$Quantity}匹以上";
			break;
		case 1821: // 敌方的召唤物 {$Quantity}匹以下
			$judge["exp"]	= "敌方的召唤物 {$Quantity}匹以下";
			break;
		case 1825: // 敌方的召唤物 {$Quantity}匹
			$judge["exp"]	= "敌方的召唤物 {$Quantity}匹";
			break;
		//------------------------ 魔法阵
		case 1839:
			$judge["exp"]	= "魔法阵";
			$judge["css"]	= true;
			break;
		case 1840: // 我方的魔法阵数 {$Quantity}个以上
			$judge["exp"]	= "我方的魔法阵数 {$Quantity}个以上";
			break;
		case 1841: // 我方的魔法阵数 {$Quantity}个以下
			$judge["exp"]	= "我方的魔法阵数 {$Quantity}个以下";
			break;
		case 1845: // 我方的魔法阵数 {$Quantity}个
			$judge["exp"]	= "我方的魔法阵数 {$Quantity}个";
			break;
		//------------------------ 魔法阵(敌)
		case 1849:
			$judge["exp"]	= "魔法阵(敌)";
			$judge["css"]	= true;
			break;
		case 1850: // 敌方的魔法阵数 {$Quantity}个以上
			$judge["exp"]	= "敌方的魔法阵数 {$Quantity}个以上";
			break;
		case 1851: // 敌方的魔法阵数 {$Quantity}个以下
			$judge["exp"]	= "敌方的魔法阵数 {$Quantity}个以下";
			break;
		case 1855: // 敌方的魔法阵数 {$Quantity}个
			$judge["exp"]	= "敌方的魔法阵数 {$Quantity}个";
			break;

		//------------------------ 指定行动回数
		case 1899:
			$judge["exp"]	= "指定行动回数";
			$judge["css"]	= true;
			break;
		case 1900: // 自己的行动回数 {$Quantity}回以上
			$judge["exp"]	= "自己的行动回数 {$Quantity}回以上";
			break;
		case 1901: // 自己的行动回数 {$Quantity}回以下
			$judge["exp"]	= "自己的行动回数 {$Quantity}回以下";
			break;
		case 1902: // 自己的第 {$Quantity}回合
			$judge["exp"]	= "自己的第 {$Quantity}回合";
			break;
		//------------------------ 回合限制
		case 1919:
			$judge["exp"]	= "回合限制";
			$judge["css"]	= true;
			break;
		case 1920: // {$Quantity}回 必定"
			$judge["exp"]	= "第{$Quantity}回 必定";
			break;
		//------------------------ 概率
		case 1939:
			$judge["exp"]	= "概率";
			$judge["css"]	= true;
			break;
		case 1940: // {$Quantity}%的概率
			$judge["exp"]	= "{$Quantity}%的概率";
			break;
		//----------------------- 特殊
		case 9000: // 敌方Lv超过以上。
			$judge["exp"]	= "敌方Lv超过{$Quantity}以上";
			break;
		default:
			$judge	= false;
	}
	return $judge;
}
