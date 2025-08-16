<?php
// 游戏设置
const TITLE = "荣誉圣殿中文版 （Hall of Fame）";//网页标题
const MAX_TIME = 100;//最大体力
const TIME_GAIN_DAY = 500;//每日所获得的体力
const MAX_CHAR = 5;//最大角色数量
const MAX_USERS = 500;//最大用户数量
const ABANDONED = 60 * 60 * 24 * 14;//删除用户周期
const CONTROL_PERIOD = 60 * 60 * 12;//自动管理周期
const RECORD_IP = 1;//IP记录(0=NO 1=YES)

// 其他设置
const DEBUG = 0;// 0=OFF
const CHAR_NO_IMAGE = "NoImage.gif";// 无角色图片
const SESSION_SWITCH = 1;// 0=OFF
const CHAR_ROW = 5;//角色队列数
const CRYPT_KEY = '$1$12345678$';//パス射规步キ〖(ゲ〖ム肋弥稿は恃えるな)
const COOKIE_EXPIRE = 60 * 60 * 24 * 3;//cookie时间 60*60*24*3
const UP_PASS = "password";// 公告管理密码

const START_TIME = 100;//游戏初始体力
const START_MONEY = 10000;//游戏初始资金
const MAX_STATUS = 255;//最大属性点
const GET_STATUS_POINT = 3;//升级获得属性点
const GET_SKILL_POINT = 1;//升级获得技能点
const MAX_LEVEL = 50;//最大等级
const SELLING_PRICE = 1 / 5;//卖出物品比率（物品原价x比率）
const REFINE_LIMIT = 10;//篮希嘎肠猛

const EXP_RATE = 1;//经验倍数
const MONEY_RATE = 1;//金钱倍数

const NEW_NAME_COST = 100000;//改变队伍名称所需资金
const BBS_OUT = 0;//论坛链接地址
const BBS_BOTTOM_TOGGLE = 0;//底部论坛链接按钮(0=OFF 1=ON)
const AUCTION_TOGGLE = 1;//是否开启拍卖会所(0=OFF 1=ON)
const AUCTION_EXHIBIT_TOGGLE = 0;////拍卖(0=暂停 1=开启)
const JUDGE_LIST_AUTO_LOAD = 1;//条件判定列表自动取得 1=自动 0=手动操作
const AUCTION_MAX = 100;//最大拍卖数

// 排名设置
const RANK_TEAM_SET_TIME = 60 * 60 * 48;//排名队伍設定周期
const RANK_BATTLE_NEXT_LOSE = 60 * 60 * 24;//失败后再挑战时间
const RANK_BATTLE_NEXT_WIN = 60 * 1;//赢得排名站再战的时间

// 对战设置
const NORMAL_BATTLE_TIME = 1;//默认战斗消耗体力
const ENEMY_INCREASE = 0;//对手选择(随机)
const BATTLE_MAX_TURNS = 100;//战斗最大回合数
const TURN_EXTENDS = 20;// 疯缅がつきそうな眷圭变墓するタ〖ン眶。
const BATTLE_MAX_EXTENDS = 100;//变墓した眷圭の呵络乖瓢搀眶(变墓の嘎肠)
const BTL_IMG_TYPE = 2;// (0=GD 1=CSS 2=瓤啪貉茶咙蝗脱CSS)
const BTL_IMG = "./image.php";// GD文件
const BATTLE_STAT_TURNS = 10;// 战斗统计回合
const DEAD_IMG = "mon_145.gif";// HP=0时的角色图片
const MAX_BATTLE_LOG = 100;// 战斗记录保存数(通常怪)
const MAX_BATTLE_LOG_UNION = 50;// 战斗记录保存数(BOSS)
const MAX_BATTLE_LOG_RANK = 50;// 战斗记录保存数(BOSS)
const MAX_STATUS_MAXIMUM = 2500;// 最大战斗回合数(1000%=10)

const DELAY_TYPE = 1;// 0=奠 1=糠
// DELAY_TYPE=0
const DELAY = 2.5;//ディレイ(2笆惧が誊奥。眶猛が你いとSPDが光い客は铜网)
// DELAY_TYPE=1
const DELAY_BASE = 5;// 眶猛が光いと汗がつかなくなる。

// union
const UNION_BATTLE_TIME = 10;//BOSS战消耗体力
const UNION_BATTLE_NEXT = 60 * 20;//BOSS战再挑战时间

// files
const INDEX = "index.php";

// CLASS FILE
const CLASS_DIR = "./class/";
const BTL_IMG_CSS = CLASS_DIR . "class.css_btl_image.php";// CSS山绩
const CLASS_MAIN = CLASS_DIR . "class.main.php";
const CLASS_USER = CLASS_DIR . "class.user.php";
const CLASS_CHAR = CLASS_DIR . "class.char.php";
const CLASS_MONSTER = CLASS_DIR . "class.monster.php";
const CLASS_UNION = CLASS_DIR . "class.union.php";
const CLASS_BATTLE = CLASS_DIR . "class.battle.php";
const CLASS_SKILL_EFFECT = CLASS_DIR . "class.skill_effect.php";
const CLASS_RANKING = CLASS_DIR . "class.rank2.php";
const CLASS_JS_ITEMLIST = CLASS_DIR . "class.JS_itemlist.php";
const CLASS_SMITHY = CLASS_DIR . "class.smithy.php";
const CLASS_AUCTION = CLASS_DIR . "class.auction.php";
const GLOBAL_PHP = CLASS_DIR . "global.php";
const COLOR_FILE = CLASS_DIR . "Color.dat";

// DATA FILE
const DATA_DIR = "./data/";
const DATA_BASE_CHAR = DATA_DIR . "data.base_char.php";
const DATA_JOB = DATA_DIR . "data.job.php";
const DATA_ITEM = DATA_DIR . "data.item.php";
const DATA_ENCHANT = DATA_DIR . "data.enchant.php";
const DATA_SKILL = DATA_DIR . "data.skill.php";
const DATA_SKILL_TREE = DATA_DIR . "data.skilltree.php";
const DATA_JUDGE_SETUP = DATA_DIR . "data.judge_setup.php";
const DATA_JUDGE = DATA_DIR . "data.judge.php";
const DATA_MONSTER = DATA_DIR . "data.monster.php";
const DATA_LAND = DATA_DIR . "data.land_info.php";
const DATA_LAND_APPEAR = DATA_DIR . "data.land_appear.php";
const DATA_CLASSCHANGE = DATA_DIR . "data.classchange.php";
const DATA_CREATE = DATA_DIR . "data.create.php";
const DATA_TOWN = DATA_DIR . "data.town_appear.php";

const MANUAL = DATA_DIR . "data.manual0.php";
const MANUAL_HIGH = DATA_DIR . "data.manual1.php";

const GAME_DATA_JOB = DATA_DIR . "data.gd_job.php";
const GAME_DATA_ITEM = DATA_DIR . "data.gd_item.php";
const GAME_DATA_JUDGE = DATA_DIR . "data.gd_judge.php";
const GAME_DATA_MONSTER = DATA_DIR . "data.gd_monster.php";

const TUTORIAL = DATA_DIR . "data.tutorial.php";
// DAT
const AUCTION_ITEM = "./db/auction.dat";//アイテムオ〖クション脱のファイル
const AUCTION_ITEM_LOG = "./db/auction_log.dat";//アイテムオ〖クション脱のログファイル

const REGISTER = "./db/register.dat";
const UPDATE = "./db/update.dat";
const CTRL_TIME_FILE = "./db/ctrltime.dat";//年袋瓷妄のための箕粗淡脖ファイル
const RANKING = "./db/ranking.dat";
const BBS_BOTTOM = "./db/bbs.dat";
const BBS_TOWN = "./db/bbs_town.dat";
const MANAGE_LOG_FILE = "./db/managed.dat";//年袋瓷妄淡峡ファイル
const USER_NAME = "./db/username.dat";//叹涟瘦赂ファイル

// dir
const IMG_CHAR = "./image/char/";
const IMG_CHAR_REV = "./image/char_rev/";
const IMG_ICON = "./image/icon/";
const IMG_OTHER = "./image/other/";
const USER = "./user/";
const UNION = "./union/";
const DATA = "data.dat";
const ITEM = "item.dat";

const LOG_BATTLE_NORMAL = "./log/normal/";
const LOG_BATTLE_RANK = "./log/rank/";
const LOG_BATTLE_UNION = "./log/union/";

// 觉轮年盗
const FRONT = "front";
const BACK = "back";

const TEAM_0 = 0;
const TEAM_1 = 1;
const WIN = 0;
const LOSE = 1;
const DRAW = "d";

const ALIVE = 0;
const DEAD = 1;
const POISON = 2;

const CHARGE = 0;
const CAST = 1;

?>