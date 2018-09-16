--
-- add foreign key
--
ALTER TABLE `usr_profile_values` ADD  CONSTRAINT `uid` FOREIGN KEY (`user_id`) REFERENCES `usr_users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
--
-- delete foreign key
--
alter table cms.fragment_fields_values drop foreign key fragment_value_vid