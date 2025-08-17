<?php

/**
 * 游戏物品锻造系统类
 * 
 * 功能说明：
 * 1. 实现游戏内物品锻造和精炼系统
 * 2. 管理物品属性、附加能力和精炼等级
 * 3. 处理物品创建、精炼和属性增强
 * 
 * 主要功能模块：
 * 1. 物品解析：
 *    - 解析物品编号结构
 *    - 提取基础属性、精炼等级和附加能力
 * 2. 物品创建：
 *    - 随机生成物品附加能力
 *    - 区分低级和高级附加属性
 * 3. 精炼系统：
 *    - 精炼成功率计算
 *    - 精炼等级限制
 *    - 精炼失败处理
 * 
 * 技术特点：
 * 1. 物品编码系统：
 *    - 使用特定编码结构存储物品属性
 *    - 基础编号(4位)+精炼等级(2位)+附加能力(3×3位)
 * 2. 概率系统：
 *    - 附加能力生成概率控制
 *    - 精炼成功率递减机制
 * 3. 类型限制：
 *    - 特定类型物品才能精炼
 *    - 精炼等级上限控制
 * 
 * 物品编码结构：
 * 1. 基础编号 (4位)：物品唯一标识
 * 2. 精炼等级 (2位)：0-99级精炼
 * 3. 附加能力 (3组×3位)：特殊属性加成
 * 
 * 特殊机制：
 * 1. 附加能力生成：
 *    - 分低级和高级属性池
 *    - 随机选择属性组合
 * 2. 精炼系统：
 *    - 精炼等级越高成功率越低
 *    - 精炼失败无惩罚
 * 3. 特殊附加槽：
 *    - 独立添加特殊属性
 * 
 * 使用注意事项：
 * 1. 物品类型限制：
 *    - 只有特定类型物品可精炼
 *    - 精炼等级上限由REFINE_LIMIT常量控制
 * 2. 概率机制：
 *    - 附加能力生成概率为1/3(低)、1/3(高)、1/3(双)
 *    - 精炼成功率随等级提升而下降
 * 3. 物品编码：
 *    - 使用ReturnItem()获取完整物品编码
 * 
 * 使用流程：
 * 1. 初始化Item对象（传入物品编号）
 * 2. 创建新物品（CreateItem）
 * 3. 添加特殊属性（可选，AddSpecial）
 * 4. 精炼物品（ItemRefine）
 * 5. 获取最终物品编码（ReturnItem）
 */

class Item
{
	var $item;

	var $base, $refine;
	var $option0, $option1, $option2;

	var $type;

	function Item($no)
	{
		mt_srand();
		$this->SetItem($no);
	}
	//////////////////////////////////////////////////
	//	アイテムが渡された場合データを解析する?
	function SetItem($no)
	{
		if (!$no) return false;
		$this->item	= $no;

		$this->base	= substr($no, 0, 4); //アイテムの基本番号
		// 精錬値
		$this->refine	= (int)substr($no, 4, 2);
		if (!$this->refine)
			$this->refine	= 0;
		// 付加能力
		$this->option0	= substr($no, 6, 3);
		$this->option1	= substr($no, 9, 3);
		$this->option2	= substr($no, 12, 3);

		if ($item = LoadItemData($this->base)) {
			$this->type	= $item["type"];
		}
	}
	//////////////////////////////////////////////////
	//	アイテムを製作する。
	function CreateItem()
	{
		$this->refine	= false;
		$this->option0	= false;
		$this->option1	= false;
		$this->option2	= false;
		list($low, $high)	= ItemAbilityPossibility($this->type);

		// 2:3:4
		// 付加能力がつく確率。
		$prob	= mt_rand(1, 9);
		switch ($prob) {
			case 1:
			case 2:
			case 3:
				$AddLow	= true;
				break;
			case 4:
			case 5:
			case 6:
				$AddHigh	= true;
				break;
			case 7:
			case 8:
			case 9:
				$AddLow	= true;
				$AddHigh	= true;
				break;
		}

		// array_rand() は微妙なので敬遠する。

		if ($AddHigh) {
			$prob	= mt_rand(0, count($high) - 1);
			$this->option1	= $high["$prob"];
		}
		if ($AddLow) {
			$prob	= mt_rand(0, count($low) - 1);
			$this->option2	= $low["$prob"];
		}
	}
	//////////////////////////////////////////////////
	//	特殊なあれ？3番目の付加？
	function AddSpecial($opt)
	{
		$this->option0	= $opt;
	}
	//////////////////////////////////////////////////
	//	精錬可能な物かどうか。
	function CanRefine()
	{
		$possible	= CanRefineType();
		if (REFINE_LIMIT <= $this->refine)
			return false;
		else if (in_array($this->type, $possible))
			return true;
		else
			return false;
	}
	//////////////////////////////////////////////////
	//	精錬をする
	function ItemRefine()
	{
		if ($this->RefineProb($this->refine)) {
			print("+" . $this->refine . " -> ");
			$this->refine++;
			print("+" . $this->refine . " <span class=\"recover\">成功</span> !<br />\n");
			return true;
		} else {
			print("+" . $this->refine . " -> ");
			print("+" . ($this->refine + 1) . " <span class=\"dmg\">失败</span>.<br />\n");
			return false;
		}
	}
	//////////////////////////////////////////////////
	//	精錬度別に精錬成功か否かとその確率
	function RefineProb($now)
	{
		$prob	= mt_rand(0, 99);
		//return true;// コメント取ると成功率100%
		switch ($now) {
			case 0:
			case 1:
			case 2:
			case 3:
				return true;
			case 4:
				if ($prob < 60)
					return true;
			case 5:
				if ($prob < 40)
					return true;
			case 6:
				if ($prob < 40)
					return true;
			case 7:
				if ($prob < 20)
					return true;
			case 8:
				if ($prob < 20)
					return true;
			case 9:
				if ($prob < 10)
					return true;
		}
		return false;
	}
	//////////////////////////////////////////////////
	//	アイテムを返す。
	function ReturnItem()
	{
		// 精錬もオプションも無い場合は先頭4文字だけ返す。
		if (!$this->refine && !$this->option0 && !$this->option1 && !$this->option2)
			return $this->base;

		// 少なくとも精錬されているか、オプションが有る場合
		$item	= $this->base .
			sprintf("%02d", $this->refine) .
			$this->option0 .
			$this->option1 .
			$this->option2;
		return $item;
	}
}
