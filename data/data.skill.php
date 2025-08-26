<?php

/**
 * 技能数据加载函数
 * 
 * 功能说明：
 * 1. 根据技能ID($no)加载完整的技能数据
 * 2. 提供详细的技能属性和效果描述
 * 3. 支持多种技能类型和效果机制
 * 
 * 参数说明：
 * @param int $no 技能ID
 * 
 * 返回说明：
 * @return array|bool 技能数据数组，若ID不存在则返回false
 * 
 * 技能数据结构说明：
 * 1. 基础属性：
 *    - name: 技能名称
 *    - img: 技能图标路径
 *    - exp: 技能描述文本
 *    - sp: 技能消耗SP值
 *    - type: 技能类型(0=物理,1=魔法)
 *    - learn: 学习所需技能点数
 * 
 * 2. 目标与效果：
 *    - target: 目标范围数组 [目标阵营, 目标类型, 目标数量]
 *        - 目标阵营: enemy(敌方)/friend(友方)/all(全体)/self(自身)
 *        - 目标类型: individual(单体)/multi(随机多人)/all(全体)
 *        - 目标数量: 作用目标数量
 *    - pow: 基础威力百分比(100=100%基础伤害)
 *    - invalid: 是否无视位置(1=true)
 *    - charge: 吟唱时间数组 [准备时间, 硬直时间]
 * 
 * 3. 特殊效果：
 *    - support: 是否为支援技能(1=true)
 *    - pierce: 是否无视防御(true)
 *    - poison: 中毒概率百分比
 *    - knockback: 击退概率百分比
 *    - summon: 召唤怪物ID
 *    - Up* / Down*: 属性提升/降低效果(STR/INT/DEX/SPD等)
 *    - priority: 优先目标(LowHpRate/Dead/Summon等)
 * 
 * 4. 限制条件：
 *    - limit: 使用限制(如"鞭"=>true)
 *    - strict: 武器限制数组(如"Bow"=>true)
 * 
 * 5. 特殊类型：
 *    - passive: 被动技能标记(1=true)
 *    - p_*: 被动加成属性(如p_maxhp)
 * 
 * 技能分类说明：
 * 1. 基础攻击技能(1000-1999):
 *    - 普通攻击、连击、属性攻击等
 * 
 * 2. 战士系技能(1100-1199):
 *    - 力量提升、狂暴化等
 * 
 * 3. 刺客系技能(1200-1299):
 *    - 毒系攻击、致盲等
 * 
 * 4. 射手系技能(2300-2399):
 *    - 弓箭射击、特殊箭矢等
 * 
 * 5. 法师系技能(2000-2999):
 *    - 元素魔法、状态魔法等
 * 
 * 6. 治疗系技能(3000-3999):
 *    - 治疗、复活、状态恢复等
 * 
 * 7. 召唤系技能(2400-2499, 3300-3399):
 *    - 召唤怪物、召唤物强化等
 * 
 * 8. 增益/减益技能(3100-3199, 3200-3299):
 *    - 属性强化、属性弱化等
 * 
 * 9. 被动技能(7000-7999):
 *    - 属性加成、特效触发等
 * 
 * 10. 特殊技能(9000+):
 *     - 多条件判定、特殊机制等
 * 
 * 注意事项：
 * 1. 部分技能有使用武器限制
 * 2. 召唤技能需要配合怪物数据
 * 3. 技能效果可能受角色属性影响
 * 4. 被动技能无需主动施放
 * 
 * 例子：
 * "name"	=> "技能名称",
 * "img"	=> "skill_042.png",//图标
 * "exp"	=> "技能介绍",
 * "sp"	=> "消耗魔力",
 * "type"	=> "0",//0=物理 1=魔法
 * "target"=> array(friend/enemy/all/self,individual/multi/all,攻撃回数),
 * 	----(例)----------------------------------------
 * 		frien/enemy	= 己方/敌方
 * 		all			= 己方+敌方 全体
 * 		self		= 自己
 * 	enemy individual 1	= 敌方单体攻击1次
 * 	enemy individual 3	= 敌方单体攻击3次
 * 	enemy multi 3		= 敌方随机攻击3人1次(可能对同一目标重复攻击)
 * 	enemy all 1			= 敌方全体攻击1次
 * 	all individual 5	= 对敌我全体攻击5次
 * 	all multi 5			= 对敌我全体随机攻击5人1次(可能对同一目标重复攻击)
 * 	all all 3			= 对敌我全体全部攻击3次
 * 	------------------------------------------------
 * "pow"	=> "100",// 攻击倍数 130=1.3倍 100为基本攻击。
 * // "hit"	=> "100",// 命中率
 * "invalid"	=> "1",//攻击后排
 * "support"	=> "1",//己方增益魔法(需要与"invalid"区别)
 * "priority"	=> "LowHpRate",//优先目标(LowHpRate,Dead,Summon,Charge)
 * //"charge"	=> "",//吟唱或蓄力时间(0=无CD瞬发)
 * //"stiff"	=> "",//释放后硬直时间(0=无硬直 100=待机时间x2(待机时间=硬直时间) )
 * "charge" => array(charge,stiff),//更改为数组。
 * "learn"	=> "学习技能需要的技能点",
 * "Up**"
 * "Down**"
 * "pierce"
 * "delay"
 * "knockback"
 * "poison"
 * "summon"
 * "move"
 * "strict" => array("Bow"=>true),//武器限制
 * "umove" // 使用者が移動。
 * "DownSTR"	=> "40",// IND DEX SPD LUK ATK MATK DEF MDEF HP SP
 * "UpSTR"
 * "PlusSTR"	=> 50,
 */
function LoadSkillData($no)
{
	static $cache = [];

	// 内存缓存优化
	if (isset($cache[$no])) return $cache[$no];

	$db = $GLOBALS['DB'];
	$stmt = $db->prepare('SELECT * FROM skills WHERE id = ?');
	$stmt->execute([$no]);
	$data = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$data) return false;

	// 重构为原结构
	$skill = [
		"name" => $data['name'],
		"img" => $data['img'],
		"exp" => $data['exp'],
		"sp" => $data['sp'],
		"type" => $data['type'],
		"learn" => $data['learn'],
		"target" => json_decode($data['target'], true),
		"pow" => $data['pow'],
		"invalid" => (bool)$data['invalid'],
		"charge" => json_decode($data['charge'], true),
		"support" => (bool)$data['support'],
		"pierce" => (bool)$data['pierce'],
		"passive" => (bool)$data['passive']
	];

	// 可选字段处理
	if ($data['poison']) $skill['poison'] = $data['poison'];
	if ($data['knockback']) $skill['knockback'] = $data['knockback'];
	if ($data['summon']) $skill['summon'] = $data['summon'];

	// 特殊效果
	$effects = json_decode($data['effects'], true);
	foreach ($effects as $key => $value) {
		$skill[$key] = $value;
	}

	// 限制条件
	$limits = json_decode($data['limits'], true);
	if ($limits) {
		if (isset($limits['鞭'])) {
			$skill['limit'] = $limits;
		} else {
			$skill['strict'] = $limits;
		}
	}

	// 添加原始ID
	$skill['no'] = $no;

	// 缓存结果
	$cache[$no] = $skill;
	return $skill;
}
