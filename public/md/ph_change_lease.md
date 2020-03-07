### ph_change_lease [异动] 租约
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 5 |  | house_id | varchar(32) | MUL |  | NO |  |
| 6 |  | tenant_id | int(10) | MUL |  | NO |  |
| 7 |  | tenant_name | varchar(255) |  |  | NO |  |
| 8 | 原租户id | change_remark | varchar(255) |  |  | NO |  |
| 9 | 新租户id | child_json | text |  |  | NO |  |
| 10 | 序列化数据 | data_json | text |  |  | NO |  |
| 11 | 变更原因 | change_reason | varchar(255) |  |  | NO |  |
| 12 | 打印次数 | print_times | tinyint(4) unsigned |  |  | NO |  |
| 13 | 最近打印时间 | last_print_time | int(10) unsigned |  |  | NO |  |
| 14 | 租约编号 | szno | varchar(64) |  |  | NO |  |
| 15 | 二维码url | qrcode | varchar(255) |  |  | NO |  |
| 16 | 失败的原因 | reason | varchar(255) |  |  | NO |  |
| 17 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 18 |  | is_back | tinyint(1) unsigned |  |  | NO |  |
| 19 | 是否有效 | is_valid | tinyint(1) unsigned |  |  | NO |  |
| 20 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 21 |  | entry_date | char(7) |  |  | NO |  |
| 22 |  | ctime | int(10) unsigned |  |  | NO |  |
| 23 |  | ftime | int(10) unsigned |  |  | NO |  |
| 24 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
