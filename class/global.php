<?php

/**
 * 游戏核心功能库
 * 
 * 功能说明：
 * 1. 提供游戏商店、拍卖行、精炼系统等核心功能
 * 2. 实现用户账户管理、闲置账户清理机制
 * 3. 处理战斗日志记录、显示和分析
 * 4. 管理游戏道具、技能和角色数据
 * 5. 实现游戏内货币和资源管理系统
 * 
 * 主要功能模块：
 * 1. 商店系统：
 *    - 商品列表管理
 *    - 道具类型分类
 *    - 定价与销售机制
 * 2. 用户管理：
 *    - 闲置账户检测与清理
 *    - 定期维护任务
 *    - 用户注册验证
 * 3. 文件操作：
 *    - 文件锁定与并发控制
 *    - 数据解析与存储
 *    - 日志记录与管理
 * 4. 战斗系统：
 *    - 战斗日志记录
 *    - 日志分类与显示
 *    - 详细战斗回放
 * 5. 数据展示：
 *    - 技能详细描述
 *    - 道具属性展示
 *    - 游戏数据手册
 * 
 * 技术特点：
 * 1. 高效文件处理：
 *    - 文件锁定机制保证数据一致性
 *    - 结构化数据存储与解析
 * 2. 自动化维护：
 *    - 定期执行账户清理
 *    - 智能避开高峰时段
 * 3. 多语言支持：
 *    - 中英双语界面元素
 *    - 本地化技能描述
 * 4. 响应式设计：
 *    - 移动设备检测与适配
 *    - 简洁高效的数据展示
 * 
 * 核心功能：
 * 1. 商店与拍卖：
 *    - 定义可交易道具类型
 *    - 精炼系统规则
 *    - 道具定价策略
 * 2. 账户维护：
 *    - 自动删除长期未登录账户
 *    - 排行榜数据同步
 *    - 资源释放管理
 * 3. 战斗分析：
 *    - 战斗日志分类存储
 *    - 胜负统计展示
 *    - 详细回合记录查看
 * 4. 数据手册：
 *    - 技能效果详细说明
 *    - 道具属性展示
 *    - 游戏机制说明文档
 * 
 * 使用注意事项：
 * 1. 文件操作：
 *    - 使用FileLock()保证文件操作安全
 *    - 定期调用RegularControl()执行维护
 * 2. 用户管理：
 *    - 闲置账户由IsAbandoned()检测
 *    - DeleteAbandonAccount()执行清理
 * 3. 多语言：
 *    - 技能描述已本地化为中文
 *    - 界面元素支持中英双语
 * 4. 移动适配：
 *    - isMobile()检测设备类型
 *    - 界面自动适配移动端
 * 
 * 全局常量：
 * 1. CONTROL_PERIOD: 系统维护周期
 * 2. ABANDONED: 账户闲置阈值
 * 3. SELLING_PRICE: 道具出售价格系数
 */

// 初始化数据库连接
if (!function_exists('initDatabase')) {
	function initDatabase()
	{
		// 确保配置常量已定义
		if (!defined('DB_PATH')) {
			throw new RuntimeException("数据库配置未定义，请检查 setting.php");
		}

		$dbPath = DB_PATH;
		$dbDir = dirname($dbPath);

		try {
			// 自动创建数据库目录
			if (!is_dir($dbDir)) {
				if (!mkdir($dbDir, 0755, true)) {
					throw new RuntimeException("无法创建数据库目录: $dbDir");
				}
			}

			// 初始化数据库连接
			$db = new PDO('sqlite:' . $dbPath, null, null, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 3,  // 查询超时3秒
				PDO::ATTR_PERSISTENT => false // 禁用持久连接
			]);

			// 首次运行时创建表结构
			if (!file_exists($dbPath) || filesize($dbPath) == 0) {
				$db->exec("PRAGMA journal_mode = WAL;"); // 启用WAL模式提高并发性能

				$db->exec("CREATE TABLE IF NOT EXISTS battle_logs (
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
            	)");

				$db->exec("CREATE TABLE IF NOT EXISTS town_bbs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,	-- 留言ID
                user_name TEXT NOT NULL,				-- 用户名
                message TEXT NOT NULL,					-- 具体信息
                post_time INTEGER NOT NULL				-- 留言时间
           		)");

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

				// 添加索引优化查询性能
				$db->exec("CREATE INDEX IF NOT EXISTS idx_battle_type ON battle_logs(battle_type)");
				$db->exec("CREATE INDEX IF NOT EXISTS idx_battle_time ON battle_logs(battle_time DESC)");
				$db->exec("CREATE INDEX IF NOT EXISTS idx_bbs_time ON town_bbs(post_time DESC)");

				// 添加视图简化复杂查询
				$db->exec("CREATE VIEW IF NOT EXISTS v_battle_stats AS
                SELECT battle_type, 
					COUNT(*) AS total, 
					AVG(total_turns) AS avg_turns,
					SUM(CASE WHEN winner = 0 THEN 1 ELSE 0 END) AS team0_wins,
					SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS team1_wins
                FROM battle_logs
                GROUP BY battle_type");
			}

			return $db;
		} catch (PDOException $e) {
			// 数据库连接失败时记录详细错误
			error_log("数据库连接失败: " . $e->getMessage());
			error_log("数据库路径: $dbPath");

			// 返回伪连接对象防止系统崩溃
			return new class {
				public function prepare($sql)
				{
					throw new PDOException("数据库不可用");
				}
				public function query($sql)
				{
					throw new PDOException("数据库不可用");
				}
			};
		} catch (Exception $e) {
			// 其他类型异常处理
			error_log("系统错误: " . $e->getMessage());
			return null;
		}
	}
}
$GLOBALS['DB'] = initDatabase();


//////////////////////////////////////////////////
//	商店列表
function ShopList()
{
	return array(
		1002,
		1003,
		1004,
		1100,
		1101,
		1200,
		1700,
		1701,
		1702,
		1703,
		1800,
		1801,
		2000,
		2001,
		3000,
		3001,
		3002,
		3100,
		3101,
		5000,
		5001,
		5002,
		5003,
		5100,
		5101,
		5102,
		5103,
		5200,
		5201,
		5202,
		5203,
		5500,
		5501,
		7000,
		7001,
		7500,
		7510,
		7511,
		7512,
		7513,
		7520,
		8000,
		8009,
		8012
	);
}
//////////////////////////////////////////////////
//	可以拍卖的道具类型
function CanExhibitType()
{
	return array(
		"剑"	=> "1",
		"双手剑"	=> "1",
		"匕首"	=> "1",
		"魔杖"	=> "1",
		"杖"	=> "1",
		"弓"	=> "1",
		"鞭"	=> "1",
		"盾"	=> "1",
		"书"	=> "1",
		"甲"	=> "1",
		"衣服"	=> "1",
		"长袍"	=> "1",
		"道具"	=> "1",
		"材料"	=> "1",
	);
}
//////////////////////////////////////////////////
//	可以精炼的道具类型
function CanRefineType()
{
	return array(
		"剑",
		"双手剑",
		"匕首",
		"魔杖",
		"杖",
		"弓",
		"鞭",
		"盾",
		"书",
		"甲",
		"衣服",
		"长袍",
	);
}
//////////////////////////////////////////////////
//	删除过期用户
function DeleteAbandonAccount()
{
	$list	= glob(USER . "*");
	$now	= time();
	// 用户列表
	foreach ($list as $file) {
		if (!is_dir($file)) continue;
		$UserID	= substr($file, strrpos($file, "/") + 1);
		$user	= new user($UserID, true);
		// 用户将被删除
		if ($user->IsAbandoned()) {
			// 排行榜相关
			if (!isset($Ranking)) {
				include_once(CLASS_RANKING);
				$Ranking	= new Ranking();
				$RankChange	= false; // 排行榜不可被修改
			}
			// 消除排名
			if ($Ranking->DeleteRank($UserID)) {
				$RankChange	= true; // 排行榜可以修改了
			}
			RecordManage(date("Y M d G:i:s", $now) . ": user " . $user->id . " deleted.");
			$user->DeleteUser(false); //设置false则不可从排行榜删除
		}
		// 不可删除
		else {
			$user->fpCloseAll();
			unset($user);
		}
	}
	// 用户验证后对排行榜的处理
	if ($RankChange === true)
		$Ranking->SaveRanking();
	else if ($RankChange === false)
		$Ranking->fpclose();
	//print("<pre>".print_r($list,1)."</pre>");
}
//////////////////////////////////////////////////
//	定期自动管理相关
function RegularControl($value = null)
{
	/*
			服务器负载过大则时间段推迟。
			PM 7:00 - AM 2:00不处理。
			※时间设置请慎重！
		*/
	if (19 <= date("H") || date("H") <= 1)
		return false;
	$now	= time();
	$fp		= FileLock(CTRL_TIME_FILE, true);
	if (!$fp)
		return false;
	//$ctrltime	= file_get_contents(CTRL_TIME_FILE);
	$ctrltime	= trim(fgets($fp, 1024));
	// 如果未到周期，则结束
	if ($now < $ctrltime) {
		fclose($fp);
		unset($fp);
		return false;
	}
	// 管理の処理
	RecordManage(date("Y M d G:i:s", $now) . ": auto regular control by {$value}.");
	DeleteAbandonAccount(); //设置为1 清除过期用户
	// 定期管理结束后，写入下一个管理时间并结束。
	WriteFileFP($fp, $now + CONTROL_PERIOD);
	fclose($fp);
	unset($fp);
}
//////////////////////////////////////////////////
//	$id 是否登录过
function is_registered($id)
{
	if ($registered = @file(REGISTER)) {
		if (array_search($id . "\n", $registered) !== false && !preg_match("/[\.\/]+/", $id)) // 改行記号必須
			return true;
		else
			return false;
	}
}
//////////////////////////////////////////////////
//	锁文件并返回文件指针
function FileLock($file, $noExit = false)
{
	if (!file_exists($file))
		return false;
	$fp	= @fopen($file, "r+") or die("Error!");
	if (!$fp)
		return false;
	$i = 0;
	do {
		if (flock($fp, LOCK_EX | LOCK_NB)) {
			stream_set_write_buffer($fp, 0);
			return $fp;
		} else {
			usleep(10000); //0.01秒为单位
			$i++;
		}
	} while ($i < 5);
	//if($noExit) {
	//	return false;
	//} else {
	//	ob_clean();
	//	exit("file lock error.");
	//}
	//flock($fp, LOCK_EX);//排他
	//flock($fp, LOCK_SH);//共有ロック
	//flock($fp,LOCK_EX);
	return $fp;
}
//////////////////////////////////////////////////
//文件写入（参数：文件指针）
function WriteFileFP($fp, $text, $check = false)
{
	// 检查文件指针是否有效
	if (!is_resource($fp)) {
		error_log("WriteFileFP: 无效的文件指针");
		return false;
	}

	if (!$check && !trim($text))
		return false;

	// 现在可以安全操作
	ftruncate($fp, 0);
	rewind($fp);
	fputs($fp, $text);
}
//////////////////////////////////////////////////
//	写入文件
function WriteFile($file, $text, $check = false)
{
	if (!$check && !$text) //如果$text为空，则结束
		return false;
	/*if(file_exists($file)):
			ftruncate()
		else:
			$fp	= fopen($file,"w+");*/
	$fp	= fopen($file, "w+");
	flock($fp, LOCK_EX);
	fputs($fp, $text);
}

//////////////////////////////////////////////////
//	读取文件并将其存储在数组中(参数:文件指针)
function ParseFileFP($fp)
{
	if (!$fp) return false;
	while (!feof($fp)) {
		$str	= fgets($fp);
		$str	= trim($str);
		if (!$str) continue;
		$pos	= strpos($str, "=");
		if ($pos === false)
			continue;
		$key	= substr($str, 0, $pos);
		$val	= substr($str, ++$pos);
		$data[$key]	= trim($val);
	}
	//print("<pre>");
	//print_r($data);
	//print("</pre>");
	if ($data)
		return $data;
	else
		return false;
}
//////////////////////////////////////////////////
//	ファイルを粕んで芹误に呈羌
function ParseFile($file)
{
	// 添加文件存在检查
	if (!file_exists($file)) {
		// 记录错误日志并返回空数组
		error_log("ParseFile error: File not found - $file");
		return [];
	}

	$fp = @fopen($file, "r+");
	if (!$fp) {
		// 添加错误日志记录
		error_log("ParseFile error: Unable to open file - $file");
		return [];
	}

	// 添加文件锁定超时处理
	$locked = false;
	$attempts = 0;
	while ($attempts < 5) {
		if (flock($fp, LOCK_EX | LOCK_NB)) {
			$locked = true;
			break;
		}
		usleep(100000); // 等待100ms
		$attempts++;
	}

	if (!$locked) {
		fclose($fp);
		error_log("ParseFile error: File lock timeout - $file");
		return [];
	}

	$data = [];
	while (!feof($fp)) {
		$str = fgets($fp);
		$str = trim($str);
		if (!$str) continue;

		$pos = strpos($str, "=");
		if ($pos === false) continue;

		$key = substr($str, 0, $pos);
		$val = substr($str, $pos + 1);
		$data[$key] = trim($val);
	}

	flock($fp, LOCK_UN);
	fclose($fp);

	return $data;
}
//////////////////////////////////////////////////
//	
function UserAmount()
{
	static $amount;

	if ($amount) {
		return $amount;
	} else {
		$amount	= count(glob(USER . "*"));
		return $amount;
	}
}
//////////////////////////////////////////////////
//	
function JudgeList()
{

	// 极瓢粕み哈み(forでル〖プさせてるから痰绿な借妄)
	if (JUDGE_LIST_AUTO_LOAD) {
		for ($i = 1000; $i < 2500; $i++) {
			if (LoadJudgeData($i) !== false)
				$list[] = $i;
		}
		return $list;
		// 缄瓢(纳裁した冉们は极尸で今き颅せ)
	} else {
		return array(
			1000,
			1001,
			1099,
			1100,
			1101,
			1105,
			1106,
			1110,
			1111,
			1121,
			1125,
			1126,
			1199,
			1200,
			1201,
			1205,
			1206,
			1210,
			1211,
			1221,
			1225,
			1226,
			1399,
			1400,
			1401,
			1405,
			1406,
			1410,
			1449,
			1450,
			1451,
			1455,
			1456,
			1499,
			1500,
			1501,
			1505,
			1506,
			1510,
			1511,
			1549,
			1550,
			1551,
			1555,
			1556,
			1560,
			1561,
			1599,
			1600,
			1610,
			1611,
			1612,
			1613,
			1614,
			1615,
			1616,
			1617,
			1618,
			1699,
			1700,
			1701,
			1710,
			1711,
			1712,
			1715,
			1716,
			1717,
			1749,
			1750,
			1751,
			1752,
			1755,
			1756,
			1757,
			1799,
			1800,
			1801,
			1805,
			1819,
			1820,
			1821,
			1825,
			1839,
			1840,
			1841,
			1845,
			1849,
			1850,
			1851,
			1855,
			1899,
			1900,
			1901,
			1902,
			1919,
			1920,
			1939,
			1940,
		);
	}
}

//////////////////////////////////////////////////
//	お垛の山绩数及
function MoneyFormat($number)
{
	return '$&nbsp;' . number_format($number);
}
//////////////////////////////////////////////////
//	
function ItemSellPrice($item)
{
	$price	= (isset($item["sell"]) ? $item["sell"] : round($item["buy"] * SELLING_PRICE));
	return $price;
}

//////////////////////////////////////////////////
//	显示日志列表
function ShowLogList()
{
	print("<div style=\"margin:15px\">\n");
	print("<a href=\"?log\" class=\"a0\">全部</a> ");
	print("<a href=\"?clog\">普通</a> ");
	print("<a href=\"?ulog\">BOSS战</a> ");
	print("<a href=\"?rlog\">排行战</a>");

	$db = $GLOBALS['DB'];

	// 普通战斗日志（原Recent Battles）
	print("<h4>最近的战斗 - <a href=\"?clog\">全部显示</a>(Recent Battles)</h4>\n");
	$stmt = $db->prepare("SELECT * FROM battle_logs 
                         WHERE battle_type = 'normal' 
                         ORDER BY battle_time DESC LIMIT 30");
	$stmt->execute();
	$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($logs as $log) {
		BattleLogDetail($log);
	}

	// BOSS战区块（原Union Battle）
	print("<h4>BOSS战 - <a href=\"?ulog\">全部显示</a>(Union Battle Log)</h4>\n");
	$stmt = $db->prepare("SELECT * FROM battle_logs 
                         WHERE battle_type = 'union' 
                         ORDER BY battle_time DESC LIMIT 30");
	$stmt->execute();
	$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($logs as $log) {
		BattleLogDetail($log);
	}

	// 排行战区块（原Rank Battle）
	print("<h4>排名战 - <a href=\"?rlog\">全部显示</a>(Rank Battle Log)</h4>\n");
	$stmt = $db->prepare("SELECT * FROM battle_logs 
                         WHERE battle_type = 'rank' 
                         ORDER BY battle_time DESC LIMIT 30");
	$stmt->execute();
	$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($logs as $log) {
		BattleLogDetail($log);
	}

	print("</div>\n");
}
//////////////////////////////////////////////////
//	显示战斗日志(普通)
function LogShowCommon()
{
	print("<div style=\"margin:15px\">\n");

	print("<a href=\"?log\">全部</a> ");
	print("<a href=\"?clog\" class=\"a0\">普通</a> ");
	print("<a href=\"?ulog\">BOSS战</a> ");
	print("<a href=\"?rlog\">排行战</a>");
	// common
	print("<h4>最近的战斗 - 全记录(Recent Battles)</h4>\n");

	$db = $GLOBALS['DB'];
	$stmt = $db->prepare("SELECT * FROM battle_logs 
                         WHERE battle_type = 'normal' 
                         ORDER BY battle_time DESC");
	$stmt->execute();
	$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($logs as $log) {
		BattleLogDetail($log);
	}

	print("</div>\n");
}
//////////////////////////////////////////////////
//	显示战斗日志(BOSS)
function LogShowUnion()
{
	print("<div style=\"margin:15px\">\n");

	print("<a href=\"?log\">全部</a> ");
	print("<a href=\"?clog\">普通</a> ");
	print("<a href=\"?ulog\" class=\"a0\">BOSS战</a> ");
	print("<a href=\"?rlog\">排行战</a>");
	// union
	print("<h4>BOSS战 - 全记录(Union Battle Log)</h4>\n");

	$db = $GLOBALS['DB'];
	$stmt = $db->prepare("SELECT * FROM battle_logs 
                         WHERE battle_type = 'union' 
                         ORDER BY battle_time DESC");
	$stmt->execute();
	$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($logs as $log) {
		BattleLogDetail($log);
	}

	print("</div>\n");
}
//////////////////////////////////////////////////
//	显示战斗日志(Ranking / 排名)
function LogShowRanking()
{
	print("<div style=\"margin:15px\">\n");

	print("<a href=\"?log\">全部</a> ");
	print("<a href=\"?clog\">普通</a> ");
	print("<a href=\"?ulog\">BOSS战</a> ");
	print("<a href=\"?rlog\" class=\"a0\">排行战</a>");

	$db = $GLOBALS['DB'];
	$stmt = $db->prepare("SELECT * FROM battle_logs 
                         WHERE battle_type = 'rank' 
                         ORDER BY battle_time DESC");
	$stmt->execute();
	$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($logs as $log) {
		BattleLogDetail($log);
	}

	print("</div>\n");
}
//////////////////////////////////////////////////
//	战斗日志总结（简略）
function BattleLogDetail($log)
{
	// 检查 $log 是否是有效的数组
	if (!is_array($log) || !isset($log['battle_time'])) {
		return; // 或记录错误
	}

	$date = date("m/d H:i:s", (int)$log['battle_time']); // 确保转换为整数

	// 根据类型生成链接
	$linkType = "";
	$logParam = $log['id'];

	switch ($log['battle_type']) {
		case 'rank':
			$linkType = "rlog";
			break;
		case 'union':
			$linkType = "ulog";
			break;
		default:
			$linkType = "log";
	}

	print("[ <a href=\"?{$linkType}={$logParam}\">{$date}</a> ]&nbsp;\n");
	print("<span class=\"bold\">战斗{$log['total_turns']}</span>回合&nbsp;\n");

	// 胜负显示逻辑 - 修复后的正确逻辑
	$winner = (int)$log['winner'];

	if ($winner === TEAM_0) {  // 团队0胜利
		print("[胜]&nbsp;<span class=\"recover\">{$log['team0_name']}</span>");
		print("({$log['team0_count']}:{$log['team0_avg_level']})");
		print(" vs ");
		print("<span class=\"dmg\">{$log['team1_name']}</span>");
		print("({$log['team1_count']}:{$log['team1_avg_level']})");
	} elseif ($winner === TEAM_1) {  // 团队1胜利
		print("[败]&nbsp;<span class=\"dmg\">{$log['team0_name']}</span>");
		print("({$log['team0_count']}:{$log['team0_avg_level']})");
		print(" vs ");
		print("<span class=\"recover\">{$log['team1_name']}</span>");
		print("({$log['team1_count']}:{$log['team1_avg_level']})");
	} else {  // 平局
		print("[平]&nbsp;{$log['team0_name']}");
		print("({$log['team0_count']}:{$log['team0_avg_level']})");
		print(" vs ");
		print("{$log['team1_name']}");
		print("({$log['team1_count']}:{$log['team1_avg_level']})");
	}

	print("<br />");
}
//////////////////////////////////////////////////
//	显示战斗日志详细内容
function ShowBattleLog($logId, $type = false)
{
	$db = $GLOBALS['DB'];

	$stmt = $db->prepare("SELECT * FROM battle_logs WHERE id = ?");
	$stmt->execute([$logId]);
	$log = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$log) {
		print("没有找到记录");
		return false;
	}

	print('<div style="padding:15px 0;width:100%;text-align:center" class="break">');
	print("<h2>战斗记录*</h2>");
	print("\n战斗开始于<br />");
	print(date("m/d H:i:s", $log['battle_time']));
	print("</div>\n");

	print($log['battle_content']);
}
//////////////////////////////////////////////////
//	显示技能描述内容
function ShowSkillDetail($skill, $radio = false)
{
	if (!$skill) return false;

	if ($radio)
		print('<input type="radio" name="newskill" value="' . $skill["no"] . '" class="vcent" />');

	print('<img src="' . IMG_ICON . $skill["img"] . '" class="vcent">');
	print("{$skill["name"]}");

	if ($radio)
		print(" / <span class=\"bold\">需要 {$skill["learn"]} 点技能</span>");

	// 转换技能对象为中文表述
	if (isset($skill["target"][0])) {
		if ($skill["target"][0] == "all")
			print(" / <span class=\"charge\">战场</span>");
		else if ($skill["target"][0] == "enemy")
			print(" / <span class=\"dmg\">敌方</span>");
		else if ($skill["target"][0] == "friend")
			print(" / <span class=\"recover\">友方</span>");
		else if ($skill["target"][0] == "self")
			print(" / <span class=\"support\">自己</span>");
		else
			print(" / {$skill["target"][0]}");
	}

	// 转换技能效果范围为中文表述
	if (isset($skill["target"][1])) {
		if ($skill["target"][1] == "all")
			print(" - <span class=\"charge\">全体</span>");
		else if ($skill["target"][1] == "individual")
			print(" - <span class=\"recover\">单体</span>");
		else if ($skill["target"][1] == "multi")
			print(" - <span class=\"spdmg\">群体</span>");
		else
			print(" - {$skill["target"][1]}");
	}

	// 牺牲属性
	if (isset($skill["sacrifice"]))
		print(" / <span class=\"dmg\">Sacrifice:{$skill["sacrifice"]}%</span>");

	// 消耗SP
	if (isset($skill["sp"]))
		print(" / <span class=\"support\">消耗 {$skill["sp"]} 魔力</span>");

	// 魔法圆属性
	if (isset($skill["MagicCircleDeleteTeam"]))
		print(" / <span class=\"support\">MagicCircle x" . $skill["MagicCircleDeleteTeam"] . "</span>");

	// 威力属性
	if (isset($skill["pow"]) && $skill["pow"]) {
		print(" / <span class=\"" . (isset($skill["support"]) && $skill["support"] ? "recover" : "dmg") . "\">{$skill["pow"]}%</span>x");
		print(isset($skill["target"][2]) ? $skill["target"][2] : "1");
	}

	// 技能类型
	if (isset($skill["type"]) && $skill["type"] == 1)
		print(" / <span class=\"spdmg\">魔法</span>");

	// 快速属性
	if (isset($skill["quick"]) && $skill["quick"])
		print(" / <span class=\"charge\">Quick</span>");

	// 无效属性
	if (isset($skill["invalid"]) && $skill["invalid"])
		print(" / <span class=\"charge\">攻击后排</span>");

	// 优先级属性
	if (isset($skill["priority"]) && $skill["priority"] == "Back")
		print(" / <span class=\"support\">以牙还牙</span>");

	// 解毒属性
	if (isset($skill["CurePoison"]) && $skill["CurePoison"])
		print(" / <span class=\"support\">CurePoison</span>");

	// 延迟属性
	if (isset($skill["delay"]) && $skill["delay"])
		print(" / <span class=\"support\">延迟 -" . $skill["delay"] . "%</span>");

	// 增益属性
	$upAttributes = [
		"UpMAXHP",
		"UpMAXSP",
		"UpSTR",
		"UpINT",
		"UpDEX",
		"UpSPD",
		"UpLUK",
		"UpATK",
		"UpMATK",
		"UpDEF",
		"UpMDEF"
	];
	foreach ($upAttributes as $attr) {
		if (isset($skill[$attr]) && $skill[$attr])
			print(" / <span class=\"charge\">" . substr($attr, 2) . " +" . $skill[$attr] . "%</span>");
	}

	// 减益属性
	$downAttributes = [
		"DownMAXHP",
		"DownMAXSP",
		"DownSTR",
		"DownINT",
		"DownDEX",
		"DownSPD",
		"DownLUK",
		"DownATK",
		"DownMATK",
		"DownDEF",
		"DownMDEF"
	];
	foreach ($downAttributes as $attr) {
		if (isset($skill[$attr]) && $skill[$attr])
			print(" / <span class=\"dmg\">" . substr($attr, 4) . " -" . $skill[$attr] . "%</span>");
	}

	// 附加属性
	$plusAttributes = ["PlusSTR", "PlusINT", "PlusDEX", "PlusSPD", "PlusLUK"];
	foreach ($plusAttributes as $attr) {
		if (isset($skill[$attr]) && $skill[$attr])
			print(" / <span class=\"charge\">" . substr($attr, 4) . " +" . $skill[$attr] . "</span>");
	}

	// 吟唱时间
	$charge0 = isset($skill["charge"][0]) ? $skill["charge"][0] : 0;
	$charge1 = isset($skill["charge"][1]) ? $skill["charge"][1] : 0;
	if ($charge0 || $charge1) {
		print(" / (" . $charge0 . ":" . $charge1 . ")");
	}

	// 装备需求
	if (isset($skill["limit"]) && $skill["limit"]) {
		$Limit = " / 装备需求：";
		foreach ($skill["limit"] as $type => $bool) {
			$Limit .= $type . ", ";
		}
		print(substr($Limit, 0, -2));
	}

	// 技能描述
	if (isset($skill["exp"]) && $skill["exp"])
		print(" / {$skill["exp"]}");

	print("\n");
}
//////////////////////////////////////////////////
//	显示道具描述内容
function ShowItemDetail($item, $amount = false, $text = false, $need = false)
{
	if (!$item) return false;

	$html	= "<img src=\"" . IMG_ICON . $item["img"] . "\" class=\"vcent\">";

	// 篮希猛
	if ($item["refine"])
		$html	.= "+{$item["refine"]} ";
	if ($item["AddName"])
		$html	.= "{$item["AddName"]} ";
	$html	.= "{$item["base_name"]}"; // 叹涟

	if ($item["type"])
		$html	.= "<span class=\"light\"> ({$item["type"]})</span>";
	if ($amount) { //眶翁
		$html	.= " x<span class=\"bold\" style=\"font-size:80%\">{$amount}</span>";
	}
	if ($item["atk"]["0"]) //湿妄苟封
		$html	.= ' / <span class="dmg">物理攻击：' . $item["atk"][0] . '</span>';
	if ($item["atk"]["1"]) //蒜恕苟封
		$html	.= ' / <span class="spdmg">魔法攻击：' . $item["atk"][1] . '</span>';
	if ($item["def"]) {
		$html	.= " / <span class=\"recover\">物理防御：{$item["def"][0]}+{$item["def"][1]}</span>";
		$html	.= " / <span class=\"support\">魔法防御：{$item["def"][2]}+{$item["def"][3]}</span>";
	}
	if ($item["P_SUMMON"])
		$html	.= ' / <span class="support">召唤 +' . $item["P_SUMMON"] . '%</span>';
	if (isset($item["handle"]))
		$html	.= ' / <span class="charge">重量：' . $item['handle'] . '</span>'; // FIXED: changed $item[handle] to $item['handle']
	if ($item["option"])
		$html	.= ' / <span style="font-size:80%">' . substr($item["option"], 0, -2) . "</span>";

	if ($need && $item["need"]) {
		$html	.= " /";
		foreach ($item["need"] as $M_itemNo => $M_amount) {
			$M_item	= LoadItemData($M_itemNo);
			$html	.= "";
			$html	.= "{$M_item["base_name"]}"; // 叹涟
			$html	.= " x<span class=\"bold\" style=\"font-size:80%\">{$M_amount}</span>";
			if ($need["$M_itemNo"])
				$html	.= "<span class=\"light\">(" . $need["$M_itemNo"] . ")</span>";
		}
	}

	if ($text)
		return $html;

	print($html);
}

//////////////////////////////////////////////////
//	乐い焚桂矢でエラ〖山绩
function ShowResult($message, $add = false)
{
	if ($add)
		$add	= " " . $add;
	if (is_string($message))
		print('<div class="result' . $add . '">' . $message . '</div>' . "\n");
}
//////////////////////////////////////////////////
//	乐い焚桂矢でエラ〖山绩
function ShowError($message, $add = false)
{
	if ($add)
		$add	= " " . $add;
	if (is_string($message))
		print('<div class="error' . $add . '">' . $message . '</div>' . "\n");
}
//////////////////////////////////////////////////
//	マニュアルを山绩する
function ShowManual()
{
	include_once(MANUAL);
	return true;
}
//////////////////////////////////////////////////
//	マニュアルを山绩する
function ShowManual2()
{
	include_once(MANUAL_HIGH);
	return true;
}
//////////////////////////////////////////////////
//	チュ〖トリアルを山绩する
function ShowTutorial()
{
	include_once(TUTORIAL);
	return true;
}
//////////////////////////////////////////////////
//	构糠柒推の山绩
function ShowUpDate()
{
	print('<div style="margin:15px">');
	print("<p><a href=\"?\">Back</a><br><a href=\"#btm\">to bottom</a></p>");

	if ($_POST["updatetext"]) {
		$update	= htmlspecialchars($_POST["updatetext"], ENT_QUOTES);
		$update	= stripslashes($update);
	} else
		$update	= @file_get_contents(UPDATE);

	print('<form action="?update" method="post">');
	if ($_POST["updatepass"] == UP_PASS) {
		print('<textarea class="text" rows="12" cols="60" name="updatetext">');
		print("$update");
		print('</textarea><br>');
		print('<input type="submit" class="btn" value="update">');
		print('<a href="?update">刷新<br>');
	}

	print(nl2br($update) . "\n");
	print('<br><a name="btm"></a>');
	if ($_POST["updatepass"] == UP_PASS && $_POST["updatetext"]) {
		$fp	= fopen(UPDATE, "w");
		$text	= htmlspecialchars($_POST["updatetext"], ENT_QUOTES);
		$text	= stripslashes($text);
		flock($fp, 2);
		fputs($fp, $text);
		fclose($fp);
	}
	print <<< EOD
				<input type="password" class="text" name="updatepass" style="width:100px" value="$_POST[updatepass]">
				<input type="submit" class="btn" value="update">
				</form>
			EOD;
	print("<p><a href=\"?\">Back</a></p></div>");
}
//////////////////////////////////////////////////
//	げ〖むで〖た
function ShowGameData()
{
?>
	<div style="margin:15px">
		<h4>GameData</h4>
		<div style="margin:0 20px">
			| <a href="?gamedata=job">职业(Job)</a> |
			<a href="?gamedata=item">道具(item)</a> |
			<a href="?gamedata=judge">判定</a> |
		</div>
	</div><?php
			switch ($_GET["gamedata"]) {
				case "job":
					include_once(GAME_DATA_JOB);
					break;
				case "item":
					include_once(GAME_DATA_ITEM);
					break;
				case "judge":
					include_once(GAME_DATA_JUDGE);
					break;
				case "monster":
					include_once(GAME_DATA_MONSTER);
					break;
				default:
					include_once(GAME_DATA_JOB);
					break;
			}
		}
		//////////////////////////////////////////////////
		//	
		function userNameLoad()
		{
			$name	= @file(USER_NAME);
			if ($name) {
				foreach ($name as $key => $var) {
					$name[$key]	= trim($name[$key]);
					if ($name[$key] === "")
						unset($name[$key]);
				}
				return $name;
			} else {
				return array();
			}
		}
		//////////////////////////////////////////////////
		//	
		function userNameAdd($add)
		{
			foreach (userNameLoad() as $name) {
				$string	.= $name . "\n";
			}
			$string .= $add . "\n";
			$fp	= fopen(USER_NAME, "w+");
			flock($fp, LOCK_EX);
			fwrite($fp, $string);
			fclose($fp);
		}
		//////////////////////////////////////////////////
		//	链ランキングの山绩
		function RankAllShow()
		{
			print('<div style="margin:15px">' . "\n");
			print('<h4>Ranking - ' . date("Y年n月j日 G:i:s") . '</h4>' . "\n");
			include_once(CLASS_RANKING);
			$Rank	= new Ranking();
			$Rank->ShowRanking();
			print('</div>' . "\n");
		}
		//////////////////////////////////////////////////
		//	
		function RecordManage($string)
		{
			$file	= MANAGE_LOG_FILE;

			$fp	= @fopen($file, "r+") or die();
			$text	= fread($fp, 2048);
			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, $string . "\n" . $text);
		}

		/*
	*	掐蜗された矢机误を澄千する
	*	手り猛
	*	喇根 = array(true,恃垂($string));
	*	己窃 = array(false,己窃妄统);
	*/
		function CheckString($string, $maxLength = 16)
		{
			$string	= trim($string);
			$string	= stripslashes($string);
			if (is_numeric(strpos($string, "\t"))) {
				return array(false, "非法字符");
			}
			if (is_numeric(strpos($string, "\n"))) {
				return array(false, "非法字符");
			}
			if (!$string) {
				return array(false, "不能为空");
			}
			$length	= strlen($string);
			if (0 == $length || $maxLength < $length) {
				return array(false, "过短或过长");
			}
			$string	= htmlspecialchars($string, ENT_QUOTES);
			return array(true, $string);
		}
		///////////////////////////////////////////////////
		//	眉琐を冉们。
		function isMobile()
		{
			if (strstr($_SERVER['HTTP_USER_AGENT'], "DoCoMo")) {
				$env = 'i';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "Vodafone")) {
				$env = 'i';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "SoftBank")) {
				$env = 'i';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "MOT-")) {
				$env = 'i';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "J-PHONE")) {
				$env = 'i';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "KDDI")) {
				//$env = 'ez';
				$env = 'ez';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "UP.Browser")) {
				$env = 'i';
			} elseif (strstr($_SERVER['HTTP_USER_AGENT'], "WILLCOM")) {
				$env = 'ez';
			} else {
				$env = 'pc';
			}
			return $env;
		}
		//////////////////////////////////////////////////
		//	DUMP
		if (!function_exists("dump")) {
			function dump($array)
			{
				print("<pre>" . print_r($array, 1) . "</pre>");
			}
		}
			?>