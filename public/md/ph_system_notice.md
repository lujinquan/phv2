### ph_system_notice [系统]公告
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 类型,1档案,2租金,3异动,4报表,5工单 | type | tinyint(2) unsigned |  |  | NO |  |
| 3 | 是否置顶1置顶2不置顶 | is_top | tinyint(1) unsigned |  |  | NO |  |
| 4 | 排序 | sort | int(10) unsigned |  |  | NO |  |
| 5 |  | inst_id | int(10) unsigned |  |  | NO |  |
| 6 |  | title | varchar(255) |  |  | NO |  |
| 7 |  | content | text |  |  | NO |  |
| 8 | 阅读记录 | reads | text |  |  | NO |  |
| 9 | 创建人 | cuid | int(10) unsigned |  |  | NO |  |
| 10 |  | create_time | int(10) unsigned |  |  | NO |  |
| 11 |  | update_time | int(10) unsigned |  |  | NO |  |
| 12 |  | delete_time | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
