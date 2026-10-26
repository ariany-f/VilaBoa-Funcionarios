-- Portal do Colaborador Vila Boa — tabelas de login e permissões
-- Mesma estrutura do sistema Vila Boa (users, roles, menus e permissões por tela/ação).
-- Depois de criar as tabelas: rode `php backend/scripts/sincronizar_permissoes.php`
-- com DB_DRIVER=mysql para gravar as telas e ações de backend/config/permissoes.php.

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `profile_image` VARCHAR(255) NULL DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `recovery_code` VARCHAR(255) NULL DEFAULT NULL,
  `recovery_code_expires` DATETIME NULL DEFAULT NULL,
  `cellphone` VARCHAR(20) NULL DEFAULT NULL,
  -- Vínculo com o colaborador do portal (o colaborador entra por CPF + data de nascimento)
  `cpf` VARCHAR(11) NULL DEFAULT NULL,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_cpf_unique` (`cpf`),
  KEY `users_cellphone` (`cellphone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `users_roles_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_roles_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menus` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `parent_id` INT NULL DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `icon` VARCHAR(255) NULL DEFAULT NULL,
  `allow_delete` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles_menus` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role_id` INT NOT NULL,
  `menu_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `roles_menus_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roles_menus_menu_fk` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permission_screens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(120) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `controller` VARCHAR(80) NULL DEFAULT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_screens_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permission_actions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `screen_id` INT NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `group_name` VARCHAR(120) NULL DEFAULT NULL,
  `action_kind` VARCHAR(20) NOT NULL DEFAULT 'execute',
  `route_controller` VARCHAR(80) NULL DEFAULT NULL,
  `route_action` VARCHAR(120) NULL DEFAULT NULL,
  `http_methods` VARCHAR(60) NULL DEFAULT NULL,
  `enforce_route` TINYINT(1) NOT NULL DEFAULT 0,
  `ui_selector` VARCHAR(255) NULL DEFAULT NULL,
  `meta` TEXT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_actions_screen_slug` (`screen_id`, `slug`),
  KEY `permission_actions_route` (`route_controller`, `route_action`),
  CONSTRAINT `permission_actions_screen_fk` FOREIGN KEY (`screen_id`) REFERENCES `permission_screens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles_permission_actions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role_id` INT NOT NULL,
  `permission_action_id` INT NOT NULL,
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_permission_actions_unique` (`role_id`, `permission_action_id`),
  CONSTRAINT `rpa_role_fk` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rpa_action_fk` FOREIGN KEY (`permission_action_id`) REFERENCES `permission_actions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Perfis e menu iniciais
INSERT INTO `roles` (`id`, `nome`, `created`, `modified`) VALUES
  (1, 'Administrador', NOW(), NOW()),
  (2, 'RH', NOW(), NOW()),
  (3, 'RH - Consulta', NOW(), NOW());

INSERT INTO `menus` (`id`, `name`, `url`, `parent_id`, `position`, `icon`, `allow_delete`, `created`, `modified`) VALUES
  (1, 'Painel do RH', '/rh', NULL, 1, 'shield-check', 0, NOW(), NOW());

INSERT INTO `roles_menus` (`role_id`, `menu_id`) VALUES (1, 1), (2, 1), (3, 1);
