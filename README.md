# short-url 缩短链接

### 部署:

在serv00.net创建一个帐号，将`template.html` `index.php` `.htaccess` 三个文件（注意： 其中`mysql.php`是使用mysql数据库保存的 和 `index.php` 二选一即可，如果使用mysql保存数据 需要编辑里面的参数改为你的数据库帐号密码 并将`mysql.php`更名为`index.php`）
上传到`/usr/home/你的帐号/domains/你的帐号.serv00.net/public_html/*`里面 <br>
打开浏览器打开`你的帐号.serv00.net/short` 即可  (如果你想换个后缀 修改php文件里的`define('ADMIN_PATH', '/short');`的`/short` 不要后缀 使用`/` 不能为空)
![](./预览图UI.png)

### 忘记密码:

在`/usr/home/你的帐号/domains/你的帐号.serv00.net/public_html/shortlinks/后缀名.json`里面的`"password":"abc"`  其中的abc就是密码，改成`"password":""`或者直接删除这个`后缀名.json`即可重置密码
mysql数据的 则登录你的mysql服务器 查询和修改
