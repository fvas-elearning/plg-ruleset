-- ----------------------------------------------------
-- SQL
-- 
-- Author: Michael Mifsud <info@tropotek.com>
--
-- ----------------------------------------------------

alter table rule change profile_id course_id int unsigned default 0 not null;
drop index rule_profile_id_index on rule;
create index course_id on rule (course_id);


