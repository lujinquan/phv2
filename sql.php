<?php

/**
 * 1、同步楼栋表[ph_ban => ph_ban_back]
 * 字段：楼栋编号、栋号、单元数量、楼层数量、楼栋地址、管段、所、产别、产权、产权来源、建成年份、栋系数、完损等级、结构类别
 * 民建面、机建面、企建面、民栋数、机栋数、企栋数、民规租、机规租、企规租、民原价、机原价、企原价、使面、状态
 */
drop table if exists ph_v2.ph_ban_back;
create table ph_v2.ph_ban_back like ph_v2.ph_ban;
# 同步数据
insert into ph_v2.ph_ban_back 
(ban_number,ban_door,ban_units,ban_floors,ban_address,ban_inst_id,ban_inst_pid,ban_owner_id,ban_property_id,ban_property_source,ban_build_year,ban_ratio,ban_damage_id,ban_struct_id,ban_civil_holds,ban_party_holds,ban_career_holds,ban_civil_area,ban_party_area,ban_career_area,ban_civil_num,ban_party_num,ban_career_num,ban_civil_rent,ban_party_rent,ban_career_rent,ban_civil_oprice,ban_party_oprice,ban_career_oprice,ban_use_area,ban_ctime,ban_gpsx,ban_gpsy,ban_status) 
select 
BanID,BanNumber,BanUnitNum,BanFloorNum,AreaFour,TubulationID,InstitutionID,OwnerType,BanPropertyID,PropertySource,BanYear,BanRatio,DamageGrade,StructureType,CivilHolds,PartyHolds,EnterpriseHolds,CivilArea,PartyArea,EnterpriseArea,CivilNum,PartyNum,EnterpriseNum,CivilRent,PartyRent,EnterpriseRent,CivilOprice,PartyOprice,EnterpriseOprice,BanUsearea,CreateTime,BanGpsX,BanGpsY,Status
from ph_v1.ph_ban;
# 更新楼栋的创建人信息 
update ph_v2.ph_ban_back as a left join ph_v2.ph_system_user as b on a.ban_inst_id = b.inst_id set a.ban_cuid = b.id;
update ph_v2.ph_ban_back set ban_cuid = 1 where ban_cuid = 0;

/**
 * 2、将v1的房屋表中的ban_number全部替换成v2楼栋表中的ban_id [反向更新v1的房屋表]
 */
# update ph_v1.ph_house as a inner join ph_v2.ph_ban_back as b on a.BanID = b.ban_number set a.BanID = b.ban_id;
update ph_v1.ph_house a,ph_v2.ph_ban_back b set a.BanID = b.ban_id where a.BanID = b.ban_number;


/**
 * 3、同步租户表[ph_tenant => ph_tenant_back]
 * 字段：租户编号、租户姓名、管段、所、电话、身份证号、状态
 */
drop table if exists ph_v2.ph_tenant_back;
create table ph_v2.ph_tenant_back like ph_v2.ph_tenant;
# 同步数据
insert into ph_v2.ph_tenant_back 
(tenant_number,tenant_name,tenant_inst_id,tenant_inst_pid,tenant_tel,tenant_card,tenant_status) 
select 
TenantID,TenantName,InstitutionID,InstitutionPID,TenantTel,TenantNumber,Status
from ph_v1.ph_tenant;

/**
 * 4、将v1的房屋表中的tenant_number全部替换成v2楼栋表中的tenant_id [反向更新v1的房屋表]
 */
# update ph_v1.ph_house as a left join ph_v2.ph_tenant_back as b on a.TenantID = b.tenant_number set a.TenantID = b.tenant_id;
update ph_v1.ph_house a,ph_v2.ph_tenant_back b set a.TenantID = b.tenant_id where a.TenantID = b.tenant_number;
update ph_v2.ph_tenant_back as a left join ph_v2.ph_system_user as b on a.tenant_inst_id = b.inst_id set a.tenant_cuid = b.id;


/**
 * 5、同步房屋表[ph_house => ph_house_back]
 * 字段：房屋编号、规租、计算租金、单元号、楼层号、门牌号、使用性质、使用面积、建面、计租面积、原价、泵费、租差、协议租金、状态
 */
drop table if exists ph_v2.ph_house_back;
create table ph_v2.ph_house_back like ph_v2.ph_house;
# 同步数据
insert into ph_v2.ph_house_back 
(house_number,ban_id,tenant_id,house_pre_rent,house_cou_rent,house_unit_id,house_floor_id,house_door,house_use_id,house_use_area,house_area,house_lease_area,house_oprice,house_pump_rent,house_diff_rent,house_protocol_rent,house_is_pause,house_status) 
select 
HouseID,BanID,TenantID,HousePrerent,ApprovedRent,UnitID,FloorID,DoorID,UseNature,HouseUsearea,HouseArea,LeasedArea,OldOprice,PumpCost,DiffRent,ProtocolRent,IfSuspend,Status
from ph_v1.ph_house;
# 将规租更新成包含租差泵费和协议租金
update ph_v2.ph_house_back set house_pre_rent = house_pre_rent + house_diff_rent + house_pump_rent + house_protocol_rent;
update ph_v2.ph_house_back as a left join ph_ban_back as b on a.ban_id = b.ban_id set a.house_cuid = b.ban_cuid;

# 更新楼栋表的户数
update ph_v2.ph_ban_back as a left join (select ban_id,count(house_id) as houseids from ph_v2.ph_house_back where house_status = 1 and house_use_id = 1 group by ban_id) as b on a.ban_id = b.ban_id set a.ban_civil_holds = b.houseids; 
update ph_v2.ph_ban_back as a left join (select ban_id,count(house_id) as houseids from ph_v2.ph_house_back where house_status = 1 and house_use_id = 2 group by ban_id) as b on a.ban_id = b.ban_id set a.ban_career_holds = b.houseids;
update ph_v2.ph_ban_back as a left join (select ban_id,count(house_id) as houseids from ph_v2.ph_house_back where house_status = 1 and house_use_id = 3 group by ban_id) as b on a.ban_id = b.ban_id set a.ban_party_holds = b.houseids;

/**
 * 6、将v1的房间表中的ban_number全部替换成v2房间中的ban_id [反向更新v1的房屋表]
 */
update ph_v1.ph_room a,ph_v2.ph_ban_back b set a.BanID = b.ban_id where a.BanID = b.ban_number;



/**
 * 7、同步房间表[ph_room => ph_room_back]
 * 字段：房间编号、房间类型、规租、计算租金、间号、单元号、楼层号、使用面积、建面、计租面积、共用状态、状态
 */
drop table if exists ph_v2.ph_room_back;
create table ph_v2.ph_room_back like ph_v2.ph_room;
# 同步数据
insert into ph_v2.ph_room_back 
(room_number,ban_id,room_type,room_pre_rent,room_cou_rent,room_door,room_unit_id,room_floor_id,room_use_area,room_area,room_lease_area,room_pub_num,room_status) 
select 
RoomID,BanID,RoomType,RoomPrerent,RoomRentMonth,RoomNumber,UnitID,FloorID,UseArea,RoomArea,LeasedArea,RoomPublicStatus,Status
from ph_v1.ph_room;


/**
 * 8、同步由逗号分隔的数据到test表中
 */
truncate table ph_v2.test; 
insert into ph_v2.test 
(a,b) 
select 
RoomID,HouseID
from ph_v1.ph_room;



/**
 * 9、运行存储过程，将逗号分隔的数据拆分到test1表中 耗时262.971s
 */
# 运行存储过程函数（将有逗号分隔的插入到test1表中）
#truncate table ph_v2.test1;
# 【运行过一次就不用再运行了，因为这一步主要是拆分房间编号-房屋编号对应关系，而编号一直也没变】
#call split_str();




/**
 * 10、同步房屋房间映射表[test1 => ph_house_room_back]
 */
drop table if exists ph_v2.ph_house_room_back;
create table ph_v2.ph_house_room_back like ph_v2.ph_house_room;
# 同步数据
insert into ph_v2.ph_house_room_back 
(room_number,house_number) 
select 
a,b
from ph_v2.test1;



/**
 * 11、补充house_room_back表
 */
update ph_v2.ph_house_room_back a,ph_v2.ph_room_back b set a.room_id = b.room_id where a.room_number = b.room_number;
update ph_v2.ph_house_room_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_number = b.house_number;
update ph_v2.ph_room_back as a left join ph_ban_back as b on a.ban_id = b.ban_id set a.room_cuid = b.ban_cuid;


update ph_house_back set house_status = 2 where house_status > 1;
update ph_ban_back set ban_status = 2 where ban_status > 1;
update ph_tenant_back set tenant_status = 2 where tenant_status > 1;
update ph_room_back set room_status = 2 where room_status > 1;
/* 至此，档案数据全部同步完成 */




/**
 * 12、同步租金表[ph_rent_order => ph_rent_order]
 * 字段：订单编号、订单日期、减免租金、租差、泵费、应收租金、已缴租金、房屋编号、租户编号、支付时间（用创建时间作为支付时间）
 */
drop table if exists ph_v2.ph_rent_order_back;
create table ph_v2.ph_rent_order_back like ph_v2.ph_rent_order;
# 同步数据
insert into ph_v2.ph_rent_order_back 
(rent_order_number,rent_order_date,rent_order_cut,rent_order_diff,rent_order_pump,rent_order_receive,rent_order_paid,house_id,tenant_id,ptime) 
select 
RentOrderID,OrderDate,CutRent,DiffRent,PumpCost,ReceiveRent,PaidRent,HouseID,TenantID,CreateTime
from ph_v1.ph_rent_order;



/**
 * 13、补充rent_order_back表 41s
 */
update ph_v2.ph_rent_order_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update ph_v2.ph_rent_order_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_rent_order_back set ptime = 0 where rent_order_receive > rent_order_paid;
update ph_v2.ph_rent_order_back set is_deal = 1,pay_way = 1 where rent_order_paid > 0;
update ph_v2.ph_rent_order_back set is_deal = 1 where rent_order_date < left(replace(curdate(),'-',''),6);



/**
 * 14、同步租金收欠表[ph_old_rent => ph_rent_recycle]
 * 字段：房屋编号、租户编号、租户姓名、支付金额、订单年份、订单月份、创建月份、创建时间
 */
drop table if exists ph_v2.ph_rent_recycle_back;
create table ph_v2.ph_rent_recycle_back like ph_v2.ph_rent_recycle;
# 同步数据
insert into ph_v2.ph_rent_recycle_back 
(house_id,tenant_id,tenant_name,pay_rent,pay_year,pay_month,cdate,ctime) 
select 
HouseID,TenantID,TenantName,PayRent,PayYear,PayMonth,OldPayMonth,CreateTime
from ph_v1.ph_old_rent;



/**
 * 15、补充ph_rent_recycle表 41s
 */
update ph_v2.ph_rent_recycle_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update ph_v2.ph_rent_recycle_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;



/**
 * 16、同步租约表[ph_lease_change_order => ph_change_lease]
 * 字段：异动编号、流程id、房屋编号、备注、租户编号、租户姓名、子流程过程、序列化数据、打印次数、最近打印时间、有效二维码url、租约编号、失败原因、附件、状态、创建时间
 */
drop table if exists ph_v2.ph_change_lease_back;
create table ph_v2.ph_change_lease_back like ph_v2.ph_change_lease;
# 同步数据
insert into ph_v2.ph_change_lease_back 
(change_order_number,process_id,house_id,change_remark,tenant_id,tenant_name,child_json,data_json,last_print_time,print_times,qrcode,szno,reason,change_imgs,change_status,ctime) 
select 
ChangeOrderID,ProcessConfigType,HouseID,Recorde,TenantID,TenantName,Child,Deadline,PrintTime,PrintTimes,QrcodeUrl,Szno,Reason,ChangeImageIDS,Status,CreateTime
from ph_v1.ph_lease_change_order;



/**
 * 17、补充ph_change_lease表 
 */
update ph_v2.ph_change_lease_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update ph_v2.ph_change_lease_back a,ph_v2.ph_house_back b set a.house_id = b.house_id,a.cuid = b.house_cuid where a.house_id = b.house_number;
update ph_v2.ph_change_lease_back a,ph_v2.ph_house_back b set a.house_id = b.house_id,a.ban_id = b.ban_id,a.cuid = b.house_cuid where a.house_id = b.house_number;
update ph_v2.ph_change_lease_back a,ph_v2.ph_house_back b set a.ban_id = b.ban_id where a.house_id = b.house_id;

/**
 * 18、同步异动统计表[ph_rent_table => ph_change_table]
 * 字段：异动编号、异动类型、新发类型、注销类型、减免类型、所id、管段、产别、使用性质、影响租金、影响建面、影响使面、影响原价、影响栋数、以前月租金、以前年租金、租户编号、房屋编号、楼栋编号、异动日期、状态
 */
drop table if exists ph_v2.ph_change_table_back;
create table ph_v2.ph_change_table_back like ph_v2.ph_change_table;
# 同步数据
insert into ph_v2.ph_change_table_back 
(change_order_number,change_type,change_send_type,change_cancel_type,cut_type,inst_pid,inst_id,new_inst_id,owner_id,use_id,change_rent,change_area,change_use_area,change_oprice,change_ban_num,change_month_rent,change_year_rent,tenant_id,house_id,ban_id,end_date,order_date,change_status) 
select 
ChangeOrderID,ChangeType,NewSendRentType,CancelType,CutType,InstitutionPID,InstitutionID,NewInstitutionID,OwnerType,UseNature,InflRent,Area,UseArea,Oprice,ChangeNum,OldMonthRent,OldYearRent,TenantID,HouseID,BanID,DateEnd,OrderDate,Status
from ph_v1.ph_rent_table;



/**
 * 19、补充ph_change_table表 
 */
update ph_v2.ph_change_table_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update ph_v2.ph_change_table_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_table_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id where a.ban_id = b.ban_number;



/**
 * 20、增加档案临时表 
 */
drop table if exists ph_v2.ph_ban_temp;
create table ph_v2.ph_ban_temp like ph_v2.ph_ban;
insert into ph_v2.ph_ban_temp select * from ph_ban;

drop table if exists ph_v2.ph_house_temp;
create table ph_v2.ph_house_temp like ph_v2.ph_house;
insert into ph_v2.ph_house_temp select * from ph_house;

drop table if exists ph_v2.ph_room_temp;
create table ph_v2.ph_room_temp like ph_v2.ph_room;
insert into ph_v2.ph_room_temp select * from ph_room;

drop table if exists ph_v2.ph_house_room_temp;
create table ph_v2.ph_house_room_temp like ph_v2.ph_house_room;
insert into ph_v2.ph_house_room_temp select * from ph_house_room;


/**
 * 21、同步使用权变更表[ph_use_change_order => ph_change_use]
 * 字段：异动编号、变更类型、转让金额、房屋编号、原租户编号、原租户姓名、新租户编号、新租户姓名、备注、失败原因、附件、创建时间、状态
 */
drop table if exists ph_v2.ph_change_use_back;
create table ph_v2.ph_change_use_back like ph_v2.ph_change_use;
# 同步数据
update ph_v1.ph_use_change_order as a left join (select FatherOrderID,CreateTime from ph_v1.ph_use_child_order Order by id desc) as b on a.ChangeOrderID = b.FatherOrderID set a.FinishTime = b.CreateTime ;
insert into ph_v2.ph_change_use_back 
(change_order_number,change_use_type,transfer_rent,house_id,old_tenant_id,old_tenant_name,new_tenant_id,new_tenant_name,change_remark,change_reason,change_imgs,ctime,ftime,cuid,change_status) 
select 
ChangeOrderID,ChangeType,TransferRent,HouseID,OldTenantID,OldTenantName,NewTenantID,NewTenantName,ChangeReason,Reson,ChangeImageIDS,CreateTime,FinishTime,UserNumber,Status
from ph_v1.ph_use_change_order;

/**
 * 22、补充ph_change_use表 
 */
update ph_v2.ph_change_use_back a,ph_v2.ph_tenant_back b set a.old_tenant_id = b.tenant_id where a.old_tenant_id = b.tenant_number;
update ph_v2.ph_change_use_back a,ph_v2.ph_tenant_back b set a.new_tenant_id = b.tenant_id where a.new_tenant_id = b.tenant_number;
update ph_v2.ph_change_use_back a,ph_v2.ph_house_back b set a.house_id = b.house_id,a.cuid = b.house_cuid where a.house_id = b.house_number;



/**
 * 23、同步数据中心处理的ph_json_data表和ph_json_child表
 * 
 */

# 同步子异动表
# drop table if exists ph_v2.ph_change_child_back;
# create table ph_v2.ph_change_child_back like ph_v2.ph_change_child;
# insert into ph_v2.ph_change_child_back 
# (change_order_number,inst_id,child_step,child_remark,child_status,is_valid,child_cuid,child_ctime) 
# select 
# FatherOrderID,InstitutionID,Step,Reson,Status,IfValid,UserNumber,CreateTime
# from ph_v1.ph_use_child_order;
# update ph_v2.ph_json_data as a left join ph_v2.ph_system_user as b on a.child_cuid = b.number set a.child_cuid = b.id;
#update ph_v2.ph_json_child as a left join ph_v2.ph_system_user as b on a.uid = b.number set a.uid = b.id;

# 同步暂停计租
drop table if exists ph_v2.ph_change_pause_back;
create table ph_v2.ph_change_pause_back like ph_v2.ph_change_pause;
# 同步数据
insert into ph_v2.ph_change_pause_back 
(change_order_number,house_id,ban_id,change_pause_rent,change_imgs,ctime,ftime,change_status) 
select 
ChangeOrderID,HouseID,BanID,InflRent,ChangeImageIDS,CreateTime,FinishTime,Status
from ph_v1.ph_change_order where ChangeType = 3 and Status < 2;

update ph_v2.ph_change_pause_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_pause_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;


# 同步新发租
drop table if exists ph_v2.ph_change_new_back;
create table ph_v2.ph_change_new_back like ph_v2.ph_change_new;
# 同步数据
insert into ph_v2.ph_change_new_back 
(change_order_number,house_id,ban_id,tenant_id,new_type,change_imgs,ctime,ftime,change_status) 
select 
ChangeOrderID,HouseID,BanID,TenantID,NewLeaseType,ChangeImageIDS,CreateTime,FinishTime,Status
from ph_v1.ph_change_order where ChangeType = 7 and Status < 2;

update ph_v2.ph_change_new_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_new_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;
update ph_v2.ph_change_new_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;


# 同步租金减免
drop table if exists ph_v2.ph_change_cut_back;
create table ph_v2.ph_change_cut_back like ph_v2.ph_change_cut;
# 同步数据
insert into ph_v2.ph_change_cut_back 
(change_order_number,house_id,ban_id,tenant_id,cut_type,cut_rent,change_imgs,ctime,ftime,end_date,change_status)
select 
ChangeOrderID,HouseID,BanID,TenantID,CutType,InflRent,ChangeImageIDS,CreateTime,FinishTime,DateEnd,Status
from ph_v1.ph_change_order where ChangeType = 1 and Status < 2;

update ph_v2.ph_change_cut_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_cut_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;
update ph_v2.ph_change_cut_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;


# 同步注销+房改异动
drop table if exists ph_v2.ph_change_cancel_back;
create table ph_v2.ph_change_cancel_back like ph_v2.ph_change_cancel;
# 同步数据
insert into ph_v2.ph_change_cancel_back 
(change_order_number,house_id,ban_id,cancel_type,change_imgs,ctime,ftime,change_remark,change_status) 
select 
ChangeOrderID,HouseID,BanID,CancelType,ChangeImageIDS,CreateTime,FinishTime,Remark,Status
from ph_v1.ph_change_order where ChangeType in (5,8) and Status < 2;

update ph_v2.ph_change_cancel_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_cancel_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;
update ph_v2.ph_change_cancel_back set cancel_type = 1 where cancel_type = 0;
update ph_v2.ph_change_cancel_back as a left join ph_v1.ph_rent_table as b on a.change_order_number = b.ChangeOrderID set a.cancel_ban = b.ChangeNum,a.cancel_rent = b.InflRent,a.cancel_area = b.Area,a.cancel_use_area = b.UseArea,a.cancel_oprice = b.Oprice;


# 同步房屋调整
drop table if exists ph_v2.ph_change_house_back;
create table ph_v2.ph_change_house_back like ph_v2.ph_change_house;
# 同步数据
insert into ph_v2.ph_change_house_back 
(change_order_number,house_id,ban_id,change_imgs,ctime,ftime,change_status) 
select 
ChangeOrderID,HouseID,BanID,ChangeImageIDS,CreateTime,FinishTime,Status
from ph_v1.ph_change_order where ChangeType = 9 and Status < 2;

update ph_v2.ph_change_house_back a,ph_v2.ph_house_back b set a.house_id = b.house_id,a.tenant_id = b.tenant_id where a.house_id = b.house_number;
update ph_v2.ph_change_house_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;


# 同步管段调整
drop table if exists ph_v2.ph_change_inst_back;
create table ph_v2.ph_change_inst_back like ph_v2.ph_change_inst;
# 同步数据
insert into ph_v2.ph_change_inst_back 
(change_order_number,ban_ids,old_inst_id,new_inst_id,change_imgs,change_ban_rent,ctime,ftime,change_status) 
select 
ChangeOrderID,BanID,InstitutionID,NewInstitutionID,ChangeImageIDS,InflRent,CreateTime,FinishTime,Status
from ph_v1.ph_change_order where ChangeType = 10 and Status < 2;

update ph_v2.ph_change_inst_back a,ph_v2.ph_ban_back b set a.ban_ids = b.ban_id,a.cuid = b.ban_cuid where a.ban_ids = b.ban_number;


# 同步陈欠核销
drop table if exists ph_v2.ph_change_offset_back;
create table ph_v2.ph_change_offset_back like ph_v2.ph_change_offset;
# 同步数据
insert into ph_v2.ph_change_offset_back 
(change_order_number,house_id,ban_id,tenant_id,before_month_rent,before_year_rent,change_imgs,ctime,ftime,change_status) 
select 
ChangeOrderID,HouseID,BanID,TenantID,OldMonthRent,OldYearRent,ChangeImageIDS,CreateTime,FinishTime,Status
from ph_v1.ph_change_order where ChangeType = 4 and Status < 2;

update ph_v2.ph_change_offset_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_offset_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;
update ph_v2.ph_change_offset_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;


# 同步别字更正
drop table if exists ph_v2.ph_change_name_back;
create table ph_v2.ph_change_name_back like ph_v2.ph_change_name;
# 同步数据
update ph_v1.ph_cor_change_order as a left join (select FatherOrderID,CreateTime from ph_v1.ph_cor_child_order Order by id desc) as b on a.ChangeOrderID = b.FatherOrderID set a.FinishTime = b.CreateTime ;
insert into ph_v2.ph_change_name_back 
(change_order_number,house_id,tenant_id,old_tenant_name,new_tenant_name,change_remark,change_reason,change_imgs,ctime,ftime,change_status) 
select 
ChangeOrderID,HouseID,OldTenantID,OldTenantName,NewTenantName,ChangeReason,Reson,ChangeImageIDS,CreateTime,FinishTime,Status
from ph_v1.ph_cor_change_order;


update ph_v2.ph_change_name_back a,ph_v2.ph_tenant_back b set a.tenant_id = b.tenant_id where a.tenant_id = b.tenant_number;
update ph_v2.ph_change_name_back a,ph_v2.ph_house_back b set a.house_id = b.house_id,a.cuid = b.house_cuid where a.house_id = b.house_number;


# 同步租金追加调整
drop table if exists ph_v2.ph_change_rentadd_back;
create table ph_v2.ph_change_rentadd_back like ph_v2.ph_change_rentadd;
# 同步数据
insert into ph_v2.ph_change_rentadd_back 
(change_order_number,house_id,ban_id,tenant_id,before_month_rent,before_year_rent,change_imgs,ctime,ftime,change_status) 
select 
ChangeOrderID,HouseID,BanID,TenantID,OldMonthRent,OldYearRent,ChangeImageIDS,CreateTime,FinishTime,Status
from ph_v1.ph_change_order where ChangeType = 11 and Status < 2;

update ph_v2.ph_change_rentadd_back a,ph_v2.ph_house_back b set a.house_id = b.house_id where a.house_id = b.house_number;
update ph_v2.ph_change_rentadd_back a,ph_v2.ph_ban_back b set a.ban_id = b.ban_id,a.cuid = b.ban_cuid where a.ban_id = b.ban_number;
update ph_v2.ph_change_rentadd_back a,ph_v2.ph_house_back b set a.tenant_id = b.tenant_id where a.house_id = b.house_id;


update ph_change_ban_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_cancel_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_cut_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_cut_cancel_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_cut_year_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_house_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_inst_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_lease_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_name_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_new_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_offset_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_pause_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_rentadd_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;
update ph_change_use_back set entry_date = from_unixtime(ftime, '%Y-%m') where ftime > 0;


/**
 * 24、同步附件表 ph_upload_file 到 ph_system_annex
 * 实际上所有的附件都会
 */
drop table if exists ph_v2.ph_system_annex_back;
create table ph_v2.ph_system_annex_back like ph_v2.ph_system_annex;
# 同步数据
insert into ph_v2.ph_system_annex_back 
(id,remark,file,cuid,ctime) 
select 
id,FileTitle,FileUrl,UploadUserID,UploadTime
from ph_v1.ph_upload_file;
update ph_v2.ph_system_annex_back as a left join ph_v2.ph_system_user as b on a.cuid = b.number set a.cuid = b.id;
update ph_system_annex_back set file = replace(file,'/uploads/','/upload/');
update ph_system_annex_back set file = replace(file,'/changeOrder/','/change/image/20181201/');
update ph_system_annex_back set file = replace(file,'/usechange/','/change/image/20181201/');
update ph_system_annex_back set file = replace(file,'/house/','/house/image/20181201/');
update ph_system_annex_back set file = replace(file,'/tenant/','/tenant/image/20181201/');
update ph_change_lease_back set qrcode = replace(qrcode,'/uploads/','/upload/');
update ph_change_lease_back set change_remark = replace(change_remark,'&nbsp&nbsp',' ');
update ph_change_lease_back set data_json = replace(data_json,'"}','","applyType":"2"}');
# 翻译附件名对应新的data_id
#租金减免
update ph_system_annex_back set name='Lowassurance' where remark = '低保证';
update ph_system_annex_back set name='Residence' where remark = '户口本';
update ph_system_annex_back set name='HouseApplicationForm' where remark = '房产证';
update ph_system_annex_back set name='Housingsecurity' where remark = '住房保障申请表';
#暂停计租
update ph_system_annex_back set name='ChangePauseUpload' where remark = '上传报告';
#陈欠核销
update ph_system_annex_back set name='ChangeOffsetUpload' where remark = '陈欠核销情况说明报告';
#新发租
update ph_system_annex_back set name='ChangeNewUpload' where remark = '新发租情况说明';
#租金追加调整
update ph_system_annex_back set name='RentAddPaper' where remark = '其他(票据)';
#注销
update ph_system_annex_back set name='ChangeCancelOne' where remark = '武汉市直管公有住房出售收入专用票据';
update ph_system_annex_back set name='ChangeCancelTwo' where remark = '武昌区房地局出售直管公有住房审批表';
#房屋调整
#update ph_system_annex_back set name='' where remark = '调整附件报告';
#租约管理
update ph_system_annex_back set name='TenantReIDCard' where remark = '身份证';
update ph_system_annex_back set name='Houselease' where remark = '计租表';
update ph_system_annex_back set name='HouseForm' where remark = '租约';
update ph_system_annex_back set name='ChangeLeaseSign' where remark = '租约签字图片';
update ph_system_annex_back as a left join ph_system_annex_type as b on a.name = b.file_type set a.data_id = b.id;



# 将back表同步到主表
drop table if exists ph_ban;
alter table ph_ban_back rename ph_ban;
drop table if exists ph_change_cancel;
alter table ph_change_cancel_back rename ph_change_cancel;
#drop table if exists ph_change_child;
#alter table ph_change_child_back rename ph_change_child;
drop table if exists ph_change_cut;
alter table ph_change_cut_back rename ph_change_cut;
drop table if exists ph_change_house;
alter table ph_change_house_back rename ph_change_house;
drop table if exists ph_change_inst;
alter table ph_change_inst_back rename ph_change_inst;
drop table if exists ph_change_lease;
alter table ph_change_lease_back rename ph_change_lease;
drop table if exists ph_change_name;
alter table ph_change_name_back rename ph_change_name;
drop table if exists ph_change_new;
alter table ph_change_new_back rename ph_change_new;
drop table if exists ph_change_offset;
alter table ph_change_offset_back rename ph_change_offset;
drop table if exists ph_change_pause;
alter table ph_change_pause_back rename ph_change_pause;
drop table if exists ph_change_rentadd;
alter table ph_change_rentadd_back rename ph_change_rentadd;
drop table if exists ph_change_table;
alter table ph_change_table_back rename ph_change_table;
drop table if exists ph_change_use;
alter table ph_change_use_back rename ph_change_use;
drop table if exists ph_house;
alter table ph_house_back rename ph_house;
drop table if exists ph_house_room;
alter table ph_house_room_back rename ph_house_room;
drop table if exists ph_rent_order;
alter table ph_rent_order_back rename ph_rent_order;
drop table if exists ph_rent_recycle;
alter table ph_rent_recycle_back rename ph_rent_recycle;
drop table if exists ph_room;
alter table ph_room_back rename ph_room;
drop table if exists ph_tenant;
alter table ph_tenant_back rename ph_tenant;
drop table if exists ph_system_annex;
alter table ph_system_annex_back rename ph_system_annex;

truncate ph_v2.ph_change_process;
# 清空台账记录
truncate ph_ban_tai;
truncate ph_house_tai;
truncate ph_tenant_tai;