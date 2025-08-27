<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>SetAction</title>
    <style type="text/css">
        * {
            line-height: 140%;
            font-family: Osaka, Verdana;
        }

        .bg {
            background-color: #cccccc;
        }

        body {
            background-color: #666666;
        }

        option {
            background-color: #dddddd;
        }

        input {
            background-color: #dddddd;
        }
    </style>
</head>

<body>
    <?php
    require_once '../setting.php';
    require_once '../class/global.php';
    require_once '../data/data.skill.php';

    // 安全访问全局变量
    $db = $GLOBALS['DB'] ?? null;

    // 行数处理 - 使用空值合并运算符
    $rows = isset($_POST["patternNum"]) ? (int)$_POST["patternNum"] : 8;
    define("IMG", "../image/char/");

    // Load 操作处理
    if (isset($_POST["Load"]) && isset($_POST["loadMob"]) && $_POST["loadMob"]) {
        include_once("../data/data.monster.php");
        $monster = CreateMonster($_POST["loadMob"]);
        
        if ($monster) {
            for ($i = 0; $i < $rows; $i++) {
                $_POST["judge" . $i] = $monster["judge"][$i] ?? null;
                $_POST["quantity" . $i] = $monster["quantity"][$i] ?? null;
                $_POST["skill" . $i] = $monster["action"][$i] ?? null;
            }
            // print('<span style="font-weight:bold">' . $_POST["loadMob"] . " " . $monster["name"] . '</span>');
			print('<span style="font-weight:bold">' . $_POST["loadMob"] . " " . $monster["name"] . '</span><img src="' . IMG . $monster["img"] . '" />');
        }
    }

    // Add 操作处理
    if (isset($_POST["add"]) && isset($_POST["number"])) {
        $number = (int)$_POST["number"];
        $var = array("judge", "quantity", "skill");
        
        foreach ($var as $head) {
            for ($i = $rows; -1 < $i; $i--) {
                if ($number == $i) {
                    $_POST[$head . $i] = null;
                } else if ($number < $i) {
                    $_POST[$head . $i] = $_POST[$head . ($i - 1)] ?? null;
                } else {
                    break;
                }
            }
        }
    }

    // Delete 操作处理
    if (isset($_POST["delete"]) && isset($_POST["number"])) {
        $number = (int)$_POST["number"];
        $var = array("judge", "quantity", "skill");
        
        foreach ($var as $head) {
            for ($i = 0; $i < $rows; $i++) {
                if ($number <= $i) {
                    $_POST[$head . $i] = $_POST[$head . ($i + 1)] ?? null;
                }
            }
        }
    }

    // 生成代码处理
    if (isset($_POST["make"])) {
        $judgeString = '"judge"	=> array(';
        $quantityString = '"quantity"	=> array(';
        $skillString = '"action"	=> array(';
        
        for ($i = 0; $i < $rows; $i++) {
            if (!empty($_POST["judge" . $i]) && !empty($_POST["skill" . $i])) {
                $judgeString .= ($_POST["judge" . $i] ?? '') . ", ";
                $quantityString .= ($_POST["quantity" . $i] ?? '') . ", ";
                $skillString .= ($_POST["skill" . $i] ?? '') . ", ";
            }
        }
        
        $judgeString .= "),\n";
        $quantityString .= "),\n";
        $skillString .= "),\n";

        print('<textarea style="width:800px;height:100px">');
        print($judgeString . $quantityString . $skillString);
        print("</textarea>\n");
    }

    // 加载判定数据
    include_once("../data/data.judge_setup.php");
    $judgeList = [];
    
    for ($i = 1000; $i < 10000; $i++) {
        $judge = LoadJudgeData($i);
        if (!$judge) continue;
        
        $judgeList["$i"]["exp"] = $judge["exp"] ?? '';
        if (isset($judge["css"])) {
            $judgeList["$i"]["css"] = true;
        }
    }

    // 加载技能数据
    include_once("../data/data.skill.php");
    $skillList = [];
    
    for ($i = 1000; $i < 10000; $i++) {
        $skill = LoadSkillData($i);
        if (!$skill) continue;
        
        $sp = $skill["sp"] ?? 0;
        $skillList["$i"] = $i . " - " . $skill["name"] . "(sp:{$sp})";
    }

    print('<form method="post" action="?">' . "\n");
    print("<table>\n");
    
    for ($i = 0; $i < $rows; $i++) {
        print("<tr><td>\n");
        print('<span style="font-weight:bold">' . sprintf("%2s", $i + 1) . "</span>");
        print("</td><td>\n");
        
        // 判定列表
        print('<select name="judge' . $i . '">' . "\n");
        print('<option></option>' . "\n");
        
        foreach ($judgeList as $key => $exp) {
            $css = !empty($exp["css"]) ? ' class="bg"' : '';
            $selected = (isset($_POST["judge" . $i]) && $_POST["judge" . $i] == $key) ? 'selected' : '';
            
            print('<option value="' . $key . '"' . $css . ' ' . $selected . '>' . 
                  ($exp["exp"] ?? '') . '</option>' . "\n");
        }
        
        print("</select>\n");
        print("</td><td>\n");
        
        // 数值
        $quantityValue = isset($_POST["quantity" . $i]) ? $_POST["quantity" . $i] : "0";
        print('<input type="text" name="quantity' . $i . '" value="' . $quantityValue . '" size="10" />' . "\n");
        print("</td><td>\n");
        
        // 技能
        print('<select name="skill' . $i . '">' . "\n");
        print('<option></option>' . "\n");
        
        foreach ($skillList as $key => $exp) {
            $selected = (isset($_POST["skill" . $i]) && $_POST["skill" . $i] == $key) ? 'selected' : '';
            print('<option value="' . $key . '" ' . $selected . '>' . $exp . '</option>' . "\n");
        }
        
        print("</select>\n");
        print("</td><td>\n");
        print('<input type="radio" name="number" value="' . $i . '">' . "\n");
        print("</td></tr>\n");
    }
    
    print("</table>\n");
    
    $patternNum = isset($_POST["patternNum"]) ? $_POST["patternNum"] : "8";
    print('判定次数: <input type="text" name="patternNum" size="10" value="' . $patternNum . '" /><br />' . "\n");
    print('<input type="submit" value="创建" name="make">' . "\n");
    print('<input type="submit" value="添加" name="add">' . "\n");
    print('<input type="submit" value="删除" name="delete"><br />' . "\n");
    print('输入怪物id: <input type="text" name="loadMob" size="10" value="' . ($_POST["loadMob"] ?? '') . '" /> <input type="submit" value="读取" name="Load" />');
    print("</form>\n");
    ?>
</body>
</html>