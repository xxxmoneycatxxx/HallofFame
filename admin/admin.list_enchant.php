<?php

/**
 * 装备效果列表 (PHP 8 兼容版本)
 */

// 使用现代路径包含方式 (根据项目结构调整路径)
include_once __DIR__ . "/../data/data.create.php";
include_once __DIR__ . "/../data/data.enchant.php";

// 获取剑类武器的附魔可能性
$enchantLevels = ItemAbilityPossibility("剑");

// 验证函数返回结构
if (!isset($enchantLevels[0]) || !isset($enchantLevels[1])) {
    throw new RuntimeException("ItemAbilityPossibility 返回了无效的数据结构");
}

// 处理低级附魔
print("<section class='enchant-section'><h3>低级附魔效果</h3>\n");
foreach ($enchantLevels[0] as $enchant) {
    $item = [];
    AddEnchantData($item, $enchant);
    
    // 验证输出结构
    if (!isset($item["option"])) {
        throw new RuntimeException("AddEnchantData 未设置 'option' 属性");
    }
    
    printf(
        '<div class="enchant-item"><span class="enchant-id">%s</span>: %s</div>' . "\n",
        htmlspecialchars($enchant),
        htmlspecialchars($item["option"])
    );
}
print("</section>\n");

// 处理高级附魔
print("<section class='enchant-section'><h3>高级附魔效果</h3>\n");
foreach ($enchantLevels[1] as $enchant) {
    $item = [];
    AddEnchantData($item, $enchant);
    
    printf(
        '<div class="enchant-item"><span class="enchant-id">%s</span>: %s</div>' . "\n",
        htmlspecialchars($enchant),
        htmlspecialchars($item["option"])
    );
}
print("</section>\n");

/**
 * 调试输出函数 (PHP 8 优化版)
 * 
 * @param mixed $var 需要输出的变量
 */
function dump(mixed $var): void
{
    print("<pre>\n");
    var_export($var);
    print("\n</pre>\n");
}