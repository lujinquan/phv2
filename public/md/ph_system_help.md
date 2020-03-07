### ph_system_help [系统]帮助文档
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 类型,1:常见问题,2:产品使用,3:系统说明 | type | tinyint(2) unsigned |  |  | NO |  |
| 3 | 排序 | sort | int(10) unsigned |  |  | NO |  |
| 4 |  | inst_id | int(10) unsigned |  |  | NO |  |
| 5 |  | title | varchar(255) |  |  | NO |  |
| 6 |  | content | text |  |  | NO |  |
| 7 | 创建人 | cuid | int(10) unsigned |  |  | NO |  |
| 8 |  | create_time | int(10) unsigned |  |  | NO |  |
| 9 |  | update_time | int(10) unsigned |  |  | NO |  |
| 10 |  | delete_time | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
