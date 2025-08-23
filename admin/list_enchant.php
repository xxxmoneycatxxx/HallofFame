<?php

/**
 * 物品附魔效果测试脚本
 * 
 * 功能说明：
 * 1. 测试武器附魔系统功能
 * 2. 展示不同品质的附魔效果
 * 3. 验证附魔数据是否正确加载
 * 
 * 此脚本用于测试游戏中的物品附魔系统：
 * - 加载物品创建和附魔数据文件
 * - 获取特定武器类型（剑）的附魔可能性
 * - 分别展示低级和高级附魔效果
 * - 提供附魔效果的可视化展示
 * 
 * 使用说明：
 * 1. 直接运行脚本查看输出结果
 * 2. 输出分为低级(LOW)和高级(HIGH)附魔效果
 * 3. 每个附魔效果显示附魔ID和对应的属性加成
 * 
 * 测试对象：
 * - 武器类型：剑
 * - 测试函数：ItemAbilityPossibility()
 * - 附魔效果生成函数：AddEnchantData()
 */

include_once("../data/data.create.php");  // 物品创建数据
include_once("../data/data.enchant.php"); // 物品附魔数据

// 获取剑类武器的低级和高级附魔可能性
list($low, $high) = ItemAbilityPossibility("剑");

// 输出低级附魔效果
print("---------------LOW<br />\n");
foreach ($low as $enchant) {
	$item = array();
	AddEnchantData($item, $enchant); // 应用附魔效果到物品
	print('<span style="width:10em;text-align:right">' . $enchant . '</span>:' . $item["option"] . "<br />\n");
}

// 输出高级附魔效果
print("---------------HIGH<br />\n");
foreach ($high as $enchant) {
	$item = array();
	AddEnchantData($item, $enchant); // 应用附魔效果到物品
	print('<span style="width:10em;text-align:right">' . $enchant . '</span>:' . $item["option"] . "<br />\n");
}

/**
 * 调试输出函数
 * 
 * 以格式化方式输出变量内容，用于调试
 * 
 * @param mixed $var 需要输出的变量
 */
function dump($var)
{
	print("<pre>\n");
	var_dump($var);
	print("\n</pre>\n");
}
