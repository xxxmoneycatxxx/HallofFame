<?php

/**
 * 游戏用户管理系统类
 * 
 * 功能说明：
 * 1. 实现游戏用户账户的全面管理
 * 2. 处理用户角色、物品、战斗记录等核心数据
 * 3. 管理用户登录状态、时间消耗和经济系统
 * 
 * 主要功能模块：
 * 1. 账户管理：
 *    - 用户登录状态维护
 *    - 密码加密与验证
 *    - 账户创建与删除
 * 2. 角色管理：
 *    - 角色数据加载与保存
 *    - 角色数量统计
 *    - 角色删除与状态维护
 * 3. 物品系统：
 *    - 物品添加与删除
 *    - 物品数据持久化存储
 *    - 物品排序与整理
 * 4. 战斗系统：
 *    - 联盟战斗时间管理
 *    - 排名战斗记录
 *    - 战斗冷却机制
 * 5. 经济系统：
 *    - 游戏货币管理
 *    - 时间资源消耗
 * 
 * 技术特点：
 * 1. 文件存储系统：
 *    - 使用文件系统存储用户数据
 *    - 文件锁定保证数据一致性
 *    - 结构化数据解析与保存
 * 2. 时间管理：
 *    - 自动计算时间资源增长
 *    - 战斗冷却时间控制
 *    - 闲置账户检测
 * 3. 安全机制：
 *    - 密码加密存储
 *    - IP地址记录
 *    - 数据完整性校验
 * 
 * 特殊机制：
 * 1. 排名战斗系统：
 *    - 胜负记录统计
 *    - 防守成功次数记录
 *    - 战斗冷却限制
 * 2. 联盟战斗：
 *    - 独立冷却计时
 *    - 特殊战斗规则
 * 3. 闲置处理：
 *    - 自动检测长期未登录账户
 *    - 账户清理机制
 * 
 * 使用注意事项：
 * 1. 文件路径：
 *    - 用户数据存储在USER常量指定目录
 *    - 每个用户有独立数据文件夹
 * 2. 资源管理：
 *    - 时间资源自动增长(每天TIME_GAIN_DAY)
 *    - 最大时间上限由MAX_TIME控制
 * 3. 数据安全：
 *    - 密码使用CRYPT_KEY加密
 *    - 重要操作需要文件锁定
 * 
 * 使用流程：
 * 1. 初始化user对象(传入用户ID)
 * 2. 加载用户数据(LoadData)
 * 3. 执行业务操作(如角色管理、物品操作)
 * 4. 调用SaveData保存数据
 * 5. 使用fpCloseAll释放资源
 */

class user
{

	// ファイルポインタ
	var $fp;
	var $file;

	var $id, $pass;
	var $name, $last, $login, $start;
	var $money;
	var $char;
	var $time;
	var $wtime; //総消費時間
	var $ip; //IPアドレス

	var $party_memo;
	var $party_rank; //ランキング用のパーティ
	var $rank_set_time; //ランキングPT設定した時間
	var $rank_btl_time; //次のランク戦に挑戦できる時間
	// ランキングの成績
	// = "総戦闘回数<>勝利数<>敗北数<>引き分け<>首位防衛";
	var $rank_record;
	var $union_btl_time; //次のUnion戦に挑戦できる時間

	// OPTION
	var $record_btl_log;
	var $no_JS_itemlist;
	var $UserColor;

	// ユーザーアイテム用の変数
	var $fp_item;
	var $item;

	//////////////////////////////////////////////////
	//	対象のIDのユーザークラスを作成
	function __construct($id, $noExit = false)
	{
		if ($id) {
			$this->id	= $id;
			if ($data = $this->LoadData($noExit)) {
				$this->DataUpDate($data); //timeとか増やす
				$this->SetData($data);
			}
		}
	}
	//////////////////////////////////////////////////
	//	IPを変更
	function SetIp($ip)
	{
		$this->ip = $ip;
	}
	//////////////////////////////////////////////////
	//	ユーザデータを読む
	function LoadData($noExit = false)
	{
		$file	= USER . $this->id . "/" . DATA;
		if (file_exists($file)) {
			$this->file	= $file;
			$this->fp	= FileLock($file, $noExit);
			if (!$this->fp)
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
		} else {
			return false;
		}
	}
	//////////////////////////////////////////////////
	//	IDが結局のところ存在しているかたしかめる
	function is_exist()
	{
		if ($this->name)
			return true;
		else
			return false;
	}
	//////////////////////////////////////////////////
	//	名前を返す
	function Name($opt = false)
	{
		if ($this->name) {
			if ($opt)
				return '<span class="' . $opt . '">' . $this->name . '</span>';
			else
				return $this->name;
		} else {
			return false;
		}
	}
	//////////////////////////////////////////////////
	//	名前を変える
	function ChangeName($name)
	{

		if ($this->name == $name)
			return false;

		$this->name	= $name;
		return true;
	}
	//////////////////////////////////////////////////
	//	Union戦闘した時間をセット
	function UnionSetTime()
	{
		$this->union_btl_time	= time();
	}
	//////////////////////////////////////////////////
	//	UnionBattleができるかどうか確認する。
	function CanUnionBattle()
	{
		$Now	= time();
		$Past	= $this->union_btl_time	+ UNION_BATTLE_NEXT;
		if ($Past <= $Now) {
			return true;
		} else {
			return abs($Now - $Past);
		}
	}
	//////////////////////////////////////////////////
	//	ランキング戦用のパーティ編成を返す
	function RankParty()
	{
		if (!$this->name)
			return "NOID"; //超エラー。そもそもユーザーが存在しない場合。
		if (!$this->party_rank)
			return false;

		$PartyRank	= explode("<>", $this->party_rank);
		foreach ($PartyRank as $no) {
			$char	= $this->CharDataLoad($no);
			if ($char)
				$party[]	= $char;
			//if($this->char[$no])
			//	$party[]	= $this->char[$no];
		}

		if ($party)
			return $party;
		else
			return false;
	}
	//////////////////////////////////////////////////
	//	ランキングの成績
	// side = ("CHALLENGE","DEFEND")
	function RankRecord($result, $side, $DefendMatch)
	{
		$record	= $this->RankRecordLoad();

		$record["all"]++;
		switch (true) {
			// 引き分け
			/*
			case ($result === "d"):
				if($side != "CHALLENGE" && $DefendMatch)
					$record["defend"]++;
				break;
			*/
			// 戦闘結果が挑戦者の勝ち
			case ($result === 0):
				if ($side == "CHALLENGER") {
					$record["win"]++;
				} else {
					$record["lose"]++;
				}
				break;
			// 戦闘結果が挑戦者の負け
			case ($result === 1):
				if ($side == "CHALLENGER") {
					$record["lose"]++;
				} else {
					$record["win"]++;
					if ($DefendMatch)
						$record["defend"]++;
				}
				break;
			default: // 引き分け
				if ($side != "CHALLENGER" && $DefendMatch)
					$record["defend"]++;
				break;
		}

		$this->rank_record	= $record["all"] . "|" . $record["win"] . "|" . $record["lose"] . "|" . $record["defend"];
	}
	//////////////////////////////////////////////////
	//	ランキング戦の成績を呼び出す
	function RankRecordLoad()
	{

		if (!$this->rank_record) {
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
		)	= explode("|", $this->rank_record);
		return $record;
	}
	//////////////////////////////////////////////////
	//	次のランク戦に挑戦できる時間を記録する。
	function SetRankBattleTime($time)
	{
		$this->rank_btl_time	= $time;
	}

	//////////////////////////////////////////////////
	//	ランキング挑戦できるか？(無理なら残り時間を返す)
	function CanRankBattle()
	{
		$now	= time();
		if ($this->rank_btl_time <= $now) {
			return true;
		} else if (!$this->rank_btl_time) {
			return true;
		} else {
			$left	= $this->rank_btl_time - $now;
			$hour		= floor($left / 3600);
			$minutes	= floor(($left % 3600) / 60);
			$seconds	= floor(($left % 3600) % 60);
			return array($hour, $minutes, $seconds);
		}
	}
	//////////////////////////////////////////////////
	//	お金を増やす
	function GetMoney($no)
	{
		$this->money	+= $no;
	}
	//////////////////////////////////////////////////
	//	お金を減らす
	function TakeMoney($no)
	{
		if ($this->money < $no) {
			return false;
		} else {
			$this->money	-= $no;
			return true;
		}
	}
	//////////////////////////////////////////////////
	//	時間を消費する(総消費時間の加算)
	function WasteTime($time)
	{
		if ($this->time < $time)
			return false;
		$this->time		-= $time;
		$this->wtime 	+= $time;
		return true;
	}
	//////////////////////////////////////////////////
	//	キャラクターを所持してる数をかぞえる。
	function CharCount()
	{
		$dir	= USER . $this->id;
		$no		= 0;
		foreach (glob("$dir/*") as $adr) {
			$number	= basename($adr, ".dat");
			if (is_numeric($number)) { //キャラデータファイル
				$no++;
			}
		}
		return $no;
	}
	//////////////////////////////////////////////////
	//	全所持キャラクターをファイルから読んで $this->char に格納
	function CharDataLoadAll()
	{
		$dir	= USER . $this->id;
		$this->char	= array(); //配列の初期化だけしておく
		foreach (glob("$dir/*") as $adr) {
			$number	= basename($adr, ".dat");
			if (is_numeric($number)) { //キャラデータファイ
				$this->char[$number]	= new char($adr);
				$this->char[$number]->SetUser($this->id); //キャラが誰のか設定する
			}
		}
	}
	//////////////////////////////////////////////////
	//	指定の所持キャラクターをファイルから読んで $this->char に格納後 "返す"。
	function CharDataLoad($CharNo)
	{
		// 既に読んでる場合。
		if ($this->char[$CharNo])
			return $this->char[$CharNo];
		// 読んで無い場合。
		$file	= USER . $this->id . "/" . $CharNo . ".dat";
		// そんなキャラいない場合。
		if (!file_exists($file))
			return false;
		$this->char[$CharNo]	= new char($file);
		$this->char[$CharNo]->SetUser($this->id); //キャラが誰のか設定する
		return $this->char[$CharNo];
	}
	//////////////////////////////////////////////////
	//	アイテムを追加
	function AddItem($no, $amount = false)
	{
		if (!isset($this->item)) //どうしたもんか…
			$this->LoadUserItem();
		if ($amount)
			$this->item[$no]	+= $amount;
		else
			$this->item[$no]++;
	}
	//////////////////////////////////////////////////
	//	アイテムを削除
	function DeleteItem($no, $amount = false)
	{
		if (!isset($this->item)) //どうしたもんか…
			$this->LoadUserItem();

		// 減らす数。
		if ($this->item[$no] < $amount) {
			$amount	= $this->item[$no];
			if (!$amount)
				$amount = 0;
		}
		if (!is_numeric($amount))
			$amount	= 1;

		// 減らす。
		$this->item[$no]	-= $amount;
		if ($this->item[$no] < 1)
			unset($this->item[$no]);

		return $amount;
	}
	//////////////////////////////////////////////////
	//	アイテムデータを読む
	function LoadUserItem()
	{

		// 2重に読むのを防止。
		if (isset($this->item))
			return false;

		$file	= USER . $this->id . "/" . ITEM;

		if (file_exists($file)) {
			$this->fp_item	= FileLock($file);
			$this->item	= ParseFileFP($this->fp_item);
			if ($this->item === false)
				$this->item	= array();
		} else {
			$this->item	= array();
		}
	}
	//////////////////////////////////////////////////
	//	アイテムデータを保存する
	function SaveUserItem()
	{
		$dir	= USER . $this->id;
		if (!file_exists($dir))
			return false;

		$file	= USER . $this->id . "/" . ITEM;

		if (!is_array($this->item))
			return false;

		// アイテムのソート
		ksort($this->item, SORT_STRING);

		foreach ($this->item as $key => $val) {
			$text	.= "$key=$val\n";
		}

		if (file_exists($file) && $this->fp_item) {
			WriteFileFP($this->fp_item, $text, 1); //$textが空でも保存する
			fclose($this->fp_item);
			unset($this->fp_item);
		} else {
			// $textが空でも保存する
			WriteFile($file, $text, 1);
		}
	}
	//////////////////////////////////////////////////
	//	時間を経過させる。(Timeの増加)
	function DataUpDate(&$data)
	{
		$now	= time();
		$diff	= $now - $data["last"];
		$data["last"]	= $now;
		$gain	= $diff / (24 * 60 * 60) * TIME_GAIN_DAY;
		$data["time"]	+= $gain;
		if (MAX_TIME < $data["time"])
			$data["time"]	= MAX_TIME;
	}
	//////////////////////////////////////////////////
	//	データをセットする。
	//	※?
	function SetData(&$data)
	{
		foreach ($data as $key => $val) {
			$this->{$key}	= $val;
		}
	}
	//////////////////////////////////////////////////
	//	パスワードを暗号化する
	function CryptPassword($pass)
	{
		return substr(crypt($pass, CRYPT_KEY), strlen(CRYPT_KEY));
	}
	//////////////////////////////////////////////////
	//	名前を消す
	function DeleteName()
	{
		$this->name	= NULL;
	}
	//////////////////////////////////////////////////
	//	データを保存する形式に変換する。(テキスト)
	function DataSavingFormat()
	{

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
		foreach ($Save as $val) {
			if ($this->{$val})
				$text	.= "$val=" . (is_array($this->{$val}) ? implode("<>", $this->{$val}) : $this->{$val}) . "\n";
		}
		return $text;
	}
	//////////////////////////////////////////////////
	//	データを保存する
	function SaveData()
	{
		$dir	= USER . $this->id;
		$file	= USER . $this->id . "/" . DATA;

		if (file_exists($this->file) && $this->fp) {
			WriteFileFP($this->fp, $this->DataSavingFormat());
			fclose($this->fp);
			unset($this->fp);
		} else {
			if (file_exists($file))
				WriteFile($file, $this->DataSavingFormat());
		}
	}
	/////////////////////////////////////////////////
	//	データファイル兼キャラファイルのファイルポインタも全部閉じる
	function fpCloseAll()
	{
		// 基本データ
		if (is_resource($this->fp)) {
			fclose($this->fp);
			unset($this->fp);
		}

		// アイテムデータ
		if (is_resource($this->fp_item)) {
			fclose($this->fp_item);
			unset($this->fp_item);
		}

		// キャラデータ
		if ($this->char) {
			foreach ($this->char as $key => $var) {
				if (method_exists($this->char[$key], "fpclose"))
					$this->char[$key]->fpclose();
			}
		}
	}
	//////////////////////////////////////////////////
	//	ユーザーの削除(全ファイル)
	function DeleteUser($DeleteFromRank = true)
	{
		//ランキングからまず消す。
		if ($DeleteFromRank) {
			include_once(CLASS_RANKING);
			$Ranking	= new Ranking();
			if ($Ranking->DeleteRank($this->id))
				$Ranking->SaveRanking();
		}

		$dir	= USER . $this->id;
		$files	= glob("$dir/*");
		$this->fpCloseAll();
		foreach ($files as $val)
			unlink($val);
		rmdir($dir);
	}
	//////////////////////////////////////////////////
	//	放棄されているかどうか確かめる
	function IsAbandoned()
	{
		$now	= time();
		// $this->login がおかしければ終了する。
		if (strlen($this->login) !== 10) {
			return false;
		}
		if (($this->login + ABANDONED) < $now) {
			return true;
		} else {
			return false;
		}
	}
	//////////////////////////////////////////////////
	//	キャラデータを消す
	function DeleteChar($no)
	{
		$file	= USER . $this->id . "/" . $no . ".dat";
		if ($this->char[$no]) {
			$this->char[$no]->fpclose();
		}
		if (file_exists($file))
			unlink($file);
	}
}
