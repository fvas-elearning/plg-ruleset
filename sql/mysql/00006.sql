-- --------------------------------------------
-- @version 3.2.18
-- @author: Michael Mifsud <info@tropotek.com>
--
-- --------------------------------------------

alter table rule add static TINYINT(1) default 1 not null after max;
UPDATE rule SET static = 0 WHERE LENGTH(label) = 1 OR (label = 'small' OR label = 'prod' OR label = 'equine' OR label = 'other');

