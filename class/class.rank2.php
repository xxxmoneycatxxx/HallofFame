<?php

/**
 * 高级排名系统类
 * 
 * 功能说明：
 * 1. 实现多玩家同排名的复杂排名系统
 * 2. 管理玩家排名数据、对战逻辑和战绩记录
 * 3. 处理排名挑战、战斗结算和位置变更
 * 
 * 主要功能模块：
 * 1. 排名架构：
 *    - 支持同一排名位置多玩家并列
 *    - 动态排名位置计算
 *    - 排名数据持久化存储
 * 2. 对战系统：
 *    - 智能对手匹配机制
 *    - 战斗流程控制与结果判定
 *    - 战绩记录与分析
 * 3. 排名操作：
 *    - 新玩家加入排名
 *    - 排名位置交换
 *    - 玩家移除排名
 * 
 * 技术特点：
 * 1. 复杂排名结构：
 *    - 多维数组存储同排名玩家
 *    - 动态计算排名位置容量
 * 2. 智能匹配：
 *    - 根据排名位置自动选择对手
 *    - 处理各种异常情况（如对手不存在）
 * 3. 数据完整性：
 *    - 文件锁定保证并发安全
 *    - 自动清理无效玩家数据
 * 
 * 特殊机制：
 * 1. 同排名系统：
 *    - 不同排名位置可容纳不同数量玩家
 *    - 首位仅限1人，后续位置可多人并列
 * 2. 战绩分析：
 *    - 记录胜率、防守次数等详细数据
 *    - 战斗冷却时间机制
 * 3. 异常处理：
 *    - 自动处理无效玩家账号
 *    - 智能跳过未设置队伍的对手
 * 
 * 使用注意事项：
 * 1. 数据存储：
 *    - 使用文件系统存储排名数据
 *    - 读写操作自动加锁保证数据安全
 * 2. 对战限制：
 *    - 玩家必须设置排名战队伍
 *    - 战斗冷却时间限制
 * 3. 显示功能：
 *    - 支持排名范围显示
 *    - 提供详细战绩数据
 * 
 * 使用流程：
 * 1. 初始化Ranking对象
 * 2. 玩家发起挑战(Challenge)
 * 3. 系统自动匹配对手并进行战斗
 * 4. 根据战斗结果更新排名和战绩
 * 5. 保存更新后的排名数据
 */

class Ranking
{

    var $fp;

    var $Ranking    = array();
    var $UserName;
    var $UserRecord;

    function __construct()
    {
        $file    = RANKING;
        $this->Ranking = []; // 显式初始化为空数组
        
        if (!file_exists($file)) {
            return;
        }
        
        $this->fp = FileLock($file);
        $Place    = 0;
        
        // 初始化首位数组
        $this->Ranking[$Place] = [];
        
        while ($line = fgets($this->fp)) {
            $line    = trim($line);
            if ($line == "") continue;
            
            // 确保当前位置是数组
            if (!isset($this->Ranking[$Place]) || !is_array($this->Ranking[$Place])) {
                $this->Ranking[$Place] = [];
            }
            
            // 检查当前排名位置是否已满
            if (count($this->Ranking[$Place]) >= $this->SamePlaceAmount($Place)) {
                $Place++;
                $this->Ranking[$Place] = []; // 初始化新位置
            }
            
            $this->Ranking[$Place][]    = $line;
        }
        
        // 转换数据结构（保持原逻辑）
        if (!empty($this->Ranking)) {
            foreach ($this->Ranking as $Rank => $SamePlaces) {
                if (!is_array($SamePlaces))
                    continue;
                foreach ($SamePlaces as $key => $val) {
                    $list    = explode("<>", $val);
                    $this->Ranking["$Rank"]["$key"]    = array();
                    $this->Ranking["$Rank"]["$key"]["id"]    = $list["0"];
                }
            }
        }
    }
    //////////////////////////////////////////////
    // ランキング戦する。戦う。
    function Challenge($user)
    {
        // ランキングが無いとき(1位になる)
        if (empty($this->Ranking)) {
            $this->JoinRanking($user->id);
            $this->SaveRanking();
            print("Rank starts.");
            //return array($message,true);
            return false;
        }
        //自分の順位
        $MyRank    = $this->SearchID($user->id);

        // 1位の場合。
        if ($MyRank["0"] === 0) {
            ShowError("第一名不可再挑战.");
            //return array($message,true);
            return false;
        }

        // 自分がランク外なら ////////////////////////////////////
        if (!$MyRank) {
            $this->JoinRanking($user->id); //自分を最下位にする。
            $MyPlace    = count($this->Ranking) - 1; //自分のランク(最下位)
            $RivalPlace    = (int)($MyPlace - 1);

            // 相手が首位なのかどうか
            if ($RivalPlace === 0)
                $DefendMatch    = true;
            else
                $DefendMatch    = false;

            //自分より1個上の人が相手。
            $RivalRankKey    = array_rand($this->Ranking[$RivalPlace]);
            $RivalID    = $this->Ranking[$RivalPlace][$RivalRankKey]["id"]; //対戦する相手のID
            $Rival    = new user($RivalID);

            $Result    = $this->RankBattle($user, $Rival, $MyPlace, $RivalPlace);
            $Return    = $this->ProcessByResult($Result, $user, $Rival, $DefendMatch);

            return $Return;
        }

        // 2位-最下位の人の処理。////////////////////////////////
        if ($MyRank) {
            $RivalPlace    = (int)($MyRank["0"] - 1); //自分より順位が1個上の人。

            // 相手が首位なのかどうか
            if ($RivalPlace === 0)
                $DefendMatch    = true;
            else
                $DefendMatch    = false;

            //自分より1個上の人が相手
            $RivalRankKey    = array_rand($this->Ranking[$RivalPlace]);
            $RivalID    = $this->Ranking[$RivalPlace][$RivalRankKey]["id"];
            $Rival    = new user($RivalID);
            //$MyID        = $this->Ranking[$MyRank["0"]][$MyRank["1"]]["id"];
            //$MyID        = $id;
            //list($message,$result)    = $this->RankBattle($MyID,$RivalID);
            $Result    = $this->RankBattle($user, $Rival, $MyRank["0"], $RivalPlace);
            $Return    = $this->ProcessByResult($Result, $user, $Rival, $DefendMatch);

            return $Return;
        }
    }

    //////////////////////////////////////////////
    // 戦わせる
    function RankBattle($user, $Rival, $UserPlace, $RivalPlace)
    {

        $UserPlace    = "[" . ($UserPlace + 1) . "位]";
        $RivalPlace    = "[" . ($RivalPlace + 1) . "位]";

        if ($Rival->is_exist() == false) {
            ShowError("对手不存在(不战而胜)");
            $this->DeleteRank($DefendID);
            $this->SaveRanking();
            //return array(true);
            return "DEFENDER_NO_ID";
        }

        // お互いのランキンぐ用のパーテテティーを読み込む
        $Party_Challenger    = $user->RankParty();
        $Party_Defender        = $Rival->RankParty();


        if ($Party_Challenger === false) {
            ShowError("戦うメンバーがいません（？）。");
            return "CHALLENGER_NO_PARTY";
        }

        if ($Party_Defender === false) {
            ShowError($Rival->name . " 对战的人物还未决定<br />(不战而胜)");
            return "DEFENDER_NO_PARTY"; //不战而胜とする
        }

        include_once(CLASS_BATTLE);
        $battle    = new battle($Party_Challenger, $Party_Defender);
        $battle->SetBackGround("colosseum");
        $battle->SetResultType(1); // 決着つかない場合は生存者の数で決めるようにする
        $battle->SetTeamName($user->name . $UserPlace, $Rival->name . $RivalPlace);
        $battle->Process(); //戦闘闘闘闘開始
        $battle->RecordLog("RANK");
        $Result    = $battle->ReturnBattleResult(); // 戦闘闘闘闘結果

        //return array("Battle",$Result);
        if ($Result === TEAM_0) {
            return "CHALLENGER_WIN";
        } else if ($Result === TEAM_1) {
            return "DEFENDER_WIN";
        } else if ($Result === DRAW) {
            return "DRAW_GAME";
        } else {
            return "DRAW_GAME"; //(エラー)予定では出ないエラー(回避用)
        }
    }
    //////////////////////////////////////////////////
    //    結果によって処理を変える
    function ProcessByResult($Result, $user, $Rival, $DefendMatch)
    {
        switch ($Result) {

            // 受けた側のIDが存在しない
            case "DEFENDER_NO_ID":
                $this->ChangePlace($user->id, $Rival->id);
                $this->DeleteRank($Rival->id);
                $this->SaveRanking();
                return false;
                break;

            // 挑戦側PT無し
            case "CHALLENGER_NO_PARTY":
                return false;
                break;

            // 受けた側PT無し
            case "DEFENDER_NO_PARTY":
                $this->ChangePlace($user->id, $Rival->id);
                $this->SaveRanking();
                //$user->RankRecord(0,"CHALLENGER",$DefendMatch);
                $user->SetRankBattleTime(time() + RANK_BATTLE_NEXT_WIN);
                $Rival->RankRecord(0, "DEFEND", $DefendMatch);
                $Rival->SaveData();
                return true;
                break;

            // 挑戦者勝ち
            case "CHALLENGER_WIN":
                $this->ChangePlace($user->id, $Rival->id);
                $this->SaveRanking();
                $user->RankRecord(0, "CHALLENGER", $DefendMatch);
                $user->SetRankBattleTime(time() + RANK_BATTLE_NEXT_WIN);
                $Rival->RankRecord(0, "DEFEND", $DefendMatch);
                $Rival->SaveData();
                return "BATTLE";
                break;

            // 受けた側勝ち
            case "DEFENDER_WIN":
                //$this->SaveRanking();
                $user->RankRecord(1, "CHALLENGER", $DefendMatch);
                $user->SetRankBattleTime(time() + RANK_BATTLE_NEXT_LOSE);
                $Rival->RankRecord(1, "DEFEND", $DefendMatch);
                $Rival->SaveData();
                return "BATTLE";
                break;

            // 引分け
            case "DRAW_GAME":
                //$this->SaveRanking();
                $user->RankRecord("d", "CHALLENGER", $DefendMatch);
                $user->SetRankBattleTime(time() + RANK_BATTLE_NEXT_LOSE);
                $Rival->RankRecord("d", "DEFEND", $DefendMatch);
                $Rival->SaveData();
                return "BATTLE";
                break;
            default:
                return true;
                break;
        }
    }
    //////////////////////////////////////////////////
    //    引数の順位 と 同じ順位の人数
    function SamePlaceAmount($Place)
    {
        switch (true) {
            case ($Place == 0):
                return 1; //1位
            case ($Place == 1):
                return 2; //2位
            case ($Place == 2):
                return 3; //3位
            case (2 < $Place):
                return 3;
        }
    }
    //////////////////////////////////////////////
    // ランキングの最下位に参加させる
    function JoinRanking($id)
    {
        if (empty($this->Ranking)) {
            $this->Ranking["0"]["0"]["id"]    = $id;
            return;
        }
        
        $last    = count($this->Ranking) - 1;
        // 最下位の順位が定員オーバーになる場合
        if (count($this->Ranking[$last]) >= $this->SamePlaceAmount($last)) {
            $this->Ranking[$last + 1]["0"]["id"]    = $id;
            // ならない場合
        } else {
            $this->Ranking[$last][]["id"]    = $id;
        }
    }
    //////////////////////////////////////////////////
    // ランキングから消す
    function DeleteRank($id)
    {
        $place    = $this->SearchID($id);
        if ($place === false) return false; //削除失敗
        unset($this->Ranking[$place[0]][$place[1]]);
        
        // 如果该位置变为空数组，则移除该位置
        if (empty($this->Ranking[$place[0]])) {
            unset($this->Ranking[$place[0]]);
            // 重新索引数组
            $this->Ranking = array_values($this->Ranking);
        }
        return true; //削除成功
    }
    //////////////////////////////////////////////////
    // ランキングを保存する
    function SaveRanking()
    {
        $ranking = '';
        foreach ($this->Ranking as $rank => $val) {
            foreach ($val as $key => $val2) {
                $ranking    .= $val2["id"] . "\n";
            }
        }

        WriteFileFP($this->fp, $ranking);
        $this->fpclose();
    }
    //////////////////////////////////////////////////
    //    
    function fpclose()
    {
        if ($this->fp) {
            fclose($this->fp);
            unset($this->fp);
        }
    }
    //////////////////////////////////////////////////
    //    順位を入れ替える
    function ChangePlace($id_0, $id_1)
    {
        $Place_0    = $this->SearchID($id_0);
        $Place_1    = $this->SearchID($id_1);
        $temp    = $this->Ranking[$Place_0["0"]][$Place_0["1"]];
        $this->Ranking[$Place_0["0"]][$Place_0["1"]]    = $this->Ranking[$Place_1["0"]][$Place_1["1"]];
        $this->Ranking[$Place_1["0"]][$Place_1["1"]]    = $temp;
    }
    //////////////////////////////////////////////////
    // $id のランク位置を探す
    function SearchID($id)
    {
        foreach ($this->Ranking as $rank => $val) {
            if (!is_array($val)) continue;
            foreach ($val as $key => $val2) {
                if ($val2["id"] == $id)
                    return array((int)$rank, (int)$key); // 順位無いの何番目か。
            }
        }
        return false;
    }
    //////////////////////////////////////////////////
    // ランキングの表示
    function ShowRanking($from = false, $to = false, $bold_id = false)
    {
        // 範囲が無い場合は全ランキングを表示
        if ($from === false or $to === false) {
            $from    = 0; //首位
            $to        = count($this->Ranking); //最下位
        }

        // 太字にするランク
        if ($bold_id)
            $BoldRank    = $this->SearchID($bold_id);

        $LastPlace    = count($this->Ranking) - 1; // 最下位

        print("<table cellspacing=\"0\">\n");
        print("<tr><td class=\"td6\" style=\"text-align:center\">排位</td><td  class=\"td6\" style=\"text-align:center\">队伍</td></tr>\n");
        for ($Place = $from; $Place < $to + 1; $Place++) {
            if (!isset($this->Ranking[$Place]) || empty($this->Ranking[$Place]))
                continue;
            print("<tr><td class=\"td7\" valign=\"middle\" style=\"text-align:center\">\n");
            // 順位アイコン
            switch ($Place) {
                case 0:
                    print('');
                    break;
                case 1:
                    print('');
                    break;
                case 2:
                    print('');
                    break;
                default:
                    if ($Place == $LastPlace)
                        print("底");
                    else
                        print(($Place + 1) . "位");
            }
            print("</td><td class=\"td8\">\n");
            foreach ($this->Ranking["$Place"] as $SubRank => $data) {
                list($Name, $R)    = $this->LoadUserName($data["id"], true); //成績も読み込む

                // 安全获取战绩数据
                $all = isset($R['all']) ? $R['all'] : 0;
                $win = isset($R['win']) ? $R['win'] : 0;
                $lose = isset($R['lose']) ? $R['lose'] : 0;
                $defend = isset($R['defend']) ? $R['defend'] : 0;

                // 计算平局次数
                $draw = $all - $win - $lose;

                // 计算胜率（需要处理除零错误）
                if ($all > 0) {
                    $WinProb = sprintf("%0.0f", ($win / $all) * 100);
                } else {
                    $WinProb = "--";
                }

                $Record    = "(" . $all . "战 " . $win . "胜" .
                    $lose . "败 " .
                    $draw . "引 " .
                    $defend . "防 " .
                    "胜率" . $WinProb . '%' .
                    ")";

                if (isset($BoldRank) && $BoldRank["0"] == $Place && $BoldRank["1"] == $SubRank) {
                    print('<span class="bold u">' . $Name . "</span> {$Record}");
                } else {
                    print($Name . " " . $Record);
                }
                print("<br />\n");
            }
            print("</td></tr>\n");
        }
        print("</table>\n");
    }
    //////////////////////////////////////////////
    //    ±ランク 対象ID
    function ShowRankingRange($id, $Amount)
    {
        $RankAmount    = count($this->Ranking);
        $Last    = $RankAmount - 1;
        do {
            // ランキングがAmount以上ないとき
            if ($RankAmount <= $Amount) {
                $start    = 0;
                $end    = $Last;
                break;
            }

            $Rank    = $this->SearchID($id);
            if ($Rank === false) {
                print("排名未知");
                return 0;
            }
            $Range    = floor($Amount / 2);
            // 首位に近いか首位
            if (($Rank[0] - $Range) <= 0) {
                $start    = 0;
                $end    = $Amount - 1;
                // 最下位にちかいか最下位
            } else if ($Last < ($Rank[0] + $Range)) {
                $start    = $RankAmount - $Amount;
                $end    = $RankAmount;
                // 範囲内におさまる
            } else {
                $start    = $Rank[0] - $Range;
                $end    = $Rank[0] + $Range;
            }
        } while (0);

        $this->ShowRanking($start, $end, $id);
    }
    //////////////////////////////////////////////
    //    ユーザの名前を呼び出す
    function LoadUserName($id, $rank = false)
    {

        if (!isset($this->UserName[$id])) {
            $User    = new user($id);
            $Name    = $User->Name();
            $Record    = $User->RankRecordLoad();
            if ($Name !== false) {
                $this->UserName[$id]    = $Name;

                // 确保记录是数组格式
                if ($rank && !is_array($Record)) {
                    $Record = [
                        'all' => 0,
                        'win' => 0,
                        'lose' => 0,
                        'defend' => 0
                    ];
                }
                $this->UserRecord[$id]    = $Record;
            } else {
                $this->UserName[$id]    = "-";

                $this->DeleteRank($id);

                foreach ($this->Ranking as $rank => $val) {
                    foreach ($val as $key => $val2) {
                        $ranking    .= $val2["id"] . "\n";
                    }
                }

                WriteFileFP($this->fp, $ranking);
            }
        }

        if ($rank) {
            // 确保返回数组格式
            if (!is_array($this->UserRecord[$id])) {
                $this->UserRecord[$id] = [
                    'all' => 0,
                    'win' => 0,
                    'lose' => 0,
                    'defend' => 0
                ];
            }
            return array($this->UserName[$id], $this->UserRecord[$id]);
        } else {
            return $this->UserName[$id];
        }
    }
    //////////////////////////////////////////////////
    //    
    function dump()
    {
        print("<pre>" . print_r($this, 1) . "</pre>\n");
    }
    // end of class
}