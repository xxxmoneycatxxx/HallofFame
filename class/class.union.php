<?php

/**
 * BOSS怪物系统类
 * 
 * 功能说明：
 * 1. 实现游戏中联盟怪物的特殊行为和状态管理
 * 2. 处理联盟怪物的战斗机制、状态变化和属性调整
 * 3. 管理联盟怪物的数据持久化和特殊机制
 * 
 * 主要功能模块：
 * 1. 状态管理：
 *    - 中毒伤害计算与处理
 *    - 死亡判定与复活机制
 *    - 状态恢复与净化
 * 2. 战斗系统：
 *    - 行动延迟机制
 *    - 属性调整（HP/SP上限、攻防属性）
 *    - 战斗变量初始化
 * 3. 数据管理：
 *    - 怪物数据加载与保存
 *    - 经验值计算与分配
 *    - 联盟专属属性管理
 * 
 * 技术特点：
 * 1. 继承扩展：
 *    - 继承基础角色类(char)并扩展联盟专属功能
 * 2. 特殊机制：
 *    - 周期性复活系统
 *    - 基于伤害的经验值分配
 *    - 属性调整的百分比计算
 * 3. 数据持久化：
 *    - 使用文件存储联盟怪物状态
 *    - 文件锁定保证数据一致性
 * 
 * 特殊机制：
 * 1. 中毒系统：
 *    - 基于当前HP的百分比伤害
 *    - 随机伤害浮动
 * 2. 复活机制：
 *    - 死亡后根据设定周期自动复活
 *    - 复活时恢复满HP/SP
 * 3. 行动延迟：
 *    - 根据速度属性调整行动顺序
 *    - 支持两种延迟计算模式
 * 
 * 联盟专属属性：
 * 1. UnionLand: 联盟所属领地
 * 2. LevelLimit: 挑战等级限制
 * 3. Slave: 附属怪物信息
 * 4. LastDefeated: 最后被击败时间
 * 
 * 使用注意事项：
 * 1. 文件存储：
 *    - 每个联盟怪物有独立数据文件
 *    - 文件路径由UNION常量定义
 * 2. 状态管理：
 *    - 死亡状态会记录时间并周期性复活
 *    - HP/SP变化会实时保存
 * 3. 战斗机制：
 *    - 使用SetBattleVariable()初始化战斗状态
 *    - 死亡判定自动触发LastDefeated记录
 * 
 * 使用流程：
 * 1. 初始化union对象（传入联盟编号或文件路径）
 * 2. 调用SetBattleVariable()准备战斗
 * 3. 进行战斗操作（攻击、防御等）
 * 4. 调用SaveCharData()保存状态变化
 */

include_once("class.char.php");
class union extends char
{

	var $file;
	var $fp;

	var $UnionName;
	var $MonsterNumber;
	var $LastDefeated;

	var $Slave;
	var $Union = true;
	var $UnionNo;
	var $UnionLand;
	var $LevelLimit;
	/*
	Unionモンスターはダメージを受けると経験値を渡す。
	なので、全開のHPと差分を取って死亡判定時に経験値を渡すことにする。
	*/
	var $LastHP;

	// モンスター専用の変数
	var $monster = true;
	var $exphold; //経験値
	var $moneyhold; //お金
	var $itemdrop; //落とすアイテム

	//////////////////////////////////////////////////
	//	コンストラクタ
	function __construct($file = false)
	{
		$this->LoadData($file);
	}

	//////////////////////////////////////////////////
	//	毒ダメージ
	function PoisonDamage($multiply = 1)
	{
		if ($this->STATE !== 2) return false;

		$poison	= $this->PoisonDamageFormula($multiply);
		print("<span class=\"spdmg\">" . $this->Name(bold) . " 由于中毒受到 ");
		print("<span class=\"bold\">$poison</span> 伤害.\n");
		$this->HpDamage2($poison);
		print("</span><br />\n");
	}

	//////////////////////////////////////////////////
	//	毒ダメージの公式
	function PoisonDamageFormula($multiply = 1)
	{
		$damage	= round($this->HP * 0.01);
		$damage	*= mt_rand(50, 150) / 100;
		if (200 < $damage)
			$damage	= 200;
		$damage	*= $multiply;
		return round($damage);
	}

	//////////////////////////////////////////////////
	//	生存状態にする。
	function GetNormal($mes = false)
	{
		if ($this->STATE === ALIVE)
			return true;
		if ($this->STATE === DEAD) { //死亡状態
			// ユニオンは復活しない事とする。
			return true;
			/*
			if($mes)
				print($this->Name(bold).' <span class="recover">revived</span>!<br />'."\n");
			$this->STATE = 0;
			return true;
			*/
		}
		if ($this->STATE === POISON) { //毒状態
			if ($mes)
				print($this->Name(bold) . " 的<span class=\"spdmg\">毒</span> 被治愈.<br />\n");
			$this->STATE = 0;
			return true;
		}
	}

	//////////////////////////////////////////////////
	//	行動を遅らせる(Rate)
	function DelayByRate($No, $BaseDelay, $Show = false)
	{
		if (DELAY_TYPE === 0) {
			if ($Show) {
				print("(" . sprintf("%0.1f", $this->delay));
				print('<span style="font-size:80%"> >>> </span>');
			}
			$Delay	= ($BaseDelay - $this->SPD) * ($No / 100); //遅らせる間隔
			$this->delay	-= $Delay;
			if ($Show) {
				print(sprintf("%0.1f", $this->delay) . "/" . sprintf("%0.1f", $BaseDelay) . ")");
			}
		} else if (DELAY_TYPE === 1) {
			if ($Show) {
				print("(" . sprintf("%0.0f", $this->delay));
				print('<span style="font-size:80%"> >>> </span>');
			}
			$Delay	= round($No / 3); //遅らせる間隔
			$this->delay	-= $Delay;
			if ($Show) {
				print(sprintf("%0.0f", $this->delay) . "/" . sprintf("%d", 100) . ")");
			}
		}
	}

	//////////////////////////////////////////////////
	//	戦闘中のキャラ名,HP,SP を色を分けて表示する
	//	それ以外にも必要な物があれば表示するようにした。
	function ShowHpSp()
	{
		if ($this->STATE === 1)
			$sub	= " dmg";
		else if ($this->STATE === 2)
			$sub	= " spdmg";
		//名前
		print("<span class=\"bold{$sub}\">{$this->name}</span>\n");
		// チャージor詠唱
		if ($this->expect_type === 0)
			print('<span class="charge">(charging)</span>' . "\n");
		else if ($this->expect_type === 1)
			print('<span class="charge">(casting)</span>' . "\n");
		// HP,SP
		print("<div class=\"hpsp\">\n");
		$sub	= $this->STATE === 1 ? "dmg" : "recover";
		//print("<span class=\"{$sub}\">HP : ????/{$this->MAXHP}</span><br />\n");//HP
		print("<span class=\"{$sub}\">HP : ????/????</span><br />\n"); //HP
		$sub	= $this->STATE === 1 ? "dmg" : "support";
		print("<span class=\"{$sub}\">SP : ????/????</span>\n");
		print("</div>\n"); //SP
	}

	//////////////////////////////////////////////////
	//	値の変化を表示する(ダメージ受けた時とか)
	/*
	function ShowValueChange() {
		print("(??? > ???)");
	}
	*/

	//////////////////////////////////////////////////
	//	番号で呼び出す
	function UnionNumber($no)
	{
		$file	= UNION . $no . "_Union.dat";
		if ($this->LoadData($file))
			return true;
		else
			return false;
	}

	//////////////////////////////////////////////////
	//	ユニオン自体が生きてるかどうか確認する(戦闘外で)
	function is_Alive()
	{
		if (0 < $this->hp)
			return true;
		else
			return false;
	}

	//////////////////////////////////////////////////
	//	
	function LoadData($file)
	{
		if (!file_exists($file))
			return false;

		$this->file	= $file;
		$this->fp	= FileLock($this->file);

		$this->UnionNo	= substr(basename($file), 0, 4);
		$data	= ParseFileFP($this->fp);
		$this->SetCharData($data);
		return true;
	}

	//////////////////////////////////////////////////
	function ShowCharLink()
	{
		// <div class="land_<*=$this->UnionLand*>">
?>
		<div class="carpet_frame">
			<div class="land" style="background-image : url(<?php print IMG_OTHER . "land_" . $this->UnionLand . ".gif" ?>);">
				<a href="?union=<?php print $this->UnionNo ?>"><?php $this->ShowImage(); ?></a>
			</div>
			<div class="bold dmg"><?php print $this->UnionName ?></div>限制级别:<?php print $this->LevelLimit ?>级
		</div><?php
			}
			//////////////////////////////////////////////////
			function UpMAXHP($no)
			{
				print($this->Name(bold) . " MAXHP(????) extended to ");
				$this->MAXHP	= round($this->MAXHP * (1 + $no / 100));
				print("????<br />\n");
			}
			function UpMAXSP($no)
			{
				print($this->Name(bold) . " MAXSP(????) extended to ");
				$this->MAXSP	= round($this->MAXSP * (1 + $no / 100));
				print("????<br />\n");
			}
			function DownMAXHP($no)
			{
				$no	/= 2;
				print($this->Name(bold) . " MAXHP(????) down to ");
				$this->MAXHP	= round($this->MAXHP * (1 - $no / 100));
				if ($this->MAXHP < $this->HP)
					$this->HP	= $this->MAXHP;
				print("????<br />\n");
			}
			function DownMAXSP($no)
			{
				$no	/= 2;
				print($this->Name(bold) . " MAXSP(????) down to ");
				$this->MAXSP	= round($this->MAXSP * (1 - $no / 100));
				if ($this->MAXSP < $this->SP)
					$this->SP	= $this->MAXSP;
				print("????<br />\n");
			}
			function DownATK($no)
			{
				$no	= round($no / 2);
				$this->atk["0"]	= round($this->atk["0"] * (1 - $no / 100));
				print($this->Name(bold) . " ATK down {$no}%<br />\n");
			}
			function DownMATK($no)
			{
				$no	= round($no / 2);
				$this->atk["1"]	= round($this->atk["1"] * (1 - $no / 100));
				print($this->Name(bold) . " MATK down {$no}%<br />\n");
			}
			function DownDEF($no)
			{
				$no	= round($no / 2);
				$this->def["0"]	= round($this->def["0"] * (1 - $no / 100));
				print($this->Name(bold) . " DEF down {$no}%<br />\n");
			}
			function DownMDEF($no)
			{
				$no	= round($no / 2);
				$this->def["2"]	= round($this->def["2"] * (1 - $no / 100));
				print($this->Name(bold) . " MDEF down {$no}%<br />\n");
			}
			//////////////////////////////////////////////////
			//	差分経験値
			function HpDifferenceEXP()
			{
				$dif	= $this->LastHP - $this->HP;
				$this->LastHP	= $this->HP;
				if ($dif < 0) return 0;
				$exp	= ceil($this->exphold * ($dif / $this->maxhp));
				return $exp;
			}
			//////////////////////////////////////////////////
			//	キャラの変数をセットする。
			function SetCharData($data)
			{
				$this->MonsterNumber	= $data["MonsterNumber"];
				$this->LastDefeated		= $data["LastDefeated"];

				$monster	= CreateMonster($this->MonsterNumber);

				$this->UnionName	= $monster["UnionName"];

				$this->name	= $monster["name"];
				$this->level	= $monster["level"];

				if ($monster["img"])
					$this->img		= $monster["img"];

				$this->str		= $monster["str"];
				$this->int		= $monster["int"];
				$this->dex		= $monster["dex"];
				$this->spd		= $monster["spd"];
				$this->luk		= $monster["luk"];

				$this->maxhp	= $monster["maxhp"];
				$this->hp		= $data["HP"];
				$this->maxsp	= $monster["maxsp"];
				$this->sp		= $data["SP"];

				$this->position	= $monster["position"];
				$this->guard	= $monster["guard"];

				if (is_array($monster["judge"]))
					$this->judge	= $monster["judge"];
				//else
				//	$this->judge	= explode("<>",$monster["judge"]);
				if (is_array($monster["quantity"]))
					$this->quantity	= $monster["quantity"];
				if (is_array($monster["action"]))
					$this->action	= $monster["action"];

				//モンスター専用
				$this->monster		= true;
				$this->exphold		= $monster["exphold"];
				$this->moneyhold	= $monster["moneyhold"];
				$this->itemdrop		= $monster["itemdrop"];
				$this->atk	= $monster["atk"];
				$this->def	= $monster["def"];
				$this->SPECIAL	= $monster["SPECIAL"];

				$this->Slave	= $monster["Slave"];
				$this->UnionLand	= $monster["land"];
				$this->LevelLimit	= $monster["LevelLimit"];

				// 時間が経過して復活する処理。
				$Now	= time();
				$Passed	= $this->LastDefeated + $monster["cycle"];
				if ($Passed < $Now && !$this->hp) {
					$this->hp	= $this->maxhp;
					$this->sp	= $this->maxsp;
				}
				$this->LastHP	= $data["HP"]; //差分を取るためのHP。
			}

			//////////////////////////////////////////////////
			//	戦闘用の変数
			function SetBattleVariable($team = false)
			{
				// 再読み込みを防止できる か?
				if (isset($this->IMG))
					return false;

				$this->team		= $team; //これ必要か?
				$this->IMG		= $this->img;
				$this->MAXHP	= $this->maxhp;
				$this->HP		= $this->hp;
				$this->MAXSP	= $this->maxsp;
				$this->SP		= $this->sp;
				$this->STR		= $this->str + $this->P_STR;
				$this->INT		= $this->int + $this->P_INT;
				$this->DEX		= $this->dex + $this->P_DEX;
				$this->SPD		= $this->spd + $this->P_SPD;
				$this->LUK		= $this->luk + $this->P_LUK;
				$this->POSITION	= $this->position;
				$this->STATE	= ALIVE; //生存状態にする

				$this->expect	= false; //(数値=詠唱中 false=待機中)
				$this->ActCount	= 0; //行動回数
				$this->JdgCount	= array(); //決定した判断の回数
			}
			//////////////////////////////////////////////////
			//	しぼーしてるかどうか確認する。
			function CharJudgeDead()
			{
				if ($this->HP < 1 && $this->STATE !== 1) { //しぼー
					$this->STATE	= 1;
					$this->HP	= 0;
					$this->ResetExpect();

					$this->LastDefeated	= time();
					return true;
				}
			}
			//////////////////////////////////////////////////
			//	キャラデータの保存
			function SaveCharData($id = "")
			{
				if (!file_exists($this->file))
					return false;
				$string	 = "MonsterNumber=" . $this->MonsterNumber . "\n";
				$string	.= "LastDefeated=" . $this->LastDefeated . "\n";
				$string	.= "HP=" . $this->HP . "\n";
				$string	.= "SP=" . $this->SP . "\n";

				WriteFileFP($this->fp, $string);
				fclose($this->fp);
				unset($this->fp);
			}
		}
				?>