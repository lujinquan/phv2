### ph_ban_tai [档案] 房屋台账表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 台账id | ban_tai_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 序列化数据 | data_json | text |  |  | NO |  |
| 3 | 台账类型 | ban_tai_type | tinyint(2) unsigned |  |  | NO |  |
| 4 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 5 | 台账备注 | ban_tai_remark | varchar(255) |  |  | NO |  |
| 6 |  | change_type | tinyint(2) unsigned |  |  | NO |  |
| 7 |  | change_id | int(10) unsigned |  |  | NO |  |
| 8 |  | ctime | int(10) unsigned |  |  | NO |  |
| 9 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
