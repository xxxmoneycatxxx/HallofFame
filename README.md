## PHP页游 - 荣誉圣殿
**PHP Web Game - Hall Of Fame**

改造后在php7.4.3下顺利运行。可能存在少量报错，欢迎反馈。

### 项目结构
```
admin/ *管理后台
    admin.php *游戏管理后台主界面
    admin.list_enchant.php *装备效果列表
    admin.list_item.php *游戏道具列表页面，通过PHP脚本从data.item.php数据文件加载道具信息
    list_job.php *游戏职业详情列表页面，通过PHP脚本从data.job.php和data.skill.php加载职业和技能信息
    list_judge.php *判定条件列表生成器
    list_monster.php *怪物列表，数据来源：
                                1. data.monster.php - 怪物基础数据
                                2. data.judge_setup.php - 技能判定数据
                                3. data.skill.php - 技能效果数据
                                4. data.item.php - 物品数据
                                5. data.enchant.php - 装备效果
    list_skill.php *游戏技能百科全书，通过PHP脚本从data.skill.php加载所有技能信息
    admin.set_action.php *怪物行动模式设置及生成工具

class/ *功能实现
    class.auction.php *拍卖系统核心类
    class.battle.php *战斗系统核心类
    class.char.php *角色核心类
    class.css_btl_image.php *CSS战斗画面渲染类
    class.JS_item_list.php *JavaScript动态道具列表类
    class.main.php *主控制器类
    class.monster.php *怪物角色类
    class.rank.php *排名系统类
    class.rank2.php *高级排名系统类
    class.skill_effect.php *战斗技能效果处理类
    class.smithy.php *物品锻造系统类
    class.union.php *BOSS怪物系统类
    class.user.php *用户管理系统类
    global.php *全局核心功能库

css/ *css资源
    global.css *全局页面css

data/ *游戏数据
    data.base_char.php *角色基础职业生成函数
    data.class_change.php *角色转职资格验证函数
    data.create.php *可制作道具列表生成函数
    data.enchant.php *道具附魔效果添加函数
    data.gd_item.php *道具分类展示页面
    data.gd_job.php *职业信息展示页面
    data.gd_judge.php *游戏判定条件说明页面
    data.gd_monster.php *怪物信息展示页面
    data.item.php *道具数据加载函数
    data.job.php *职业数据加载函数
    data.judge_setup.php *判定条件数据加载函数
    data.judge.php *战斗判定决策函数
    data.land_appear.php *可进入地图列表生成函数
    data.land_info.php *地图信息加载函数
    data.manual0.php *游戏指南手册文档
    data.manual1.php *高级游戏指南文档
    data.monster.php *怪物数据创建函数
    data.skill.php *技能数据加载函数（已改造为接口，数据已迁移至game.db）
    data.skilltree.php *角色技能树加载函数
    data.town_appear.php *城镇设施出现条件控制函数
    data.tutorial.php *游戏新手教程文档

db/ *数据库
    auction_log.dat
    auction.dat
    managed.dat
    ranking.dat
    register.dat
    update.dat
    username.dat
    game.db *已进行sqlite化改造的功能：
                1. 战斗日志 - battle_logs
                2. 城镇广场bbs - town_bbs
                3. 技能 - skills
                4. 自动维护管理 - maintenance_schedule
                5. 操作日志 -  manage_logs
  
image/ *image资源
    char/
        *.gif(s)
    char_rev/
        *.gif(s)
    icon/
        *.gif(s)
    manual/
        *.gif(s)
    other/
        *.gif(s)
    *.gif(s)

union/ *BOSS数据
    *.dat(s) *格式为（例）：
                MonsterNumber=2007
                LastDefeated=120025074
                HP=3420
                SP=122

user/
    {username}/
        *.dat(s) *主要为角色（编号.dat）+ data.dat + item.dat

admin.php *管理入口
image.php *战斗场景图像生成
index.php *项目入口
setting.php *全局配置
```

### 数据结构(game.db)
```
battle_logs
    id INTEGER PRIMARY KEY AUTOINCREMENT,		-- 战斗日志ID
    battle_time INTEGER NOT NULL,				-- 
    team0_name TEXT NOT NULL,					-- 
    team1_name TEXT NOT NULL,					-- 
    team0_count INTEGER NOT NULL,				-- 
    team1_count INTEGER NOT NULL,				-- 
    team0_avg_level REAL NOT NULL,				-- team0平均等级
    team1_avg_level REAL NOT NULL,				-- team1平均等级
    winner INTEGER NOT NULL,					-- 胜方
    total_turns INTEGER NOT NULL,				-- 回合数
    battle_content TEXT NOT NULL,				-- 日志内容
    battle_type TEXT NOT NULL CHECK(battle_type IN ('normal', 'union', 'rank'))		-- 日志类型

town_bbs
    id INTEGER PRIMARY KEY AUTOINCREMENT,	-- 留言ID
    user_name TEXT NOT NULL,				-- 用户名
    message TEXT NOT NULL,					-- 具体信息
    post_time INTEGER NOT NULL				-- 留言时间

skills
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
    pierce BOOLEAN DEFAULT 0,   -- 是否无视防御
    poison INTEGER,             -- 中毒概率
    knockback INTEGER,          -- 击退概率
    summon TEXT,                -- 召唤怪物ID
    effects TEXT,               -- JSON格式的特殊效果
    limits TEXT,                -- JSON格式的限制条件
    passive BOOLEAN DEFAULT 0,  -- 是否被动技能
    p_effects TEXT,             -- JSON格式的被动效果
    category INTEGER            -- 技能分类

maintenance_schedule
    task_name TEXT PRIMARY KEY,  -- 任务名称
    next_run INTEGER NOT NULL,   -- 下次执行时间戳
    last_run INTEGER,             -- 上次执行时间
    interval_sec INTEGER         -- 执行间隔(秒)

manage_logs
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_time INTEGER NOT NULL,       -- 时间戳
    event_type TEXT NOT NULL,        -- 事件类型
    user_id TEXT,                    -- 操作用户ID
    target_id TEXT,                  -- 目标ID
    details TEXT NOT NULL,           -- 详情
    ip_address TEXT                  -- 操作IP
......

```

### 暂时就这样
