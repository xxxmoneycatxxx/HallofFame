<?php

/**
 * 地图信息加载函数
 * 
 * 功能说明：
 * 1. 根据地图标识符($land)加载对应的地图信息和怪物分布数据
 * 2. 提供地图的详细描述和怪物生成规则
 * 
 * 参数说明：
 * @param string $land 地图标识符（如"gb0", "ac1", "snow2"等）
 * 
 * 返回说明：
 * @return array 包含两个元素的数组：
 *   - [0]: 地图信息数组
 *     - 'name': 地图中文名称
 *     - 'name0': 地图英文名称
 *     - 'land': 地图类型（草地/洞穴/海洋等）
 *     - 'proper': 地图属性描述（等级范围等）
 *   - [1]: 怪物分布数组
 *     - 键: 怪物ID
 *     - 值: [出现权重, 稀有度] 
 *         - 权重: 出现概率权重（数值越大出现概率越高）
 *         - 稀有度: 0=稀有, 1=普通
 * 
 * 地图类型分类：
 * 1. 草地(grass): 哥布林系列地图(gb0-gb2)
 * 2. 洞穴(cave): 古之洞穴系列(ac0-ac4)
 * 3. 海洋(sea/ocean): 海岸/海洋系列(sea0-sea1, ocean0)
 * 4. 沙漠(sand): 沙漠/掠夺者系列(sand0, des01, plund01)
 * 5. 火山(mount/lava): 火山系列(mt0, volc0-volc1)
 * 6. 沼泽(swamp): 沼泽/村庄系列(swamp0-swamp1)
 * 7. 雪地(snow): 滴冻山系列(snow0-snow2)
 * 8. 废弃地(aband): Blow山脉地区(blow01)
 * 
 * 怪物分布规则：
 * 1. 权重系统：怪物出现的概率由其权重值决定
 * 2. 稀有度标记：0表示稀有怪物，1表示普通怪物
 * 3. 特殊标记：权重为0表示该怪物不会自然出现
 * 
 * 示例：
 * LandInformation("gb0")返回：
 * [
 *   [
 *     "name" => "最弱的哥布林",
 *     "name0" => "Goblin Training",
 *     "land" => "grass",
 *     "proper" => "Lv1"
 *   ],
 *   [
 *     1000 => [300, 1],
 *     1001 => [300, 1]
 *   ]
 * ]
 * 
 * 注意事项：
 * 1. 地图标识符需严格匹配，否则将返回空数据
 * 2. 怪物分布数据用于战斗系统的敌人生成
 * 3. 权重值用于计算怪物出现的概率分布
 */
function LandInformation($land)
{
	switch ($land) {
		case "gb0":
			$land	= array(
				"name"	=> "最弱的哥布林 [LV1]",
				"name0"	=> "Goblin Training",
				"land"	=> "grass",
				"proper"	=> "Lv1",
			);
			$monster	= array(
				1000	=> array(300, 1),
				1001	=> array(300, 1),
			);
			break;
		case "gb1":
			$land	= array(
				"name"	=> "强一点的哥布林 [LV1~LV10]",
				"name0"	=> "The Goblins",
				"land"	=> "grass",
				"proper"	=> "Lv1-5",
			);
			$monster	= array(
				1000	=> array(300, 1),
				1001	=> array(300, 1),
				1002	=> array(150, 1),
				1003	=> array(150, 1),
				1004	=> array(100, 1),
				1005	=> array(100, 1),
				1006	=> array(100, 1),
				1007	=> array(100, 1),
			);
			break;
		case "gb2":
			$land	= array(
				"name"	=> "哥布林战士 [LV6~LV15]",
				"name0"	=> "The Goblin Warriors",
				"land"	=> "grass",
				"proper"	=> "Lv3-8",
			);
			$monster	= array(
				1005	=> array(100, 1),
				1006	=> array(100, 1),
				1007	=> array(100, 1),
				1008	=> array(100, 1),
				1009	=> array(100, 1),
			);
			break;
		case "ac0":
			$land	= array(
				"name"	=> "古之洞穴",
				"name0"	=> "TheAncientCave",
				"land"	=> "cave",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1010	=> array(0, 1), // 实际上并不出现
				1011	=> array(0, 1), // 由1012代替
				1012	=> array(500, 0),
				1013	=> array(150, 1),
				1014	=> array(150, 1),
				1015	=> array(150, 1),
				1016	=> array(100, 0),
				1017	=> array(50, 0),
			);
			break;
		case "ac1":
			$land	= array(
				"name"	=> "古之洞穴(B2)",
				"name0"	=> "TheAncientCave2F",
				"land"	=> "cave",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1018	=> array(100, 1),
				1019	=> array(100, 1),
				1020	=> array(100, 1),
				1021	=> array(100, 1),
				1022	=> array(30, 1),
			);
			break;
		case "ac2":
			$land	= array(
				"name"	=> "古之洞穴(B3)",
				"name0"	=> "TheAncientCave3F",
				"land"	=> "cave",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1023	=> array(400, 1),
				1024	=> array(300, 1),
				1025	=> array(35, 0),
			);
			break;
		case "ac3":
			$land	= array(
				"name"	=> "古之洞穴(B4)",
				"name0"	=> "TheAncientCave4F",
				"land"	=> "cave",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1027	=> array(20, 1),
				1028	=> array(80, 1),
				1029	=> array(100, 1),
			);
			break;
		case "ac4":
			$land	= array(
				"name"	=> "古之洞穴(B5)",
				"name0"	=> "TheAncientCave5F",
				"land"	=> "cave",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1030	=> array(150, 1),
				1031	=> array(150, 1),
				1032	=> array(150, 1),
				1033	=> array(200, 1),
				1034	=> array(70, 1),
				1035	=> array(10, 0),
			);
			break;

		case "sea0":
			$land	= array(
				"name"	=> "海岸 [LV16~LV21]",
				"name0"	=> "",
				"land"	=> "sea",
				"proper"	=> "99-99",
			);
			$monster	= array(
				1060	=> array(7, 0),
				1061	=> array(100, 1),
				1062	=> array(100, 1),
				1063	=> array(100, 1),
				1064	=> array(100, 1),
				1065	=> array(100, 1),
				1066	=> array(5, 0),
			);
			break;
		case "sea1":
			$land	= array(
				"name"	=> "海(西海岸) [LV21]",
				"name0"	=> "",
				"land"	=> "sea",
				"proper"	=> "99-99",
			);
			$monster	= array(
				1065	=> array(100, 1),
			);
			break;

		case "ocean0":
			$land	= array(
				"name"	=> "海洋 [LV33~LV39]",
				"name0"	=> "",
				"land"	=> "ocean",
				"proper"	=> "99-99",
			);
			$monster	= array(
				1067	=> array(8, 0),
				1068	=> array(100, 1),
				1069	=> array(100, 1),
				1070	=> array(100, 1),
				1071	=> array(100, 1),
			);
			break;

		case "sand0":
			$land	= array(
				"name"	=> "沙漠 [LV23~LV25]",
				"name0"	=> "",
				"land"	=> "sand",
				"proper"	=> "99-99",
			);
			$monster	= array(
				1083	=> array(100, 1),
				1084	=> array(7, 0),
				1085	=> array(100, 1),
				1086	=> array(100, 1),
				1087	=> array(100, 1),
			);
			break;
		case "mt0":
			$land	= array(
				"name"	=> "火山入口 [LV29~LV34]",
				"name0"	=> "",
				"land"	=> "mount",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1072	=> array(100, 1),
				1073	=> array(100, 1),
				1078	=> array(100, 1),
				1082	=> array(5, 0),
			);
			break;
		case "volc0":
			$land	= array(
				"name"	=> "火山中腹 [LV34~LV41]",
				"name0"	=> "",
				"land"	=> "lava",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1073	=> array(80, 1),
				1074	=> array(100, 1),
				1077	=> array(100, 1),
				1080	=> array(100, 1),
				1088	=> array(10, 0),
			);
			break;
		case "volc1":
			$land	= array(
				"name"	=> "火山顶上 [LV35~LV41]",
				"name0"	=> "",
				"land"	=> "lava",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1074	=> array(60, 1),
				1075	=> array(170, 1),
				1077	=> array(120, 1),
				1080	=> array(100, 1),
				1081	=> array(8, 0),
				1076	=> array(3, 0),
			);
			break;
		case "swamp0":
			$land	= array(
				"name"	=> "沼泽 [LV29~LV36]",
				"name0"	=> "",
				"land"	=> "swamp",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1053	=> array(100, 1),
				1054	=> array(100, 1),
				1055	=> array(100, 1),
				1056	=> array(100, 1),
				1057	=> array(100, 1),
				1058	=> array(20, 0),
			);
			break;
		case "swamp1":
			$land	= array(
				"name"	=> "村庄 [LV36~LV42]",
				"name0"	=> "",
				"land"	=> "swamp",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1053	=> array(70, 1),
				1050	=> array(100, 1),
				1051	=> array(150, 1),
				1052	=> array(100, 1),
				1059	=> array(8, 0),
			);
			break;
		case "snow0":
			$land	= array(
				"name"	=> "滴冻入口 [LV ??]",
				"name0"	=> "FrostyMountain(foot)",
				"land"	=> "snow",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1036	=> array(100, 1),
				1037	=> array(100, 1),
				1038	=> array(100, 1),
				1039	=> array(100, 1),
			);
			break;
		case "snow1":
			$land	= array(
				"name"	=> "滴冻中腹 [LV ??]",
				"name0"	=> "FrostyMountain(HalfWay)",
				"land"	=> "snow",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1040	=> array(100, 1),
				1041	=> array(100, 1),
				1042	=> array(100, 1),
				1043	=> array(100, 1),
				1044	=> array(40, 1),
				1045	=> array(15, 0),
				1046	=> array(10, 0),
			);
			break;
		case "snow2":
			$land	= array(
				"name"	=> "滴冻顶上 [LV ??]",
				"name0"	=> "FrostyMountain(Top)",
				"land"	=> "snow",
				"proper"	=> "Lv??",
			);
			$monster	= array(
				1089	=> array(80, 1),
				1090	=> array(80, 1),
				1044	=> array(30, 1),
				1047	=> array(100, 1),
				1048	=> array(60, 1),
				1049	=> array(5, 0),
				1046	=> array(10, 0),
			);
			break;
		case "des01":
			$land	= array(
				"name"	=> "掠夺者的沙漠 [LV ??]",
				"name0"	=> "Plunderer's Sandland",
				"land"	=> "sand",
				"proper"	=> "Lv5-10",
			);
			$monster	= array(
				1005	=> array(100, 1),
				1006	=> array(100, 1),
				1007	=> array(100, 1),
				1008	=> array(100, 1),
				1009	=> array(100, 1),
			);
			break;
		case "plund01":
			$land	= array(
				"name"	=> "贼之巢穴 [LV ??]",
				"name0"	=> "Plunderer's Nest",
				"land"	=> "sand",
				"proper"	=> "Lv10-15",
			);
			$monster	= array(
				1005	=> array(100, 1),
				1006	=> array(100, 1),
				1007	=> array(100, 1),
				1008	=> array(100, 1),
				1009	=> array(100, 1),
			);
			break;
		case "blow01":
			$land	= array(
				"name"	=> "Blow山脉地区 [LV ??]",
				"name0"	=> "TheBlowHills",
				"land"	=> "aband",
				"proper"	=> "Lv20-30",
			);
			$monster	= array(
				1018	=> array(100, 1),
				1019	=> array(100, 1),
				1020	=> array(100, 1),
				1021	=> array(100, 1),
				1022	=> array(30, 1),
			);
			break;
		case "horh":
			$land	= array(
				"name"	=> "天堂或地狱 [LV ??]",
				"name0"	=> "Heaven or Hell",
				"land"	=> "sea",
				"proper"	=> "Lv99",
			);
			$monster	= array(
				5100	=> array(100, 1),
				5101	=> array(100, 1),
				5102	=> array(100, 1),
				5103	=> array(100, 1),
				5104	=> array(100, 1),
			);
			break;
	}
	return array($land, $monster);
}
