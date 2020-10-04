-- ****************************** Module Header ******************************
-- Module Name:  xm website SQL file
-- Project:      xm website to track everyday expenses
-- Copyright (c) Sundar Krishnamurthy sundar@passion8cakes.com
-- All rights reserved.
--
-- xm.sql file to create the database, load metadata tables and stored procedures - please employ MySQL 5.5 or greater
--
-- 1.     T1. mails table to store emails that need to be sent.
-- 2.     T2. mailsLog table to store log of emails that were sent.
-- 3.     T3. mailAttachments table to store attachments, that need to be sent (if any).
-- 4.     T4. mailApiKeys table to store mail API keys, valid ones we honor to dispatch email.
-- 5.     P1. addEmail stored procedure to fetch the active and email value for furnished API key.
-- 6.     P2. addMailAttachment stored procedure to add attachments to an email that was stored prior
-- 7.     P3. checkMailApiKey stored procedure to fetch the active and email value for furnished API key.
-- 8.     P4. markEmailAsReady stored procedure to change the status of an email as ready to send.
-- 9.     P5. logEmailDispatch stored procedure to change the status of an email as ready to send.
-- 10.    P6. getEmailToSend stored procedure to get the next email record that needs to be sent.
-- 11.    P7. getAttachmentsForEmail stored procedure to get the next email record that needs to be sent.
-- 12.    P8. deleteEmail stored procedure to delete the email whose ID is provided.
-- 13.    P9. populateApiKeys stored procedure to insert API keys into mailApiKeys table.
-- 14.    T5. appLogs table to store application logs.
-- 15.   P10. addAppLog stored procedure to add a certain string or log to the appLogs table.
-- 16.   P11. showLatestAppLogs stored procedure to fetch the latest configurable amount of logs inserted.
-- 17.   P12. clearAppLogs stored procedure to remove all entries from appLogs table.
-- 18.    T6. systemSettings table - table to store system settings, queried at session start and other places.
-- 19.   P13: populateSystemSettings SP - to get initial set of settings required for app.
-- 20.   P14: getSystemSetting SP - to get the value for a provided setting name.
--
-- Revisions:
--      1. Sundar Krishnamurthy         sundar@passion8cakes.com               9/25/2020       Initial file created.

-- Very, very, very bad things happen if you uncomment this line below. Do at your peril, you have been warned!
-- drop database if exists $$DATABASE_NAME$$;                                                     -- $$ DATABASE_NAME $$

-- Create database $$DATABASE_NAME$$, with utf8 and utf8_general_ci
create database if not exists $$DATABASE_NAME$$ character set utf8 collate utf8_general_ci;       -- $$ DATABASE_NAME $$

-- Employ $$DATABASE_NAME$$
use $$DATABASE_NAME$$;                                                                            -- $$ DATABASE_NAME $$

-- drop table if exists mails;

-- 1. T1. mails table to store emails that need to be sent
create table if not exists mails (
    mailId                                    int( 10 ) unsigned               not null auto_increment,
    sender                                    varchar ( 64 )                   default null,
    senderEmail                               varchar ( 128 )                  default null,
    recipients                                varchar ( 4096 )                 not null,
    ccRecipients                              varchar ( 4096 )                 default null,
    bccRecipients                             varchar ( 4096 )                 default null,
    replyTo                                   varchar ( 128 )                  default null,
    subject                                   varchar ( 236 )                  not null,
    subjectPrefix                             varchar ( 16 )                   default null,
    body                                      text,
    ready                                     tinyint( 1 ) unsigned            not null default 0,
    hasAttachments                            tinyint( 1 ) unsigned            not null default 0,
    importance                                tinyint( 1 ) unsigned            not null default 0,
    timestamp                                 datetime                         default null,
    created                                   datetime                         not null,
    KEY                                       mailId ( mailId )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

-- drop table if exists mailsLog;

-- 2. T2. mailsLog table to store log of emails that were sent
create table if not exists mailsLog (
    logId                                     int( 10 ) unsigned               not null auto_increment,
    apiKeyId                                  int( 10 ) unsigned               not null,
    mailId                                    int( 10 ) unsigned               default null,
    sender                                    varchar ( 128 )                  not null,
    recipient                                 varchar ( 4096 )                 not null,
    subject                                   varchar ( 255 )                  not null,
    size                                      int( 10 ) unsigned               default null,
    timestamp                                 datetime                         not null,
    KEY                                       logId ( logId )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

-- drop table if exists mailsAttachments;

-- 3. T3. mailAttachments table to store attachments, that need to be sent (if any)
create table if not exists mailAttachments (
    mailAttachmentId                          int( 10 ) unsigned               not null auto_increment,
    mailId                                    int( 10 ) unsigned               not null,
    filename                                  varchar ( 1024 )                 not null,
    filesize                                  int( 10 ) unsigned               not null,
    attachment                                longblob                         not null,
    created                                   datetime                         not null,
    KEY                                       mailAttachmentId ( mailAttachmentId )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

-- drop table if exists mailApiKeys;

-- 4. T4. mailApiKeys table to store mail API keys, valid ones we honor to dispatch email
create table if not exists mailApiKeys (
    apiId                                     int( 10 ) unsigned               not null auto_increment,
    apiKey                                    varchar ( 32 )                   not null,
    email                                     varchar ( 128 )                  not null,
    active                                    tinyint( 1 ) unsigned            not null default 0,
    created                                   datetime                         not null,
    lastUpdate                                datetime                         not null,
    KEY                                       apiId  ( apiId ),
    UNIQUE INDEX                              i_apiKey ( apiKey )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

drop procedure if exists addEmail;

delimiter //

-- 5. P1. addEmail stored procedure to fetch the active and email value for furnished API key.
create procedure addEmail (
    in p_apiKey                              varchar ( 32 ),
    in p_sender                              varchar ( 64 ),
    in p_senderEmail                         varchar ( 128 ),
    in p_recipients                          varchar ( 4096 ),
    in p_ccRecipients                        varchar ( 4096 ),
    in p_bccRecipients                       varchar ( 4096 ),
    in p_replyTo                             varchar ( 128 ),
    in p_subject                             varchar ( 236 ),
    in p_subjectPrefix                       varchar ( 64 ),
    in p_body                                text,
    in p_markMailAsReady                     tinyint( 1 ) unsigned,
    in p_hasAttachments                      tinyint( 1 ) unsigned,
    in p_importance                          tinyint( 1 ) unsigned,
    in p_timestamp                           datetime
)
begin

    declare l_apiId                          int( 10 ) unsigned default null;

    select apiId into l_apiId
    from mailApiKeys
    where apiKey = p_apiKey
    and active = 1;

    if l_apiId is not null then

       insert mails (
           sender,
           senderEmail,
           recipients,
           ccRecipients,
           bccRecipients,
           replyTo,
           subject,
           subjectPrefix,
           body,
           ready,
           hasAttachments,
           importance,
           timestamp,
           created
        ) values (
           p_sender,
           p_senderEmail,
           p_recipients,
           p_ccRecipients,
           p_bccRecipients,
           p_replyTo,
           p_subject,
           p_subjectPrefix,
           p_body,
           p_markMailAsReady,
           p_hasAttachments,
           p_importance,
           p_timestamp,
           utc_timestamp()
        );

        select last_insert_id() as mailId;

    end if;
end //

delimiter ;

drop procedure if exists addMailAttachment;

delimiter //

-- 6. P2. addMailAttachment stored procedure to add attachments to an email that was stored prior
create procedure addMailAttachment (
    in p_mailId                              int( 10 ) unsigned,
    in p_filename                            varchar ( 1024 ),
    in p_filesize                            int( 10 ) unsigned,
    in p_attachment                          longblob
)
begin

    declare l_mailId             int( 10 ) unsigned;
    declare l_hasAttachments     bit;

    set l_mailId = null;
    set l_hasAttachments = null;

    select
        hasAttachments, mailId
    into
        l_hasAttachments, l_mailId
    from
        mails
    where
        mailId = p_mailId;

    if l_hasAttachments is not null and l_hasAttachments = 0 then
        update
            mails
        set
            hasAttachments = 1
        where
            mailId = p_mailId;
    end if;

    if l_mailId is not null then
        insert mailAttachments (
            mailId,
            filename,
            filesize,
            attachment,
            created
        ) values (
            p_mailId,
            p_filename,
            p_filesize,
            p_attachment,
            utc_timestamp()
        );

        select last_insert_id() as mailAttachmentId;
    else
        select null as mailAttachmentId;
    end if;
end //

delimiter ;

drop procedure if exists checkMailApiKey;

delimiter //

-- 7. P3. checkMailApiKey stored procedure to fetch the active and email value for furnished API key.
create procedure checkMailApiKey (
    in p_apiKey                               varchar ( 32 )
)
begin

    select
        apiId as apiKeyId,
        active,
        email
    from
        mailApiKeys
    where
        apiKey = p_apiKey;
end //

delimiter ;

drop procedure if exists markEmailAsReady;

delimiter //

-- 8. P4 markEmailAsReady stored procedure to change the status of an email as ready to send
create procedure markEmailAsReady (
    in p_mailId                              int( 10 ) unsigned
)
begin

    declare l_ready bit;
    set l_ready = null;

    select
        ready into l_ready
    from
        mails
    where
        mailId = p_mailId;

    if l_ready is not null and l_ready = 0 then
        update
            mails
        set
            ready = 1
        where
            mailId = p_mailId;

    end if;

    select p_mailId as mailId;
end //

delimiter ;

drop procedure if exists logEmailDispatch;

delimiter //

-- 9. P5 logEmailDispatch stored procedure to change the status of an email as ready to send
create procedure logEmailDispatch (
    in p_apiKeyId                             int( 10 ) unsigned,
    in p_senderEmail                          varchar ( 128 ),
    in p_recipients                           varchar ( 4096 ),
    in p_subject                              varchar ( 255 ),
    in p_size                                 int( 10 ) unsigned
)
begin

    insert mailsLog (
        apiKeyId,
        sender,
        recipient,
        subject,
        size,
        timestamp
    ) values (
        p_apiKeyId,
        p_senderEmail,
        p_recipients,
        p_subject,
        p_size,
        utc_timestamp()
    );

    select last_insert_id() as logId;

end //

delimiter ;

drop procedure if exists getEmailToSend;

delimiter //

-- 10. P6. getEmailToSend stored procedure to get the next email record that needs to be sent
create procedure getEmailToSend (
    in p_timestamp                            datetime
)
begin

    select
        mailId,
        sender,
        senderEmail,
        recipients,
        subject,
        subjectPrefix,
        ccRecipients,
        bccRecipients,
        replyTo,
        body,
        hasAttachments,
        importance,
        created
    from
        mails
    where
        ready = 1
    and
        ((timestamp is null) or (timestamp < p_timestamp))
    order by
        mailId
    limit 1;
end //

delimiter ;

drop procedure if exists getAttachmentsForEmail;

delimiter //

-- 11. P7 getAttachmentsForEmail stored procedure to get the next email record that needs to be sent
create procedure getAttachmentsForEmail (
    in p_mailId                              int( 10 ) unsigned
)
begin

    select
        mailAttachmentId,
        mailId,
        filename,
        filesize,
        attachment,
        created
    from
        mailAttachments
    where
        mailId = p_mailId
    order by
        mailAttachmentId;
end //

delimiter ;

delimiter ;

drop procedure if exists deleteEmail;

delimiter //

-- 12. P8 deleteEmail stored procedure to delete the email whose ID is provided
create procedure deleteEmail (
    in p_mailId                               int( 10 ) unsigned
)
begin

    declare l_mailId                          int( 10 ) unsigned default null;

    select mailId
        into l_mailId
    from
        mails
    where
        mailId = p_mailId;

    if l_mailId is not null then
        
        delete
        from
            mailAttachments
        where
            mailId = p_mailId;

        delete
        from
            mails
        where
            mailId = p_mailId;

        select p_mailId as mailId;
    end if;
end //

delimiter ;

drop procedure if exists populateApiKeys;

delimiter //

-- 13. P9: populateApiKeys SP - populateApiKeys stored procedure to insert API keys into mailApiKeys table.
create procedure populateApiKeys()
begin
    declare l_apiKeysCount                    int( 10 ) unsigned;

    select count(*) into l_apiKeysCount from mailApiKeys;

    if l_apiKeysCount = 0 then

        insert mailApiKeys ( apiKey, email, active, created, lastUpdate)
        values ('$$MAIL_API_KEY$$',                                             -- $$ MAIL_API_KEY $$
                '$$ADMIN_EMAIL$$',                                              -- $$ ADMIN_EMAIL $$
                1, utc_timestamp(), utc_timestamp());

    end if;
end //

delimiter ;

call populateApiKeys();

drop procedure populateApiKeys;

-- drop table if exists appLogs;

-- 14. T5. appLogs table to store application logs.
create table if not exists appLogs (
    id                                        int( 10 ) unsigned               not null auto_increment,
    log                                       varchar ( 255 )                  not null,
    created                                   datetime                         not null,
    key ( id )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

drop procedure if exists addAppLog;

delimiter //

-- 15. P9. addAppLog stored procedure to add a certain string or log to the appLogs table.
create procedure addAppLog(
    in            p_log                       varchar( 255 )
)
begin

    declare l_logId                           int( 10 ) unsigned default 0;

    insert appLogs (log, created)
    values ( p_log, utc_timestamp() );

    select last_insert_id() into l_logId;
    select l_logId as logId;

end //

delimiter ;

drop procedure if exists showLatestAppLogs;

delimiter //

-- 16. P11. showLatestAppLogs stored procedure to fetch the latest configurable amount of logs inserted.
create procedure showLatestAppLogs(
    in            p_count                     int( 10 ) unsigned
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

-- 17. P12. clearAppLogs stored procedure to remove all entries from appLogs table.
create procedure clearAppLogs()
begin
    truncate table appLogs;

end //

delimiter ;

-- drop table if exists systemSettings;

-- 18. T6. systemSettings table - table to store system settings, queried at session start and other places.
create table if not exists systemSettings (
    id                                        int( 10 ) unsigned              not null auto_increment,
    name                                      varchar ( 32 )                  not null,
    value                                     varchar ( 255 )                 not null,
    enabled                                   tinyint( 1 )                    unsigned default 0,
    created                                   datetime                        not null,
    lastUpdate                                datetime                        not null,
    KEY ( id ),
    UNIQUE INDEX ix_name ( name )
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8;

drop procedure if exists populateSystemSettings;

delimiter //

-- 19. P13: populateSystemSettings SP - to get initial set of settings required for app.
create procedure populateSystemSettings()
begin

    declare l_settingsCount                   int( 10 ) unsigned;

    select count(*) into l_settingsCount from systemSettings;

    if l_settingsCount = 0 then

        insert systemSettings ( name, value, enabled, created, lastUpdate)
        values ('logAllCalls', '0', 1, utc_timestamp(), utc_timestamp());

    end if;
end //

delimiter ;

call populateSystemSettings();

drop procedure populateSystemSettings;

drop procedure if exists getSystemSetting;

delimiter //

-- 20. P14: getSystemSetting SP - to get the value for a provided setting name.
create procedure getSystemSetting(
    in            p_name                      varchar ( 32 )
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
