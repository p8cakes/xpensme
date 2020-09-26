-- ****************************** Module Header ******************************
-- Module Name:  xm website SQL file
-- Project:      xm website to track everyday expenses
-- Copyright (c) Sundar Krishnamurthy sundar@passion8cakes.com
-- All rights reserved.
--
-- xm.sql file to create the database, load metadata tables and stored procedures - please employ MySQL 5.5 or greater
--
-- 1.    T1. appLogs table to store application logs
-- 2.    P1. addAppLog stored procedure to add a certain string or log to the appLogs table
-- 3.    P2. showLatestAppLogs stored procedure to fetch the latest configurable amount of logs inserted
-- 4.    P3. clearAppLogs stored procedure to remove all entries from appLogs table
-- 5.    T2. systemSettings table - table to store system settings, queried at session start and other places.
-- 6.    P4: populateSystemSettings SP - to get initial set of settings required for app.
-- 7.    P5: getSystemSetting SP - to get the value for a provided setting name.
--
-- Revisions:
--      1. Sundar Krishnamurthy         sundar@passion8cakes.com               9/25/2020       Initial file created.

-- Very, very, very bad things happen if you uncomment this line below. Do at your peril, you have been warned!
-- drop database if exists $$DATABASE_NAME$$;                                                        -- $$ DATABASE_NAME $$

-- Create database $$DATABASE_NAME$$, with utf8 and utf8_general_ci
create database if not exists $$DATABASE_NAME$$ character set utf8 collate utf8_general_ci;       -- $$ DATABASE_NAME $$

-- Employ $$DATABASE_NAME$$
use $$DATABASE_NAME$$;                                                                            -- $$ DATABASE_NAME $$

-- drop table if exists appLogs;

-- 1. T1. appLogs table to store application logs
create table if not exists appLogs (
    id                                        int ( 10 ) unsigned              not null auto_increment,
    log                                       varchar( 255 )                   not null,
    created                                   datetime                         not null,
    key ( id )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

drop procedure if exists addAppLog;

delimiter //

-- 2. P1. addAppLog stored procedure to add a certain string or log to the appLogs table
create procedure addAppLog(
    in            p_log                       varchar( 255 )
)
begin

    declare l_logId                           int ( 10 ) unsigned default 0;

    insert appLogs (log, created)
    values ( p_log, utc_timestamp() );

    select last_insert_id() into l_logId;
    select l_logId as logId;

end //

delimiter ;

drop procedure if exists showLatestAppLogs;

delimiter //

-- 3. P2. showLatestAppLogs stored procedure to fetch the latest configurable amount of logs inserted
create procedure showLatestAppLogs(
    in            p_count                     int ( 10 ) unsigned
)
begin
    select id, log, created
    from appLogs
    order by id desc
    limit p_count;

end //

delimiter ;

drop procedure if exists clearAppLogs;

delimiter //

-- 4. P3. clearAppLogs stored procedure to remove all entries from appLogs table
create procedure clearAppLogs()
begin
    truncate table appLogs;

end //

delimiter ;

-- drop table if exists systemSettings;

-- 5. T2. systemSettings table to store system settings
create table if not exists systemSettings (
    id                                        int ( 10 ) unsigned              not null auto_increment,
    name                                      varchar( 32 )                    not null,
    value                                     varchar( 255 )                   not null,
    enabled                                   tinyint ( 1 )                    unsigned default 0,
    created                                   datetime                         not null,
    lastUpdate                                datetime                         not null,
    key ( id ),
    unique index ix_name ( name )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

drop procedure if exists populateSystemSettings;

delimiter //

-- 6. P1. populateSystemSettings SP to get initial set of settings required for app.
create procedure populateSystemSettings()
begin

    declare l_settingsCount                   int ( 10 ) unsigned;

    select count(*) into l_settingsCount from systemSettings;

    if l_settingsCount = 0 then

        insert systemSettings ( name, value, enabled, created, lastUpdate)
        values ('logAllCalls', '0', 1, utc_timestamp(), utc_timestamp());

    end if;
end //

delimiter ;

call populateSystemSettings();

drop procedure if exists getSystemSetting;

delimiter //

-- 7. P5. getSystemSetting SP to get the value for a provided setting name.
create procedure getSystemSetting(
    in            p_name                      varchar( 32 )
)
begin

    select
        name,
        value,
        enabled
    from
        systemSettings
    where
        name = p_name
    order by
        id
    limit 1;
end //

delimiter ;
