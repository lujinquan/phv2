### ph_weixin_token [用户版小程序] 会员token表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 | 主键id | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 会员标识 | token | varchar(100) | MUL |  | NO |  |
| 3 | 会员id | member_id | int(10) unsigned |  |  | NO |  |
| 4 | 保持登录的session | session_key | varchar(100) |  |  | NO |  |
| 5 | 过期时间 | expires_in | int(10) unsigned |  |  | NO |  |
| 6 | token是否有效 | token_status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
