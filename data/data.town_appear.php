<?php
// 町でいける店とかの出現条件とか...
// 日付別でいける場所を変えれるとか、
// あるアイテムがないと行けないとかできる
// 別ファイルにする必要があったのかどうか微妙

/**
 * 城镇设施出现条件控制函数
 * 
 * 功能说明：
 * 1. 控制城镇中各类设施的出现条件
 * 2. 支持基于日期、物品持有状态等条件动态控制设施可用性
 * 3. 提供默认无条件开放的设施列表
 * 
 * 参数说明：
 * @param object $user 用户对象，包含用户当前状态信息
 *     - 可包含物品持有状态($user->item)、日期信息等条件数据
 * 
 * 返回说明：
 * @return array 可用设施列表数组
 *     - 键：设施名称（如"Shop"）
 *     - 值：布尔值，表示是否可用（true=可用）
 * 
 * 设施控制机制：
 * 1. 无条件开放设施（默认开放）：
 *     - Shop（商店）
 *     - Recruit（招募所）
 *     - Smithy（铁匠铺）
 *     - Auction（拍卖行）
 *     - Colosseum（竞技场）
 * 
 * 2. 条件开放设施（示例）：
 *     - 可基于用户物品持有状态控制（如：$user->item[特定物品ID]）
 *     - 可基于游戏日期/时间控制
 *     - 可基于任务进度控制
 * 
 * 设计特点：
 * - 模块化设计，便于扩展新设施
 * - 条件判断逻辑清晰，易于维护
 * - 返回结构简单，便于前端展示
 * 
 * 使用示例：
 * ```
 * $availablePlaces = TownAppear($currentUser);
 * if ($availablePlaces["SpecialShop"]) {
 *     // 显示特殊商店入口
 * }
 * ```
 */
function TownAppear($user)
{
	$place	= array();

	// 無条件で行ける
	$place["Shop"]	= true;
	$place["Recruit"]	= true;
	$place["Smithy"]	= true;
	$place["Auction"]	= true;
	$place["Colosseum"]	= true;

	// 特定のアイテムがないと行けない施設
	//if($user->item[****])
	//	$place["****"]	= true;

	return $place;
}
