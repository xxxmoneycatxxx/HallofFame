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
                                5. data.enchant.php - 装备附魔（精炼？）数据
    list_skill.php *游戏技能百科全书，通过PHP脚本从data.skill.php加载所有技能信息
    set_action.php *怪物行动模式设置及生成工具

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

data/ *游戏数据（？）事实上不太好分类
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
    data.skill.php *技能数据加载函数
    data.skilltree.php *角色技能树加载函数
    data.town_appear.php *城镇设施出现条件控制函数
    data.tutorial.php *游戏新手教程文档

db/ *数据库？但这个项目是用写入文件的方式存数据。总之是放数据的地方
    auction_log.dat
    auction.dat
    ctrltime.dat
    managed.dat
    ranking.dat
    register.dat
    update.dat
    username.dat
    game.db *战斗日志sqlite化改造
  
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

数据结构(game.db)
```
battle_logs
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    battle_time INTEGER NOT NULL,
    team0_name TEXT NOT NULL,
    team1_name TEXT NOT NULL,
    team0_count INTEGER NOT NULL,
    team1_count INTEGER NOT NULL,
    team0_avg_level REAL NOT NULL,
    team1_avg_level REAL NOT NULL,
    winner INTEGER NOT NULL,
    total_turns INTEGER NOT NULL,
    battle_content TEXT NOT NULL,
    battle_type TEXT NOT NULL

town_bbs
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_name TEXT NOT NULL,
    message TEXT NOT NULL,
    post_time INTEGER NOT NULL


......

```

### 暂时就这样
