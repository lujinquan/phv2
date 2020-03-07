### ph_weixin_config [用户版小程序] 参数配置
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 配置ID | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 配置名称 | name | varchar(100) | UNI |  | NO |  |
| 3 | 配置分组 | config_group | varchar(20) | MUL |  | NO |  |
| 4 | 创建时间 | create_time | int(11) unsigned |  |  | NO |  |
| 5 | 配置值 | value | text |  |  | NO |  |
| 6 | 描述 | info | varchar(255) |  |  | NO |  |
| 7 | 排序 | sort | smallint(3) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
