<?php

/**
 * 游戏排名系统类
 * 
 * 功能说明：
 * 1. 实现游戏内排名对战系统
 * 2. 管理玩家排名数据及对战逻辑
 * 3. 处理排名挑战、战斗和位置变更
 * 
 * 主要功能模块：
 * 1. 排名管理：
 *    - 排名数据加载与保存
 *    - 玩家加入排名系统
 *    - 排名位置调整（上升/下降）
 * 2. 对战系统：
 *    - 挑战匹配机制
 *    - 战斗流程控制
 *    - 胜负判定与排名更新
 * 3. 显示功能：
 *    - 排名列表展示
 *    - 特定玩家周围排名显示
 * 
 * 技术特点：
 * 1. 文件存储：
 *    - 使用文件系统存储排名数据
 *    - 读写操作使用文件锁保证数据一致性
 * 2. 战斗集成：
 *    - 与battle类集成处理实际战斗
 *    - 自动处理特殊战斗情况（如对手不存在）
 * 3. 智能匹配：
 *    - 自动为不同排名的玩家匹配合适对手
 *    - 处理新玩家加入排名逻辑
 * 
 * 特殊机制：
 * 1. 挑战规则：
 *    - 第一名不可被挑战
 *    - 新玩家从最末位开始挑战
 *    - 胜利后与对手交换排名
 * 2. 异常处理：
 *    - 对手不存在时自动胜利
 *    - 对手未设置队伍时自动胜利
 *    - 玩家未注册排名队伍处理
 * 
 * 使用注意事项：
 * 1. 数据存储：
 *    - 排名数据存储在RANKING常量指定路径
 *    - 每次排名变更后需调用SaveRanking保存
 * 2. 对战限制：
 *    - 玩家必须设置排名战队伍才能参与
 *    - 第一名不可发起挑战
 * 3. 显示控制：
 *    - ShowRanking显示完整排名
 *    - ShowNearlyRank显示玩家周围排名
 * 
 * 使用流程：
 * 1. 初始化Ranking对象
 * 2. 玩家发起挑战(Challenge)
 * 3. 系统自动匹配对手并进行战斗
 * 4. 根据战斗结果更新排名
 * 5. 保存更新后的排名数据
 */

class Ranking
{
	/*
	処理手順(ランキング戦)
	1. 挑戦者のIDを渡す
	2.
		1位の人。
			戦闘できませんエラー。
		2-最下位の人。
			1個上の人を探す。
		ランク外の人。
			最下位の人を探す。
	3. 自分の相手と戦闘
	4. 勝利者、敗者の順位変動
	5. 保存。
	----------------------------
	エラー怖いよ、怖いよー
	起こりうる全ての(?)事象。
	◎|1位が居ない時(ランク自体が無いとき)挑戦者が1位になる。
	◎|1位は挑戦できない。
	◎|正常な2位-最下位の者が上に挑戦して勝つ。
	◎|正常な2位-最下位の者が上に挑戦して負ける。
	◎|正常な2位-最下位の者が上に挑戦して1位になる。
	△|チーム登録されて無い者は挑戦できない。
	○|チーム登録はしたけど、ランキングに参加してない者が挑戦する。
	◎|挑戦した相手のチームがおかしい(数名欠けている)。
	◎|挑戦した相手のチームがおかしい(全員欠けている)。
	○|挑戦した相手のID自体が消えている。
	○|IDを消したときランキングからも消滅する。
	△|時間制限がある場合は挑戦できない。
	◎|相手が時間制限中(→たぶん無関係)
	*/

	var $Ranking	= array();

	//////////////////////////////////////////////
	// ファイルから読み込んでランキングを配列にする
	function Ranking()
	{
		$file	= RANKING;

		if (!file_exists($file)) return 0;

		// ファイルから読んで配列にいれる
		$fp	= fopen($file, "r");
		flock($fp, LOCK_EX);
		while ($line = fgets($fp)) {
			$line	= trim($line);
			if (trim($line) == "") continue;
			$this->Ranking[]	= $line;
		}
		//$this->Ranking	= file($file);
		// 配列が0なら終了
		if (!$this->Ranking) return 0;
		// 区切って文字列を分割
		foreach ($this->Ranking as $rank => $val) {
			$list	= explode("<>", $val);
			$this->Ranking["$rank"]	= array();
			$this->Ranking["$rank"]["id"]	= $list["0"];
		}
		//$this->JoinRanking("yqyqqq","last");
		//dump($this->Ranking);
	}

	//////////////////////////////////////////////
	// ランキング戦する。戦う。
	function Challenge($id)
	{
		// ランキングが無いとき(1位になる)
		if (!$this->Ranking) {
			$this->JoinRanking($id);
			$this->SaveRanking();
			$message	= "排名开始.";
			return array($message, true);
		}

		$MyRank	= $this->SearchID($id); //自分の順位
		// 1位の場合。
		if ($MyRank === 0) {
			$message	= "第一名不可再挑战.";
			return array($message, true);
		}

		// 自分がランク外なら
		if (!$MyRank) {
			$this->JoinRanking($id); //自分を最下位にする。
			$MyRank	= count($this->Ranking) - 1; //自分のランク(最下位)

			$MyID	= $this->Ranking["$MyRank"]["id"];
			$RivalID = $this->Ranking["$MyRank" - 1]["id"]; //自分より1個上の人が相手。
			/*
			dump($this->Ranking);
			dump($RivalID);
			dump($MyID);
			dump($MyRank);//エラーでたら頑張れ
			return 0;*/
			list($message, $result)	= $this->RankBattle($MyID, $RivalID);
			if ($message == "Battle" && $result === true)
				$this->RankUp($MyID);

			$this->SaveRanking();
			return array($message, $result);
		}

		// 2位-最下位の人の処理。
		if ($MyRank) {
			$rival	= $MyRank - 1; //自分より順位が1個上の人。

			$MyID	= $this->Ranking["$MyRank"]["id"];
			$RivalID = $this->Ranking["$rival"]["id"];
			list($message, $result)	= $this->RankBattle($MyID, $RivalID);
			if ($message != "Battle")
				return array($message, $result);

			// 戦闘を行ってtrueならランクうｐ
			if ($message == "Battle" && $result === true) {
				$this->RankUp($MyID);
				$this->SaveRanking();
			}
			return array($message, $result);
		}
	}

	//////////////////////////////////////////////
	// 戦わせる
	function RankBattle($ChallengerID, $DefendID)
	{
		$challenger	= new user($ChallengerID);
		$challenger->CharDataLoadAll();
		$defender	= new user($DefendID);
		$defender->CharDataLoadAll();
		//print($ChallengerID."<br>".$DefendID."<br>");

		$Party_Challenger	= $challenger->RankParty();
		$Party_Defender		= $defender->RankParty();
		if ($Party_Defender == "NOID") { //ユーザ自体が既に存在しない場合
			$message	= "没有用户...<br />(自动胜利)";
			$this->DeleteRank($DefendID);
			$this->SaveRanking();
			return array($message, true);
		}

		// 返値
		// array(メッセージ,戦闘があったか,勝敗)

		// ランク用パーティーがありません！！！
		if ($Party_Challenger === false) {
			$message	= "设置战斗队伍!<br />(如果被挑下马的话排名也就没了)";
			return array($message, true);
		}
		// ランク用パーティーがありません！！！
		if ($Party_Defender === false) {
			$this->DeleteRank($DefendID);
			$this->SaveRanking();
			$message	= "{$defender->name} 没有排名战队伍<br />(自动胜利)";
			return array($message, true);
		}

		//dump($Party_Challenger);
		//dump($Party_Defender);
		include(CLASS_BATTLE);
		$battle	= new battle($Party_Challenger, $Party_Defender);
		$battle->SetBackGround("colosseum");
		$battle->SetTeamName($challenger->name, $defender->name);
		$battle->Process(); //戦闘開始
		$battle->RecordLog("RANK");
		return array("Battle", $battle->isChallengerWin());
	}

	//////////////////////////////////////////////
	// ランキングに参加させる。
	function JoinRanking($id, $place = false)
	{
		if (!$place) //最下位に入れる
			$place	= count($this->Ranking);
		$data	= array(array("id" => $id));
		array_splice($this->Ranking, $place, 0, $data);
	}

	//////////////////////////////////////////////////
	// 順位を入れ替える。
	function ChangeRank($id, $id0) {}

	//////////////////////////////////////////////////
	// 順位を上げる。
	function RankUp($id)
	{
		$place	= $this->SearchID($id);
		//1位は無理 あと、ランキングが1つの場合(1位のみ)
		$number	= count($this->Ranking);
		if ($place === 0 || $number < 2)
			return false;

		$temp	= $this->Ranking["$place"];
		$this->Ranking["$place"]	= $this->Ranking["$place" - 1];
		$this->Ranking["$place" - 1]	= $temp;
	}

	//////////////////////////////////////////////////
	// 順位を下げる。
	function RankDown($id)
	{
		$place	= $this->SearchID($id);
		// 最下位は無理 あと、ランキングが1つの場合(1位のみ)
		$number	= count($this->Ranking);
		if ($place === ($number - 1) ||  $number < 2)
			return false;

		$temp	= $this->Ranking["$place"];
		$this->Ranking["$place"]	= $this->Ranking["$place" + 1];
		$this->Ranking["$place" + 1]	= $temp;
	}

	//////////////////////////////////////////////////
	// ランキングから消す
	function DeleteRank($id)
	{
		$place	= $this->SearchID($id);
		if ($place === false) return false; //削除失敗
		unset($this->Ranking["$place"]);
		return true; //削除成功
	}

	//////////////////////////////////////////////////
	// ランキングを保存する
	function SaveRanking()
	{
		foreach ($this->Ranking as $rank => $val) {
			$ranking	.= $val["id"] . "\n";
		}

		WriteFile(RANKING, $ranking);
	}

	//////////////////////////////////////////////////
	// $id を探す
	function SearchID($id)
	{
		foreach ($this->Ranking as $rank => $val) {
			if ($val["id"] == $id)
				return (int)$rank;
		}
		return false;
	}

	//////////////////////////////////////////////////
	// ランキングの表示
	function ShowRanking($from = false, $to = false, $bold = false)
	{
		$last	= count($this->Ranking) - 1;
		// ランキングが存在しない時
		if (count($this->Ranking) < 1) {
			print("<div class=\"bold\">没有排名.</div>\n");
			// 表示する数を指定された時
		} else if (is_numeric($from) && is_numeric($to)) {
			for ($from; $from < $to; $from++) {
				$user	= new user($this->Ranking["$from"]["id"]);
				$place	= ($from == $last ? "位(最下位)" : "位");
				if ($bold === $from) {
					echo ($from + 1) . "{$place} : <span class=\"u\">" . $user->name . "</span><br />";
					continue;
				}
				if ($this->Ranking["$from"])
					echo ($from + 1) . "{$place} : " . $user->name . "<br />";
				//else break;
			}
			// 表示する数を指定されなかった時(全表示)
		} else if (!$no) {
			foreach ($this->Ranking as $key => $val) {
				$user	= new user($val["id"]);
				echo ($key + 1) . "位 : " . $user->name . "<br />";
			}
		}
	}

	//////////////////////////////////////////////////
	// $id周辺のランキングを表示
	function ShowNearlyRank($id, $no = 5)
	{
		//dump($this->Ranking);
		$MyRank	= $this->SearchID($id);
		//print("aaa".$MyRank.":".$id."<br>");
		$lowest	= count($this->Ranking);
		// 最下位に近いので繰り上げて表示
		if ($lowest < ($MyRank + $no)) {
			$moveup	= $no - ($lowest - $MyRank);
			$this->ShowRanking($MyRank - $moveup - 5, $lowest, $MyRank);
			return 0;
		}
		// 上に近いので繰り下げて表示
		if (($MyRank - $no) < 0) {
			$this->ShowRanking(0, $no + 5, $MyRank);
			return 0;
		}
		// 中間
		$this->ShowRanking($MyRank - $no, $MyRank + $no, $MyRank);
	}

	// end of class
}
