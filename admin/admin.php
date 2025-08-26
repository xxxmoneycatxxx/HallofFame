<?php

/**
 * 游戏管理后台主界面
 * 
 * 功能说明：
 * 1. 提供管理员登录认证功能
 * 2. 实现游戏数据管理功能（用户、角色、道具等）
 * 3. 支持系统配置查看和修改
 * 4. 提供日志管理和数据维护工具
 * 
 * 此文件是游戏管理后台的核心界面，包含以下主要功能模块：
 * 
 * 登录系统：
 * - 管理员密码验证
 * - 登录状态保持（30分钟）
 * - 注销功能
 * 
 * 用户管理：
 * - 用户列表查看
 * - 用户数据修改
 * - 用户删除功能
 * 
 * 数据统计：
 * - 用户数据汇总
 * - 角色数据统计
 * - 道具分布分析
 * - IP地址追踪
 * 
 * 系统管理：
 * - 战斗日志管理
 * - 拍卖系统管理
 * - 排行榜数据维护
 * - 游戏公告管理
 * 
 * 配置管理：
 * - 游戏参数查看
 * - 数据文件编辑
 * - 自动维护记录
 * 
 * 安全说明：
 * 1. 所有管理操作需要管理员密码验证
 * 2. 敏感操作（如用户删除）需要二次密码确认
 * 3. 提供完整的数据备份和恢复功能
 * 
 * 使用提示：
 * 1. 登录后左侧菜单选择功能模块
 * 2. 数据编辑后点击"修改"按钮保存
 * 3. 谨慎执行删除操作
 */

require_once __DIR__ . '/../class/global.php';

$login = false; // 初始化变量

if (!defined("ADMIN_PASSWORD"))
	exit(1);

// 登录
if ((isset($_POST['pass']) && $_POST['pass'] == ADMIN_PASSWORD) ||
	(isset($_COOKIE['adminPass']) && $_COOKIE['adminPass'] == ADMIN_PASSWORD)
) {

	$passValue = $_POST['pass'] ?? $_COOKIE['adminPass'];
	setcookie("adminPass", $passValue, time() + 60 * 30);
	$login = true;
}

// 注销
if ($_POST['logout'] ?? false) {
	setcookie("adminPass", "", time() - 3600); // 设置为过期
	$login = false;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script type="text/javascript" src="prototype.js"></script>
	<title>HoF - 管理后台</title>
	<style TYPE="text/css">
		<!--
		form {
			margin: 0;
			padding: 0;
		}
		-->
	</style>
</head>

<body>
	<?php
	if ($login) {

		// function dump
		if (!function_exists("dump")) {
			function dump($var)
			{
				print("<pre>" . print_r($var, 1) . "</pre>\n");
			}
		}

		// changeData(更改数据)
		function changeData($file, $text)
		{
			$fp = @fopen($file, "w") or die("file lock error!");
			flock($fp, LOCK_EX);
			fwrite($fp, stripcslashes($text));
			flock($fp, LOCK_UN);
			fclose($fp);
			print("<span style=\"font-weight:bold\">数据修改</span>");
		}

		// 菜单
		print <<< MENU
				<form action="?" method="post">
				<a href="?">管理首页</a>
				<a href="?menu=user">用户管理</a>
				<a href="?menu=data">数据管理</a>
				<a href="?menu=other">其他</a>
				<input type="submit" value="注销" name="logout" />
				</form>
				<hr>
				MENU;

		// 用户列表
		if (isset($_GET['menu']) && $_GET['menu'] === "user") {
			$userList = glob(USER . "*");
			print("<p>全部用户</p>\n");
			foreach ($userList as $user) {
				print('<form action="?" method="post">');
				print('<input type="submit" name="UserData" value=" 管理 ">');
				print('<input type="hidden" name="userID" value="' . basename($user) . '">');
				print(basename($user) . "\n");
				print("</form>\n");
			}
		}

		// 用户数据
		else if (isset($_POST["UserData"]) && $_POST["UserData"]) {
			$userFileList = glob(USER . $_POST["userID"] . "/*");
			print("<p>USER :" . $_POST["userID"] . "</p>\n");
			foreach ($userFileList as $file) {
				print('<form action="?" method="post">');
				print('<input type="submit" name="UserFileDet" value=" 管理 ">');
				print('<input type="hidden" name="userFile" value="' . basename($file) . '">');
				print('<input type="hidden" name="userID" value="' . $_POST["userID"] . '">');
				print(basename($file) . "\n");
				print("</form>\n");
			}
			print('<br><form action="?" method="post">');
			print('输入管理密码以删除用户:<input type="text" name="deletePass" size="">');
			print('<input type="submit" name="deleteUser" value="删除">');
			print('<input type="hidden" name="userID" value="' . $_POST["userID"] . '">');
			print("</form>\n");
		}

		// 用户数据删除
		else if (isset($_POST["deleteUser"]) && $_POST["deleteUser"]) {
			if ($_POST["deletePass"] == ADMIN_PASSWORD) {
				include_once(CLASS_USER);
				$userD = new user($_POST["userID"]);
				$userD->DeleteUser();
				print($_POST["userID"] . "被删除。");
			} else {
				print("没有密码。");
			}
		}

		// 用户数据(详细)
		else if (isset($_POST["UserFileDet"]) && $_POST["UserFileDet"]) {
			$file = USER . $_POST["userID"] . "/" . $_POST["userFile"];

			// 数据修改
			if ($_POST["changeData"]) {
				$fp = @fopen($file, "w") or die("file lock error!");
				flock($fp, LOCK_EX);
				fwrite($fp, $_POST["fileData"]);
				flock($fp, LOCK_UN);
				fclose($fp);
				print("数据修改");
			}

			print("<p>$file</p>\n");

			// 添加文件存在检查
			if (!file_exists($file)) {
				print("<div class='error'>文件不存在或无法访问</div>");
			} else {
				$fileContents = @file_get_contents($file);
				if ($fileContents === false) {
					print("<div class='error'>无法读取文件内容</div>");
				} else {
					print('<form action="?" method="post">');
					print('<textarea name="fileData" style="width:800px;height:300px;">');
					print(htmlspecialchars($fileContents));
					print("</textarea><br>\n");
					print('<input type="submit" name="changeData" value="修改">');
					print('<input type="submit" value="更新">');
					print('<input type="hidden" name="userFile" value="' . $_POST["userFile"] . '">');
					print('<input type="hidden" name="userID" value="' . $_POST["userID"] . '">');
					print('<input type="hidden" name="UserFileDet" value="1">');
					print("</form>\n");
				}
			}

			print('<form action="?" method="post">');
			print('<input type="submit" name="UserData" value="放弃">');
			print('<input type="hidden" name="userID" value="' . $_POST["userID"] . '">');
			print("</form>\n");
		}
		// 数据汇总
		else if (isset($_GET['menu']) && $_GET['menu'] === "data") {
			print <<< DATA
					<br>
					<form action="?" method="post">
					<ul>
					<li><input type="submit" name="UserDataDetail" value=" 管理 ">(※1)用户数据汇总表示</li>
					<li><input type="submit" name="UserCharDetail" value=" 管理 ">(※1)人物数据汇总表示</li>
					<li><input type="submit" name="ItemDataDetail" value=" 管理 ">(※1)道具数据汇总表示</li>
					<li><input type="submit" name="UserIpShow" value=" 管理 ">(※1)用户IP表示</li>
					<li><input type="submit" name="searchBroken" value=" 管理 ">(※1)有可能是已损坏的数据<input type="text" name="brokenSize" value="100" size=""></li>
					<li><input type="submit" name="adminBattleLog" value=" 管理 ">战斗记录管理</li>
					<li><input type="submit" name="adminAuction" value=" 管理 ">拍卖管理</li>
					<li><input type="submit" name="adminRanking" value=" 管理 ">排名管理</li>
					<li><input type="submit" name="adminTown" value=" 管理 ">广场管理</li>
					<li><input type="submit" name="adminRegister" value=" 管理 ">用户登录数据管理</li>
					<li><input type="submit" name="adminUserName" value=" 管理 ">用户名管理</li>
					<li><input type="submit" name="adminUpDate" value=" 管理 ">更新数据管理</li>
					<li><input type="submit" name="adminAutoControl" value=" 管理 ">自动管理记录</li>
					</ul>
					<p>(※1)将会比较消耗性能。<br>
					甚至超过增加的数据处理。
					</p>
					</form>
					DATA;
		}

		// 数据汇总(用户数据)
		else if (isset($_POST["UserDataDetail"]) && $_POST["UserDataDetail"]) {
			include_once(CLASS_USER);
			$userFileList = glob(USER . "*");
			$totalMoney = 0;
			foreach ($userFileList as $user) {
				$user = new user(basename($user, ".dat"));
				$totalMoney += $user->money;
			}
			print("UserAmount :" . count($userFileList) . "<br>\n");
			print("TotalMoney :" . MoneyFormat($totalMoney) . "<br>\n");
			print("AveMoney :" . MoneyFormat($totalMoney / count($userFileList)) . "<br>\n");
		}

		// 数据汇总(人物数据)
		else if (isset($_POST["UserCharDetail"]) && $_POST["UserCharDetail"]) {
			$userFileList = glob(USER . "*");
			$charAmount = 0;
			$totalLevel = 0;
			// 添加统计变量初始化
			$totalStr = $totalInt = $totalDex = $totalSpd = $totalLuk = 0;
			$totalMale = $totalFemale = 0;
			$totalJob = [];

			foreach ($userFileList as $user) {
				$userDir = glob($user . "/*");
				foreach ($userDir as $fileName) {
					if (!is_numeric(basename($fileName, ".dat"))) continue;

					// 修改：检查ParseFile返回值
					$charData = ParseFile($fileName);
					if (empty($charData) || !isset($charData["level"])) {
						error_log("Skipping invalid char file: $fileName");
						continue;
					}

					$charAmount++;
					$totalLevel += $charData["level"];
					$totalStr += $charData["str"];
					$totalInt += $charData["int"];
					$totalDex += $charData["dex"];
					$totalSpd += $charData["spd"];
					$totalLuk += $charData["luk"];

					if ($charData["gender"] === "0") $totalMale++;
					else if ($charData["gender"] === "1") $totalFemale++;

					$job = $charData["job"];
					$totalJob[$job] = ($totalJob[$job] ?? 0) + 1;
				}
			}

			// 添加检查防止除以零错误
			if ($charAmount === 0) {
				print("没有找到有效的角色数据");
				return;
			}
			print("人物总数:" . $charAmount . "<br>\n");
			print("平均等级 :" . $totalLevel / $charAmount . "<br>\n");
			print("平均str :" . $totalStr / $charAmount . "<br>\n");
			print("平均int :" . $totalInt / $charAmount . "<br>\n");
			print("平均dex :" . $totalDex / $charAmount . "<br>\n");
			print("平均spd :" . $totalSpd / $charAmount . "<br>\n");
			print("平均luk :" . $totalLuk / $charAmount . "<br>\n");
			print("男 :{$totalMale}(" . ($totalMale / $charAmount * 100) . "%)<br>\n");
			print("女 :{$totalFemale}(" . ($totalFemale / $charAmount * 100) . "%)<br>\n");
			print("--- 职业<br>\n");
			arsort($totalJob);
			include_once(DATA_JOB);
			foreach ($totalJob as $job => $amount) {
				$jobData = LoadJobData($job);
				print($job . "({$jobData["name_male"]},{$jobData["name_female"]})" . " : " . $amount . "(" . ($amount / $charAmount * 100) . "%)<br>\n");
			}
		}

		// 数据汇总(道具数据)
		else if (isset($_POST["ItemDataDetail"]) && $_POST["ItemDataDetail"]) {
			$userFileList = glob(USER . "*");
			$userAmount = count($userFileList);
			$items = array();
			foreach ($userFileList as $user) {
				if (!$data = ParseFile($user . "/item.dat"));
				foreach ($data as $itemno => $amount)
					$items[$itemno] += $amount;
			}
			foreach ($items as $itemno => $amount) {
				if (strlen($itemno) != 4) continue;
				print($itemno . " : " . $amount . "(平均:" . $amount / $userAmount . ")<br>");
			}
		}

		// 用户IP
		else if (isset($_POST["UserIpShow"]) && $_POST["UserIpShow"]) {
			$userFileList = glob(USER . "*");
			$ipList = array();
			$html = '';
			foreach ($userFileList as $user) {
				$file = $user . "/data.dat";
				if (!$data = ParseFile($file)) continue;
				$html .= "<tr><td>" . $data["id"] . "</td><td>" . $data["name"] . "</td><td>" . $data["ip"] . "</td></tr>\n";
				$ipList[$data["ip"] ? $data["ip"] : "*UnKnown"]++;
			}
			// 重复列表
			print("<p>IP重复列表</p>\n");
			foreach ($ipList as $ip => $amount) {
				if (1 < $amount)
					print("$ip : $amount<br>\n");
			}
			print("<table border=\"1\">\n");
			print($tags = "<tr><td>ID</td><td>名字</td><td>IP</td></tr>\n");
			print($html);
			print("</table>\n");
		}

		// 有可能是已损坏的数据
		else if (isset($_POST["searchBroken"]) && $_POST["searchBroken"]) {
			print("<p>可能会损坏文件<br>\n");
			$baseSize = $_POST["brokenSize"] ? (int)$_POST["brokenSize"] : 100;
			print("※{$baseSize}byte 以下的文件搜索(道具数据除外).</p>");
			$userFileList = glob(USER . "*");
			foreach ($userFileList as $user) {
				$userDir = glob($user . "/*");
				$dataFile = $user . "/data.dat";
				if (file_exists($dataFile) && filesize($dataFile) < $baseSize) {
					print($dataFile . "(" . filesize($dataFile) . ")<br>\n");
				}
				foreach ($userDir as $fileName) {
					if (!is_numeric(basename($fileName, ".dat"))) continue;
					if (file_exists($fileName) && filesize($fileName) < $baseSize) {
						print($fileName . "(" . filesize($fileName) . ")<br>\n");
					}
				}
			}
		}

		// 战斗记录管理
		else if (isset($_POST["adminBattleLog"]) && $_POST["adminBattleLog"]) {
			$db = $GLOBALS['DB'];

			// 检查删除普通战斗记录键是否存在
			if (isset($_POST["deleteLogCommon"]) && $_POST["deleteLogCommon"]) {
				// 删除普通战斗记录
				$stmt = $db->prepare("DELETE FROM battle_logs WHERE battle_type = 'normal'");
				$stmt->execute();
				$count = $stmt->rowCount();
				print("<p>已删除普通战斗记录：{$count}条</p>\n");
			}
			// 检查删除BOSS战斗记录键是否存在
			else if (isset($_POST["deleteLogUnion"]) && $_POST["deleteLogUnion"]) {
				// 删除BOSS战斗记录
				$stmt = $db->prepare("DELETE FROM battle_logs WHERE battle_type = 'union'");
				$stmt->execute();
				$count = $stmt->rowCount();
				print("<p>已删除BOSS战斗记录：{$count}条</p>\n");
			}
			// 检查删除排行战记录键是否存在
			else if (isset($_POST["deleteLogRanking"]) && $_POST["deleteLogRanking"]) {
				// 删除排行战记录
				$stmt = $db->prepare("DELETE FROM battle_logs WHERE battle_type = 'rank'");
				$stmt->execute();
				$count = $stmt->rowCount();
				print("<p>已删除排行战记录：{$count}条</p>\n");
			}

			// 添加统计功能
			$stmt = $db->query("SELECT battle_type, COUNT(*) as count FROM battle_logs GROUP BY battle_type");
			$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$statHtml = "<p>当前战斗记录统计：</p><ul>";
			foreach ($stats as $stat) {
				$typeName = [
					'normal' => '普通战斗',
					'union' => 'BOSS战',
					'rank' => '排行战'
				][$stat['battle_type']] ?? $stat['battle_type'];

				$statHtml .= "<li>{$typeName}: {$stat['count']}条</li>";
			}
			$statHtml .= "</ul>";

			print <<< DATA
					{$statHtml}
					<br>
					<form action="?" method="post">
					<input type="hidden" name="adminBattleLog" value="1">
					<ul>
					<li><input type="submit" name="deleteLogCommon" value="删除普通战斗记录">通常战斗记录全部删除</li>
					<li><input type="submit" name="deleteLogUnion" value="删除BOSS战斗记录">BOSS战斗记录全部删除</li>
					<li><input type="submit" name="deleteLogRanking" value="删除排行战记录">排名记录全部删除</li>
					</ul>
					</form>
					DATA;
		}

		// 拍卖管理
		else if (isset($_POST["adminAuction"]) && $_POST["adminAuction"]) {
			$file = AUCTION_ITEM;
			print("<p>拍卖管理</p>\n");
			// 数据修改
			if ($_POST["changeData"]) {
				changeData($file, $_POST["fileData"]);
			}
			print('<form action="?" method="post">');
			print('<textarea name="fileData" style="width:800px;height:300px;">');
			print(file_get_contents($file));
			print("</textarea><br>\n");
			print('<input type="submit" name="changeData" value="修改">');
			print('<input type="submit" value="更新">');
			print('<input type="hidden" name="adminAuction" value="1">');
			print("</form>\n");
		}

		// 排名管理
		else if (isset($_POST["adminRanking"]) && $_POST["adminRanking"]) {
			$file = RANKING;
			print("<p>排名管理</p>\n");
			// 数据修改
			if ($_POST["changeData"]) {
				changeData($file, $_POST["fileData"]);
			}
			print('<form action="?" method="post">');
			print('<textarea name="fileData" style="width:800px;height:300px;">');
			print(file_get_contents($file));
			print("</textarea><br>\n");
			print('<input type="submit" name="changeData" value="修改">');
			print('<input type="submit" value="更新">');
			print('<input type="hidden" name="adminRanking" value="1">');
			print("</form>\n");
		}

		// 广场管理
		else if (isset($_POST["adminTown"]) && $_POST["adminTown"]) {
			$db = $GLOBALS['DB'];

			print("<p>广场管理</p>\n");

			// 处理删除请求
			if ($_POST["deleteMessage"]) {
				$stmt = $db->prepare("DELETE FROM town_bbs WHERE id = ?");
				$stmt->execute([$_POST["messageId"]]);
				print("<p>留言已删除</p>");
			}

			// 处理修改请求
			if ($_POST["updateMessage"]) {
				$stmt = $db->prepare("UPDATE town_bbs SET message = ? WHERE id = ?");
				$stmt->execute([$_POST["newMessage"], $_POST["messageId"]]);
				print("<p>留言已更新</p>");
			}

			// 显示留言列表
			$stmt = $db->query("SELECT * FROM town_bbs ORDER BY post_time DESC");
			$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if ($messages) {
				print("<table border='1' cellpadding='5'>");
				print("<tr><th>ID</th><th>用户</th><th>时间</th><th>内容</th><th>操作</th></tr>");

				foreach ($messages as $msg) {
					$time = date("Y-m-d H:i", $msg["post_time"]);

					print("<tr>");
					print("<td>{$msg['id']}</td>");
					print("<td>{$msg['user_name']}</td>");
					print("<td>{$time}</td>");
					print("<td>{$msg['message']}</td>");
					print("<td>");
					print('<form action="?" method="post" style="display:inline">');
					print('<input type="hidden" name="messageId" value="' . $msg['id'] . '">');
					print('<input type="hidden" name="adminTown" value="1">');
					print('<input type="submit" name="deleteMessage" value="删除">');
					print("</form>");
					print('<form action="?" method="post" style="display:inline">');
					print('<input type="hidden" name="messageId" value="' . $msg['id'] . '">');
					print('<input type="hidden" name="adminTown" value="1">');
					print('<input type="text" name="newMessage" value="' . htmlspecialchars($msg['message']) . '">');
					print('<input type="submit" name="updateMessage" value="更新">');
					print("</form>");
					print("</td>");
					print("</tr>");
				}

				print("</table>");
			} else {
				print("<p>暂无留言</p>");
			}

			// 添加搜索功能
			print('<form action="?" method="post" style="margin-top:20px">');
			print('<h3>搜索留言</h3>');
			print('关键词: <input type="text" name="searchKeyword">');
			print('<input type="submit" name="searchMessages" value="搜索">');
			print('<input type="hidden" name="adminTown" value="1">');
			print("</form>");

			// 处理搜索请求
			if ($_POST["searchMessages"]) {
				$keyword = "%{$_POST['searchKeyword']}%";
				$stmt = $db->prepare("SELECT * FROM town_bbs WHERE message LIKE ? ORDER BY post_time DESC");
				$stmt->execute([$keyword]);
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

				if ($results) {
					print("<h3>搜索结果</h3>");
					print("<table border='1' cellpadding='5'>");
					print("<tr><th>ID</th><th>用户</th><th>时间</th><th>内容</th></tr>");

					foreach ($results as $msg) {
						$time = date("Y-m-d H:i", $msg["post_time"]);

						print("<tr>");
						print("<td>{$msg['id']}</td>");
						print("<td>{$msg['user_name']}</td>");
						print("<td>{$time}</td>");
						print("<td>{$msg['message']}</td>");
						print("</tr>");
					}

					print("</table>");
				} else {
					print("<p>未找到匹配的留言</p>");
				}
			}
		}

		// 用户登录数据管理
		else if (isset($_POST["adminRegister"]) && $_POST["adminRegister"]) {
			$file = REGISTER;
			print("<p>用户登录数据管理</p>\n");
			// 数据修正
			if ($_POST["changeData"]) {
				changeData($file, $_POST["fileData"]);
			}
			print('<form action="?" method="post">');
			print('<textarea name="fileData" style="width:800px;height:300px;">');
			print(file_get_contents($file));
			print("</textarea><br>\n");
			print('<input type="submit" name="changeData" value="修改">');
			print('<input type="submit" value="更新">');
			print('<input type="hidden" name="adminRegister" value="1">');
			print("</form>\n");
		}

		// 用户名管理
		else if (isset($_POST["adminUserName"]) && $_POST["adminUserName"]) {
			$file = USER_NAME;
			print("<p>用户名管理</p>\n");
			// 数据修改
			if ($_POST["changeData"]) {
				changeData($file, $_POST["fileData"]);
			}
			print('<form action="?" method="post">');
			print('<textarea name="fileData" style="width:800px;height:300px;">');
			print(file_get_contents($file));
			print("</textarea><br>\n");
			print('<input type="submit" name="changeData" value="修改">');
			print('<input type="submit" value="更新">');
			print('<input type="hidden" name="adminUserName" value="1">');
			print("</form>\n");
		}

		// 更新情報管理
		else if (isset($_POST["adminUpDate"]) && $_POST["adminUpDate"]) {
			$file = UPDATE;
			print("<p>更新情報管理</p>\n");
			// 数据修改
			if ($_POST["changeData"]) {
				changeData($file, $_POST["fileData"]);
			}
			print('<form action="?" method="post">');
			print('<textarea name="fileData" style="width:800px;height:300px;">');
			print(file_get_contents($file));
			print("</textarea><br>\n");
			print('<input type="submit" name="changeData" value="修改">');
			print('<input type="submit" value="更新">');
			print('<input type="hidden" name="adminUpDate" value="1">');
			print("</form>\n");
		}

		// 自动管理记录
		else if (isset($_POST["adminAutoControl"]) && $_POST["adminAutoControl"]) {
			$file = MANAGE_LOG_FILE;
			print("<p>自动管理记录</p>\n");
			// 数据修改
			if ($_POST["changeData"]) {
				changeData($file, $_POST["fileData"]);
			}
			print('<form action="?" method="post">');
			print('<textarea name="fileData" style="width:800px;height:300px;">');
			print(file_get_contents($file));
			print("</textarea><br>\n");
			print('<input type="submit" name="changeData" value="修改">');
			print('<input type="submit" value="更新">');
			print('<input type="hidden" name="adminAutoControl" value="1">');
			print("</form>\n");
		}

		// 其他-1
		else if (isset($_GET['menu']) && $_GET['menu'] === "other") {
			print("
				<p>管理</p>\n
				<ul>\n
				<li><a href=\"" . ADMIN_DIR . "admin.list_item.php\">道具列表</a></li>\n
				<li><a href=\"" . ADMIN_DIR . "admin.list_enchant.php\">装备效果列表</a></li>\n
				<li><a href=\"" . ADMIN_DIR . "list_job.php\">职业列表</a></li>\n
				<li><a href=\"" . ADMIN_DIR . "list_judge.php\">判定列表</a></li>\n
				<li><a href=\"" . ADMIN_DIR . "list_monster.php\">怪物列表</a></li>\n
				<li><a href=\"" . ADMIN_DIR . "list_skill.php\">技能列表</a></li>\n
				<li><a href=\"" . ADMIN_DIR . "set_action.php\">行动模式设定机</a></li>\n
				</ul>\n
				");
		}

		// 其他-2
		else {
			print("<p>基本設定</p>\n
				<table border=\"1\">\n
				<tr><td>定义</td><td>说明</td><td>值</td></tr>
				<tr><td>TITLE</td><td>标题</td><td>" . TITLE . "</td></tr>\n
				<tr><td>MAX_TIME</td><td>最大体力</td><td>" . MAX_TIME . "Time</td></tr>\n
				<tr><td>TIME_GAIN_DAY</td><td>每天所给的体力</td><td>" . TIME_GAIN_DAY . "Time</td></tr>\n
				<tr><td>CONTROL_PERIOD</td><td>自动管理周期</td><td>" . CONTROL_PERIOD . "s(" . (CONTROL_PERIOD / 60 / 60) . "hour)" . "</td></tr>\n
				<tr><td>RECORD_IP</td><td>IP记录(1=ON)</td><td>" . RECORD_IP . "</td></tr>\n
				<tr><td>SELLING_PRICE</td><td>卖值</td><td>" . SELLING_PRICE . "</td></tr>\n
				<tr><td>EXP_RATE</td><td>经验值倍率</td><td>x" . EXP_RATE . "</td></tr>\n
				<tr><td>MONEY_RATE</td><td>掉钱倍率</td><td>x" . MONEY_RATE . "</td></tr>\n
				<tr><td>AUCTION_MAX</td><td>最大出品数</td><td>" . AUCTION_MAX . "</td></tr>\n
				<tr><td>JUDGE_LIST_AUTO_LOAD</td><td>条件判定列表自动取得(1=自动)</td><td>" . JUDGE_LIST_AUTO_LOAD . "</td></tr>\n
				<tr><td>AUCTION_TOGGLE</td><td>拍卖ON/OFF(1=ON)</td><td>" . AUCTION_TOGGLE . "</td></tr>\n
				<tr><td>AUCTION_EXHIBIT_TOGGLE</td><td>出品ON/OFF(1=ON)</td><td>" . AUCTION_EXHIBIT_TOGGLE . "</td></tr>\n
				<tr><td>RANK_TEAM_SET_TIME</td><td>排名队伍設定周期</td><td>" . RANK_TEAM_SET_TIME . "s(" . (RANK_TEAM_SET_TIME / 60 / 60) . "hour)" . "</td></tr>\n
				<tr><td>RANK_BATTLE_NEXT_LOSE</td><td>失败后再挑战时间</td><td>" . RANK_BATTLE_NEXT_LOSE . "s(" . (RANK_BATTLE_NEXT_LOSE / 60 / 60) . "hour)" . "</td></tr>\n
				<tr><td>RANK_BATTLE_NEXT_WIN</td><td>赢得排名站再战的时间</td><td>" . RANK_BATTLE_NEXT_WIN . "s</td></tr>\n
				<tr><td>NORMAL_BATTLE_TIME</td><td>普通战斗消耗体力</td><td>" . NORMAL_BATTLE_TIME . "Time</td></tr>\n
				<tr><td>MAX_BATTLE_LOG</td><td>战斗记录保存数(通常怪)</td><td>" . MAX_BATTLE_LOG . "</td></tr>\n
				<tr><td>MAX_BATTLE_LOG_UNION</td><td>战斗记录保存数(BOSS)</td><td>" . MAX_BATTLE_LOG_UNION . "</td></tr>\n
				<tr><td>MAX_BATTLE_LOG_RANK</td><td>战斗记录保存数(排名)</td><td>" . MAX_BATTLE_LOG_RANK . "</td></tr>\n
				<tr><td>UNION_BATTLE_TIME</td><td>BOSS战消耗体力</td><td>" . UNION_BATTLE_TIME . "Time</td></tr>\n
				<tr><td>UNION_BATTLE_NEXT</td><td>BOSS战再挑战时间</td><td>" . UNION_BATTLE_NEXT . "s</td></tr>\n
				</table>\n
				");
		}

		print <<< ADMIN
<hr>
<p>请不要频繁使用管理功能。<br>
用户数或数据有可能导致错误。
</p>
ADMIN;
	} else {
		print <<< LOGIN
				<form action="?" method="post">
				请输入管理密码: <input type="text" name="pass" />
				<input type="submit" value="进入后台" />
				</form>
				LOGIN;
	}

	?>
</body>

</html>