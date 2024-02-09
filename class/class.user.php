<?php 
class user {

	// 
	var $fp;
	var $file;

	var $id, $pass;
	var $name, $last, $login ,$start;
	var $money;
	var $char;
	var $time;
	var $wtime;//總消費時間
	var $ip;//IP

	var $party_memo;
	var $party_rank;//用
	var $rank_set_time;//PT設定時間
	var $rank_btl_time;//次戰挑戰時間
	// 成績
	// = "總戰鬥回數<>勝利數<>敗北數<>引分<>首位防衛";
	var $rank_record;
	var $union_btl_time;//次Union戰挑戰時間

	// OPTION
	var $record_btl_log;
	var $no_JS_itemlist;
	var $UserColor;

	// 用變數
	var $fp_item;
	var $item;

//////////////////////////////////////////////////
//	對像ID作成
	function user($id,$noExit=false) {
		if($id)
		{
			$this->id	= $id;
			if($data = $this->LoadData($noExit)) {
				$this->DataUpDate($data);//time增
				$this->SetData($data);
			}
		}
	}
//////////////////////////////////////////////////
//	IP變更
	function SetIp($ip) {
		$this->ip = $ip;
	}
//////////////////////////////////////////////////
//	讀
	function LoadData($noExit=false) {
		$file	= USER.$this->id."/".DATA;
		if(file_exists($file))
		{
			$this->file	= $file;
			$this->fp	= FileLock($file,$noExit);
			if(!$this->fp)
				return false;
			$data	= ParseFileFP($this->fp);
			//$data	= ParseFile($file);// (2007/7/30 追加)
			/*
			$Array	= array("party_memo","party_rank");
			foreach($Array as $val)
			{
				if(!$data["$val"]) continue;
				$data["$val"]	= explode("<>",$data["$val"]);
			}
			*/
			return $data;
		}
			else
		{
			return false;
		}
	}

//////////////////////////////////////////////////
//	ID結局存在
	function is_exist() {
		if($this->name)
			return true;
		else
			return false;
	}
//////////////////////////////////////////////////
//	名前返
	function Name($opt=false) {
		if($this->name) {
			if($opt)
				return '<span class="'.$opt.'">'.$this->name.'</span>';
			else
				return $this->name;
		} else {
			return false;
		}
	}
//////////////////////////////////////////////////
//	名前變
	function ChangeName($name) {

		if($this->name == $name)
			return false;

		$this->name	= $name;
		return true;
	}
//////////////////////////////////////////////////
//	Union戰鬥時間
	function UnionSetTime() {
		$this->union_btl_time	= time();
	}
//////////////////////////////////////////////////
//	UnionBattle確認。
	function CanUnionBattle() {
		$Now	= time();
		$Past	= $this->union_btl_time	+ UNION_BATTLE_NEXT;
		if($Past <= $Now) {
			return true;
		} else {
			return abs($Now - $Past);
		}
	}
//////////////////////////////////////////////////
//	戰用編成返
	function RankParty() {
		if(!$this->name)
			return "NOID";//超。存在場合。
		if(!$this->party_rank)
			return false;

		$PartyRank	= explode("<>",$this->party_rank);
		foreach($PartyRank as $no) {
			$char	= $this->CharDataLoad($no);
			if($char)
				$party[]	= $char;
			//if($this->char[$no])
			//	$party[]	= $this->char[$no];
		}

		if($party)
			return $party;
		else
			return false;
	}
//////////////////////////////////////////////////
//	成績
// side = ("CHALLENGE","DEFEND")
	function RankRecord($result,$side,$DefendMatch) {
		$record	= $this->RankRecordLoad();

		$record["all"]++;
		switch(true) {
			// 引分
			/*
			case ($result === "d"):
				if($side != "CHALLENGE" && $DefendMatch)
					$record["defend"]++;
				break;
			*/
			// 戰鬥結果挑戰者勝
			case ($result === 0):
				if($side == "CHALLENGER") {
					$record["win"]++;
				} else {
					$record["lose"]++;
				}
				break;
			// 戰鬥結果挑戰者負
			case ($result === 1):
				if($side == "CHALLENGER") {
					$record["lose"]++;
				} else {
					$record["win"]++;
					if($DefendMatch)
						$record["defend"]++;
				}
				break;
			default:// 引分
				if($side != "CHALLENGER" && $DefendMatch)
					$record["defend"]++;
				break;
		}

		$this->rank_record	= $record["all"]."|".$record["win"]."|".$record["lose"]."|".$record["defend"];
	}
//////////////////////////////////////////////////
//	戰成績呼出
	function RankRecordLoad() {

		if(!$this->rank_record) {
			$record	= array(
						"all" => 0,
						"win" => 0,
						"lose" => 0,
						"defend" => 0,
						);
			return $record;
		}

		list(
			$record["all"],
			$record["win"],
			$record["lose"],
			$record["defend"],
		)	= explode("|",$this->rank_record);
		return $record;
	}
//////////////////////////////////////////////////
//	次戰挑戰時間記錄。
	function SetRankBattleTime($time) {
		$this->rank_btl_time	= $time;
	}

//////////////////////////////////////////////////
//	挑戰？(無理殘時間返)
	function CanRankBattle() {
		$now	= time();
		if($this->rank_btl_time <= $now) {
			return true;
		} else if(!$this->rank_btl_time) {
			return true;
		} else {
			$left	= $this->rank_btl_time - $now;
			$hour		= floor($left/3600);
			$minutes	= floor(($left%3600)/60);
			$seconds	= floor(($left%3600)%60);
			return array($hour,$minutes,$seconds);
		}
	}

//////////////////////////////////////////////////
//	金增
	function GetMoney($no) {
		$this->money	+= $no;
	}

//////////////////////////////////////////////////
//	金減
	function TakeMoney($no) {
		if($this->money < $no) {
			return false;
		} else {
			$this->money	-= $no;
			return true;
		}
	}

//////////////////////////////////////////////////
//	時間消費(總消費時間加算)
	function WasteTime($time) {
		if($this->time < $time)
			return false;
		$this->time		-= $time;
		$this->wtime 	+= $time;
		return true;
	}
//////////////////////////////////////////////////
//	所持數。
	function CharCount() {
		$dir	= USER.$this->id;
		$no		= 0;
		foreach(glob("$dir/*") as $adr) {
			$number	= basename($adr,".dat");
			if(is_numeric($number)) {//
				$no++;
			}
		}
		return $no;
	}
//////////////////////////////////////////////////
//	全所持讀 $this->char 格納
	function CharDataLoadAll() {
		$dir	= USER.$this->id;
		$this->char	= array();//配列初期化
		foreach(glob("$dir/*") as $adr) {
			//print("substr:".substr($adr,-20,16)."<br>");//確認用
			//$number	= substr($adr,-20,16);//↓1行同結果
			$number	= basename($adr,".dat");
			if(is_numeric($number)) {//
				//$chardata	= ParseFile($adr);// (2007/7/30 $adr -> $fp)
				//$this->char[$number]	= new char($chardata);
				$this->char[$number]	= new char($adr);
				$this->char[$number]->SetUser($this->id);//誰設定
			}
		}
	}
//////////////////////////////////////////////////
//	指定所持讀 $this->char 格納後 "返"。
	function CharDataLoad($CharNo) {
		// 既讀場合。
		if($this->char[$CharNo])
			return $this->char[$CharNo];
		// 讀無場合。
		$file	= USER.$this->id."/".$CharNo.".dat";
		// 場合。
		if(!file_exists($file))
			return false;

		// 居場合。
		//$chardata	= ParseFile($file);
		//$this->char[$CharNo]	= new char($chardata);
		$this->char[$CharNo]	= new char($file);
		$this->char[$CharNo]->SetUser($this->id);//誰設定
		return $this->char[$CharNo];
	}
//////////////////////////////////////////////////
//	追加
	function AddItem($no,$amount=false) {
		if(!isset($this->item))//…
			$this->LoadUserItem();
		if($amount)
			$this->item[$no]	+= $amount;
		else
			$this->item[$no]++;
	}

//////////////////////////////////////////////////
//	削除
	function DeleteItem($no,$amount=false) {
		if(!isset($this->item))//…
			$this->LoadUserItem();

		// 減數。
		if($this->item[$no] < $amount) {
			$amount	= $this->item[$no];
			if(!$amount)
				$amount = 0;
		}
		if(!is_numeric($amount))
			$amount	= 1;

		// 減。
		$this->item[$no]	-= $amount;
		if($this->item[$no] < 1)
			unset($this->item[$no]);

		return $amount;
	}

//////////////////////////////////////////////////
//	讀
	function LoadUserItem() {

		// 2重讀防止。
		if(isset($this->item))
			return false;

		$file	= USER.$this->id."/".ITEM;

		if(file_exists($file)) {
			$this->fp_item	= FileLock($file);
			$this->item	= ParseFileFP($this->fp_item);
			if($this->item === false)
				$this->item	= array();
		} else {
			$this->item	= array();
		}
	}

//////////////////////////////////////////////////
//	保存
	function SaveUserItem() {
		$dir	= USER.$this->id;
		if(!file_exists($dir))
			return false;

		$file	= USER.$this->id."/".ITEM;

		if(!is_array($this->item))
			return false;

		// 
		ksort($this->item,SORT_STRING);

		foreach($this->item as $key => $val) {
			$text	.= "$key=$val\n";
		}

		if(file_exists($file) && $this->fp_item) {
			WriteFileFP($this->fp_item,$text,1);//$text空保存
			fclose($this->fp_item);
			unset($this->fp_item);
		} else {
			// $text空保存
			WriteFile($file,$text,1);
		}
	}

//////////////////////////////////////////////////
//	時間經過。(Time增加)
	function DataUpDate(&$data) {
		$now	= time();
		$diff	= $now - $data["last"];
		$data["last"]	= $now;
		$gain	= $diff / (24*60*60) * TIME_GAIN_DAY;
		$data["time"]	+= $gain;
		if(MAX_TIME < $data["time"])
			$data["time"]	= MAX_TIME;
	}

//////////////////////////////////////////////////
//	。
//	※?
	function SetData(&$data) {

		foreach($data as $key => $val) {
			$this->{$key}	= $val;
		}
		/*
		$this->name	= $data["name"];
		$this->login	= $data["login"];
		$this->last	= $data["last"];
		$this->start	= $data["start"];
		*/
	}

//////////////////////////////////////////////////
//	暗號化
	function CryptPassword($pass) {
		return substr(crypt($pass,CRYPT_KEY),strlen(CRYPT_KEY));
	}

//////////////////////////////////////////////////
//	名前消
	function DeleteName() {
		$this->name	= NULL;
	}

//////////////////////////////////////////////////
//	保存形式變換。()
	function DataSavingFormat() {

		$Save	= array(
			"id",
			"pass",
			"ip",
			"name",
			"last",
			"login",
			"start",
			"money",
			"time",
			"wtime",
			"party_memo",
			"party_rank",
			"rank_set_time",
			"rank_btl_time",
			"rank_record",
			"union_btl_time",
			// opt
			"record_btl_log",
			"no_JS_itemlist",
			"UserColor",
			);
		foreach($Save as $val) {
			if($this->{$val})
				$text	.= "$val=".(is_array($this->{$val}) ? implode("<>",$this->{$val}) : $this->{$val})."\n";
		}
		

		/*
		$Save	= get_object_vars($this);
		unset($Save["char"]);
		unset($Save["item"]);
		unset($Save["islogin"]);
		foreach($Save as $key => $val) {
			$text	.= "$key=".(is_array($val) ? implode("<>",$val) : $val)."\n";
		}
		*/

		//print("<pre>".print_r($AAA,1)."</pre>");

		return $text;
	}

//////////////////////////////////////////////////
//	保存
	function SaveData() {
		$dir	= USER.$this->id;
		$file	= USER.$this->id."/".DATA;

		if(file_exists($this->file) && $this->fp) {
			//print("BBB");
			//ftruncate($this->fp,0);
			//rewind($this->fp);
			//$fp	= fopen($file,"w+");
			//flock($fp,LOCK_EX);
			//fputs($this->fp,$this->DataSavingFormat());
			WriteFileFP($this->fp,$this->DataSavingFormat());
			fclose($this->fp);
			unset($this->fp);
			//WriteFile("./user/1234/data2.dat",$this->DataSavingFormat());
			//WriteFile($file,$this->DataSavingFormat());
			//WriteFileFP($this->fp,$this->DataSavingFormat());
			//fclose($this->fp);
		} else {
			if(file_exists($file))
				WriteFile($file,$this->DataSavingFormat());
		}
	}
/////////////////////////////////////////////////
//	兼全部閉
	function fpCloseAll() {
		// 基本
		if(is_resource($this->fp))
		{
			fclose($this->fp);
			unset($this->fp);
		}

		// 
		if(is_resource($this->fp_item))
		{
			fclose($this->fp_item);
			unset($this->fp_item);
		}

		// 
		if($this->char)
		{
			foreach($this->char as $key => $var)
			{
				if(method_exists($this->char[$key],"fpclose"))
					$this->char[$key]->fpclose();
			}
		}

	}
//////////////////////////////////////////////////
//	削除(全)
	function DeleteUser($DeleteFromRank=true) {
		//消。
		if($DeleteFromRank) {
			include_once(CLASS_RANKING);
			$Ranking	= new Ranking();
			if( $Ranking->DeleteRank($this->id) )
				$Ranking->SaveRanking();
		}

		$dir	= USER.$this->id;
		$files	= glob("$dir/*");
		$this->fpCloseAll();
		foreach($files as $val)
			unlink($val);
		rmdir($dir);
	}
//////////////////////////////////////////////////
//	放棄確
	function IsAbandoned() {
		$now	= time();
		// $this->login 終了。
		if(strlen($this->login) !== 10) {
			return false;
		}
		if( ($this->login + ABANDONED) < $now) {
			return true;
		} else {
			return false;
		}
	}
//////////////////////////////////////////////////
//	消
	function DeleteChar($no) {
		$file	= USER.$this->id."/".$no.".dat";
		if($this->char[$no]) {
			$this->char[$no]->fpclose();
		}
		if(file_exists($file))
			unlink($file);
	}

//////////////////////////////////////////////////
//	
	//function Load

}
?>
