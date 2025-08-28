<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../css/global.css" type="text/css">
	<title>职业列表 - JOB List</title>
	<style type="text/css">
		<!--
		* {
			padding: 0;
			margin: 0;
			line-height: 140%;
			font-family: Osaka, Verdana, sans-serif;
		}

		body {
			margin: 30px;
			background: #98a0a5;
			color: #bdc8d7;
		}

		.container {
			max-width: 1200px;
			margin: 0 auto;
		}

		.header {
			margin-bottom: 20px;
			text-align: center;
		}

		.job-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 30px;
		}

		.job-table td {
			white-space: nowrap;
			background-color: #10151b;
			text-align: center;
			padding: 8px;
			border: 1px solid #333;
		}

		.job-table .header-cell {
			background-color: #333333;
			font-weight: bold;
		}

		.job-table .skill-cell {
			text-align: left;
			white-space: normal;
			width: 60%;
		}

		.skill-details {
			margin: 10px 0 0 20px;
			padding: 10px;
			background-color: #1a2028;
			border-radius: 4px;
		}

		.skill-item {
			margin-bottom: 8px;
			padding-bottom: 8px;
			border-bottom: 1px dashed #444;
		}

		.skill-item:last-child {
			border-bottom: none;
		}

		.skill-name {
			font-weight: bold;
			color: #8ab4f8;
		}

		.skill-meta {
			font-size: 0.9em;
			color: #a0a8b0;
		}

		.job-section {
			margin-bottom: 40px;
			border: 1px solid #555;
			border-radius: 6px;
			overflow: hidden;
		}

		.job-header {
			background-color: #2d3748;
			padding: 10px;
			font-size: 1.2em;
			font-weight: bold;
		}
		-->
	</style>
</head>

<body>
	<div class="container">
		<div class="header">
			<h1>游戏职业技能全表</h1>
			<p>展示所有职业的基础信息及可学习技能树</p>
		</div>

		<?php
		// 添加数据库初始化和全局函数引入
		include_once("../setting.php");  // 引入配置文件，包含数据库路径等定义
		include_once(GLOBAL_PHP);       // 引入全局函数，包含数据库初始化

		include_once("../data/data.job.php");
		include_once("../data/data.skill.php");

		// 确保数据库连接已初始化
		if (!isset($GLOBALS['DB']) || !$GLOBALS['DB']) {
			$GLOBALS['DB'] = initDatabase();
		}

		include_once("../data/data.job.php");
		include_once("../data/data.skill.php");
		include_once("../data/data.skilltree.php");

		// 职业分类映射
		$jobCategories = [
			'100-103' => '战士系',
			'200-203' => '法师系',
			'300-302' => '支援系',
			'400-403' => '弓手系'
		];

		// 获取所有职业ID并按分类组织
		$jobIds = [];
		foreach (range(100, 403) as $no) {
			$j = LoadJobData((string)$no);
			if ($j) {
				if ($no >= 100 && $no <= 103) $jobIds['100-103'][] = $no;
				else if ($no >= 200 && $no <= 203) $jobIds['200-203'][] = $no;
				else if ($no >= 300 && $no <= 302) $jobIds['300-302'][] = $no;
				else if ($no >= 400 && $no <= 403) $jobIds['400-403'][] = $no;
			}
		}

		// 循环展示每个职业分类
		foreach ($jobIds as $category => $nos) {
			echo '<div class="job-category">';
			echo '<h2>' . $jobCategories[$category] . '</h2>';

			// 循环展示分类下的每个职业
			foreach ($nos as $no) {
				$jobData = LoadJobData((string)$no);
				if (!$jobData) continue;

				// 创建临时角色对象用于获取技能树
				$char = new stdClass();
				$char->job = $no;
				$char->skill = []; // 空技能列表表示未学习任何技能
				$char->level = 99; // 满级角色可学习所有技能

				// 获取可学习的技能
				$learnableSkills = LoadSkillTree($char);
				// 去重并排序
				$learnableSkills = array_unique($learnableSkills);
				sort($learnableSkills);

				echo '<div class="job-section">';
				echo '<div class="job-header">' . $jobData["name_male"] . ' (ID: ' . $no . ')</div>';

				echo '<table class="job-table">';
				echo '<tr>';
				echo '<td class="header-cell">职业名称</td>';
				echo '<td colspan="3">' . $jobData["name_male"] . ' / ' . $jobData["name_female"] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="header-cell">属性成长</td>';
				echo '<td colspan="3">HP系数: ' . $jobData["coe"][0] . ' / SP系数: ' . $jobData["coe"][1] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="header-cell">职业形象</td>';
				echo '<td colspan="3">';
				echo '<img src="../image/char/' . $jobData["img_male"] . '" alt="男性形象">';
				echo '<img src="../image/char/' . $jobData["img_female"] . '" alt="女性形象">';
				echo '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="header-cell">可装备物品</td>';
				echo '<td colspan="3">' . implode(', ', $jobData["equip"]) . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="header-cell">可转职方向</td>';
				echo '<td colspan="3">';
				if (!empty($jobData["change"])) {
					$changeJobs = [];
					foreach ($jobData["change"] as $changeNo) {
						$changeData = LoadJobData((string)$changeNo);
						$changeJobs[] = $changeData ? $changeData["name_male"] . ' (ID: ' . $changeNo . ')' : '未知职业 (ID: ' . $changeNo . ')';
					}
					echo implode(', ', $changeJobs);
				} else {
					echo '无';
				}
				echo '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td class="header-cell">可学习技能</td>';
				echo '<td class="skill-cell" colspan="3">';
				if (!empty($learnableSkills)) {
					echo '<div class="skill-details">';
					foreach ($learnableSkills as $skillId) {
						$skill = LoadSkillData($skillId);
						if ($skill) {
							echo '<div class="skill-item">';
							echo '<div class="skill-name">' . $skill["name"] . ' (ID: ' . $skillId . ')</div>';
							echo '<div class="skill-meta">';
							echo '类型: ' . ($skill["type"] == 0 ? '物理' : '魔法') . ' | ';
							echo 'SP消耗: ' . $skill["sp"] . ' | ';
							echo '威力: ' . ($skill["pow"] ?? 'N/A') . ' | ';
							$target = $skill["target"] ?? [];
							if (is_string($target)) {
								$target = json_decode($target, true) ?? [];
							}
							echo '目标: ' . (is_array($target) ? implode(', ', $target) : $target) . ' | ';
							echo ($skill["support"] ? '支援技能 | ' : '') . ($skill["passive"] ? '被动技能' : '');
							echo '</div>';
							if (!empty($skill["exp"])) {
								echo '<div>描述: ' . $skill["exp"] . '</div>';
							}
							echo '</div>';
						} else {
							echo '<div class="skill-item">未知技能 (ID: ' . $skillId . ')</div>';
						}
					}
					echo '</div>';
				} else {
					echo '无可用技能数据';
				}
				echo '</td>';
				echo '</tr>';
				echo '</table>';
				echo '</div>'; // .job-section
			}
			echo '</div>'; // .job-category
		}
		?>
	</div>
</body>

</html>