### ph_tenant [档案] 租户
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | tenant_id | int(10) unsigned | PRI |  | NO |  |
| 2 | 租户所属管段 | tenant_inst_id | tinyint(2) unsigned |  |  | NO |  |
| 3 | 租户所属机构 | tenant_inst_pid | tinyint(2) unsigned |  |  | NO |  |
| 4 | 租户编号 | tenant_number | int(8) unsigned |  |  | NO |  |
| 5 | 租户姓名 | tenant_name | varchar(32) |  |  | NO |  |
| 6 | 租户电话号码 | tenant_tel | char(11) |  |  | NO |  |
| 7 | 租户身份证号 | tenant_card | char(18) |  |  | NO |  |
| 8 |  | tenant_imgs | varchar(255) |  |  | NO |  |
| 9 |  | tenant_key | varchar(255) |  |  | NO |  |
| 10 |  | tenant_weixin_ctime | int(10) unsigned |  |  | NO |  |
| 11 | 创建时间 | tenant_ctime | int(10) unsigned |  |  | NO |  |
| 12 |  | tenant_cuid | int(10) unsigned |  |  | NO |  |
| 13 |  | tenant_status | tinyint(1) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
