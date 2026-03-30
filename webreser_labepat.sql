-- phpMyAdmin SQL Dump
-- version 4.9.11
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 26-03-2026 a las 14:38:50
-- Versión del servidor: 5.6.20
-- Versión de PHP: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `webreser_labepat`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_field` int(10) UNSIGNED DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time_from` varchar(20) DEFAULT NULL,
  `time_until` varchar(20) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `visitors` varchar(50) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `total_payment` bit(1) DEFAULT NULL,
  `total` float DEFAULT NULL,
  `parcial` float DEFAULT NULL,
  `diference` float DEFAULT NULL,
  `reservation` float DEFAULT NULL,
  `payment` float DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `approved` bit(1) DEFAULT NULL,
  `mp` bit(1) DEFAULT NULL,
  `use_offer` bit(1) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `annulled` bit(1) DEFAULT b'0',
  `id_customer` int(10) UNSIGNED DEFAULT NULL,
  `id_preference_parcial` varchar(250) NOT NULL,
  `id_preference_total` varchar(250) NOT NULL,
  `booking_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `area_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `complete_phone` varchar(20) DEFAULT NULL,
  `city` varchar(150) DEFAULT NULL,
  `offer` float DEFAULT '0',
  `quantity` int(11) DEFAULT '0',
  `email` varchar(50) DEFAULT NULL,
  `type_institution` varchar(50) DEFAULT NULL,
  `user_name` varchar(50) DEFAULT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `deleted` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `customers`
--

INSERT INTO `customers` (`id`, `name`, `last_name`, `dni`, `area_code`, `phone`, `complete_phone`, `city`, `offer`, `quantity`, `email`, `type_institution`, `user_name`, `pass`, `deleted`) VALUES
(10, 'Institución de prueba', NULL, '23365607109', '0220', '4708610', '02204708610', 'Marcos Paz', 50, 41, 'alejandobr@gmail.com', 'test', NULL, NULL, b'0'),
(11, 'Institución Test', NULL, '23365607109', '11', '23509958', '1123509958', 'Marcos Paz', 0, 0, 'alejandrotorres_lp@hotmail.com', 'institución_educativa', NULL, NULL, b'0'),
(12, 'ESCUELA RURAL MARCOS PAZ', NULL, '20359025832', '011', '63365402', '01163365402', 'MARCOS PAZ', 0, 1, 'dantunez38@gmail.com', 'escuela_rural', NULL, NULL, b'0'),
(13, 'lucia romera', NULL, '27331852096', '54', '2944967828', '542944967828', 'el bolson', 0, 2, 'astrodansoma@hotmail.com', 'agencia', NULL, NULL, b'0'),
(14, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(15, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(16, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(17, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(18, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(19, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(20, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(21, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(22, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(23, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(24, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(25, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(26, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(27, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(28, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(29, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(30, 'Institución de prueba', NULL, NULL, '0220', '4708610', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(31, 'Fran Firpo', NULL, '23283831329', '294', '4416200', '2944416200', 'The Hoyo', 0, 0, 'franfirpo@hotmail.com', 'test', NULL, NULL, b'0'),
(32, 'ESCUELA RURAL MARCOS PAZ', NULL, NULL, '011', '63365402', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(33, 'Lucia Romera', NULL, '33185209', '0294', '4967828', '02944967828', 'EL HOYO', 50, 0, 'sidralaberinto@gmail.com', 'agencia', NULL, NULL, b'0'),
(34, 'Lucia Romera', NULL, NULL, '0294', '4967828', NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fields`
--

CREATE TABLE `fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `floor_type` varchar(250) DEFAULT NULL,
  `sizes` varchar(250) DEFAULT NULL,
  `ilumination` bit(1) DEFAULT NULL,
  `field_type` varchar(250) DEFAULT NULL,
  `roofed` bit(1) DEFAULT NULL,
  `value` float DEFAULT NULL,
  `ilumination_value` float DEFAULT NULL,
  `elements_rent` bit(1) DEFAULT NULL,
  `disabled` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `fields`
--

INSERT INTO `fields` (`id`, `name`, `floor_type`, `sizes`, `ilumination`, `field_type`, `roofed`, `value`, `ilumination_value`, `elements_rent`, `disabled`) VALUES
(1, 'Laberinto', '', '20x10', b'0', '', b'0', NULL, NULL, b'0', b'0');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mercado_pago`
--

CREATE TABLE `mercado_pago` (
  `id` int(10) UNSIGNED NOT NULL,
  `collection_id` varchar(50) NOT NULL,
  `collection_status` varchar(50) NOT NULL,
  `payment_id` varchar(50) NOT NULL,
  `status` varchar(11) NOT NULL,
  `external_reference` varchar(50) DEFAULT NULL,
  `payment_type` varchar(20) NOT NULL,
  `merchant_order_id` varchar(50) NOT NULL,
  `preference_id` varchar(250) NOT NULL,
  `site_id` varchar(20) NOT NULL,
  `processing_mode` varchar(20) NOT NULL,
  `merchant_account_id` varchar(50) DEFAULT NULL,
  `annulled` bit(1) DEFAULT b'0',
  `id_booking` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mercado_pago_keys`
--

CREATE TABLE `mercado_pago_keys` (
  `id` int(10) UNSIGNED NOT NULL,
  `public_key` varchar(500) DEFAULT NULL,
  `access_token` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `mercado_pago_keys`
--

INSERT INTO `mercado_pago_keys` (`id`, `public_key`, `access_token`) VALUES
(7, 'APP_USR-b5415ed5-f6c0-46d3-89c1-fa25074a8bfc', 'APP_USR-584685606778950-092608-8b3a52be0b73ed70e74621ddd0b64169-205737781');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `offers`
--

CREATE TABLE `offers` (
  `id` int(10) UNSIGNED NOT NULL,
  `value` int(11) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_booking` int(10) UNSIGNED NOT NULL,
  `id_customer` int(10) UNSIGNED DEFAULT NULL,
  `id_mercado_pago` int(10) UNSIGNED DEFAULT NULL,
  `amount` float NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rate`
--

CREATE TABLE `rate` (
  `id` int(10) UNSIGNED NOT NULL,
  `value` int(11) NOT NULL,
  `qty_visitors` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `rate`
--

INSERT INTO `rate` (`id`, `value`, `qty_visitors`) VALUES
(1, 30, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `time`
--

CREATE TABLE `time` (
  `id` int(10) UNSIGNED NOT NULL,
  `from` varchar(10) NOT NULL,
  `until` varchar(10) NOT NULL,
  `from_cut` varchar(10) DEFAULT NULL,
  `until_cut` varchar(10) DEFAULT NULL,
  `nocturnal_time` varchar(10) DEFAULT NULL,
  `is_sunday` bit(1) DEFAULT NULL,
  `is_saturday` bit(1) DEFAULT NULL,
  `is_friday` bit(1) DEFAULT NULL,
  `is_thursday` bit(1) DEFAULT NULL,
  `is_wednesday` bit(1) DEFAULT NULL,
  `is_tuesday` bit(1) DEFAULT NULL,
  `is_monday` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `time`
--

INSERT INTO `time` (`id`, `from`, `until`, `from_cut`, `until_cut`, `nocturnal_time`, `is_sunday`, `is_saturday`, `is_friday`, `is_thursday`, `is_wednesday`, `is_tuesday`, `is_monday`) VALUES
(5, '09:00', '19:00', NULL, NULL, NULL, b'1', NULL, b'1', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uploads`
--

CREATE TABLE `uploads` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `main_color` varchar(50) DEFAULT NULL,
  `secondary_color` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `uploads`
--

INSERT INTO `uploads` (`id`, `name`, `main_color`, `secondary_color`) VALUES
(2, '68d6a48d53bc6.png', '#0d6a3a', '#e98521');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `user` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `superadmin` bit(1) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active` bit(1) NOT NULL DEFAULT b'1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `user`, `password`, `superadmin`, `name`, `active`) VALUES
(1, 'testuser', '$2y$10$jlRsLrGdwCeREMurdGwRQOnKNpimpc19tHXZCl6AEDN2rcKR3SF.K', b'1', 'Testuser', b'1'),
(3, 'Luciana', '$2y$10$deFFxusHvmE6mBaLhsZmp.AOD9nrfCAKZBNNOgAicTPB42WKq1Jaa', b'0', 'Administrador', b'1'),
(4, 'Prueba', '$2y$10$HzMiB103zCcfih2okpzkHerWZm1dE3c5GExT58i5hbn4F64GGgVmi', b'0', 'Prueba', b'1'),
(5, 'admin', '$2y$10$iOST5b1NLtr6HgMS9.MDpO2qttknPJKhQgk0FB/Ba97mlJiL/sqQa', b'1', 'Administración', b'1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `values`
--

CREATE TABLE `values` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `value` varchar(50) DEFAULT NULL,
  `amount` double DEFAULT NULL,
  `extra_amount` double DEFAULT NULL,
  `disabled` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `values`
--

INSERT INTO `values` (`id`, `name`, `value`, `amount`, `extra_amount`, `disabled`) VALUES
(1, 'Escuela rural', 'escuela_rural', 18000, NULL, b'0'),
(2, 'Educación especial', 'educación_especial', 25000, NULL, b'0'),
(3, 'Institución educativa', 'institución_educativa', 35000, NULL, b'0'),
(5, 'Agencia', 'agencia', 20000, NULL, b'0'),
(6, 'Test', 'test', 0.1, NULL, b'0'),
(7, 'Particular', 'particular', 20000, NULL, b'0');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `bookings_id_field_foreign` (`id_field`),
  ADD KEY `bookings_id_customer_foreign` (`id_customer`);

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mercado_pago`
--
ALTER TABLE `mercado_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mercado_pago_id_booking_foreign` (`id_booking`);

--
-- Indices de la tabla `mercado_pago_keys`
--
ALTER TABLE `mercado_pago_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_id_user_foreign` (`id_user`),
  ADD KEY `payments_id_booking_foreign` (`id_booking`),
  ADD KEY `payments_id_customer_foreign` (`id_customer`);

--
-- Indices de la tabla `rate`
--
ALTER TABLE `rate`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `time`
--
ALTER TABLE `time`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `values`
--
ALTER TABLE `values`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `fields`
--
ALTER TABLE `fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `mercado_pago`
--
ALTER TABLE `mercado_pago`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mercado_pago_keys`
--
ALTER TABLE `mercado_pago_keys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rate`
--
ALTER TABLE `rate`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `time`
--
ALTER TABLE `time`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `values`
--
ALTER TABLE `values`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_id_customer_foreign` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_id_field_foreign` FOREIGN KEY (`id_field`) REFERENCES `fields` (`id`);

--
-- Filtros para la tabla `mercado_pago`
--
ALTER TABLE `mercado_pago`
  ADD CONSTRAINT `mercado_pago_id_booking_foreign` FOREIGN KEY (`id_booking`) REFERENCES `bookings` (`id`);

--
-- Filtros para la tabla `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_id_booking_foreign` FOREIGN KEY (`id_booking`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `payments_id_customer_foreign` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `payments_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
