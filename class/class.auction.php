<?php

/**
 * 游戏拍卖系统核心类
 * 
 * 功能说明：
 * 1. 实现游戏内物品拍卖的全流程管理
 * 2. 支持拍卖品的上架、竞标、结算等核心功能
 * 3. 提供拍卖日志记录和数据分析
 * 
 * 主要功能模块：
 * 1. 拍卖品管理：
 *    - 拍卖品上架与展示
 *    - 竞标流程控制
 *    - 自动结算到期拍卖
 * 2. 用户管理：
 *    - 买卖双方资金处理
 *    - 物品所有权转移
 *    - 用户数据临时缓存
 * 3. 数据持久化：
 *    - 拍卖数据实时保存
 *    - 操作日志记录
 *    - 数据变更检测
 * 
 * 系统特性：
 * 1. 支持两种拍卖类型：物品拍卖(目前实现)和角色拍卖(预留)
 * 2. 智能竞标系统：
 *    - 最低出价自动计算
 *    - 出价后自动延长拍卖时间
 *    - 多用户竞标处理
 * 3. 安全机制：
 *    - IP限制防止恶意操作
 *    - 敏感操作验证
 *    - 数据变更追踪
 * 4. 数据可视化：
 *    - 多种排序方式
 *    - 详细拍卖日志
 *    - 实时倒计时显示
 * 
 * 使用流程：
 * 1. 初始化拍卖类型(Auction::__construct)
 * 2. 加载拍卖数据(Auction::ItemArticleRead)
 * 3. 处理用户操作(竞标/上架)
 * 4. 定期检查到期拍卖(Auction::ItemCheckSuccess)
 * 5. 保存数据变更(Auction::ItemSaveData)
 * 
 * 注意事项：
 * 1. 需配合用户系统(user类)使用
 * 2. 依赖游戏数据文件(DATA_ITEM)
 * 3. 使用文件锁保证数据一致性
 */

class Auction
{
	var $fp;
	var $AuctionType;
	var $ArticleNo;
	var $Article = array();
	var $UserName;
	var $TempUser	= array();
	var $AuctionLog;
	var $DataChange	= false;
	var $QUERY;
	var $sort;

	function __construct($type)
    {
        if ($type == "item") {
            $this->AuctionType = "item";
            $this->ItemArticleRead();
        } else if ($type == "char") {
            $this->AuctionType = "char";
        }
    }
	
	function AuctionHttpQuery($name)
	{
		$this->QUERY	= $name;
	}
	function ItemCheckSuccess()
	{
		$Now	= time();
		foreach ($this->Article as $no => $Article) {
			if (AuctionLeftTime($Now, $Article["end"]))
				continue;
			if (!function_exists("LoadItemData"))
				include(DATA_ITEM);
			$item	= LoadItemData($Article["item"]);
			if ($Article["bidder"]) {
				$this->UserGetItem($Article["bidder"], $Article["item"], $Article["amount"]);
				$this->UserGetMoney($Article["exhibitor"], $Article["price"]);
				$this->AddLog("No.{$Article["No"]} <img src=\"" . IMG_ICON . $item["img"] . "\"><span class=\"bold\">{$item["name"]} x{$Article["amount"]}</span>个 " . $this->UserGetNameFromTemp($Article["bidder"]) . "" . MoneyFormat($Article["price"]) . " <span class=\"recover\">中标。</span>");
			} else {
				$this->UserGetItem($Article["exhibitor"], $Article["item"], $Article["amount"]);
				$this->AddLog("No.{$Article["No"]} <img src=\"" . IMG_ICON . $item["img"] . "\"><span class=\"bold\">{$item["name"]} x{$Article["amount"]}</span>个<span class=\"dmg\">流标。</span>");
			}
			unset($this->Article["$no"]);
			$this->DataChange	= true;
		}
	}
	function UserGetNameFromTemp($UserID)
	{
		if ($this->TempUser["$UserID"]["Name"])
			return $this->TempUser["$UserID"]["Name"];
		else
			return "-";
	}
	function UserGetMoney($UserID, $Money)
	{
		if (!$this->TempUser["$UserID"]["user"]) {
			$this->TempUser["$UserID"]["user"]	= new user($UserID);
			$this->TempUser["$UserID"]["Name"]	= $this->TempUser["$UserID"]["user"]->Name();
		}

		$this->TempUser["$UserID"]["UserGetTotalMoney"]	+= $Money;
		$this->TempUser["$UserID"]["Money"]	= true;
	}
	function UserGetItem($UserID, $item, $amount)
	{
		if (!$this->TempUser["$UserID"]["user"]) {
			$this->TempUser["$UserID"]["user"]	= new user($UserID);
			$this->TempUser["$UserID"]["Name"]	= $this->TempUser["$UserID"]["user"]->Name();
		}

		$this->TempUser["$UserID"]["UserGetItem"]["$item"]	+= $amount;
		$this->TempUser["$UserID"]["item"]	= true;
	}
	function UserGetChar($UserID, $char)
	{
		$this->TempUser["$UserID"]["char"][]	= $char;
		$this->TempUser["$UserID"]["CharAdd"]	= true;
	}
	function UserSaveData()
	{
		foreach ($this->TempUser as $user => $Result) {
			if ($this->TempUser["$user"]["Money"]) {
				$this->TempUser["$user"]["user"]->GetMoney($this->TempUser["$user"]["UserGetTotalMoney"]);
				$this->TempUser["$user"]["user"]->SaveData();
			}
			if ($this->TempUser["$user"]["item"]) {
				foreach ($this->TempUser["$user"]["UserGetItem"] as $itemNo => $amount) {
					$this->TempUser["$user"]["user"]->AddItem($itemNo, $amount);
				}
				$this->TempUser["$user"]["user"]->SaveUserItem();
			}
			if ($this->TempUser["$user"]["CharAdd"]) {
				if ($this->TempUser["$user"]["char"]) {
					foreach ($this->TempUser["$user"]["char"] as $char) {
						$char->SaveCharData($user);
					}
				}
			}
			$this->TempUser["$user"]["user"]->fpCloseAll();
		}
		unset($this->TempUser);
	}
	function ItemBidRight($ArticleNo, $UserID)
	{
		if ($this->Article["$ArticleNo"]["bidder"] == $UserID)
			return false;
		if ($this->Article["$ArticleNo"]["exhibitor"] == $UserID)
			return false;
		return true;
	}
	function LoadUserName($id)
	{
		if ($this->UserName["$id"]) {
			return $this->UserName["$id"];
		} else {
			$User	= new user($id);
			$Name	= $User->Name();
			if ($Name) {
				$this->UserName["$id"]	= $Name;
			} else {
				$this->UserName["$id"]	= "-";
			}
			return $this->UserName["$id"];
		}
	}
	function ItemBottomPrice($ArticleNo)
	{
		if ($this->Article["$ArticleNo"]) {
			return BottomPrice($this->Article["$ArticleNo"]["price"]);
		}
	}
	function ItemBid($ArticleNo, $BidPrice, $Bidder, $BidderName)
	{
		if (!$Article	= $this->Article["$ArticleNo"])
			return false;
		$BottomPrice	= BottomPrice($this->Article["$ArticleNo"]["price"]);
		if ($Article["IP"] == $_SERVER["REMOTE_ADDR"]) {
			ShowError("IP制限.");
			return false;
		}
		if ("isMobile" == "i") {
			ShowError("mobile forbid.");
			return false;
		}
		if ($BidPrice < $BottomPrice)
			return false;
		if ($Article["bidder"]) {
			$this->UserGetMoney($Article["bidder"], $Article["price"]);
			$this->UserSaveData();
		}
		$Now	= time();
		$left	= AuctionLeftTime($Now, $Article["end"], true);
		if (0 < $left && $left < 901) {
			$dif	= 900 - $left;
			$this->Article["$ArticleNo"]["end"]	+= $dif;
		}
		$this->Article["$ArticleNo"]["price"]	= $BidPrice;
		$this->Article["$ArticleNo"]["TotalBid"]++;
		$this->Article["$ArticleNo"]["bidder"]	= $Bidder;
		$this->DataChange	= true;
		$item	= LoadItemData($Article["item"]);
		$this->AddLog("No." . $Article["No"] . " <span class=\"bold\">{$item["name"]} x{$Article["amount"]}</span>个 " . MoneyFormat($BidPrice) . "  " . $BidderName . " <span class=\"support\">出价。</span>");
		return true;
	}
	function ItemShowArticle($bidding = false)
	{
		if (count($this->Article) == 0) {
			print("无拍卖物(No auction)<br />\n");
			return false;
		} else {
			$Now	= time();
			$exp	= '<tr><td class="td9">编号</td><td class="td9">价格</td><td class="td9">投标人</td><td class="td9">出价数</td><td class="td9">其余</td>' .
				'<td class="td9">参展者</td><td class="td9"> 描述 </td></tr>' . "\n";
			print('<table style="width:725px;text-align:center" cellpadding="0" cellspacing="0" border="0">' . "{$exp}\n");
			foreach ($this->Article as $Article) {
				print("<tr><td class=\"td7\">");
				print($Article["No"]);
				print("</td><td class=\"td7\">");
				print(MoneyFormat($Article["price"]));
				print("</td><td class=\"td7\">");
				if (!$Article["bidder"])
					$bidder	= "-";
				else
					$bidder	= $this->LoadUserName($Article["bidder"]);
				print($bidder);
				print("</td><td class=\"td7\">");
				print($Article["TotalBid"]);
				print("</td><td class=\"td7\">");
				print(AuctionLeftTime($Now, $Article["end"]));
				print("</td><td class=\"td7\">");
				$exhibitor	= $this->LoadUserName($Article["exhibitor"]);
				print($exhibitor);
				print("</td><td class=\"td8\">");
				print($Article["comment"] ? $Article["comment"] : " ");
				print("</td></tr>\n");
				print('<tr><td colspan="7" style="text-align:left;padding-left:15px" class="td6">');
				$item	= LoadItemData($Article["item"]);
				print('<form action="?menu=auction" method="post">');
				if ($bidding) {
					print('<a href="#" onClick="Element.toggle(\'Bid' . $Article["No"] . '\';return false;)">招投标</a>');
					print('<span style="display:none" id="Bid' . $Article["No"] . '">');
					print(' <input type="text" name="BidPrice" style="width:80px" class="text" value="' . BottomPrice($Article["price"]) . '">');
					print('<input type="submit" value="出价" class="btn">');
					print('<input type="hidden" name="ArticleNo" value="' . $Article["No"] . '">');
					print('</span>');
				}
				print(ShowItemDetail($item, $Article["amount"], 1));
				print("</form>");
				print("</td></tr>\n");
			}
			print("{$exp}</table>\n");
			return true;
		}
	}
	function ItemShowArticle2($bidding = false)
	{
		if (count($this->Article) == 0) {
			print("无拍卖物(No auction)<br />\n");
			return false;
		} else {
			$Now	= time();
			if ($this->sort)
				${"Style_" . $this->sort}	= ' class="a0"';
			$exp	= '<tr><td class="td9"><a href="?menu=' . $this->QUERY . '&sort=no"' . $Style_no . '>No</a></td>' .
				'<td class="td9"><a href="?menu=' . $this->QUERY . '&sort=time"' . $Style_time . '>其余</td>' .
				'<td class="td9"><a href="?menu=' . $this->QUERY . '&sort=price"' . $Style_price . '>价格</a>' .
				'<br /><a href="?menu=' . $this->QUERY . '&sort=rprice"' . $Style_rprice . '>（登）</a></td>' .
				'<td class="td9">Item</td>' .
				'<td class="td9"><a href="?menu=' . $this->QUERY . '&sort=bid"' . $Style_bid . '>Bids</a></td>' .
				'<td class="td9">投标人</td><td class="td9">参展人</td></tr>' . "\n";
			print("所列项目总数:" . $this->ItemAmount() . "\n");
			print('<table style="width:725px;text-align:center" cellpadding="0" cellspacing="0" border="0">' . "\n");
			print($exp);
			foreach ($this->Article as $Article) {
				// 競売番号
				print("<tr><td rowspan=\"2\" class=\"td7\">");
				print($Article["No"]);
				// 終了時刻
				print("</td><td class=\"td7\">");
				print(AuctionLeftTime($Now, $Article["end"]));
				// 現在竞标价格
				print("</td><td class=\"td7\">");
				print(MoneyFormat($Article["price"]));
				// アイテム
				print('</td><td class="td7" style="text-align:left">');
				$item	= LoadItemData($Article["item"]);
				print(ShowItemDetail($item, $Article["amount"], 1));
				// 合計竞标数
				print("</td><td class=\"td7\">");
				print($Article["TotalBid"]);
				// 投标人
				print("</td><td class=\"td7\">");
				if (!$Article["bidder"])
					$bidder	= "-";
				else
					$bidder	= $this->LoadUserName($Article["bidder"]);
				print($bidder);
				// 参展人
				print("</td><td class=\"td8\">");
				$exhibitor	= $this->LoadUserName($Article["exhibitor"]);
				print($exhibitor);
				// コメント
				print("</td></tr><tr>");
				print("<td colspan=\"6\" class=\"td8\" style=\"text-align:left\">");
				print('<form action="?menu=auction" method="post">');
				// 竞标フォーム
				if ($bidding) {
					print('<a style="margin:0 10px" href="#" onClick="Element.toggle(\'Bid' . $Article["No"] . '\');return false;">竞标</a>');
					print('<span style="display:none" id="Bid' . $Article["No"] . '">');
					print(' <input type="text" name="BidPrice" style="width:80px" class="text" value="' . BottomPrice($Article["price"]) . '">');
					print('<input type="submit" value="出价" class="btn">');
					print('<input type="hidden" name="ArticleNo" value="' . $Article["No"] . '">');
					print('</span>');
				}
				print($Article["comment"] ? $Article["comment"] : " ");
				print("</form>");
				print("</td></tr>\n");
				print("</td></tr>\n");
			}
			print($exp);
			print("</table>\n");
			return true;
		}
	}
	//////////////////////////////////////////////////
	//	その番号の競売品が出品されているかたしかめる。
	function ItemArticleExists($no)
	{
		if ($this->Article["$no"]) {
			return true;
		} else {
			return false;
		}
	}
	//////////////////////////////////////////////
	//	アイテムを出品する
	function ItemAddArticle($item, $amount, $id, $time, $StartPrice, $comment)
	{
		// 終了時刻の計算
		$Now	= time();
		$end	= $Now + round($now + (60 * 60 * $time));
		// 開始价格のあれ
		if (preg_match('/^[0-9]/', $StartPrice)) {
			$price = (int)$StartPrice;
		} else {
			$price = 0;
		}
		// コメント処理
		$comment	= str_replace("\t", "", $comment);
		$comment	= htmlspecialchars(trim($comment), ENT_QUOTES);
		$comment	= stripslashes($comment);
		// 競売品番号
		$this->ArticleNo++;
		if (9999 < $this->ArticleNo)
			$this->ArticleNo	= 0;
		$New	= array(
			// 競売品番号
			"No"		=> $this->ArticleNo,
			// 終了時刻
			"end"		=> $end,
			// 今の竞标价格
			"price"		=> (int)$price,
			// 参展人id
			"exhibitor"	=> $id,
			// アイテム
			"item"		=> $item,
			// 個数
			"amount"	=> (int)$amount,
			// 合計竞标数
			"TotalBid"	=> 0,
			// 最終投标人id
			"bidder"	=> NULL,
			// 最終竞标時間(使ってない！？使いたければ使ってください)
			"latest"	=> NULL,
			// コメント
			"comment"	=> $comment,
			// IP
			"IP"	=> $_SERVER["REMOTE_ADDR"],
		);
		array_unshift($this->Article, $New);
		$itemData	= LoadItemData($item);
		$this->AddLog("No." . $this->ArticleNo . "  <img src=\"" . IMG_ICON . $itemData["img"] . "\"><span class=\"bold\">{$itemData["name"]} x{$amount}</span>个<span class=\"charge\"> 加入拍卖。</span>");
		$this->DataChange	= true;
	}
	//////////////////////////////////////////////
	//	オークションのデータを保存する
	function ItemSaveData()
	{
		if (!$this->DataChange) {
			fclose($this->fp);
			unset($this->fp);
			return false;
		}
		// アイテム オークションを保存する。
		$string	= $this->ArticleNo . "\n";
		foreach ($this->Article as $val) {
			//if(strlen($val["end"]) != 10) continue;
			$string	.=	$val["No"] .
				"<>" . $val["end"] .
				"<>" . $val["price"] .
				"<>" . $val["exhibitor"] .
				"<>" . $val["item"] .
				"<>" . $val["amount"] .
				"<>" . $val["TotalBid"] .
				"<>" . $val["bidder"] .
				"<>" . $val["latest"] .
				"<>" . $val["comment"] .
				"<>" . $val["IP"] . "\n";
		}
		//print($string);
		if (file_exists(AUCTION_ITEM) && $this->fp) {
			WriteFileFP($this->fp, $string, true);
			fclose($this->fp);
			unset($this->fp);
		} else {
			WriteFile(AUCTION_ITEM, $string, true);
		}
		$this->SaveLog();
	}
	//////////////////////////////////////////////
	function ItemSortBy($type)
	{
		switch ($type) {
			case "no":
				usort($this->Article, "ItemArticleSortByNo");
				$this->sort	= "no";
				break;
			case "time":
				usort($this->Article, "ItemArticleSortByTime");
				$this->sort	= "time";
				break;
			case "price":
				usort($this->Article, "ItemArticleSortByPrice");
				$this->sort	= "price";
				break;
			case "rprice":
				usort($this->Article, "ItemArticleSortByRPrice");
				$this->sort	= "rprice";
				break;
			case "bid":
				usort($this->Article, "ItemArticleSortByTotalBid");
				$this->sort	= "bid";
				break;
			default:
				usort($this->Article, "ItemArticleSortByTime");
				$this->sort	= "time";
				break;
		}
	}
	//////////////////////////////////////////////
	// アイテムオークション用のファイルを開いて
	// データを取り出し,格納
	function ItemArticleRead()
	{
		// ファイルがある場合
		if (file_exists(AUCTION_ITEM)) {
			//$fp	= fopen(AUCTION_ITEM,"r+");
			$this->fp	= FileLock(AUCTION_ITEM);
			//if(!$fp) return false;
			//flock($fp,LOCK_EX);
			// 競売番号を先読みする
			$this->ArticleNo	= trim(fgets($this->fp));
			while (!feof($this->fp)) {
				$str	= fgets($this->fp);
				if (!$str) continue;
				$article = explode("<>", $str);
				if (strlen($article["1"]) != 10) continue;
				$this->Article[$article["0"]]	= array(
					"No"		=> $article["0"], // 競売番号
					"end"		=> $article["1"], // 終了時刻
					"price"		=> $article["2"], // 今の竞标价格
					"exhibitor"	=> $article["3"], // 参展人id
					"item"		=> $article["4"], // アイテム
					"amount"	=> $article["5"], // 個数
					"TotalBid"	=> $article["6"], // 合計竞标数
					"bidder"	=> $article["7"], // 最終投标人id
					"latest"	=> $article["8"], // 最終竞标時間
					"comment"	=> trim($article["9"]), // コメント
					"IP"	=> trim($article["10"]), // IP
				);
			}
			// ファイルが無い場合
		} else {
			// 何もしない。
		}
	}
	//////////////////////////////////////////////////
	//	出品物の数
	function ItemAmount()
	{
		return count($this->Article);
	}
	//////////////////////////////////////////////////
	//	オークション経過ログを読む
	function LoadLog()
	{
		if ($this->AuctionType == "item") {
			if (!file_exists(AUCTION_ITEM_LOG)) {
				$this->AuctionLog	= array();
				return false;
			}
			$fp	= fopen(AUCTION_ITEM_LOG, "r+");
			if (!$fp) return false;
			flock($fp, LOCK_EX);
			while (!feof($fp)) {
				$str	= trim(fgets($fp));
				if (!$str) continue;
				$this->AuctionLog[]	= $str;
			}
		}
	}
	//////////////////////////////////////////////////
	//	オークション経過ログの保存
	function SaveLog()
	{
		if ($this->AuctionType == "item") {
			if (!$this->AuctionLog)
				return false;
			// 30行以下に収める
			while (100 < count($this->AuctionLog)) {
				array_pop($this->AuctionLog);
			}
			foreach ($this->AuctionLog as $log) {
				$string	.= $log . "\n";
			}
			WriteFile(AUCTION_ITEM_LOG, $string);
		}
	}
	//////////////////////////////////////////////////
	//	ログの表示
	function ShowLog()
	{
		if (!$this->AuctionLog)
			$this->LoadLog();
		if (!$this->AuctionLog)
			return false;
		foreach ($this->AuctionLog as $log) {
			print("{$log}<br />\n");
		}
	}
	//////////////////////////////////////////////////
	//	ログの追加
	function AddLog($string)
	{
		if (!$this->AuctionLog)
			$this->LoadLog();
		if (!$this->AuctionLog)
			$this->AuctionLog	= array();
		array_unshift($this->AuctionLog, $string);
	}
}

//////////////////////////////////////////////////
//  辅助函数区域
//////////////////////////////////////////////////

/**
 * 按拍卖品编号排序
 * @param array $a 拍卖品A
 * @param array $b 拍卖品B
 * @return int 排序结果
 */
function ItemArticleSortByNo($a, $b)
{
	if ($a["No"] == $b["No"])
		return 0;
	return ($a["No"] > $b["No"]) ? 1 : -1;
}

/**
 * 按剩余时间排序
 * @param array $a 拍卖品A
 * @param array $b 拍卖品B
 * @return int 排序结果
 */
function ItemArticleSortByTime($a, $b)
{
	if ($a["end"] == $b["end"])
		return 0;
	return ($a["end"] > $b["end"]) ? 1 : -1;
}

/**
 * 按价格降序排序
 * @param array $a 拍卖品A
 * @param array $b 拍卖品B
 * @return int 排序结果
 */
function ItemArticleSortByPrice($a, $b)
{
	if ($a["price"] == $b["price"])
		return 0;
	return ($a["price"] > $b["price"]) ? -1 : 1;
}

/**
 * 按价格升序排序
 * @param array $a 拍卖品A
 * @param array $b 拍卖品B
 * @return int 排序结果
 */
function ItemArticleSortByRPrice($a, $b)
{
	if ($a["price"] == $b["price"])
		return 0;
	return ($a["price"] > $b["price"]) ? 1 : -1;
}

/**
 * 按竞标数降序排序
 * @param array $a 拍卖品A
 * @param array $b 拍卖品B
 * @return int 排序结果
 */
function ItemArticleSortByTotalBid($a, $b)
{
	if ($a["TotalBid"] == $b["TotalBid"])
		return 0;
	return ($a["TotalBid"] > $b["TotalBid"]) ? -1 : 1;
}

/**
 * 计算拍卖剩余时间
 * @param int $now 当前时间戳
 * @param int $end 结束时间戳
 * @param bool $int 是否返回原始差值
 * @return mixed 格式化的时间字符串或原始时间差
 */
function AuctionLeftTime($now, $end, $int = false)
{
	$left	= $end - $now;
	// $int=true なら差分だけ返す
	if ($int)
		return $left;
	if ($left < 1) { // 終了している場合はfalse
		return false;
	}
	if ($left < 601) {
		return "{$left}秒";
	} else if ($left < 3601) {
		$minutes	= floor($left / 60);
		return "{$minutes}分";
	} else {
		$hour	= floor($left / 3600);
		$minutes	= floor(($left % 3600) / 60);
		return "{$hour}小时$minutes}分";
	}
}

/**
 * 计算最低出价
 * @param int $price 当前价格
 * @return float 最低出价金额
 */
function BottomPrice($price)
{
	$bottom	= floor($price * 0.10);
	if ($bottom < 101)
		return sprintf("%0.0f", $price + 100);
	else
		return sprintf("%0.0f", $price + $bottom);
}
