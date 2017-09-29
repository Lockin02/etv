ALTER TABLE `zxt_hotel_category` DROP INDEX `hid`;

CREATE INDEX `hid_pid` ON `zxt_hotel_category`(`hid`, `pid`) USING BTREE ;

ALTER TABLE `zxt_hotel_resource` DROP INDEX `hid_cid`;

CREATE INDEX `hid_cid_cat` ON `zxt_hotel_resource`(`hid`, `category_id`, `cat`) USING BTREE ;