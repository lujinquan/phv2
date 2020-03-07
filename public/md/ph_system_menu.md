### ph_system_menu [系统] 管理菜单
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 管理员ID(快捷菜单专用) | uid | int(5) unsigned |  |  | NO |  |
| 3 |  | pid | int(10) unsigned |  |  | NO |  |
| 4 | 模块名或插件名，插件名格式:plugins.插件名 | module | varchar(20) |  |  | NO |  |
| 5 | 菜单标题 | title | varchar(20) |  |  | NO |  |
| 6 |  | tip | varchar(255) |  |  | NO |  |
| 7 | 菜单图标 | icon | varchar(80) |  |  | NO |  |
| 8 | 链接地址(模块/控制器/方法) | url | varchar(200) |  |  | NO |  |
| 9 | 扩展参数 | param | varchar(200) |  |  | NO |  |
| 10 | 打开方式(_blank,_self) | target | varchar(20) |  |  | NO |  |
| 11 | 排序 | sort | int(10) unsigned |  |  | NO |  |
| 12 | 开发模式可见 | debug | tinyint(1) unsigned |  |  | NO |  |
| 13 | 是否为系统菜单，系统菜单不可删除 | system | tinyint(1) unsigned |  |  | NO |  |
| 14 | 是否为菜单显示，1显示0不显示 | nav | tinyint(1) unsigned |  |  | NO |  |
| 15 | 状态1显示，0隐藏 | status | tinyint(1) unsigned |  |  | NO |  |
| 16 |  | ctime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
