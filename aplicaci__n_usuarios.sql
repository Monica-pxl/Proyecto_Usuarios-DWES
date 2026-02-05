-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-01-2026 a las 12:00:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `aplicación_usuarios`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260116080939', '2026-01-16 09:09:52', 703);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensage`
--

CREATE TABLE `mensage` (
  `id` int(11) NOT NULL,
  `contenido` longtext NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `leido_por` longtext DEFAULT NULL,
  `autor_id` int(11) NOT NULL,
  `sala_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sala`
--

CREATE TABLE `sala` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `activa` tinyint(4) NOT NULL,
  `fecha_creacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sala_user`
--

CREATE TABLE `sala_user` (
  `sala_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token_autenticacion` varchar(255) DEFAULT NULL,
  `latitud` double DEFAULT NULL,
  `longitud` double DEFAULT NULL,
  `fecha_actualizacion_ubicacion` varchar(255) DEFAULT NULL,
  `estado` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user`
--

INSERT INTO `user` (`id`, `nombre`, `correo`, `password`, `token_autenticacion`, `latitud`, `longitud`, `fecha_actualizacion_ubicacion`, `estado`) VALUES
(1, 'Admin', 'admin@proyecto.com', '$2y$13$VR3uztblOmZJPVB2PLWyyeh5bcrjRUFjTeaMvU4NDgMRtWNN8YqwS', '9a220c4b1546dc1139f399ab14fee2f0a6b011167a5618c19c1334cafdac3c9b', NULL, NULL, NULL, 1),
(2, 'Juan Gonzalez', 'juan@gmail.com', '$2y$13$vHIzY593ssD2jDJbC.UhR.I8XV.rmbeaD9OTEoutaa.5zBgOpY682', NULL, NULL, NULL, NULL, 0),
(3, 'Maria Martinez', 'maria@gmail.com', '$2y$13$XZpjA6JfM9laRXvAmlDOXO2ePuv4Z1YUMwhxJIIJJ8jqwN6jTIc3C', '3cf5971201de8c8485af02067844592332a7eb91f7b4e09e7459a846f7613d21', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_bloqueado`
--

CREATE TABLE `usuario_bloqueado` (
  `user_source` int(11) NOT NULL,
  `user_target` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Indices de la tabla `mensage`
--
ALTER TABLE `mensage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_2ECD634C14D45BBE` (`autor_id`),
  ADD KEY `IDX_2ECD634CC51CDF3F` (`sala_id`);

--
-- Indices de la tabla `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sala_user`
--
ALTER TABLE `sala_user`
  ADD PRIMARY KEY (`sala_id`,`user_id`),
  ADD KEY `IDX_AA4CE0BAC51CDF3F` (`sala_id`),
  ADD KEY `IDX_AA4CE0BAA76ED395` (`user_id`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D64977040BC9` (`correo`);

--
-- Indices de la tabla `usuario_bloqueado`
--
ALTER TABLE `usuario_bloqueado`
  ADD PRIMARY KEY (`user_source`,`user_target`),
  ADD KEY `IDX_8F201BF13AD8644E` (`user_source`),
  ADD KEY `IDX_8F201BF1233D34C1` (`user_target`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mensage`
--
ALTER TABLE `mensage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sala`
--
ALTER TABLE `sala`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mensage`
--
ALTER TABLE `mensage`
  ADD CONSTRAINT `FK_2ECD634C14D45BBE` FOREIGN KEY (`autor_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_2ECD634CC51CDF3F` FOREIGN KEY (`sala_id`) REFERENCES `sala` (`id`);

--
-- Filtros para la tabla `sala_user`
--
ALTER TABLE `sala_user`
  ADD CONSTRAINT `FK_AA4CE0BAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_AA4CE0BAC51CDF3F` FOREIGN KEY (`sala_id`) REFERENCES `sala` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuario_bloqueado`
--
ALTER TABLE `usuario_bloqueado`
  ADD CONSTRAINT `FK_8F201BF1233D34C1` FOREIGN KEY (`user_target`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_8F201BF13AD8644E` FOREIGN KEY (`user_source`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
