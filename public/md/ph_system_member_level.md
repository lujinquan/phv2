### ph_system_member_level [系统] 会员等级
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 等级名称 | name | varchar(80) |  |  | NO |  |
| 3 | 最小经验值 | min_exper | int(10) unsigned |  |  | NO |  |
| 4 | 最大经验值 | max_exper | int(10) unsigned |  |  | NO |  |
| 5 | 折扣率(%) | discount | int(2) unsigned |  |  | NO |  |
| 6 | 等级简介 | intro | varchar(255) |  |  | NO |  |
| 7 | 默认等级 | default | tinyint(1) unsigned |  |  | NO |  |
| 8 | 会员有效期(天) | expire | int(10) unsigned |  |  | NO |  |
| 9 |  | status | tinyint(1) unsigned |  |  | NO |  |
| 10 |  | ctime | int(10) unsigned |  |  | NO |  |
| 11 |  | mtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
