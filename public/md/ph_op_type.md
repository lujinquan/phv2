### ph_op_type [工单] 工单类型
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 分类的标题 | title | varchar(255) |  |  | NO |  |
| 3 |  | pid | int(10) unsigned |  |  | NO |  |
| 4 |  | keyids | varchar(255) |  |  | NO |  |
| 5 |  | filetypes | varchar(255) |  |  | NO |  |
| 6 | 描述 | remark | varchar(255) |  |  | NO |  |
| 7 |  | ctime | int(11) unsigned |  |  | NO |  |
| 8 |  | sort | int(10) unsigned |  |  | NO |  |
| 9 | 是否可用 | status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
