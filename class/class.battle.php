<?php 
include(CLASS_SKILL_EFFECT);
class battle extends ClassSkillEffect{
/*
 * $battle	= new battle($MyParty,$EnemyParty);
 * $battle->SetTeamName($this->name,$party["name"]);
 * $battle->Process();//戰鬥開始
 */
	// teams
	var $team0, $team1;
	// team name
	var $team0_name, $team1_name;
	// team ave level
	var $team0_ave_lv, $team1_ave_lv;

	// 魔方陣
	var $team0_mc = 0;
	var $team1_mc = 0;

	// 戰鬥最大回合數(延長回合可能性)
	var $BattleMaxTurn	= BATTLE_MAX_TURNS;
	var $NoExtends	= false;

	//
	var $NoResult	= false;

	// 戰鬥背景
	var $BackGround = "grass";

	//  ( << >> ← 變數)
	var $Scroll = 0;

	// 總傷害數
	var $team0_dmg = 0;
	var $team1_dmg = 0;
	// 總行動回數
	var $actions = 0;
	// 戰鬥延遲基準
	var $delay;
	// 勝利
	var $result;
	// 金
	var $team0_money, $team1_money;
	// 
	var $team0_item=array(), $team1_item=array();
	var $team0_exp=0, $team1_exp=0;// 總經驗值。

	// 特殊變數
	var $ChangeDelay	= false;//SPD變化際DELAY再計算。

	var $BattleResultType	= 0;// 0=決著著Draw 1=生存者數勝敗決
	var $UnionBattle;// 殘HP總HP隱(????/????)
//////////////////////////////////////////////////
//	。

	//各配列受。
	function battle($team0,$team1) {
		include(DATA_JUDGE);
		include_once(DATA_SKILL);

		//參戰召喚場合
		include_once(CLASS_MONSTER);

		$this->team0	= $team0;
		$this->team1	= $team1;

		// 各戰鬥專用變數設定(class.char.php)
		// 裝備特殊機能等計算設定。
		// 戰鬥專用變數大文字英語。class.char.php參照。
		//  $this->team["$key"] 渡.(引數番號)
		foreach($this->team0 as $key => $char)
			$this->team0["$key"]->SetBattleVariable(TEAM_0);
		foreach($this->team1 as $key => $char)
			$this->team1["$key"]->SetBattleVariable(TEAM_1);
		//dump($this->team0[0]);
		// delay關連
		$this->SetDelay();//計算
		$this->DelayResetAll();//初期化
	}
//////////////////////////////////////////////////
//	
	function SetResultType($var) {
		$this->BattleResultType	= $var;
	}
//////////////////////////////////////////////////
//	UnionBattle事。
	function SetUnionBattle() {
		$this->UnionBattle	= true;
	}
//////////////////////////////////////////////////
//	背景畫像。
	function SetBackGround($bg) {
		$this->BackGround	= $bg;
	}
//////////////////////////////////////////////////
//	戰鬥途中參加。
	function JoinCharacter($user,$add) {
		foreach($this->team0 as $char) {
			if($user === $char) {
				//array_unshift($this->team0,$add);
				$add->SetTeam(TEAM_0);
				array_push($this->team0,$add);
				//dump($this->team0);
				$this->ChangeDelay();
				return 0;
			}
		}
		foreach($this->team1 as $char) {
			if($user === $char) {
				//array_unshift($this->team1,$add);
				$add->SetTeam(TEAM_1);
				array_push($this->team1,$add);
				$this->ChangeDelay();
				return 0;
			}
		}
	}
//////////////////////////////////////////////////
//	設定最大戰鬥回和數。
	function LimitTurns($no) {
		$this->BattleMaxTurn	= $no;
		$this->NoExtends		= true;//以上延長。
	}
//////////////////////////////////////////////////
//	
	function NoResult() {
		$this->NoResult	= true;
	}
//////////////////////////////////////////////////
//	戰鬥最大回合數增加
	function ExtendTurns($no,$notice=false) {
		// 延長變數設定延長。
		if($this->NoExtends === true) return false;

		$this->BattleMaxTurn	+= $no;
		if(BATTLE_MAX_EXTENDS < $this->BattleMaxTurn)
			$this->BattleMaxTurn	= BATTLE_MAX_EXTENDS;
		if($notice) {
print <<< HTML
	<tr><td colspan="2" class="break break-top bold" style="text-align:center;padding:20px 0;">
	超出戰鬥回合數.
	</td></tr>
HTML;
		}
		return true;
	}
//////////////////////////////////////////////////
//	戰鬥中獲得物品返迴。
	function ReturnItemGet($team) {
		if($team == TEAM_0) {
			if(count($this->team0_item) != 0)
				return $this->team0_item;
			else
				return false;
		} else if($team == TEAM_1) {
			if(count($this->team1_item) != 0)
				return $this->team1_item;
			else
				return false;
		}
	}
//////////////////////////////////////////////////
//	返回戰鬥結果
	function ReturnBattleResult() {
		return $this->result;
	}
//////////////////////////////////////////////////
//	戰鬥記錄保存
	function RecordLog($type=false) {
		if($type == "RANK") {
			$file	= LOG_BATTLE_RANK;
			$log	= @glob(LOG_BATTLE_RANK."*");
			$logAmount = MAX_BATTLE_LOG_RANK;
		} else if($type == "UNION") {
			$file	= LOG_BATTLE_UNION;
			$log	= @glob(LOG_BATTLE_UNION."*");
			$logAmount = MAX_BATTLE_LOG_UNION;
		} else {
			$file	= LOG_BATTLE_NORMAL;
			$log	= @glob(LOG_BATTLE_NORMAL."*");
			$logAmount = MAX_BATTLE_LOG;
		}

		// 舊紀錄消除
		$i	= 0;
		while($logAmount <= count($log) ) {
			unlink($log["$i"]);
			unset($log["$i"]);
			$i++;
		}

		// 新紀錄生成
		$time	= time().substr(microtime(),2,6);
		$file	.= $time.".dat";

		$head	= $time."\n";//開始時間(1行目)
		$head	.= $this->team0_name."<>".$this->team1_name."\n";//參加(2行目)
		$head	.= count($this->team0)."<>".count($this->team1)."\n";//參加人數(3行目)
		$head	.= $this->team0_ave_lv."<>".$this->team1_ave_lv."\n";//平均(4行目)
		$head	.= $this->result."\n";//勝利(5行目)
		$head	.= $this->actions."\n";//總數(6行目)
		$head	.= "\n";// 改行(7行目)

		WriteFile($file,$head.ob_get_contents());
	}
//////////////////////////////////////////////////
//	戰鬥處理(實行戰鬥過程處理)
	function Process() {
		$this->BattleHeader();

		//戰鬥終
		do {
			if($this->actions % BATTLE_STAT_TURNS == 0)//一定間隔狀況表示
				$this->BattleState();//狀況表示

			// 行動
			if(DELAY_TYPE === 0)
				$char	= &$this->NextActer();
			else if(DELAY_TYPE === 1)
				$char	= &$this->NextActerNew();

			$this->Action($char);//行動
			$result	= $this->BattleResult();//↑行動戰鬥終了判定

			//技使用等SPD變化場合DELAY再計算。
			if($this->ChangeDelay)
				$this->SetDelay();

		} while(!$result);

		$this->ShowResult($result);//戰鬥結果表示
		$this->BattleFoot();

		//$this->SaveCharacters();
	}
//////////////////////////////////////////////////
//	戰鬥後狀況保存。
	function SaveCharacters() {
		//0
		foreach($this->team0 as $char) {
			$char->SaveCharData();
		}
		//1
		foreach($this->team1 as $char) {
			$char->SaveCharData();
		}
	}

//////////////////////////////////////////////////
//	戰鬥終了判定
//	全員死=draw(?)
	function BattleResult() {
		if(CountAlive($this->team0) == 0)//全員負。
			$team0Lose	= true;
		if(CountAlive($this->team1) == 0)//全員負。
			$team1Lose	= true;
		//勝者番號引分返
		if( $team0Lose && $team1Lose ) {
			$this->result	= DRAW;
			return "draw";
		} else if($team0Lose) {//team1 won
			$this->result	= TEAM_1;
			return "team1";
		} else if($team1Lose) {// team0 won
			$this->result	= TEAM_0;
			return "team0";

		// 兩生存最大行動數達時。
		} else if($this->BattleMaxTurn <= $this->actions) {
			// 生存者數差。
			/*
				// 生存者數差1人以上延長
			$AliveNumDiff	= abs(CountAlive($this->team0) - CountAlive($this->team1));
			if(0 < $AliveNumDiff && $this->BattleMaxTurn < BATTLE_MAX_EXTENDS) {
			*/
			$AliveNumDiff	= abs(CountAlive($this->team0) - CountAlive($this->team1));
			$Not5	= (CountAlive($this->team0) != 5 && CountAlive($this->team1) != 5);
			//$lessThan4	= ( CountAlive($this->team0) < 5 || CountAlive($this->team1) < 5 );
			//if( ( $lessThan4 || 0 < $AliveNumDiff ) && $this->BattleMaxTurn < BATTLE_MAX_EXTENDS ) {
			if( ( $Not5 || 0 < $AliveNumDiff ) && $this->BattleMaxTurn < BATTLE_MAX_EXTENDS ) {
				if($this->ExtendTurns(TURN_EXTENDS,1))
					return false;
			}

			// 決著著引分。
			if($this->BattleResultType == 0) {
				$this->result	= DRAW;//引分。
				return "draw";
			// 決著著生存者數勝敗。
			} else if($this->BattleResultType == 1) {
				// 引分設定
				// (1) 生存者數多勝
				// (2) (1) 同總多勝
				// (3) (2) 同引分…???(or防衛側勝)
	
				$team0Alive	= CountAliveChars($this->team0);
				$team1Alive	= CountAliveChars($this->team1);
				if($team1存活 < $team0Alive) {// team0 won
					$this->result	= TEAM_0;
					return "team0";
				} else if($team0存活 < $team1Alive) {// team1 won
					$this->result	= TEAM_1;
					return "team1";
				} else {
					$this->result	= DRAW;
					return "draw";
				}
			} else {
				$this->result	= DRAW;
				print("error321708.<br />請報告出錯了...（？）。");
				return "draw";// 迴避。
			}

			$this->result	= DRAW;
			print("error321709.<br />請報告出錯了...（？）。");
			return "draw";// 迴避。
		}
	}
//////////////////////////////////////////////////
//	戰鬥結果表示
	function ShowResult($result) {

		// 左側(戰鬥受側)
		$TotalAlive2	= 0;
		// 殘HP / 合計HP  表示
		foreach($this->team1 as $char) {//1
			if($char->STATE !== DEAD)
				$TotalAlive2++;
			$TotalHp2	+= $char->HP;//合計HP
			$TotalMaxHp2	+= $char->MAXHP;//合計最大HP
		}

		// 右側(戰鬥仕掛側)
		$TotalAlive1	= 0;
		foreach($this->team0 as $char) {//0
			if($char->STATE !== DEAD)
				$TotalAlive1++;
			$TotalHp1	+= $char->HP;//合計HP
			$TotalMaxHp1	+= $char->MAXHP;//合計最大HP
		}

		// 結果表示。
		if($this->NoResult) {
			print('<tr><td colspan="2" style="text-align:center;padding:10px 0px" class="break break-top">');
			//print("<a name=\"s{$this->Scroll}\"></a>");// 最後
			print("模擬戰結束");
			print("</td></tr>\n");
			print('<tr><td class="teams break">'."\n");
			// 左側
			print("殘留HP : {$TotalHp2}/{$TotalMaxHp2}<br />\n");
			print("存活 : {$TotalAlive2}/".count($this->team1)."<br />\n");
			print("總傷害 : {$this->team1_dmg}<br />\n");
			// 右側
			print('</td><td class="teams break">'."\n");
			print("殘留HP : {$TotalHp1}/{$TotalMaxHp1}<br />\n");
			print("存活 : {$TotalAlive1}/".count($this->team0)."<br />\n");
			print("總傷害 : {$this->team0_dmg}<br />\n");
			print("</td></tr>\n");
			return false;
		}

		//if($this->actions % BATTLE_STAT_TURNS != 0 || $result == "draw")
		//if(($this->actions + 1) % BATTLE_STAT_TURNS != 0)
		$BreakTop	= " break-top";
		print('<tr><td colspan="2" style="text-align:center;padding:10px 0px" class="break'.$BreakTop.'">'."\n");
		//print($this->actions."%".BATTLE_STAT_TURNS."<br>");
		print("<a name=\"s{$this->Scroll}\"></a>\n");// 最後
		if($result == "draw") {
			print("<span style=\"font-size:150%\">平局</span><br />\n");
		} else {
			$Team	= &$this->{$result};
			$TeamName	= $this->{$result."_name"};
			print("<span style=\"font-size:200%\">{$TeamName} 勝利!</span><br />\n");
		}

		print('<tr><td class="teams">'."\n");
		// Union
		print("殘留HP : ");
		print($this->UnionBattle?"????/????":"{$TotalHp2}/{$TotalMaxHp2}");
		print("<br />\n");
/*
		if($this->UnionBattle) {
			print("殘留HP : ????/????<br />\n");
		} else {
			print("殘留HP : {$TotalHp2}/{$TotalMaxHp2}<br />\n");
		}
*/
		// 左側
		print("存活 : {$TotalAlive2}/".count($this->team1)."<br />\n");
		print("總傷害 : {$this->team1_dmg}<br />\n");
		if($this->team1_exp)//得經驗值
			print("總經驗值 : ".$this->team1_exp."<br />\n");
		if($this->team1_money)//得金
			print("金錢 : ".MoneyFormat($this->team1_money)."<br />\n");
		if($this->team1_item) {//得
			print("<div class=\"bold\">道具</div>\n");
			foreach($this->team0_item as $itemno => $amount) {
				$item	= LoadItemData($itemno);
				print("<img src=\"".IMG_ICON.$item["img"]."\" class=\"vcent\">");
				print("{$item[name]} x {$amount}<br />\n");
			}
		}

		// 右側
		print('</td><td class="teams">');
		print("殘留HP : {$TotalHp1}/{$TotalMaxHp1}<br />\n");
		print("存活 : {$TotalAlive1}/".count($this->team0)."<br />\n");
		print("總傷害 : {$this->team0_dmg}<br />\n");
		if($this->team0_exp)//得經驗值
			print("總經驗值 : ".$this->team0_exp."<br />\n");
		if($this->team0_money)//得金
			print("金錢 : ".MoneyFormat($this->team0_money)."<br />\n");
		if($this->team0_item) {//得
			print("<div class=\"bold\">Items</div>\n");
			foreach($this->team0_item as $itemno => $amount) {
				$item	= LoadItemData($itemno);
				print("<img src=\"".IMG_ICON.$item["img"]."\" class=\"vcent\">");
				print("{$item[name]} x {$amount}<br />\n");
			}
		}
		print("</td></tr>\n");
		//print("</td></tr>\n");//?
	}

//////////////////////////////////////////////////
//	行動
	function Action(&$char) {
		// $char->judge 設定飛
		if($char->judge === array()) {
			$char->delay	= $char->SPD;
			return false;
		}

		// 0人右側
		// 1人左側 行動內容結果 表示
		print("<tr><td class=\"ttd2\">\n");
		if($char->team === TEAM_0)
			print("</td><td class=\"ttd1\">\n");
		// 自分?
		foreach($this->team0 as $val) {
			if($val === $char) {
				$MyTeam	= &$this->team0;
				$EnemyTeam	= &$this->team1;
				break;
			}
		}
		//01
		if(!$MyTeam) {
			$MyTeam	= &$this->team1;
			$EnemyTeam	= &$this->team0;
		}

		//行動判定(使用技判定)
		if($char->expect) {// 詠唱,貯 完了
			$skill	= $char->expect;
			$return	= &$char->target_expect;
		} else {//待機→判定→
			$JudgeKey	= -1;

			// 持續回復系
			$char->AutoRegeneration();
			// 毒狀態受。
			$char->PoisonDamage();

			//判定
			do {
				$Keys	= array();//空配列(初期化)
				do {
					$JudgeKey++;
					$Keys[]	= $JudgeKey;
				// 重複判定次加
				} while($char->action["$JudgeKey"] == 9000 && $char->judge["$JudgeKey"]);

				//$return	= MultiFactJudge($Keys,$char,$MyTeam,$EnemyTeam);
				$return	= MultiFactJudge($Keys,$char,$this);

				if($return) {
					$skill	= $char->action["$JudgeKey"];
					foreach($Keys as $no)
						$char->JdgCount[$no]++;//決定判斷ｐ
					break;
				}
			} while($char->judge["$JudgeKey"]);

			/* // (2007/10/15)
			foreach($char->judge as $key => $judge){
				// $return  true,false,配列
				// 配列場合判定條件一致返()。
				$return	=& DecideJudge($judge,$char,$MyTeam,$EnemyTeam,$key);
				if($return) {
					$skill	= $char->action["$key"];
					$char->JdgCount[$key]++;//決定判斷ｐ
					break;
				}
			}
			*/
		}

		// 戰鬥總行動回數增。
		$this->actions++;

		if($skill) {
			$this->UseSkill($skill,$return,$char,$MyTeam,$EnemyTeam);
		// 行動場合處理
		} else {
			print($char->Name(bold)." 陷入沉思結果忘了行動.<br />(無更多行動模式)<br />\n");
			$char->DelayReset();
		}

		//
		//if($ret	!== "DontResetDelay")
		//	$char->DelayReset;

		//echo $char->name." ".$skill."<br>";//確認用
		//終
		if($char->team === TEAM_1)
			print("</td><td class=\"ttd1\"> \n");
		print("</td></tr>\n");
	}
//////////////////////////////////////////////////
//	總加算
	function AddTotalDamage($team,$dmg) {
		if(!is_numeric($dmg)) return false;
		if($team == $this->team0)
			$this->team0_dmg	+= $dmg;
		else if($team == $this->team1)
			$this->team1_dmg	+= $dmg;
	}

//////////////////////////////////////////////////
//
	function UseSkill($skill_no,&$JudgedTarget,&$My,&$MyTeam,&$Enemy) {
		$skill	= LoadSkillData($skill_no);//技讀

		// 武器不一致
		if($skill["limit"] && !$My->monster) {
			if(!$skill["limit"][$My->WEAPON]) {
				print('<span class="u">'.$My->Name(bold));
				print('<span class="dmg"> 失敗</span> 因為 ');
				print($skill["limit"][$My->WEAPON]);
				print("<img src=\"".IMG_ICON.$skill["img"]."\" class=\"vcent\"/>");
				print($skill[name]."</span><br />\n");
				//print($My->Name(bold)." Failed to use ".$skill["name"]."<br />\n");
				print("(武器類型不符)<br />\n");
				$My->DelayReset();// 行動順
				return true;
			}
		}

		// SP不足
		if($My->SP < $skill["sp"]) {
			print($My->Name(bold).$skill["name"]."失敗(SP不足)");
			if($My->expect) {//詠唱貯途中SP不足場合
				$My->ResetExpect();
			}
			$My->DelayReset();// 行動順
			return true;
		}

		//  "詠唱"  "貯" 必要技(+詠唱開始場合)→詠唱,貯開始
		if($skill["charge"]["0"] && $My->expect === false) {
			// 貯詠唱開始場合 /////////////////////
			// 物理魔法文變
			if($skill["type"] == 0) {//物理
				print('<span class="charge">'.$My->Name(bold).' 開始蓄力.</span>');
				$My->expect_type	= CHARGE;
			} else {//魔法
				print('<span class="charge">'.$My->Name(bold).' 開始詠唱.</span>');
				$My->expect_type	= CAST;
			}
			$My->expect	= $skill_no;//詠唱?貯完了同時使用技
			// ↓使。
			//$My->target_expect	= $JudgedTarget;//一應保存
			//詠唱?貯時間設定。
			$My->DelayByRate($skill["charge"]["0"],$this->delay,1);
			print("<br />\n");

			// 戰鬥總行動回數減(貯or詠唱 行動入)
			$this->actions--;

			return true;//變更。
		} else {
			// 技實際使用 ///////////////////////////////////

			// 行動回數
			$My->ActCount++;

			// 行動內容表示(行動)
			print('<div class="u">'.$My->Name(bold));
			print("<img src=\"".IMG_ICON.$skill["img"]."\" class=\"vcent\"/>");
			print($skill[name]."</div>\n");

			// 魔法陣消費(味方)
			if($skill["MagicCircleDeleteTeam"])
			{
				if($this->MagicCircleDelete($My->team,$skill["MagicCircleDeleteTeam"])) {
					print($My->Name(bold).'<span class="charge"> 使用魔法陣 x'.$skill["MagicCircleDeleteTeam"].'</span><br />'."\n");
				// 魔法陣消費失敗
				} else {
					print('<span class="dmg">失敗!(魔法陣不足)</span><br />'."\n");
					$My->DelayReset();// 行動順
					return true;
				}
			}

			// SP消費(位置貯?詠唱完了同時消費)
			$My->SpDamage($skill["sp"],false);

			// (詠唱)完了同時使用技情報消。
			if($My->expect)
				$My->ResetExpect();

			// HP犧牲技場合(Sacrifice)
			if($skill["sacrifice"])
				$My->SacrificeHp($skill["sacrifice"]);

		}

		// 選(候補)
		if($skill["target"]["0"] == "friend"):
			$candidate	= &$MyTeam;
		elseif($skill["target"]["0"] == "enemy"):
			$candidate	= &$Enemy;
		elseif($skill["target"]["0"] == "self"):
			$candidate[]	= &$My;
		elseif($skill["target"]["0"] == "all"):
			//$candidate	= $MyTeam + $Enemy;//???
			$candidate	= array_merge_recursive(&$MyTeam,&$Enemy);//結合後,並方??
		endif;

		// 候補使用對像選 → (使用)

		// 單體使用
		if($skill["target"]["1"] == "individual") {
			$target	=& $this->SelectTarget($candidate,$skill);//對像選擇
			if($defender =& $this->Defending($target,$candidate,$skill) )//守入
				$target	= &$defender;
			for($i=0; $i<$skill["target"]["2"]; $i++) {//單體複數回實行
				$dmg	= $this->SkillEffect($skill,$skill_no,$My,$target);
				$this->AddTotalDamage($MyTeam,$dmg);
			}

		// 複數使用
		} else if($skill["target"]["1"] == "multi") {
			for($i=0; $i<$skill["target"]["2"]; $i++) {
				$target	=& $this->SelectTarget($candidate,$skill);//對像選擇
				if($defender =& $this->Defending($target,$candidate,$skill) )//守入
					$target	= &$defender;
				$dmg	= $this->SkillEffect($skill,$skill_no,$My,$target);
				$this->AddTotalDamage($MyTeam,$dmg);
			}

		// 全體使用
		} else if($skill["target"]["1"] == "all") {
			foreach($candidate as $key => $char) {
				$target	= &$candidate[$key];
				//if($char->STATE === DEAD) continue;//死亡者。
				if($skill["priority"] != "死亡") {//一時的。
					if($char->STATE === DEAD) continue;//死亡者。
				}
				// 全體攻擊守入()
				for($i=0; $i<$skill["target"]["2"]; $i++) {
					$dmg	= $this->SkillEffect($skill,$skill_no,$My,$target);
					$this->AddTotalDamage($MyTeam,$dmg);
				}
			}
		}

		// 使用後使用者影響效果等
		if($skill["umove"])
			$My->Move($skill["umove"]);

		// 攻擊對像達確(HP=0)。
		if($skill["sacrifice"]) { // Sacri系技使場合。
			$Sacrier[]	= &$My;
			$this->JudgeTargetsDead($Sacrier);
		}
		list($exp,$money,$itemdrop)	= $this->JudgeTargetsDead($candidate);//又、取得經驗值得

		$this->GetExp($exp,$MyTeam);
		$this->GetItem($itemdrop,$MyTeam);
		$this->GetMoney($money,$MyTeam);

		// 技使用等SPD變化場合DELAY再計算。
		if($this->ChangeDelay)
			$this->SetDelay();

		// 行動後硬直(設定)
		if($skill["charge"]["1"]) {
			$My->DelayReset();
			print($My->Name(bold)." 行動推遲了");
			$My->DelayByRate($skill["charge"]["1"],$this->delay,1);
			print("<br />\n");
			return false;
		}

		// 最後行動順。
		$My->DelayReset();
	}
//////////////////////////////////////////////////
//	經驗值得
function GetExp($exp,&$team) {
	if(!$exp) return false;

	$exp	= round(EXP_RATE * $exp);

	if($team === $this->team0){
		$this->team0_exp	+= $exp;
	} else {
		$this->team1_exp	+= $exp;
	}

	$Alive	= CountAliveChars($team);
	if($Alive=== 0) return false;
	$ExpGet	= ceil($exp/$Alive);//生存者經驗值分。
	print("存活者獲得 {$ExpGet} 經驗.<br />\n");
	foreach($team as $key => $char) {
		if($char->STATE === 1) continue;//死亡者EXP
		if($team[$key]->GetExp($ExpGet))//LvUptrue返
			print("<span class=\"levelup\">".$char->Name()." 升級!</span><br />\n");
	}
}
//////////////////////////////////////////////////
//	取得()
	function GetItem($itemdrop,$MyTeam) {
		if(!$itemdrop) return false;
		if($MyTeam === $this->team0) {
			foreach($itemdrop as $itemno => $amount) {
				$this->team0_item["$itemno"]	+= $amount;
			}
		} else {
			foreach($itemdrop as $itemno => $amount) {
				$this->team1_item["$itemno"]	+= $amount;
			}
		}
	}

//////////////////////////////////////////////////
//	後衛守入選。
	function &Defending(&$target,&$candidate,$skill) {
		if($target === false) return false;

		if($skill["invalid"])//防禦無視技。
			return false;
		if($skill["support"])//支援。
			return false;
		if($target->POSITION == "front")//前衛守必要無。終
			return false;
		// "前衛尚且生存者"配列詰↓
		// 前衛 + 生存者 + HP1以上 變更 ( 多段系攻擊死守 [2007/9/20] )
		foreach($candidate as $key => $char) {
			//print("{$char->POSTION}:{$char->STATE}<br>");
			if($char->POSITION == "front" && $char->STATE !== 1 && 1 < $char->HP )
				$fore[]	= &$candidate["$key"];
		}
		if(count($fore) == 0)//前衛守。終
			return false;
		// 一人守入入判定。
		shuffle($fore);//配列並混
		foreach($fore as $key => $char) {
			// 判定使變數計算。
			switch($char->guard) {
				case "life25":
				case "life50":
				case "life75":
					$HpRate	= ($char->HP / $char->MAXHP) * 100;
				case "prob25":
				case "prob50":
				case "prob75":
					mt_srand();
					$prob	= mt_rand(1,100);
			}
			// 實際判定。
			switch($char->guard) {
				case "never":
					continue;
				case "life25":// HP(%)25%以上
					if(25 < $HpRate) $defender	= &$fore["$key"]; break;
				case "life50":// 〃50%〃
					if(50 < $HpRate) $defender	= &$fore["$key"]; break;
				case "life75":// 〃70%〃
					if(75 < $HpRate) $defender	= &$fore["$key"]; break;
				case "prob25":// 25%確率
					if($prob < 25) $defender	= &$fore["$key"]; break;
				case "prob50":// 50% 〃
					if($prob < 50) $defender	= &$fore["$key"]; break;
				case "prob75":// 75% 〃
					if($prob < 75) $defender	= &$fore["$key"]; break;
				default:
					$defender	= &$fore["$key"];
			}
			// 誰後衛守入表示
			if($defender) {
				print('<span class="bold">'.$defender->name.'</span> 保護<span class="bold">'.$target->name.'</span>!<br />'."\n");
				return $defender;
			}
		}
	}
//////////////////////////////////////////////////
//	使用後對像者(候補)確
	function JudgeTargetsDead(&$target) {
		foreach($target as $key => $char) {
			// 與差分經驗值取得場合。
			if(method_exists($target[$key],'HpDifferenceEXP')) {
				$exp	+= $target[$key]->HpDifferenceEXP();
			}
			if($target[$key]->CharJudgeDead()) {//死
				// 死亡
				print("<span class=\"dmg\">".$target[$key]->Name(bold)." 被打倒.</span><br />\n");

				//經驗值取得
				$exp	+= $target[$key]->DropExp();

				//金取得
				$money	+= $target[$key]->DropMoney();

				// 
				if($item = $target[$key]->DropItem()) {
					$itemdrop["$item"]++;
					$item	= LoadItemData($item);
					print($char->Name("bold")." 掉落了");
					print("<img src=\"".IMG_ICON.$item["img"]."\" class=\"vcent\"/>\n");
					print("<span class=\"bold u\">{$item[name]}</span>.<br />\n");
				}

				//召喚消。
				if($target[$key]->summon === true) {
					unset($target[$key]);
				}

				// 死直。
				$this->ChangeDelay();
			}
		}
		return array($exp,$money,$itemdrop);//取得經驗值返
	}
//////////////////////////////////////////////////
//	優先順位從候補一人返
	function &SelectTarget(&$target_list,$skill) {

		/*
		* 優先、當最終的要。
		* 例 : 後衛居→前衛對像。
		*    : 全員HP100%→誰  對像。
		*/

		//殘HP(%)少人
		if($skill["priority"] == "LowHpRate") {
			$hp = 2;//一應1大數字???
			foreach($target_list as $key => $char) {
				if($char->STATE == DEAD) continue;//者對像。
				$HpRate	= $char->HP / $char->MAXHP;//HP(%)
				if($HpRate < $hp) {
					$hp	= $HpRate;//現狀最HP(%)低人
					$target	= &$target_list[$key];
				}
			}
			return $target;//最HP低人

		//後衛優先
		} else if($skill["priority"] == "Back") {
			foreach($target_list as $key => $char) {
				if($char->STATE == DEAD) continue;//者對像。
				if($char->POSITION != FRONT)//後衛
				$target[]	= &$target_list[$key];//候補
			}
			if($target)
				return $target[array_rand($target)];//中

		/*
		* 優先、
		* 優先對像使用失敗(絞迂)
		*/

		//者中返。
		} else if($skill["priority"] == "Dead") {
			foreach($target_list as $key => $char) {
				if($char->STATE == DEAD)//
				$target[]	= &$target_list[$key];//者
			}
			if($target)
				return $target[array_rand($target)];//者中
			else
				return false;//誰false返...(→使用失敗)

		// 召喚優先。
		} else if($skill["priority"] == "Summon") {
			foreach($target_list as $key => $char) {
				if($char->summon)//召喚
					$target[]	= &$target_list[$key];//召喚
			}
			if($target)
				return $target[array_rand($target)];//召喚中
			else
				return false;//誰false返...(→使用失敗)

		// 中
		} else if($skill["priority"] == "Charge") {
			foreach($target_list as $key => $char) {
				if($char->expect)
					$target[]	= &$target_list[$key];
			}
			if($target)
				return $target[array_rand($target)];
			else
				return false;//誰false返...(→使用失敗)
		//
		}

		//以外()
		foreach($target_list as $key => $char) {
			if($char->STATE != DEAD)//以外
				$target[]	= &$target_list[$key];//者
		}
		return $target[array_rand($target)];//誰一人
	}
//////////////////////////////////////////////////
//	次行動誰(又、詠唱中魔法發動誰)
//	返
	function &NextActer() {
		// 最大人探
		foreach($this->team0 as $key => $char) {
			if($char->STATE === 1) continue;
			// 最初誰最初人。
			if(!isset($delay)) {
				$delay	= $char->delay;
				$NextChar	= &$this->team0["$key"];
				continue;
			}
			// 今多交代
			if($delay <= $char->delay) {//行動
				// 同50%交代
				if($delay == $char->delay) {
					if(mt_rand(0,1))
						continue;
				}
				$delay	= $char->delay;
				$NextChar	= &$this->team0["$key"];
			}
		}
		// ↑同。
		foreach($this->team1 as $key => $char) {
			if($char->STATE === 1) continue;
			if($delay <= $char->delay) {//行動
				if($delay == $char->delay) {
					if(mt_rand(0,1))
						continue;
				}
				$delay	= $char->delay;
				$NextChar	= &$this->team1["$key"];
			}
		}
		// 全員減少
		$dif	= $this->delay - $NextChar->delay;//戰鬥基本行動者差分
		if($dif < 0)//差分0以下…
			return $NextChar;
		foreach($this->team0 as $key => $char) {
			$this->team0["$key"]->Delay($dif);
		}
		foreach($this->team1 as $key => $char) {
			$this->team1["$key"]->Delay($dif);
		}
		/*// 出。
		if(!is_object($NextChar)) {
			print("AAA");
			dump($NextChar);
			print("BBB");
		}
		*/

		return $NextChar;
	}
//////////////////////////////////////////////////
//	次行動誰(又、詠唱中魔法發動誰)
//	返
	function &NextActerNew() {

		// 次行動最距離短人探。
		$nextDis	= 1000;
		foreach($this->team0 as $key => $char) {
			if($char->STATE === DEAD) continue;
			$charDis	= $this->team0[$key]->nextDis();
			if($charDis == $nextDis) {
				$NextChar[]	= &$this->team0["$key"];
			} else if($charDis <= $nextDis) {
				$nextDis	= $charDis;
				$NextChar	= array(&$this->team0["$key"]);
			}
		}

		// ↑同。
		foreach($this->team1 as $key => $char) {
			if($char->STATE === DEAD) continue;
			$charDis	= $this->team1[$key]->nextDis();
			if($charDis == $nextDis) {
				$NextChar[]	= &$this->team1["$key"];
			} else if($charDis <= $nextDis) {
				$nextDis	= $charDis;
				$NextChar	= array(&$this->team1["$key"]);
			}
		}

		// 全員減少 //////////////////////

		//差分0以下
		if($nextDis < 0) {
			if(is_array($NextChar)) {
				return $NextChar[array_rand($NextChar)];
			} else
				return $NextChar;
		}

		foreach($this->team0 as $key => $char) {
			$this->team0["$key"]->Delay($nextDis);
		}
		foreach($this->team1 as $key => $char) {
			$this->team1["$key"]->Delay($nextDis);
		}
		// 出。
		/*
		if(!is_object($NextChar)) {
			print("AAA");
			dump($NextChar);
			print("BBB");
		}
		*/

		if(is_array($NextChar))
			return $NextChar[array_rand($NextChar)];
		else
			return $NextChar;
	}
//////////////////////////////////////////////////
//	全員行動初期化(=SPD)
	function DelayResetAll() {
		if(DELAY_TYPE === 0 || DELAY_TYPE === 1)
		{
			foreach($this->team0 as $key => $char) {
				$this->team0["$key"]->DelayReset();
			}
			foreach($this->team1 as $key => $char) {
				$this->team1["$key"]->DelayReset();
			}
		}
	}
//////////////////////////////////////////////////
//	計算設定
//	誰SPD變化場合呼直
//	*** 技使用等SPD變化際呼出 ***
	function SetDelay() {
		if(DELAY_TYPE === 0)
		{
			//SPD最大值合計求
			foreach($this->team0 as $key => $char) {
				$TotalSPD	+= $char->SPD;
				if($MaxSPD < $char->SPD)
					$MaxSPD	= $char->SPD;
			}
			//dump($this->team0);
			foreach($this->team1 as $char) {
				$TotalSPD	+= $char->SPD;
				if($MaxSPD < $char->SPD)
					$MaxSPD	= $char->SPD;
			}
			//平均SPD
			$AverageSPD	= $TotalSPD/( count($this->team0) + count($this->team1) );
			//基準delay
			$AveDELAY	= $AverageSPD * DELAY;
			$this->delay	= $MaxSPD + $AveDELAY;//戰鬥基準
			$this->ChangeDelay	= false;//false每回DELAY計算直。
		}
			else if(DELAY_TYPE === 1)
		{
		}
	}
//////////////////////////////////////////////////
//	戰鬥基準再計算。
//	使場所、技使用SPD變化際使。
//	class.skill_effect.php 使用。
	function ChangeDelay(){
		if(DELAY_TYPE === 0)
		{
			$this->ChangeDelay	= true;
		}
	}
//////////////////////////////////////////////////
//	名前設定
	function SetTeamName($name1,$name2) {
		$this->team0_name	= $name1;
		$this->team1_name	= $name2;
	}
//////////////////////////////////////////////////
//	戰鬥開始時平均合計HP等計算?表示
//	戰鬥經緯一表構成
	function BattleHeader() {
		foreach($this->team0 as $char) {//0
			$team0_total_lv	+= $char->level;//合計LV
			$team0_total_hp	+= $char->HP;//合計HP
			$team0_total_maxhp	+= $char->MAXHP;//合計最大HP
		}
		$team0_avelv	= round($team0_total_lv/count($this->team0)*10)/10;//0平均LV
		$this->team0_ave_lv	= $team0_avelv;
		foreach($this->team1 as $char) {//1
			$team1_total_lv	+= $char->level;
			$team1_total_hp	+= $char->HP;
			$team1_total_maxhp	+= $char->MAXHP;
		}
		$team1_avelv	= round($team1_total_lv/count($this->team1)*10)/10;
		$this->team1_ave_lv	= $team1_avelv;
		if($this->UnionBattle) {
			$team1_total_hp		= '????';
			$team1_total_maxhp	= '????';
		}
		?>
<table style="width:100%;" cellspacing="0"><tbody>
<tr><td class="teams"><div class="bold"><?php print $this->team1_name?></div>
總級別 : <?php print $team1_total_lv?><br>
平均級別 : <?php print $team1_avelv?><br>
總HP : <?php print $team1_total_hp?>/<?php print $team1_total_maxhp?>
</td><td class="teams ttd1"><div class="bold"><?php print $this->team0_name?></div>
總級別 : <?php print $team0_total_lv?><br>
平均級別 : <?php print $team0_avelv?><br>
總HP : <?php print $team0_total_hp?>/<?php print $team0_total_maxhp?>
</td></tr><?php 
	}
//////////////////////////////////////////////////
//	戰鬥終了時表示
	function BattleFoot() {
	/*	print("<tr><td>");
		dump($this->team0);
		print("</td></tr>");*/
		?>
</tbody></table>
<?php 
	}
//////////////////////////////////////////////////
//	戰鬥畫像?各殘HP殘SP等表示
	function BattleState() {
		static $last;
		if($last !== $this->actions)
			$last	= $this->actions;
		else
			return false;

		print("<tr><td colspan=\"2\" class=\"btl_img\">\n");
		// 戰鬥順自動
		print("<a name=\"s".$this->Scroll."\"></a>\n");
		print("<div style=\"width:100%;hight:100%;position:relative;\">\n");
		print('<div style="position:absolute;bottom:0px;right:0px;">'."\n");
		if($this->Scroll)
			print("<a href=\"#s".($this->Scroll - 1)."\"><<</a>\n");
		else
			print("<<" );
		print("<a href=\"#s".(++$this->Scroll)."\">>></a>\n");
		print('</div>');

		switch(BTL_IMG_TYPE) {
			case 0:
				print('<div style="text-align:center">');
				$this->ShowGdImage();//畫像
				print('</div>');
				break;
			case 1:
			case 2:
				$this->ShowCssImage();//畫像
				break;
		}
		print("</div>");
		print("</td></tr><tr><td class=\"ttd2 break\">\n");

		print("<table style=\"width:100%\"><tbody><tr><td style=\"width:50%\">\n");// team1-backs

		// 	左側後衛
		foreach($this->team1 as $char) {
			// 召喚死亡場合飛
			if($char->STATE === DEAD && $char->summon == true)
				continue;

			if($char->POSITION != FRONT)
				$char->ShowHpSp();
		}

		// 	左側前衛
		print("</td><td style=\"width:50%\">\n");
		foreach($this->team1 as $char) {
			// 召喚死亡場合飛
			if($char->STATE === DEAD && $char->summon == true)
				continue;

			if($char->POSITION == FRONT)
				$char->ShowHpSp();
		}

		print("</td></tr></tbody></table>\n");

		print("</td><td class=\"ttd1 break\">\n");

		// 	右側前衛
		print("<table style=\"width:100%\"><tbody><tr><td style=\"width:50%\">\n");
		foreach($this->team0 as $char) {
			// 召喚死亡場合飛
			if($char->STATE === DEAD && $char->summon == true)
				continue;
			if($char->POSITION == FRONT)
				$char->ShowHpSp();
		}

		// 	右側後衛
		print("</td><td style=\"width:50%\">\n");
		foreach($this->team0 as $char) {
			// 召喚死亡場合飛
			if($char->STATE === DEAD && $char->summon == true)
				continue;
			if($char->POSITION != FRONT)
				$char->ShowHpSp();
		}
		print("</td></tr></tbody></table>\n");

		print("</td></tr>\n");
	}
//////////////////////////////////////////////////
//	戰鬥畫像(畫像)
	function ShowGdImage() {
		$url	= BTL_IMG."?";

		// HP=0 畫像(擴張子取)
		$DeadImg	= substr(DEAD_IMG,0,strpos(DEAD_IMG,"."));

		//1
		$f	= 1;
		$b	= 1;//前衛數?後衛數初期化
		foreach($this->team0 as $char) {
			//畫像設定畫像擴張子名前
			if($char->STATE === 1)
				$img	= $DeadImg;
			else
				$img	= substr($char->img,0,strpos($char->img,"."));
			if($char->POSITION == "front")://前衛
				$url	.= "f2{$f}=$img&";
				$f++;
			else:
				$url	.= "b2{$b}=$img&";//後衛
				$b++;
			endif;
		}
		//0
		$f	= 1;
		$b	= 1;
		foreach($this->team1 as $char) {
			if($char->STATE === 1)
				$img	= $DeadImg;
			else
				$img	= substr($char->img,0,strpos($char->img,"."));
			if($char->POSITION == "front"):
				$url	.= "f1{$f}=$img&";
				$f++;
			else:
				$url	.= "b1{$b}=$img&";
				$b++;
			endif;
		}
		print('<img src="'.$url.'">');// ←表示
	}
//////////////////////////////////////////////////
//	CSS戰鬥畫面
	function ShowCssImage() {
		include_once(BTL_IMG_CSS);
		$img	= new cssimage();
		$img->SetBackGround($this->BackGround);
		$img->SetTeams($this->team1,$this->team0);
		$img->SetMagicCircle($this->team1_mc, $this->team0_mc);
		if(BTL_IMG_TYPE == 2)
			$img->NoFlip();// CSS畫像反轉無
		$img->Show();
	}
//////////////////////////////////////////////////
//	金得、一時的變數保存。
//	class內作
	function GetMoney($money,$team) {
		if(!$money) return false;
		$money	= ceil($money * MONEY_RATE);
		if($team === $this->team0) {
			print("{$this->team0_name} 獲得 ".MoneyFormat($money).".<br />\n");
			$this->team0_money	+= $money;
		} else if($team === $this->team1) {
			print("{$this->team1_name} 獲得 ".MoneyFormat($money).".<br />\n");
			$this->team1_money	+= $money;
		}
	}
//////////////////////////////////////////////////
//	得合計金額渡
	function ReturnMoney() {
		return array($this->team0_money,$this->team1_money);
	}

//////////////////////////////////////////////////
//	全體死者數數...(使?)
	function CountDeadAll() {
		$dead	= 0;
		foreach($this->team0 as $char) {
			if($char->STATE === DEAD)
				$dead++;
		}
		foreach($this->team1 as $char) {
			if($char->STATE === DEAD)
				$dead++;
		}
		return $dead;
	}

//////////////////////////////////////////////////
//	指定死者數數(指定)使?
	function CountDead($VarChar) {
		$dead	= 0;

		if($VarChar->team == TEAM_0) {
		//	print("A".$VarChar->team."<br>");
			$Team	= $this->team0;
		} else {
			//print("B".$VarChar->team);
			$Team	= $this->team1;
		}

		foreach($Team as $char) {
			if($char->STATE === DEAD) {
				$dead++;
			} else if($char->SPECIAL["Undead"] == true) {
				//print("C".$VarChar->Name()."/".count($Team)."<br>");
				$dead++;
			}
		}
		return $dead;
	}
//////////////////////////////////////////////////
//	魔方陣追加
	function MagicCircleAdd($team,$amount) {
		if($team == TEAM_0) {
			$this->team0_mc	+= $amount;
			if(5 < $this->team0_mc)
				$this->team0_mc	= 5;
			return true;
		} else {
			$this->team1_mc	+= $amount;
			if(5 < $this->team1_mc)
				$this->team1_mc	= 5;
			return true;
		}
	}
//////////////////////////////////////////////////
//	魔方陣削除
	function MagicCircleDelete($team,$amount) {
		if($team == TEAM_0) {
			if($this->team0_mc < $amount)
				return false;
			$this->team0_mc	-= $amount;
			return true;
		} else {
			if($this->team1_mc < $amount)
				return false;
			$this->team1_mc	-= $amount;
			return true;
		}
	}
// end of class. /////////////////////////////////////////////////////
}

//////////////////////////////////////////////////
//	生存者數數返
function CountAlive($team) {
	$no	= 0;//初期化
	foreach($team as $char) {
		if($char->STATE !== 1)
			$no++;
	}
	return $no;
}

//////////////////////////////////////////////////
//	初期生存數數返
function CountAliveChars($team) {
	$no	= 0;//初期化
	foreach($team as $char) {
		if($char->STATE === 1)
			continue;
		if($char->monster)
			continue;
		$no++;
	}
	return $no;
}
//////////////////////////////////////////////////
//	召還系呼。
	function CreateSummon($no,$strength=false) {
		include_once(DATA_MONSTER);
		$monster	= CreateMonster($no,1);

		$monster["summon"]	= true;
		// 召喚強化。
		if($strength) {
			$monster["maxhp"]	= round($monster["maxhp"]*$strength);
			$monster["hp"]	= round($monster["hp"]*$strength);
			$monster["maxsp"]	= round($monster["maxsp"]*$strength);
			$monster["sp"]	= round($monster["sp"]*$strength);
			$monster["str"]	= round($monster["str"]*$strength);
			$monster["int"]	= round($monster["int"]*$strength);
			$monster["dex"]	= round($monster["dex"]*$strength);
			$monster["spd"]	= round($monster["spd"]*$strength);
			$monster["luk"]	= round($monster["luk"]*$strength);

			$monster["atk"]["0"]	= round($monster["atk"]["0"]*$strength);
			$monster["atk"]["1"]	= round($monster["atk"]["1"]*$strength);
		}

		$monster	= new monster($monster);
		$monster->SetBattleVariable();
		return $monster;
	}
//////////////////////////////////////////////////
//	複數判斷要素判定
//function MultiFactJudge($Keys,$char,$MyTeam,$EnemyTeam) {
function MultiFactJudge($Keys,$char,$classBattle) {
	foreach($Keys as $no) {

		//$return	= DecideJudge($no,$char,$MyTeam,$EnemyTeam);
		$return	= DecideJudge($no,$char,$classBattle);

		// 判定否場合終了。
		if(!$return)
			return false;

		// 配列比較共通項目殘(廢止方向)
		/*
		if(!$compare && is_array($return))
			$compare	= $return;
		else if(is_array($return))
			$compare	= array_intersect($intersect,$return);
		*/

	}

	/*
	if($compare == array())
		$compare	= true;
	return $compare;
	*/
	return true;
}
?>
