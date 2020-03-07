### ph_change_rentadd [异动] 租金追加调整
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 |  | house_id | varchar(32) |  |  | NO |  |
| 5 | 楼栋id | ban_id | int(10) unsigned |  |  | NO |  |
| 6 |  | tenant_id | int(10) unsigned |  |  | NO |  |
| 7 |  | child_json | text |  |  | NO |  |
| 8 |  | data_json | text |  |  | NO |  |
| 9 | 核销以前年金额 | before_year_rent | decimal(10,2) unsigned |  |  | NO |  |
| 10 | 核销以前月金额 | before_month_rent | decimal(10,2) unsigned |  |  | NO |  |
| 11 | 核销当月金额 | this_month_rent | decimal(10,2) unsigned |  |  | NO |  |
| 12 | 备注 | change_remark | varchar(255) |  |  | NO |  |
| 13 | 变更原因 | change_reason | varchar(255) |  |  | NO |  |
| 14 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 15 | 是否打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 16 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 17 |  | entry_date | char(7) |  |  | NO |  |
| 18 |  | ctime | int(10) |  |  | NO |  |
| 19 |  | etime | timestamp |  |  | NO |  |
| 20 | 终审完成时间 | ftime | int(10) unsigned |  |  | NO |  |
| 21 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
