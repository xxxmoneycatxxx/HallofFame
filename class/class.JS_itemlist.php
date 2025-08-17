<?php

/**
 * JavaScript动态道具列表类
 * 
 * 功能说明：
 * 1. 实现道具分类动态切换显示功能
 * 2. 支持武器、防具、道具和其他四大分类
 * 3. 提供表格化和列表式两种展示方式
 * 
 * 主要功能模块：
 * 1. 分类管理：
 *    - 自动将道具按类型分类（武器/防具/道具/其他）
 *    - 支持自定义分类名称和ID
 * 2. 动态切换：
 *    - 生成JavaScript实现无刷新分类切换
 *    - 支持"全部"选项显示所有道具
 * 3. 显示控制：
 *    - 表格模式与列表模式切换
 *    - 表头表尾自定义插入内容
 *    - 无JavaScript回退方案
 * 
 * 技术特点：
 * 1. 前端交互：
 *    - 使用纯JavaScript实现分类切换
 *    - 动态更新DOM内容
 * 2. 数据组织：
 *    - 内部数组存储分类道具
 *    - 自动处理道具类型识别
 * 3. 兼容性设计：
 *    - 提供无JavaScript的静态显示方案
 *    - 表单嵌套安全处理
 * 
 * 使用注意事项：
 * 1. 表单嵌套：
 *    - 生成的select表单不能嵌套在其他表单内
 *    - 需独立放置避免表单嵌套问题
 * 2. 显示模式：
 *    - 支持表格模式(ListTable)和列表模式
 *    - 表格模式需设置表头表尾内容
 * 
 * 使用流程：
 * 1. 实例化JS_ItemList对象
 * 2. 设置ID和名称(SetID, SetName)
 * 3. 添加道具(AddItem)
 * 4. 设置表格模式(可选)
 * 5. 输出JavaScript(GetJavaScript)
 * 6. 输出分类选择表单(ShowSelect)
 * 7. 输出默认道具列表(ShowDefault)
 */

// 以下是原版的日文头部注释
// サンプル
/*
$list0	= new JS_ItemList();
$list0->SetID("buy");//引数なんでもいい
$list0->SetName("type_buy");//引数なんでもいい
foreach($ShopList as $no) {
	$item	= LoadItemData($no);
	$head	= '<input type="radio" name="item_no" value="'.$no.'" class="vcent">'.MoneyFormat($item["buy"]);
	$head	.= ShowItemDetail($item,false,1);
	$list0->AddItem($item,$head);
}
	print($list0->GetJavaScript("list"));
	print($list0->ShowSelect());
------------------ ↓も必要
<form action="?" method="post">
<div id="list"><?php print $list0->ShowDefault()?></div>
<input type="submit" class="btn" name="shop_buy" value="Buy">
</form>
-----------------------
※注意事項
<form></form>
が入れ子にならないように注意。

print($list0->ShowSelect());
は<form>～～～～</form>で書き出される。
print("<form>");
print($list0->ShowSelect());
print("</form>");// の形になると動かない。
*/

/*
	// テーブル形式で表示させる場合
	// 各アイテムの項目に\ nは使えない！！！
$list0->ListTable("<table>");// テーブルタグのはじまり
$list0->ListTableInsert("<tr><td>No</td><td>Item</td></tr>"); // テーブルの最初と最後の行に表示させるやつ。
*/

class JS_ItemList
{
	var $ID;
	var $name;

	var $weapon	= array();
	var $armor	= array();
	var $item	= array();
	var $other	= array();

	//
	var $Table	= false;
	var $TableInsert;

	// JSを使用しない！！！
	var $NoJS;

	function SetID($ID)
	{
		$this->ID	= $ID;
	}

	function SetName($name)
	{
		$this->name	= $name;
	}

	// JSを使用しない！！！
	function NoJS()
	{
		$this->NoJS	= true;
	}

	// 添加道具类目
	function AddItem($item, $string)
	{
		switch ($item["type"]) {
			case "剑":
			case "双手剑":
			case "匕首":
			case "魔杖":
			case "杖":
			case "弓":
			case "鞭":
				array_push($this->weapon, $string);
				break;
			case "盾":
			case "书":
			case "甲":
			case "衣服":
			case "长袍":
				array_push($this->armor, $string);
				break;
			case "道具":
				array_push($this->item, $string);
				break;
			default:
				array_push($this->other, $string);
		}
	}

	// テーブルタグで表形式に表示するようにする。
	function ListTable($HTML)
	{
		$this->Table	= $HTML;
	}

	// テーブルの一番上と下に必ず表示する項目みたいなの
	function ListTableInsert($string)
	{
		$this->TableInsert	= $string;
	}

	// JSを返す。
	function GetJavaScript($Id)
	{
		if ($this->NoJS)
			return NULL;

		foreach ($this->weapon as $str)
			$JS_weapon	.= ($JS_weapon ? " + \n'" : "'") . $str . "'";
		foreach ($this->armor as $str)
			$JS_armor	.= ($JS_armor ? " + \n'" : "'") . $str . "'";
		foreach ($this->item as $str)
			$JS_item	.= ($JS_item ? " + \n'" : "'") . $str . "'";
		foreach ($this->other as $str)
			$JS_other	.= ($JS_other ? " + \n'" : "'") . $str . "'";

		if (!$JS_weapon)
			$JS_weapon	= "''";
		if (!$JS_armor)
			$JS_armor	= "''";
		if (!$JS_item)
			$JS_item	= "''";
		if (!$JS_other)
			$JS_other	= "''";
		/*
		$JS_weapon	.= ($JS_weapon?" + \n'":"'None.")."<input type=\"hidden\" name=\"list_type\" value=\"weapon\">'";
		$JS_armor	.= ($JS_armor?" + \n'":"'None.")."<input type=\"hidden\" name=\"list_type\" value=\"armor\">'";
		$JS_item	.= ($JS_item?" + \n'":"'None.")."<input type=\"hidden\" name=\"list_type\" value=\"item\">'";
		$JS_other	.= ($JS_other?" + \n'":"'None.")."<input type=\"hidden\" name=\"list_type\" value=\"other\">'";
*/
		if ($this->Table) {
			$insert	= "insert = '" . $this->TableInsert . "'";
			$Table0	= "html = '" . $this->Table . "' + insert + html;";
			$Table1	= "html += insert + '</table>';";
		} else {
			$None	= 'html = (html?"":"没有找到适合的道具。") + html;';
		}
		$js = <<< _JS_
<script type="text/javascript"><!--
function List{$this->name}(mode) {
switch(mode) {
case "weapon":
html = {$JS_weapon}; break;
case "armor":
html = {$JS_armor}; break;
case "item":
html = {$JS_item}; break;
case "other":
html = {$JS_other}; break;
}
return(html);
}
function ChangeType{$this->ID}() {
mode = document.getElementById('{$this->ID}').{$this->name}.options[document.getElementById('{$this->ID}').{$this->name}.selectedIndex].value
if(mode == 'all') {
html = List{$this->name}('weapon') + List{$this->name}('armor') + List{$this->name}('item') + List{$this->name}('other');
{$None}
hidden = '<input type="hidden" name="list_type" value="all">';
} else {
html = List{$this->name}(mode);
{$None}
hidden = '<input type="hidden" name="list_type" value="' + mode + '">';
}
{$insert}
{$Table0}
{$Table1}
html += hidden;
document.getElementById("{$Id}").innerHTML = html;
}
//--></script>
_JS_;
		return $js;
	}

	// 最初に表示するもの
	function ShowDefault()
	{
		// JSを使わないので全て表示する。
		if ($this->NoJS) {
			$list	= array_merge($this->weapon, $this->armor, $this->item, $this->other);
			foreach ($list as $str)
				$open	.= $str . "\n";
			if ($this->Table) {
				$open	= $this->Table . $this->TableInsert . $open;
				$open	.= $this->TableInsert . "</table>";
			}
			return $open;
		}

		switch ($_POST["list_type"]) {
			default:
			case "weapon":
				$list	= $this->weapon;
				break;
			case "armor":
				$list	= $this->armor;
				break;
			case "item":
				$list	= $this->item;
				break;
			case "other":
				$list	= $this->other;
				break;
			case "all":
				$list	= array_merge($this->weapon, $this->armor, $this->item, $this->other);
				break;
		}
		foreach ($list as $str)
			$open	.= $str . "\n";

		switch ($_POST["list_type"]) {
			case "armor":
				$open	.= "<input type=\"hidden\" name=\"list_type\" value=\"armor\">\n";
				break;
			case "item":
				$open	.= "<input type=\"hidden\" name=\"list_type\" value=\"item\">\n";
				break;
			case "other":
				$open	.= "<input type=\"hidden\" name=\"list_type\" value=\"other\">\n";
				break;
			case "all":
				$open	.= "<input type=\"hidden\" name=\"list_type\" value=\"all\">\n";
				break;
		}

		if ($this->Table) {
			$open	= $this->Table . $this->TableInsert . $open;
			$open	.= $this->TableInsert . "</table>";
		}

		return $open;
	}

	// SELECTボックスの表示。
	function ShowSelect()
	{
		if ($this->NoJS)
			return NULL;

		switch ($_POST["list_type"]) {
			case "armor":
				$armor	= " selected";
				break;
			case "item":
				$item	= " selected";
				break;
			case "other":
				$other	= " selected";
				break;
			case "all":
				$all	= " selected";
				break;
		}

		$html = <<< HTML
<form id="{$this->ID}"><select onchange="ChangeType{$this->ID}()" name="{$this->name}" style="margin-bottom:10px">
  <option value="weapon">武器(weapon)</option>
  <option value="armor"{$armor}>防具(armor)</option>
  <option value="item"{$item}>道具(---)</option>
  <option value="other"{$other}>其他(other)</option>
  <option value="all"{$all}>全部(all)</option>
</select></form>
HTML;

		return $html;
	}
}
