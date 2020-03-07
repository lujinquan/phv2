### ph_system_user [系统] 管理用户
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 |  | number | int(10) unsigned |  |  | NO |  |
| 3 | 角色ID | role_id | int(10) unsigned |  |  | NO |  |
| 4 |  | inst_id | tinyint(2) unsigned |  |  | NO |  |
| 5 | 运营中心所拥有的管段集合 | inst_ids | varchar(100) |  |  | NO |  |
| 6 | 机构级别 | inst_level | tinyint(1) unsigned |  |  | NO |  |
| 7 | 简介 | intro | varchar(255) |  |  | NO |  |
| 8 | 用户名 | username | varchar(50) |  |  | NO |  |
| 9 |  | password | varchar(64) |  |  | NO |  |
| 10 | 昵称 | nick | varchar(50) |  |  | NO |  |
| 11 |  | mobile | varchar(11) |  |  | NO |  |
| 12 | 邮箱 | email | varchar(50) |  |  | NO |  |
| 13 | 权限 | auth | text |  |  | NO |  |
| 14 | 0默认，1框架 | iframe | tinyint(1) unsigned |  |  | NO |  |
| 15 | 主题 | theme | varchar(50) |  |  | NO |  |
| 16 | 状态 | status | tinyint(1) unsigned |  |  | NO |  |
| 17 |  | user_key | varchar(255) |  |  | NO |  |
| 18 |  | user_weixin_ctime | int(10) unsigned |  |  | NO |  |
| 19 | 最后登陆IP | last_login_ip | varchar(128) |  |  | NO |  |
| 20 | 最后登陆时间 | last_login_time | int(10) unsigned |  |  | NO |  |
| 21 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 22 | 修改时间 | mtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
