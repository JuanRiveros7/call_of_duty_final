-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-10-2025 a las 00:02:09
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
-- Base de datos: `call_of_duty`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `armas`
--

CREATE TABLE `armas` (
  `id_arma` int(11) NOT NULL,
  `id_tipo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dano` int(11) NOT NULL,
  `municion_max` int(11) DEFAULT 0,
  `municion_total` int(11) DEFAULT 0,
  `cadencia` int(11) DEFAULT 0,
  `descripcion` text DEFAULT NULL,
  `precision_porcentaje` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `armas`
--

INSERT INTO `armas` (`id_arma`, `id_tipo`, `nombre`, `dano`, `municion_max`, `municion_total`, `cadencia`, `descripcion`, `precision_porcentaje`, `imagen`) VALUES
(1, 1, 'Guantes Reforzados', 12, 0, 0, 120, 'Guantes acolchados que aumentan el daño cuerpo a cuerpo.', 92, 'assets/img/armas/puño/Guantes_Reforzados.png'),
(2, 1, 'Puño Clásico', 10, 0, 0, 100, 'Puño básico de combate estándar.', 90, 'assets/img/armas/puño/Puño_clasico.png'),
(3, 1, 'Puño Metálico', 15, 0, 0, 90, 'Puño con refuerzos metálicos que inflige más daño.', 88, 'assets/img/armas/puño/Puño_Metalico.png'),
(4, 2, 'Desert Eagle', 45, 7, 35, 250, 'Pistola potente de alto retroceso y gran daño.', 80, 'assets/img/armas/pistola/Desert_Egle.png'),
(5, 2, 'Glock 18', 20, 17, 85, 400, 'Pistola automática de alta cadencia y precisión media.', 70, 'assets/img/armas/pistola/Glock_18.png'),
(6, 2, 'M1911', 30, 8, 40, 280, 'Pistola clásica, confiable y equilibrada.', 75, 'assets/img/armas/pistola/M1911.png'),
(7, 3, 'AX-50', 90, 5, 20, 60, 'Rifle de francotirador de cerrojo con alta precisión.', 98, 'assets/img/armas/francotirador/AX_50.png'),
(8, 3, 'Barrett .50', 100, 5, 15, 40, 'Francotirador semiautomático de gran calibre.', 97, 'assets/img/armas/francotirador/Barrett_50.png'),
(9, 3, 'Dragunov', 85, 10, 30, 80, 'Rifle semiautomático con buena velocidad de disparo.', 94, 'assets/img/armas/francotirador/Dragunov.webp'),
(10, 4, 'M249 SAW', 45, 100, 300, 850, 'Ametralladora ligera con alta cadencia y buen control.', 65, 'assets/img/armas/ametralladora/M249_SAW.webp'),
(11, 4, 'PKM', 50, 100, 300, 800, 'Ametralladora pesada, daño alto y precisión media.', 60, 'assets/img/armas/ametralladora/PKM.webp'),
(12, 4, 'RPD', 40, 100, 300, 820, 'Ametralladora equilibrada con buen control y daño medio.', 62, 'assets/img/armas/ametralladora/RPD.webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avatar`
--

CREATE TABLE `avatar` (
  `id_avatar` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `url_imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `avatar`
--

INSERT INTO `avatar` (`id_avatar`, `nombre`, `url_imagen`) VALUES
(1, 'Avatar 1', 'assets/img/avatar/avatar.jpg'),
(2, 'Avatar 2', 'assets/img/avatar/avatar2.png'),
(3, 'Avatar 3', 'assets/img/avatar/avatar3.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `desbloqueos`
--

CREATE TABLE `desbloqueos` (
  `id_desbloqueo` int(11) NOT NULL,
  `tipo_objeto` enum('arma','personaje','mapa') NOT NULL,
  `id_objeto` int(11) NOT NULL,
  `nivel_requerido` int(11) DEFAULT 0,
  `rango_requerido` int(11) DEFAULT NULL,
  `puntos_requeridos` int(11) DEFAULT 0,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_usuario`
--

CREATE TABLE `estados_usuario` (
  `id_estado` int(11) NOT NULL,
  `nombre_estado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados_usuario`
--

INSERT INTO `estados_usuario` (`id_estado`, `nombre_estado`) VALUES
(1, 'activo'),
(2, 'inactivo'),
(3, 'bloqueado'),
(4, 'baneado'),
(5, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_acciones`
--

CREATE TABLE `historial_acciones` (
  `id_accion` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_acciones`
--

INSERT INTO `historial_acciones` (`id_accion`, `id_admin`, `id_usuario`, `accion`, `motivo`, `fecha`) VALUES
(4, 18, 18, 'bloquear', 'Cambio de estado a Bloquear', '2025-10-24 19:44:17'),
(5, 18, 18, 'activar', 'Cambio de estado a Activar', '2025-10-24 19:45:45'),
(6, 18, 18, 'bloquear', 'Cambio de estado a Bloquear', '2025-10-24 19:47:38'),
(7, 18, 18, 'activar', 'Cambio de estado a Activar', '2025-10-24 19:48:02'),
(8, 18, 18, 'bloquear', 'Cambio de estado a Bloquear', '2025-10-24 19:56:08'),
(9, 18, 18, 'activar', 'Cambio de estado a Activar', '2025-10-24 19:56:10'),
(10, 18, 18, 'bloquear', 'Cambio de estado a Bloquear', '2025-10-24 20:13:08'),
(11, 18, 18, 'activar', 'Cambio de estado a Activar', '2025-10-24 20:13:12'),
(12, 18, 18, 'bloquear', 'Bloquear al usuario', '2025-10-24 20:20:01'),
(13, 18, 18, 'activar', 'Activar al usuario', '2025-10-24 20:20:04'),
(14, 18, 19, 'activar', 'Activar al usuario', '2025-10-24 20:26:54'),
(15, 18, 19, 'banear', 'Banear al usuario', '2025-10-24 20:27:04'),
(16, 18, 19, 'activar', 'Activar al usuario', '2025-10-24 20:27:14'),
(17, 18, 20, 'activar', 'Activar al usuario', '2025-10-24 20:28:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_dano`
--

CREATE TABLE `historial_dano` (
  `id_dano` int(11) NOT NULL,
  `id_partida` int(11) DEFAULT NULL,
  `id_atacante` int(11) DEFAULT NULL,
  `id_victima` int(11) DEFAULT NULL,
  `id_arma` int(11) DEFAULT NULL,
  `id_zona` int(11) DEFAULT NULL,
  `dano_aplicado` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mapas`
--

CREATE TABLE `mapas` (
  `id_mapa` int(11) NOT NULL,
  `nombre_mapa` varchar(50) NOT NULL,
  `modo_juego` enum('BR','DE') NOT NULL,
  `imagen_preview` varchar(400) NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mapas`
--

INSERT INTO `mapas` (`id_mapa`, `nombre_mapa`, `modo_juego`, `imagen_preview`, `descripcion`) VALUES
(1, 'Isolated', 'BR', 'assets/img/mapa_br/isolated.png', 'es un mapa de Battle Royale para el modo de juego de Call of Duty: Mobile, conocido por ser un mapa original del juego que combina áreas de otros mapas de la saga con localizaciones únicas y exclusivas'),
(2, 'Blackout', 'BR', 'assets/img/mapa_br/blackout.png', 'modo de juego de Battle Royale incluido en Call of Duty: Black Ops 4 y en Call of Duty: Mobile, que combina el combate característico de Black Ops con el mapa más grande de la historia de Call of Duty hasta la fecha'),
(3, 'Alcatraz', 'BR', 'assets/img/mapa_br/alcatraz.png', 'el mapa para el modo Blackout en Call of Duty: Black Ops 4 y el modo de juego de reaparición rápida en Call of Duty: Mobile'),
(4, 'Nuketown', 'DE', 'assets/img/mapa_de/nuketown.png', 'es un mapa de multijugador icónico y pequeño de la serie Call of Duty, ambientado en una ciudad de pruebas nucleares de los años 50 en el desierto de Nevada'),
(5, 'Crossfire', 'DE', 'assets/img/mapa_de/crossfire.png', 'el mapa multijugador de Call of Duty y el juego de disparos en primera persona (FPS) Crossfire.'),
(6, 'Firing Range', 'DE', 'assets/img/mapa_de/firing_range.png', 'es un mapa clásico de Call of Duty ambientado en un campo de entrenamiento militar, reconocido por su diseño equilibrado con tres carriles principales y numerosos puntos de cobertura.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `niveles`
--

CREATE TABLE `niveles` (
  `id_nivel` int(11) NOT NULL,
  `nombre_nivel` varchar(100) NOT NULL,
  `puntos_requeridos` int(11) NOT NULL DEFAULT 0,
  `img_nivel` varchar(400) NOT NULL,
  `id_rango` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `niveles`
--

INSERT INTO `niveles` (`id_nivel`, `nombre_nivel`, `puntos_requeridos`, `img_nivel`, `id_rango`) VALUES
(1, 'Principiante Oro-1', 0, 'assets/img/niveles/oro1.png', NULL),
(2, 'Principiante Platino-2', 250, 'assets/img/niveles/platino2.png', NULL),
(3, 'Intermedio Diamante-1', 500, 'assets/img/niveles/diamante1.png', NULL),
(4, 'Intermedio Heroico-2', 750, 'assets/img/niveles/heroico2.png', NULL),
(5, 'Avanzado Maestro-3', 1000, 'assets/img/niveles/maestro3.png', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `id_mapa` int(11) NOT NULL,
  `modo_juego` enum('BR','DE','TDM','DUO','SOLO') NOT NULL,
  `estado` enum('esperando','en_curso','finalizada') DEFAULT 'esperando',
  `jugadores_max` int(11) NOT NULL,
  `nivel_minimo` int(11) NOT NULL,
  `nivel_maximo` int(11) NOT NULL,
  `rango_minimo` int(11) NOT NULL,
  `rango_maximo` int(11) NOT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partida_jugadores`
--

CREATE TABLE `partida_jugadores` (
  `id_partida_jugadores` int(11) NOT NULL,
  `id_partida` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_personaje` int(11) NOT NULL,
  `id_arma` int(11) NOT NULL,
  `salud_actual` int(11) DEFAULT 100,
  `estado` enum('vivo','eliminado','desconectado') NOT NULL,
  `kills` int(11) DEFAULT 0,
  `asistencias` int(11) DEFAULT 0,
  `puntos` int(11) DEFAULT 0,
  `equipo` enum('A','B','Ninguno') DEFAULT 'Ninguno'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_logs`
--

CREATE TABLE `password_reset_logs` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT 'Desconocida',
  `success` tinyint(1) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_reset_logs`
--

INSERT INTO `password_reset_logs` (`id`, `email`, `id_usuario`, `ip`, `user_agent`, `ubicacion`, `success`, `fecha`) VALUES
(6, 'juanestebank7@gmail.com', 19, '::1', NULL, 'Desconocida', 1, '2025-10-25 12:10:20'),
(7, 'juanestebank7@gmail.com', 19, '::1', NULL, 'Desconocida', 1, '2025-10-25 12:12:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `ip_request` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `id_usuario`, `token_hash`, `created_at`, `expires_at`, `used`, `ip_request`, `user_agent`) VALUES
(5, 19, '7360aae348f8afcc1264231317cc0ecdfa4c3d1791b399112bfceb0dd35d5283', '2025-10-25 19:10:20', '2025-10-25 20:10:20', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personajes`
--

CREATE TABLE `personajes` (
  `id_personaje` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(400) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `personajes`
--

INSERT INTO `personajes` (`id_personaje`, `nombre`, `descripcion`, `imagen`) VALUES
(1, 'Ghost', 'Soldado británico de élite, famoso por su máscara de calavera y sigilo', 'assets/img/pj/ghost.webp'),
(2, 'Hidora Kai', 'Villano japonés, líder criminal ambicioso y despiadado', 'assets/img/pj/hidora_kai.webp'),
(3, 'Outrider', 'Francotiradora cubana experta en rastreo y combate con arco', 'assets/img/pj/outrider.webp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos`
--

CREATE TABLE `puntos` (
  `id_punto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo_evento` enum('kill','victoria','headshot') NOT NULL DEFAULT 'kill',
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `fecha_puntos_obtenidos` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `puntos_partida`
--

CREATE TABLE `puntos_partida` (
  `id_puntos_partida` int(11) NOT NULL,
  `id_partida` int(11) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `puntos_dano` int(11) DEFAULT 0,
  `puntos_kills` int(11) DEFAULT 0,
  `puntos_victoria` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rangos`
--

CREATE TABLE `rangos` (
  `id_rango` int(11) NOT NULL,
  `nombre_rango` varchar(50) NOT NULL,
  `nivel_minimo` int(11) NOT NULL,
  `nivel_maximo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rangos`
--

INSERT INTO `rangos` (`id_rango`, `nombre_rango`, `nivel_minimo`, `nivel_maximo`) VALUES
(1, 'Principiante', 1, 2),
(2, 'Intermedio', 3, 4),
(3, 'Avanzado', 5, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporte_partidas`
--

CREATE TABLE `reporte_partidas` (
  `id_reporte` int(11) NOT NULL,
  `id_partida` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_mapa` int(11) NOT NULL,
  `modo_juego` enum('BR','DE','DUO','SOLO','TEAM','OTRO') DEFAULT 'BR',
  `resultado` enum('victoria','derrota','empate','abandono') DEFAULT 'derrota',
  `kills` int(11) DEFAULT 0,
  `muertes` int(11) DEFAULT 0,
  `asistencias` int(11) DEFAULT 0,
  `dano_causado` int(11) DEFAULT 0,
  `dano_recibido` int(11) DEFAULT 0,
  `tiros_cabeza` int(11) DEFAULT 0,
  `puntos_obtenidos` int(11) DEFAULT 0,
  `rango_al_jugar` int(11) DEFAULT NULL,
  `nivel_al_jugar` int(11) DEFAULT NULL,
  `arma_mas_usada` varchar(100) DEFAULT NULL,
  `duracion_segundos` int(11) DEFAULT 0,
  `fecha_partida` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) DEFAULT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'admin', 'El Administrador (Admin) es el usuario con máximos privilegios dentro del sistema del videojuego. Su función principal es gestionar, supervisar y mantener el correcto funcionamiento del entorno del juego, tanto a nivel técnico como comunitario. Este rol garantiza la estabilidad, seguridad y equidad del sistema, además de coordinar la gestión de usuarios, contenido y datos internos del proyecto.'),
(2, 'player', 'El Usuario es el núcleo del videojuego: la persona que interactúa directamente con la experiencia creada por el equipo de desarrollo. Su función principal es participar activamente en el entorno del juego, explorando, compitiendo o colaborando según las mecánicas establecidas. Cada usuario contribuye al crecimiento de la comunidad y al dinamismo del proyecto.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_arma`
--

CREATE TABLE `tipos_arma` (
  `id_tipo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_arma`
--

INSERT INTO `tipos_arma` (`id_tipo`, `nombre`) VALUES
(1, 'Puño'),
(2, 'Pistola'),
(3, 'Francotirador'),
(4, 'Ametralladora');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `id_avatar` int(11) DEFAULT NULL,
  `puntos_totales` int(11) NOT NULL DEFAULT 0,
  `id_nivel` int(11) NOT NULL DEFAULT 1,
  `id_rango` int(11) NOT NULL DEFAULT 1,
  `id_rol` int(11) DEFAULT 2,
  `ultimo_inicio_sesion` datetime DEFAULT NULL,
  `id_personaje` int(11) DEFAULT NULL,
  `id_arma` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre_usuario`, `email`, `contrasena`, `id_avatar`, `puntos_totales`, `id_nivel`, `id_rango`, `id_rol`, `ultimo_inicio_sesion`, `id_personaje`, `id_arma`, `id_estado`) VALUES
(18, 'Alejandro', 'jose@gmail.com', '$2y$10$C8hmKlWXvjRUE31uk0HfWOiPMBcI2aiD2bmreX6enrXe23B1pGRx6', NULL, 0, 1, 1, 1, '2025-10-25 13:50:04', NULL, NULL, 1),
(19, 'Mono', 'juanestebank7@gmail.com', '$2y$10$A985O8M/nONcgY1qfOxY4ebda4Oh328Lmt0HgCgAT.15YL4K6shjS', 2, 0, 1, 1, 2, '2025-10-25 13:50:34', 1, NULL, 1),
(20, 'Miguel', 'Miguel@gmail.com', '$2y$10$7OwlFyWfSxnoA3HRGUYNhe.kOX9TB.0U9Tpm89B6NQEOQvGr/4WW.', NULL, 0, 1, 1, 2, '2025-10-24 15:59:19', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas_dano`
--

CREATE TABLE `zonas_dano` (
  `id_zona` int(11) NOT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `multiplicador` decimal(3,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `zonas_dano`
--

INSERT INTO `zonas_dano` (`id_zona`, `nombre`, `multiplicador`) VALUES
(1, 'Cabeza', 1.50),
(2, 'Torso', 1.00),
(3, 'Piernas', 0.80);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `armas`
--
ALTER TABLE `armas`
  ADD PRIMARY KEY (`id_arma`),
  ADD KEY `armas_ibfk_1` (`id_tipo`);

--
-- Indices de la tabla `avatar`
--
ALTER TABLE `avatar`
  ADD PRIMARY KEY (`id_avatar`);

--
-- Indices de la tabla `desbloqueos`
--
ALTER TABLE `desbloqueos`
  ADD PRIMARY KEY (`id_desbloqueo`),
  ADD KEY `desbloqueos_ibfk_1` (`id_objeto`),
  ADD KEY `nivel_requerido` (`nivel_requerido`),
  ADD KEY `rango_requerido` (`rango_requerido`);

--
-- Indices de la tabla `estados_usuario`
--
ALTER TABLE `estados_usuario`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indices de la tabla `historial_acciones`
--
ALTER TABLE `historial_acciones`
  ADD PRIMARY KEY (`id_accion`),
  ADD KEY `historial_acciones_ibfk_1` (`id_admin`),
  ADD KEY `historial_acciones_ibfk_2` (`id_usuario`);

--
-- Indices de la tabla `historial_dano`
--
ALTER TABLE `historial_dano`
  ADD PRIMARY KEY (`id_dano`),
  ADD KEY `id_partida` (`id_partida`),
  ADD KEY `id_arma` (`id_arma`),
  ADD KEY `id_zona` (`id_zona`),
  ADD KEY `id_atacante` (`id_atacante`),
  ADD KEY `id_victima` (`id_victima`);

--
-- Indices de la tabla `mapas`
--
ALTER TABLE `mapas`
  ADD PRIMARY KEY (`id_mapa`);

--
-- Indices de la tabla `niveles`
--
ALTER TABLE `niveles`
  ADD PRIMARY KEY (`id_nivel`),
  ADD KEY `fk_niveles_rangos` (`id_rango`);

--
-- Indices de la tabla `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id_partida`),
  ADD KEY `id_mapa` (`id_mapa`);

--
-- Indices de la tabla `partida_jugadores`
--
ALTER TABLE `partida_jugadores`
  ADD PRIMARY KEY (`id_partida_jugadores`),
  ADD KEY `id_partida` (`id_partida`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_personaje` (`id_personaje`),
  ADD KEY `id_arma` (`id_arma`);

--
-- Indices de la tabla `password_reset_logs`
--
ALTER TABLE `password_reset_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `personajes`
--
ALTER TABLE `personajes`
  ADD PRIMARY KEY (`id_personaje`);

--
-- Indices de la tabla `puntos`
--
ALTER TABLE `puntos`
  ADD PRIMARY KEY (`id_punto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `puntos_partida`
--
ALTER TABLE `puntos_partida`
  ADD PRIMARY KEY (`id_puntos_partida`),
  ADD KEY `id_partida` (`id_partida`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `rangos`
--
ALTER TABLE `rangos`
  ADD PRIMARY KEY (`id_rango`);

--
-- Indices de la tabla `reporte_partidas`
--
ALTER TABLE `reporte_partidas`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_partida` (`id_partida`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_mapa` (`id_mapa`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tipos_arma`
--
ALTER TABLE `tipos_arma`
  ADD PRIMARY KEY (`id_tipo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_nivel` (`id_nivel`),
  ADD KEY `id_rango` (`id_rango`),
  ADD KEY `id_personaje` (`id_personaje`),
  ADD KEY `id_armas` (`id_arma`),
  ADD KEY `fk_usuarios_avatar` (`id_avatar`),
  ADD KEY `idx_nombre_usuario` (`nombre_usuario`),
  ADD KEY `idx_id_rol` (`id_rol`);

--
-- Indices de la tabla `zonas_dano`
--
ALTER TABLE `zonas_dano`
  ADD PRIMARY KEY (`id_zona`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `armas`
--
ALTER TABLE `armas`
  MODIFY `id_arma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `avatar`
--
ALTER TABLE `avatar`
  MODIFY `id_avatar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `desbloqueos`
--
ALTER TABLE `desbloqueos`
  MODIFY `id_desbloqueo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estados_usuario`
--
ALTER TABLE `estados_usuario`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historial_acciones`
--
ALTER TABLE `historial_acciones`
  MODIFY `id_accion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `historial_dano`
--
ALTER TABLE `historial_dano`
  MODIFY `id_dano` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `niveles`
--
ALTER TABLE `niveles`
  MODIFY `id_nivel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `partida_jugadores`
--
ALTER TABLE `partida_jugadores`
  MODIFY `id_partida_jugadores` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `password_reset_logs`
--
ALTER TABLE `password_reset_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `personajes`
--
ALTER TABLE `personajes`
  MODIFY `id_personaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `puntos`
--
ALTER TABLE `puntos`
  MODIFY `id_punto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `puntos_partida`
--
ALTER TABLE `puntos_partida`
  MODIFY `id_puntos_partida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rangos`
--
ALTER TABLE `rangos`
  MODIFY `id_rango` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reporte_partidas`
--
ALTER TABLE `reporte_partidas`
  MODIFY `id_reporte` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tipos_arma`
--
ALTER TABLE `tipos_arma`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `zonas_dano`
--
ALTER TABLE `zonas_dano`
  MODIFY `id_zona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `armas`
--
ALTER TABLE `armas`
  ADD CONSTRAINT `armas_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_arma` (`id_tipo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `desbloqueos`
--
ALTER TABLE `desbloqueos`
  ADD CONSTRAINT `desbloqueos_ibfk_1` FOREIGN KEY (`id_objeto`) REFERENCES `armas` (`id_arma`) ON DELETE CASCADE,
  ADD CONSTRAINT `desbloqueos_ibfk_2` FOREIGN KEY (`nivel_requerido`) REFERENCES `niveles` (`id_nivel`),
  ADD CONSTRAINT `desbloqueos_ibfk_3` FOREIGN KEY (`rango_requerido`) REFERENCES `rangos` (`id_rango`);

--
-- Filtros para la tabla `historial_acciones`
--
ALTER TABLE `historial_acciones`
  ADD CONSTRAINT `historial_acciones_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `historial_acciones_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `historial_dano`
--
ALTER TABLE `historial_dano`
  ADD CONSTRAINT `historial_dano_ibfk_1` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`),
  ADD CONSTRAINT `historial_dano_ibfk_2` FOREIGN KEY (`id_arma`) REFERENCES `armas` (`id_arma`),
  ADD CONSTRAINT `historial_dano_ibfk_3` FOREIGN KEY (`id_zona`) REFERENCES `zonas_dano` (`id_zona`),
  ADD CONSTRAINT `historial_dano_ibfk_4` FOREIGN KEY (`id_atacante`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `historial_dano_ibfk_5` FOREIGN KEY (`id_victima`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `niveles`
--
ALTER TABLE `niveles`
  ADD CONSTRAINT `fk_niveles_rangos` FOREIGN KEY (`id_rango`) REFERENCES `rangos` (`id_rango`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `partidas`
--
ALTER TABLE `partidas`
  ADD CONSTRAINT `partidas_ibfk_1` FOREIGN KEY (`id_mapa`) REFERENCES `mapas` (`id_mapa`);

--
-- Filtros para la tabla `partida_jugadores`
--
ALTER TABLE `partida_jugadores`
  ADD CONSTRAINT `partida_jugadores_ibfk_1` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`),
  ADD CONSTRAINT `partida_jugadores_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `partida_jugadores_ibfk_3` FOREIGN KEY (`id_personaje`) REFERENCES `personajes` (`id_personaje`),
  ADD CONSTRAINT `partida_jugadores_ibfk_4` FOREIGN KEY (`id_arma`) REFERENCES `armas` (`id_arma`);

--
-- Filtros para la tabla `password_reset_logs`
--
ALTER TABLE `password_reset_logs`
  ADD CONSTRAINT `password_reset_logs_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `puntos`
--
ALTER TABLE `puntos`
  ADD CONSTRAINT `puntos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `puntos_partida`
--
ALTER TABLE `puntos_partida`
  ADD CONSTRAINT `puntos_partida_ibfk_1` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`),
  ADD CONSTRAINT `puntos_partida_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `reporte_partidas`
--
ALTER TABLE `reporte_partidas`
  ADD CONSTRAINT `reporte_partidas_ibfk_1` FOREIGN KEY (`id_partida`) REFERENCES `partidas` (`id_partida`),
  ADD CONSTRAINT `reporte_partidas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `reporte_partidas_ibfk_3` FOREIGN KEY (`id_mapa`) REFERENCES `mapas` (`id_mapa`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_avatar` FOREIGN KEY (`id_avatar`) REFERENCES `avatar` (`id_avatar`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_estado`) REFERENCES `estados_usuario` (`id_estado`),
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`id_nivel`) REFERENCES `niveles` (`id_nivel`),
  ADD CONSTRAINT `usuarios_ibfk_4` FOREIGN KEY (`id_rango`) REFERENCES `rangos` (`id_rango`),
  ADD CONSTRAINT `usuarios_ibfk_5` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  ADD CONSTRAINT `usuarios_ibfk_6` FOREIGN KEY (`id_personaje`) REFERENCES `personajes` (`id_personaje`),
  ADD CONSTRAINT `usuarios_ibfk_7` FOREIGN KEY (`id_arma`) REFERENCES `armas` (`id_arma`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
