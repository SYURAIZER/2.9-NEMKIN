-- phpMyAdmin SQL Dump
-- version 5.2.3-1.red80
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Май 14 2026 г., 05:11
-- Версия сервера: 10.11.16-MariaDB
-- Версия PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `nemkin`
--

-- --------------------------------------------------------

--
-- Структура таблицы `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_type` enum('individual','legal_entity') NOT NULL COMMENT 'Тип лица',
  `full_name` varchar(255) NOT NULL COMMENT 'ФИО или наименование',
  `inn` varchar(12) DEFAULT NULL COMMENT 'ИНН',
  `kpp` varchar(9) DEFAULT NULL COMMENT 'КПП',
  `ogrn` varchar(15) DEFAULT NULL COMMENT 'ОГРН',
  `legal_address` text DEFAULT NULL COMMENT 'Юридический адрес',
  `bank_details` text DEFAULT NULL COMMENT 'Банковские реквизиты',
  `phone` varchar(20) NOT NULL COMMENT 'Телефон',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email',
  `contact_person` varchar(255) DEFAULT NULL COMMENT 'Контактное лицо',
  `source` varchar(100) DEFAULT NULL COMMENT 'Источник привлечения',
  `status` enum('active','archived') DEFAULT 'active' COMMENT 'Статус',
  `notes` text DEFAULT NULL COMMENT 'Примечания',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Клиенты агентства';

--
-- Дамп данных таблицы `clients`
--

INSERT INTO `clients` (`id`, `client_type`, `full_name`, `inn`, `kpp`, `ogrn`, `legal_address`, `bank_details`, `phone`, `email`, `contact_person`, `source`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'individual', 'Петров Иван Игорович', '23352', NULL, NULL, NULL, NULL, '+7909909099', 'petrov@gmail.com', NULL, NULL, 'active', NULL, '2026-05-07 03:24:26', '2026-05-07 03:24:26');

-- --------------------------------------------------------

--
-- Структура таблицы `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `last_name` varchar(100) NOT NULL COMMENT 'Фамилия',
  `first_name` varchar(100) NOT NULL COMMENT 'Имя',
  `middle_name` varchar(100) DEFAULT NULL COMMENT 'Отчество',
  `position` enum('manager','director','admin') NOT NULL DEFAULT 'manager' COMMENT 'Должность',
  `login` varchar(50) NOT NULL COMMENT 'Логин',
  `password_hash` varchar(255) NOT NULL COMMENT 'Хэш пароля',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Активен',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Сотрудники (пользователи системы)';

--
-- Дамп данных таблицы `employees`
--

INSERT INTO `employees` (`id`, `last_name`, `first_name`, `middle_name`, `position`, `login`, `password_hash`, `is_active`, `created_at`) VALUES
(2, 'Немкин', 'Алексей', 'Александрович', 'admin', 'syur', '$2y$10$/OKL/apV5KX6kr4qpl2JeOkfC1Ki3TplyQFb22VIAs7JxHzE7Z2jK', 1, '2026-05-07 03:53:22'),
(3, 'Алексей', 'Александрович', 'Немкин', 'admin', 'admin', '$2y$10$Slgq22s/p.UpcAqXfONJa.PZn0Z3tAdNw.a2QllWWIkiHR4tDlMaS', 1, '2026-05-07 04:06:28');

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL COMMENT 'Клиент',
  `employee_id` int(10) UNSIGNED NOT NULL COMMENT 'Менеджер',
  `order_date` date NOT NULL COMMENT 'Дата оформления',
  `planned_completion_date` date DEFAULT NULL COMMENT 'Плановая дата завершения',
  `status` enum('new','negotiation','paid','in_progress','completed','cancelled') DEFAULT 'new' COMMENT 'Статус',
  `total_amount` decimal(12,2) DEFAULT 0.00 COMMENT 'Итоговая сумма',
  `discount_percent` decimal(5,2) DEFAULT 0.00 COMMENT 'Скидка %',
  `discount_absolute` decimal(12,2) DEFAULT 0.00 COMMENT 'Скидка абсолютная',
  `vat_rate` decimal(5,2) DEFAULT 20.00 COMMENT 'Ставка НДС',
  `notes` text DEFAULT NULL COMMENT 'Примечания',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Заказы';

-- --------------------------------------------------------

--
-- Структура таблицы `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL COMMENT 'Заказ',
  `service_id` int(10) UNSIGNED NOT NULL COMMENT 'Услуга',
  `placement_date` date NOT NULL COMMENT 'Дата выхода',
  `placement_time` time DEFAULT NULL COMMENT 'Время выхода',
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'Количество выходов',
  `duration` decimal(10,2) DEFAULT NULL COMMENT 'Хронометраж (сек)',
  `price_per_unit` decimal(12,2) NOT NULL COMMENT 'Цена за единицу',
  `total_price` decimal(12,2) NOT NULL COMMENT 'Стоимость позиции'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Позиции медиаплана (детали заказа)';

-- --------------------------------------------------------

--
-- Структура таблицы `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Категория',
  `name` varchar(255) NOT NULL COMMENT 'Наименование услуги',
  `unit` varchar(50) NOT NULL COMMENT 'Единица измерения',
  `base_price` decimal(12,2) NOT NULL COMMENT 'Базовая стоимость',
  `cost_price` decimal(12,2) DEFAULT NULL COMMENT 'Себестоимость',
  `min_order_qty` int(11) DEFAULT 1 COMMENT 'Минимальный объём',
  `seasonal_coefficient` decimal(5,2) DEFAULT 1.00 COMMENT 'Сезонный коэффициент',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Активна',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Прайс-лист услуг';

-- --------------------------------------------------------

--
-- Структура таблицы `service_categories`
--

CREATE TABLE `service_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Название категории',
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Родительская категория'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Иерархический справочник категорий услуг';

--
-- Дамп данных таблицы `service_categories`
--

INSERT INTO `service_categories` (`id`, `name`, `parent_id`) VALUES
(1, 'Наружная реклама', NULL),
(2, 'Интернет', NULL),
(3, 'Полиграфия', NULL),
(4, 'ТВ/Радио', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_phone` (`phone`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_inn` (`inn`);

--
-- Индексы таблицы `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_login` (`login`),
  ADD KEY `idx_position` (`position`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_date` (`order_date`);

--
-- Индексы таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_service` (`service_id`);

--
-- Индексы таблицы `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_name` (`name`);

--
-- Индексы таблицы `service_categories`
--
ALTER TABLE `service_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `service_categories`
--
ALTER TABLE `service_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Ограничения внешнего ключа таблицы `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Ограничения внешнего ключа таблицы `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `service_categories`
--
ALTER TABLE `service_categories`
  ADD CONSTRAINT `service_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
