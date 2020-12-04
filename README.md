# 说明
1、config.ini用于phalcon tools生成model用，借助phalcon
创建model文件示例：
```
php /opt/phalcon-devtools/phalcon.php model CartModel  --name=t_cart --config=config/config.ini --namespace=\\Kuga\\\Module\\Demo\\Model --extends=\\Kuga\\Core\\Base\\AbstractModel --camelize --mapcolumn
```
2、错误代码请在Api/Demo/Exception.php中定义，根据需要也可以在Model或Service中用或在Module/Demo下定义一个Exception Trait，然后相关Exception进行use引用。

3、国际化需要gettext扩展，请下载poedit，建议下载1.8.1之下的版本，使用说明：
- 1）打开po文件，菜单文件--打开，找到demo/src/Langs/en_US/LC_MESSAGES/common.po
- 2）菜单选编目--属性---源路径，将demo/src/Api和demo/src/Module两目录添加进来
- 3）菜单选编目--从源更新，系统会扫描需要翻译的内容
- 4）点击要翻译的内容，在下面翻译那一栏内输入对应的译文即可

4、目前多语化还有些问题，稍后解决。