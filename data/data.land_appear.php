<?php
/**
 * 可进入地图列表生成函数
 * 
 * 功能说明：
 * 1. 根据用户拥有的道具和当前时间生成可进入的地图列表
 * 2. 结合无条件地图、道具解锁地图和时间限定地图
 * 
 * 参数说明：
 * @param object $user 用户对象
 *   - 要求包含item属性：用户道具数组（道具ID => 数量）
 * 
 * 返回说明：
 * @return array 可进入的地图标识符数组
 * 
 * 地图解锁规则：
 * 1. 无条件地图：
 *    - "gb0", "gb1", "gb2" 始终可用
 * 
 * 2. 道具解锁地图：
 *    - 道具8000解锁"ac0"（古之洞穴）
 *    - 道具8001解锁"ac1"（古之洞穴B2）
 *    - 道具8002解锁"ac2"（古之洞穴B3）
 *    - 道具8003解锁"ac3"（古之洞穴B4）
 *    - 道具8004解锁"ac4"（古之洞穴B5）
 *    - 道具8009解锁"snow0"（滴冻入口）
 *    - 道具8010解锁"snow1"（滴冻中腹）
 *    - 道具8011解锁"snow2"（滴冻顶上）
 * 
 * 3. 时间限定地图：
 *    - "horh"（天堂或地狱）仅在凌晨2点且分钟数以5开头时出现（如02:50）
 * 
 * 注意事项：
 * 1. 函数返回的地图标识符需与LandInformation函数兼容
 * 2. 部分地图在代码中被注释（如海洋、沙漠等），可根据需要启用
 * 3. 时间限定地图基于服务器时间（date("H")和date("i")）
 * 
 * 示例返回：
 *   ["gb0", "gb1", "gb2", "ac0", "snow0"]（当用户拥有道具8000和8009时）
 */
function LoadMapAppear($user) {
	$land	= array();
	// 无条件的
	array_push($land,"gb0","gb1","gb2");
	// 需要携带地图道具或特殊条件才出现的
	if($user->item["8000"])
		array_push($land,"ac0");
	if($user->item["8001"])
		array_push($land,"ac1");
	if($user->item["8002"])
		array_push($land,"ac2");
	if($user->item["8003"])
		array_push($land,"ac3");
	if($user->item["8004"])
		array_push($land,"ac4");
	if($user->item["8009"])
		array_push($land,"snow0");
	if($user->item["8010"])
		array_push($land,"snow1");
	if($user->item["8011"])
		array_push($land,"snow2");
	/*
	array_push($land,"sea0");
	array_push($land,"sea1");
	array_push($land,"ocean0");
	array_push($land,"sand0");
	array_push($land,"swamp0");
	array_push($land,"swamp1");
	array_push($land,"mt0");
	array_push($land,"volc0");
	array_push($land,"volc1");
	*/
	if(date("H") == 2 && substr(date("i"),0,1)==5)
		array_push($land,"horh");
	return $land;
}
?>