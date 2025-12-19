-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-12-2025 a las 17:38:04
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
(1, 1, 1, '2025-10-24', 'Rechazado'),
(2, 1, 2, '2025-10-29', 'En proceso'),
(3, 1, 3, '2025-11-03', 'en proceso'),
(4, 1, 4, '2025-11-08', 'en proceso'),
(5, 1, 1, '2025-11-13', 'en proceso'),
(6, 4, 1, '2025-11-17', 'en proceso'),
(7, 5, 3, '2025-11-17', 'En revisión'),
(8, 6, 1, '2025-11-18', 'contratado'),
(9, 6, 3, '2025-11-18', 'Rechazado'),
(10, 7, 1, '2025-11-18', 'En revisión'),
(11, 8, 1, '2025-12-03', 'Entrevista'),
(12, 9, 4, '2025-12-03', 'Rechazado'),
(13, 8, 4, '2025-12-17', 'En proceso'),
(14, 9, 1, '2025-12-17', 'Rechazado'),
(15, 10, 1, '2025-12-17', 'Rechazado'),
(16, 10, 3, '2025-12-17', 'Rechazado'),
(17, 10, 4, '2025-12-17', 'Rechazado'),
(18, 10, 5, '2025-12-17', 'Rechazado'),
(19, 8, 5, '2025-12-17', 'Entrevista'),
(20, 1, 5, '2025-12-17', 'Contratado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canal_comunicacion`
--

CREATE TABLE `canal_comunicacion` (
  `idComunicacion` int(11) NOT NULL,
  `idClientes` int(11) NOT NULL COMMENT 'FK a candidatos.id',
  `tipo_origen` varchar(50) NOT NULL,
  `id_origen` int(11) NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `canal` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `mensaje` text NOT NULL,
  `estado` varchar(20) NOT NULL,
  `automatica` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `canal_comunicacion`
--

INSERT INTO `canal_comunicacion` (`idComunicacion`, `idClientes`, `tipo_origen`, `id_origen`, `tipo_destino`, `id_destino`, `canal`, `fecha`, `hora`, `mensaje`, `estado`, `automatica`) VALUES
(1, 8, 'reclutador', 1, 'cliente', 8, 'Correo', '2025-12-26', '18:19:00', 'Se programó entrevista para el 2025-12-26 de 18:19 a 18:23.', 'Enviado', 1),
(2, 8, 'reclutador', 1, 'cliente', 8, 'Correo', '2025-12-25', '09:43:00', 'Se programó entrevista para el 2025-12-25 de 09:43 a 09:48.', 'Enviado', 1),
(3, 8, 'Sistema', 1, 'Candidato', 8, 'Email', '2025-12-17', '19:44:08', 'Hola Alexis,\n\nGracias por participar en la entrevista para la vacante: Diseñador UX/UI Senior\n\nTu entrevista fue programada para el 30/11/-0001 a las 09:43.\n\nNos pondremos en contacto contigo pronto con los resultados.\n\nSaludos,\nEquipo de RRHH', 'Enviado', 1),
(4, 8, 'Sistema', 1, 'Candidato', 8, 'Calendar', '2025-12-17', '20:12:14', 'Entrevista confirmada y evento de calendario enviado al candidato para la vacante 4 en la fecha 2025-12-19 de 09:00:00 a 10:00:00. Fuente: Sistema_Forzado', 'Enviado', 1),
(5, 1, 'Sistema', 1, 'Candidato', 1, 'Email', '2025-12-17', '20:12:50', 'Hola Francisco,\n\nGracias por tu interés en la posición de Programador .\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nTu evaluación general fue de 88%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', 'Enviado', 1),
(6, 6, 'Sistema', 1, 'Candidato', 6, 'Email', '2025-12-17', '20:12:50', 'Hola Jorge,\n\nGracias por tu interés en la posición de Analista de negocio.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', 'Enviado', 1),
(7, 1, 'reclutador', 1, 'cliente', 1, 'Correo', '2025-12-18', '00:07:00', 'Se programó entrevista para el 2025-12-18 de 00:07 a 13:11.', 'Enviado', 1),
(8, 1, 'Sistema', 1, 'Candidato', 1, 'Email', '2025-12-17', '21:09:27', 'Hola Francisco,\n\nGracias por participar en la entrevista para la vacante: Programador master\n\nTu entrevista fue programada para el 18/12/2025 a las 00:07.\n\nNos pondremos en contacto contigo pronto con los resultados.\n\nSaludos,\nEquipo de RRHH', 'Enviado', 1),
(9, 8, 'Sistema', 1, 'Candidato', 8, 'Calendar', '2025-12-17', '21:10:09', 'Entrevista confirmada y evento de calendario enviado al candidato para la vacante 5 en la fecha 2025-12-19 de 09:00:00 a 10:00:00. Fuente: Sistema_Forzado', 'Enviado', 1);

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
  `canal_preferido` varchar(20) DEFAULT 'Email',
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

INSERT INTO `candidatos` (`id`, `nombre`, `email`, `password`, `telefono`, `canal_preferido`, `habilidades_tecnicas`, `habilidades_blandas`, `cv_path`, `portfolio_file_path`, `portfolio_url`, `fecha_registro`) VALUES
(1, 'Francisco', 'holamundo@gmail.com', '$2y$10$x94J0pP0vTYQE.3JBsLDGupVnSIAWlf6oZn4bziAOI0M97xTyO/lC', '+52552123587945', 'Email', 'Javascript', 'trabajo en equipo', 'uploads/cv_6913d369123bb.pdf', '', '', '2025-11-12 00:23:05'),
(2, 'Jired', 'Ticst@asdfsd.com', '$2y$10$6tuqyGV4CGWe4346.lBcrext.UyfsqgrEtvMjHxnFPy3ZGrfpsJSy', '+52557845123698', 'Email', 'Javascript', 'comunicación', 'uploads/cv_6914cde58220a.pdf', '', '', '2025-11-12 18:11:49'),
(3, 'Angie', 'Angie@gmail.com', '$2y$10$pbQP7qdXCX83RkYSL2wfLu1Mrp.lFv4cmvYe5vfoYCiFVYXKwK.NG', '+5255123456789', 'Email', 'Javascript', 'trabajo en equipo', 'uploads/cv_6915fb9e1e3a7.pdf', '', '', '2025-11-13 15:39:10'),
(4, 'Juan ', 'juan@gmail.com', '$2y$10$KPnxAGqPZ/LmCDRJaRymZ.ynnGjUyvi5dARsPcnSwc1x0A6gGCuia', '+521478523698', 'Email', 'react', 'liderazgo', 'uploads/cv_691b7636c124a.pdf', '', '', '2025-11-17 19:23:34'),
(5, 'Arturo', 'arturo@gmail.com', '$2y$10$mswHozG/YKl214tgEHVTN.ROCkxqW86YNc4Y5.ismhKCHvgHOI4c6', '+527894561232', 'Email', 'Python', 'resolución', 'uploads/cv_691ba25aab550.pdf', '', '', '2025-11-17 22:31:54'),
(6, 'Jorge', 'Jorge@gmail.com', '$2y$10$BZX4UdGGJH.ChhUNYF9lKeRiVUfbAM4l9ilmhP7iXZ/kmKbJc4cK2', '+527895236412', 'Email', 'Node.js', 'resolución', 'uploads/cv_691c7ab46497c.pdf', '', '', '2025-11-18 13:55:00'),
(7, 'Montserrat', 'monky@gmail.com', '$2y$10$34gOl.EeOnISFksMCaQjPek6R.JA2stnQBL4Ed1dvh/umVjkV3MGO', '+527412369850', 'Email', 'SQL', 'SI', 'uploads/cv_691ca5c5a01d6.pdf', '', '', '2025-11-18 16:58:45'),
(8, 'Alexis', 'alexis@gmail.com', '$2y$10$2Fwsz3jO5.c1HNV2YDjp1ekv2FKj5zUwbua2eGegf1ExIZsZ/60G2', '+525547894263', 'Email', 'JavaScript, Node.js', 'Comunicación y trabajo en equipo', 'uploads/cv_693079edec42a.pdf', '', '', '2025-12-03 17:57:02'),
(9, 'Santiago', 'sanati@gmail.com', '$2y$10$SqofeHC1e4ovEAHVVHOMCObTOE4TnsxNwXoyjKPpQB6QTl2nzJHXi', '+525547894263', 'Email', 'no se nada ', 'no me gusta ser social', 'uploads/cv_69307afab7253.pdf', '', '', '2025-12-03 18:01:30'),
(10, 'Marco ', 'AUrelio@gmail.com', '$2y$10$0oeRYu7ldk0LHfjpGZdljuhnZVXSVg1t90jEsvGbXS3qbSkxlLNCi', '+5257856451', 'Email', 'no se', 'no se', 'uploads/cv_69435e67cf058.docx', '', '', '2025-12-18 01:52:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_validacion`
--

CREATE TABLE `comentarios_validacion` (
  `id_comentario` int(11) NOT NULL,
  `id_aplicacion` int(11) NOT NULL,
  `autor` varchar(100) NOT NULL DEFAULT 'RRHH',
  `mensaje` text NOT NULL,
  `tipo_validacion` enum('proceso','entregable','feedback_cliente') DEFAULT 'proceso',
  `estado_validacion` enum('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  `fecha_limite` date DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios_validacion`
--

INSERT INTO `comentarios_validacion` (`id_comentario`, `id_aplicacion`, `autor`, `mensaje`, `tipo_validacion`, `estado_validacion`, `fecha_limite`, `fecha`) VALUES
(1, 2, 'Gerente IT', 'El perfil técnico se ve bien, pero ¿tiene experiencia en AWS?', 'proceso', 'pendiente', NULL, '2025-11-24 17:11:11'),
(2, 2, 'RRHH', 'Sí, tiene certificación Cloud Practitioner, adjunto en su CV.', 'proceso', 'pendiente', NULL, '2025-11-24 17:11:11'),
(3, 12, 'Sistema (Agente)', 'Evaluación incompleta o score muy bajo', 'proceso', 'rechazado', '2025-12-05', '2025-12-03 23:05:31'),
(4, 11, 'Sistema (Agente)', 'Evaluación incompleta o score muy bajo', 'proceso', 'rechazado', '2025-12-05', '2025-12-03 23:05:33'),
(5, 12, 'Sistema (Agente)', 'Evaluación incompleta o score muy bajo', 'proceso', 'rechazado', '2025-12-05', '2025-12-03 23:05:38'),
(6, 2, 'Sistema (Agente)', 'Evaluación incompleta o score muy bajo', 'proceso', 'rechazado', '2025-12-05', '2025-12-03 23:08:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidaddelequipo`
--

CREATE TABLE `disponibilidaddelequipo` (
  `id` int(30) NOT NULL,
  `idClientes` int(11) NOT NULL COMMENT 'FK a candidatos.id',
  `dia_semana` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `fecha_referencia` date DEFAULT NULL,
  `puesto` varchar(100) NOT NULL,
  `estado` varchar(30) DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `disponibilidaddelequipo`
--

INSERT INTO `disponibilidaddelequipo` (`id`, `idClientes`, `dia_semana`, `hora_inicio`, `hora_fin`, `fecha_referencia`, `puesto`, `estado`) VALUES
(1, 8, 'Lunes', '09:00:00', '10:00:00', '2025-12-26', 'si', 'Coincidencia óptima');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `disponibilidades_rrhh`
--

CREATE TABLE `disponibilidades_rrhh` (
  `id` int(11) NOT NULL,
  `idreclutadores` int(15) UNSIGNED NOT NULL,
  `dia_semana` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `fecha_referencia` date NOT NULL,
  `puesto` varchar(100) NOT NULL,
  `estado` varchar(30) DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrevistas`
--

CREATE TABLE `entrevistas` (
  `id_entrevista` int(11) NOT NULL,
  `idClientes` int(11) DEFAULT NULL COMMENT 'FK a candidatos.id',
  `idVacante` int(11) DEFAULT NULL COMMENT 'FK a vacantes.id_vacante',
  `idReclutador` int(15) UNSIGNED DEFAULT NULL COMMENT 'FK a reclutadores.idreclutadores',
  `id_aplicacion` int(11) NOT NULL,
  `fecha_propuesta_1` date NOT NULL,
  `hora_propuesta_1` time NOT NULL,
  `fecha_propuesta_2` date DEFAULT NULL,
  `hora_propuesta_2` time DEFAULT NULL,
  `fecha_propuesta_3` date DEFAULT NULL,
  `hora_propuesta_3` time DEFAULT NULL,
  `fecha_final` date DEFAULT NULL,
  `hora_final` time DEFAULT NULL,
  `fecha` date DEFAULT NULL COMMENT 'Fecha final confirmada',
  `hora_inicio` time DEFAULT NULL COMMENT 'Hora inicio',
  `hora_fin` time DEFAULT NULL COMMENT 'Hora fin',
  `estado` varchar(30) DEFAULT 'Programada',
  `notas` text DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `status_confirmacion` varchar(50) NOT NULL DEFAULT 'Pendiente de confirmación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entrevistas`
--

INSERT INTO `entrevistas` (`id_entrevista`, `idClientes`, `idVacante`, `idReclutador`, `id_aplicacion`, `fecha_propuesta_1`, `hora_propuesta_1`, `fecha_propuesta_2`, `hora_propuesta_2`, `fecha_propuesta_3`, `hora_propuesta_3`, `fecha_final`, `hora_final`, `fecha`, `hora_inicio`, `hora_fin`, `estado`, `notas`, `fecha_creacion`, `status_confirmacion`) VALUES
(1, NULL, NULL, NULL, 1, '2025-12-06', '10:00:00', '2025-12-07', '14:00:00', '2025-12-08', '16:00:00', '2025-11-04', '11:00:00', NULL, NULL, NULL, 'Programada', NULL, '2025-12-17 13:03:43', 'Pendiente de confirmación'),
(2, NULL, NULL, NULL, 2, '2025-11-20', '10:00:00', '2025-11-21', '14:00:00', '2025-11-22', '16:00:00', '2025-11-17', '13:00:00', NULL, NULL, NULL, 'Programada', NULL, '2025-12-17 13:03:43', 'Confirmada'),
(3, NULL, NULL, NULL, 3, '2025-12-05', '09:00:00', '2025-12-06', '12:00:00', '2025-12-07', '17:00:00', NULL, NULL, NULL, NULL, NULL, 'Programada', NULL, '2025-12-17 13:03:43', 'Pendiente de confirmación'),
(4, NULL, NULL, NULL, 6, '2026-01-01', '12:41:03', '2026-01-08', '13:41:03', '2026-01-07', '12:41:03', '2026-01-16', '17:41:03', NULL, NULL, NULL, 'Programada', NULL, '2025-12-17 13:03:43', 'Pendiente de confirmación'),
(10, 8, 1, 1, 11, '2025-12-21', '10:00:00', '2025-12-22', '14:00:00', '2025-12-23', '16:00:00', NULL, NULL, '0000-00-00', '18:19:00', '18:23:00', 'Programada', 'Entrevista programada desde el panel.', '2025-12-17 16:19:23', 'Pendiente de confirmación'),
(16, 8, 4, 1, 13, '0000-00-00', '00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, '0000-00-00', '09:00:00', '10:00:00', 'Programado por IA', 'Entrevista programada desde el panel.', '2025-12-17 19:44:08', 'Pendiente de confirmación'),
(19, 8, 5, 1, 19, '2025-12-21', '10:00:00', '2025-12-22', '14:00:00', '2025-12-23', '16:00:00', NULL, NULL, '0000-00-00', '09:00:00', '10:00:00', 'Programado por IA', NULL, '2025-12-17 21:00:39', 'Pendiente de confirmación'),
(20, 1, 5, 1, 20, '2025-12-21', '10:00:00', '2025-12-22', '14:00:00', '2025-12-23', '16:00:00', NULL, NULL, '2025-12-18', '00:07:00', '13:11:00', 'Programada', 'Entrevista programada desde el panel.', '2025-12-17 21:02:51', 'Pendiente de confirmación');

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
  `clasificacion_fit` enum('Alto Fit','Medio Fit','Bajo Fit') DEFAULT NULL,
  `segmento` char(1) DEFAULT 'C',
  `comentarios_tecnicos` text DEFAULT NULL,
  `fecha_evaluacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluaciones`
--

INSERT INTO `evaluaciones` (`id_evaluacion`, `id_aplicacion`, `score_tecnico`, `score_blando`, `clasificacion_fit`, `segmento`, `comentarios_tecnicos`, `fecha_evaluacion`) VALUES
(1, 1, 95, 80, NULL, 'C', 'Excelente nivel de JS, le falta un poco de trabajo en equipo.', '2025-11-24 17:11:11'),
(2, 17, 0, 50, 'Bajo Fit', 'C', 'Análisis Automático: Se detectaron coincidencias en: . El perfil técnico parece bajo respecto a los requisitos descritos.', '2025-12-18 02:29:53'),
(3, 18, 0, 50, 'Bajo Fit', 'C', 'Análisis Automático: Se detectaron coincidencias en: . El perfil técnico parece bajo respecto a los requisitos descritos.', '2025-12-18 02:51:31'),
(4, 19, 60, 50, 'Medio Fit', 'C', 'Análisis Automático: Se detectaron coincidencias en: javascript. El candidato cuenta con las palabras clave principales.', '2025-12-18 03:00:14'),
(5, 20, 100, 50, 'Alto Fit', 'B', 'Análisis Automático: Se detectaron coincidencias en: javascript. El candidato cuenta con las palabras clave principales.', '2025-12-18 03:02:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `feedback_entrevista`
--

CREATE TABLE `feedback_entrevista` (
  `id_feedback` int(11) NOT NULL,
  `id_entrevista` int(11) NOT NULL,
  `tipo` enum('entrevistador','candidato') NOT NULL,
  `feedback_texto` text DEFAULT NULL,
  `calificacion` int(11) DEFAULT 0,
  `fecha_feedback` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `feedback_entrevista`
--

INSERT INTO `feedback_entrevista` (`id_feedback`, `id_entrevista`, `tipo`, `feedback_texto`, `calificacion`, `fecha_feedback`) VALUES
(1, 10, 'entrevistador', 'buena', 5, '2025-12-17 20:29:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `feedback_rechazo`
--

CREATE TABLE `feedback_rechazo` (
  `id_feedback` int(11) NOT NULL,
  `id_aplicacion` int(11) DEFAULT NULL,
  `mensaje_generado` text DEFAULT NULL,
  `razones_rechazo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`razones_rechazo`)),
  `sugerencias_mejora` text DEFAULT NULL,
  `fecha_envio` timestamp NULL DEFAULT NULL,
  `estado_envio` enum('pendiente','enviado','fallido') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `feedback_rechazo`
--

INSERT INTO `feedback_rechazo` (`id_feedback`, `id_aplicacion`, `mensaje_generado`, `razones_rechazo`, `sugerencias_mejora`, `fecha_envio`, `estado_envio`) VALUES
(1, 12, 'Hola Santiago,\n\nGracias por tu interés en la posición de Diseñador UX/UI Senior.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-03 18:33:19', 'enviado'),
(2, 14, 'Hola Santiago,\n\nGracias por tu interés en la posición de Programador .\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-18 02:06:58', 'enviado'),
(3, 1, 'Hola Francisco,\n\nGracias por tu interés en la posición de Programador .\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nTu evaluación general fue de 88%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"No cumple con los requisitos m\\u00ednimos del puesto\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-18 02:12:50', 'enviado'),
(4, 9, 'Hola Jorge,\n\nGracias por tu interés en la posición de Analista de negocio.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"No cumple con los requisitos m\\u00ednimos del puesto\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-18 02:12:50', 'enviado'),
(5, 16, 'Hola Marco ,\n\nGracias por tu interés en la posición de Analista de negocio.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-18 02:15:06', 'enviado'),
(6, 17, 'Hola Marco ,\n\nGracias por tu interés en la posición de Diseñador UX/UI Senior.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nTu evaluación general fue de 25%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-18 02:30:45', 'enviado'),
(7, 15, 'Hola Marco ,\n\nGracias por tu interés en la posición de Programador .\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', '2025-12-18 02:35:38', 'enviado'),
(8, 18, 'Hola Marco ,\n\nGracias por tu interés en la posición de Programador master.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nTu evaluación general fue de 25%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', NULL, 'pendiente'),
(9, 18, 'Hola Marco ,\n\nGracias por tu interés en la posición de Programador master.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nTu evaluación general fue de 25%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', NULL, 'pendiente'),
(10, 18, 'Hola Marco ,\n\nGracias por tu interés en la posición de Programador master.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nTu evaluación general fue de 25%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', NULL, 'pendiente'),
(11, 18, 'Hola Marco ,\n\nGracias por tu interés en la posición de Programador master.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nTu evaluación general fue de 25%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', NULL, 'pendiente'),
(12, 18, 'Hola Marco ,\n\nGracias por tu interés en la posición de Programador master.\n\nDespués de revisar cuidadosamente tu perfil y aplicación, lamentamos informarte que en esta ocasión no podremos avanzar con tu candidatura.\n\nRazones principales:\n- Score bajo\n- No cumple requisitos\n\nTu evaluación general fue de 25%.\n\nAgradecemos el tiempo que invertiste en aplicar y te deseamos éxito en tu búsqueda profesional.\n\nSaludos cordiales,\nEquipo de RRHH', '[\"Score bajo\",\"No cumple requisitos\"]', 'Continúa desarrollando tu experiencia y habilidades', NULL, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_agentes`
--

CREATE TABLE `log_agentes` (
  `id_log` int(11) NOT NULL,
  `id_aplicacion` int(11) DEFAULT NULL,
  `agente_nombre` varchar(100) DEFAULT NULL,
  `estado` enum('pendiente','procesando','completado','error') DEFAULT NULL,
  `datos_entrada` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_entrada`)),
  `datos_salida` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_salida`)),
  `fecha_inicio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_fin` timestamp NULL DEFAULT NULL,
  `error_mensaje` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `log_agentes`
--

INSERT INTO `log_agentes` (`id_log`, `id_aplicacion`, `agente_nombre`, `estado`, `datos_entrada`, `datos_salida`, `fecha_inicio`, `fecha_fin`, `error_mensaje`) VALUES
(1, 11, 'segmentacion', 'procesando', '{\"id_aplicacion\":11}', NULL, '2025-12-03 17:57:10', NULL, NULL),
(2, 12, 'segmentacion', 'procesando', '{\"id_aplicacion\":12}', NULL, '2025-12-03 18:01:37', NULL, NULL),
(3, 1, 'calendarizacion', 'completado', '{\"accion\":\"crear\",\"id_aplicacion\":\"1\",\"fecha_base\":\"2025-12-06\"}', '{\"success\":true,\"id_entrevista\":1,\"opciones\":[{\"fecha\":\"2025-12-06\",\"hora\":\"10:00:00\"},{\"fecha\":\"2025-12-07\",\"hora\":\"14:00:00\"},{\"fecha\":\"2025-12-08\",\"hora\":\"16:00:00\"}]}', '2025-12-03 18:30:14', '2025-12-03 18:30:14', NULL),
(4, 12, 'feedback_rechazo', 'procesando', '{\"id_aplicacion\":\"12\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', NULL, '2025-12-03 18:30:38', NULL, NULL),
(5, 12, 'feedback_rechazo', 'completado', '{\"id_aplicacion\":\"12\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"success\":true,\"mensaje\":\"Feedback generado y enviado\",\"email\":\"sanati@gmail.com\"}', '2025-12-03 18:33:18', '2025-12-03 18:33:19', NULL),
(6, 1, 'calendarizacion', 'completado', '{\"accion\":\"crear\",\"id_aplicacion\":\"1\",\"fecha_base\":\"2025-12-06\"}', '{\"success\":true,\"id_entrevista\":1,\"opciones\":[{\"fecha\":\"2025-12-06\",\"hora\":\"10:00:00\"},{\"fecha\":\"2025-12-07\",\"hora\":\"14:00:00\"},{\"fecha\":\"2025-12-08\",\"hora\":\"16:00:00\"}]}', '2025-12-03 18:36:21', '2025-12-03 18:36:21', NULL),
(7, 12, 'validacion_proceso', 'procesando', '{\"id_aplicacion\":\"12\"}', NULL, '2025-12-03 23:05:31', NULL, NULL),
(8, 11, 'validacion_proceso', 'procesando', '{\"id_aplicacion\":\"11\"}', NULL, '2025-12-03 23:05:33', NULL, NULL),
(9, 12, 'validacion_proceso', 'procesando', '{\"id_aplicacion\":\"12\"}', NULL, '2025-12-03 23:05:38', NULL, NULL),
(10, 2, 'validacion_proceso', 'procesando', '{\"id_aplicacion\":\"2\"}', NULL, '2025-12-03 23:08:43', NULL, NULL),
(11, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:13:47', '2025-12-18 01:13:47', 'Unknown column \'id_candidato\' in \'field list\''),
(12, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:16:14', '2025-12-18 01:16:14', 'Unknown column \'id_candidato\' in \'field list\''),
(13, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:16:42', '2025-12-18 01:16:42', 'Unknown column \'id_candidato\' in \'field list\''),
(14, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:16:43', '2025-12-18 01:16:43', 'Unknown column \'id_candidato\' in \'field list\''),
(15, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:17:57', '2025-12-18 01:17:57', 'Unknown column \'id_candidato\' in \'field list\''),
(16, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:18:16', '2025-12-18 01:18:16', 'Unknown column \'id_candidato\' in \'field list\''),
(17, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:18:17', '2025-12-18 01:18:17', 'Unknown column \'id_candidato\' in \'field list\''),
(18, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:24:18', '2025-12-18 01:24:18', 'Unknown column \'id_candidato\' in \'field list\''),
(19, NULL, 'seguimiento_post_entrevista', 'procesando', '{\"id_entrevista\":16}', NULL, '2025-12-18 01:44:08', NULL, NULL),
(20, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 01:45:38', '2025-12-18 01:45:38', 'Unknown column \'id_candidato\' in \'field list\''),
(21, 14, 'segmentacion', 'procesando', '{\"id_aplicacion\":14}', NULL, '2025-12-18 01:47:24', NULL, NULL),
(22, 11, 'calendarizacion', 'completado', '{\"accion\":\"crear\",\"id_aplicacion\":\"11\",\"fecha_base\":\"2025-12-21\"}', '{\"success\":true,\"id_entrevista\":10,\"opciones\":[{\"fecha\":\"2025-12-21\",\"hora\":\"10:00:00\"},{\"fecha\":\"2025-12-22\",\"hora\":\"14:00:00\"},{\"fecha\":\"2025-12-23\",\"hora\":\"16:00:00\"}]}', '2025-12-18 01:49:02', '2025-12-18 01:49:02', NULL),
(23, 15, 'segmentacion', 'procesando', '{\"id_aplicacion\":15}', NULL, '2025-12-18 01:52:45', NULL, NULL),
(24, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:03:40', '2025-12-18 02:03:40', 'Unknown column \'id_candidato\' in \'field list\''),
(25, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:03:42', '2025-12-18 02:03:42', 'Unknown column \'id_candidato\' in \'field list\''),
(26, 14, 'feedback_rechazo', 'completado', '{\"id_aplicacion\":\"14\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"success\":true,\"mensaje\":\"Feedback generado y enviado\",\"email\":\"sanati@gmail.com\"}', '2025-12-18 02:06:58', '2025-12-18 02:06:58', NULL),
(27, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:16', '2025-12-18 02:07:16', 'Unknown column \'id_candidato\' in \'field list\''),
(28, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:17', '2025-12-18 02:07:17', 'Unknown column \'id_candidato\' in \'field list\''),
(29, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:17', '2025-12-18 02:07:17', 'Unknown column \'id_candidato\' in \'field list\''),
(30, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:18', '2025-12-18 02:07:18', 'Unknown column \'id_candidato\' in \'field list\''),
(31, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:18', '2025-12-18 02:07:18', 'Unknown column \'id_candidato\' in \'field list\''),
(32, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:18', '2025-12-18 02:07:18', 'Unknown column \'id_candidato\' in \'field list\''),
(33, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:18', '2025-12-18 02:07:18', 'Unknown column \'id_candidato\' in \'field list\''),
(34, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:18', '2025-12-18 02:07:18', 'Unknown column \'id_candidato\' in \'field list\''),
(35, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:18', '2025-12-18 02:07:18', 'Unknown column \'id_candidato\' in \'field list\''),
(36, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:19', '2025-12-18 02:07:19', 'Unknown column \'id_candidato\' in \'field list\''),
(37, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_candidato\' in \'field list\'\"}', '2025-12-18 02:07:19', '2025-12-18 02:07:19', 'Unknown column \'id_candidato\' in \'field list\''),
(38, 16, 'segmentacion', 'procesando', '{\"id_aplicacion\":16}', NULL, '2025-12-18 02:10:20', NULL, NULL),
(39, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:12:50', NULL, NULL),
(40, 16, 'feedback_rechazo', 'completado', '{\"id_aplicacion\":\"16\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"success\":true,\"mensaje\":\"Feedback generado y enviado\",\"email\":\"AUrelio@gmail.com\"}', '2025-12-18 02:15:06', '2025-12-18 02:15:06', NULL),
(41, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:15:10', NULL, NULL),
(42, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:15:11', NULL, NULL),
(43, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:15:12', NULL, NULL),
(44, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:18:53', NULL, NULL),
(45, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:27:55', NULL, NULL),
(46, 17, 'segmentacion', 'completado', '{\"id_aplicacion\":17}', '{\"success\":true,\"score_global\":25,\"score_tecnico\":0,\"score_blando\":50,\"clasificacion\":\"Bajo Fit\",\"segmento\":\"C\"}', '2025-12-18 02:29:53', '2025-12-18 02:29:53', NULL),
(47, 17, 'deteccion_riesgos', 'completado', '{\"id_aplicacion\":17}', '{\"success\":true,\"riesgos_encontrados\":2,\"score_riesgo\":75,\"riesgos\":[{\"tipo\":\"informacion_sospechosa\",\"severidad\":\"alta\",\"descripcion\":\"Score de evaluaci\\u00f3n extremadamente bajo (25%)\",\"evidencia\":\"Score global: 25%\"},{\"tipo\":\"inconsistencia\",\"severidad\":\"alta\",\"descripcion\":\"Habilidades t\\u00e9cnicas sospechosas o inadecuadas\",\"evidencia\":\"Habilidades t\\u00e9cnicas: no se\"}]}', '2025-12-18 02:29:53', '2025-12-18 02:29:53', NULL),
(48, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:30:27', NULL, NULL),
(49, 17, 'feedback_rechazo', 'completado', '{\"id_aplicacion\":\"17\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"success\":true,\"mensaje\":\"Feedback generado y enviado\",\"email\":\"AUrelio@gmail.com\"}', '2025-12-18 02:30:45', '2025-12-18 02:30:45', NULL),
(50, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:31:22', NULL, NULL),
(51, 15, 'feedback_rechazo', 'completado', '{\"id_aplicacion\":\"15\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"success\":true,\"mensaje\":\"Feedback generado y enviado\",\"email\":\"AUrelio@gmail.com\"}', '2025-12-18 02:35:38', '2025-12-18 02:35:38', NULL),
(52, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:50:53', '2025-12-18 02:50:53', 'Unknown column \'id_comunicacion\' in \'field list\''),
(53, 18, 'segmentacion', 'completado', '{\"id_aplicacion\":18}', '{\"success\":true,\"score_global\":25,\"score_tecnico\":0,\"score_blando\":50,\"clasificacion\":\"Bajo Fit\",\"segmento\":\"C\"}', '2025-12-18 02:51:31', '2025-12-18 02:51:31', NULL),
(54, 18, 'deteccion_riesgos', 'completado', '{\"id_aplicacion\":18}', '{\"success\":true,\"riesgos_encontrados\":2,\"score_riesgo\":75,\"riesgos\":[{\"tipo\":\"informacion_sospechosa\",\"severidad\":\"alta\",\"descripcion\":\"Score de evaluaci\\u00f3n extremadamente bajo (25%)\",\"evidencia\":\"Score global: 25%\"},{\"tipo\":\"inconsistencia\",\"severidad\":\"alta\",\"descripcion\":\"Habilidades t\\u00e9cnicas sospechosas o inadecuadas\",\"evidencia\":\"Habilidades t\\u00e9cnicas: no se\"}]}', '2025-12-18 02:51:31', '2025-12-18 02:51:31', NULL),
(55, 18, 'feedback_rechazo', 'error', '{\"id_aplicacion\":\"18\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:51:52', '2025-12-18 02:51:52', 'Unknown column \'id_comunicacion\' in \'field list\''),
(56, NULL, 'feedback_no_seleccionados', 'error', '[]', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:51:54', '2025-12-18 02:51:54', 'Unknown column \'id_comunicacion\' in \'field list\''),
(57, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:54:14', NULL, NULL),
(58, 18, 'feedback_rechazo', 'error', '{\"id_aplicacion\":\"18\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:54:21', '2025-12-18 02:54:21', 'Unknown column \'id_comunicacion\' in \'field list\''),
(59, 18, 'feedback_rechazo', 'error', '{\"id_aplicacion\":\"18\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:54:35', '2025-12-18 02:54:35', 'Unknown column \'id_comunicacion\' in \'field list\''),
(60, 18, 'feedback_rechazo', 'error', '{\"id_aplicacion\":\"18\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:54:39', '2025-12-18 02:54:40', 'Unknown column \'id_comunicacion\' in \'field list\''),
(61, 18, 'feedback_rechazo', 'error', '{\"id_aplicacion\":\"18\",\"razones\":[\"Score bajo\",\"No cumple requisitos\"]}', '{\"error\":\"Unknown column \'id_comunicacion\' in \'field list\'\"}', '2025-12-18 02:55:49', '2025-12-18 02:55:49', 'Unknown column \'id_comunicacion\' in \'field list\''),
(62, NULL, 'feedback_no_seleccionados', 'procesando', '[]', NULL, '2025-12-18 02:55:56', NULL, NULL),
(63, 19, 'segmentacion', 'completado', '{\"id_aplicacion\":19}', '{\"success\":true,\"score_global\":55,\"score_tecnico\":60,\"score_blando\":50,\"clasificacion\":\"Medio Fit\",\"segmento\":\"C\"}', '2025-12-18 03:00:14', '2025-12-18 03:00:14', NULL),
(64, 19, 'deteccion_riesgos', 'completado', '{\"id_aplicacion\":19}', '{\"success\":true,\"riesgos_encontrados\":0,\"score_riesgo\":0,\"riesgos\":[]}', '2025-12-18 03:00:14', '2025-12-18 03:00:14', NULL),
(65, 19, 'calendarizacion', 'completado', '{\"accion\":\"crear\",\"id_aplicacion\":\"19\",\"fecha_base\":\"2025-12-21\"}', '{\"success\":true,\"id_entrevista\":19,\"opciones\":[{\"fecha\":\"2025-12-21\",\"hora\":\"10:00:00\"},{\"fecha\":\"2025-12-22\",\"hora\":\"14:00:00\"},{\"fecha\":\"2025-12-23\",\"hora\":\"16:00:00\"}]}', '2025-12-18 03:00:39', '2025-12-18 03:00:39', NULL),
(66, 20, 'segmentacion', 'completado', '{\"id_aplicacion\":20}', '{\"success\":true,\"score_global\":75,\"score_tecnico\":100,\"score_blando\":50,\"clasificacion\":\"Alto Fit\",\"segmento\":\"B\"}', '2025-12-18 03:02:39', '2025-12-18 03:02:39', NULL),
(67, 20, 'deteccion_riesgos', 'completado', '{\"id_aplicacion\":20}', '{\"success\":true,\"riesgos_encontrados\":0,\"score_riesgo\":0,\"riesgos\":[]}', '2025-12-18 03:02:39', '2025-12-18 03:02:39', NULL),
(68, 20, 'calendarizacion', 'completado', '{\"accion\":\"crear\",\"id_aplicacion\":\"20\",\"fecha_base\":\"2025-12-21\"}', '{\"success\":true,\"id_entrevista\":20,\"opciones\":[{\"fecha\":\"2025-12-21\",\"hora\":\"10:00:00\"},{\"fecha\":\"2025-12-22\",\"hora\":\"14:00:00\"},{\"fecha\":\"2025-12-23\",\"hora\":\"16:00:00\"}]}', '2025-12-18 03:02:51', '2025-12-18 03:02:51', NULL),
(69, NULL, 'seguimiento_post_entrevista', 'procesando', '{\"id_entrevista\":20}', NULL, '2025-12-18 03:09:27', NULL, NULL),
(70, 20, 'seguimiento_ingreso', 'procesando', '{\"accion\":\"iniciar\",\"id_aplicacion\":\"20\",\"fecha_ingreso\":\"2025-12-25\"}', NULL, '2025-12-18 03:10:19', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_entrevista`
--

CREATE TABLE `notificaciones_entrevista` (
  `id_notificacion` int(11) NOT NULL,
  `id_entrevista` int(11) DEFAULT NULL,
  `tipo` enum('confirmacion','recordatorio','reprogramacion') DEFAULT NULL,
  `canal` enum('email','whatsapp','sms') DEFAULT NULL,
  `destinatario` varchar(255) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `estado` enum('pendiente','enviada','fallida') DEFAULT NULL,
  `fecha_envio` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones_entrevista`
--

INSERT INTO `notificaciones_entrevista` (`id_notificacion`, `id_entrevista`, `tipo`, `canal`, `destinatario`, `mensaje`, `estado`, `fecha_envio`) VALUES
(1, 1, 'confirmacion', 'email', 'holamundo@gmail.com', 'Hola Francisco,\n\nTe hemos programado una entrevista para la vacante: Programador \n\nOpciones disponibles:\n1. 2025-12-06 a las 10:00\n2. 2025-12-07 a las 14:00\n3. 2025-12-08 a las 16:00\n\nPor favor, confirma tu disponibilidad ingresando a tu portal de candidatos.\n\nSaludos,\nEquipo de RRHH', 'pendiente', NULL),
(2, 1, 'confirmacion', 'email', 'holamundo@gmail.com', 'Hola Francisco,\n\nTe hemos programado una entrevista para la vacante: Programador \n\nOpciones disponibles:\n1. 2025-12-06 a las 10:00\n2. 2025-12-07 a las 14:00\n3. 2025-12-08 a las 16:00\n\nPor favor, confirma tu disponibilidad ingresando a tu portal de candidatos.\n\nSaludos,\nEquipo de RRHH', 'pendiente', NULL),
(3, 10, 'confirmacion', 'email', 'alexis@gmail.com', 'Hola Alexis,\n\nTe hemos programado una entrevista para la vacante: Programador \n\nOpciones disponibles:\n1. 2025-12-21 a las 10:00\n2. 2025-12-22 a las 14:00\n3. 2025-12-23 a las 16:00\n\nPor favor, confirma tu disponibilidad ingresando a tu portal de candidatos.\n\nSaludos,\nEquipo de RRHH', 'pendiente', NULL),
(4, 19, 'confirmacion', 'email', 'alexis@gmail.com', 'Hola Alexis,\n\nTe hemos programado una entrevista para la vacante: Programador master\n\nOpciones disponibles:\n1. 2025-12-21 a las 10:00\n2. 2025-12-22 a las 14:00\n3. 2025-12-23 a las 16:00\n\nPor favor, confirma tu disponibilidad ingresando a tu portal de candidatos.\n\nSaludos,\nEquipo de RRHH', 'pendiente', NULL),
(5, 20, 'confirmacion', 'email', 'holamundo@gmail.com', 'Hola Francisco,\n\nTe hemos programado una entrevista para la vacante: Programador master\n\nOpciones disponibles:\n1. 2025-12-21 a las 10:00\n2. 2025-12-22 a las 14:00\n3. 2025-12-23 a las 16:00\n\nPor favor, confirma tu disponibilidad ingresando a tu portal de candidatos.\n\nSaludos,\nEquipo de RRHH', 'pendiente', NULL);

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
  `notificaciones_enviadas` text DEFAULT NULL,
  `tareas_pendientes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tareas_pendientes`)),
  `fecha_limite_ingreso` date DEFAULT NULL,
  `recordatorios_enviados` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `onboarding`
--

INSERT INTO `onboarding` (`id_onboarding`, `id_aplicacion`, `fecha_ingreso`, `doc_contratacion`, `config_equipos`, `induccion`, `entrenamiento`, `notificaciones_enviadas`, `tareas_pendientes`, `fecha_limite_ingreso`, `recordatorios_enviados`) VALUES
(1, 8, '2025-11-25', 'Completado', 'En proceso', 'Pendiente', 'Pendiente', NULL, NULL, NULL, 0),
(2, 20, '2025-12-25', 'Pendiente', 'Pendiente', 'Pendiente', 'Pendiente', NULL, '{\"doc_contratacion\":{\"nombre\":\"Documentaci\\u00f3n\",\"estado\":\"Pendiente\"},\"config_equipos\":{\"nombre\":\"Config. Equipos\",\"estado\":\"Pendiente\"},\"induccion\":{\"nombre\":\"Inducci\\u00f3n\",\"estado\":\"Pendiente\"},\"entrenamiento\":{\"nombre\":\"Entrenamiento\",\"estado\":\"Pendiente\"}}', '2025-12-24', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reclutadores`
--

CREATE TABLE `reclutadores` (
  `idreclutadores` int(15) UNSIGNED NOT NULL,
  `NombreCompleto` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `estados` varchar(30) NOT NULL DEFAULT 'Activo',
  `roles` varchar(30) NOT NULL DEFAULT 'Reclutadora'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reclutadores`
--

INSERT INTO `reclutadores` (`idreclutadores`, `NombreCompleto`, `email`, `password`, `estados`, `roles`) VALUES
(1, 'Jonatan', 'jonatan@gmail.com', '123456', 'Activo', 'Reclutadora');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resultados_entrevista`
--

CREATE TABLE `resultados_entrevista` (
  `idResultado` int(11) NOT NULL,
  `idEntrevista` int(11) NOT NULL COMMENT 'FK a entrevistas.id_entrevista',
  `resultado` enum('Aceptacion','SiguienteFase','Rechazo') NOT NULL,
  `salario_ofrecido` decimal(10,2) DEFAULT NULL,
  `fecha_siguiente` date DEFAULT NULL,
  `hora_siguiente` time DEFAULT NULL,
  `tipo_entrevista` varchar(100) DEFAULT NULL,
  `feedback_area` varchar(100) DEFAULT NULL,
  `feedback_detalle` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resultados_entrevista`
--

INSERT INTO `resultados_entrevista` (`idResultado`, `idEntrevista`, `resultado`, `salario_ofrecido`, `fecha_siguiente`, `hora_siguiente`, `tipo_entrevista`, `feedback_area`, `feedback_detalle`, `fecha_registro`) VALUES
(1, 16, 'Rechazo', NULL, NULL, NULL, NULL, 'en todo', 'en todo', '2025-12-17 20:12:42'),
(2, 20, 'Aceptacion', 25000.00, NULL, NULL, NULL, NULL, NULL, '2025-12-17 21:09:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `riesgos_detectados`
--

CREATE TABLE `riesgos_detectados` (
  `id_riesgo` int(11) NOT NULL,
  `id_aplicacion` int(11) DEFAULT NULL,
  `tipo_riesgo` enum('inconsistencia','gap_temporal','informacion_sospechosa','credenciales_dudosas') DEFAULT NULL,
  `severidad` enum('baja','media','alta') DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `evidencia` text DEFAULT NULL,
  `score_riesgo` int(11) DEFAULT NULL,
  `fecha_deteccion` timestamp NOT NULL DEFAULT current_timestamp(),
  `revisado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `riesgos_detectados`
--

INSERT INTO `riesgos_detectados` (`id_riesgo`, `id_aplicacion`, `tipo_riesgo`, `severidad`, `descripcion`, `evidencia`, `score_riesgo`, `fecha_deteccion`, `revisado`) VALUES
(1, 17, 'informacion_sospechosa', 'alta', 'Score de evaluación extremadamente bajo (25%)', 'Score global: 25%', 75, '2025-12-18 02:29:53', 0),
(2, 17, 'inconsistencia', 'alta', 'Habilidades técnicas sospechosas o inadecuadas', 'Habilidades técnicas: no se', 75, '2025-12-18 02:29:53', 0),
(3, 18, 'informacion_sospechosa', 'alta', 'Score de evaluación extremadamente bajo (25%)', 'Score global: 25%', 75, '2025-12-18 02:51:31', 0),
(4, 18, 'inconsistencia', 'alta', 'Habilidades técnicas sospechosas o inadecuadas', 'Habilidades técnicas: no se', 75, '2025-12-18 02:51:31', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vacantes`
--

CREATE TABLE `vacantes` (
  `id_vacante` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `empresa` varchar(255) NOT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `requisitos` text NOT NULL,
  `salario` varchar(100) DEFAULT NULL,
  `tipo_trabajo` varchar(50) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `fecha_publicacion` date NOT NULL,
  `fechaApertura` date DEFAULT NULL,
  `fechaCierre` date DEFAULT NULL,
  `responsable` varchar(150) DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'Abierta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vacantes`
--

INSERT INTO `vacantes` (`id_vacante`, `titulo`, `empresa`, `departamento`, `ubicacion`, `descripcion`, `requisitos`, `salario`, `tipo_trabajo`, `tipo`, `fecha_publicacion`, `fechaApertura`, `fechaCierre`, `responsable`, `fecha_creacion`, `estado`) VALUES
(1, 'Programador ', 'Empresa ABC', 'Empresa ABC', 'Guadalajara', 'Buscamos un Analista de Datos junior con pasión por la extracción de información significativa de grandes volúmenes de datos. Serás clave en la toma de decisiones estratégicas.', 'Experiencia mínima de 1 año en análisis de datos.Dominio de SQL y Excel avanzado.Conocimiento de Power BI o Tableau.\r\nHabilidad para comunicar resultados a equipos no técnicos.', '$18,000 - $25,000 MXN', 'Tiempo Completo', 'Tiempo Completo', '2025-11-11', '2025-11-11', '2025-11-11', NULL, '2025-11-11', 'Abierta'),
(2, 'Desarrollo de software', 'Empresa DEF', 'Empresa DEF', 'Hidalgo', 'Desarrollador Full Stack con enfoque en tecnologías JavaScript (Node.js y React). Participarás en todo el ciclo de vida del desarrollo de nuestras aplicaciones críticas.', '3+ años de experiencia en desarrollo web.\r\nExperiencia sólida con Node.js, Express y bases de datos NoSQL (MongoDB).Conocimiento de AWS o Google Cloud Platform.\r\nCapacidad para trabajar de forma remota.', '$30,000 - $45,000 MXN', 'Remoto', 'Remoto', '2025-11-06', '2025-11-06', '2025-11-06', NULL, '2025-11-06', 'Abierta'),
(3, 'Analista de negocio', 'Empresa GHI', 'Empresa GHI', 'Monterrey', 'Posición ideal para un profesional con experiencia en la identificación de necesidades de negocio y la traducción de estas en requerimientos técnicos funcionales.', 'Certificación CBAP (deseable).\r\nExperiencia con metodologías Agile/Scrum.\r\nExcelentes habilidades de comunicación y negociación.\r\nInglés avanzado indispensable.', '$22,000 - $32,000 MXN', 'Híbrido', 'Híbrido', '2025-11-09', '2025-11-09', '2025-11-09', NULL, '2025-11-09', 'Abierta'),
(4, 'Diseñador UX/UI Senior', 'Tecnologías de la Información', 'Tecnologías JKL', 'Ciudad de México', 'Liderarás el diseño de la experiencia de usuario de nuestra plataforma principal, asegurando interfaces intuitivas y estéticamente agradables.', '5+ años de experiencia en diseño UX/UI.\r\nDominio de herramientas de diseño (Figma, Sketch, Adobe XD).\r\nPortfolio comprobable con proyectos complejos.\r\nExperiencia realizando pruebas de usabilidad.', '2500', 'Tiempo completo', 'Remoto', '2025-11-11', '2025-11-11', '2025-11-11', NULL, '2025-11-11', 'Activa'),
(5, 'Programador master', 'Marketing', NULL, 'Ciudad de México', 'programar backend frontend base de datos todos los días para todos los departamentos', 'conocimiento en JavaScript', '200000', 'Tiempo completo', NULL, '2025-11-11', NULL, NULL, NULL, NULL, 'Activa');

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
-- Indices de la tabla `canal_comunicacion`
--
ALTER TABLE `canal_comunicacion`
  ADD PRIMARY KEY (`idComunicacion`),
  ADD KEY `fk_comunicacion_candidato` (`idClientes`);

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
-- Indices de la tabla `disponibilidaddelequipo`
--
ALTER TABLE `disponibilidaddelequipo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_disponibilidad_candidato` (`idClientes`);

--
-- Indices de la tabla `disponibilidades_rrhh`
--
ALTER TABLE `disponibilidades_rrhh`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_disponibilidad_reclutador` (`idreclutadores`);

--
-- Indices de la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  ADD PRIMARY KEY (`id_entrevista`),
  ADD UNIQUE KEY `id_aplicacion` (`id_aplicacion`),
  ADD KEY `fk_e_cli` (`idClientes`),
  ADD KEY `fk_e_vac` (`idVacante`),
  ADD KEY `fk_e_rec` (`idReclutador`);

--
-- Indices de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD PRIMARY KEY (`id_evaluacion`),
  ADD UNIQUE KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `feedback_entrevista`
--
ALTER TABLE `feedback_entrevista`
  ADD PRIMARY KEY (`id_feedback`),
  ADD KEY `id_entrevista` (`id_entrevista`);

--
-- Indices de la tabla `feedback_rechazo`
--
ALTER TABLE `feedback_rechazo`
  ADD PRIMARY KEY (`id_feedback`),
  ADD KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `log_agentes`
--
ALTER TABLE `log_agentes`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `notificaciones_entrevista`
--
ALTER TABLE `notificaciones_entrevista`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_entrevista` (`id_entrevista`);

--
-- Indices de la tabla `onboarding`
--
ALTER TABLE `onboarding`
  ADD PRIMARY KEY (`id_onboarding`),
  ADD UNIQUE KEY `id_aplicacion` (`id_aplicacion`);

--
-- Indices de la tabla `reclutadores`
--
ALTER TABLE `reclutadores`
  ADD PRIMARY KEY (`idreclutadores`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `resultados_entrevista`
--
ALTER TABLE `resultados_entrevista`
  ADD PRIMARY KEY (`idResultado`),
  ADD KEY `fk_resultado_entrevista` (`idEntrevista`);

--
-- Indices de la tabla `riesgos_detectados`
--
ALTER TABLE `riesgos_detectados`
  ADD PRIMARY KEY (`id_riesgo`),
  ADD KEY `id_aplicacion` (`id_aplicacion`);

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
  MODIFY `id_aplicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `canal_comunicacion`
--
ALTER TABLE `canal_comunicacion`
  MODIFY `idComunicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `candidatos`
--
ALTER TABLE `candidatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `comentarios_validacion`
--
ALTER TABLE `comentarios_validacion`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `disponibilidaddelequipo`
--
ALTER TABLE `disponibilidaddelequipo`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `disponibilidades_rrhh`
--
ALTER TABLE `disponibilidades_rrhh`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  MODIFY `id_entrevista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  MODIFY `id_evaluacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `feedback_entrevista`
--
ALTER TABLE `feedback_entrevista`
  MODIFY `id_feedback` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `feedback_rechazo`
--
ALTER TABLE `feedback_rechazo`
  MODIFY `id_feedback` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `log_agentes`
--
ALTER TABLE `log_agentes`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `notificaciones_entrevista`
--
ALTER TABLE `notificaciones_entrevista`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `onboarding`
--
ALTER TABLE `onboarding`
  MODIFY `id_onboarding` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `reclutadores`
--
ALTER TABLE `reclutadores`
  MODIFY `idreclutadores` int(15) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `resultados_entrevista`
--
ALTER TABLE `resultados_entrevista`
  MODIFY `idResultado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `riesgos_detectados`
--
ALTER TABLE `riesgos_detectados`
  MODIFY `id_riesgo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `vacantes`
--
ALTER TABLE `vacantes`
  MODIFY `id_vacante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Filtros para la tabla `canal_comunicacion`
--
ALTER TABLE `canal_comunicacion`
  ADD CONSTRAINT `fk_comunicacion_candidato` FOREIGN KEY (`idClientes`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `comentarios_validacion`
--
ALTER TABLE `comentarios_validacion`
  ADD CONSTRAINT `comentarios_validacion_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `disponibilidaddelequipo`
--
ALTER TABLE `disponibilidaddelequipo`
  ADD CONSTRAINT `fk_disponibilidad_candidato` FOREIGN KEY (`idClientes`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `disponibilidades_rrhh`
--
ALTER TABLE `disponibilidades_rrhh`
  ADD CONSTRAINT `fk_disponibilidad_reclutador` FOREIGN KEY (`idreclutadores`) REFERENCES `reclutadores` (`idreclutadores`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `entrevistas`
--
ALTER TABLE `entrevistas`
  ADD CONSTRAINT `entrevistas_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_e_cli` FOREIGN KEY (`idClientes`) REFERENCES `candidatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_e_rec` FOREIGN KEY (`idReclutador`) REFERENCES `reclutadores` (`idreclutadores`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_e_vac` FOREIGN KEY (`idVacante`) REFERENCES `vacantes` (`id_vacante`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `evaluaciones`
--
ALTER TABLE `evaluaciones`
  ADD CONSTRAINT `evaluaciones_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `feedback_entrevista`
--
ALTER TABLE `feedback_entrevista`
  ADD CONSTRAINT `feedback_entrevista_ibfk_1` FOREIGN KEY (`id_entrevista`) REFERENCES `entrevistas` (`id_entrevista`);

--
-- Filtros para la tabla `feedback_rechazo`
--
ALTER TABLE `feedback_rechazo`
  ADD CONSTRAINT `feedback_rechazo_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`);

--
-- Filtros para la tabla `log_agentes`
--
ALTER TABLE `log_agentes`
  ADD CONSTRAINT `log_agentes_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`);

--
-- Filtros para la tabla `notificaciones_entrevista`
--
ALTER TABLE `notificaciones_entrevista`
  ADD CONSTRAINT `notificaciones_entrevista_ibfk_1` FOREIGN KEY (`id_entrevista`) REFERENCES `entrevistas` (`id_entrevista`);

--
-- Filtros para la tabla `onboarding`
--
ALTER TABLE `onboarding`
  ADD CONSTRAINT `onboarding_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resultados_entrevista`
--
ALTER TABLE `resultados_entrevista`
  ADD CONSTRAINT `fk_resultado_entrevista` FOREIGN KEY (`idEntrevista`) REFERENCES `entrevistas` (`id_entrevista`) ON DELETE CASCADE;

--
-- Filtros para la tabla `riesgos_detectados`
--
ALTER TABLE `riesgos_detectados`
  ADD CONSTRAINT `riesgos_detectados_ibfk_1` FOREIGN KEY (`id_aplicacion`) REFERENCES `aplicaciones` (`id_aplicacion`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

