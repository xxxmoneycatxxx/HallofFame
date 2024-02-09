<?php 
include_once("class.char.php");
class monster extends char{

	// 專用變數
	var $monster = true;
	var $exphold;//經驗值
	var $moneyhold;//金
	var $itemdrop;//落
	var $summon;
//////////////////////////////////////////////////
//	
	function monster($data) {
		$this->SetCharData($data);

	}
//////////////////////////////////////////////////
//	保存
	function SaveCharData() {
		// 保存。
		return false;
	}

//////////////////////////////////////////////////
//	生存狀態。
	function GetNormal($mes=false) {
		if($this->STATE === ALIVE)
			return true;
		if($this->STATE === DEAD) {//死亡狀態
			if($this->summon) return true;
			if($mes)
				print($this->Name(bold).' <span class="recover">revived</span>!<br />'."\n");
			$this->STATE = 0;
			return true;
		}
		if($this->STATE === POISON) {//毒狀態
			if($mes)
				print($this->Name(bold)."'s <span class=\"spdmg\">poison</span> has cured.<br />\n");
			$this->STATE = 0;
			return true;
		}
	}
//////////////////////////////////////////////////
//	確認。
	function CharJudgeDead() {
		if($this->HP < 1 && $this->STATE !== DEAD) {//
			$this->STATE	= DEAD;
			$this->HP	= 0;
			$this->ResetExpect();
			//$this->delay	= 0;

			return true;
		}
	}
//////////////////////////////////////////////////
//	變數。
	function SetCharData($monster) {

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

		if(is_array($monster["judge"]))
			$this->judge	= $monster["judge"];
		if(is_array($monster["quantity"]))
			$this->quantity	= $monster["quantity"];
		if(is_array($monster["action"]))
			$this->action	= $monster["action"];

		//專用
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
//	戰鬥用變數
	function SetBattleVariable($team=false) {
		// 再讀迂防止 ?
		if(isset($this->IMG))
			return false;

		$this->team		= $team;//必要?
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
		$this->STATE	= ALIVE;//生存狀態

		$this->expect	= false;//(數值=詠唱中 false=待機中)
		$this->ActCount	= 0;//行動回數
		$this->JdgCount	= array();//決定判斷回數
	}
}
?>
