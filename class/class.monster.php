<?php
include_once("class.char.php");
/**
 * 怪物角色类
 * 
 * 功能说明：
 * 1. 实现游戏怪物角色的数据管理和行为控制
 * 2. 继承自基础角色类(char)，扩展怪物特有功能
 * 3. 处理怪物在战斗中的特殊行为和状态
 * 
 * 主要功能模块：
 * 1. 怪物特性：
 *    - 经验值和金钱掉落机制
 *    - 物品掉落系统
 *    - 召唤怪物特殊处理
 * 2. 状态管理：
 *    - 死亡状态判定与处理
 *    - 中毒状态恢复
 *    - 召唤状态识别
 * 3. 战斗系统：
 *    - 战斗属性初始化
 *    - 位置和防御策略设置
 *    - 特殊技能效果
 * 
 * 技术特点：
 * 1. 继承与扩展：
 *    - 继承基础角色类所有功能
 *    - 扩展怪物特有属性和方法
 * 2. 状态机设计：
 *    - 多种状态管理（正常/死亡/中毒）
 *    - 状态转换逻辑
 * 3. 战斗系统集成：
 *    - 攻击防御属性计算
 *    - 特殊效果处理
 *    - 战斗变量初始化
 * 
 * 特殊机制：
 * 1. 掉落系统：
 *    - 经验值(exphold)和金钱(moneyhold)持有
 *    - 物品掉落(itemdrop)机制
 * 2. 召唤系统：
 *    - 召唤怪物标识(summon)
 *    - 召唤怪物特殊处理
 * 3. 状态恢复：
 *    - 自动复活机制
 *    - 中毒状态治愈
 * 
 * 使用注意事项：
 * 1. 数据持久化：
 *    - 怪物数据不会被保存(SaveCharData返回false)
 *    - 临时战斗对象
 * 2. 状态管理：
 *    - STATE常量：0=正常, 1=死亡, 2=中毒
 *    - 死亡状态自动重置战斗期望
 * 3. 战斗初始化：
 *    - SetBattleVariable()方法准备战斗所需变量
 * 
 * 使用流程：
 * 1. 创建怪物对象(传入怪物数据数组)
 * 2. 设置战斗变量(SetBattleVariable)
 * 3. 加入战斗队伍
 * 4. 战斗结束后自动销毁
 */

class monster extends char
{

	// モンスター専用の変数
	var $monster = true;
	var $exphold; //経験値
	var $moneyhold; //お金
	var $itemdrop; //落とすアイテム
	var $summon;
	//////////////////////////////////////////////////
	//	
	function __construct($data)
	{
		$this->SetCharData($data);
	}
	//////////////////////////////////////////////////
	//	キャラデータの保存
	function SaveCharData($id = "")
	{
		// モンスターは保存しない。
		return false;
	}

	//////////////////////////////////////////////////
	//	生存状態にする。
	function GetNormal($mes = false)
	{
		if ($this->STATE === ALIVE)
			return true;
		if ($this->STATE === DEAD) { //死亡状態
			if ($this->summon) return true;
			if ($mes)
				print($this->Name("bold") . ' <span class="recover">revived</span>!<br />' . "\n");
			$this->STATE = 0;
			return true;
		}
		if ($this->STATE === POISON) { //毒状態
			if ($mes)
				print($this->Name("bold") . "'s <span class=\"spdmg\">poison</span> has cured.<br />\n");
			$this->STATE = 0;
			return true;
		}
	}
	//////////////////////////////////////////////////
	//	しぼーしてるかどうか確認する。
	function CharJudgeDead()
	{
		if ($this->HP < 1 && $this->STATE !== DEAD) { //しぼー
			$this->STATE	= DEAD;
			$this->HP	= 0;
			$this->ResetExpect();
			//$this->delay	= 0;

			return true;
		}
	}
	//////////////////////////////////////////////////
	//	キャラの変数をセットする。
	function SetCharData($monster)
	{

		$this->name		= $monster["name"];
		$this->level	= $monster["level"];

		if ($monster["img"])
			$this->img		= $monster["img"];

		$this->str		= $monster["str"];
		$this->int		= $monster["int"];
		$this->dex		= $monster["dex"];
		$this->spd		= $monster["spd"];
		$this->luk		= $monster["luk"];

		$this->maxhp	= $monster["maxhp"];
		$this->hp		= $monster["hp"];
		$this->maxsp	= $monster["maxsp"];
		$this->sp		= $monster["sp"];

		$this->position	= $monster["position"];
		$this->guard	= $monster["guard"];

		if (is_array($monster["judge"]))
			$this->judge	= $monster["judge"];
		if (is_array($monster["quantity"]))
			$this->quantity	= $monster["quantity"];
		if (is_array($monster["action"]))
			$this->action	= $monster["action"];

		//モンスター専用
		//$this->monster		= $monster["monster"];
		$this->monster		= true;
		$this->summon		= $monster["summon"];
		$this->exphold		= $monster["exphold"];
		$this->moneyhold	= $monster["moneyhold"];
		$this->itemdrop		= $monster["itemdrop"];
		$this->atk	= $monster["atk"];
		$this->def	= $monster["def"];
		$this->SPECIAL	= $monster["SPECIAL"];
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
}
