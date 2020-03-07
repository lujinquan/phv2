### ph_system_log [系统] 操作日志
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(11) unsigned | PRI |  | NO |  |
| 2 |  | uid | int(10) unsigned |  |  | NO |  |
| 3 |  | title | varchar(100) |  |  | YES |  |
| 4 |  | url | varchar(200) |  |  | YES |  |
| 5 |  | param | text |  |  | YES |  |
| 6 |  | remark | varchar(255) |  |  | YES |  |
| 7 |  | count | int(10) unsigned |  |  | NO |  |
| 8 |  | ip | varchar(128) |  |  | YES |  |
| 9 |  | ctime | int(10) unsigned |  |  | NO |  |
| 10 |  | mtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
