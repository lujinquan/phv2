### ph_system_plugins [系统] 插件表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(11) unsigned | PRI |  | NO |  |
| 2 |  | system | tinyint(1) unsigned |  |  | NO |  |
| 3 | 插件名称(英文) | name | varchar(32) |  |  | NO |  |
| 4 | 插件标题 | title | varchar(32) |  |  | NO |  |
| 5 | 图标 | icon | varchar(64) |  |  | NO |  |
| 6 | 插件简介 | intro | text |  |  | NO |  |
| 7 | 作者 | author | varchar(32) |  |  | NO |  |
| 8 | 作者主页 | url | varchar(255) |  |  | NO |  |
| 9 | 版本号 | version | varchar(16) |  |  | NO |  |
| 10 | 插件唯一标识符 | identifier | varchar(64) |  |  | NO |  |
| 11 | 插件配置 | config | text |  |  | NO |  |
| 12 | 来源(0本地) | app_id | varchar(30) |  |  | NO |  |
| 13 | 应用秘钥 | app_keys | varchar(200) |  |  | YES |  |
| 14 |  | ctime | int(10) unsigned |  |  | NO |  |
| 15 |  | mtime | int(10) unsigned |  |  | NO |  |
| 16 | 排序 | sort | int(10) unsigned |  |  | NO |  |
| 17 | 状态 | status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
