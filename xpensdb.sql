-- ****************************** Module Header ******************************
-- Module Name:  xm website SQL file
-- Project:      xm website to track everyday expenses
-- Copyright (c) Sundar Krishnamurthy sundar@passion8cakes.com
-- All rights reserved.
--
-- xm.sql file to create the database, load metadata tables and stored procedures - please employ MySQL 5.5 or greater
--
-- 1.    T1:    systemSettings table - table to store system settings, queried at session start and other places.
-- 2.    P1:    populateSystemSettings SP - to get initial set of settings required for app.
-- 3.    P2:    getSystemSetting SP - to get the value for a provided setting name.
--
-- Revisions:
--      1. Sundar Krishnamurthy         sundar@passion8cakes.com               9/25/2020       Initial file created.

-- Very, very, very bad things happen if you uncomment this line below. Do at your peril, you have been warned!
drop database if exists $$DATABASE_NAME$$;                                                        -- $$ DATABASE_NAME $$

-- Create database xpensDB, with utf8 and utf8_general_ci
create database if not exists $$DATABASE_NAME$$ character set utf8 collate utf8_general_ci;       -- $$ DATABASE_NAME $$

-- Employ $$DATABASE_NAME$$
use $$DATABASE_NAME$$;                                                                            -- $$ DATABASE_NAME $$

-- drop table if exists systemSettings;

-- 1. T1. systemSettings table to store system settings
create table if not exists systemSettings (
    id                                        int ( 10 ) unsigned              not null auto_increment,
    name                                      varchar( 32 )                    not null,
    value                                     varchar( 255 )                   not null,
    enabled                                   tinyint ( 1 )                    unsigned default 0,
    created                                   datetime                         not null,
    lastUpdate                                datetime                         not null,
    key ( id ),
    index ix_name ( name )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

drop procedure if exists populateSystemSettings;

delimiter //

-- 2. P1. populateSystemSettings SP to get initial set of settings required for app.
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

-- 3. P2. getSystemSetting SP to get the value for a provided setting name.
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
