<?php

//设置环境开发时切换但不要提交
define("APPMODE", "alpha"); //local (本地), alpha (测试), release (正式), admin (crontab)

require "app.php";