### ph_system_hook [系统] 钩子表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 系统插件 | system | tinyint(1) unsigned |  |  | NO |  |
| 3 | 钩子名称 | name | varchar(50) | UNI |  | NO |  |
| 4 | 钩子来源[plugins.插件名，module.模块名] | source | varchar(50) |  |  | NO |  |
| 5 | 钩子简介 | intro | varchar(200) |  |  | NO |  |
| 6 |  | status | tinyint(1) unsigned |  |  | NO |  |
| 7 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 8 | 更新时间 | mtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
