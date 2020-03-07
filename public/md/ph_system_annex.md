### ph_system_annex [系统] 上传附件
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 附件英文名 | name | varchar(255) |  |  | NO |  |
| 3 | 关联的数据ID | data_id | int(10) unsigned |  |  | NO |  |
| 4 | 类型 | type | varchar(20) |  |  | NO |  |
| 5 | 文件分组 | group | varchar(100) |  |  | NO |  |
| 6 | 上传文件 | file | varchar(255) |  |  | NO |  |
| 7 | 文件hash值 | hash | varchar(64) |  |  | NO |  |
| 8 | 附件大小KB | size | decimal(12,2) unsigned |  |  | NO |  |
| 9 |  | remark | varchar(255) |  |  | NO |  |
| 10 | 使用状态(0未使用，1已使用) | status | tinyint(1) unsigned |  |  | NO |  |
| 11 |  | cuid | int(10) unsigned |  |  | NO |  |
| 12 |  | ctime | int(10) unsigned |  |  | NO |  |
| 13 | 过期时间 | etime | int(11) |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
