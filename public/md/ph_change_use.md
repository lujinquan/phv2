### ph_change_use [异动] 使用权变更
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 使用权变更异动单号 | change_order_number | varchar(32) |  |  | NO |  |
| 3 | 流程id | process_id | tinyint(2) unsigned |  |  | NO |  |
| 4 | 使用权变更类型1交易转让2亲属转让3正常过户 | change_use_type | tinyint(1) unsigned |  |  | NO |  |
| 5 |  | ban_id | int(10) unsigned |  |  | NO |  |
| 6 |  | house_id | varchar(32) |  |  | NO |  |
| 7 | 原租户id | old_tenant_id | int(10) unsigned |  |  | NO |  |
| 8 |  | old_tenant_name | varchar(64) |  |  | NO |  |
| 9 | 新租户id | new_tenant_id | int(10) unsigned |  |  | NO |  |
| 10 |  | new_tenant_name | varchar(64) |  |  | NO |  |
| 11 | 是否自动新发租约 | is_create_lease | tinyint(1) unsigned |  |  | NO |  |
| 12 | 转让金额 | transfer_rent | decimal(10,2) unsigned |  |  | NO |  |
| 13 | 手续费 | service_rent | decimal(10,2) unsigned |  |  | NO |  |
| 14 | 是否属代、托、改造产 | is_reform | tinyint(1) unsigned |  |  | NO |  |
| 15 | 是否是五年内新翻覆修房屋 | is_repair | tinyint(1) unsigned |  |  | NO |  |
| 16 | 是否属于征收范围内房屋 | is_collection | tinyint(1) unsigned |  |  | NO |  |
| 17 | 是否属门面营业用房 | is_facade | tinyint(1) unsigned |  |  | NO |  |
| 18 | 是否已检查 | is_check | tinyint(1) unsigned |  |  | NO |  |
| 19 | 序列化数据 | child_json | text |  |  | NO |  |
| 20 | 异动备注 | change_remark | varchar(255) |  |  | NO |  |
| 21 | 变更失败原因 | change_reason | varchar(255) |  |  | NO |  |
| 22 | 异动附件 | change_imgs | varchar(255) |  |  | NO |  |
| 23 | 是否打回过 | is_back | tinyint(1) unsigned |  |  | NO |  |
| 24 | 异动状态1成功,0失败 | change_status | tinyint(1) unsigned |  |  | NO |  |
| 25 |  | entry_date | char(7) |  |  | NO |  |
| 26 |  | ctime | int(10) unsigned |  |  | NO |  |
| 27 |  | etime | timestamp |  |  | NO |  |
| 28 | 终审完成时间 | ftime | int(10) unsigned |  |  | NO |  |
| 29 |  | cuid | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
