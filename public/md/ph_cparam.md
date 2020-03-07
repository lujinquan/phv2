### ph_cparam [配置] 配置
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(6) unsigned | PRI |  | NO |  |
| 2 | 配置的英文名 | name | varchar(20) |  |  | NO |  |
| 3 | 分组 | group | varchar(16) |  |  | NO |  |
| 4 | 配置的中文名 | title | varchar(32) |  |  | NO |  |
| 5 | 配置值 | value | text |  |  | NO |  |
| 6 | 配置类型 | type | varchar(16) |  |  | NO |  |
| 7 | 条件 | options | text |  |  | NO |  |
| 8 | 描述 | remark | varchar(64) |  |  | NO |  |
| 9 |  | system | tinyint(1) unsigned |  |  | NO |  |
| 10 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 11 |  | sort | int(10) unsigned |  |  | NO |  |
| 12 | 状态1可用2不可用 | status | tinyint(2) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
