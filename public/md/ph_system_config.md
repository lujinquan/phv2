### ph_system_config [系统] 系统配置
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 是否为系统配置(1是，0否) | system | tinyint(1) unsigned |  |  | NO |  |
| 3 | 分组 | group | varchar(20) |  |  | NO |  |
| 4 | 配置标题 | title | varchar(20) |  |  | NO |  |
| 5 | 配置名称，由英文字母和下划线组成 | name | varchar(50) |  |  | NO |  |
| 6 | 配置值 | value | text |  |  | NO |  |
| 7 | 配置类型() | type | varchar(20) |  |  | NO |  |
| 8 | 配置项(选项名:选项值) | options | text |  |  | NO |  |
| 9 | 文件上传接口 | url | varchar(255) |  |  | NO |  |
| 10 | 配置提示 | tips | varchar(255) |  |  | NO |  |
| 11 | 排序 | sort | int(10) unsigned |  |  | NO |  |
| 12 | 状态 | status | tinyint(1) unsigned |  |  | NO |  |
| 13 |  | ctime | int(10) unsigned |  |  | NO |  |
| 14 |  | mtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
