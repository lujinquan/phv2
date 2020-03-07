### ph_json_child 
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | change_order_number | varchar(32) |  |  | NO |  |
| 3 |  | step | tinyint(1) unsigned |  |  | NO |  |
| 4 |  | uid | varchar(10) |  |  | NO |  |
| 5 |  | time | int(10) unsigned |  |  | NO |  |
| 6 | 动作 | action | varchar(32) |  |  | NO |  |
| 7 |  | img | varchar(255) |  |  | NO |  |
| 8 |  | changetype | varchar(255) |  |  | NO |  |
| 9 |  | success | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
