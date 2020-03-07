### ph_system_role [系统] 管理角色
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 角色名称 | name | varchar(50) | UNI |  | NO |  |
| 3 | 角色简介 | intro | varchar(200) |  |  | NO |  |
| 4 | 角色权限 | auth | text |  |  | NO |  |
| 5 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 6 | 修改时间 | mtime | int(10) unsigned |  |  | NO |  |
| 7 |  | sort | tinyint(2) unsigned |  |  | NO |  |
| 8 | 状态 | status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
