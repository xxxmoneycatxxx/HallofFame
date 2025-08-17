<?php

/**
 * 角色转职资格验证函数
 * 
 * 功能说明：
 * 1. 验证指定角色是否符合转职到目标职业的条件
 * 2. 根据目标职业检查角色当前职业和等级要求
 * 3. 支持战士、法师、牧师和猎人四大基础职业的进阶转职
 * 
 * 参数说明：
 * @param object $char 角色对象
 *   - 必须包含以下属性：
 *        level: 角色当前等级
 *        job: 角色当前职业编号
 * @param string $class 目标职业编号
 * 
 * 返回说明：
 * @return bool 是否符合转职条件
 *   - true: 满足转职条件
 *   - false: 不满足转职条件
 * 
 * 转职规则说明：
 * 1. 战士进阶职业：
 *   - 101(皇家卫士): 等级>19 且 当前职业=100(战士)
 *   - 102(狂战士): 等级>24 且 当前职业=100
 *   - 103(魔女狩): 等级>22 且 当前职业=100
 * 
 * 2. 法师进阶职业：
 *   - 201(术士): 等级>19 且 当前职业=200(法师)
 *   - 202(召唤师): 等级>24 且 当前职业=200
 *   - 203(死灵法师): 等级>21 且 当前职业=200
 * 
 * 3. 牧师进阶职业：
 *   - 301(主教): 等级>24 且 当前职业=300(牧师)
 *   - 302(德鲁伊): 等级>19 且 当前职业=300
 * 
 * 4. 猎人进阶职业：
 *   - 401(狙击手): 等级>19 且 当前职业=400(猎人)
 *   - 402(驯兽师): 等级>24 且 当前职业=400
 *   - 403(刺客): 等级>21 且 当前职业=400
 * 
 * 注意事项：
 * 1. 所有转职都需要角色达到指定等级且保持基础职业
 * 2. 不支持的职业编号将返回false
 * 3. 转职系统设计为单向进阶，不可逆
 */
function CanClassChange($char, $class)
{
	switch ($class) {
		case "101": // 皇家卫士
			if (19 < $char->level && $char->job == 100)
				return true;
			return false;
		case "102": // 狂战士
			if (24 < $char->level && $char->job == 100)
				return true;
			return false;
		case "103": // 魔女狩
			if (22 < $char->level && $char->job == 100)
				return true;
			return false;
		case "201": // 术士
			if (19 < $char->level && $char->job == 200)
				return true;
			return false;
		case "202": // 召唤师
			if (24 < $char->level && $char->job == 200)
				return true;
			return false;
		case "203": // 死灵法师
			if (21 < $char->level && $char->job == 200)
				return true;
			return false;
		case "301": // 主教
			if (24 < $char->level && $char->job == 300)
				return true;
			return false;
		case "302": // 德鲁伊
			if (19 < $char->level && $char->job == 300)
				return true;
			return false;
		case "401": // 狙击手
			if (19 < $char->level && $char->job == 400)
				return true;
			return false;
		case "402": // 驯兽师
			if (24 < $char->level && $char->job == 400)
				return true;
			return false;
		case "403": // 刺客
			if (21 < $char->level && $char->job == 400)
				return true;
			return false;
		default:
			return false;
	}
}
