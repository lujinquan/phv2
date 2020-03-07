### ph_system_module [系统] 模块
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 系统模块 | system | tinyint(1) unsigned |  |  | NO |  |
| 3 | 模块名(英文) | name | varchar(50) | UNI |  | NO |  |
| 4 | 模块标识(模块名(字母).开发者标识.module) | identifier | varchar(100) | UNI |  | NO |  |
| 5 | 模块标题 | title | varchar(50) |  |  | NO |  |
| 6 | 模块简介 | intro | varchar(255) |  |  | NO |  |
| 7 | 作者 | author | varchar(100) |  |  | NO |  |
| 8 | 图标 | icon | varchar(80) |  |  | NO |  |
| 9 | 版本号 | version | varchar(20) |  |  | NO |  |
| 10 | 链接 | url | varchar(255) |  |  | NO |  |
| 11 | 排序 | sort | int(5) unsigned |  |  | NO |  |
| 12 | 0未安装，1未启用，2已启用 | status | tinyint(1) unsigned |  |  | NO |  |
| 13 | 默认模块(只能有一个) | default | tinyint(1) unsigned |  |  | NO |  |
| 14 | 配置 | config | text |  |  | NO |  |
| 15 | 应用市场ID(0本地) | app_id | varchar(30) |  |  | NO |  |
| 16 | 应用秘钥 | app_keys | varchar(200) |  |  | YES |  |
| 17 | 主题模板 | theme | varchar(50) |  |  | NO |  |
| 18 | 创建时间 | ctime | int(10) unsigned |  |  | NO |  |
| 19 | 修改时间 | mtime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
