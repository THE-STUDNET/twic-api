INSERT IGNORE INTO `permission` (`libelle`) VALUES ('conversation.get');
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`) VALUES (5, 
(SELECT `id` FROM `permission` WHERE `libelle`= 'conversation.get'));