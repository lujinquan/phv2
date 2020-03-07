### ph_system_hook_plugins [系统] 钩子-插件对应表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(11) unsigned | PRI |  | NO |  |
| 2 | 钩子id | hook | varchar(32) |  |  | NO |  |
| 3 | 插件标识 | plugins | varchar(32) |  |  | NO |  |
| 4 |  | ctime | int(11) unsigned |  |  | NO |  |
| 5 |  | mtime | int(11) unsigned |  |  | NO |  |
| 6 |  | sort | int(11) unsigned |  |  | NO |  |
| 7 |  | status | tinyint(2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
