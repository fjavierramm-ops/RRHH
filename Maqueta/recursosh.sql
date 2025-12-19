-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-11-2025 a las 00:38:37
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
-- Base de datos: `recursosh`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aplicaciones`
--

CREATE TABLE `aplicaciones` (
  `id_aplicacion` int(11) NOT NULL,
  `id_candidato` int(11) NOT NULL,
  `id_vacante` int(11) NOT NULL,
  `fecha_aplicacion` date NOT NULL,
  `status_aplicacion` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aplicaciones`
--

INSERT INTO `aplicaciones` (`id_aplicacion`, `id_candidato`, `id_vacante`, `fecha_aplicacion`, `status_aplicacion`) VALUES
(1, 1, 1, '2025-10-24', 'en proceso'),
(2, 1, 2, '2025-10-29', 'En proceso'),
(3, 1, 3, '2025-11-03', 'en proceso'),
(4, 1, 4, '2025-11-08', 'en proceso'),
(5, 1, 1, '2025-11-13', 'en proceso'),
(6, 4, 1, '2025-11-17', 'en proceso'),
(7, 5, 3, '2025-11-17', 'En revisión'),
(8, 6, 1, '2025-11-18', 'contratado'),
(9, 6, 3, '2025-11-18', 'Rechazado'),
(10, 7, 1, '2025-11-18', 'En revisión');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `candidatos`
--

CREATE TABLE `candidatos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `habilidades_tecnicas` text DEFAULT NULL,
  `habilidades_blandas` text DEFAULT NULL,
  `cv_path` varchar(255) NOT NULL,
  `portfolio_file_path` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `candidatos`
--

INSERT INTO `candidatos` (`id`, `nombre`, `email`, `password`, `telefono`, `habilidades_tecnicas`, `habilidades_blandas`, `cv_path`, `portfolio_file_path`, `portfolio_url`, `fecha_registro`) VALUES
(1, 'Francisco', 'holamundo@gmail.com', '$2y$10$x94J0pP0vTYQE.3JBsLDGupVnSIAWlf6oZn4bziAOI0M97xTyO/lC', '+52552123587945', 'Javascript', 'trabajo en equipo', 'uploads/cv_6913d369123bb.pdf', '', '', '2025-11-12 00:23:05'),
(2, 'Jired', 'Ticst@asdfsd.com', '$2y$10$6tuqyGV4CGWe4346.lBcrext.UyfsqgrEtvMjHxnFPy3ZGrfpsJSy', '+52557845123698', 'Javascript', 'comunicación', 'uploads/cv_6914cde58220a.pdf', '', '', '2025-11-12 18:11:49'),
(3, 'Angie', 'Angie@gmail.com', '$2y$10$pbQP7qdXCX83RkYSL2wfLu1Mrp.lFv4cmvYe5vfoYCiFVYXKwK.NG', '+5255123456789', 'Javascript', 'trabajo en equipo', 'uploads/cv_6915fb9e1e3a7.pdf', '', '', '2025-11-13 15:39:10'),
(4, 'Juan ', 'juan@gmail.com', '$2y$10$KPnxAGqPZ/LmCDRJaRymZ.ynnGjUyvi5dARsPcnSwc1x0A6gGCuia', '+521478523698', 'react', 'liderazgo', 'uploads/cv_691b7636c124a.pdf', '', '', '2025-11-17 19:23:34'),
(5, 'Arturo', 'arturo@gmail.com', '$2y$10$mswHozG/YKl214tgEHVTN.ROCkxqW86YNc4Y5.ismhKCHvgHOI4c6', '+527894561232', 'Python', 'resolución', 'uploads/cv_691ba25aab550.pdf', '', '', '2025-11-17 22:31:54'),
(6, 'Jorge', 'Jorge@gmail.com', '$2y$10$BZX4UdGGJH.ChhUNYF9lKeRiVUfbAM4l9ilmhP7iXZ/kmKbJc4cK2', '+527895236412', 'Node.js', 'resolución', 'uploads/cv_691c7ab46497c.pdf', '', '', '2025-11-18 13:55:00'),
(7, 'Montserrat', 'monky@gmail.com', '$2y$10$34gOl.EeOnISFksMCaQjPek6R.JA2stnQBL4Ed1dvh/umVjkV3MGO', '+527412369850', 'SQL', 'SI', 'uploads/cv_691ca5c5a01d6.pdf', '', '', '2025-11-18 16:58:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_validacion`
--

CREATE TABLE `comentarios_validacion` (
  `id_comentario` int(11) NOT NULL,
  `id_aplicacion` int(11) NOT NULL,
  `autor` varchar(100) NOT NULL DEFAULT 'RRHH',
  `mensaje` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios_validacion`
--

INSERT INTO `comentarios_validacion` (`id_comentario`, `id_aplicacion`, `autor`, `mensaje`, `fecha`) VALUES
(1, 2, 'Gerente IT', 'El perfil técnico se ve bien, pero ¿tiene experiencia en AWS?', '2025-11-24 17:11:11'),
(2, 2, 'RRHH', 'Sí, tiene certificación Cloud Practitioner, adjunto en su CV.', '2025-11-24 17:11:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrevistas`
--

CREATE TABLE `entrevistas` (
  `id_entrevista` int(11) NOT NULL,
  `id_aplicacion` int(11) NOT NULL,
  `fecha_propuesta_1` date NOT NULL,
  `hora_propuesta_1` time NOT NULL,
  `fecha_propuesta_2` date DEFAULT NULL,
  `hora_propuesta_2` time DEFAULT NULL,
  `fecha_propuesta_3` date DEFAULT NULL,
  `hora_propuesta_3` time DEFAULT NULL,
  `fecha_final` date DEFAULT NULL,
  `hora_final` time DEFAULT NULL,
  `status_confirmacion` varchar(50) NOT NULL DEFAULT 'Pendiente de confirmación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entrevistas`
--

INSERT INTO `entrevistas` (`id_entrevista`, `id_aplicacion`, `fecha_propuesta_1`, `hora_propuesta_1`, `fecha_propuesta_2`, `hora_propuesta_2`, `fecha_propuesta_3`, `hora_propuesta_3`, `fecha_final`, `hora_final`, `status_confirmacion`) VALUES
(1, 1, '2025-04-08', '11:00:00', '2025-04-09', '15:00:00', '2025-11-25', '18:06:00', '2025-11-04', '11:00:00', 'Pendiente de cambio'),
(2, 2, '2025-11-20', '10:00:00', '2025-11-21', '14:00:00', '2025-11-22', '16:00:00', '2025-11-17', '13:00:00', 'Confirmada'),
(3, 3, '2025-12-05', '09:00:00', '2025-12-06', '12:00:00', '2025-12-07', '17:00:00', NULL, NULL, 'Pendiente de confirmación'),
(4, 6, '2026-01-01', '12:41:03', '2026-01-08', '13:41:03', '2026-01-07', '12:41:03', '2026-01-16', '17:41:03', 'Pendiente de confirmación'),
(5, 7, '2026-01-01', '12:41:03', '2026-01-08', '13:41:03', '2026-01-07', '12:41:03', '2026-01-16', '17:41:03', 'Pendiente de confirmación'),
(6, 10, '2026-01-01', '12:41:03', '2026-01-08', '13:41:03', '2026-01-07', '12:41:03', '2026-01-16', '17:41:03', 'Pendiente de confirmación');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones`
--

CREATE TABLE `evaluaciones` (
  `id_evaluacion` int(11) NOT NULL,
  `id_aplicacion` int(11) NOT NULL,
  `score_tecnico` int(3) DEFAULT 0,
  `score_blando` int(3) DEFAULT 0,
  `score_global` int(3) GENERATED ALWAYS AS ((`score_tecnico` + `score_blando`) / 2) VIRTUAL,
  `comentarios_tecnicos` text DEFAULT NULL,
  `fecha_evaluacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluaciones`
--

INSERT INTO `evaluaciones` (`id_evaluacion`, `id_aplicacion`, `score_tecnico`, `score_blando`, `comentarios_tecnicos`, `fecha_evaluacion`) VALUES
(1, 1, 95, 80, 'Excelente nivel de JS, le falta un poco de trabajo en equipo.', '2025-11-24 17:11:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `onboarding`
--

CREATE TABLE `onboarding` (
  `id_onboarding` int(11) NOT NULL,
  `id_aplicacion` int(11) NOT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `doc_contratacion` enum('Pendiente','En proceso','Completado') DEFAULT 'Pendiente',
  `config_equipos` enum('Pendiente','En proceso','Completado') DEFAULT 'Pendiente',
  `induccion` enum('Pendiente','En proceso','Completado') DEFAULT 'Pendiente',
  `entrenamiento` enum('Pendiente','En proceso','Completado') DEFAULT 'Pendiente',
  `notificaciones_enviadas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `onboarding`
--

INSERT INTO `onboarding` (`id_onboarding`, `id_aplicacion`, `fecha_ingreso`, `doc_contratacion`, `config_equipos`, `induccion`, `entrenamiento`, `notificaciones_enviadas`) VALUES
(1, 8, '2025-11-25', 'Completado', 'En proceso', 'Pendiente', 'Pendiente', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacantes`
--

CREATE TABLE `vacantes` (
  `id_vacante` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `empresa` varchar(255) NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `requisitos` text NOT NULL,
  `salario` varchar(100) DEFAULT NULL,
  `tipo_trabajo` varchar(50) NOT NULL,
  `fecha_publicacion` date NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'Abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vacantes`
--

INSERT INTO `vacantes` (`id_vacante`, `titulo`, `empresa`, `ubicacion`, `descripcion`, `requisitos`, `salario`, `tipo_trabajo`, `fecha_publicacion`, `estado`) VALUES
(1, 'Programador ', 'Empresa ABC', 'Guadalajara', 'Buscamos un Analista de Datos junior con pasión por la extracción de información significativa de grandes volúmenes de datos. Serás clave en la toma de decisiones estratégicas.', 'Experiencia mínima de 1 año en análisis de datos.Dominio de SQL y Excel avanzado.Conocimiento de Power BI o Tableau.\r\nHabilidad para comunicar resultados a equipos no técnicos.', '$18,000 - $25,000 MXN', 'Tiempo Completo', '2025-11-11', 'Abierta'),
(2, 'Desarrollo de software', 'Empresa DEF', 'Hidalgo', 'Desarrollador Full Stack con enfoque en tecnologías JavaScript (Node.js y React). Participarás en todo el ciclo de vida del desarrollo de nuestras aplicaciones críticas.', '3+ años de experiencia en desarrollo web.\r\nExperiencia sólida con Node.js, Express y bases de datos NoSQL (MongoDB).Conocimiento de AWS o Google Cloud Platform.\r\nCapacidad para trabajar de forma remota.', '$30,000 - $45,000 MXN', 'Remoto', '2025-11-06', 'Abierta'),
(3, 'Analista de negocio', 'Empresa GHI', 'Monterrey', 'Posición ideal para un profesional con experiencia en la identificación de necesidades de negocio y la traducción de estas en requerimientos técnicos funcionales.', 'Certificación CBAP (deseable).\r\nExperiencia con metodologías Agile/Scrum.\r\nExcelentes habilidades de comunicación y negociación.\r\nInglés avanzado indispensable.', '$22,000 - $32,000 MXN', 'Híbrido', '2025-11-09', 'Abierta'),
(4, 'Diseñador UX/UI Senior', 'Tecnologías JKL', 'Ciudad de México', 'Liderarás el diseño de la experiencia de usuario de nuestra plataforma principal, asegurando interfaces intuitivas y estéticamente agradables.', '5+ años de experiencia en diseño UX/UI.\r\nDominio de herramientas de diseño (Figma, Sketch, Adobe XD).\r\nPortfolio comprobable con proyectos complejos.\r\nExperiencia realizando pruebas de usabilidad.', '$40,000 - $60,000 MXN', 'Remoto', '2025-11-11', 'Abierta');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `aplicaciones`
--
ALTER TABLE `aplicaciones`
  ADD PRIMARY KEY (`id_aplicacion`),
  ADD KEY `id_candidato` (`id_candidato`),
  ADD KEY `id_vacante` (`id_vacante`);

--
-- Indices de la tabla `candidatos`
--
ALTER TABLE `candidatos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `comentarios_validacion`
--
ALTER TABLE `comentarios_validacion`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  ADD PRIMARY KEY (`id_entrevista`),
  ADD UNIQUE KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD UNIQUE KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `onboarding`
--
ALTER TABLE `onboarding`
  ADD PRIMARY KEY (`id_onboarding`),
  ADD UNIQUE KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `vacantes`
--
ALTER TABLE `vacantes`
  ADD PRIMARY KEY (`id_vacante`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `aplicaciones`
--
ALTER TABLE `aplicaciones`
  MODIFY `id_aplicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `candidatos`
--
ALTER TABLE `candidatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `comentarios_validacion`
--
ALTER TABLE `comentarios_validacion`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  MODIFY `id_entrevista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `onboarding`
--
ALTER TABLE `onboarding`
  MODIFY `id_onboarding` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `vacantes`
--
ALTER TABLE `vacantes`
  MODIFY `id_vacante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `aplicaciones`
--
ALTER TABLE `aplicaciones`
  ADD CONSTRAINT `aplicaciones_ibfk_1` FOREIGN KEY (`id_candidato`) REFERENCES `candidatos` (`id`),
  ADD CONSTRAINT `aplicaciones_ibfk_2` FOREIGN KEY (`id_vacante`) REFERENCES `vacantes` (`id_vacante`);

--
-- Filtros para la tabla `comentarios_validacion`
--
ALTER TABLE `comentarios_validacion`
  ADD CONSTRAINT `comentarios_validacion_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `entrevistas_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD CONSTRAINT `evaluaciones_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `onboarding`
--
ALTER TABLE `onboarding`
  ADD CONSTRAINT `onboarding_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
