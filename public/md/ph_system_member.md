### ph_system_member [系统] 会员表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 会员等级ID | level_id | int(10) unsigned |  |  | NO |  |
| 3 | 昵称 | nick | varchar(50) |  |  | NO |  |
| 4 | 用户名 | username | varchar(30) |  |  | NO |  |
| 5 | 手机号 | mobile | bigint(11) unsigned |  |  | NO |  |
| 6 | 邮箱 | email | varchar(50) |  |  | NO |  |
| 7 | 密码 | password | varchar(128) |  |  | NO |  |
| 8 | 密码混淆 | salt | varchar(16) |  |  | NO |  |
| 9 | 可用金额 | money | decimal(10,2) unsigned |  |  | NO |  |
| 10 | 冻结金额 | frozen_money | decimal(10,2) unsigned |  |  | NO |  |
| 11 | 收入统计 | income | decimal(10,2) unsigned |  |  | NO |  |
| 12 | 开支统计 | expend | decimal(10,2) unsigned |  |  | NO |  |
| 13 | 经验值 | exper | int(10) unsigned |  |  | NO |  |
| 14 | 积分 | integral | int(10) unsigned |  |  | NO |  |
| 15 | 冻结积分 | frozen_integral | int(10) unsigned |  |  | NO |  |
| 16 | 性别(1男，0女) | sex | tinyint(1) unsigned |  |  | NO |  |
| 17 | 头像 | avatar | varchar(255) |  |  | NO |  |
| 18 | 最后登陆IP | last_login_ip | varchar(128) |  |  | NO |  |
| 19 | 最后登陆时间 | last_login_time | int(10) unsigned |  |  | NO |  |
| 20 | 登陆次数 | login_count | int(10) unsigned |  |  | NO |  |
| 21 | 到期时间(0永久) | expire_time | int(10) unsigned |  |  | NO |  |
| 22 | 状态(0禁用，1正常) | status | tinyint(1) unsigned |  |  | NO |  |
| 23 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
