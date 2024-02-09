<?php 
class Ranking {
/*
處理手順(戰)
1. 挑戰者ID渡
2.
	1位人。
		戰鬥。
	2-最下位人。
		1個上人探。
	外人。
		最下位人探。
3. 自分相手戰鬥
4. 勝利者、敗者順位變動
5. 保存。
----------------------------
怖、怖
起全(?)事象。
◎|1位居時(自體無)挑戰者1位。
◎|1位挑戰。
◎|正常2位-最下位者上挑戰勝。
◎|正常2位-最下位者上挑戰負。
◎|正常2位-最下位者上挑戰1位。
△|登錄無者挑戰。
○|登錄、參加者挑戰。
◎|挑戰相手(數名欠)。
◎|挑戰相手(全員欠)。
○|挑戰相手ID自體消。
○|ID消消滅。
△|時間制限場合挑戰。
◎|相手時間制限中(→無關係)
*/

	var $Ranking	= array();

//////////////////////////////////////////////
// 讀迂配列
	function Ranking() {
		$file	= RANKING;

		if(!file_exists($file)) return 0;

		// 讀配列
		$fp	= fopen($file,"r");
		flock($fp,LOCK_EX);
		while($line = fgets($fp) ) {
			$line	= trim($line);
			if(trim($line) == "") continue;
				$this->Ranking[]	= $line;
		}
		//$this->Ranking	= file($file);
		// 配列0終了
		if(!$this->Ranking) return 0;
		// 區切文字列分割
		foreach($this->Ranking as $rank => $val) {
			$list	= explode("<>", $val);
			$this->Ranking["$rank"]	= array();
			$this->Ranking["$rank"]["id"]	= $list["0"];
		}
		//$this->JoinRanking("yqyqqq","last");
		//dump($this->Ranking);
	}

//////////////////////////////////////////////
// 戰。戰。
	function Challenge($id) {
		// 無(1位)
		if(!$this->Ranking) {
			$this->JoinRanking($id);
			$this->SaveRanking();
			$message	= "排名開始."; 
			return array($message,true);
		}

		$MyRank	= $this->SearchID($id);//自分順位
		// 1位場合。
		if($MyRank === 0) {
			$message	= "第一名不可再挑戰.";
			return array($message,true);
		}

		// 自分外
		if(!$MyRank) {
			$this->JoinRanking($id);//自分最下位。
			$MyRank	= count($this->Ranking) - 1;//自分(最下位)

			$MyID	= $this->Ranking["$MyRank"]["id"];
			$RivalID= $this->Ranking["$MyRank" - 1]["id"];//自分1個上人相手。
			/*
			dump($this->Ranking);
			dump($RivalID);
			dump($MyID);
			dump($MyRank);//頑張
			return 0;*/
			list($message,$result)	= $this->RankBattle($MyID,$RivalID);
			if($message == "Battle" && $result === true)
				$this->RankUp($MyID);

			$this->SaveRanking();
			return array($message,$result);
		}

		// 2位-最下位人處理。
		if($MyRank) {
			$rival	= $MyRank - 1;//自分順位1個上人。

			$MyID	= $this->Ranking["$MyRank"]["id"];
			$RivalID= $this->Ranking["$rival"]["id"];
			list($message,$result)	= $this->RankBattle($MyID,$RivalID);
			if($message != "Battle")
				return array($message,$result);

			// 戰鬥行trueｐ
			if($message == "Battle" && $result === true) {
				$this->RankUp($MyID);
				$this->SaveRanking();
			}
			return array($message,$result);
		}
	}

//////////////////////////////////////////////
// 戰
	function RankBattle($ChallengerID,$DefendID) {
		$challenger	= new user($ChallengerID);
		$challenger->CharDataLoadAll();
		$defender	= new user($DefendID);
		$defender->CharDataLoadAll();
		//print($ChallengerID."<br>".$DefendID."<br>");

		$Party_Challenger	= $challenger->RankParty();
		$Party_Defender		= $defender->RankParty();
		if($Party_Defender == "NOID") {//自體既存在場合
			$message	= "沒有用戶...<br />(自動勝利)";
			$this->DeleteRank($DefendID);
			$this->SaveRanking();
			return array($message,true);
		}

		// 返值
		// array(,戰鬥,勝敗)

		// 用！！！
		if($Party_Challenger === false) {
			$message	= "設置戰鬥隊伍!<br />(如果被挑下馬的話排名也就沒了)";
			return array($message,true);
		}
		// 用！！！
		if($Party_Defender === false) {
			$this->DeleteRank($DefendID);
			$this->SaveRanking();
			$message	= "{$defender->name} 沒有排名戰隊伍<br />(自動勝利)";
			return array($message,true);
		}

		//dump($Party_Challenger);
		//dump($Party_Defender);
		include(CLASS_BATTLE);
		$battle	= new battle($Party_Challenger,$Party_Defender);
		$battle->SetBackGround("colosseum");
		$battle->SetTeamName($challenger->name,$defender->name);
		$battle->Process();//戰鬥開始
		$battle->RecordLog("RANK");
		return array("Battle",$battle->isChallengerWin());
	}

//////////////////////////////////////////////
// 參加。
	function JoinRanking($id,$place=false) {
		if(!$place)//最下位入
			$place	= count($this->Ranking);
		$data	= array(array("id"=>$id));
		array_splice($this->Ranking, $place, 0, $data);
	}

//////////////////////////////////////////////////
// 順位入替。
	function ChangeRank($id,$id0) {
	
	}

//////////////////////////////////////////////////
// 順位上。
	function RankUp($id) {
		$place	= $this->SearchID($id);
		//1位無理 、1場合(1位)
		$number	= count($this->Ranking);
		if($place === 0 || $number < 2)
			return false;

		$temp	= $this->Ranking["$place"];
		$this->Ranking["$place"]	= $this->Ranking["$place"-1];
		$this->Ranking["$place"-1]	= $temp;
	}

//////////////////////////////////////////////////
// 順位下。
	function RankDown($id) {
		$place	= $this->SearchID($id);
		// 最下位無理 、1場合(1位)
		$number	= count($this->Ranking);
		if($place === ($number - 1) ||  $number < 2)
			return false;

		$temp	= $this->Ranking["$place"];
		$this->Ranking["$place"]	= $this->Ranking["$place"+1];
		$this->Ranking["$place"+1]	= $temp;
	}

//////////////////////////////////////////////////
// 消
	function DeleteRank($id) {
		$place	= $this->SearchID($id);
		if($place === false) return false;//削除失敗
		unset($this->Ranking["$place"]);
		return true;//削除成功
	}

//////////////////////////////////////////////////
// 保存
	function SaveRanking() {
		foreach($this->Ranking as $rank => $val) {
			$ranking	.= $val["id"]."\n";
		}

		WriteFile(RANKING,$ranking);
	}

//////////////////////////////////////////////////
// $id 探
	function SearchID($id) {
		foreach($this->Ranking as $rank => $val) {
			if($val["id"] == $id)
				return (int)$rank;
		}
		return false;
	}

//////////////////////////////////////////////////
// 表示
	function ShowRanking($from=false,$to=false,$bold=false) {
		$last	= count($this->Ranking) - 1;
		// 存在時
		if(count($this->Ranking) < 1) {
			print("<div class=\"bold\">沒有排名.</div>\n");
		// 表示數指定時
		} else if(is_numeric($from) && is_numeric($to)) {
			for($from; $from<$to; $from++) {
				$user	= new user($this->Ranking["$from"]["id"]);
				$place	= ($from==$last?"位(最下位)":"位");
				if($bold === $from) {
					echo ($from+1)."{$place} : <span class=\"u\">".$user->name."</span><br />";
					continue;
				}
				if($this->Ranking["$from"])
					echo ($from+1)."{$place} : ".$user->name."<br />";
				//else break;
			}
		// 表示數指定時(全表示)
		} else if(!$no) {
			foreach($this->Ranking as $key => $val) {
				$user	= new user($val["id"]);
				echo ($key+1)."位 : ".$user->name."<br />";
			}
		}
	}

//////////////////////////////////////////////////
// $id周邊表示
	function ShowNearlyRank($id,$no=5) {
		//dump($this->Ranking);
		$MyRank	= $this->SearchID($id);
		//print("aaa".$MyRank.":".$id."<br>");
		$lowest	= count($this->Ranking);
		// 最下位近繰上表示
		if( $lowest < ($MyRank+$no) ) {
			$moveup	= $no - ($lowest - $MyRank);
			$this->ShowRanking($MyRank-$moveup-5,$lowest,$MyRank);
			return 0;
		}
		// 上近繰下表示
		if( ($MyRank-$no) < 0 ) {
			$this->ShowRanking(0,$no+5,$MyRank);
			return 0;
		}
		// 中間
		$this->ShowRanking($MyRank-$no,$MyRank+$no,$MyRank);
	}

// end of class
}
?>
