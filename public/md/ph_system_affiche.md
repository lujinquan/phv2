### ph_system_affiche [系统]消息推送
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | title | varchar(255) |  |  | NO |  |
| 3 | 超链接 | url | varchar(64) |  |  | NO |  |
| 4 | 分组 | group | tinyint(2) unsigned |  |  | NO |  |
| 5 |  | from_user_id | varchar(255) |  |  | NO |  |
| 6 |  | to_user_id | varchar(255) |  |  | NO |  |
| 7 | 已读的用户id | read_users | varchar(255) |  |  | NO |  |
| 8 |  | content | text |  |  | NO |  |
| 9 |  | create_time | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
