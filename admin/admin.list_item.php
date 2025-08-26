<!DOCTYPE html>
<html lang="zh-CN">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>ITEM List</title>
	<style type="text/css">
		* {
			padding: 0;
			margin: 0;
			line-height: 140%;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
		}

		body {
			margin: 30px;
			background: #1e2a38;
			color: #e0e7ff;
		}

		.container {
			max-width: 1200px;
			margin: 0 auto;
		}

		h1 {
			text-align: center;
			margin-bottom: 20px;
			color: #4fc3f7;
			text-shadow: 0 0 5px rgba(79, 195, 247, 0.3);
		}

		.stats {
			text-align: center;
			margin: 15px 0;
			font-size: 1.1em;
		}

		.table-container {
			overflow-x: auto;
			box-shadow: 0 0 15px rgba(0, 0, 0, 0.4);
			border-radius: 8px;
		}

		table {
			border-collapse: collapse;
			width: 100%;
			min-width: 800px;
		}

		td,
		th {
			padding: 10px 8px;
			border: 1px solid #304562;
		}

		.header-row {
			background: linear-gradient(to bottom, #263850, #1c2a40);
			color: #bbdefb;
			font-weight: bold;
			position: sticky;
			top: 0;
		}

		.header-row th {
			padding: 12px 8px;
		}

		tbody tr:nth-child(even) {
			background-color: #1a2435;
		}

		tbody tr:nth-child(odd) {
			background-color: #16202e;
		}

		tbody tr:hover {
			background-color: #243147;
		}

		.materials-row {
			background-color: #1c2b3e;
			color: #a5d6ff;
		}

		.materials-row td {
			padding: 8px 15px;
		}

		.item-icon {
			width: 32px;
			height: 32px;
			object-fit: contain;
			vertical-align: middle;
		}

		.num-cell {
			text-align: center;
			font-family: 'Courier New', monospace;
		}

		.type-cell {
			color: #81d4fa;
		}

		.attack-cell,
		.defense-cell {
			line-height: 1.6;
		}
	</style>

<body>
	<h1>游戏道具列表</h1>
	<?php
	// 包含道具数据加载函数
	require_once __DIR__ . '/../data/data.item.php';

	// 安全检查函数
	function safe_print($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	// 道具列表容器
	echo '<table>';
	echo '<thead><tr class="header-row">';
	echo '<td>ID</td>';
	echo '<td>图标</td>';
	echo '<td>名称</td>';
	echo '<td>类型</td>';
	echo '<td>攻击力</td>';
	echo '<td>防御力</td>';
	echo '<td>重量</td>';
	echo '<td>购买价格</td>';
	echo '<td>出售价格</td>';
	echo '</tr></thead>';

	echo '<tbody>';

	$itemCounter = 0;
	$imagePath = '../image/icon/';

	for ($itemId = 1000; $itemId < 10000; $itemId++) {
		$itemData = LoadItemData($itemId);

		// 跳过无效道具
		if (!$itemData) {
			continue;
		}

		// 处理基础道具属性
		echo '<tr>';
		echo '<td>' . safe_print($itemId) . '</td>';
		echo '<td>' . ('<img src=' . $imagePath . safe_print($itemData["img"]) . '>') ?? '' . '</td>';
		echo '<td>' . safe_print($itemData["name"] ?? '') . '</td>';
		echo '<td>' . safe_print($itemData["type"] ?? '') . '</td>';

		// 处理攻击力属性
		echo '<td>';
		if (!empty($itemData["atk"][0]) || !empty($itemData["atk"][1])) {
			echo safe_print($itemData["atk"][0] ?? 0) . '<br>' . safe_print($itemData["atk"][1] ?? 0);
		} else {
			echo 'N/A';
		}
		echo '</td>';

		// 处理防御力属性
		echo '<td>';
		if (isset($itemData["def"]) && is_array($itemData["def"])) {
			echo safe_print($itemData["def"][0] ?? 0) . '+' . safe_print($itemData["def"][1] ?? 0) . '<br>';
			echo safe_print($itemData["def"][2] ?? 0) . '+' . safe_print($itemData["def"][3] ?? 0);
		} else {
			echo 'N/A';
		}
		echo '</td>';

		echo '<td>' . safe_print($itemData["handle"] ?? 0) . '</td>';
		echo '<td>' . safe_print($itemData["buy"] ?? 0) . '</td>';
		echo '<td>' . safe_print($itemData["sell"] ?? 0) . '</td>';
		echo '</tr>';

		// 处理制作材料
		if (!empty($itemData["need"]) && is_array($itemData["need"])) {
			echo '<tr class="materials-row">';
			echo '<td colspan="9">';
			echo '<strong>制作材料:</strong> ';

			$materials = [];
			foreach ($itemData["need"] as $materialId => $amount) {
				$materialData = LoadItemData($materialId);
				if ($materialData) {
					$materialName = safe_print($materialData["name"] ?? '未知材料');
					$materialIcon = ('<img src=' . $imagePath . safe_print($materialData["img"]) . '>') ?? '';
					$materials[] = "{$materialIcon} {$materialName} × " . safe_print($amount);
				}
			}

			echo implode(' | ', $materials);
			echo '</td>';
			echo '</tr>';
		}

		$itemCounter++;
	}

	echo '</tbody>';
	echo '</table>';

	// 显示道具统计信息
	echo "<p>共加载道具: {$itemCounter} 个</p>";
	?>
</body>

</html>