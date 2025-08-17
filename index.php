<?php

/**
 * 游戏主入口文件
 * 
 * 功能说明：
 * 1. 包含游戏全局配置文件(setting.php)
 * 2. 加载游戏主逻辑类(CLASS_MAIN)
 * 3. 初始化游戏主控制器并启动游戏
 * 
 * 此文件是游戏的入口点，负责初始化游戏环境并启动主控制器
 * 通过实例化main类，触发整个游戏流程的执行
 */
include("setting.php");
include(CLASS_MAIN);

new main();
