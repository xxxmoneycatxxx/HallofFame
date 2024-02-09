<?php
// 町店出現條件...
// 日付別場所變、
// 行
// 別必要微妙
function TownAppear($user) {
	$place	= array();

	// 無條件行
	$place["Shop"]	= true;
	$place["Recruit"]	= true;
	$place["Smithy"]	= true;
	$place["Auction"]	= true;
	$place["Colosseum"]	= true;

	// 特定行施設
	//if($user->item[****])
	//	$place["****"]	= true;

	return $place;
}
?>