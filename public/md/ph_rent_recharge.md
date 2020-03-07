### ph_rent_recharge [租金] 充值表
|  编号  |  中文名  |  字段名称  |  数据类型  |  主键  |  外键  |  是否允许为空  |  备注  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
| 1 |  | id | int(10) unsigned | PRI |  | NO |  |
| 2 | 账单流水号 | pay_number | varchar(64) |  |  | NO |  |
| 3 |  | house_id | int(10) unsigned |  |  | NO |  |
| 4 |  | tenant_id | int(10) unsigned |  |  | NO |  |
| 5 | 充值金额 | pay_rent | double(10,2) unsigned |  |  | NO |  |
| 6 | 支付方式 | pay_way | tinyint(2) unsigned |  |  | NO |  |
| 7 | 支付备注 | pay_remark | varchar(255) |  |  | NO |  |
| 8 |  | ctime | int(10) unsigned |  |  | NO |  |

### 索引

|  编号  |  名  |  字段  |  索引类型  |  索引方法  |
|: ------ :|: ------ :|: ------ :|: ------ :|: ------ :|
|   1 |    |    |    |    |

### 引擎

|  引擎  |  排序规则  |  字符集  |  数据目录  |
|: ------ :|: ------ :|: ------ :|: ------ :|
| InnoDB | utf8_general_ci | utf8_general_ci |utf8_general_ci |
