### ph_weixin_member [用户版小程序] 会员
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | member_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 租户id | tenant_id | int(10) unsigned | MUL |  | NO |  |
| 3 | 会员昵称 | member_name | varchar(64) |  |  | NO |  |
| 4 | 真实姓名 | real_name | varchar(64) |  |  | NO |  |
| 5 | 认证手机号 | tel | char(11) |  |  | NO |  |
| 6 | 微信绑定的手机号 | weixin_tel | char(11) |  |  | NO |  |
| 7 | 微信头像 | avatar | varchar(255) |  |  | NO |  |
| 8 | 微信用户唯一标识符 | openid | varchar(64) |  |  | NO |  |
| 9 | 小程序主页菜单,按顺序逗号分隔 | app_menus | varchar(64) |  |  | NO |  |
| 10 | 微信平台通用unionid | unionid | varchar(64) |  |  | NO |  |
| 11 | 登录总次数 | login_count | int(10) unsigned |  |  | NO |  |
| 12 | 最近一次登录时间 | last_login_time | int(10) unsigned |  |  | NO |  |
| 13 | 最近一次登录ip | last_login_ip | varchar(32) |  |  | NO |  |
| 14 | 创建时间 | create_time | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | gbk_chinese_ci | gbk_chinese_ci |gbk_chinese_ci |
