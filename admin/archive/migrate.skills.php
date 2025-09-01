<?php
// http://localhost/admin/migrate.skills.php

require_once '../setting.php'; // 包含全局配置文件
require_once '../class/global.php';
require_once '../data/data.skill.php'; // 包含原技能加载函数

$db = $GLOBALS['DB'];

// 清空并重建表
$db->exec('DROP TABLE IF EXISTS skills');
$db->exec("CREATE TABLE IF NOT EXISTS skills (
    id INTEGER PRIMARY KEY,     -- 技能ID
    name TEXT NOT NULL,         -- 技能名称
    img TEXT,                   -- 图标路径
    exp TEXT,                   -- 技能描述
    sp INTEGER DEFAULT 0,       -- 消耗SP
    type INTEGER DEFAULT 0,     -- 类型(0=物理,1=魔法)
    learn INTEGER DEFAULT 0,    -- 学习所需点数
    target TEXT,                -- JSON数组格式的目标范围
    pow INTEGER,                -- 基础威力
    invalid BOOLEAN DEFAULT 0,  -- 是否无视位置
    charge TEXT,                -- JSON数组格式的吟唱时间
    support BOOLEAN DEFAULT 0,  -- 是否为支援技能
    pierce BOOLEAN DEFAULT 0,  -- 是否无视防御
    poison INTEGER,             -- 中毒概率
    knockback INTEGER,          -- 击退概率
    summon TEXT,                -- 召唤怪物ID
    effects TEXT,               -- JSON格式的特殊效果
    limits TEXT,                -- JSON格式的限制条件
    passive BOOLEAN DEFAULT 0,  -- 是否被动技能
    p_effects TEXT,             -- JSON格式的被动效果
    category INTEGER            -- 技能分类
    )");

$stmt = $db->prepare('INSERT INTO skills VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

// 迁移所有技能数据
$skillIds = [
    // 基础攻击技能
    ...range(1000, 1025),
    // 战士系技能
    ...range(1100, 1119),
    // 刺客系技能
    ...range(1200, 1211), 1220,
    // 驯兽师技能
    ...range(1240, 1244),
    // 法师系技能
    ...range(2000, 2091),
    // 射手系技能
    ...range(2300, 2310),
    // 召唤系技能
    ...range(2400, 2410), ...range(2460, 2465), 2480, 2481,
    // 治疗系技能
    ...range(3000, 3060),
    // 增益/减益技能
    3101, 3102, 3103, 3110, 3111, 3112, 3113, 3120, 3121, 3122, 3123, 3130, 3135,
    3200, 3205, 3210, 3215, 3220, 3221, 3222, 3230, 3231, 3235, 3250, 3255, 3265,
    // 召唤物强化
    ...range(3300, 3310),
    // 持续回复系
    3400, 3401,
    // 魔法阵相关
    ...range(3410, 3421),
    // 测试技能
    3900, 3901,
    // 队列修正
    4000,
    // 敌人技能
    ...range(4999, 5073),
    ...range(5799, 5807),
    // 被动技能
    ...range(7000, 7005),
    // 特殊技能
    9000
];

foreach ($skillIds as $id) {
    $skill = LoadSkillData(strval($id));
    if (!$skill) continue;
    
    // 处理特殊字段
    $effects = [];
    foreach ($skill as $key => $value) {
        if (strpos($key, 'Up') === 0 || strpos($key, 'Down') === 0 || 
            strpos($key, 'Plus') === 0 || $key === 'delay') {
            $effects[$key] = $value;
        }
    }
    
    $limits = $skill['limit'] ?? $skill['strict'] ?? [];
    
    // 分类逻辑
    $category = match(true) {
        $id < 2000 => 1, // 物理系
        $id < 3000 => 2, // 法师系
        $id < 4000 => 3, // 治疗系
        default => 0
    };
    
    $stmt->execute([
        $id,
        $skill['name'],
        $skill['img'],
        $skill['exp'],
        $skill['sp'] ?? 0,
        $skill['type'] ?? 0,
        $skill['learn'] ?? 0,
        json_encode($skill['target']),
        $skill['pow'] ?? null,
        $skill['invalid'] ?? 0,
        json_encode($skill['charge'] ?? []),
        $skill['support'] ?? 0,
        $skill['pierce'] ?? 0,
        $skill['poison'] ?? null,
        $skill['knockback'] ?? null,
        $skill['summon'] ?? null,
        json_encode($effects),
        json_encode($limits),
        $skill['passive'] ?? 0,
        json_encode([]), // p_effects留空
        $category
    ]);
}

echo "技能数据迁移完成！";