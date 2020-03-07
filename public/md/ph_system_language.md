### ph_system_language [系统] 语言包
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(11) unsigned | PRI |  | NO |  |
| 2 | 语言包名称 | name | varchar(50) |  |  | NO |  |
| 3 | 编码 | code | varchar(20) | UNI |  | NO |  |
| 4 | 本地浏览器语言编码 | locale | varchar(255) |  |  | NO |  |
| 5 | 图标 | icon | varchar(30) |  |  | NO |  |
| 6 | 上传的语言包 | pack | varchar(100) |  |  | NO |  |
| 7 |  | sort | tinyint(2) unsigned |  |  | NO |  |
| 8 |  | status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
