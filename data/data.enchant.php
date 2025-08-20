<?php

/**
 * 道具附魔效果添加函数
 * 
 * 功能说明：
 * 1. 根据附魔代码为道具添加额外属性
 * 2. 更新道具的附加属性描述
 * 3. 特殊附魔会修改道具名称前缀
 * 
 * 参数说明：
 * @param array &$item 道具数据（引用传递）
 *   - 将被修改的字段：
 *        atk: 攻击力数组 [物理攻击, 魔法攻击]
 *        def: 防御力数组 [物理防御, 魔法防御]
 *        P_MAXHP: 生命值固定加成
 *        M_MAXHP: 生命值百分比加成
 *        P_MAXSP: 法力值固定加成
 *        M_MAXSP: 法力值百分比加成
 *        P_STR: 力量固定加成
 *        P_INT: 智力固定加成
 *        P_DEX: 敏捷固定加成
 *        P_SPD: 速度固定加成
 *        P_LUK: 幸运固定加成
 *        option: 附加属性描述字符串
 *        AddName: 道具附加名称
 * @param int $opt 附魔效果代码
 * 
 * 附魔效果分类：
 * 1. 物理攻击加成(100-119): 固定值增加物理攻击
 * 2. 魔法攻击加成(150-169): 固定值增加魔法攻击
 * 3. 物理攻击倍率(200-203): 百分比提升物理攻击
 * 4. 魔法攻击倍率(250-253): 百分比提升魔法攻击
 * 5. 物理防御加成(300-304): 固定值增加物理防御
 * 6. 魔法防御加成(350-354): 固定值增加魔法防御
 * 7. 生命值加成(H00-H05): 固定值增加最大生命
 * 8. 生命值倍率(HM0-HM5): 百分比增加最大生命
 * 9. 法力值加成(S00-S03): 固定值增加最大法力
 * 10. 法力值倍率(SM0-SM5): 百分比增加最大法力
 * 11. 属性加成：
 *    - 力量(P00-P09)
 *    - 智力(I00-I09)
 *    - 敏捷(D00-D09)
 *    - 速度(A00-A09)
 *    - 幸运(L00-L09)
 * 12. 特殊前缀效果(X00,X01,M01): 根据道具类型添加不同效果
 * 
 * 注意事项：
 * 1. 函数直接修改传入的$item数组
 * 2. 所有数值加成都会更新到道具的option描述
 * 3. 特殊前缀效果会修改道具的AddName字段
 */
function AddEnchantData(&$item, $opt) {
    // 预处理：清理option结尾逗号
    $item["option"] = rtrim($item["option"], ", ");

    // 数字型附魔代码处理
    if (is_numeric($opt)) {
        $value = (int)$opt;
        $handled = true;

        // 物理攻击加成 (100-119)
        if ($value >= 100 && $value <= 119) {
            $bonus = $value - 99;
            $item["atk"][0] += $bonus;
            $item["option"] .= ", Atk+$bonus";
        }
        // 魔法攻击加成 (150-169)
        elseif ($value >= 150 && $value <= 169) {
            $bonus = $value - 149;
            $item["atk"][1] += $bonus;
            $item["option"] .= ", Matk+$bonus";
        }
        // 物理攻击倍率 (200-203)
        elseif ($value >= 200 && $value <= 203) {
            $rates = [1.05, 1.10, 1.15, 1.20];
            $rate = $rates[$value - 200];
            $item["atk"][0] = round($item["atk"][0] * $rate);
            $item["option"] .= ", Atk+" . (($rate-1)*100) . "%";
        }
        // 魔法攻击倍率 (250-253)
        elseif ($value >= 250 && $value <= 253) {
            $rates = [1.05, 1.10, 1.15, 1.20];
            $rate = $rates[$value - 250];
            $item["atk"][1] = round($item["atk"][1] * $rate);
            $item["option"] .= ", Matk+" . (($rate-1)*100) . "%";
        }
        // 物理防御加成 (300-304)
        elseif ($value >= 300 && $value <= 304) {
            $bonus = $value - 299;
            $item["def"][0] += $bonus;
            $item["option"] .= ", Def+$bonus";
        }
        // 魔法防御加成 (350-354)
        elseif ($value >= 350 && $value <= 354) {
            $bonus = $value - 349;
            $item["def"][2] += $bonus;
            $item["option"] .= ", Mdef+$bonus";
        }
        else {
            $handled = false;
        }
        
        if ($handled) {
            $item["option"] = ltrim($item["option"], ", ");
            return;
        }
    }

    // 字符串型附魔代码处理
    if (is_string($opt)) {
        $handled = true;
        $prefix = substr($opt, 0, 1);
        $suffix = substr($opt, 1);

        switch ($prefix) {
            // 生命值固定加成 (H00-H05)
            case 'H':
                if (is_numeric($suffix) && $suffix >= 0 && $suffix <= 5) {
                    $bonus = ($suffix + 1) * 10;
                    $item["P_MAXHP"] += $bonus;
                    $item["option"] .= ", MAXHP+$bonus";
                }
                break;
            
            // 生命值百分比加成 (HM0-HM5)
            case 'M':
                if ($opt[0] === 'H' && is_numeric($opt[2])) { // 处理HM前缀
                    $suffixVal = (int)$opt[2];
                    if ($suffixVal >= 0 && $suffixVal <= 5) {
                        $bonus = $suffixVal + 1;
                        $item["M_MAXHP"] += $bonus;
                        $item["option"] .= ", MAXHP+{$bonus}%";
                    }
                }
                break;
            
            // 法力值固定加成 (S00-S03)
            case 'S':
                if (is_numeric($suffix) && $suffix >= 0 && $suffix <= 3) {
                    $bonus = ($suffix + 1) * 10;
                    $item["P_MAXSP"] += $bonus;
                    $item["option"] .= ", MAXSP+$bonus";
                }
                break;
            
            // 属性加成统一处理 (P/I/D/A/L)
            case 'P': // 力量
            case 'I': // 智力
            case 'D': // 敏捷
            case 'A': // 速度
            case 'L': // 幸运
                if (is_numeric($suffix) && $suffix >= 0 && $suffix <= 9) {
                    $bonus = $suffix + 1;
                    $propMap = [
                        'P' => 'P_STR',
                        'I' => 'P_INT',
                        'D' => 'P_DEX',
                        'A' => 'P_SPD',
                        'L' => 'P_LUK'
                    ];
                    $textMap = [
                        'P' => 'STR',
                        'I' => 'INT',
                        'D' => 'DEX',
                        'A' => 'SPD',
                        'L' => 'LUK'
                    ];
                    
                    $prop = $propMap[$prefix];
                    $text = $textMap[$prefix];
                    $item[$prop] += $bonus;
                    $item["option"] .= ", {$text}+{$bonus}";
                }
                break;
                
            default:
                $handled = false;
        }
        
        if ($handled) {
            $item["option"] = ltrim($item["option"], ", ");
            return;
        }
    }

    // 特殊前缀处理（保持原逻辑）
    switch ($opt) {
        case "X00":
            if ($item["type2"] == "WEAPON") {
                $item["atk"][0] += 5;
                $item["option"] .= ", Atk+5";
                $item["AddName"] = "力量";
            } else {
                $item["def"][0] += 2;
                $item["option"] .= ", Def+2";
                $item["AddName"] = "稳固";
            }
            break;
            
        case "X01":
            if ($item["type2"] == "WEAPON") {
                $item["atk"][1] += 5;
                $item["option"] .= ", Matk+5";
                $item["AddName"] = "智慧";
            } else {
                $item["def"][2] += 2;
                $item["option"] .= ", Mdef+2";
                $item["AddName"] = "睿智";
            }
            break;
            
        case "M01":
            $item["P_MAXHP"] += 10;
            $item["option"] .= ", MAXHP+10";
            $item["AddName"] = "哥布林之";
            break;
            
        // 保留空操作case
        case 400:
            break;
    }
    
    $item["option"] = ltrim($item["option"], ", ");
}